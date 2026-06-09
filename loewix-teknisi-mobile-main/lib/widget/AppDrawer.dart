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
import '../constants/app_constants.dart';

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
  static const Color _darkSlate = Color(0xFF1E293B);
  static const Color _skyBlue = Color(0xFF0EA5E9);
  static const Color _teal = Color(0xFF14B8A6);
  static const Color _warmOrange = Color(0xFFF97316);
  static const Color _rose = Color(0xFFF43F5E);
  static const Color _indigo = Color(0xFF6366F1);
  static const Color _emerald = Color(0xFF10B981);
  static const Color _textPrimary = Color(0xFF0F172A);
  static const Color _textSecondary = Color(0xFF94A3B8);
  static const Color _divider = Color(0xFFF1F5F9);

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
      child: SafeArea(
        top: false,
        child: Column(
          children: [
            // ─── Header ───
            _buildHeader(),

            // ─── Menu Items (scrollable) ───
            Expanded(
              child: SingleChildScrollView(
                physics: const BouncingScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(14, 16, 14, 8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildSectionTitle('MENU UTAMA'),
                    const SizedBox(height: 6),
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

                    const SizedBox(height: 8),
                    _buildDivider(),
                    const SizedBox(height: 8),

                    _buildSectionTitle('LAINNYA'),
                    const SizedBox(height: 6),
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
                      color: _emerald,
                      badge: 'Baru',
                      onTap: () {
                        Navigator.pop(context);
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

                    const SizedBox(height: 8),
                    _buildDivider(),
                    const SizedBox(height: 12),

                    // ─── Logout (langsung di bawah menu, bukan di bawah screen) ───
                    _buildLogoutButton(),
                    const SizedBox(height: 16),

                    // ─── Version ───
                    Center(
                      child: Text(
                        'Teknisi Loewix v${AppConstants.appVersion}',
                        style: TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 11,
                          color: _textSecondary.withValues(alpha: 0.6),
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ─── Header ─────────────────────────────────
  Widget _buildHeader() {
    final topPadding = MediaQuery.of(context).padding.top;
    return Container(
      width: double.infinity,
      padding: EdgeInsets.fromLTRB(20, topPadding + 20, 20, 20),
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [Color(0xFF0F172A), Color(0xFF1E3A5F)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.only(
          bottomLeft: Radius.circular(28),
          bottomRight: Radius.circular(28),
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

          return Row(
            children: [
              // Avatar
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: LinearGradient(
                    colors: [
                      _skyBlue.withValues(alpha: 0.3),
                      _teal.withValues(alpha: 0.3),
                    ],
                  ),
                  border: Border.all(
                    color: Colors.white.withValues(alpha: 0.4),
                    width: 2,
                  ),
                ),
                child: const Icon(
                  Iconsax.user,
                  size: 26,
                  color: Colors.white,
                ),
              ),
              const SizedBox(width: 14),
              // Info
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      nama,
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 16,
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
                          size: 12,
                          color: Colors.white.withValues(alpha: 0.7),
                        ),
                        const SizedBox(width: 5),
                        Text(
                          telp,
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 12,
                            color: Colors.white.withValues(alpha: 0.7),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    // Badge
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 10,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: _emerald.withValues(alpha: 0.2),
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(
                          color: _emerald.withValues(alpha: 0.3),
                          width: 1,
                        ),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Iconsax.verify5, size: 13, color: _emerald),
                          const SizedBox(width: 4),
                          Text(
                            'Teknisi Aktif',
                            style: TextStyle(
                              fontFamily: 'Poppins',
                              fontSize: 10,
                              fontWeight: FontWeight.w600,
                              color: _emerald,
                            ),
                          ),
                        ],
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

  // ─── Section Title ──────────────────────────
  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(left: 10, bottom: 2),
      child: Text(
        title,
        style: TextStyle(
          fontFamily: 'Poppins',
          fontSize: 10,
          fontWeight: FontWeight.w700,
          color: _textSecondary.withValues(alpha: 0.7),
          letterSpacing: 1.2,
        ),
      ),
    );
  }

  // ─── Divider ────────────────────────────────
  Widget _buildDivider() {
    return Container(
      height: 1,
      margin: const EdgeInsets.symmetric(horizontal: 10),
      color: _divider,
    );
  }

  // ─── Menu Item (indexed - for tab switching) ─
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
      padding: const EdgeInsets.only(bottom: 2),
      child: Material(
        color: isActive ? color.withValues(alpha: 0.08) : Colors.transparent,
        borderRadius: BorderRadius.circular(14),
        child: InkWell(
          onTap: () {
            Navigator.pop(context);
            widget.onItemTapped(index);
          },
          borderRadius: BorderRadius.circular(14),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            child: Row(
              children: [
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    color: isActive ? color : color.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(11),
                  ),
                  child: Center(
                    child: Icon(
                      isActive ? activeIcon : icon,
                      size: 20,
                      color: isActive ? Colors.white : color,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 14,
                          fontWeight: isActive ? FontWeight.w600 : FontWeight.w500,
                          color: isActive ? color : _textPrimary,
                        ),
                      ),
                      Text(
                        subtitle,
                        style: const TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 10,
                          color: _textSecondary,
                        ),
                      ),
                    ],
                  ),
                ),
                if (badge != null)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                    decoration: BoxDecoration(
                      color: _warmOrange.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      badge,
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 9,
                        fontWeight: FontWeight.w700,
                        color: _warmOrange,
                      ),
                    ),
                  ),
                if (isActive && badge == null)
                  Icon(
                    Iconsax.arrow_right_3,
                    size: 14,
                    color: color.withValues(alpha: 0.5),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // ─── Menu Item Custom (non-indexed - for navigator push) ─
  Widget _buildMenuItemCustom({
    required IconData icon,
    required String title,
    required String subtitle,
    required Color color,
    required VoidCallback onTap,
    String? badge,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 2),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(14),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(14),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            child: Row(
              children: [
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(11),
                  ),
                  child: Center(
                    child: Icon(icon, size: 20, color: color),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: const TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 14,
                          fontWeight: FontWeight.w500,
                          color: _textPrimary,
                        ),
                      ),
                      Text(
                        subtitle,
                        style: const TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 10,
                          color: _textSecondary,
                        ),
                      ),
                    ],
                  ),
                ),
                if (badge != null)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                    decoration: BoxDecoration(
                      color: _warmOrange.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      badge,
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 9,
                        fontWeight: FontWeight.w700,
                        color: _warmOrange,
                      ),
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // ─── Logout Button ──────────────────────────
  Widget _buildLogoutButton() {
    return SizedBox(
      width: double.infinity,
      child: Material(
        color: _rose.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(14),
        child: InkWell(
          onTap: _showLogoutConfirmation,
          borderRadius: BorderRadius.circular(14),
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 12),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Iconsax.logout, size: 18, color: _rose),
                const SizedBox(width: 8),
                Text(
                  'Keluar',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: _rose,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
