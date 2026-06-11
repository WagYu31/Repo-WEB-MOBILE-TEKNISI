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

import '../Auth/LoginPage.dart';

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

      AuthApp.setID(Provider.of<PreferencesIDProvider>(context, listen: false).isUserRole);

      await Provider.of<AuthProvider>(context, listen: false).checkLoginStatus();

      // ═══ CEK VERSI SEBELUM LANJUT ═══
      if (mounted) {
        final canProceed = await ForceUpdateService().checkVersion(context);
        if (!canProceed) {
          // App diblokir, dialog force update sudah tampil
          return;
        }
      }

      bool hasLocationPermission = await checkLocationPermission();

      bool checkLocation = await checkLocationService();

      if (hasLocationPermission) {
        // Lanjutkan dengan operasi yang memerlukan akses lokasi

        if(checkLocation){
          if (Provider.of<AuthProvider>(context, listen: false).isLoggedIn) {
            Navigator.pushReplacement(
              context,
              MaterialPageRoute(builder: (context) => const HomePageAdmin()),
            );
          } else {
            Navigator.pushReplacement(
              context,
              MaterialPageRoute(builder: (context) => const LoginPage()),
            );
          }
        }
      } else {
        // Tampilkan pesan kepada pengguna bahwa izin lokasi diperlukan
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
      backgroundColor: Colors.blue,
      body: Center(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.center,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Image.asset('assets/imgLogo.png',
            scale: 6,),
            const SizedBox(height: 30,),
            SizedBox(
              width: width * 0.5,
                child: const LinearProgressIndicator(color: Colors.blue,)
            ),
          ],
        ),
      ),
    );
  }
}
