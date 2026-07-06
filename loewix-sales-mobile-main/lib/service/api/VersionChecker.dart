import 'dart:convert';
import 'package:http/http.dart' as http;
import 'ApiLink.dart';

enum UpdateStatus { upToDate, updateAvailable, updateRequired, error }

class VersionInfo {
  final String latestVersion;
  final String minVersion;
  final String downloadUrl;
  final String changelog;
  final UpdateStatus status;

  VersionInfo({
    required this.latestVersion,
    required this.minVersion,
    required this.downloadUrl,
    required this.changelog,
    required this.status,
  });
}

class VersionChecker {
  /// Compare two semantic versions (e.g. "1.2.0" vs "1.1.0")
  /// Returns: negative if a < b, 0 if equal, positive if a > b
  static int _compareVersions(String a, String b) {
    final partsA = a.split('.').map(int.parse).toList();
    final partsB = b.split('.').map(int.parse).toList();
    for (int i = 0; i < 3; i++) {
      final va = i < partsA.length ? partsA[i] : 0;
      final vb = i < partsB.length ? partsB[i] : 0;
      if (va != vb) return va - vb;
    }
    return 0;
  }

  /// Check the server for version info and determine update status.
  static Future<VersionInfo> check() async {
    try {
      final cacheBusterUrl = 
          'https://jadwal.id-giti.com/staff/api_sales_version.php?t=${DateTime.now().millisecondsSinceEpoch}';
      final res = await http
          .get(Uri.parse(cacheBusterUrl), headers: {'Accept': 'application/json'})
          .timeout(const Duration(seconds: 10));

      if (res.statusCode != 200) {
        // Server unreachable → let user proceed
        return VersionInfo(
          latestVersion: Api.AppVersion,
          minVersion: Api.AppVersion,
          downloadUrl: '',
          changelog: '',
          status: UpdateStatus.error,
        );
      }

      final data = jsonDecode(res.body);
      final latestVersion = data['latest_version'] ?? Api.AppVersion;
      final minVersion = data['min_version'] ?? '0.0.0';
      final downloadUrl = data['update_url'] ?? data['download_url'] ?? '';
      final changelog = data['update_message'] ?? data['changelog'] ?? '';

      UpdateStatus status;
      if (_compareVersions(Api.AppVersion, minVersion) < 0) {
        status = UpdateStatus.updateRequired;
      } else if (_compareVersions(Api.AppVersion, latestVersion) < 0) {
        status = UpdateStatus.updateAvailable;
      } else {
        status = UpdateStatus.upToDate;
      }

      return VersionInfo(
        latestVersion: latestVersion,
        minVersion: minVersion,
        downloadUrl: downloadUrl,
        changelog: changelog,
        status: status,
      );
    } catch (_) {
      // Network error → let user proceed
      return VersionInfo(
        latestVersion: Api.AppVersion,
        minVersion: Api.AppVersion,
        downloadUrl: '',
        changelog: '',
        status: UpdateStatus.error,
      );
    }
  }
}
