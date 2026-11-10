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
  bool _isLocationAlwaysDenied = false;
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
      final alwaysStatus = await Permission.locationAlways.status;

      if (!mounted) return;
      setState(() {
        _isGpsDisabled = !isEnabled;
        _isPermissionDenied = permStatus.isDenied || permStatus.isPermanentlyDenied;
        _isLocationAlwaysDenied = permStatus.isGranted && !alwaysStatus.isGranted;
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

  Future<void> _requestLocationAlways() async {
    try {
      final opened = await openAppSettings();
      if (!opened) {
        await Geolocator.openAppSettings();
      }
    } catch (_) {
      try {
        await Geolocator.openAppSettings();
      } catch (_) {}
    }
    await _checkGpsStatus();
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
          )
        else if (_isLocationAlwaysDenied)
          // Blocker for "Allow All The Time" (Sepanjang Waktu) - TIDAK BISA DILEWATI
          Positioned.fill(
            child: PopScope(
              canPop: false,
              child: Material(
                color: const Color(0xFF0F172A),
                child: SafeArea(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 26),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        // Icon lock & location
                        Container(
                          width: 88,
                          height: 88,
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(
                              colors: [Color(0xFFEF4444), Color(0xFFFF5B22)],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                            shape: BoxShape.circle,
                            boxShadow: [
                              BoxShadow(
                                color: const Color(0xFFFF5B22).withValues(alpha: 0.35),
                                blurRadius: 20,
                                offset: const Offset(0, 6),
                              ),
                            ],
                          ),
                          child: const Center(
                            child: Icon(
                              Icons.lock_person_rounded,
                              size: 44,
                              color: Colors.white,
                            ),
                          ),
                        ),
                        const SizedBox(height: 22),
                        const Text(
                          'Aplikasi Terkunci!\nWajib "Sepanjang Waktu"',
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 20,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                            height: 1.3,
                          ),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 10),
                        Text(
                          'Untuk mencegah manipulasi GPS atau lokasi mati di jalan, aplikasi Loewix mewajibkan izin lokasi "Sepanjang Waktu" sebelum dapat digunakan.',
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 13,
                            color: Colors.white.withValues(alpha: 0.8),
                            height: 1.5,
                          ),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 20),

                        // Guidance box
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: const Color(0xFF1E293B),
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: const Color(0xFF334155)),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Row(
                                children: [
                                  Icon(Icons.touch_app_rounded, color: Color(0xFF38BDF8), size: 18),
                                  SizedBox(width: 8),
                                  Text(
                                    'Langkah Pengaturan:',
                                    style: TextStyle(
                                      fontFamily: 'Poppins',
                                      fontSize: 12.5,
                                      fontWeight: FontWeight.w700,
                                      color: Color(0xFF38BDF8),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 10),
                              _buildStepText('1', 'Ketuk tombol "Buka Setelan HP Sekarang" di bawah'),
                              _buildStepText('2', 'Buka menu "Izin" ➔ pilih "Lokasi"'),
                              _buildStepText('3', 'Ubah pilihan ke: "Izinkan sepanjang waktu"'),
                              _buildStepText('4', '(Disarankan) Baterai: Pilih "Tanpa Batasan"'),
                            ],
                          ),
                        ),
                        const SizedBox(height: 24),

                        // Primary Button: Direct to phone settings
                        SizedBox(
                          width: double.infinity,
                          height: 52,
                          child: ElevatedButton.icon(
                            onPressed: _requestLocationAlways,
                            icon: const Icon(Icons.settings_suggest_rounded, size: 20),
                            label: const Text(
                              'Buka Setelan HP Sekarang',
                              style: TextStyle(
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
                                borderRadius: BorderRadius.circular(14),
                              ),
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

  Widget _buildStepText(String step, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 18,
            height: 18,
            margin: const EdgeInsets.only(top: 2, right: 8),
            decoration: const BoxDecoration(
              color: Color(0xFF0284C7),
              shape: BoxShape.circle,
            ),
            child: Center(
              child: Text(
                step,
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 10,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                ),
              ),
            ),
          ),
          Expanded(
            child: Text(
              text,
              style: TextStyle(
                fontFamily: 'Poppins',
                fontSize: 11.5,
                color: Colors.white.withValues(alpha: 0.85),
                height: 1.4,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

