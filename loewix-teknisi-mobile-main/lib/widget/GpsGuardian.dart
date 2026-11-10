import 'dart:async';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:location/location.dart' as loc;
import 'package:permission_handler/permission_handler.dart' hide ServiceStatus;

class GpsGuardian extends StatefulWidget {
  final Widget child;

  const GpsGuardian({super.key, required this.child});

  @override
  State<GpsGuardian> createState() => _GpsGuardianState();
}

class _GpsGuardianState extends State<GpsGuardian> with WidgetsBindingObserver {
  bool _isGpsDisabled = false;
  bool _isPermissionDenied = false;
  StreamSubscription<ServiceStatus>? _serviceStatusSubscription;
  Timer? _pollingTimer;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _checkGpsStatus();
    _listenToGpsStatus();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _serviceStatusSubscription?.cancel();
    _pollingTimer?.cancel();
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    // When returning to app, immediately re-verify GPS
    if (state == AppLifecycleState.resumed) {
      _checkGpsStatus();
    }
  }

  void _listenToGpsStatus() {
    _serviceStatusSubscription = Geolocator.getServiceStatusStream().listen(
      (ServiceStatus status) {
        if (!mounted) return;
        setState(() {
          _isGpsDisabled = (status == ServiceStatus.disabled);
        });
      },
    );

    // Fallback polling every 3 seconds to catch changes instantly
    _pollingTimer = Timer.periodic(const Duration(seconds: 3), (_) {
      _checkGpsStatus();
    });
  }

  Future<void> _checkGpsStatus() async {
    try {
      final isEnabled = await Geolocator.isLocationServiceEnabled();
      final permStatus = await Permission.location.status;

      if (!mounted) return;
      setState(() {
        _isGpsDisabled = !isEnabled;
        _isPermissionDenied = permStatus.isDenied || permStatus.isPermanentlyDenied;
      });
    } catch (_) {}
  }

  Future<void> _requestEnableGps() async {
    try {
      final location = loc.Location();
      bool enabled = await location.requestService();
      if (enabled) {
        _checkGpsStatus();
      } else {
        await Geolocator.openLocationSettings();
      }
    } catch (_) {
      await Geolocator.openLocationSettings();
    }
  }

  Future<void> _requestPermission() async {
    final status = await Permission.location.request();
    if (status.isPermanentlyDenied) {
      await openAppSettings();
    }
    _checkGpsStatus();
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        // Main App Content
        widget.child,

        // Full Screen Blocker if GPS or Permission is disabled ("Gk Boleh Mati")
        if (_isGpsDisabled || _isPermissionDenied)
          Positioned.fill(
            child: PopScope(
              canPop: false, // Prevent back navigation while GPS is off
              child: Material(
                color: const Color(0xFF0F172A),
                child: SafeArea(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 28),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        // Pulsing Warning Icon
                        Container(
                          width: 100,
                          height: 100,
                          decoration: BoxDecoration(
                            color: const Color(0xFFEF4444).withValues(alpha: 0.15),
                            shape: BoxShape.circle,
                          ),
                          child: const Center(
                            child: Icon(
                              Icons.location_off_rounded,
                              size: 52,
                              color: Color(0xFFEF4444),
                            ),
                          ),
                        ),
                        const SizedBox(height: 28),
                        Text(
                          _isGpsDisabled ? '⚠️ GPS Dimatikan!' : '⚠️ Izin Lokasi Diperlukan!',
                          style: const TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 22,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                          ),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 12),
                        Text(
                          _isGpsDisabled
                              ? 'Aplikasi Loewix Teknisi mewajibkan GPS selalu aktif selama jam operasional lapangan.\n\nAplikasi terkunci dan tidak dapat digunakan jika GPS dimatikan.'
                              : 'Aplikasi Loewix Teknisi membutuhkan izin akses lokasi agar tugas dan tracking dapat berjalan dengan baik.',
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 13.5,
                            color: Colors.white.withValues(alpha: 0.75),
                            height: 1.5,
                          ),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 36),
                        // Primary Button: Enable GPS Now
                        SizedBox(
                          width: double.infinity,
                          height: 52,
                          child: ElevatedButton.icon(
                            onPressed: _isGpsDisabled ? _requestEnableGps : _requestPermission,
                            icon: const Icon(Icons.location_on_rounded, size: 20),
                            label: Text(
                              _isGpsDisabled ? 'Aktifkan GPS Sekarang' : 'Izinkan Akses Lokasi',
                              style: const TextStyle(
                                fontFamily: 'Poppins',
                                fontSize: 14.5,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFFFF5B22),
                              foregroundColor: Colors.white,
                              elevation: 0,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(16),
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(height: 12),
                        // Secondary Button: Open Settings
                        TextButton(
                          onPressed: () => Geolocator.openLocationSettings(),
                          child: Text(
                            'Buka Pengaturan Lokasi HP',
                            style: TextStyle(
                              fontFamily: 'Poppins',
                              fontSize: 13,
                              color: Colors.white.withValues(alpha: 0.6),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
      ],
    );
  }
}

