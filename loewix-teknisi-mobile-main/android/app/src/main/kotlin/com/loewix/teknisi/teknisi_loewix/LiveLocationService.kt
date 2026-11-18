package com.loewix.teknisi.teknisi_loewix

import android.Manifest
import android.app.AlarmManager
import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.app.Service
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.content.pm.ServiceInfo
import android.location.Location
import android.location.LocationListener
import android.location.LocationManager
import android.os.BatteryManager
import android.os.Build
import android.os.Bundle
import android.os.Handler
import android.os.IBinder
import android.os.Looper
import android.os.PowerManager
import android.os.SystemClock
import androidx.core.content.ContextCompat
import org.json.JSONObject
import java.io.OutputStreamWriter
import java.net.HttpURLConnection
import java.net.URL
import java.util.Calendar
import java.util.TimeZone
import java.util.concurrent.CountDownLatch
import java.util.concurrent.Executors
import java.util.concurrent.TimeUnit
import java.util.concurrent.atomic.AtomicReference

class LiveLocationService : Service(), LocationListener {

    companion object {
        const val PREFS_NAME = "loewix_tracking_prefs"
        const val KEY_TEKNISI_ID = "teknisi_id"
        const val KEY_ENABLED = "tracking_enabled"

        private const val CHANNEL_ID = "loewix_live_tracking_fg"
        private const val ALERT_CHANNEL_ID = "loewix_gps_off_alert"
        private const val NOTIFICATION_ID = 888
        private const val ALERT_NOTIFICATION_ID = 889
        private const val UPDATE_URL = "https://jadwal.id-giti.com/update_teknisi_location.php"

        // Jam kerja pemantauan wajib: 06:00 s/d 20:00 WIB
        const val WORK_START_HOUR = 6
        const val WORK_END_HOUR = 20

        fun isWithinWorkingHours(): Boolean {
            val cal = Calendar.getInstance(TimeZone.getTimeZone("Asia/Jakarta"))
            val hour = cal.get(Calendar.HOUR_OF_DAY)
            return hour in WORK_START_HOUR until WORK_END_HOUR
        }

        fun getSavedTeknisiId(context: Context): String? {
            val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
            val enabled = prefs.getBoolean(KEY_ENABLED, false)
            val directId = prefs.getString(KEY_TEKNISI_ID, null)?.trim()
            if (enabled && !directId.isNullOrEmpty() && directId != "no role") {
                return directId
            }
            val flutterPrefs = context.getSharedPreferences("FlutterSharedPreferences", Context.MODE_PRIVATE)
            val token = flutterPrefs.getString("flutter.USER_TOKEN", null)
            val flutterUserId = flutterPrefs.getString("flutter.USER_ID", null)?.trim()
            if (!token.isNullOrEmpty() && token != "no token" && !flutterUserId.isNullOrEmpty() && flutterUserId != "no role") {
                return flutterUserId
            }
            return null
        }

        fun saveTrackingState(context: Context, teknisiId: String?, enabled: Boolean) {
            context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
                .edit()
                .putString(KEY_TEKNISI_ID, teknisiId ?: "")
                .putBoolean(KEY_ENABLED, enabled)
                .apply()
        }

        fun hasForegroundLocationPermission(context: Context): Boolean {
            val fine = ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED
            val coarse = ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_COARSE_LOCATION) == PackageManager.PERMISSION_GRANTED
            return fine || coarse
        }

        fun hasBackgroundLocationPermission(context: Context): Boolean {
            if (!hasForegroundLocationPermission(context)) return false
            return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
                ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_BACKGROUND_LOCATION) == PackageManager.PERMISSION_GRANTED
            } else {
                true
            }
        }

        fun isLocationProviderEnabled(context: Context): Boolean {
            val lm = context.getSystemService(Context.LOCATION_SERVICE) as? LocationManager ?: return false
            return try {
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) {
                    lm.isLocationEnabled
                } else {
                    lm.isProviderEnabled(LocationManager.GPS_PROVIDER) || lm.isProviderEnabled(LocationManager.NETWORK_PROVIDER)
                }
            } catch (_: Throwable) {
                false
            }
        }

        fun startOrSchedule(context: Context, fromBackground: Boolean = false) {
            val teknisiId = getSavedTeknisiId(context)
            if (teknisiId.isNullOrEmpty()) {
                cancelWatchdogAlarms(context)
                return
            }

            scheduleNextCheckAlarm(context)

            if (!isWithinWorkingHours()) {
                dismissGpsOffWarning(context)
                try {
                    context.stopService(Intent(context, LiveLocationService::class.java))
                } catch (_: Throwable) {}
                return
            }

            val canStart = if (fromBackground) {
                hasBackgroundLocationPermission(context)
            } else {
                hasForegroundLocationPermission(context)
            }
            if (!canStart) return

            try {
                val intent = Intent(context, LiveLocationService::class.java)
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                    context.startForegroundService(intent)
                } else {
                    context.startService(intent)
                }
            } catch (_: Throwable) {
                // Jika Android 12+ menolak startForegroundService di background,
                // TrackingScheduleReceiver tetap mengirim lokasi langsung lewat sendDirectBackgroundPing()
            }
        }

        /**
         * Dipanggil langsung oleh TrackingScheduleReceiver di dalam goAsync() setiap ~70 detik.
         * Tetap berhasil mengirim lokasi walaupun aplikasi di-swipe close & startForegroundService diblokir OEM/Android 12+.
         */
        fun sendDirectBackgroundPing(context: Context) {
            val teknisiId = getSavedTeknisiId(context) ?: return
            if (!isWithinWorkingHours()) {
                dismissGpsOffWarning(context)
                return
            }

            val bm = context.getSystemService(Context.BATTERY_SERVICE) as? BatteryManager
            val batPct = bm?.getIntProperty(BatteryManager.BATTERY_PROPERTY_CAPACITY)

            // Deteksi bila teknisi sengaja mematikan tombol GPS di HP
            if (!isLocationProviderEnabled(context) || !hasForegroundLocationPermission(context)) {
                showGpsOffWarning(context)
                postJsonSync(JSONObject().apply {
                    put("teknisi_id", teknisiId)
                    put("gps_disabled", 1)
                    if (batPct != null && batPct in 0..100) put("battery", batPct.toString())
                })
                return
            }

            dismissGpsOffWarning(context)

            val lm = context.getSystemService(Context.LOCATION_SERVICE) as? LocationManager ?: return
            var freshLoc: Location? = null

            // Coba ambil lokasi aktif terbaru (Android 11+ API 30+)
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
                val providers = mutableListOf<String>()
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S && lm.allProviders.contains("fused")) {
                    providers.add("fused")
                }
                if (lm.allProviders.contains(LocationManager.NETWORK_PROVIDER)) {
                    providers.add(LocationManager.NETWORK_PROVIDER)
                }
                if (lm.allProviders.contains(LocationManager.GPS_PROVIDER)) {
                    providers.add(LocationManager.GPS_PROVIDER)
                }
                for (prov in providers) {
                    try {
                        if (!lm.isProviderEnabled(prov) && prov != "fused") continue
                        val latch = CountDownLatch(1)
                        val ref = AtomicReference<Location?>(null)
                        @Suppress("MissingPermission")
                        lm.getCurrentLocation(prov, null, ContextCompat.getMainExecutor(context)) { loc ->
                            ref.set(loc)
                            latch.countDown()
                        }
                        latch.await(4500L, TimeUnit.MILLISECONDS)
                        val got = ref.get()
                        if (got != null) {
                            freshLoc = got
                            break
                        }
                    } catch (_: Throwable) {}
                }
            }

            // Fallback ke lastKnownLocation terbaik (fused / gps / network / passive)
            if (freshLoc == null) {
                val allProvs = listOf("fused", LocationManager.GPS_PROVIDER, LocationManager.NETWORK_PROVIDER, LocationManager.PASSIVE_PROVIDER)
                for (prov in allProvs) {
                    try {
                        if (!lm.allProviders.contains(prov)) continue
                        @Suppress("MissingPermission")
                        val loc = lm.getLastKnownLocation(prov)
                        if (loc != null && (freshLoc == null || loc.time > freshLoc!!.time)) {
                            freshLoc = loc
                        }
                    } catch (_: Throwable) {}
                }
            }

            val chosen = freshLoc ?: return
            val isMock = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
                chosen.isMock
            } else {
                @Suppress("DEPRECATION")
                chosen.isFromMockProvider
            }
            val speedKmh = if (chosen.hasSpeed()) (chosen.speed * 3.6f) else 0f
            val heading = if (chosen.hasBearing()) chosen.bearing else 0f

            postJsonSync(JSONObject().apply {
                put("teknisi_id", teknisiId)
                put("latitude", chosen.latitude.toString())
                put("longitude", chosen.longitude.toString())
                put("accuracy", String.format("%.1f", chosen.accuracy))
                put("speed", String.format("%.1f", speedKmh))
                put("heading", String.format("%.1f", heading))
                put("is_mock", if (isMock) 1 else 0)
                put("gps_disabled", 0)
                if (batPct != null && batPct in 0..100) {
                    put("battery", batPct.toString())
                }
            })
        }

        fun postJsonSync(payload: JSONObject) {
            var conn: HttpURLConnection? = null
            try {
                conn = (URL(UPDATE_URL).openConnection() as HttpURLConnection).apply {
                    requestMethod = "POST"
                    connectTimeout = 9_000
                    readTimeout = 9_000
                    doOutput = true
                    setRequestProperty("Content-Type", "application/json; charset=UTF-8")
                }
                OutputStreamWriter(conn.outputStream, Charsets.UTF_8).use { writer ->
                    writer.write(payload.toString())
                    writer.flush()
                }
                conn.responseCode
            } catch (_: Throwable) {
            } finally {
                conn?.disconnect()
            }
        }

        fun showGpsOffWarning(context: Context) {
            try {
                val nm = context.getSystemService(Context.NOTIFICATION_SERVICE) as? NotificationManager ?: return
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                    val ch = NotificationChannel(
                        ALERT_CHANNEL_ID,
                        "Peringatan GPS Jam Kerja",
                        NotificationManager.IMPORTANCE_HIGH
                    ).apply {
                        description = "Peringatan saat GPS HP dimatikan pada jam kerja 06:00 - 20:00 WIB"
                        enableVibration(true)
                    }
                    nm.createNotificationChannel(ch)
                }
                val openIntent = Intent(context, MainActivity::class.java).apply {
                    flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP
                }
                val piFlags = PendingIntent.FLAG_UPDATE_CURRENT or
                    (if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) PendingIntent.FLAG_IMMUTABLE else 0)
                val contentPi = PendingIntent.getActivity(context, 889, openIntent, piFlags)

                val builder = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                    Notification.Builder(context, ALERT_CHANNEL_ID)
                } else {
                    @Suppress("DEPRECATION")
                    Notification.Builder(context)
                }
                nm.notify(
                    ALERT_NOTIFICATION_ID,
                    builder
                        .setContentTitle("⚠️ GPS HP Dimatikan di Jam Kerja!")
                        .setContentText("Segera nyalakan kembali GPS Anda (06:00–20:00 WIB). Status ini tercatat di Admin.")
                        .setSmallIcon(R.mipmap.ic_launcher)
                        .setContentIntent(contentPi)
                        .setOngoing(true)
                        .build()
                )
            } catch (_: Throwable) {}
        }

        fun dismissGpsOffWarning(context: Context) {
            try {
                val nm = context.getSystemService(Context.NOTIFICATION_SERVICE) as? NotificationManager ?: return
                nm.cancel(ALERT_NOTIFICATION_ID)
            } catch (_: Throwable) {}
        }

        fun stopAll(context: Context) {
            saveTrackingState(context, "", false)
            cancelWatchdogAlarms(context)
            dismissGpsOffWarning(context)
            try {
                context.stopService(Intent(context, LiveLocationService::class.java))
            } catch (_: Throwable) {}
        }

        fun scheduleNextCheckAlarm(context: Context, delayMsOverride: Long? = null) {
            val am = context.getSystemService(Context.ALARM_SERVICE) as? AlarmManager ?: return
            val intent = Intent(context, TrackingScheduleReceiver::class.java).apply {
                action = TrackingScheduleReceiver.ACTION_CHECK_TRACKING
            }
            val flags = PendingIntent.FLAG_UPDATE_CURRENT or
                (if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) PendingIntent.FLAG_IMMUTABLE else 0)
            val pi = PendingIntent.getBroadcast(context, 8801, intent, flags)

            val nowWib = Calendar.getInstance(TimeZone.getTimeZone("Asia/Jakarta"))
            val hour = nowWib.get(Calendar.HOUR_OF_DAY)

            val triggerAtMillis: Long = if (hour in WORK_START_HOUR until WORK_END_HOUR) {
                // Selama jam kerja 06:00-20:00: exact alarm setiap 70 detik agar tetap Online (<120 detik) walau app di-swipe
                System.currentTimeMillis() + (delayMsOverride ?: 70_000L)
            } else {
                val nextStart = Calendar.getInstance(TimeZone.getTimeZone("Asia/Jakarta")).apply {
                    if (hour >= WORK_END_HOUR) {
                        add(Calendar.DAY_OF_YEAR, 1)
                    }
                    set(Calendar.HOUR_OF_DAY, WORK_START_HOUR)
                    set(Calendar.MINUTE, 0)
                    set(Calendar.SECOND, 5)
                    set(Calendar.MILLISECOND, 0)
                }
                nextStart.timeInMillis
            }

            try {
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
                    am.setExactAndAllowWhileIdle(AlarmManager.RTC_WAKEUP, triggerAtMillis, pi)
                } else {
                    am.setExact(AlarmManager.RTC_WAKEUP, triggerAtMillis, pi)
                }
            } catch (_: Throwable) {
                try {
                    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
                        am.setAndAllowWhileIdle(AlarmManager.RTC_WAKEUP, triggerAtMillis, pi)
                    } else {
                        am.set(AlarmManager.RTC_WAKEUP, triggerAtMillis, pi)
                    }
                } catch (_: Throwable) {}
            }
        }

        private fun cancelWatchdogAlarms(context: Context) {
            val am = context.getSystemService(Context.ALARM_SERVICE) as? AlarmManager ?: return
            val intent = Intent(context, TrackingScheduleReceiver::class.java).apply {
                action = TrackingScheduleReceiver.ACTION_CHECK_TRACKING
            }
            val flags = PendingIntent.FLAG_NO_CREATE or
                (if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) PendingIntent.FLAG_IMMUTABLE else 0)
            val pi = PendingIntent.getBroadcast(context, 8801, intent, flags)
            if (pi != null) {
                am.cancel(pi)
                pi.cancel()
            }
        }
    }

    private val mainHandler = Handler(Looper.getMainLooper())
    private val ioExecutor = Executors.newSingleThreadExecutor()
    private var locationManager: LocationManager? = null
    private var wakeLock: PowerManager.WakeLock? = null
    private var bestLocation: Location? = null
    private var lastSentTimeMs: Long = 0L
    private var lastSentLocation: Location? = null
    private var listenersRegistered = false

    private val heartbeatRunnable = object : Runnable {
        override fun run() {
            val teknisiId = getSavedTeknisiId(this@LiveLocationService)
            if (teknisiId.isNullOrEmpty() || !isWithinWorkingHours() || !hasForegroundLocationPermission(this@LiveLocationService)) {
                scheduleNextCheckAlarm(this@LiveLocationService)
                stopSelfCleanly()
                return
            }

            scheduleNextCheckAlarm(this@LiveLocationService)

            if (!isLocationProviderEnabled(this@LiveLocationService)) {
                showGpsOffWarning(this@LiveLocationService)
                val bm = getSystemService(Context.BATTERY_SERVICE) as? BatteryManager
                val batPct = bm?.getIntProperty(BatteryManager.BATTERY_PROPERTY_CAPACITY)
                ioExecutor.execute {
                    postJsonSync(JSONObject().apply {
                        put("teknisi_id", teknisiId)
                        put("gps_disabled", 1)
                        if (batPct != null && batPct in 0..100) put("battery", batPct.toString())
                    })
                }
                mainHandler.postDelayed(this, 25_000L)
                return
            }

            dismissGpsOffWarning(this@LiveLocationService)
            ensureLocationListeners()

            val candidate = pickBestAvailableLocation()
            if (candidate != null) {
                postLocationAsync(teknisiId, candidate, forceHeartbeat = true)
            }

            mainHandler.postDelayed(this, 25_000L)
        }
    }

    override fun onCreate() {
        super.onCreate()
        createNotificationChannel()
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        val teknisiId = getSavedTeknisiId(this)
        if (teknisiId.isNullOrEmpty() || !hasForegroundLocationPermission(this) || !isWithinWorkingHours()) {
            scheduleNextCheckAlarm(this)
            stopSelfCleanly()
            return START_NOT_STICKY
        }

        try {
            val notification = buildNotification()
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
                startForeground(NOTIFICATION_ID, notification, ServiceInfo.FOREGROUND_SERVICE_TYPE_LOCATION)
            } else {
                startForeground(NOTIFICATION_ID, notification)
            }
        } catch (_: Throwable) {
            stopSelf()
            return START_NOT_STICKY
        }

        acquirePartialWakeLock()
        ensureLocationListeners()
        scheduleNextCheckAlarm(this)

        mainHandler.removeCallbacks(heartbeatRunnable)
        mainHandler.post(heartbeatRunnable)

        return START_STICKY
    }

    override fun onTaskRemoved(rootIntent: Intent?) {
        val teknisiId = getSavedTeknisiId(this)
        if (!teknisiId.isNullOrEmpty() && isWithinWorkingHours()) {
            scheduleNextCheckAlarm(applicationContext, delayMsOverride = 3_000L)
            val am = getSystemService(Context.ALARM_SERVICE) as? AlarmManager
            val restartIntent = Intent(applicationContext, TrackingScheduleReceiver::class.java).apply {
                action = TrackingScheduleReceiver.ACTION_CHECK_TRACKING
            }
            val flags = PendingIntent.FLAG_ONE_SHOT or
                (if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) PendingIntent.FLAG_IMMUTABLE else 0)
            val pi = PendingIntent.getBroadcast(applicationContext, 8802, restartIntent, flags)
            if (am != null) {
                try {
                    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
                        am.setExactAndAllowWhileIdle(AlarmManager.ELAPSED_REALTIME_WAKEUP, SystemClock.elapsedRealtime() + 3_000L, pi)
                    } else {
                        am.set(AlarmManager.ELAPSED_REALTIME_WAKEUP, SystemClock.elapsedRealtime() + 3_000L, pi)
                    }
                } catch (_: Throwable) {}
            }
        }
        super.onTaskRemoved(rootIntent)
    }

    override fun onDestroy() {
        mainHandler.removeCallbacks(heartbeatRunnable)
        removeLocationListeners()
        releaseWakeLock()
        super.onDestroy()
    }

    override fun onBind(intent: Intent?): IBinder? = null

    override fun onLocationChanged(location: Location) {
        if (!isWithinWorkingHours()) {
            scheduleNextCheckAlarm(this)
            stopSelfCleanly()
            return
        }
        dismissGpsOffWarning(this)
        updateBestLocation(location)
        val teknisiId = getSavedTeknisiId(this) ?: return
        val chosen = bestLocation ?: location
        postLocationAsync(teknisiId, chosen, forceHeartbeat = false)
    }

    @Deprecated("Deprecated in Java")
    override fun onStatusChanged(provider: String?, status: Int, extras: Bundle?) {}
    override fun onProviderEnabled(provider: String) {
        dismissGpsOffWarning(this)
    }
    override fun onProviderDisabled(provider: String) {
        if (isWithinWorkingHours() && !isLocationProviderEnabled(this)) {
            showGpsOffWarning(this)
        }
    }

    private fun updateBestLocation(incoming: Location) {
        val current = bestLocation
        if (current == null) {
            bestLocation = incoming
            return
        }
        val timeDelta = incoming.time - current.time
        val isMuchNewer = timeDelta > 30_000L
        val isMoreAccurate = incoming.accuracy <= current.accuracy
        if (!isMuchNewer && current.accuracy < 35f && incoming.accuracy > 100f) {
            return
        }
        if (isMuchNewer || isMoreAccurate || timeDelta >= 0) {
            bestLocation = incoming
        }
    }

    private fun pickBestAvailableLocation(): Location? {
        val lm = locationManager ?: (getSystemService(Context.LOCATION_SERVICE) as? LocationManager) ?: return bestLocation
        var candidate: Location? = bestLocation
        for (provider in listOf("fused", LocationManager.GPS_PROVIDER, LocationManager.NETWORK_PROVIDER, LocationManager.PASSIVE_PROVIDER)) {
            try {
                if (!lm.allProviders.contains(provider)) continue
                if (hasForegroundLocationPermission(this)) {
                    @Suppress("MissingPermission")
                    val loc = lm.getLastKnownLocation(provider)
                    if (loc != null && (candidate == null || loc.time > candidate.time)) {
                        candidate = loc
                    }
                }
            } catch (_: Throwable) {}
        }
        if (candidate != null) bestLocation = candidate
        return candidate
    }

    private fun ensureLocationListeners() {
        if (listenersRegistered || !hasForegroundLocationPermission(this)) return
        val lm = (getSystemService(Context.LOCATION_SERVICE) as? LocationManager) ?: return
        locationManager = lm
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            try {
                @Suppress("MissingPermission")
                if (lm.allProviders.contains("fused")) {
                    lm.requestLocationUpdates("fused", 15_000L, 10f, this, Looper.getMainLooper())
                }
            } catch (_: Throwable) {}
        }
        try {
            @Suppress("MissingPermission")
            if (lm.allProviders.contains(LocationManager.GPS_PROVIDER)) {
                lm.requestLocationUpdates(LocationManager.GPS_PROVIDER, 15_000L, 12f, this, Looper.getMainLooper())
            }
        } catch (_: Throwable) {}
        try {
            @Suppress("MissingPermission")
            if (lm.allProviders.contains(LocationManager.NETWORK_PROVIDER)) {
                lm.requestLocationUpdates(LocationManager.NETWORK_PROVIDER, 20_000L, 15f, this, Looper.getMainLooper())
            }
        } catch (_: Throwable) {}
        listenersRegistered = true
    }

    private fun removeLocationListeners() {
        if (!listenersRegistered) return
        try {
            locationManager?.removeUpdates(this)
        } catch (_: Throwable) {}
        listenersRegistered = false
    }

    private fun postLocationAsync(teknisiId: String, location: Location, forceHeartbeat: Boolean) {
        val now = System.currentTimeMillis()
        val prevLoc = lastSentLocation
        if (!forceHeartbeat && prevLoc != null) {
            val movedMeters = prevLoc.distanceTo(location)
            val elapsedSec = (now - lastSentTimeMs) / 1000L
            if (movedMeters < 8f && elapsedSec < 18L) {
                return
            }
        }
        if (forceHeartbeat && (now - lastSentTimeMs) < 18_000L) {
            return
        }

        lastSentTimeMs = now
        lastSentLocation = location

        val isMock = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            location.isMock
        } else {
            @Suppress("DEPRECATION")
            location.isFromMockProvider
        }
        val speedKmh = if (location.hasSpeed()) (location.speed * 3.6f) else 0f
        val heading = if (location.hasBearing()) location.bearing else 0f
        val bm = getSystemService(Context.BATTERY_SERVICE) as? BatteryManager
        val batPct = bm?.getIntProperty(BatteryManager.BATTERY_PROPERTY_CAPACITY)

        ioExecutor.execute {
            postJsonSync(JSONObject().apply {
                put("teknisi_id", teknisiId)
                put("latitude", location.latitude.toString())
                put("longitude", location.longitude.toString())
                put("accuracy", String.format("%.1f", location.accuracy))
                put("speed", String.format("%.1f", speedKmh))
                put("heading", String.format("%.1f", heading))
                put("is_mock", if (isMock) 1 else 0)
                put("gps_disabled", 0)
                if (batPct != null && batPct in 0..100) {
                    put("battery", batPct.toString())
                }
            })
        }
    }

    private fun acquirePartialWakeLock() {
        if (wakeLock?.isHeld == true) return
        try {
            val pm = getSystemService(Context.POWER_SERVICE) as? PowerManager ?: return
            wakeLock = pm.newWakeLock(PowerManager.PARTIAL_WAKE_LOCK, "LoewixTeknisi::LiveGpsWakeLock").apply {
                setReferenceCounted(false)
                acquire(14 * 60 * 60 * 1000L)
            }
        } catch (_: Throwable) {}
    }

    private fun releaseWakeLock() {
        try {
            if (wakeLock?.isHeld == true) {
                wakeLock?.release()
            }
        } catch (_: Throwable) {}
        wakeLock = null
    }

    private fun stopSelfCleanly() {
        removeLocationListeners()
        releaseWakeLock()
        try {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.N) {
                stopForeground(STOP_FOREGROUND_REMOVE)
            } else {
                @Suppress("DEPRECATION")
                stopForeground(true)
            }
        } catch (_: Throwable) {}
        stopSelf()
    }

    private fun createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val nm = getSystemService(Context.NOTIFICATION_SERVICE) as? NotificationManager ?: return
            val channel = NotificationChannel(
                CHANNEL_ID,
                "Pemantauan Lokasi Jam Kerja",
                NotificationManager.IMPORTANCE_LOW
            ).apply {
                description = "Aktif otomatis jam 06:00 - 20:00 WIB untuk koordinasi lapangan"
                setShowBadge(false)
            }
            nm.createNotificationChannel(channel)
        }
    }

    private fun buildNotification(): Notification {
        val openIntent = Intent(this, MainActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_SINGLE_TOP or Intent.FLAG_ACTIVITY_CLEAR_TOP
        }
        val piFlags = PendingIntent.FLAG_UPDATE_CURRENT or
            (if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) PendingIntent.FLAG_IMMUTABLE else 0)
        val contentPi = PendingIntent.getActivity(this, 0, openIntent, piFlags)

        val builder = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            Notification.Builder(this, CHANNEL_ID)
        } else {
            @Suppress("DEPRECATION")
            Notification.Builder(this)
        }

        return builder
            .setContentTitle("Loewix: Tracking Jam Kerja Aktif")
            .setContentText("Lokasi dipantau otomatis jam 06:00 – 20:00 WIB")
            .setSmallIcon(R.mipmap.ic_launcher)
            .setContentIntent(contentPi)
            .setOngoing(true)
            .build()
    }
}
