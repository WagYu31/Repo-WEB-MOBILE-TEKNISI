import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:teknisi_loewix/page/Pinjam_Barang/PinjamBarangContainer.dart';
import 'package:teknisi_loewix/widget/AppDrawer.dart';

import '../Dashboard/DashboardPage.dart';
import '../History/HistoryPage.dart';
import '../Profile/ProfilePage.dart';
import '../pencapaian/PencapaianPage.dart';
import '../tutorial/TutorialPage.dart';

class HomePageAdmin extends StatefulWidget {
  static const routeName = '/home_page_admin';
  const HomePageAdmin({super.key});

  @override
  State<HomePageAdmin> createState() => _HomePageAdminState();
}

class _HomePageAdminState extends State<HomePageAdmin> {
  int _currentIndex = 0;

  void _onItemTapped(int index) {
    setState(() {
      _currentIndex = index;
    });
  }

  Future<bool> _showExitConfirmation() async {
    final result = await showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
        title: const Row(
          children: [
            Icon(
              Icons.exit_to_app_rounded,
              color: Color(0xFF2563EB),
              size: 28,
            ),
            SizedBox(width: 12),
            Text(
              'Keluar Aplikasi',
              style: TextStyle(
                fontFamily: 'Poppins',
                fontSize: 18,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
        content: const Text(
          'Apakah Anda yakin ingin keluar dari aplikasi?',
          style: TextStyle(
            fontFamily: 'Poppins',
            fontSize: 14,
            color: Color(0xFF6B7280),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text(
              'Batal',
              style: TextStyle(
                fontFamily: 'Poppins',
                fontWeight: FontWeight.w500,
                color: Color(0xFF6B7280),
              ),
            ),
          ),
          ElevatedButton(
            onPressed: () => Navigator.of(context).pop(true),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF2563EB),
              foregroundColor: Colors.white,
              elevation: 0,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
              ),
            ),
            child: const Text(
              'Keluar',
              style: TextStyle(
                fontFamily: 'Poppins',
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
    return result ?? false;
  }

  List<Widget> get _screens => [
    DashboardPage(onNavigate: _onItemTapped),
    const HistoryPage(),
    const ContainerPinjamBarang(),
    const PencapaianPage(),
    const TutorialPage(),
    const ProfileScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: _currentIndex != 0,
      onPopInvokedWithResult: (didPop, result) async {
        if (didPop) return;

        // Jika di dashboard (index 0), tampilkan konfirmasi keluar
        if (_currentIndex == 0) {
          final shouldExit = await _showExitConfirmation();
          if (shouldExit) {
            SystemNavigator.pop();
          }
        }
      },
      child: Scaffold(
        drawer: AppDrawer(
          currentIndex: _currentIndex,
          onItemTapped: _onItemTapped,
        ),
        body: _screens[_currentIndex],
      ),
    );
  }
}
