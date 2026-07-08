import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:workmanager/workmanager.dart';
import '../api/ApiLink.dart';

/// ─── Background task handler (top-level) ───────────────
@pragma('vm:entry-point')
void salesCallbackDispatcher() {
  Workmanager().executeTask((task, inputData) async {
    if (task == 'checkSalesKunjungan') {
      await _checkAndNotifySales(inputData);
    }
    return true;
  });
}

Future<void> _checkAndNotifySales(Map<String, dynamic>? inputData) async {
  final salesId = inputData?['salesId'] as String?;
  if (salesId == null || salesId.isEmpty) return;

  try {
    final httpClient = HttpClient()
      ..badCertificateCallback = (cert, host, port) => true;
    final request = await httpClient.getUrl(
      Uri.parse('${Api.Url}/api_sales_task.php?sales_id=$salesId'),
    );
    request.headers.set('Content-type', 'application/json');
    request.headers.set('Accept', 'application/json');
    final httpResponse = await request.close();
    final responseBody = await httpResponse.transform(utf8.decoder).join();

    if (httpResponse.statusCode >= 200 && httpResponse.statusCode < 300) {
      final body = json.decode(responseBody);
      final tasks = body['data'] as List? ?? [];

      // ─── Kunjungan Aktif (dijadwalkan / berjalan) ───
      int activeCount = 0;
      List<String> activeNames = [];

      // ─── Laporan Pending (sudah clock in, belum clock out) ───
      int pendingCount = 0;
      List<String> pendingNames = [];

      for (final task in tasks) {
        final status = (task['status'] ?? '').toString().toLowerCase();
        final customer = task['customer'];
        final nama = (customer is Map) ? (customer['nama']?.toString() ?? '') : '';

        // Cek status pelaksanaan sales ini
        final pelaksanaan = task['pelaksanaan'] as List? ?? [];
        String pelStatus = 'dijadwalkan';
        for (final p in pelaksanaan) {
          if (p['sales_id']?.toString() == salesId) {
            pelStatus = (p['status'] ?? 'dijadwalkan').toString().toLowerCase();
            break;
          }
        }

        if (pelStatus == 'dijadwalkan' || status == 'dijadwalkan') {
          activeCount++;
          if (nama.isNotEmpty && activeNames.length < 3) activeNames.add(nama);
        } else if (pelStatus == 'berjalan') {
          pendingCount++;
          if (nama.isNotEmpty && pendingNames.length < 3) pendingNames.add(nama);
        }
      }

      if (activeCount > 0) {
        await _showActiveNotification(activeCount, activeNames);
      }

      if (pendingCount > 0) {
        await _showPendingNotification(pendingCount, pendingNames);
      }
    }
    httpClient.close();
  } catch (_) {}
}

Future<void> _showActiveNotification(int count, List<String> names) async {
  final plugin = FlutterLocalNotificationsPlugin();
  const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
  const iOSSettings = DarwinInitializationSettings();
  const initSettings = InitializationSettings(
    android: androidSettings,
    iOS: iOSSettings,
  );
  await plugin.initialize(initSettings);

  final nameStr = names.isNotEmpty ? names.join(', ') : '';
  final body = count == 1
      ? 'Ada 1 kunjungan aktif hari ini${nameStr.isNotEmpty ? " — $nameStr" : ""}. Segera kunjungi!'
      : 'Ada $count kunjungan aktif hari ini${nameStr.isNotEmpty ? " — $nameStr" : ""}. Segera kunjungi!';

  final androidDetails = AndroidNotificationDetails(
    'active_visit_channel',
    'Kunjungan Aktif',
    channelDescription: 'Pengingat kunjungan aktif sales',
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
    2001,
    '📋 Kunjungan Aktif',
    body,
    NotificationDetails(android: androidDetails),
  );
}

Future<void> _showPendingNotification(int count, List<String> names) async {
  final plugin = FlutterLocalNotificationsPlugin();
  const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
  const iOSSettings = DarwinInitializationSettings();
  const initSettings = InitializationSettings(
    android: androidSettings,
    iOS: iOSSettings,
  );
  await plugin.initialize(initSettings);

  final nameStr = names.isNotEmpty ? names.join(', ') : '';
  final body = count == 1
      ? 'Ada 1 kunjungan belum Clock Out${nameStr.isNotEmpty ? " — $nameStr" : ""}. Segera selesaikan!'
      : 'Ada $count kunjungan belum Clock Out${nameStr.isNotEmpty ? " — $nameStr" : ""}. Segera selesaikan!';

  final androidDetails = AndroidNotificationDetails(
    'pending_visit_channel',
    'Kunjungan Pending',
    channelDescription: 'Pengingat kunjungan belum selesai',
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
    2002,
    '⏰ Belum Clock Out',
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
    const iOSSettings = DarwinInitializationSettings();
    const initSettings = InitializationSettings(
      android: androidSettings,
      iOS: iOSSettings,
    );

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
      _permissionGranted = true;
    }

    _initialized = true;
    debugPrint('🔔 Sales NotificationService initialized');
  }

  /// Register periodic background check
  Future<void> registerPeriodicCheck(String salesId) async {
    if (salesId.isEmpty) return;

    await Workmanager().initialize(salesCallbackDispatcher, isInDebugMode: false);
    await Workmanager().registerPeriodicTask(
      'salesKunjunganCheck',
      'checkSalesKunjungan',
      inputData: {'salesId': salesId},
      frequency: const Duration(hours: 3),
      initialDelay: const Duration(minutes: 1),
      constraints: Constraints(networkType: NetworkType.connected),
      existingWorkPolicy: ExistingWorkPolicy.replace,
    );
    debugPrint('🔔 Registered periodic check for sales: $salesId');
  }

  /// Show notification — for in-app use
  Future<void> showNow({
    required String title,
    required String body,
    int id = 0,
  }) async {
    if (!_initialized) await init();

    try {
      final androidDetails = AndroidNotificationDetails(
        'general_sales_channel',
        'Notifikasi Sales',
        channelDescription: 'Notifikasi umum sales Loewix',
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
    } catch (e) {
      debugPrint('🔔 ERROR showing notification: $e');
    }
  }

  /// Cancel all
  Future<void> cancelAll() async {
    await _plugin.cancelAll();
  }
}
