import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:geolocator/geolocator.dart';
import 'package:http/http.dart' as http;
import 'package:location/location.dart' as loc;
import 'package:permission_handler/permission_handler.dart' hide ServiceStatus;
import 'package:workmanager/workmanager.dart';
import '../../widget/LocationAlwaysSheet.dart';
import '../notification/NotificationService.dart';

class LiveTrackingService {
  static final LiveTrackingService _instance = LiveTrackingService._internal();
  factory LiveTrackingService() => _instance;
  LiveTrackingService._internal();

  static const MethodChannel _nativeChannel = MethodChannel('com.loewix.teknisi/live_tracking');
  static const String updateUrl = 'https://jadwal.id-giti.com/update_teknisi_location.php';

  // Jam operasional pemantauan wajib: 06:00 s/d 20:00 WIB
  static const int workStartHourWib = 6;
  static const int workEndHourWib = 20;

  String? _teknisiId;
  StreamSubscription<Position>? _positionSubscription;
  Timer? _heartbeatTimer;
  bool _isTracking = false;
  Position? _lastSentPosition;

  bool get isTracking => _isTracking;

  /// Cek apakah waktu saat ini berada di jam kerja 06:00 - 20:00 WIB (UTC+7)
  static bool isWithinWorkingHoursWib() {
    final wibNow = DateTime.now().toUtc().add(const Duration(hours: 7));
    return wibNow.hour >= workStartHourWib && wibNow.hour < workEndHourWib;
  }

  /// Start live location tracking automatically (aktif penuh 06:00 - 20:00 WIB & tahan swipe-close)
  Future<void> startTracking(String teknisiId) async {
    if (teknisiId.isEmpty || teknisiId == 'no role') return;
    _teknisiId = teknisiId;

    // 1. Pastikan GPS & izin lokasi siap
    await _ensureLocationReady();

    // 2. Di Android: jalankan Native Foreground Service (tetap hidup walau app di-swipe close)
    if (Platform.isAndroid) {
      try {
        await _nativeChannel.invokeMethod('startTracking', {'teknisiId': teknisiId});
      } catch (e) {
        debugPrint('⚠️ Native startTracking error: $e');
      }

      // Daftarkan WorkManager 15-menit sebagai jaring pengaman tambahan di jam 06:00 - 20:00 WIB
      try {
        await Workmanager().initialize(callbackDispatcher, isInDebugMode: false);
        await Workmanager().registerPeriodicTask(
          'liveTrackingWatchdog',
          'liveTrackingWatchdog',
          inputData: {'teknisiId': teknisiId},
          frequency: const Duration(minutes: 15),
          constraints: Constraints(networkType: NetworkType.connected),
          existingWorkPolicy: ExistingWorkPolicy.keep,
        );
      } catch (_) {}

      _isTracking = true;

      // Kirim titik segar & tetap jalankan stream FusedLocation saat app aktif/minimized (optimal di dalam gedung)
      if (isWithinWorkingHoursWib()) {
        unawaited(_captureAndSendCurrentPosition());
      }
      _startPositionStream();
      _startHeartbeatTimer();
      return;
    }

    // Fallback non-Android (iOS)
    if (_isTracking) return;
    _isTracking = true;
    if (isWithinWorkingHoursWib()) {
      await _captureAndSendCurrentPosition();
    }
    _startPositionStream();
    _startHeartbeatTimer();
  }

  /// Sync ulang service native (dipanggil setelah izin lokasi / baterai baru diberikan)
  Future<void> refreshNativeServiceIfNeeded() async {
    if (!Platform.isAndroid || _teknisiId == null || _teknisiId!.isEmpty) return;
    try {
      await _nativeChannel.invokeMethod('startTracking', {'teknisiId': _teknisiId});
    } catch (_) {}
  }

  /// Stop tracking (hanya dipanggil saat Logout)
  Future<void> stopTracking() async {
    _isTracking = false;
    _teknisiId = null;
    await _positionSubscription?.cancel();
    _positionSubscription = null;
    _heartbeatTimer?.cancel();
    _heartbeatTimer = null;

    if (Platform.isAndroid) {
      try {
        await _nativeChannel.invokeMethod('stopTracking');
      } catch (_) {}
      try {
        await Workmanager().cancelByUniqueName('liveTrackingWatchdog');
      } catch (_) {}
    }
  }

  /// Check whether "Allow all the time" permission is granted
  Future<bool> isLocationAlwaysGranted() async {
    try {
      final status = await Permission.locationAlways.status;
      return status.isGranted;
    } catch (e) {
      debugPrint('⚠️ LiveTrackingService isLocationAlwaysGranted error: $e');
      return false;
    }
  }

  /// Check whether battery optimization is unrestricted ("Tanpa Batasan")
  Future<bool> isIgnoringBatteryOptimizations() async {
    if (!Platform.isAndroid) return true;
    try {
      final res = await _nativeChannel.invokeMethod<bool>('isIgnoringBatteryOptimizations');
      return res ?? false;
    } catch (_) {
      try {
        return await Permission.ignoreBatteryOptimizations.isGranted;
      } catch (_) {
        return true;
      }
    }
  }

  /// Tampilkan dialog 1-klik resmi Android untuk menonaktifkan pembatasan baterai
  Future<bool> requestIgnoreBatteryOptimizations() async {
    if (!Platform.isAndroid) return true;
    try {
      await _nativeChannel.invokeMethod('requestIgnoreBatteryOptimizations');
    } catch (_) {
      try {
        await Permission.ignoreBatteryOptimizations.request();
      } catch (_) {}
    }
    return await isIgnoringBatteryOptimizations();
  }

  /// Request "Allow all the time" permission, opening settings if needed
  Future<bool> requestLocationAlways() async {
    try {
      final opened = await openAppSettings();
      if (!opened) {
        await Geolocator.openAppSettings();
      }
      return await isLocationAlwaysGranted();
    } catch (e) {
      debugPrint('⚠️ LiveTrackingService requestLocationAlways error: $e');
      return false;
    }
  }

  /// Show education bottom sheet to guide user to select "Sepanjang Waktu"
  Future<bool> promptLocationAlways(BuildContext context) async {
    return await LocationAlwaysSheet.show(context);
  }

  Future<void> _ensureLocationReady() async {
    try {
      final locPlugin = loc.Location();
      bool serviceEnabled = await locPlugin.serviceEnabled();
      if (!serviceEnabled) {
        serviceEnabled = await locPlugin.requestService();
      }

      PermissionStatus permission = await Permission.location.status;
      if (permission.isDenied) {
        permission = await Permission.location.request();
      }
      if (permission.isGranted) {
        final alwaysStatus = await Permission.locationAlways.status;
        if (!alwaysStatus.isGranted) {
          await Permission.locationAlways.request();
        }
      }
    } catch (e) {
      debugPrint('⚠️ LiveTrackingService ready check error: $e');
    }
  }

  void _startPositionStream() {
    _positionSubscription?.cancel();

    const locationSettings = LocationSettings(
      accuracy: LocationAccuracy.high,
      distanceFilter: 15,
    );

    _positionSubscription = Geolocator.getPositionStream(
      locationSettings: locationSettings,
    ).listen(
      (Position position) {
        if (isWithinWorkingHoursWib()) {
          _sendPositionToServer(position);
        }
      },
      onError: (error) {
        debugPrint('⚠️ LiveTrackingService position stream error: $error');
      },
    );
  }

  void _startHeartbeatTimer() {
    _heartbeatTimer?.cancel();
    _heartbeatTimer = Timer.periodic(const Duration(seconds: 25), (_) async {
      if (!_isTracking || !isWithinWorkingHoursWib()) return;
      await _captureAndSendCurrentPosition();
    });
  }

  Future<void> _captureAndSendCurrentPosition() async {
    if (!isWithinWorkingHoursWib()) return;
    try {
      Position? position;
      try {
        position = await Geolocator.getCurrentPosition(
          desiredAccuracy: LocationAccuracy.high,
          timeLimit: const Duration(seconds: 8),
        );
      } catch (_) {
        position = await Geolocator.getLastKnownPosition();
      }
      if (position != null) {
        await _sendPositionToServer(position);
      }
    } catch (e) {
      debugPrint('⚠️ LiveTrackingService capture error: $e');
    }
  }

  Future<void> _sendPositionToServer(Position position) async {
    if (_teknisiId == null || _teknisiId!.isEmpty || !isWithinWorkingHoursWib()) return;

    if (_lastSentPosition != null) {
      final distance = Geolocator.distanceBetween(
        _lastSentPosition!.latitude,
        _lastSentPosition!.longitude,
        position.latitude,
        position.longitude,
      );
      if (distance < 5 && DateTime.now().difference(_lastSentPosition!.timestamp).inSeconds < 15) {
        return;
      }
    }

    _lastSentPosition = position;

    try {
      final payload = {
        'teknisi_id': _teknisiId,
        'latitude': position.latitude.toString(),
        'longitude': position.longitude.toString(),
        'accuracy': position.accuracy.toString(),
        'speed': (position.speed * 3.6).toStringAsFixed(1),
        'heading': position.heading.toString(),
        'is_mock': position.isMocked ? 1 : 0,
      };

      await http.post(
        Uri.parse(updateUrl),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode(payload),
      ).timeout(const Duration(seconds: 10));
    } catch (e) {
      debugPrint('⚠️ LiveTracking: Send error $e');
    }
  }
}
