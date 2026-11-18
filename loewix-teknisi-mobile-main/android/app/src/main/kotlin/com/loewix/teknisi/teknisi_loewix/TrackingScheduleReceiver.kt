package com.loewix.teknisi.teknisi_loewix

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import java.util.concurrent.Executors

class TrackingScheduleReceiver : BroadcastReceiver() {
    companion object {
        const val ACTION_CHECK_TRACKING = "com.loewix.teknisi.ACTION_CHECK_TRACKING"
        private val bgExecutor = Executors.newSingleThreadExecutor()
    }

    override fun onReceive(context: Context, intent: Intent?) {
        val appCtx = context.applicationContext
        // 1. Jadwalkan kembali alarm berikutnya & coba nyalakan ForegroundService
        LiveLocationService.startOrSchedule(appCtx, fromBackground = true)

        // 2. Kirim ping lokasi langsung via goAsync() agar tetap Online walau app di-swipe close
        val pendingResult = goAsync()
        bgExecutor.execute {
            try {
                LiveLocationService.sendDirectBackgroundPing(appCtx)
            } catch (_: Throwable) {
            } finally {
                try {
                    pendingResult.finish()
                } catch (_: Throwable) {}
            }
        }
    }
}
