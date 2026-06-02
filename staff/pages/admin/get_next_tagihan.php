<?php
include "../../conn.php";

// Ambil offset dari request POST
$offset = isset($_POST['offset']) ? $_POST['offset'] : 0;

// Query untuk mendapatkan 3 tagihan berikutnya dengan offset yang ditentukan
$queryNextTagihan = "SELECT * FROM tagihan LIMIT 3 OFFSET $offset";
$resultNextTagihan = mysqli_query($conn, $queryNextTagihan);

// Cek apakah ada tagihan berikutnya
if (mysqli_num_rows($resultNextTagihan) > 0) {
  while ($rowNextTagihan = mysqli_fetch_assoc($resultNextTagihan)) {
    $idTagihan = $rowNextTagihan['id_tagihan'];
    $namaTagihan = $rowNextTagihan['nama_tagihan'];
    $jumlahTagihan = $rowNextTagihan['jumlah'];

    // Mengubah nilai ke format nominal rupiah
    $jumlahTagihanRupiah = "Rp " . number_format($jumlahTagihan, 0, ',', '.') . ",00";

    $jenisTagihan = $rowNextTagihan['jenis'];
    $statusTagihan = $rowNextTagihan['status'];
    $statusClass = ($statusTagihan == "Y") ? "aktif" : "tidak";
    $statusTagihanSekarang = ($statusTagihan == "Y") ? "On" : "Off";
?>
    <li class="list-group-item border-0 d-flex justify-content-between ps-0 mb-2 border-radius-lg">
      <div class="d-flex flex-column col-6">
        <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $namaTagihan; ?></h6>
        <span class="text-xs"><?php echo $jenisTagihan; ?></span>
      </div>

      <div class="d-flex align-items-center text-sm col-3">
        <?php echo $jumlahTagihanRupiah; ?>
      </div>

      <div class="d-flex align-items-center text-sm col-3">
        <button class="btn btn-link text-dark text-sm mb-0 px-0 ms-4 toggle-status" data-id="<?php echo $idTagihan; ?>" data-status="<?php echo $statusTagihan; ?>">
          <span class="status-indicator <?php echo $statusClass; ?>"><i class="material-icons text-lg position-relative me-1">circle</i> <?php echo $statusTagihanSekarang; ?></span>
        </button>
      </div>
    </li>
<?php
  }
} else {
  // Jika tidak ada tagihan berikutnya, kirim pesan atau kode HTML yang sesuai
  echo "<p>Tidak ada tagihan berikutnya.</p>";
}

// Tutup koneksi database
mysqli_close($conn);
?>