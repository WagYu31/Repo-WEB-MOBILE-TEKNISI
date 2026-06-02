<?php
/**
 * Tab Navigasi Laporan - Komponen Reusable
 * Design: Clean, Professional, ISO-compliant
 * - Minimal colors, high contrast
 * - Active state with bottom border indicator
 * - WCAG 2.1 AA compliant contrast ratios
 */

$tabs = [
    ['url' => 'lap-kegiatan.php', 'label' => 'Belum Input Invoice', 'icon' => 'edit_note'],
    ['url' => 'lap-kegiatan-selesai.php', 'label' => 'Selesai', 'icon' => 'check_circle'],
    ['url' => 'lap-noinv.php', 'label' => 'No Invoice', 'icon' => 'receipt_long'],
    ['url' => 'lap-loss.php', 'label' => 'Tidak Selesai', 'icon' => 'cancel'],
];

$currentFile = basename($_SERVER['PHP_SELF']);
?>
<style>
    .nav-laporan-wrap {
        background: #ffffff;
        border-radius: 10px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: stretch;
        overflow: hidden;
    }
    .nav-laporan-wrap .nav-tab {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 14px 10px;
        text-decoration: none;
        font-size: 11.5px;
        font-weight: 600;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        color: #64748b;
        background: transparent;
        border: none;
        border-bottom: 2px solid transparent;
        transition: all 0.2s ease;
        position: relative;
    }
    .nav-laporan-wrap .nav-tab .tab-icon {
        font-size: 16px;
        line-height: 1;
    }
    .nav-laporan-wrap .nav-tab:hover {
        color: #1e293b;
        background: #f8fafc;
    }
    .nav-laporan-wrap .nav-tab.active-tab {
        color: #0f172a;
        font-weight: 700;
        border-bottom-color: #1e293b;
        background: #f8fafc;
    }
    .nav-laporan-wrap .nav-tab + .nav-tab {
        border-left: 1px solid #f1f5f9;
    }
    .nav-laporan-wrap .nav-tab-print {
        width: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        color: #94a3b8;
        background: transparent;
        border-left: 1px solid #f1f5f9;
        transition: all 0.2s ease;
    }
    .nav-laporan-wrap .nav-tab-print:hover {
        color: #1e293b;
        background: #f8fafc;
    }
    @media (max-width: 768px) {
        .nav-laporan-wrap .nav-tab {
            padding: 12px 6px;
            font-size: 10px;
            flex-direction: column;
            gap: 4px;
        }
        .nav-laporan-wrap .nav-tab .tab-icon { font-size: 18px; }
    }
</style>
<div class="row">
    <div class="col-12">
        <div class="nav-laporan-wrap">
            <?php foreach ($tabs as $tab) : ?>
                <a href="<?= $tab['url'] ?>" class="nav-tab <?= ($currentFile === $tab['url']) ? 'active-tab' : '' ?>">
                    <i class="material-icons tab-icon"><?= $tab['icon'] ?></i>
                    <span><?= $tab['label'] ?></span>
                </a>
            <?php endforeach; ?>
            <a href="laporan-bulanan.php" class="nav-tab-print" target="_blank" title="Print Laporan Bulanan">
                <i class="material-icons" style="font-size:18px;">print</i>
            </a>
        </div>
    </div>
</div>
