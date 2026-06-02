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
    .nav-laporan { display: flex; width: 100%; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    .nav-laporan .nav-tab-item {
        flex: 1; text-align: center; padding: 14px 8px;
        font-size: 12px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase;
        text-decoration: none; color: rgba(255,255,255,0.7);
        background: linear-gradient(135deg, #f59e0b, #d97706);
        border: none;
        transition: all 0.25s ease;
    }
    .nav-laporan .nav-tab-item:hover { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #fff; }
    .nav-laporan .nav-tab-item.active-tab { 
        background: linear-gradient(135deg, #fbbf24, #f59e0b); 
        color: #1e293b; font-weight: 800;
        box-shadow: inset 0 -3px 0 rgba(0,0,0,0.15);
    }
    .nav-laporan .nav-tab-item + .nav-tab-item { border-left: 1px solid rgba(255,255,255,0.2); }
    .nav-laporan .nav-tab-print {
        width: 48px; text-align: center; padding: 14px 0;
        background: linear-gradient(135deg, #d97706, #b45309); color: rgba(255,255,255,0.7); text-decoration: none;
        transition: all 0.25s ease; border-left: 1px solid rgba(255,255,255,0.2);
    }
    .nav-laporan .nav-tab-print:hover { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; }
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
