<?php
include "conn.php";
include "session.php";
include "get-user-data.php";

include_once "menu-access-helper.php";
if (!hasMenuAccess($conn, $idSesi, 'live_tracking', ($role == 'Super Admin' || $role == 'Admin'))) {
    header("Location: index-sa.php");
    exit;
}

$pageNow = "Live Tracking";
$todayStr = date("Y-m-d");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <?php include "head.php"; ?>
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <!-- Google Fonts Poppins & Inter -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <style>
    :root {
        --lt-orange: #ff5b22;
        --lt-orange-dark: #e04812;
        --lt-dark: #0f172a;
        --lt-slate: #64748b;
        --scrollbar-thumb: #64748b;
        --scrollbar-track: #f1f5f9;
    }

    body {
        font-family: 'Poppins', 'Inter', -apple-system, sans-serif !important;
    }

    .visually-hidden:where(:not(:focus-within, :active)) {
        position: absolute !important;
        clip-path: inset(50%) !important;
        overflow: hidden !important;
        width: 1px !important;
        height: 1px !important;
        margin: -1px !important;
        padding: 0 !important;
        border: 0 !important;
        white-space: nowrap !important;
    }

    .live-tracking-wrapper :where(a:any-link, button, input):focus-visible {
        outline: 2px solid var(--lt-orange);
        outline-offset: 2px;
    }

    .main-content {
        display: flex !important;
        flex-direction: column !important;
        height: 100vh !important;
        height: 100dvh !important;
        max-height: 100vh !important;
        max-height: 100dvh !important;
        overflow: hidden !important;
    }

    body:has(.btm-nav) .live-tracking-wrapper {
        margin-bottom: calc(68px + env(safe-area-inset-bottom, 0px)) !important;
    }
    body:has(.btm-nav) .navbar-main {
        padding-top: 0.25rem !important;
        padding-bottom: 0.25rem !important;
    }

    .live-tracking-wrapper {
        flex: 1 1 auto;
        min-height: 0;
        display: flex;
        margin: 0 20px 20px 20px;
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        position: relative;
    }

    /* ─── Left Drawer ─── */
    .tracking-drawer {
        width: 425px;
        min-width: 360px;
        max-width: 440px;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        border-right: 1px solid #e2e8f0;
        height: 100%;
        z-index: 10;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .tracking-drawer-header {
        padding: 14px 16px 12px;
        background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
        color: #ffffff;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .drawer-title-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .header-icon-box {
        width: 38px;
        height: 38px;
        border-radius: 11px;
        background: rgba(255, 91, 34, 0.2);
        border: 1px solid rgba(255, 91, 34, 0.4);
        color: #ff7a45;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .live-pill {
        background: rgba(16, 185, 129, 0.16);
        border: 1px solid rgba(16, 185, 129, 0.4);
        color: #6ee7b7;
        font-size: 10.5px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    .live-dot {
        width: 7px;
        height: 7px;
        background: #10b981;
        border-radius: 50%;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
        animation: livePulse 1.4s infinite;
    }

    @keyframes livePulse {
        0%, 100% { opacity: 0.45; transform: scale(0.92); }
        50% { opacity: 1; transform: scale(1.08); }
    }

    .drawer-date-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        background: rgba(255, 255, 255, 0.07);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 10px;
        padding: 6px 11px;
    }

    .date-chip-left {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-inline-size: 0;
    }

    .date-input-clean {
        position: relative;
        background: transparent;
        border: none;
        padding: 0;
        color: #f8fafc;
        font-size: 11.5px;
        font-weight: 600;
        font-family: inherit;
        cursor: pointer;
        color-scheme: dark;
    }
    .date-input-clean::-webkit-calendar-picker-indicator {
        position: absolute;
        inset: 0;
        inline-size: 100%;
        block-size: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .sync-time-lbl {
        font-size: 10.5px;
        color: #94a3b8;
        white-space: nowrap;
    }

    /* ─── Filter & Search ─── */
    .tracking-filter-pane {
        padding: 12px 14px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        gap: 9px;
    }

    .tracking-search-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }

    .tracking-search-wrap .search-ico {
        position: absolute;
        left: 12px;
        color: #64748b;
        font-size: 12.5px;
        pointer-events: none;
    }

    .tracking-search-input {
        width: 100%;
        padding: 8px 32px 8px 34px;
        border: 1.5px solid #cbd5e1;
        border-radius: 10px;
        font-size: 12px;
        background: #ffffff;
        color: #0f172a;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .tracking-search-input:focus {
        border-color: var(--lt-orange);
        box-shadow: 0 0 0 3px rgba(255, 91, 34, 0.12);
    }

    .btn-clear-search {
        position: absolute;
        right: 8px;
        width: 22px;
        height: 22px;
        border: none;
        border-radius: 50%;
        background: #e2e8f0;
        color: #475569;
        font-size: 11px;
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .btn-clear-search.visible { display: inline-flex; }

    .tracking-type-pills {
        display: flex;
        flex-wrap: wrap;
        flex-wrap: balance;
        gap: 5px;
    }

    .type-pill {
        padding: 4px 9px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #475569;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: background 0.15s, color 0.15s, border-color 0.15s;
        min-height: 27px;
    }
    .type-pill:hover {
        border-color: #cbd5e1;
        color: #0f172a;
    }
    .type-pill[aria-pressed="true"] {
        background: #0f172a;
        color: #ffffff;
        border-color: #0f172a;
    }
    .type-pill.pill-online[aria-pressed="true"] {
        background: var(--lt-orange);
        border-color: var(--lt-orange);
        color: #ffffff;
    }
    .type-pill.pill-alert {
        border-color: #fecdd3;
        color: #be123c;
        background: #fff1f2;
    }
    .type-pill.pill-alert[aria-pressed="true"] {
        background: #e11d48;
        border-color: #e11d48;
        color: #ffffff;
    }
    .type-pill.pill-today {
        border-color: #fecaca;
        color: #b91c1c;
        background: #fef2f2;
    }
    .type-pill.pill-today[aria-pressed="true"] {
        background: #ef4444;
        border-color: #ef4444;
        color: #ffffff;
    }
    .type-pill.pill-upcoming {
        border-color: #bfdbfe;
        color: #1d4ed8;
        background: #eff6ff;
    }
    .type-pill.pill-upcoming[aria-pressed="true"] {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
    }
    .type-pill.pill-waiting {
        border-color: #ddd6fe;
        color: #6d28d9;
        background: #f5f3ff;
    }
    .type-pill.pill-waiting[aria-pressed="true"] {
        background: #7c3aed;
        border-color: #7c3aed;
        color: #ffffff;
    }

    /* ─── Scrollable Activity List (Standard Scrollbar + Fallback) ─── */
    .tracking-cards-list {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        scrollbar-gutter: stable;
        overscroll-behavior: contain;
        padding: 12px;
        display: flex;
        flex-direction: column;
        gap: 11px;
        background: #f1f5f9;
        scrollbar-width: thin;
        scrollbar-color: var(--scrollbar-thumb) var(--scrollbar-track);
    }

    @supports not (scrollbar-color: auto) {
        .tracking-cards-list {
            scrollbar-width: auto;
        }
        .tracking-cards-list::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .tracking-cards-list::-webkit-scrollbar-thumb {
            background: var(--scrollbar-thumb);
            border-radius: 4px;
        }
        .tracking-cards-list::-webkit-scrollbar-track {
            background: var(--scrollbar-track);
        }
    }

    /* ─── Semantic Card (<article>) with Stretched Primary Button ─── */
    .kegiatan-card {
        container: keg-card / inline-size;
        position: relative;
        background: #ffffff;
        border: 2px solid #e2e8f0;
        border-radius: 14px;
        padding: 13px 14px;
        transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
    }
    .kegiatan-card:hover,
    .kegiatan-card:has(.card-link:hover) {
        border-color: #fdba74;
        box-shadow: 0 6px 16px rgba(255, 91, 34, 0.1);
    }
    .kegiatan-card.selected {
        border-color: var(--lt-orange);
        background: #fffaf7;
        box-shadow: 0 6px 18px rgba(255, 91, 34, 0.15);
    }
    .kegiatan-card.card-upcoming {
        border-color: #dbeafe;
        border-left: 4.5px solid #2563eb;
        background: #fbfdff;
    }
    .kegiatan-card.card-upcoming:hover,
    .kegiatan-card.card-upcoming:has(.card-link:hover) {
        border-color: #93c5fd;
        border-left-color: #2563eb;
        box-shadow: 0 6px 16px rgba(37, 99, 235, 0.12);
    }
    .kegiatan-card.card-upcoming.selected {
        border-color: #2563eb;
        background: #eff6ff;
        box-shadow: 0 6px 18px rgba(37, 99, 235, 0.2);
    }
    .kegiatan-card.card-upcoming .btn-focus-map {
        background: #2563eb;
    }
    .kegiatan-card.card-upcoming .btn-focus-map:hover {
        background: #1d4ed8;
    }
    .kegiatan-card.card-upcoming.selected .btn-focus-map {
        background: #1e3a8a;
    }
    .kegiatan-card.card-waiting {
        border-color: #ddd6fe;
        border-left: 4.5px solid #7c3aed;
        background: #fdfcff;
    }
    .kegiatan-card.card-waiting:hover,
    .kegiatan-card.card-waiting:has(.card-link:hover) {
        border-color: #a78bfa;
        border-left-color: #7c3aed;
        box-shadow: 0 6px 16px rgba(124, 58, 237, 0.12);
    }
    .kegiatan-card.card-waiting.selected {
        border-color: #7c3aed;
        background: #faf5ff;
        box-shadow: 0 6px 18px rgba(124, 58, 237, 0.2);
    }
    .kegiatan-card.card-waiting .btn-focus-map {
        background: #7c3aed;
    }
    .kegiatan-card.card-waiting .btn-focus-map:hover {
        background: #6d28d9;
    }
    .kegiatan-card.card-waiting.selected .btn-focus-map {
        background: #4c1d95;
    }
    .kegiatan-card:has(.card-link:focus-visible) {
        outline: 2px solid var(--lt-orange);
        outline-offset: 2px;
    }

    .kegiatan-card .card-link {
        background: none;
        border: none;
        padding: 0;
        margin: 0;
        font: inherit;
        color: inherit;
        text-align: left;
        cursor: pointer;
    }
    .kegiatan-card .card-link:focus-visible {
        outline: none;
    }
    .kegiatan-card .card-link::after {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: 12px;
    }
    .kegiatan-card a:not(.card-link),
    .kegiatan-card button:not(.card-link) {
        position: relative;
        z-index: 1;
    }

    .keg-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        margin-bottom: 6px;
    }
    .badge-type {
        font-size: 9.5px;
        font-weight: 800;
        padding: 3px 8px;
        border-radius: 6px;
        text-transform: uppercase;
        letter-spacing: 0.35px;
    }
    .type-pasang  { background: #dcfce7; color: #15803d; }
    .type-service { background: #e0f2fe; color: #0369a1; }
    .type-survey  { background: #fef3c7; color: #b45309; }
    .type-default { background: #f1f5f9; color: #475569; }

    .keg-meta-right {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .keg-time {
        font-size: 11px;
        font-weight: 600;
        color: #475569;
        background: #f1f5f9;
        padding: 2px 7px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .keg-kode {
        font-size: 10.5px;
        font-weight: 700;
        color: #64748b;
    }

    .keg-customer {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 4px 0;
        line-height: 1.3;
    }

    .keg-keterangan {
        font-size: 11px;
        color: #475569;
        font-style: italic;
        margin-bottom: 7px;
        background: #f8fafc;
        padding: 5px 9px;
        border-radius: 7px;
        border-left: 3px solid var(--lt-orange);
    }

    .keg-alamat {
        font-size: 11.5px;
        color: #475569;
        display: flex;
        align-items: flex-start;
        gap: 6px;
        margin-bottom: 9px;
        line-height: 1.4;
    }
    .keg-alamat i {
        color: #ef4444;
        font-size: 12px;
        margin-top: 2px;
        flex-shrink: 0;
    }

    /* ─── Technician Rows inside Card ─── */
    .keg-teknisi-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 11px;
        padding: 8px 9px;
        margin-bottom: 10px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .keg-teknisi-title {
        font-size: 10px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        padding: 0 2px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
    }
    .keg-teknisi-hint {
        font-size: 10px;
        font-weight: 500;
        color: #94a3b8;
        text-transform: none;
        letter-spacing: 0;
    }
    .tek-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        padding: 7px 9px;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .tek-card.tek-card-alert {
        border-color: #fecdd3;
        background: #fffafa;
    }
    .tek-top-line {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        min-inline-size: 0;
    }
    .btn-tek-jump {
        background: none;
        border: none;
        padding: 2px 5px;
        margin: -2px -5px;
        border-radius: 6px;
        flex: 1 1 auto;
        min-inline-size: 0;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-align: left;
        cursor: pointer;
        transition: background 0.15s;
    }
    .btn-tek-jump:hover {
        background: #f1f5f9;
    }
    .tek-name {
        font-weight: 600;
        color: #0f172a;
        font-size: 12px;
        min-inline-size: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .tek-right-actions {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .tek-meta-line {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 5px;
        padding-inline-start: 18px;
    }
    .badge-ketua {
        flex-shrink: 0;
        font-size: 9px;
        font-weight: 700;
        background: #fef3c7;
        color: #92400e;
        padding: 1.5px 5px;
        border-radius: 4px;
    }
    .badge-status-work {
        flex-shrink: 0;
        font-size: 9.5px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 5px;
        text-transform: capitalize;
        white-space: nowrap;
    }
    .status-dkj  { background: #dbeafe; color: #1d4ed8; }
    .status-mlap { background: #ffedd5; color: #c2410c; }
    .status-sls  { background: #dcfce7; color: #15803d; }
    .status-jdw  { background: #e2e8f0; color: #475569; }

    .gps-pill-online {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #047857;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 999px;
        white-space: nowrap;
    }
    .tek-meta-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #334155;
        font-size: 10px;
        font-weight: 600;
        padding: 2px 7px;
        border-radius: 999px;
        white-space: nowrap;
    }
    .tek-meta-chip.bat-low {
        background: #fff1f2;
        border-color: #fecdd3;
        color: #be123c;
    }
    .gps-pill-offline {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #f1f5f9;
        color: #64748b;
        font-size: 10px;
        font-weight: 600;
        padding: 2px 7px;
        border-radius: 999px;
        white-space: nowrap;
    }
    .gps-pill-alert {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #fff1f2;
        border: 1px solid #fecdd3;
        color: #be123c;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 999px;
        white-space: nowrap;
    }
    .dot-online {
        width: 6px;
        height: 6px;
        background: #10b981;
        border-radius: 50%;
    }
    .dot-offline {
        width: 6px;
        height: 6px;
        background: #94a3b8;
        border-radius: 50%;
    }
    .dot-alert {
        width: 6px;
        height: 6px;
        background: #e11d48;
        border-radius: 50%;
    }

    .btn-wa-mini {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        background: #dcfce7;
        color: #15803d;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        text-decoration: none;
        flex-shrink: 0;
    }
    .btn-wa-mini:hover {
        background: #16a34a;
        color: #ffffff;
    }

    .keg-actions {
        display: flex;
        align-items: center;
        gap: 7px;
    }
    .btn-focus-map {
        flex: 1;
        min-height: 32px;
        padding: 6px 12px;
        background: var(--lt-orange);
        color: #ffffff;
        border: none;
        border-radius: 9px;
        font-size: 11.5px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: background 0.2s;
    }
    .btn-focus-map:hover { background: var(--lt-orange-dark); }
    .kegiatan-card.selected .btn-focus-map {
        background: #0f172a;
    }

    .btn-call-cust {
        min-height: 32px;
        padding: 6px 10px;
        background: #f0fdf4;
        color: #15803d;
        border: 1px solid #bbf7d0;
        border-radius: 9px;
        font-size: 11px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .btn-call-cust:hover { background: #dcfce7; color: #166534; }

    /* ─── Right Map Panel ─── */
    .tracking-map-panel {
        flex: 1;
        min-width: 0;
        position: relative;
        height: 100%;
        background: #e2e8f0;
    }
    #map {
        width: 100%;
        height: 100%;
        z-index: 1;
    }

    /* Top Floating Bar */
    .map-floating-bar {
        position: absolute;
        top: 14px;
        left: 14px;
        right: 14px;
        z-index: 1000;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        pointer-events: none;
        gap: 10px;
        flex-wrap: wrap;
    }
    .map-stats-pill {
        pointer-events: auto;
        background: rgba(15, 23, 42, 0.92);
        backdrop-filter: blur(10px);
        color: #ffffff;
        padding: 7px 14px;
        border-radius: 30px;
        display: flex;
        align-items: center;
        gap: 11px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.22);
        border: 1px solid rgba(255, 255, 255, 0.14);
    }
    .stat-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11.5px;
        font-weight: 600;
        white-space: nowrap;
        background: none;
        border: none;
        color: #ffffff;
        padding: 2px 4px;
        border-radius: 12px;
    }
    button.stat-item { cursor: pointer; }
    button.stat-item:hover { background: rgba(255, 255, 255, 0.12); }
    .stat-divider {
        width: 1px;
        height: 14px;
        background: rgba(255, 255, 255, 0.2);
    }

    .map-actions-group {
        pointer-events: auto;
        display: flex;
        align-items: center;
        gap: 7px;
        flex-wrap: wrap;
    }
    .map-action-btn {
        background: rgba(15, 23, 42, 0.92);
        backdrop-filter: blur(10px);
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 30px;
        padding: 7px 13px;
        font-size: 11.5px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.18);
        transition: background 0.15s, border-color 0.15s;
        white-space: nowrap;
    }
    .map-action-btn:hover,
    .map-action-btn[aria-pressed="true"] {
        background: var(--lt-orange);
        border-color: var(--lt-orange);
    }

    /* Active Focus Banner on Map */
    .focus-banner {
        position: absolute;
        top: 60px;
        left: 14px;
        z-index: 1000;
        background: #ffffff;
        border: 1.5px solid var(--lt-orange);
        border-radius: 14px;
        padding: 8px 12px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.16);
        display: none;
        align-items: center;
        gap: 10px;
        max-width: min(460px, calc(100% - 28px));
    }
    .focus-banner.visible { display: flex; }
    .focus-banner-info {
        font-size: 11.5px;
        color: #0f172a;
        line-height: 1.35;
    }
    .focus-eta-pill {
        display: inline-block;
        background: #fff7ed;
        color: #c2410c;
        border: 1px solid #fed7aa;
        font-size: 10.5px;
        font-weight: 700;
        padding: 1px 7px;
        border-radius: 10px;
        margin-left: 4px;
    }
    .btn-exit-focus {
        background: #f1f5f9;
        border: none;
        color: #334155;
        font-size: 11px;
        font-weight: 700;
        padding: 5px 10px;
        border-radius: 8px;
        cursor: pointer;
        white-space: nowrap;
    }
    .btn-exit-focus:hover { background: #e2e8f0; color: #0f172a; }

    /* Bottom-Left Map Legend & Style Switcher */
    .map-legend-card {
        position: absolute;
        bottom: 16px;
        left: 14px;
        z-index: 999;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(8px);
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 8px 12px;
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.12);
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        font-size: 11px;
        color: #334155;
        font-weight: 600;
    }
    .legend-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }
    .legend-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        border: 2px solid #ffffff;
        box-shadow: 0 1px 4px rgba(0,0,0,0.25);
    }

    /* ─── Map Markers with Floating Labels ─── */
    .marker-wrap {
        position: relative;
        width: 38px;
        height: 38px;
    }
    .marker-pin-circle {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: 3px solid #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 14px;
        position: relative;
        z-index: 2;
    }
    .pin-customer {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.45);
    }
    .pin-upcoming {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        box-shadow: 0 4px 14px rgba(37, 99, 235, 0.5);
    }
    .pin-waiting {
        background: linear-gradient(135deg, #8b5cf6, #6d28d9);
        box-shadow: 0 4px 14px rgba(124, 58, 237, 0.55);
    }
    .pin-online {
        background: linear-gradient(135deg, #ff7a00, #ff5b22);
        box-shadow: 0 4px 14px rgba(255, 91, 34, 0.55);
    }
    .pin-offline {
        background: #64748b;
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.3);
    }
    .pulse-ring {
        position: absolute;
        inset: -5px;
        border-radius: 50%;
        border: 2.5px solid #ff5b22;
        animation: markerHalo 1.8s ease-out infinite;
        z-index: 1;
        pointer-events: none;
    }
    @keyframes markerHalo {
        0% { transform: scale(0.85); opacity: 0.9; }
        100% { transform: scale(1.45); opacity: 0; }
    }

    .marker-name-tag {
        position: absolute;
        top: 41px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(15, 23, 42, 0.88);
        color: #ffffff;
        font-family: 'Poppins', sans-serif;
        font-size: 10px;
        font-weight: 600;
        padding: 2px 7px;
        border-radius: 8px;
        white-space: nowrap;
        max-width: 130px;
        overflow: hidden;
        text-overflow: ellipsis;
        box-shadow: 0 2px 6px rgba(0,0,0,0.22);
        pointer-events: none;
        z-index: 3;
    }
    .marker-name-tag.tag-cust {
        background: rgba(153, 27, 27, 0.9);
    }
    .marker-name-tag.tag-upcoming {
        background: rgba(30, 64, 175, 0.92);
    }
    .marker-name-tag.tag-waiting {
        background: rgba(91, 33, 182, 0.92);
    }
    .hide-map-labels .marker-name-tag {
        display: none !important;
    }

    /* Modern Leaflet Popup Override */
    .leaflet-popup-content-wrapper {
        border-radius: 14px !important;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.18) !important;
        padding: 2px !important;
    }
    .leaflet-popup-content {
        margin: 12px 14px !important;
        font-family: 'Poppins', sans-serif !important;
    }

    @media (max-width: 991px) {
        .btn-drawer-map {
            min-height: 28px;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid rgba(255, 122, 69, 0.5);
            background: var(--lt-orange);
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }
        .live-tracking-wrapper {
            margin: 0 10px 14px 10px;
            border-radius: 14px;
        }
        .tracking-drawer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            max-width: 100%;
            height: 100%;
            z-index: 1010;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        }
        .tracking-drawer.mobile-hidden {
            transform: translateX(-105%);
            pointer-events: none;
        }
        .map-legend-card {
            bottom: 10px;
            left: 10px;
            right: 52px;
            gap: 8px;
            font-size: 10px;
        }
    }
    @media (min-width: 992px) {
        .tracking-drawer.mobile-hidden {
            transform: none !important;
            pointer-events: auto !important;
        }
    }
  </style>
</head>

<body class="g-sidenav-show bg-gray-200">
  <?php include "cek-menu.php"; ?>

  <main class="main-content position-relative border-radius-lg">
    <?php include "nav-top.php"; ?>

    <div class="live-tracking-wrapper">
      <!-- Left Drawer -->
      <div class="tracking-drawer" id="trackingDrawer">
        <div class="tracking-drawer-header">
          <div class="drawer-title-row">
            <div class="d-flex align-items-center gap-2">
              <div class="header-icon-box" aria-hidden="true">
                <i class="fa-solid fa-map-location-dot"></i>
              </div>
              <div>
                <h1 class="mb-0 fw-bold" style="font-size: 14px; color: #ffffff; line-height: 1.25;">Live Tracking Teknisi</h1>
                <div style="font-size: 11px; color: #94a3b8;">Jam pantau wajib: 06:00 – 20:00 WIB</div>
              </div>
            </div>
            <div class="d-flex align-items-center gap-2">
              <div class="live-pill" title="Data diperbarui otomatis setiap 5 detik">
                <span class="live-dot" aria-hidden="true"></span> LIVE
              </div>
              <button type="button" class="btn-drawer-map d-lg-none" onclick="toggleDrawerMobile()" aria-label="Lihat peta penuh">
                <i class="fa-solid fa-map" aria-hidden="true"></i> Peta
              </button>
            </div>
          </div>

          <div class="drawer-date-bar">
            <div class="date-chip-left">
              <i class="fa-regular fa-calendar" style="color:#ff7a45; font-size:12px;" aria-hidden="true"></i>
              <label for="trackingDate" class="visually-hidden">Pilih tanggal jadwal tracking</label>
              <input type="date" id="trackingDate" class="date-input-clean" value="<?php echo htmlspecialchars($todayStr); ?>">
            </div>
            <span class="sync-time-lbl" id="lastSyncText">Memuat...</span>
          </div>
        </div>

        <!-- Search & Filter Pills -->
        <div class="tracking-filter-pane">
          <div class="tracking-search-wrap">
            <label for="searchInput" class="visually-hidden">Cari nama customer, teknisi, atau kode kegiatan</label>
            <i class="fa-solid fa-magnifying-glass search-ico" aria-hidden="true"></i>
            <input type="text" id="searchInput" class="tracking-search-input" placeholder="Cari customer, nama teknisi, #kode...">
            <button type="button" id="btnClearSearch" class="btn-clear-search" aria-label="Hapus pencarian" onclick="clearSearchInput()">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </div>
          <div class="tracking-type-pills" role="group" aria-label="Filter kegiatan">
            <button type="button" class="type-pill" data-type="all" aria-pressed="true" onclick="filterType('all', this)">Semua (<span id="countAll">0</span>)</button>
            <button type="button" class="type-pill pill-today" data-type="today" aria-pressed="false" onclick="filterType('today', this)"><i class="fa-solid fa-store" aria-hidden="true"></i> Hari Ini (<span id="countToday">0</span>)</button>
            <button type="button" class="type-pill pill-upcoming" data-type="upcoming" aria-pressed="false" onclick="filterType('upcoming', this)"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i> Akan Datang (<span id="countUpcoming">0</span>)</button>
            <button type="button" class="type-pill pill-waiting" data-type="waiting" aria-pressed="false" onclick="filterType('waiting', this)"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> Waiting (<span id="countWaiting">0</span>)</button>
            <button type="button" class="type-pill pill-online" data-type="online" aria-pressed="false" onclick="filterType('online', this)"><i class="fa-solid fa-satellite-dish" aria-hidden="true"></i> GPS Online (<span id="countOnlineCard">0</span>)</button>
            <button type="button" class="type-pill pill-alert" id="pillGpsMati" data-type="gps_mati" aria-pressed="false" hidden onclick="filterType('gps_mati', this)"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> GPS Mati (<span id="countGpsMati">0</span>)</button>
            <button type="button" class="type-pill" data-type="berjalan" aria-pressed="false" onclick="filterType('berjalan', this)">Dikerjakan (<span id="countBerjalan">0</span>)</button>
            <button type="button" class="type-pill" data-type="pasang" aria-pressed="false" onclick="filterType('pasang', this)">Pasang (<span id="countPasang">0</span>)</button>
            <button type="button" class="type-pill" data-type="service" aria-pressed="false" onclick="filterType('service', this)">Service (<span id="countService">0</span>)</button>
            <button type="button" class="type-pill" data-type="survey" aria-pressed="false" onclick="filterType('survey', this)">Survey (<span id="countSurvey">0</span>)</button>
          </div>
        </div>

        <!-- Scrollable Cards -->
        <div class="tracking-cards-list" id="kegiatanList">
          <div style="text-align: center; color: #64748b; padding: 44px 20px; font-size: 13px;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px; color: #ff5b22; display:block; margin-bottom: 10px;" aria-hidden="true"></i>
            Memuat data kegiatan...
          </div>
        </div>
      </div>

      <!-- Right Map Panel -->
      <div class="tracking-map-panel" id="mapPanel">
        <!-- Top Floating Bar -->
        <div class="map-floating-bar">
          <div class="map-stats-pill">
            <button type="button" class="stat-item" onclick="quickToggleCategoryFilter('today')" title="Klik untuk memfilter jadwal Hari Ini">
              <i class="fa-solid fa-store" style="color: #f87171;" aria-hidden="true"></i>
              <span id="statKegiatan">0 Hari Ini</span>
            </button>
            <div class="stat-divider"></div>
            <button type="button" class="stat-item" onclick="quickToggleCategoryFilter('upcoming')" title="Klik untuk memfilter Kegiatan Akan Datang">
              <i class="fa-solid fa-calendar-days" style="color: #60a5fa;" aria-hidden="true"></i>
              <span id="statUpcoming">0 Akan Datang</span>
            </button>
            <div class="stat-divider"></div>
            <button type="button" class="stat-item" onclick="quickToggleCategoryFilter('waiting')" title="Klik untuk memfilter lokasi Waiting List (Belum dapat teknisi)">
              <i class="fa-solid fa-hourglass-half" style="color: #c4b5fd;" aria-hidden="true"></i>
              <span id="statWaiting">0 Waiting</span>
            </button>
            <div class="stat-divider"></div>
            <div class="stat-item">
              <i class="fa-solid fa-users" style="color: #a78bfa;" aria-hidden="true"></i>
              <span id="statTeknisi">0 Teknisi</span>
            </div>
            <div class="stat-divider"></div>
            <button type="button" class="stat-item" onclick="quickToggleOnlineFilter()" title="Klik untuk memfilter teknisi yang sedang Online GPS">
              <i class="fa-solid fa-motorcycle" style="color: #ff7a45;" aria-hidden="true"></i>
              <span id="statOnline">0 Online</span>
            </button>
          </div>

          <div class="map-actions-group">
            <button type="button" class="map-action-btn" id="btnToggleLabels" aria-pressed="true" onclick="toggleMapLabels()">
              <i class="fa-solid fa-tag" aria-hidden="true"></i> <span class="d-none d-sm-inline">Label Nama</span>
            </button>
            <button type="button" class="map-action-btn" id="btnMapStyle" onclick="cycleMapLayer()">
              <i class="fa-solid fa-layer-group" aria-hidden="true"></i> <span id="mapStyleLabel" class="d-none d-sm-inline">Google Maps</span>
            </button>
            <button type="button" class="map-action-btn" onclick="fitAllMapBounds()">
              <i class="fa-solid fa-expand" aria-hidden="true"></i> <span class="d-none d-sm-inline">Semua Titik</span>
            </button>
            <button type="button" class="map-action-btn" id="btnRefresh" onclick="manualRefresh()">
              <i class="fa-solid fa-arrows-rotate" id="refreshIcon" aria-hidden="true"></i> <span class="d-none d-sm-inline">Refresh</span>
            </button>
            <button type="button" class="map-action-btn d-lg-none" id="btnToggleDrawer" onclick="toggleDrawerMobile()">
              <i class="fa-solid fa-list-ul" aria-hidden="true"></i> <span id="btnToggleDrawerText">Daftar</span>
            </button>
          </div>
        </div>

        <!-- Focused Kegiatan Info Banner -->
        <div class="focus-banner" id="focusBanner">
          <div class="focus-banner-info" id="focusBannerText"></div>
          <button type="button" class="btn-exit-focus" onclick="fitAllMapBounds()">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i> Semua
          </button>
        </div>

        <!-- Bottom-Left Clear Legend -->
        <div class="map-legend-card" aria-label="Keterangan ikon peta">
          <span class="legend-item"><span class="legend-dot" style="background:#ff5b22;"></span> Motor Online (&le;2 mnt)</span>
          <span class="legend-item"><span class="legend-dot" style="background:#64748b;"></span> Lokasi Terakhir (&gt;2 mnt)</span>
          <span class="legend-item"><span class="legend-dot" style="background:#ef4444;"></span> Hari Ini</span>
          <span class="legend-item"><span class="legend-dot" style="background:#2563eb;"></span> Akan Datang</span>
          <span class="legend-item"><span class="legend-dot" style="background:#7c3aed;"></span> Waiting (Belum Teknisi)</span>
        </div>

        <div id="map"></div>
      </div>
    </div>
  </main>

  <!-- Leaflet JS -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <script>
    const map = L.map('map', { zoomControl: false }).setView([-6.2088, 106.8456], 11);
    L.control.zoom({ position: 'bottomright' }).addTo(map);

    const tilePresets = [
      {
        name: 'Google Maps',
        url: 'https://{s}.google.com/vt/lyrs=m&hl=id&x={x}&y={y}&z={z}',
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
        attr: 'Map data &copy; Google'
      },
      {
        name: 'Jalan OSM',
        url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        subdomains: ['a', 'b', 'c'],
        attr: '&copy; OpenStreetMap contributors'
      },
      {
        name: 'Satelit',
        url: 'https://{s}.google.com/vt/lyrs=y&hl=id&x={x}&y={y}&z={z}',
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
        attr: 'Imagery &copy; Google'
      }
    ];
    let currentTileIdx = 0;
    let activeTileLayer = L.tileLayer(tilePresets[0].url, {
      subdomains: tilePresets[0].subdomains,
      attribution: tilePresets[0].attr,
      maxZoom: 20
    }).addTo(map);

    function cycleMapLayer() {
      currentTileIdx = (currentTileIdx + 1) % tilePresets.length;
      map.removeLayer(activeTileLayer);
      activeTileLayer = L.tileLayer(tilePresets[currentTileIdx].url, {
        subdomains: tilePresets[currentTileIdx].subdomains,
        attribution: tilePresets[currentTileIdx].attr,
        maxZoom: 20
      }).addTo(map);
      const lbl = document.getElementById('mapStyleLabel');
      if (lbl) lbl.textContent = tilePresets[currentTileIdx].name;
    }

    setTimeout(() => { map.invalidateSize(); }, 300);
    window.addEventListener('resize', () => { map.invalidateSize(); });

    let kegiatanData = [];
    let activeTypeFilter = 'all';
    let customerMarkers = {};
    let teknisiMarkers = {};
    let routePolylines = [];
    let selectedKegiatanKode = null;
    let hasInitialFit = false;
    let showLabels = true;

    function escHtml(str) {
      return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    // Clear, unambiguous Indonesian relative duration (never "283m" which looks like meters)
    function formatLastSeen(seconds) {
      if (seconds === null || seconds === undefined || isNaN(seconds)) return 'Belum aktif';
      const sec = Math.max(0, parseInt(seconds, 10));
      if (sec < 60) return 'Baru saja';
      const mins = Math.floor(sec / 60);
      if (mins < 60) return `${mins} mnt lalu`;
      const hours = Math.floor(mins / 60);
      const remMins = mins % 60;
      if (hours < 24) {
        return remMins > 0 ? `${hours}j ${remMins}m lalu` : `${hours} jam lalu`;
      }
      const days = Math.floor(hours / 24);
      const remHours = hours % 24;
      return remHours > 0 ? `${days}hr ${remHours}j lalu` : `${days} hari lalu`;
    }

    function kegKey(keg) {
      return String(keg.track_key || (keg.category || 'keg') + '_' + (keg.id || keg.kode));
    }

    function shortLabel(name, maxLen = 15) {
      const s = String(name || '').trim();
      return s.length > maxLen ? s.slice(0, maxLen - 1) + '…' : s;
    }

    function toggleMapLabels() {
      showLabels = !showLabels;
      const mapEl = document.getElementById('map');
      const btn = document.getElementById('btnToggleLabels');
      mapEl.classList.toggle('hide-map-labels', !showLabels);
      if (btn) btn.setAttribute('aria-pressed', showLabels ? 'true' : 'false');
    }

    function createCustomerIcon(custName, category = 'today') {
      const pinCls = category === 'waiting' ? 'pin-waiting' : (category === 'upcoming' ? 'pin-upcoming' : 'pin-customer');
      const tagCls = category === 'waiting' ? 'tag-waiting' : (category === 'upcoming' ? 'tag-upcoming' : 'tag-cust');
      const icoCls = category === 'waiting' ? 'fa-hourglass-half' : (category === 'upcoming' ? 'fa-calendar-days' : 'fa-store');
      return L.divIcon({
        className: 'custom-icon',
        html: `<div class="marker-wrap">
                 <div class="marker-pin-circle ${pinCls}"><i class="fa-solid ${icoCls}"></i></div>
                 <span class="marker-name-tag ${tagCls}">${escHtml(shortLabel(custName, 16))}</span>
               </div>`,
        iconSize: [38, 38],
        iconAnchor: [19, 19],
        popupAnchor: [0, -20]
      });
    }

    function createTeknisiIcon(isOnline, tekName) {
      const firstWord = String(tekName || 'Teknisi').trim().split(/\s+/).slice(0, 2).join(' ');
      return L.divIcon({
        className: 'custom-icon',
        html: `<div class="marker-wrap">
                 ${isOnline ? '<span class="pulse-ring"></span>' : ''}
                 <div class="marker-pin-circle ${isOnline ? 'pin-online' : 'pin-offline'}">
                   <i class="fa-solid fa-motorcycle"></i>
                 </div>
                 <span class="marker-name-tag">${escHtml(shortLabel(firstWord, 15))}</span>
               </div>`,
        iconSize: [38, 38],
        iconAnchor: [19, 19],
        popupAnchor: [0, -20]
      });
    }

    function clearMapLayers() {
      routePolylines.forEach(p => map.removeLayer(p));
      routePolylines = [];
      Object.values(customerMarkers).forEach(m => map.removeLayer(m));
      Object.values(teknisiMarkers).forEach(m => map.removeLayer(m));
      customerMarkers = {};
      teknisiMarkers = {};
    }

    async function fetchTrackingData(isBackgroundPoll = false) {
      try {
        const dateVal = document.getElementById('trackingDate').value || '<?php echo $todayStr; ?>';
        const fetchOpts = isBackgroundPoll ? { priority: 'low' } : {};
        const response = await fetch(`../get_tracking_kegiatan.php?tanggal=${encodeURIComponent(dateVal)}`, fetchOpts);
        const result = await response.json();
        if (result.status && Array.isArray(result.data)) {
          kegiatanData = result.data;
          updateTopStats(result.summary);
          renderKegiatanSidebar();
          renderMapLayers();
          const now = new Date();
          const hh = String(now.getHours()).padStart(2, '0');
          const mm = String(now.getMinutes()).padStart(2, '0');
          const ss = String(now.getSeconds()).padStart(2, '0');
          document.getElementById('lastSyncText').textContent = `Update ${hh}:${mm}:${ss}`;
        }
      } catch (err) {
        console.error("Gagal memuat data tracking kegiatan:", err);
      }
    }

    function updateTopStats(summary) {
      if (!summary) return;
      document.getElementById('statKegiatan').textContent = `${summary.total_kegiatan} Hari Ini`;
      const statUpEl = document.getElementById('statUpcoming');
      if (statUpEl) statUpEl.textContent = `${summary.total_upcoming || 0} Akan Datang`;
      const statWaitEl = document.getElementById('statWaiting');
      if (statWaitEl) statWaitEl.textContent = `${summary.total_waiting || 0} Waiting`;
      document.getElementById('statTeknisi').textContent = `${summary.total_teknisi_terjadwal} Teknisi`;
      document.getElementById('statOnline').textContent = `${summary.teknisi_online} Online`;
    }

    function filterType(type, el) {
      activeTypeFilter = type;
      document.querySelectorAll('.type-pill').forEach(p => p.setAttribute('aria-pressed', 'false'));
      if (el) el.setAttribute('aria-pressed', 'true');
      renderKegiatanSidebar();
      renderMapLayers();
    }

    function quickToggleOnlineFilter() {
      const next = activeTypeFilter === 'online' ? 'all' : 'online';
      const btn = document.querySelector(`.type-pill[data-type="${next}"]`);
      filterType(next, btn);
    }

    function quickToggleCategoryFilter(cat) {
      const next = activeTypeFilter === cat ? 'all' : cat;
      const btn = document.querySelector(`.type-pill[data-type="${next}"]`);
      filterType(next, btn);
    }

    function matchesActiveCategory(k) {
      const kegLower = (k.kegiatan || '').toLowerCase();
      const cat = k.category || (k.is_waiting ? 'waiting' : (k.is_upcoming ? 'upcoming' : 'today'));
      if (activeTypeFilter === 'all') return true;
      if (activeTypeFilter === 'today' || activeTypeFilter === 'upcoming' || activeTypeFilter === 'waiting') {
        return cat === activeTypeFilter;
      }
      if (activeTypeFilter === 'online') {
        return (k.teknisi_list || []).some(t => t.is_online);
      }
      if (activeTypeFilter === 'gps_mati') {
        return (k.teknisi_list || []).some(t => t.gps_mati_jam_kerja);
      }
      if (activeTypeFilter === 'berjalan') {
        return (k.teknisi_list || []).some(t => {
          const s = (t.status_kerja || '').toLowerCase();
          return s.includes('berjalan') || s.includes('dikerjakan');
        });
      }
      return kegLower.includes(activeTypeFilter);
    }

    function getFilteredKegiatan() {
      const searchVal = document.getElementById('searchInput').value.toLowerCase().trim();
      return kegiatanData.filter(k => {
        if (!matchesActiveCategory(k)) return false;
        if (!searchVal) return true;
        return (k.nama_customer || '').toLowerCase().includes(searchVal) ||
          (k.kode || '').toLowerCase().includes(searchVal) ||
          (k.kegiatan || '').toLowerCase().includes(searchVal) ||
          (k.alamat || '').toLowerCase().includes(searchVal) ||
          (k.teknisi_list || []).some(t => (t.nama || '').toLowerCase().includes(searchVal));
      });
    }

    function renderKegiatanSidebar() {
      const listEl = document.getElementById('kegiatanList');
      const filtered = getFilteredKegiatan();

      document.getElementById('countAll').textContent = kegiatanData.length;
      const countTodayEl = document.getElementById('countToday');
      if (countTodayEl) countTodayEl.textContent = kegiatanData.filter(k => !k.is_waiting && !k.is_upcoming).length;
      const countUpEl = document.getElementById('countUpcoming');
      if (countUpEl) countUpEl.textContent = kegiatanData.filter(k => k.is_upcoming).length;
      const countWaitEl = document.getElementById('countWaiting');
      if (countWaitEl) countWaitEl.textContent = kegiatanData.filter(k => k.is_waiting).length;
      document.getElementById('countOnlineCard').textContent = kegiatanData.filter(k => (k.teknisi_list || []).some(t => t.is_online)).length;
      const gpsMatiCards = kegiatanData.filter(k => (k.teknisi_list || []).some(t => t.gps_mati_jam_kerja)).length;
      document.getElementById('countGpsMati').textContent = gpsMatiCards;
      const pillGpsMati = document.getElementById('pillGpsMati');
      if (pillGpsMati) {
        pillGpsMati.hidden = (gpsMatiCards === 0 && activeTypeFilter !== 'gps_mati');
      }
      document.getElementById('countBerjalan').textContent = kegiatanData.filter(k => (k.teknisi_list || []).some(t => /berjalan|dikerjakan/i.test(t.status_kerja || ''))).length;
      document.getElementById('countPasang').textContent = kegiatanData.filter(k => (k.kegiatan || '').toLowerCase().includes('pasang')).length;
      document.getElementById('countService').textContent = kegiatanData.filter(k => (k.kegiatan || '').toLowerCase().includes('service')).length;
      document.getElementById('countSurvey').textContent = kegiatanData.filter(k => (k.kegiatan || '').toLowerCase().includes('survey')).length;

      if (filtered.length === 0) {
        listEl.innerHTML = `
          <div style="text-align: center; color: #64748b; padding: 44px 20px; background:#ffffff; border-radius:14px; border:1px dashed #cbd5e1;">
            <i class="fa-solid fa-magnifying-glass-location" style="font-size: 28px; color:#94a3b8; margin-bottom: 10px; display:block;" aria-hidden="true"></i>
            <div style="font-size: 13px; font-weight:600; color:#334155;">Tidak ada jadwal yang cocok</div>
            <div style="font-size: 11.5px; margin-top:3px;">Coba pilih tanggal atau filter status lainnya</div>
          </div>`;
        return;
      }

      listEl.innerHTML = filtered.map(keg => {
        const kegLower = (keg.kegiatan || '').toLowerCase();
        let typeClass = 'type-default';
        if (kegLower.includes('pasang')) typeClass = 'type-pasang';
        else if (kegLower.includes('service')) typeClass = 'type-service';
        else if (kegLower.includes('survey')) typeClass = 'type-survey';

        const uKey = kegKey(keg);
        const isSelected = selectedKegiatanKode === uKey;
        const safeKeyJs = escHtml(uKey);

        const teknisiHtml = (keg.teknisi_list || []).length > 0
          ? keg.teknisi_list.map(t => {
              const sk = (t.status_kerja || 'Belum Mulai').toLowerCase();
              const statusClass = sk.includes('selesai') ? 'status-sls'
                : (sk.includes('menunggu') ? 'status-mlap'
                : (sk.includes('dikerjakan') || sk.includes('berjalan') ? 'status-dkj' : 'status-jdw'));

              const distText = t.jarak_ke_lokasi_km !== null && t.jarak_ke_lokasi_km !== undefined
                ? `${t.jarak_ke_lokasi_km} km`
                : '';

              const batNum = t.battery !== null && t.battery !== undefined && t.battery !== '' ? parseInt(t.battery, 10) : null;
              const hasBat = batNum !== null && !isNaN(batNum);
              const batIcon = !hasBat ? '' : (batNum >= 75 ? 'fa-battery-full' : (batNum >= 45 ? 'fa-battery-three-quarters' : (batNum >= 20 ? 'fa-battery-half' : 'fa-battery-quarter')));

              const metaChipsHtml = t.is_upcoming_only
                ? ''
                : (t.is_online
                  ? `<span class="gps-pill-online" title="GPS aktif real-time"><span class="dot-online" aria-hidden="true"></span>Online • ${Math.round(parseFloat(t.speed || 0))} km/j</span>`
                    + (distText ? `<span class="tek-meta-chip" title="Estimasi jarak ke customer"><i class="fa-solid fa-location-arrow" style="color:#ea580c; font-size:9px;" aria-hidden="true"></i>${escHtml(distText)}</span>` : '')
                    + (hasBat ? `<span class="tek-meta-chip ${batNum <= 20 ? 'bat-low' : ''}" title="Sisa baterai HP teknisi"><i class="fa-solid ${batIcon}" style="color:${batNum <= 20 ? '#e11d48' : '#16a34a'}; font-size:10px;" aria-hidden="true"></i>${batNum}%</span>` : '')
                  : (t.gps_mati_jam_kerja
                    ? `<span class="gps-pill-alert" title="GPS tidak aktif di jam kerja (06:00-20:00 WIB)"><span class="dot-alert" aria-hidden="true"></span>${t.gps_disabled ? 'GPS HP Dimatikan' : 'GPS Mati'} • ${escHtml(formatLastSeen(t.last_seen_seconds))}</span>`
                    : `<span class="gps-pill-offline" title="Waktu sejak sinyal GPS terakhir dikirim"><span class="dot-offline" aria-hidden="true"></span>Aktif ${escHtml(formatLastSeen(t.last_seen_seconds))}</span>`));

              const tekPhone = String(t.telp || '').replace(/[^0-9]/g, '').replace(/^0/, '');
              const waQuery = t.gps_mati_jam_kerja
                ? `?text=${encodeURIComponent('Halo ' + (t.nama || '') + ', mohon pastikan GPS & koneksi aplikasi Teknisi Loewix aktif ya untuk kelancaran sinkronisasi tugas.')}`
                : '';
              const hasLivePin = Boolean(t.live_latitude && t.live_longitude);

              return `
                <div class="tek-card ${t.gps_mati_jam_kerja ? 'tek-card-alert' : ''}">
                  <div class="tek-top-line">
                    <button type="button" class="btn-tek-jump" onclick="focusTeknisi(${ parseInt(t.teknisi_id, 10) }, '${safeKeyJs}')" title="${hasLivePin ? 'Klik untuk lihat posisi ' + escHtml(t.nama) + ' di peta' : 'Teknisi belum mengirim titik GPS'}">
                      <i class="fa-solid fa-motorcycle" style="color: ${t.is_online ? '#ff5b22' : (t.gps_mati_jam_kerja ? '#e11d48' : '#94a3b8')}; font-size: 12px; flex-shrink:0;" aria-hidden="true"></i>
                      <span class="tek-name">${escHtml(t.nama)}</span>
                      ${t.is_ketua ? '<span class="badge-ketua">Ketua</span>' : ''}
                    </button>
                    <div class="tek-right-actions">
                      <span class="badge-status-work ${statusClass}">${escHtml(t.status_kerja)}</span>
                      ${tekPhone ? `<a href="https://wa.me/62${tekPhone}${waQuery}" target="_blank" rel="noopener" class="btn-wa-mini" aria-label="WhatsApp ${escHtml(t.nama)}"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></a>` : ''}
                    </div>
                  </div>
                  ${metaChipsHtml ? `<div class="tek-meta-line">${metaChipsHtml}</div>` : ''}
                </div>`;
            }).join('')
          : `<div style="display:flex;align-items:center;justify-content:space-between;gap:8px;background:#f5f3ff;border:1px dashed #c4b5fd;border-radius:8px;padding:7px 10px;">
               <span style="font-size:11px;font-weight:700;color:#6d28d9;"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> Belum dapat teknisi</span>
               <a href="waiting-list.php" style="font-size:10.5px;font-weight:700;color:#fff;background:#7c3aed;padding:3px 9px;border-radius:6px;text-decoration:none;">Jadwalkan &rarr;</a>
             </div>`;

        const custPhone = String(keg.cust_nomor || '').replace(/[^0-9]/g, '').replace(/^0/, '');
        const isWaitingCard = Boolean(keg.is_waiting);
        const isUpcomingCard = Boolean(keg.is_upcoming);
        const cardCatCls = isWaitingCard ? 'card-waiting' : (isUpcomingCard ? 'card-upcoming' : '');
        const accentHex = isWaitingCard ? '#7c3aed' : (isUpcomingCard ? '#2563eb' : '');
        const zoneChipHtml = keg.zone_label
          ? `<span style="font-size:9.5px;font-weight:700;padding:2px 7px;border-radius:999px;background:${escHtml(keg.zone_bg || '#f1f5f9')};color:${escHtml(keg.zone_color || '#475569')};">📍 ${escHtml(keg.zone_label)}</span>`
          : '';

        return `
          <article class="kegiatan-card ${cardCatCls} ${isSelected ? 'selected' : ''}">
            <div class="keg-top">
              <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;">
                <span class="badge-type ${typeClass}">${escHtml(keg.kegiatan)}</span>
                ${isUpcomingCard ? '<span class="badge-type" style="background:#dbeafe;color:#1d4ed8;">📅 AKAN DATANG</span>' : ''}
                ${isWaitingCard ? '<span class="badge-type" style="background:#ede9fe;color:#6d28d9;">⏳ WAITING</span>' : ''}
                ${zoneChipHtml}
              </div>
              <div class="keg-meta-right">
                <span class="keg-time"><i class="fa-regular fa-clock" aria-hidden="true"></i> ${escHtml(keg.jadwal)}</span>
                <span class="keg-kode">#${escHtml(keg.kode)}</span>
              </div>
            </div>

            <h2 class="keg-customer">
              <button type="button" class="card-link" onclick="focusKegiatan('${safeKeyJs}')">
                ${escHtml(keg.nama_customer || 'Customer')}
              </button>
            </h2>

            ${keg.keterangan ? `<div class="keg-keterangan" style="${accentHex ? 'border-left-color:' + accentHex + ';' : ''}">"${escHtml(keg.keterangan)}"</div>` : ''}

            <div class="keg-alamat">
              <i class="fa-solid fa-location-dot" style="${accentHex ? 'color:' + accentHex + ';' : ''}" aria-hidden="true"></i>
              <div>${escHtml(keg.alamat || 'Alamat belum diisi')}${keg.coord_estimated ? ` <span style="font-size:9.5px;color:${accentHex || '#64748b'};font-weight:600;">(~Estimasi Wilayah)</span>` : ''}</div>
            </div>

            <div class="keg-teknisi-box">
              <div class="keg-teknisi-title">
                <span><i class="fa-solid fa-users-gear" aria-hidden="true"></i> ${isWaitingCard ? 'Status Penugasan' : `Tim Teknisi (${keg.teknisi_count})`}</span>
                ${(!isWaitingCard && !isUpcomingCard) ? '<span class="keg-teknisi-hint">Klik nama → fokus peta</span>' : ''}
              </div>
              ${teknisiHtml}
            </div>

            <footer class="keg-actions">
              <button type="button" class="btn-focus-map" onclick="focusKegiatan('${safeKeyJs}')">
                <i class="fa-solid ${isSelected ? 'fa-route' : 'fa-crosshairs'}" aria-hidden="true"></i>
                ${isSelected ? 'Sedang Dipantau di Peta' : 'Lihat Rute & Posisi'}
              </button>
              ${custPhone ? `
                <a href="https://wa.me/62${custPhone}" target="_blank" rel="noopener" class="btn-call-cust" aria-label="WhatsApp Customer ${escHtml(keg.nama_customer)}">
                  <i class="fa-brands fa-whatsapp" aria-hidden="true"></i> Customer
                </a>` : ''}
            </footer>
          </article>`;
      }).join('');
    }

    function updateFocusBanner(keg, extraEtaText = '') {
      const banner = document.getElementById('focusBanner');
      const txt = document.getElementById('focusBannerText');
      if (!keg) {
        banner.classList.remove('visible');
        return;
      }
      banner.classList.add('visible');
      txt.innerHTML = `<b>Fokus:</b> ${escHtml(keg.nama_customer)} <span style="color:#64748b;">(#${escHtml(keg.kode)})</span>${extraEtaText}`;
    }

    function renderMapLayers() {
      routePolylines.forEach(p => map.removeLayer(p));
      routePolylines = [];

      const activeCustKodes = new Set();
      const activeTekIds = new Set();
      const boundsList = [];

      const visibleKeys = new Set(getFilteredKegiatan().map(k => kegKey(k)));

      kegiatanData.forEach(keg => {
        const uKey = kegKey(keg);
        const isSelected = selectedKegiatanKode === uKey;
        const showCustPin = visibleKeys.has(uKey) || isSelected;
        const cLat = parseFloat(keg.latitude);
        const cLon = parseFloat(keg.longitude);
        const hasCustCoord = !isNaN(cLat) && !isNaN(cLon) && cLat !== 0 && cLon !== 0;

        if (hasCustCoord && showCustPin) {
          activeCustKodes.add(uKey);
          boundsList.push([cLat, cLon]);

          const isWaitingPin = Boolean(keg.is_waiting);
          const isUpcomingPin = Boolean(keg.is_upcoming);
          const pinCategory = isWaitingPin ? 'waiting' : (isUpcomingPin ? 'upcoming' : 'today');
          const gmapsUrl = `https://www.google.com/maps?q=${cLat},${cLon}`;
          const badgeStyle = isWaitingPin
            ? 'background:#ede9fe;color:#6d28d9;'
            : (isUpcomingPin ? 'background:#dbeafe;color:#1d4ed8;' : '');
          const badgePrefix = isWaitingPin ? '⏳ WAITING • ' : (isUpcomingPin ? '📅 AKAN DATANG • ' : '');
          const boxColor = isWaitingPin ? '#6d28d9' : (isUpcomingPin ? '#1e40af' : '#1e293b');
          const boxBg = isWaitingPin ? '#f5f3ff' : (isUpcomingPin ? '#eff6ff' : '#f8fafc');
          const boxBorder = isWaitingPin ? '#ddd6fe' : (isUpcomingPin ? '#bfdbfe' : '#e2e8f0');

          const custPopup = `
            <div style="min-width: 225px; font-size: 12px;">
              <div style="display:flex; justify-content:space-between; align-items:center; gap:6px; margin-bottom:4px; flex-wrap:wrap;">
                <span class="badge-type ${(!isWaitingPin && !isUpcomingPin) ? 'type-pasang' : ''}" style="font-size:9px; ${badgeStyle}">${badgePrefix}${escHtml(keg.kegiatan)}</span>
                <span style="font-size:11px; color:#64748b; font-weight:600;">${escHtml(keg.jadwal)}</span>
              </div>
              <div style="font-size: 14px; font-weight:700; color:#0f172a; margin-bottom:3px;">${escHtml(keg.nama_customer)}</div>
              ${keg.zone_label ? `<div style="margin-bottom:4px;"><span style="font-size:10px;font-weight:700;padding:1.5px 7px;border-radius:999px;background:${escHtml(keg.zone_bg || '#f1f5f9')};color:${escHtml(keg.zone_color || '#475569')};">📍 ${escHtml(keg.zone_label)}${keg.coord_estimated ? ' (~Estimasi)' : ''}</span></div>` : ''}
              <div style="color:#64748b; font-size:11px; line-height:1.4; margin-bottom:8px;">${escHtml(keg.alamat)}</div>
              <div style="font-size:11px; color:${boxColor}; background:${boxBg}; padding:6px 8px; border-radius:8px; border:1px solid ${boxBorder}; margin-bottom:8px; font-weight:${isWaitingPin ? '700' : '400'};">
                ${isWaitingPin ? '⏳ Belum dapat teknisi (Waiting List)' : `<b>Tim:</b> ${escHtml((keg.teknisi_list || []).map(t => t.nama).join(', ') || '-')}`}
              </div>
              <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                <a href="${gmapsUrl}" target="_blank" rel="noopener" style="display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:700; color:#2563eb; text-decoration:none;">
                  <i class="fa-solid fa-up-right-from-square"></i> Google Maps
                </a>
                ${isWaitingPin ? `<a href="waiting-list.php" style="display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; color:#fff; background:#7c3aed; padding:3px 9px; border-radius:6px; text-decoration:none;"><i class="fa-regular fa-calendar-check"></i> Jadwalkan</a>` : ''}
              </div>
            </div>`;

          if (customerMarkers[uKey]) {
            customerMarkers[uKey].setLatLng([cLat, cLon]);
            customerMarkers[uKey].setIcon(createCustomerIcon(keg.nama_customer, pinCategory));
            customerMarkers[uKey].getPopup().setContent(custPopup);
          } else {
            const marker = L.marker([cLat, cLon], { icon: createCustomerIcon(keg.nama_customer, pinCategory) })
              .addTo(map)
              .bindPopup(custPopup);
            marker.on('click', () => focusKegiatan(uKey));
            customerMarkers[uKey] = marker;
          }
        }

        (keg.teknisi_list || []).forEach(tek => {
          const tLat = parseFloat(tek.live_latitude);
          const tLon = parseFloat(tek.live_longitude);
          if (isNaN(tLat) || isNaN(tLon) || tLat === 0 || tLon === 0) return;

          activeTekIds.add(String(tek.teknisi_id));
          boundsList.push([tLat, tLon]);

          if (hasCustCoord) {
            const line = L.polyline([[tLat, tLon], [cLat, cLon]], {
              color: isSelected ? '#ff5b22' : '#64748b',
              weight: isSelected ? 4 : 1.8,
              dashArray: isSelected ? '6, 6' : '4, 8',
              opacity: isSelected ? 0.92 : 0.35
            }).addTo(map);
            routePolylines.push(line);

            if (isSelected) {
              fetch(`https://router.project-osrm.org/route/v1/driving/${tLon},${tLat};${cLon},${cLat}?overview=full&geometries=geojson&snapping=any`)
                .then(r => r.json())
                .then(res => {
                  if (res.code === 'Ok' && res.routes && res.routes.length > 0) {
                    const rt = res.routes[0];
                    const roadCoords = rt.geometry.coordinates.map(c => [c[1], c[0]]);
                    line.setLatLngs([[tLat, tLon], ...roadCoords, [cLat, cLon]]);
                    line.setStyle({ dashArray: null, weight: 4.5, opacity: 0.95 });
                    const km = (rt.distance / 1000).toFixed(1);
                    const etaMin = Math.max(1, Math.round(rt.duration / 60));
                    if (selectedKegiatanKode === uKey) {
                      updateFocusBanner(keg, `<span class="focus-eta-pill"><i class="fa-solid fa-route"></i> ${km} km (~${etaMin} mnt)</span>`);
                    }
                  }
                })
                .catch(() => {});
            }
          }

          const tekPhone = String(tek.telp || '').replace(/[^0-9]/g, '').replace(/^0/, '');
          const tekPopup = `
            <div style="min-width: 205px; font-size: 12px;">
              <div style="font-size: 13.5px; font-weight:700; color:#0f172a; margin-bottom:2px;">
                ${escHtml(tek.nama)} ${tek.is_ketua ? '<span class="badge-ketua">Ketua</span>' : ''}
              </div>
              <div style="margin-bottom:6px; display:flex; flex-wrap:wrap; gap:4px; align-items:center;">
                ${tek.is_online
                  ? `<span class="gps-pill-online"><span class="dot-online" aria-hidden="true"></span>Online • ${Math.round(parseFloat(tek.speed || 0))} km/jam</span>`
                    + (tek.battery ? `<span class="tek-meta-chip"><i class="fa-solid fa-battery-three-quarters" style="color:#16a34a;" aria-hidden="true"></i>${escHtml(String(tek.battery))}%</span>` : '')
                  : (tek.gps_mati_jam_kerja
                    ? `<span class="gps-pill-alert"><span class="dot-alert" aria-hidden="true"></span>${tek.gps_disabled ? 'GPS HP Dimatikan' : 'GPS Mati'} • ${escHtml(formatLastSeen(tek.last_seen_seconds))}</span>`
                    : `<span class="gps-pill-offline"><span class="dot-offline" aria-hidden="true"></span>Aktif ${escHtml(formatLastSeen(tek.last_seen_seconds))}</span>`)
                }
              </div>
              <div style="font-size:11.5px; color:#475569; line-height:1.45;">
                <div><b>Tujuan:</b> ${escHtml(keg.nama_customer)}</div>
                <div><b>Status Kerja:</b> ${escHtml(tek.status_kerja)}</div>
                ${tek.jarak_ke_lokasi_km !== null ? `<div style="color:#ea580c; font-weight:700; margin-top:2px;">Jarak lurus: ~${tek.jarak_ke_lokasi_km} km</div>` : ''}
              </div>
              ${tekPhone ? `<a href="https://wa.me/62${tekPhone}" target="_blank" rel="noopener" style="display:inline-flex; align-items:center; gap:5px; margin-top:8px; color:#15803d; font-weight:700; font-size:11.5px; text-decoration:none;"><i class="fa-brands fa-whatsapp"></i> Chat WhatsApp</a>` : ''}
            </div>`;

          if (teknisiMarkers[tek.teknisi_id]) {
            teknisiMarkers[tek.teknisi_id].setLatLng([tLat, tLon]);
            teknisiMarkers[tek.teknisi_id].setIcon(createTeknisiIcon(tek.is_online, tek.nama));
            teknisiMarkers[tek.teknisi_id].getPopup().setContent(tekPopup);
          } else {
            const marker = L.marker([tLat, tLon], { icon: createTeknisiIcon(tek.is_online, tek.nama) })
              .addTo(map)
              .bindPopup(tekPopup);
            teknisiMarkers[tek.teknisi_id] = marker;
          }
        });
      });

      // Remove stale markers when switching dates or completed items
      Object.keys(customerMarkers).forEach(k => {
        if (!activeCustKodes.has(k)) {
          map.removeLayer(customerMarkers[k]);
          delete customerMarkers[k];
        }
      });
      Object.keys(teknisiMarkers).forEach(id => {
        if (!activeTekIds.has(String(id))) {
          map.removeLayer(teknisiMarkers[id]);
          delete teknisiMarkers[id];
        }
      });

      if (!hasInitialFit && !selectedKegiatanKode && boundsList.length > 0) {
        map.fitBounds(boundsList, { padding: [55, 55], maxZoom: 14 });
        hasInitialFit = true;
      }
    }

    function focusKegiatan(trackKey) {
      selectedKegiatanKode = trackKey;
      const keg = kegiatanData.find(k => kegKey(k) === trackKey);
      updateFocusBanner(keg || null);
      renderKegiatanSidebar();
      renderMapLayers();

      if (window.innerWidth < 992) {
        const drawer = document.getElementById('trackingDrawer');
        if (drawer) drawer.classList.add('mobile-hidden');
        const btnText = document.getElementById('btnToggleDrawerText');
        if (btnText) btnText.textContent = 'Daftar';
      }

      if (!keg) return;

      const targets = [];
      if (keg.latitude && keg.longitude && !isNaN(parseFloat(keg.latitude))) {
        const wLat = parseFloat(keg.latitude);
        const wLon = parseFloat(keg.longitude);
        targets.push([wLat, wLon]);
        if (customerMarkers[trackKey]) customerMarkers[trackKey].openPopup();

        // Jika kartu Waiting List diklik, tampilkan teknisi terjadwal terdekat di banner atas sebagai referensi arah
        if (keg.is_waiting) {
          let bestTekName = null;
          let bestKm = Infinity;
          kegiatanData.forEach(other => {
            if (other.is_waiting) return;
            const oLat = parseFloat(other.latitude);
            const oLon = parseFloat(other.longitude);
            (other.teknisi_list || []).forEach(tk => {
              const refLat = (tk.live_latitude && !isNaN(parseFloat(tk.live_latitude))) ? parseFloat(tk.live_latitude) : oLat;
              const refLon = (tk.live_longitude && !isNaN(parseFloat(tk.live_longitude))) ? parseFloat(tk.live_longitude) : oLon;
              if (!isNaN(refLat) && !isNaN(refLon)) {
                const d = map.distance([wLat, wLon], [refLat, refLon]) / 1000;
                if (d < bestKm) {
                  bestKm = d;
                  bestTekName = tk.nama;
                }
              }
            });
          });
          if (bestTekName && isFinite(bestKm)) {
            updateFocusBanner(keg, `<span class="focus-eta-pill" style="background:#f5f3ff;color:#6d28d9;border-color:#ddd6fe;">🟣 Teknisi Terdekat: ${escHtml(bestTekName)} (~${(bestKm * 1.35).toFixed(1)} km)</span>`);
          }
        }
      }
      (keg.teknisi_list || []).forEach(t => {
        if (t.live_latitude && t.live_longitude && !isNaN(parseFloat(t.live_latitude))) {
          targets.push([parseFloat(t.live_latitude), parseFloat(t.live_longitude)]);
        }
      });

      if (targets.length === 1) {
        map.flyTo(targets[0], 16, { duration: 1.0 });
      } else if (targets.length > 1) {
        map.fitBounds(targets, { padding: [85, 85], maxZoom: 15 });
      }
    }

    function focusTeknisi(tekId, trackKey) {
      selectedKegiatanKode = trackKey;
      const keg = kegiatanData.find(k => kegKey(k) === trackKey);
      updateFocusBanner(keg || null);
      renderKegiatanSidebar();
      renderMapLayers();

      const marker = teknisiMarkers[tekId];
      if (marker) {
        if (window.innerWidth < 992) {
          document.getElementById('trackingDrawer')?.classList.add('mobile-hidden');
        }
        map.flyTo(marker.getLatLng(), 16, { duration: 0.9 });
        setTimeout(() => marker.openPopup(), 350);
      } else {
        focusKegiatan(trackKey);
      }
    }

    function fitAllMapBounds() {
      selectedKegiatanKode = null;
      updateFocusBanner(null);
      renderKegiatanSidebar();
      renderMapLayers();

      const allBounds = [];
      Object.values(customerMarkers).forEach(m => allBounds.push(m.getLatLng()));
      Object.values(teknisiMarkers).forEach(m => allBounds.push(m.getLatLng()));

      if (allBounds.length > 0) {
        map.fitBounds(allBounds, { padding: [65, 65], maxZoom: 14 });
      } else {
        map.setView([-6.2088, 106.8456], 11);
      }
    }

    async function manualRefresh() {
      const icon = document.getElementById('refreshIcon');
      if (icon) icon.classList.add('fa-spin');
      await fetchTrackingData(false);
      setTimeout(() => {
        if (icon) icon.classList.remove('fa-spin');
      }, 500);
    }

    function toggleDrawerMobile() {
      const drawer = document.getElementById('trackingDrawer');
      const btnText = document.getElementById('btnToggleDrawerText');
      if (!drawer) return;
      const isHidden = drawer.classList.contains('mobile-hidden');
      drawer.classList.toggle('mobile-hidden', !isHidden);
      if (btnText) btnText.textContent = isHidden ? 'Tutup' : 'Daftar';
      setTimeout(() => map.invalidateSize(), 310);
    }

    if (window.innerWidth < 768) {
      document.getElementById('trackingDrawer')?.classList.add('mobile-hidden');
      const bt = document.getElementById('btnToggleDrawerText');
      if (bt) bt.textContent = 'Daftar';
    }

    function clearSearchInput() {
      const inp = document.getElementById('searchInput');
      inp.value = '';
      document.getElementById('btnClearSearch').classList.remove('visible');
      renderKegiatanSidebar();
      renderMapLayers();
      inp.focus();
    }

    document.getElementById('searchInput').addEventListener('input', (e) => {
      document.getElementById('btnClearSearch').classList.toggle('visible', e.target.value.length > 0);
      renderKegiatanSidebar();
      renderMapLayers();
    });

    document.getElementById('trackingDate').addEventListener('change', () => {
      selectedKegiatanKode = null;
      hasInitialFit = false;
      updateFocusBanner(null);
      clearMapLayers();
      fetchTrackingData(false);
    });

    fetchTrackingData(false);
    setInterval(() => fetchTrackingData(true), 5000);
  </script>
  <?php include "js-include.php"; ?>
</body>
</html>
