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
/// Called by WorkManager even when app is killed.
@pragma('vm:entry-point')
void callbackDispatcher() {
  Workmanager().executeTask((task, inputData) async {
    if (task == 'checkPendingReports') {
      await _checkAndNotify(inputData);
    }
    return true;
  });
}

/// Check API for pending reports and show notification
Future<void> _checkAndNotify(Map<String, dynamic>? inputData) async {
  final teknisiId = inputData?['teknisiId'] as String?;
  if (teknisiId == null || teknisiId.isEmpty) return;

  try {
    // Allow bad certificates (same as app's MyHttpOverrides)
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

      // Count tasks that need action from teknisi
      int pendingCount = 0;
      List<String> pendingNames = [];

      for (final task in tasks) {
        final status = (task['status'] ?? '').toString().toLowerCase();
        // Match actual status values from API
        if (status == 'berjalan' ||
            status == 'menunggu laporan' ||
            status == 'dijadwalkan' ||
            status == 'tidak') {
          pendingCount++;
          // Customer name is nested: task['customer']['nama']
          final customer = task['customer'];
          if (customer is Map && pendingNames.length < 3) {
            final nama = customer['nama']?.toString() ?? '';
            if (nama.isNotEmpty) pendingNames.add(nama);
          }
        }
      }

      if (pendingCount > 0) {
        await _showSystemNotification(pendingCount, pendingNames);
      }
    }
    httpClient.close();
  } catch (e) {
    // Silent fail — don't crash background task
    debugPrint('Notification check error: $e');
  }
}

/// Show system-level notification (appears in notification tray)
Future<void> _showSystemNotification(int count, List<String> names) async {
  final plugin = FlutterLocalNotificationsPlugin();

  const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
  const initSettings = InitializationSettings(android: androidSettings);
  await plugin.initialize(initSettings);

  final nameStr = names.isNotEmpty ? names.join(', ') : '';
  final body = count == 1
      ? 'Ada 1 tugas belum selesai${nameStr.isNotEmpty ? " — $nameStr" : ""}. Segera upload laporan!'
      : 'Ada $count tugas belum selesai${nameStr.isNotEmpty ? " — $nameStr" : ""}. Segera upload laporan!';

  final androidDetails = AndroidNotificationDetails(
    'pending_report_channel',
    'Laporan Pending',
    channelDescription: 'Pengingat upload laporan teknisi',
    importance: Importance.high,
    priority: Priority.high,
    icon: '@mipmap/ic_launcher',
    largeIcon: const DrawableResourceAndroidBitmap('@mipmap/ic_launcher'),
    styleInformation: BigTextStyleInformation(body),
    autoCancel: true,
    showWhen: true,
  );

  await plugin.show(
    1001,
    '📋 Laporan Belum Diupload',
    body,
    NotificationDetails(android: androidDetails),
  );
}

/// ─── NotificationService (in-app singleton) ────────────
class NotificationService {
  static final NotificationService _instance = NotificationService._();
  factory NotificationService() => _instance;
  NotificationService._();

  final FlutterLocalNotificationsPlugin _plugin = FlutterLocalNotificationsPlugin();
  bool _initialized = false;

  /// Initialize notification system — call once in main()
  Future<void> init() async {
    if (_initialized) return;

    const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
    const initSettings = InitializationSettings(android: androidSettings);

    await _plugin.initialize(
      initSettings,
      onDidReceiveNotificationResponse: (response) {
        debugPrint('Notification tapped: ${response.payload}');
      },
    );

    // Request notification permission on Android 13+
    _plugin.resolvePlatformSpecificImplementation<
        AndroidFlutterLocalNotificationsPlugin>()
        ?.requestNotificationsPermission();

    _initialized = true;
  }

  /// Register periodic background check (every ~3 hours)
  Future<void> registerPeriodicCheck(String teknisiId) async {
    if (teknisiId.isEmpty) return;

    await Workmanager().initialize(callbackDispatcher, isInDebugMode: false);

    await Workmanager().registerPeriodicTask(
      'pendingReportCheck',
      'checkPendingReports',
      inputData: {'teknisiId': teknisiId},
      frequency: const Duration(hours: 3),
      initialDelay: const Duration(minutes: 1),
      constraints: Constraints(
        networkType: NetworkType.connected,
      ),
      existingWorkPolicy: ExistingWorkPolicy.replace,
    );

    debugPrint('📌 Periodic notification check registered for teknisi $teknisiId');
  }

  /// Check immediately and show notification if needed
  Future<void> checkNow(String teknisiId) async {
    if (teknisiId.isEmpty) return;
    await _checkAndNotify({'teknisiId': teknisiId});
  }

  /// Show instant notification
  Future<void> showNow({
    required String title,
    required String body,
    int id = 0,
  }) async {
    if (!_initialized) await init();

    const androidDetails = AndroidNotificationDetails(
      'general_channel',
      'Notifikasi Umum',
      channelDescription: 'Notifikasi umum dari Teknisi Loewix',
      importance: Importance.high,
      priority: Priority.high,
      icon: '@mipmap/ic_launcher',
      autoCancel: true,
    );

    await _plugin.show(
      id,
      title,
      body,
      const NotificationDetails(android: androidDetails),
    );
  }

  /// Cancel all notifications
  Future<void> cancelAll() async {
    await _plugin.cancelAll();
  }
}