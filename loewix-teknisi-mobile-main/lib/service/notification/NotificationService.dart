import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:timezone/timezone.dart' as tz;
import 'package:workmanager/workmanager.dart';
import 'package:http/http.dart' as http;
import '../../constants/app_constants.dart';

/// ─── Background task handler (top-level) ───────────────
@pragma('vm:entry-point')
void callbackDispatcher() {
  Workmanager().executeTask((task, inputData) async {
    if (task == 'checkPendingReports') {
      await _checkAndNotify(inputData);
    }
    return true;
  });
}

Future<void> _checkAndNotify(Map<String, dynamic>? inputData) async {
  final teknisiId = inputData?['teknisiId'] as String?;
  if (teknisiId == null || teknisiId.isEmpty) return;

  try {
    final httpClient = HttpClient()
      ..badCertificateCallback = (cert, host, port) => true;
    final request = await httpClient.getUrl(
      Uri.parse('${AppConstants.apiBaseUrl}/task/$teknisiId'),
    );
    request.headers.set('Content-type', 'application/json');
    request.headers.set('Accept', 'application/json');
    final httpResponse = await request.close();
    final responseBody = await httpResponse.transform(utf8.decoder).join();

    if (httpResponse.statusCode >= 200 && httpResponse.statusCode < 300) {
      final body = json.decode(responseBody);
      final tasks = body['data'] as List? ?? [];

      // ─── Tugas Aktif (dijadwalkan / berjalan) ───
      int activeCount = 0;
      List<String> activeNames = [];

      // ─── Laporan Pending (menunggu laporan) ───
      int reportCount = 0;
      List<String> reportNames = [];

      for (final task in tasks) {
        final pelaksanaan = task['pelaksanaan'] as List? ?? [];
        // Cari status teknisi ini
        String pelStatus = 'tidak';
        for (final p in pelaksanaan) {
          if (p['teknisi_id']?.toString() == teknisiId) {
            pelStatus = (p['status'] ?? 'tidak').toString().toLowerCase();
            break;
          }
        }

        final customer = task['customer'];
        final nama = (customer is Map) ? (customer['nama']?.toString() ?? '') : '';

        if (pelStatus == 'dijadwalkan' || pelStatus == 'tidak' || pelStatus == 'berjalan') {
          activeCount++;
          if (nama.isNotEmpty && activeNames.length < 3) activeNames.add(nama);
        } else if (pelStatus == 'menunggu laporan') {
          reportCount++;
          if (nama.isNotEmpty && reportNames.length < 3) reportNames.add(nama);
        }
      }

      // Notifikasi Tugas Aktif
      if (activeCount > 0) {
        await _showActiveTaskNotification(activeCount, activeNames);
      }

      // Notifikasi Laporan Pending
      if (reportCount > 0) {
        await _showReportNotification(reportCount, reportNames);
      }
    }
    httpClient.close();
  } catch (_) {}
}

Future<void> _showActiveTaskNotification(int count, List<String> names) async {
  final plugin = FlutterLocalNotificationsPlugin();
  const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
  const initSettings = InitializationSettings(android: androidSettings);
  await plugin.initialize(initSettings);

  final nameStr = names.isNotEmpty ? names.join(', ') : '';
  final body = count == 1
      ? 'Ada 1 tugas aktif hari ini${nameStr.isNotEmpty ? " — $nameStr" : ""}. Segera kerjakan!'
      : 'Ada $count tugas aktif hari ini${nameStr.isNotEmpty ? " — $nameStr" : ""}. Segera kerjakan!';

  final androidDetails = AndroidNotificationDetails(
    'active_task_channel',
    'Tugas Aktif',
    channelDescription: 'Pengingat tugas aktif teknisi',
    importance: Importance.max,
    priority: Priority.max,
    icon: '@mipmap/ic_launcher',
    styleInformation: BigTextStyleInformation(body),
    autoCancel: true,
    showWhen: true,
    playSound: true,
    enableVibration: true,
  );

  await plugin.show(
    1002,
    '📋 Tugas Aktif',
    body,
    NotificationDetails(android: androidDetails),
  );
}

Future<void> _showReportNotification(int count, List<String> names) async {
  final plugin = FlutterLocalNotificationsPlugin();
  const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
  const initSettings = InitializationSettings(android: androidSettings);
  await plugin.initialize(initSettings);

  final nameStr = names.isNotEmpty ? names.join(', ') : '';
  final body = count == 1
      ? 'Ada 1 laporan belum diupload${nameStr.isNotEmpty ? " — $nameStr" : ""}. Segera upload!'
      : 'Ada $count laporan belum diupload${nameStr.isNotEmpty ? " — $nameStr" : ""}. Segera upload!';

  final androidDetails = AndroidNotificationDetails(
    'pending_report_channel',
    'Laporan Pending',
    channelDescription: 'Pengingat upload laporan teknisi',
    importance: Importance.max,
    priority: Priority.max,
    icon: '@mipmap/ic_launcher',
    styleInformation: BigTextStyleInformation(body),
    autoCancel: true,
    showWhen: true,
    playSound: true,
    enableVibration: true,
  );

  await plugin.show(
    1001,
    '📝 Laporan Belum Diupload',
    body,
    NotificationDetails(android: androidDetails),
  );
}

/// ─── NotificationService (in-app singleton) ────────────
class NotificationService {
  static final NotificationService _instance = NotificationService._();
  factory NotificationService() => _instance;
  NotificationService._();

  final FlutterLocalNotificationsPlugin _plugin =
      FlutterLocalNotificationsPlugin();
  bool _initialized = false;
  bool _permissionGranted = false;

  /// Initialize — call once in main()
  Future<void> init() async {
    if (_initialized) return;

    const androidSettings =
        AndroidInitializationSettings('@mipmap/ic_launcher');
    const initSettings = InitializationSettings(android: androidSettings);

    await _plugin.initialize(
      initSettings,
      onDidReceiveNotificationResponse: (response) {
        debugPrint('Notification tapped: ${response.payload}');
      },
    );

    // Request permission on Android 13+
    final android = _plugin.resolvePlatformSpecificImplementation<
        AndroidFlutterLocalNotificationsPlugin>();
    if (android != null) {
      final granted = await android.requestNotificationsPermission();
      _permissionGranted = granted ?? false;
      debugPrint('🔔 Notification permission: $_permissionGranted');
    } else {
      _permissionGranted = true; // older Android, no permission needed
    }

    _initialized = true;
    debugPrint('🔔 NotificationService initialized');
  }

  /// Register periodic background check
  Future<void> registerPeriodicCheck(String teknisiId) async {
    if (teknisiId.isEmpty) return;

    await Workmanager().initialize(callbackDispatcher, isInDebugMode: false);
    await Workmanager().registerPeriodicTask(
      'pendingReportCheck',
      'checkPendingReports',
      inputData: {'teknisiId': teknisiId},
      frequency: const Duration(hours: 3),
      initialDelay: const Duration(minutes: 1),
      constraints: Constraints(networkType: NetworkType.connected),
      existingWorkPolicy: ExistingWorkPolicy.replace,
    );
  }

  /// Show notification — the core method
  Future<void> showNow({
    required String title,
    required String body,
    int id = 0,
  }) async {
    if (!_initialized) await init();

    debugPrint('🔔 showNow called: "$title" / "$body"');
    debugPrint('🔔 initialized=$_initialized, permission=$_permissionGranted');

    try {
      final androidDetails = AndroidNotificationDetails(
        'pending_report_channel',
        'Laporan Pending',
        channelDescription: 'Pengingat upload laporan teknisi',
        importance: Importance.max,
        priority: Priority.max,
        icon: '@mipmap/ic_launcher',
        styleInformation: BigTextStyleInformation(body),
        autoCancel: true,
        showWhen: true,
        playSound: true,
        enableVibration: true,
      );

      await _plugin.show(
        id,
        title,
        body,
        NotificationDetails(android: androidDetails),
      );
      debugPrint('🔔 Notification shown successfully!');
    } catch (e) {
      debugPrint('🔔 ERROR showing notification: $e');
    }
  }

  /// Cancel all
  Future<void> cancelAll() async {
    await _plugin.cancelAll();
  }
}