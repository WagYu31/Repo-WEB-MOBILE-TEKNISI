import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../constants/app_constants.dart';
import 'package:http/http.dart' as http;

/// Service untuk mengecek versi aplikasi dan memaksa update jika diperlukan.
/// 
/// Cara kerja:
/// 1. App memanggil endpoint server untuk mendapatkan versi minimum
/// 2. Bandingkan dengan versi app saat ini
/// 3. Jika versi app < versi minimum, tampilkan popup blocking wajib update
class ForceUpdateService {
  // Singleton
  static final ForceUpdateService _instance = ForceUpdateService._internal();
  factory ForceUpdateService() => _instance;
  ForceUpdateService._internal();

  /// URL endpoint untuk cek versi (sama domain dengan admin panel)
  static const String _versionCheckUrl = 
      'https://jadwal.id-giti.com/staff/api_app_version.php';

  /// Cek versi dan tampilkan dialog force update jika perlu.
  /// Panggil di SplashPage sebelum navigasi ke halaman berikutnya.
  /// Returns true jika app boleh lanjut, false jika harus update.
  Future<bool> checkVersion(BuildContext context) async {
    try {
      final response = await http.get(
        Uri.parse(_versionCheckUrl),
        headers: {'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        final String minVersion = data['min_version'] ?? '0.0.0';
        final String latestVersion = data['latest_version'] ?? '0.0.0';
        final String updateUrl = data['update_url'] ?? '';
        final String forceMessage = data['force_message'] ?? 
            'Silakan update aplikasi ke versi terbaru.';
        final String updateMessage = data['update_message'] ?? 
            'Versi terbaru tersedia!';

        final String currentVersion = AppConstants.appVersion;

        // Cek apakah versi saat ini < versi minimum (WAJIB update)
        if (_isVersionLower(currentVersion, minVersion)) {
          if (context.mounted) {
            await _showForceUpdateDialog(
              context, 
              forceMessage, 
              updateUrl,
              currentVersion,
              latestVersion,
            );
          }
          return false; // App tidak boleh lanjut
        }

        // Cek apakah ada versi baru (optional update - soft reminder)
        if (_isVersionLower(currentVersion, latestVersion)) {
          if (context.mounted) {
            _showOptionalUpdateDialog(
              context, 
              updateMessage, 
              updateUrl,
              currentVersion,
              latestVersion,
            );
          }
        }

        return true; // App boleh lanjut
      }
    } catch (e) {
      debugPrint('🔄 Version check failed: $e');
      // Jika gagal cek (no internet dll), biarkan app jalan
    }
    return true;
  }

  /// Bandingkan 2 versi string (e.g. "4.0.8" vs "4.0.9")
  /// Returns true jika current < target
  bool _isVersionLower(String current, String target) {
    final currentParts = current.split('.').map(int.parse).toList();
    final targetParts = target.split('.').map(int.parse).toList();

    // Pad shorter list with zeros
    while (currentParts.length < 3) currentParts.add(0);
    while (targetParts.length < 3) targetParts.add(0);

    for (int i = 0; i < 3; i++) {
      if (currentParts[i] < targetParts[i]) return true;
      if (currentParts[i] > targetParts[i]) return false;
    }
    return false; // Same version
  }

  /// Dialog BLOCKING - tidak bisa di-dismiss, WAJIB update
  Future<void> _showForceUpdateDialog(
    BuildContext context,
    String message,
    String updateUrl,
    String currentVersion,
    String latestVersion,
  ) async {
    return showDialog(
      context: context,
      barrierDismissible: false, // Tidak bisa tutup dengan tap luar
      barrierColor: const Color(0xFF0F172A).withOpacity(0.85),
      builder: (context) => PopScope(
        canPop: false, // Tidak bisa back button
        child: Dialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
          elevation: 0,
          backgroundColor: Colors.transparent,
          child: Container(
            padding: const EdgeInsets.all(28),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(24),
              boxShadow: [
                BoxShadow(
                  color: const Color(0xFF1E40AF).withOpacity(0.15),
                  blurRadius: 40,
                  offset: const Offset(0, 16),
                ),
              ],
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Icon
                Container(
                  width: 80,
                  height: 80,
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [Color(0xFFEF4444), Color(0xFFDC2626)],
                    ),
                    borderRadius: BorderRadius.circular(20),
                    boxShadow: [
                      BoxShadow(
                        color: const Color(0xFFEF4444).withOpacity(0.3),
                        blurRadius: 20,
                        offset: const Offset(0, 8),
                      ),
                    ],
                  ),
                  child: const Icon(
                    Icons.system_update_rounded,
                    color: Colors.white,
                    size: 40,
                  ),
                ),
                const SizedBox(height: 24),

                // Title
                const Text(
                  'Update Diperlukan!',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF1E293B),
                  ),
                ),
                const SizedBox(height: 8),

                // Version info
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFEF2F2),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: const Color(0xFFFECACA)),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        'v$currentVersion',
                        style: const TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: Color(0xFFDC2626),
                          decoration: TextDecoration.lineThrough,
                        ),
                      ),
                      const Padding(
                        padding: EdgeInsets.symmetric(horizontal: 8),
                        child: Icon(Icons.arrow_forward_rounded, 
                            size: 16, color: Color(0xFF64748B)),
                      ),
                      Text(
                        'v$latestVersion',
                        style: const TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: Color(0xFF16A34A),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),

                // Message
                Text(
                  message,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 14,
                    color: Color(0xFF64748B),
                    height: 1.5,
                  ),
                ),
                const SizedBox(height: 28),

                // Update Button
                SizedBox(
                  width: double.infinity,
                  height: 52,
                  child: ElevatedButton(
                    onPressed: () => _openUpdateUrl(updateUrl),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF1E40AF),
                      foregroundColor: Colors.white,
                      elevation: 0,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                    ),
                    child: const Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.download_rounded, size: 20),
                        SizedBox(width: 8),
                        Text(
                          'Download Update',
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
                const SizedBox(height: 12),

                // Exit button
                SizedBox(
                  width: double.infinity,
                  height: 44,
                  child: TextButton(
                    onPressed: () => SystemNavigator.pop(),
                    child: const Text(
                      'Keluar Aplikasi',
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 13,
                        fontWeight: FontWeight.w500,
                        color: Color(0xFF94A3B8),
                      ),
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

  /// Dialog OPTIONAL - bisa di-skip, tapi tetap diingatkan
  void _showOptionalUpdateDialog(
    BuildContext context,
    String message,
    String updateUrl,
    String currentVersion,
    String latestVersion,
  ) {
    showDialog(
      context: context,
      barrierDismissible: true,
      barrierColor: const Color(0xFF0F172A).withOpacity(0.6),
      builder: (context) => Dialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        elevation: 0,
        backgroundColor: Colors.transparent,
        child: Container(
          padding: const EdgeInsets.all(28),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(24),
            boxShadow: [
              BoxShadow(
                color: const Color(0xFF1E40AF).withOpacity(0.1),
                blurRadius: 30,
                offset: const Offset(0, 12),
              ),
            ],
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // Icon
              Container(
                width: 72,
                height: 72,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [Color(0xFF0EA5E9), Color(0xFF1E40AF)],
                  ),
                  borderRadius: BorderRadius.circular(18),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFF0EA5E9).withOpacity(0.3),
                      blurRadius: 16,
                      offset: const Offset(0, 6),
                    ),
                  ],
                ),
                child: const Icon(
                  Icons.system_update_alt_rounded,
                  color: Colors.white,
                  size: 36,
                ),
              ),
              const SizedBox(height: 20),

              const Text(
                'Update Tersedia',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFF1E293B),
                ),
              ),
              const SizedBox(height: 6),

              Text(
                'v$currentVersion → v$latestVersion',
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 12,
                  fontWeight: FontWeight.w500,
                  color: Color(0xFF64748B),
                ),
              ),
              const SizedBox(height: 12),

              Text(
                message,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 13,
                  color: Color(0xFF94A3B8),
                  height: 1.4,
                ),
              ),
              const SizedBox(height: 24),

              // Buttons row
              Row(
                children: [
                  Expanded(
                    child: SizedBox(
                      height: 46,
                      child: OutlinedButton(
                        onPressed: () => Navigator.pop(context),
                        style: OutlinedButton.styleFrom(
                          side: const BorderSide(color: Color(0xFFE2E8F0)),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        child: const Text(
                          'Nanti',
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 13,
                            fontWeight: FontWeight.w500,
                            color: Color(0xFF64748B),
                          ),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: SizedBox(
                      height: 46,
                      child: ElevatedButton(
                        onPressed: () {
                          Navigator.pop(context);
                          _openUpdateUrl(updateUrl);
                        },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF1E40AF),
                          foregroundColor: Colors.white,
                          elevation: 0,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        child: const Text(
                          'Update',
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  /// Buka URL download APK
  Future<void> _openUpdateUrl(String url) async {
    try {
      final uri = Uri.parse(url);
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      }
    } catch (e) {
      debugPrint('🔄 Failed to open update URL: $e');
    }
  }
}
