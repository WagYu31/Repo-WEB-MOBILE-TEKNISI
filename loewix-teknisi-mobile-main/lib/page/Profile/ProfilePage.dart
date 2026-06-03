import 'package:flutter/material.dart';
import 'package:iconsax/iconsax.dart';
import 'package:provider/provider.dart';
import 'package:quickalert/quickalert.dart';
import 'package:teknisi_loewix/page/reimburse/reimburse_list_screen.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../service/model/profile/ProfileTeknisi.dart';
import '../../service/provider/Auth/AuthProvider.dart';
import '../../service/provider/Profile/ProfileProvider.dart';
import '../../service/provider/preferences/PreferencesIDProvider.dart';
import '../Auth/LoginPage.dart';

class ProfileScreen extends StatefulWidget {
  static const routeName = '/Profile';

  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  // ─── Premium Color Palette ─────────────────────
  static const Color _navy = Color(0xFF0F172A);
  static const Color _skyBlue = Color(0xFF0EA5E9);
  static const Color _teal = Color(0xFF14B8A6);
  static const Color _indigo = Color(0xFF6366F1);
  static const Color _rose = Color(0xFFF43F5E);
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

  Future<void> _launchPrivacyPolicy() async {
    const url = 'https://teknisi.grav-tech.com/';
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Text('Tidak dapat membuka URL', style: TextStyle(fontFamily: 'Poppins')),
          backgroundColor: _rose,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      );
    }
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
        Navigator.pop(context);
        await _performLogout();
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
    return Scaffold(
      backgroundColor: const Color(0xFFF1F5F9),
      body: FutureBuilder<ProfileTeknisiResponse>(
        future: _profileFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(
              child: CircularProgressIndicator(color: _skyBlue, strokeWidth: 2.5),
            );
          } else if (snapshot.hasError) {
            return _buildErrorState();
          } else if (snapshot.hasData) {
            return _buildProfileContent(snapshot.data!.data);
          } else {
            return _buildErrorState();
          }
        },
      ),
    );
  }

  Widget _buildProfileContent(Data data) {
    return SingleChildScrollView(
      physics: const BouncingScrollPhysics(),
      child: Column(
        children: [
          _buildPremiumHeader(data),
          const SizedBox(height: 24),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Column(
              children: [
                _buildInfoCard(data),
                const SizedBox(height: 16),
                _buildMenuSection(data),
                const SizedBox(height: 24),
                _buildLogoutButton(),
                const SizedBox(height: 16),
                _buildVersionFooter(),
                const SizedBox(height: 40),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPremiumHeader(Data data) {
    return Container(
      width: double.infinity,
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF0F172A), Color(0xFF1E293B), Color(0xFF0F172A)],
          stops: [0.0, 0.5, 1.0],
        ),
        borderRadius: BorderRadius.only(
          bottomLeft: Radius.circular(32),
          bottomRight: Radius.circular(32),
        ),
      ),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 36),
          child: Column(
            children: [
              // Top bar
              Row(
                children: [
                  Material(
                    color: Colors.white.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(14),
                    child: InkWell(
                      onTap: () => Scaffold.of(context).openDrawer(),
                      borderRadius: BorderRadius.circular(14),
                      child: const Padding(
                        padding: EdgeInsets.all(12),
                        child: Icon(Iconsax.menu_1, color: Colors.white, size: 22),
                      ),
                    ),
                  ),
                  const Expanded(
                    child: Text(
                      'Profil Saya',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 18,
                        fontWeight: FontWeight.w600,
                        color: Colors.white,
                      ),
                    ),
                  ),
                  const SizedBox(width: 48),
                ],
              ),
              const SizedBox(height: 28),
              // Glassmorphism avatar
              Container(
                padding: const EdgeInsets.all(5),
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  border: Border.all(
                    color: Colors.white.withValues(alpha: 0.25),
                    width: 2.5,
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: _skyBlue.withValues(alpha: 0.2),
                      blurRadius: 20,
                      spreadRadius: 2,
                    ),
                  ],
                ),
                child: Container(
                  width: 90,
                  height: 90,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [
                        _skyBlue.withValues(alpha: 0.3),
                        _indigo.withValues(alpha: 0.3),
                      ],
                    ),
                  ),
                  child: const Icon(Icons.person_rounded, size: 48, color: Colors.white),
                ),
              ),
              const SizedBox(height: 16),
              // Name
              Text(
                data.nama,
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 22,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                  letterSpacing: -0.3,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 8),
              // Role badge — glassmorphism
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 7),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [_skyBlue.withValues(alpha: 0.3), _teal.withValues(alpha: 0.3)],
                  ),
                  borderRadius: BorderRadius.circular(24),
                  border: Border.all(color: Colors.white.withValues(alpha: 0.15)),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.verified_rounded, color: _skyBlue.withValues(alpha: 0.9), size: 16),
                    const SizedBox(width: 6),
                    Text(
                      'Teknisi',
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: Colors.white.withValues(alpha: 0.9),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildInfoCard(Data data) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 16,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: _skyBlue.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(Iconsax.personalcard, size: 16, color: _skyBlue),
              ),
              const SizedBox(width: 10),
              const Text(
                'Informasi Pribadi',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                  color: _textPrimary,
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          _buildInfoRow(
            icon: Iconsax.user,
            label: 'Nama Lengkap',
            value: data.nama,
            color: _navy,
          ),
          Divider(height: 28, color: Colors.grey.withValues(alpha: 0.15)),
          _buildInfoRow(
            icon: Iconsax.call,
            label: 'WhatsApp',
            value: data.telp,
            color: _teal,
            isPhone: true,
          ),
        ],
      ),
    );
  }

  Widget _buildInfoRow({
    required IconData icon,
    required String label,
    required String value,
    required Color color,
    bool isPhone = false,
  }) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: [color.withValues(alpha: 0.1), color.withValues(alpha: 0.05)],
            ),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(icon, size: 18, color: color),
        ),
        const SizedBox(width: 14),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 11,
                  color: _textSecondary,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                value,
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 15,
                  fontWeight: FontWeight.w600,
                  color: isPhone ? _teal : _textPrimary,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildMenuSection(Data data) {
    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 16,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          _buildMenuItem(
            icon: Iconsax.receipt_text,
            gradientColors: [_indigo, const Color(0xFF4F46E5)],
            title: 'Ajukan Reimbursement',
            subtitle: 'Klaim penggantian biaya',
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => ReimburseListPage(teknisiId: data.id),
                ),
              );
            },
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Divider(height: 1, color: Colors.grey.withValues(alpha: 0.12)),
          ),
          _buildMenuItem(
            icon: Iconsax.shield_tick,
            gradientColors: [_teal, const Color(0xFF0D9488)],
            title: 'Kebijakan Privasi',
            subtitle: 'Baca syarat dan ketentuan',
            onTap: _launchPrivacyPolicy,
          ),
        ],
      ),
    );
  }

  Widget _buildMenuItem({
    required IconData icon,
    required List<Color> gradientColors,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20),
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                gradient: LinearGradient(colors: gradientColors),
                borderRadius: BorderRadius.circular(12),
                boxShadow: [
                  BoxShadow(
                    color: gradientColors[0].withValues(alpha: 0.25),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Icon(icon, size: 20, color: Colors.white),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: _textPrimary,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    subtitle,
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 12,
                      color: _textSecondary,
                    ),
                  ),
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: const Color(0xFFF1F5F9),
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(Icons.arrow_forward_ios_rounded, size: 14, color: _textSecondary),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildLogoutButton() {
    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: _rose.withValues(alpha: 0.2)),
      ),
      child: ElevatedButton(
        onPressed: _showLogoutConfirmation,
        style: ElevatedButton.styleFrom(
          backgroundColor: _rose.withValues(alpha: 0.06),
          foregroundColor: _rose,
          elevation: 0,
          padding: const EdgeInsets.symmetric(vertical: 16),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        ),
        child: const Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.logout_rounded, size: 20),
            SizedBox(width: 10),
            Text(
              'Keluar dari Aplikasi',
              style: TextStyle(
                fontFamily: 'Poppins',
                fontSize: 15,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildVersionFooter() {
    return Text(
      'LOEWIX • v2.2.0',
      style: TextStyle(
        fontFamily: 'Poppins',
        fontSize: 11,
        color: _textSecondary.withValues(alpha: 0.5),
        letterSpacing: 1.5,
      ),
    );
  }

  Widget _buildErrorState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: _rose.withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(24),
              ),
              child: const Icon(Icons.error_outline_rounded, size: 56, color: _rose),
            ),
            const SizedBox(height: 20),
            const Text(
              'Gagal Memuat Profil',
              style: TextStyle(
                fontFamily: 'Poppins',
                fontSize: 18,
                fontWeight: FontWeight.w600,
                color: _textPrimary,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Terjadi kesalahan saat mengambil data.\nSilakan coba lagi.',
              style: TextStyle(fontFamily: 'Poppins', fontSize: 14, color: _textSecondary),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: () {
                final token = context.read<PreferencesIDProvider>().isUserRole;
                setState(() {
                  _profileFuture = context.read<ProfileProvider>().getUser(token);
                });
              },
              icon: const Icon(Icons.refresh_rounded, size: 18),
              label: const Text('Coba Lagi', style: TextStyle(fontFamily: 'Poppins', fontWeight: FontWeight.w600)),
              style: ElevatedButton.styleFrom(
                backgroundColor: _skyBlue,
                foregroundColor: Colors.white,
                elevation: 0,
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
