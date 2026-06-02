<?php
/**
 * Tab Navigasi Laporan - Komponen Reusable
 * 
 * Semua tab berwarna sama (slate dark).
 * Tab aktif ditandai dengan warna yang lebih terang.
 * 
 * Usage: $activePage = 'lap-kegiatan.php'; include 'nav-laporan.php';
 */

$tabs = [
    ['url' => 'lap-kegiatan.php', 'label' => 'Belum Input Invoice'],
    ['url' => 'lap-kegiatan-selesai.php', 'label' => 'Selesai'],
    ['url' => 'lap-noinv.php', 'label' => 'No Invoice'],
    ['url' => 'lap-loss.php', 'label' => 'Tidak Selesai'],
];

$currentFile = basename($_SERVER['PHP_SELF']);
?>
<style>
    .nav-laporan { display: flex; width: 100%; border-radius: 8px; overflow: hidden; }
    .nav-laporan .nav-tab-item {
        flex: 1; text-align: center; padding: 12px 8px;
        font-size: 12px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase;
        text-decoration: none; color: #94a3b8;
        background: #1e293b; border: none;
        transition: all 0.2s ease;
    }
    .nav-laporan .nav-tab-item:hover { background: #334155; color: #e2e8f0; }
    .nav-laporan .nav-tab-item.active-tab { background: #475569; color: #ffffff; }
    .nav-laporan .nav-tab-item + .nav-tab-item { border-left: 1px solid #334155; }
    .nav-laporan .nav-tab-print {
        width: 48px; text-align: center; padding: 12px 0;
        background: #1e293b; color: #94a3b8; text-decoration: none;
        transition: all 0.2s ease; border-left: 1px solid #334155;
    }
    .nav-laporan .nav-tab-print:hover { background: #334155; color: #e2e8f0; }
</style>
<div class="row">
    <div class="col-12">
        <div class="nav-laporan">
            <?php foreach ($tabs as $tab) : ?>
                <a href="<?= $tab['url'] ?>" class="nav-tab-item <?= ($currentFile === $tab['url']) ? 'active-tab' : '' ?>">
                    <?= $tab['label'] ?>
                </a>
            <?php endforeach; ?>
            <a href="laporan-bulanan.php" class="nav-tab-print" target="_blank" title="Print Laporan">
                <i class="material-icons" style="font-size:16px;vertical-align:middle;">print</i>
            </a>
        </div>
    </div>
</div>
