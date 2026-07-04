import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/app_theme.dart';
import '../../service/api/ApiLink.dart';
import '../../service/api/VersionChecker.dart';

/// Full-screen page yang tidak bisa di-dismiss.
/// Ditampilkan ketika versi app sudah tidak memenuhi min_version dari server.
class ForceUpdatePage extends StatelessWidget {
  final VersionInfo versionInfo;
  const ForceUpdatePage({super.key, required this.versionInfo});

  void _openDownloadUrl() {
    final url = versionInfo.downloadUrl;
    if (url.isNotEmpty) {
      launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
    }
  }

  @override
  Widget build(BuildContext context) {
    // ignore: deprecated_member_use
    return WillPopScope(
      onWillPop: () async => false, // Block back button
      child: Scaffold(
        backgroundColor: AppColors.bg,
        body: SafeArea(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 28),
            child: Column(
              children: [
                const Spacer(flex: 2),

                // ── Icon ──────────────────────────
                Container(
                  width: 100,
                  height: 100,
                  decoration: BoxDecoration(
                    gradient: AppColors.gradientPrimary,
                    borderRadius: BorderRadius.circular(28),
                    boxShadow: [
                      BoxShadow(
                        color: AppColors.primary.withOpacity(0.3),
                        blurRadius: 30,
                        offset: const Offset(0, 12),
                      ),
                    ],
                  ),
                  child: const Icon(
                    Icons.system_update_rounded,
                    color: Colors.white,
                    size: 48,
                  ),
                ).animate().fadeIn(duration: 500.ms).scale(
                      begin: const Offset(0.8, 0.8),
                      end: const Offset(1, 1),
                      duration: 500.ms,
                    ),

                const SizedBox(height: 32),

                // ── Title ─────────────────────────
                Text(
                  'Update Diperlukan',
                  style: S.h1(),
                  textAlign: TextAlign.center,
                ).animate(delay: 200.ms).fadeIn(duration: 400.ms).slideY(begin: 0.2, end: 0),

                const SizedBox(height: 12),

                Text(
                  'Versi terbaru (v${versionInfo.latestVersion}) sudah tersedia.\n'
                  'Versi Anda saat ini: v${Api.AppVersion}',
                  style: S.body(AppColors.textSecondary),
                  textAlign: TextAlign.center,
                ).animate(delay: 300.ms).fadeIn(duration: 400.ms).slideY(begin: 0.2, end: 0),

                const SizedBox(height: 24),

                // ── Changelog ─────────────────────
                if (versionInfo.changelog.isNotEmpty)
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: AppColors.cardAlt,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: AppColors.border),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            const Icon(Icons.new_releases_rounded,
                                size: 16, color: AppColors.primary),
                            const SizedBox(width: 8),
                            Text("Yang Baru", style: S.h3()),
                          ],
                        ),
                        const SizedBox(height: 10),
                        Text(
                          versionInfo.changelog,
                          style: S.bodySm(),
                        ),
                      ],
                    ),
                  ).animate(delay: 400.ms).fadeIn(duration: 400.ms).slideY(begin: 0.2, end: 0),

                const Spacer(flex: 2),

                // ── Download Button ───────────────
                SizedBox(
                  width: double.infinity,
                  height: 56,
                  child: ElevatedButton.icon(
                    onPressed: _openDownloadUrl,
                    icon: const Icon(Icons.download_rounded, size: 22),
                    label: const Text('Download Update'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                      elevation: 0,
                      textStyle: S.h3(Colors.white),
                    ),
                  ),
                ).animate(delay: 500.ms).fadeIn(duration: 400.ms).slideY(begin: 0.3, end: 0),

                const SizedBox(height: 16),

                Text(
                  'Silakan install APK yang sudah diunduh,\nkemudian buka kembali aplikasi ini.',
                  style: S.caption(),
                  textAlign: TextAlign.center,
                ).animate(delay: 600.ms).fadeIn(duration: 400.ms),

                const Spacer(),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
