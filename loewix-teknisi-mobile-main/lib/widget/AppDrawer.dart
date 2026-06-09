import 'package:flutter/material.dart';
import 'package:iconsax/iconsax.dart';
import 'package:provider/provider.dart';
import 'package:quickalert/quickalert.dart';

import '../service/provider/Auth/AuthProvider.dart';
import '../service/provider/preferences/PreferencesIDProvider.dart';
import '../service/provider/Profile/ProfileProvider.dart';
import '../service/model/profile/ProfileTeknisi.dart';
import '../page/Auth/LoginPage.dart';
import '../page/Garansi/CekGaransiPage.dart';

class AppDrawer extends StatefulWidget {
  final int currentIndex;
  final Function(int) onItemTapped;

  const AppDrawer({
    super.key,
    required this.currentIndex,
    required this.onItemTapped,
  });

  @override
  State<AppDrawer> createState() => _AppDrawerState();
}

class _AppDrawerState extends State<AppDrawer> {
  // ─── Premium Color Palette ─────────────────────
  static const Color _navy = Color(0xFF0F172A);
  static const Color _skyBlue = Color(0xFF0EA5E9);
  static const Color _teal = Color(0xFF14B8A6);
  static const Color _warmOrange = Color(0xFFF97316);
  static const Color _rose = Color(0xFFF43F5E);
  static const Color _indigo = Color(0xFF6366F1);
  static const Color _textPrimary = Color(0xFF0F172A);
  static const Color _textSecondary = Color(0xFF64748B);

  Future<ProfileTeknisiResponse>? _profileFuture;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      final token = context.read<PreferencesIDProvider>().isUserRole;
      setState(() {
        _profileFuture = context.read<ProfileProvider>().getUser(token);
      });
    });
  }

  void _showLogoutConfirmation() {
    // Don't pop drawer here — it invalidates context for QuickAlert
    QuickAlert.show(
      context: context,
      type: QuickAlertType.confirm,
      title: 'Keluar dari Aplikasi',
      text: 'Apakah Anda yakin ingin keluar?',
      confirmBtnText: 'Ya, Keluar',
      cancelBtnText: 'Batal',
      confirmBtnColor: _rose,
      onConfirmBtnTap: () async {
        Navigator.pop(context); // Close dialog
        Navigator.pop(context); // Close drawer
        await _performLogout();
      },
      onCancelBtnTap: () {
        Navigator.pop(context); // Close dialog only
      },
    );
  }

  Future<void> _performLogout() async {
    // Get providers before async gap
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final prefsProvider = Provider.of<PreferencesIDProvider>(context, listen: false);

    authProvider.logout();
    prefsProvider.deleteRole();

    if (!mounted) return;

    Navigator.pushAndRemoveUntil(
      context,
      MaterialPageRoute(builder: (context) => const LoginPage()),
      (route) => false,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Drawer(
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.horizontal(right: Radius.circular(24)),
      ),
      child: Column(
          children: [
            // Header (di luar SafeArea agar warna biru sampai ke status bar)
            _buildHeader(),
            const SizedBox(height: 8),
            // Menu items
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildSectionTitle('Menu Utama'),
                    const SizedBox(height: 8),
                    _buildMenuItem(
                      index: 0,
                      icon: Iconsax.task_square,
                      activeIcon: Iconsax.task_square5,
                      title: 'Tugas',
                      subtitle: 'Kelola tugas harian',
                      color: _skyBlue,
                    ),
                    _buildMenuItem(
                      index: 1,
                      icon: Iconsax.clock,
                      activeIcon: Iconsax.clock5,
                      title: 'Riwayat',
                      subtitle: 'Lihat riwayat tugas',
                      color: _teal,
                    ),
                    _buildMenuItem(
                      index: 2,
                      icon: Iconsax.box,
                      activeIcon: Iconsax.box5,
                      title: 'Peminjaman',
                      subtitle: 'Kelola peminjaman barang',
                      color: _indigo,
                    ),
                    const SizedBox(height: 16),
                    _buildSectionTitle('Lainnya'),
                    const SizedBox(height: 8),
                    _buildMenuItem(
                      index: 3,
                      icon: Iconsax.chart_1,
                      activeIcon: Iconsax.chart_15,
                      title: 'Statistik',
                      subtitle: 'Statistik & pencapaian',
                      color: _warmOrange,
                    ),
                    _buildMenuItem(
                      index: 4,
                      icon: Iconsax.video_play,
                      activeIcon: Iconsax.video5,
                      title: 'Tutorial',
                      subtitle: 'Panduan penggunaan',
                      color: _rose,
                      badge: 'Baru',
                    ),
                    _buildMenuItemCustom(
                      icon: Iconsax.shield_tick,
                      title: 'Cek Garansi',
                      subtitle: 'Cek garansi produk',
                      color: _teal,
                      badge: 'Baru',
                      onTap: () {
                        Navigator.pop(context); // close drawer
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => const CekGaransiPage(),
                          ),
                        );
                      },
                    ),
                    _buildMenuItem(
                      index: 5,
                      icon: Iconsax.user,
                      activeIcon: Iconsax.profile_circle,
                      title: 'Profil',
                      subtitle: 'Informasi akun',
                      color: _skyBlue,
                    ),
                  ],
                ),
              ),
            ),
            // Logout button
            _buildLogoutButton(),
            const SizedBox(height: 16),
          ],
        ),
    );
  }

  Widget _buildHeader() {
    final topPadding = MediaQuery.of(context).padding.top;
    return Container(
      width: double.infinity,
      padding: EdgeInsets.fromLTRB(20, topPadding + 20, 20, 20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: const BorderRadius.only(
          bottomLeft: Radius.circular(24),
          bottomRight: Radius.circular(24),
        ),
      ),
      child: FutureBuilder<ProfileTeknisiResponse>(
        future: _profileFuture,
        builder: (context, snapshot) {
          String nama = 'Teknisi';
          String telp = '-';

          if (snapshot.hasData) {
            nama = snapshot.data!.data.nama;
            telp = snapshot.data!.data.telp;
          }

          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    width: 60,
                    height: 60,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: Colors.white.withValues(alpha: 0.2),
                      border: Border.all(color: Colors.white, width: 2),
                    ),
                    child: const Icon(
                      Iconsax.user,
                      size: 30,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          nama,
                          style: const TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 18,
                            fontWeight: FontWeight.w600,
                            color: Colors.white,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 2),
                        Row(
                          children: [
                            Icon(
                              Iconsax.call,
                              size: 14,
                              color: Colors.white.withValues(alpha: 0.8),
                            ),
                            const SizedBox(width: 6),
                            Text(
                              telp,
                              style: TextStyle(
                                fontFamily: 'Poppins',
                                fontSize: 13,
                                color: Colors.white.withValues(alpha: 0.8),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: const Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Iconsax.verify5, size: 16, color: Colors.white),
                    SizedBox(width: 6),
                    Text(
                      'Teknisi Aktif',
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 12,
                        fontWeight: FontWeight.w500,
                        color: Colors.white,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(left: 8),
      child: Text(
        title,
        style: const TextStyle(
          fontFamily: 'Poppins',
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: _textSecondary,
          letterSpacing: 0.5,
        ),
      ),
    );
  }

  Widget _buildMenuItem({
    required int index,
    required IconData icon,
    required IconData activeIcon,
    required String title,
    required String subtitle,
    required Color color,
    String? badge,
  }) {
    final isActive = widget.currentIndex == index;

    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Container(
        decoration: BoxDecoration(
          color: isActive ? color.withValues(alpha: 0.1) : Colors.transparent,
          borderRadius: BorderRadius.circular(14),
        ),
        child: Material(
          color: Colors.transparent,
          borderRadius: BorderRadius.circular(14),
          child: InkWell(
            onTap: () {
              Navigator.pop(context); // Close drawer
              widget.onItemTapped(index);
            },
            borderRadius: BorderRadius.circular(14),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                Container(
                  width: 42,
                  height: 42,
                  decoration: BoxDecoration(
                    color: isActive ? color : color.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Center(
                    child: Icon(
                      isActive ? activeIcon : icon,
                      size: 22,
                      color: isActive ? Colors.white : color,
                    ),
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 15,
                          fontWeight: isActive ? FontWeight.w600 : FontWeight.w500,
                          color: isActive ? color : _textPrimary,
                        ),
                      ),
                      Text(
                        subtitle,
                        style: const TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 11,
                          color: _textSecondary,
                        ),
                      ),
                    ],
                  ),
                ),
                if (badge != null)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: _warmOrange.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      badge,
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 10,
                        fontWeight: FontWeight.w600,
                        color: _warmOrange,
                      ),
                    ),
                  ),
                if (isActive && badge == null)
                  Icon(
                    Iconsax.arrow_right_3,
                    size: 16,
                    color: color.withValues(alpha: 0.5),
                  ),
              ],
            ),
          ),
        ),
        ),
      ),
    );
  }

  Widget _buildMenuItemCustom({
    required IconData icon,
    required String title,
    required String subtitle,
    required Color color,
    required VoidCallback onTap,
    String? badge,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Container(
        decoration: BoxDecoration(
          color: Colors.transparent,
          borderRadius: BorderRadius.circular(14),
        ),
        child: Material(
          color: Colors.transparent,
          borderRadius: BorderRadius.circular(14),
          child: InkWell(
            onTap: onTap,
            borderRadius: BorderRadius.circular(14),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  Container(
                    width: 42,
                    height: 42,
                    decoration: BoxDecoration(
                      color: color.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Center(
                      child: Icon(icon, size: 22, color: color),
                    ),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          title,
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 15,
                            fontWeight: FontWeight.w500,
                            color: _textPrimary,
                          ),
                        ),
                        Text(
                          subtitle,
                          style: const TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 11,
                            color: _textSecondary,
                          ),
                        ),
                      ],
                    ),
                  ),
                  if (badge != null)
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: _warmOrange.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        badge,
                        style: const TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 10,
                          fontWeight: FontWeight.w600,
                          color: _warmOrange,
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildLogoutButton() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: SizedBox(
        width: double.infinity,
        child: ElevatedButton(
          onPressed: _showLogoutConfirmation,
          style: ElevatedButton.styleFrom(
            backgroundColor: _rose.withValues(alpha: 0.1),
            foregroundColor: _rose,
            elevation: 0,
            padding: const EdgeInsets.symmetric(vertical: 14),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(14),
            ),
          ),
          child: const Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Iconsax.logout, size: 20),
              SizedBox(width: 10),
              Text(
                'Keluar',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 15,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
