package com.loewix.teknisi.teknisi_loewix

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent

class TrackingScheduleReceiver : BroadcastReceiver() {
    companion object {
        const val ACTION_CHECK_TRACKING = "com.loewix.teknisi.ACTION_CHECK_TRACKING"
    }

    override fun onReceive(context: Context, intent: Intent?) {
        LiveLocationService.startOrSchedule(context.applicationContext, fromBackground = true)
    }
}

