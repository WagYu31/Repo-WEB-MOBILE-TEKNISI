import '../../page/container/HomePage.dart';
import '../../service/provider/Auth/AuthProvider.dart';
import '../../service/provider/preferences/PreferencesIDProvider.dart';
import '../../service/update/ForceUpdateService.dart';
import '../../utils/auth.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:permission_handler/permission_handler.dart' as b;
import 'package:provider/provider.dart';
import 'package:quickalert/models/quickalert_type.dart';
import 'package:quickalert/widgets/quickalert_dialog.dart';

import 'package:location/location.dart' as a;

import '../Onboarding/OnboardingPage.dart';

class SplashScreen extends StatefulWidget {
  static const routeName = 'splash_screen';
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {

  Future<bool> checkLocationPermission() async {
    // Periksa status izin lokasi
    b.PermissionStatus status = await b.Permission.location.status;

    if (status.isGranted) {
      // Izin lokasi sudah diberikan
      return true;
    } else if (status.isDenied) {
      // Izin lokasi ditolak, minta izin
      status = await b.Permission.location.request();
      return status.isGranted;
    } else if (status.isPermanentlyDenied) {
      // Izin lokasi ditolak secara permanen, arahkan ke pengaturan
      b.openAppSettings();
      return false;
    }

    // Izin lokasi tidak diberikan
    return false;
  }

  Future<bool> checkLocationService() async {
    a.Location location = a.Location();
    bool serviceEnabled = await location.serviceEnabled();

    if (!serviceEnabled) {
      serviceEnabled = await location.requestService();
      if (!serviceEnabled) {
        QuickAlert.show(
            context: context,
            type: QuickAlertType.warning,
            title: 'Lokasi Diperlukan',
          text: 'Harap aktifkan lokasi/GPS anda',
          onConfirmBtnTap: () {
              SystemNavigator.pop();
          }
        );
        return false;
      }
    }

    // Layanan lokasi sudah diaktifkan
    return true;
  }

  Future<void> _navigateToNextScreen() async {

    Future.delayed(const Duration(seconds: 5), () async {
      debugPrint('SPLASH: Starting navigation logic...');
      
      AuthApp.setID(Provider.of<PreferencesIDProvider>(context, listen: false).isUserRole);
      debugPrint('SPLASH: AuthApp ID set.');

      await Provider.of<AuthProvider>(context, listen: false).checkLoginStatus();
      debugPrint('SPLASH: checkLoginStatus completed.');

      // ═══ CEK VERSI SEBELUM LANJUT ═══
      if (mounted) {
        debugPrint('SPLASH: Checking app version...');
        final canProceed = await ForceUpdateService().checkVersion(context);
        debugPrint('SPLASH: App version check result: $canProceed');
        if (!canProceed) {
          // App diblokir, dialog force update sudah tampil
          return;
        }
      }

      debugPrint('SPLASH: Checking location permission...');
      bool hasLocationPermission = await checkLocationPermission();
      debugPrint('SPLASH: location permission result: $hasLocationPermission');

      debugPrint('SPLASH: Checking location service...');
      bool checkLocation = await checkLocationService();
      debugPrint('SPLASH: location service result: $checkLocation');

      if (hasLocationPermission) {
        // Lanjutkan dengan operasi yang memerlukan akses lokasi

        if(checkLocation){
          debugPrint('SPLASH: Location verified, checking login state...');
          if (Provider.of<AuthProvider>(context, listen: false).isLoggedIn) {
            debugPrint('SPLASH: User is logged in, navigating to HomePageAdmin.');
            Navigator.pushReplacement(
              context,
              MaterialPageRoute(builder: (context) => const HomePageAdmin()),
            );
          } else {
            debugPrint('SPLASH: User is NOT logged in, navigating to OnboardingPage.');
            Navigator.pushReplacement(
              context,
              MaterialPageRoute(builder: (context) => const OnboardingPage()),
            );
          }
        } else {
          debugPrint('SPLASH: Location service check failed.');
        }
      } else {
        // Tampilkan pesan kepada pengguna bahwa izin lokasi diperlukan
        debugPrint('SPLASH: Location permission denied, showing alert.');
        QuickAlert.show(
            context: context,
            type: QuickAlertType.warning,
            title: 'Izin Dibutuhkan!',
            text: 'Harap berikan izin sebelum melanjutkan',
          onConfirmBtnTap: (){
              SystemNavigator.pop();
          }
        );
      }
    });

  }

  @override
  void initState() {
    _navigateToNextScreen();
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    double width = MediaQuery.of(context).size.width;
    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [
              Color(0xFF0F172A), // Dark Slate / Navy 900
              Color(0xFF1E3A8A), // Deep Loewix Blue
              Color(0xFF1D4ED8), // Royal Blue
            ],
          ),
        ),
        child: SafeArea(
          child: Column(
            children: [
              const Spacer(flex: 2),

              // ─── Center Logo & App Branding ───
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: Colors.white.withOpacity(0.08),
                  shape: BoxShape.circle,
                  border: Border.all(
                    color: Colors.white.withOpacity(0.18),
                    width: 1.5,
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFF1D4ED8).withOpacity(0.3),
                      blurRadius: 30,
                      spreadRadius: 10,
                    ),
                  ],
                ),
                child: Image.asset(
                  'assets/icon.png',
                  width: 90,
                  height: 90,
                ),
              ),
              const SizedBox(height: 24),

              // Title
              const Text(
                'LOEWIX',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 28,
                  fontWeight: FontWeight.w900,
                  letterSpacing: 4.0,
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: 6),

              // Edition Badge
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
                decoration: BoxDecoration(
                  color: const Color(0xFF38BDF8).withOpacity(0.18),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(
                    color: const Color(0xFF38BDF8).withOpacity(0.6),
                    width: 1,
                  ),
                ),
                child: const Text(
                  'TEKNISI EDITION',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    letterSpacing: 1.8,
                    color: Color(0xFF38BDF8),
                  ),
                ),
              ),
              const SizedBox(height: 14),

              // Tagline
              const Padding(
                padding: EdgeInsets.symmetric(horizontal: 40),
                child: Text(
                  'Sistem Manajemen Lapangan & Instalasi CCTV',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 13,
                    fontWeight: FontWeight.w400,
                    color: Color(0xFF94A3B8),
                    height: 1.4,
                  ),
                ),
              ),

              const Spacer(flex: 3),

              // ─── Loading Bar ───
              SizedBox(
                width: width * 0.45,
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(10),
                  child: const LinearProgressIndicator(
                    minHeight: 4,
                    backgroundColor: Color(0xFF1E293B),
                    valueColor: AlwaysStoppedAnimation<Color>(Color(0xFF38BDF8)),
                  ),
                ),
              ),
              const SizedBox(height: 14),
              const Text(
                'Memuat sistem & perizinan...',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 11,
                  color: Color(0xFF64748B),
                ),
              ),

              const Spacer(flex: 1),

              // ─── Footer ───
              const Text(
                'PT. Loewix Indonesia Security System',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                  color: Color(0xFF94A3B8),
                  letterSpacing: 0.5,
                ),
              ),
              const SizedBox(height: 4),
              const Text(
                'Versi 2.4.0 (Official Build)',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 10,
                  color: Color(0xFF64748B),
                ),
              ),
              const SizedBox(height: 16),
            ],
          ),
        ),
      ),
    );
  }
}
