import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:geolocator/geolocator.dart';
import 'package:http/http.dart' as http;
import 'package:location/location.dart' as loc;
import 'package:permission_handler/permission_handler.dart' hide ServiceStatus;
import '../../widget/LocationAlwaysSheet.dart';

class LiveTrackingService {
  static final LiveTrackingService _instance = LiveTrackingService._internal();
  factory LiveTrackingService() => _instance;
  LiveTrackingService._internal();

  static const String _updateUrl = 'https://jadwal.id-giti.com/update_teknisi_location.php';
  
  String? _teknisiId;
  StreamSubscription<Position>? _positionSubscription;
  Timer? _heartbeatTimer;
  bool _isTracking = false;
  Position? _lastSentPosition;

  bool get isTracking => _isTracking;

  /// Start live location tracking automatically
  Future<void> startTracking(String teknisiId) async {
    if (teknisiId.isEmpty) return;
    _teknisiId = teknisiId;

    if (_isTracking) {
      debugPrint('📍 LiveTrackingService: Already tracking for teknisi $teknisiId');
      return;
    }

    debugPrint('🚀 LiveTrackingService: Starting auto-tracking for teknisi $teknisiId...');
    _isTracking = true;

    // 1. Ensure permissions & GPS enabled
    await _ensureLocationReady();

    // 2. Start ongoing persistent notification (Anti-Kill on Android)
    await _startForegroundNotification();

    // 3. Immediately send first position
    await _captureAndSendCurrentPosition();

    // 4. Stream position changes (every ~15 meters)
    _startPositionStream();

    // 5. Heartbeat timer (every 25 seconds for stationary updates)
    _startHeartbeatTimer();
  }

  /// Stop tracking (e.g. on logout)
  Future<void> stopTracking() async {
    _isTracking = false;
    _teknisiId = null;
    await _positionSubscription?.cancel();
    _positionSubscription = null;
    _heartbeatTimer?.cancel();
    _heartbeatTimer = null;
    await _cancelNotification();
    debugPrint('🛑 LiveTrackingService: Stopped.');
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

  /// Request "Allow all the time" permission, opening settings if needed
  Future<bool> requestLocationAlways() async {
    try {
      final status = await Permission.locationAlways.request();
      if (status.isGranted) return true;
      await openAppSettings();
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
        // Request background permission if supported
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
      distanceFilter: 15, // update every 15 meters
    );

    _positionSubscription = Geolocator.getPositionStream(
      locationSettings: locationSettings,
    ).listen(
      (Position position) {
        _sendPositionToServer(position);
      },
      onError: (error) {
        debugPrint('⚠️ LiveTrackingService position stream error: $error');
      },
    );
  }

  void _startHeartbeatTimer() {
    _heartbeatTimer?.cancel();
    // Send heartbeat every 25 seconds so server knows technician is online even if stationary
    _heartbeatTimer = Timer.periodic(const Duration(seconds: 25), (_) async {
      if (!_isTracking) return;
      await _captureAndSendCurrentPosition();
    });
  }

  Future<void> _captureAndSendCurrentPosition() async {
    try {
      Position? position = await Geolocator.getLastKnownPosition();
      if (position == null) {
        position = await Geolocator.getCurrentPosition(
          desiredAccuracy: LocationAccuracy.medium,
          timeLimit: const Duration(seconds: 8),
        );
      }
      if (position != null) {
        await _sendPositionToServer(position);
      }
    } catch (e) {
      debugPrint('⚠️ LiveTrackingService capture error: $e');
    }
  }

  Future<void> _sendPositionToServer(Position position) async {
    if (_teknisiId == null || _teknisiId!.isEmpty) return;

    // Avoid duplicate rapid sends if location hasn't changed at all
    if (_lastSentPosition != null) {
      final distance = Geolocator.distanceBetween(
        _lastSentPosition!.latitude,
        _lastSentPosition!.longitude,
        position.latitude,
        position.longitude,
      );
      // If moved less than 5m and less than 15s since last send, skip
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
        'speed': (position.speed * 3.6).toStringAsFixed(1), // m/s to km/h
        'heading': position.heading.toString(),
        'is_mock': position.isMocked ? 1 : 0,
      };

      final response = await http.post(
        Uri.parse(_updateUrl),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode(payload),
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode >= 200 && response.statusCode < 300) {
        debugPrint('📍 LiveTracking: Sent pos (${position.latitude}, ${position.longitude}) for #$_teknisiId');
      } else {
        debugPrint('⚠️ LiveTracking: Server response ${response.statusCode}');
      }
    } catch (e) {
      debugPrint('⚠️ LiveTracking: Send error $e');
    }
  }

  /// Persistent Notification so Android never kills the background service
  Future<void> _startForegroundNotification() async {
    try {
      final plugin = FlutterLocalNotificationsPlugin();

      const androidDetails = AndroidNotificationDetails(
        'loewix_live_tracking',
        'Live Tracking Lokasi',
        channelDescription: 'Memantau posisi live teknisi untuk operasional lapangan',
        importance: Importance.low,
        priority: Priority.low,
        ongoing: true, // Cannot be dismissed by user
        autoCancel: false,
        showWhen: true,
        icon: '@mipmap/ic_launcher',
      );

      await plugin.show(
        888, // Unique notification ID for tracking
        'Loewix: Pelacakan Lokasi Aktif',
        'GPS aktif memantau koordinasi tugas lapangan.',
        const NotificationDetails(android: androidDetails),
      );
    } catch (e) {
      debugPrint('⚠️ LiveTracking: Notification error $e');
    }
  }

  Future<void> _cancelNotification() async {
    try {
      final plugin = FlutterLocalNotificationsPlugin();
      await plugin.cancel(888);
    } catch (_) {}
  }
}

