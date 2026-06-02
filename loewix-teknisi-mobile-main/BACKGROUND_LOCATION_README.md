# Background Location Tracking & Geofencing

## 📋 Fitur yang Ditambahkan

Aplikasi Teknisi Loewix sekarang memiliki kemampuan **background location tracking** dengan **push notification** otomatis ketika user mendekati lokasi task.

### ✨ Cara Kerja

1. **Saat Task Dimulai** (Slide "Geser untuk Mulai")
   - System akan otomatis mendaftarkan task ke background monitoring
   - Background service akan mulai check lokasi user setiap 15 menit
   - Data task (koordinat + radius) disimpan di SharedPreferences

2. **Background Monitoring**
   - Service berjalan di background bahkan saat app ditutup
   - Setiap 15 menit, service akan:
     - Check apakah ada active tasks
     - Get current location user
     - Calculate distance ke setiap task location
     - Trigger notification jika dalam radius

3. **Notifikasi Push**
   - Muncul otomatis saat user masuk radius task
   - Format: "📍 Anda Sudah Dekat Lokasi Task!"
   - Detail: Nama task, customer, dan jarak
   - Hanya muncul 1x per task (tidak spam)
   - Reset jika user keluar lalu masuk radius lagi

4. **Saat Task Selesai** (Slide "Geser untuk Selesai")
   - Background monitoring untuk task tersebut akan dihentikan
   - Notification dibersihkan

---

## 🗂️ File yang Dibuat/Dimodifikasi

### File Baru
1. **`lib/service/background/BackgroundLocationService.dart`**
   - Service utama untuk background monitoring
   - Menggunakan `workmanager` untuk periodic task
   - Handle location checking dan notification trigger

2. **`lib/service/provider/Geofence/GeofenceMonitorProvider.dart`**
   - Provider untuk manage active tasks monitoring
   - API untuk start/stop monitoring per task
   - State management untuk UI indicator

3. **`BACKGROUND_LOCATION_README.md`** (file ini)
   - Dokumentasi lengkap fitur

### File Dimodifikasi
1. **`pubspec.yaml`**
   - Added: `workmanager: ^0.5.2`
   - Added: `flutter_local_notifications: ^17.2.3`

2. **`lib/service/notification/NotificationService.dart`**
   - Uncommented semua code
   - Added: `showGeofenceNotification()` method
   - Added: Singleton pattern
   - Enhanced: Channel configuration untuk geofencing

3. **`lib/main.dart`**
   - Initialize NotificationService di startup
   - Initialize BackgroundLocationService
   - Register GeofenceMonitorProvider

4. **`lib/page/Task/TaskPage.dart`**
   - Import GeofenceMonitorProvider
   - Call `startMonitoringTask()` saat task dimulai
   - Call `stopMonitoringTask()` saat task selesai

5. **`android/app/src/main/AndroidManifest.xml`**
   - Added: Workmanager receiver configuration
   - (Permissions sudah ada sebelumnya)

---

## 🚀 Cara Install & Test

### 1. Install Dependencies

```bash
flutter pub get
```

### 2. Build & Install App

```bash
# Debug build
flutter run

# Release build (recommended untuk test background)
flutter build apk --release
flutter install
```

### 3. Testing Background Location

#### Test Scenario 1: Basic Flow
1. Login ke app
2. Pilih task yang memiliki lokasi (lat, lon, rad)
3. Buka TaskPage untuk task tersebut
4. Pastikan Maps sudah load current location
5. **Slide "Geser untuk Mulai"**
6. Konfirmasi alert → Task dimulai ✅
7. **Close/minimize app** (penting!)
8. Tunggu 15-30 detik (interval pertama lebih cepat)
9. **Pindah lokasi** mendekati task location
10. Notifikasi akan muncul: "📍 Anda Sudah Dekat Lokasi Task!"

#### Test Scenario 2: Fake GPS Testing
Jika Anda ingin test dengan fake GPS:

1. Install Fake GPS app (contoh: "Fake GPS Location - GPS JoyStick")
2. Enable Developer Options → Select mock location app
3. Set fake location ke koordinat task
4. Ikuti Test Scenario 1
5. **CATATAN**: Server akan detect fake GPS dan reject clock-in, tapi notification tetap muncul

#### Test Scenario 3: Multiple Tasks
1. Start 2-3 task berbeda
2. Monitoring akan berjalan untuk semua task
3. Notifikasi muncul per task sesuai radius masing-masing

#### Test Scenario 4: Stop Monitoring
1. Slide "Geser untuk Selesai" pada task yang sedang berjalan
2. Background monitoring untuk task tersebut akan stop
3. Notifikasi tidak akan muncul lagi untuk task ini

---

## 🔧 Konfigurasi & Customization

### Mengubah Interval Check (Default: 15 menit)

Edit `lib/service/background/BackgroundLocationService.dart`:

```dart
await Workmanager().registerPeriodicTask(
  _taskName,
  _taskName,
  frequency: Duration(minutes: 15), // ← Ubah di sini (minimum: 15 menit)
  initialDelay: Duration(seconds: 10),
  ...
);
```

**Catatan**: Android membatasi minimum interval ke 15 menit untuk battery optimization.

### Mengubah Format Notifikasi

Edit `lib/service/notification/NotificationService.dart`:

```dart
Future showGeofenceNotification({
  required int taskId,
  required String taskName,
  required String customerName,
  required double distance,
}) async {
  final title = '📍 Anda Sudah Dekat Lokasi Task!'; // ← Ubah title
  final body = '$taskName\n$customerName\nJarak: ${distance.toStringAsFixed(0)}m dari lokasi'; // ← Ubah body
  ...
}
```

### Mengubah Sound/Vibration

Edit channel configuration di `NotificationService.dart`:

```dart
android: AndroidNotificationDetails(
  'geofence_channel',
  'Geofence Notifications',
  channelDescription: 'Notifikasi saat Anda mendekati lokasi task',
  importance: Importance.max,
  priority: Priority.high,
  playSound: true,        // ← false untuk disable sound
  enableVibration: true,  // ← false untuk disable vibration
),
```

---

## 📱 Permission Requirements

### Permissions yang Digunakan (Sudah ada di AndroidManifest.xml):

```xml
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" />
<uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION" />
<uses-permission android:name="android.permission.ACCESS_BACKGROUND_LOCATION"/>
<uses-permission android:name="android.permission.FOREGROUND_SERVICE" />
<uses-permission android:name="android.permission.RECEIVE_BOOT_COMPLETED"/>
<uses-permission android:name="android.permission.POST_NOTIFICATIONS"/>
<uses-permission android:name="android.permission.WAKE_LOCK" />
```

### Runtime Permissions
- Location permission: Sudah di-handle di MapsPage
- Notification permission: Otomatis di-request saat `initNotification()`
- Background location: Android 10+ akan auto-prompt

---

## 🐛 Troubleshooting

### 1. Notifikasi Tidak Muncul

**Check:**
- Pastikan app sudah running minimal 1x setelah install
- Check permission notification di Settings → Apps → Teknisi Loewix
- Check battery optimization: Disable untuk app ini
- Check logs dengan `flutter logs` atau Android Studio Logcat
- Search for tag: `[Background]` atau `[BackgroundService]`

**Fix:**
```bash
# Clear app data dan reinstall
adb shell pm clear com.teknisi.loewix  # Sesuaikan package name
flutter install
```

### 2. Background Service Tidak Berjalan

**Check:**
- Pastikan sudah slide "Geser untuk Mulai" (monitoring hanya aktif untuk task yang berjalan)
- Check battery optimization settings
- Beberapa vendor (Xiaomi, Huawei, Oppo) agresif kill background apps

**Fix untuk Xiaomi/MIUI:**
- Settings → Apps → Manage Apps → Teknisi Loewix
- Autostart: Enable
- Battery Saver: No restrictions
- Display pop-up windows while running in background: Enable

### 3. Interval Terlalu Lama

**Penjelasan:**
- Android membatasi minimum interval periodic task ke 15 menit
- Ini untuk battery optimization
- Tidak bisa diubah tanpa menggunakan foreground service

**Alternatif (Jika Butuh Real-time):**
- Upgrade ke Opsi B dengan native geofencing
- Atau gunakan foreground service dengan notification persistent

### 4. Location Tidak Akurat

**Check:**
- Pastikan GPS enabled
- Test di outdoor (GPS accuracy lebih baik)
- Check `Position.accuracy` di logs

**Fix:**
```dart
// Di BackgroundLocationService.dart, ubah accuracy:
position = await Geolocator.getCurrentPosition(
  desiredAccuracy: LocationAccuracy.best, // ← Ubah ke best (lebih akurat tapi boros baterai)
  timeLimit: Duration(seconds: 10),
);
```

---

## 📊 Monitoring & Logs

### Check Background Service Status

Logs akan muncul dengan format:

```
[Background] Task started: geofenceMonitoringTask
[Background] Current location: -6.200000, 106.816666
[Background] Distance to task 123: 150m (radius: 500m)
[Background] Task 123 is within radius! Sending notification...
[Background] Task completed successfully
```

### Check di Android Logcat

```bash
# Terminal 1: Run app
flutter run

# Terminal 2: Watch logs
adb logcat | grep -i "background\|geofence\|notification"
```

### Debug Mode

Edit `BackgroundLocationService.dart`:

```dart
await Workmanager().initialize(
  callbackDispatcher,
  isInDebugMode: true, // ← Set true untuk verbose logging
);
```

---

## ⚡ Performance & Battery Impact

### Battery Usage Estimation
- **Interval 15 menit**: ~2-3% battery per day
- **GPS accuracy**: LocationAccuracy.high (balanced)
- **Wake lock**: Only during check (10-15 detik)

### Optimization Tips
1. Monitoring hanya untuk task yang `status == 'berjalan'`
2. Notification hanya 1x per task (tidak spam)
3. Service auto-stop jika tidak ada active tasks
4. Timeout 10 detik untuk location fetch

---

## 🔄 Future Improvements (Opsional)

Jika perlu fitur lebih advanced, bisa upgrade dengan:

### 1. Real-time Geofencing (Native)
```yaml
dependencies:
  geofence_service: ^5.0.0
```
- Battery lebih hemat
- Trigger instant (tidak perlu wait 15 menit)
- Akurasi lebih tinggi

### 2. Firebase Cloud Messaging
```yaml
dependencies:
  firebase_messaging: ^14.0.0
```
- Remote notification dari server
- Bisa trigger dari dashboard admin

### 3. Historical Tracking
- Simpan history lokasi user
- Analytics & reporting
- Compliance tracking

### 4. Foreground Service
- Notification persistent "Sedang Memantau Lokasi"
- Interval bisa lebih cepat (1-5 menit)
- Tidak di-kill oleh system

---

## 📞 Support

Jika ada issue atau pertanyaan:
1. Check Troubleshooting section di atas
2. Check logs dengan `flutter logs`
3. Review code di file-file yang disebutkan
4. Test dengan scenario di atas

---

## 📝 Technical Summary

**Architecture:**
- Background Service: `workmanager` (Android WorkManager API)
- Periodic Task: 15 menit interval (Android minimum)
- Storage: `SharedPreferences` untuk active tasks list
- Distance Calculation: Haversine formula
- Notification: `flutter_local_notifications` channel-based

**Flow:**
```
User Start Task
    ↓
GeofenceMonitorProvider.startMonitoringTask()
    ↓
BackgroundLocationService.addActiveTask()
    ↓
Workmanager.registerPeriodicTask()
    ↓
[Every 15 minutes]
    ↓
callbackDispatcher() runs in background isolate
    ↓
Get SharedPreferences active_tasks
    ↓
Geolocator.getCurrentPosition()
    ↓
Calculate distance for each task
    ↓
If (distance <= radius && !already_notified)
    ↓
NotificationService.showGeofenceNotification()
    ↓
Mark as notified (prevent spam)
```

---

**Version**: 1.0
**Date**: 2025-01-26
**Implementation**: Opsi A (Simple Background Service)
