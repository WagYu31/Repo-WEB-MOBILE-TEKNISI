import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:image_picker/image_picker.dart';
import '../../core/app_theme.dart';
import '../../service/api/ApiLink.dart';
import '../../service/model/SalesModel.dart';
import '../../service/provider/SalesProvider.dart';
import '../login/LoginPage.dart';

class ProfilePage extends StatefulWidget {
  const ProfilePage({super.key});

  @override
  State<ProfilePage> createState() => _ProfilePageState();
}

class _ProfilePageState extends State<ProfilePage> {
  bool _isUploading = false;

  Future<void> _pickImage(ImageSource source) async {
    final picker = ImagePicker();
    try {
      final XFile? pickedFile = await picker.pickImage(
        source: source,
        maxWidth: 800,
        maxHeight: 800,
        imageQuality: 85,
      );
      if (pickedFile == null) return;

      setState(() {
        _isUploading = true;
      });

      final bytes = await File(pickedFile.path).readAsBytes();
      final base64Image = base64Encode(bytes);

      if (mounted) {
        final success = await context
            .read<SalesProvider>()
            .changeProfilePhoto(base64Image);
        
        if (mounted) {
          setState(() {
            _isUploading = false;
          });
          if (success) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('Foto profil berhasil diunggah!'),
                backgroundColor: AppColors.success,
              ),
            );
          } else {
            final errorMsg = context.read<SalesProvider>().error;
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(errorMsg.isNotEmpty ? errorMsg : 'Gagal mengunggah foto profil'),
                backgroundColor: AppColors.error,
              ),
            );
          }
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isUploading = false;
        });
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: ${e.toString()}'),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  void _showPhotoOptions() {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (BuildContext context) {
        return Container(
          padding: const EdgeInsets.all(24),
          decoration: const BoxDecoration(
            color: AppColors.card,
            borderRadius: BorderRadius.only(
              topLeft: Radius.circular(24),
              topRight: Radius.circular(24),
            ),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: AppColors.border,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(height: 24),
              Text(
                'Ubah Foto Profil',
                style: S.h2(),
              ),
              const SizedBox(height: 24),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                children: [
                  _photoOptionItem(
                    icon: Icons.camera_alt_rounded,
                    label: 'Kamera',
                    onTap: () {
                      Navigator.pop(context);
                      _pickImage(ImageSource.camera);
                    },
                  ),
                  _photoOptionItem(
                    icon: Icons.photo_library_rounded,
                    label: 'Galeri',
                    onTap: () {
                      Navigator.pop(context);
                      _pickImage(ImageSource.gallery);
                    },
                  ),
                ],
              ),
              const SizedBox(height: 16),
            ],
          ),
        );
      },
    );
  }

  Widget _photoOptionItem({
    required IconData icon,
    required String label,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Column(
        children: [
          Container(
            width: 60,
            height: 60,
            decoration: BoxDecoration(
              color: AppColors.primary.withOpacity(0.08),
              shape: BoxShape.circle,
              border: Border.all(color: AppColors.primary.withOpacity(0.2)),
            ),
            child: Icon(icon, color: AppColors.primary, size: 24),
          ),
          const SizedBox(height: 8),
          Text(label, style: S.bodySm()),
        ],
      ),
    );
  }

  Widget _buildAvatar(String initials, SalesProfile? profile) {
    final hasPhoto = profile?.foto != null && profile!.foto!.isNotEmpty;
    
    return Stack(
      children: [
        GestureDetector(
          onTap: _isUploading ? null : _showPhotoOptions,
          child: Container(
            width: 72,
            height: 72,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              border: Border.all(color: Colors.white, width: 2),
              boxShadow: [
                BoxShadow(
                  color: AppColors.primary.withOpacity(0.25),
                  blurRadius: 12,
                  spreadRadius: 2,
                ),
              ],
            ),
            child: ClipOval(
              child: hasPhoto
                  ? Image.network(
                      '${Api.Url}/storage/profile/${profile.foto}',
                      fit: BoxFit.cover,
                      errorBuilder: (context, error, stackTrace) {
                        return Container(
                          decoration: const BoxDecoration(
                            gradient: AppColors.gradientPrimary,
                          ),
                          child: Center(
                            child: Text(
                              initials,
                              style: S.h2(Colors.white),
                            ),
                          ),
                        );
                      },
                    )
                  : Container(
                      decoration: const BoxDecoration(
                        gradient: AppColors.gradientPrimary,
                      ),
                      child: Center(
                        child: Text(
                          initials,
                          style: S.h2(Colors.white),
                        ),
                      ),
                    ),
            ),
          ),
        ),
        Positioned(
          bottom: 0,
          right: 0,
          child: GestureDetector(
            onTap: _isUploading ? null : _showPhotoOptions,
            child: Container(
              padding: const EdgeInsets.all(5),
              decoration: const BoxDecoration(
                color: AppColors.primary,
                shape: BoxShape.circle,
                boxShadow: [
                  BoxShadow(
                    color: Colors.black26,
                    blurRadius: 4,
                    offset: Offset(0, 2),
                  ),
                ],
              ),
              child: const Icon(
                Icons.camera_alt_rounded,
                color: Colors.white,
                size: 14,
              ),
            ),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final prov    = context.watch<SalesProvider>();
    final profile = prov.profile;
    final tasks   = prov.tasks;

    final total   = tasks.length;
    final selesai = tasks.where((t) => t.selesai).length;
    final rate    = total == 0 ? 0.0 : selesai / total;

    // Avatar initials
    final nama    = profile?.nama ?? '';
    final parts   = nama.split(' ');
    final initials = parts.length >= 2
        ? '${parts[0][0]}${parts[1][0]}'.toUpperCase()
        : nama.isNotEmpty ? nama[0].toUpperCase() : 'S';

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: SafeArea(
        child: Stack(
          children: [
            SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(20, 24, 20, 40),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // ── Page title ───────────────────────────
                  Text('Profil', style: S.h1())
                      .animate().fadeIn(duration: 400.ms),
                  const SizedBox(height: 6),
                  Text('Informasi akun Anda', style: S.caption())
                      .animate(delay: 100.ms).fadeIn(duration: 400.ms),
                  const SizedBox(height: 28),

                  // ── Avatar + Name ────────────────────────
                  Container(
                    padding: const EdgeInsets.all(20),
                    decoration: AppTheme.cardDeco(accentColor: AppColors.primary),
                    child: Row(
                      children: [
                        _buildAvatar(initials, profile),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(nama.isEmpty ? '—' : nama,
                                  style: S.h2(), maxLines: 2,
                                  overflow: TextOverflow.ellipsis),
                              const SizedBox(height: 6),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 10, vertical: 3),
                                decoration: BoxDecoration(
                                  color: AppColors.primary.withOpacity(0.15),
                                  borderRadius: BorderRadius.circular(20),
                                  border: Border.all(
                                      color: AppColors.primary.withOpacity(0.3)),
                                ),
                                child: Text(
                                  profile?.jabatan ?? 'Sales',
                                  style: S.micro(AppColors.primaryLight),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  )
                      .animate(delay: 150.ms)
                      .fadeIn(duration: 500.ms)
                      .slideY(begin: 0.1, end: 0),

                  const SizedBox(height: 16),

                  // ── Info Card ────────────────────────────
                  Container(
                    padding: const EdgeInsets.all(20),
                    decoration: AppTheme.cardDeco(),
                    child: Column(
                      children: [
                        _infoRow(Icons.badge_outlined, 'NIK',
                            profile?.nik ?? '—'),
                        _divider(),
                        _infoRow(Icons.phone_outlined, 'No. Telepon',
                            profile?.noTlp.isEmpty ?? true
                                ? '—'
                                : profile!.noTlp),
                        _divider(),
                        _infoRow(Icons.work_outline_rounded, 'Jabatan',
                            profile?.jabatan ?? 'Sales'),
                      ],
                    ),
                  )
                      .animate(delay: 250.ms)
                      .fadeIn(duration: 500.ms)
                      .slideY(begin: 0.1, end: 0),

                  const SizedBox(height: 16),

                  // ── Stats mini ───────────────────────────
                  Container(
                    padding: const EdgeInsets.all(20),
                    decoration: AppTheme.cardDeco(),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Statistik Kunjungan', style: S.h3()),
                        const SizedBox(height: 16),
                        Row(
                          children: [
                            _miniStat('Total', total.toString(), AppColors.primary),
                            const SizedBox(width: 12),
                            _miniStat('Selesai', selesai.toString(), AppColors.success),
                            const SizedBox(width: 12),
                            _miniStat('Berjalan',
                                tasks.where((t) => t.sedangBerjalan).length.toString(),
                                AppColors.warning),
                          ],
                        ),
                        const SizedBox(height: 16),
                        // Completion rate bar
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text('Completion Rate', style: S.caption()),
                                Text('${(rate * 100).toStringAsFixed(0)}%',
                                    style: S.micro(AppColors.success)),
                              ],
                            ),
                            const SizedBox(height: 6),
                            ClipRRect(
                              borderRadius: BorderRadius.circular(4),
                              child: LinearProgressIndicator(
                                value: rate,
                                backgroundColor: AppColors.border,
                                color: AppColors.success,
                                minHeight: 6,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  )
                      .animate(delay: 350.ms)
                      .fadeIn(duration: 500.ms)
                      .slideY(begin: 0.1, end: 0),

                  const SizedBox(height: 16),

                  // ── App info ─────────────────────────────
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: AppTheme.cardDeco(),
                    child: Row(
                      children: [
                        const Icon(Icons.info_outline_rounded,
                            color: AppColors.textMuted, size: 18),
                        const SizedBox(width: 12),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Loewix Sales App', style: S.bodySm()),
                            Text('Versi 1.6.0 • PT. Loewix Indonesia',
                                style: S.caption()),
                          ],
                        ),
                      ],
                    ),
                  )
                      .animate(delay: 450.ms)
                      .fadeIn(duration: 400.ms),

                  const SizedBox(height: 32),

                  // ── Logout Button ────────────────────────
                  GestureDetector(
                    onTap: () async {
                      final confirm = await _showLogoutDialog(context);
                      if (confirm == true && context.mounted) {
                        await context.read<SalesProvider>().logout();
                        if (context.mounted) {
                          Navigator.pushReplacementNamed(
                              context, LoginPage.routeName);
                        }
                      }
                    },
                    child: Container(
                      width: double.infinity,
                      height: 52,
                      decoration: BoxDecoration(
                        color: AppColors.error.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(
                            color: AppColors.error.withOpacity(0.3)),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(Icons.logout_rounded,
                              color: AppColors.error, size: 20),
                          const SizedBox(width: 10),
                          Text('Keluar dari Akun',
                              style: S.btn(AppColors.error)),
                        ],
                      ),
                    ),
                  )
                      .animate(delay: 500.ms)
                      .fadeIn(duration: 400.ms),
                ],
              ),
            ),
            if (_isUploading)
              Container(
                color: Colors.black.withOpacity(0.4),
                child: const Center(
                  child: CircularProgressIndicator(
                    color: AppColors.primary,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _infoRow(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 10),
      child: Row(
        children: [
          Icon(icon, color: AppColors.primary, size: 18),
          const SizedBox(width: 14),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: S.label()),
              const SizedBox(height: 2),
              Text(value, style: S.bodyLg()),
            ],
          ),
        ],
      ),
    );
  }

  Widget _divider() => Container(
    height: 1, color: AppColors.divider,
    margin: const EdgeInsets.symmetric(vertical: 2),
  );

  Widget _miniStat(String label, String value, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: color.withOpacity(0.08),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: color.withOpacity(0.2)),
        ),
        child: Column(
          children: [
            Text(value,
                style: S.h2(color),
                textAlign: TextAlign.center),
            const SizedBox(height: 2),
            Text(label, style: S.label(color),
                textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }

  Future<bool?> _showLogoutDialog(BuildContext context) {
    return showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        backgroundColor: AppColors.cardAlt,
        shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20)),
        title: Text('Keluar?', style: S.h2()),
        content: Text('Anda akan keluar dari akun ini.',
            style: S.body()),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text('Batal', style: S.btn(AppColors.textSecondary)),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.error,
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10)),
            ),
            child: Text('Keluar', style: S.btn()),
          ),
        ],
      ),
    );
  }
}
