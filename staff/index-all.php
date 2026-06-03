<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Dashboard";
$currentPage = "Clear";
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <?php include "head.php"; ?>
  <style>
    <?php include "css/floating-menu2.css"; ?>
    .section-header { display:flex;align-items:center;justify-content:space-between;padding:14px 20px;background:#1e293b;border-radius:10px 10px 0 0;cursor:pointer;transition:background 0.2s; }
    .section-header:hover { background:#334155; }
    .section-header h6 { margin:0;font-size:13px;font-weight:700;color:#fff;letter-spacing:0.04em;text-transform:uppercase; }
    .section-header .material-icons { font-size:18px;color:#94a3b8; }
    .section-card { border:1px solid #e2e8f0;border-radius:0 0 10px 10px;border-top:none;box-shadow:0 1px 4px rgba(0,0,0,0.05),0 4px 16px rgba(0,0,0,0.02);background:#fff; }
    .tbl-header { background:#f8fafc !important;border:none !important;border-bottom:2px solid #e2e8f0 !important;border-radius:0 !important;padding:12px 16px !important; }
    .tbl-th { font-size:10.5px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.06em; }
    .tbl-row { border:none !important;border-bottom:1px solid #f1f5f9 !important;border-radius:0 !important;padding:16px !important;transition:background 0.15s; }
    .tbl-row:hover { background:#f0f4f8 !important; }
    .tbl-row:last-child { border-bottom:none !important; }
    .badge-type { font-size:9px;font-weight:700;padding:3px 10px;border-radius:20px;letter-spacing:0.04em;text-transform:uppercase;display:inline-block; }
    .badge-survey { background:#fef3c7;color:#92400e; } .badge-service { background:#e0e7ff;color:#3730a3; }
    .badge-pasang { background:#dcfce7;color:#166534; } .badge-default { background:#f1f5f9;color:#475569; }
    .badge-status { font-size:10px;font-weight:600;padding:4px 10px;border-radius:20px;letter-spacing:0.02em;display:inline-block; }
    .badge-selesai { background:#dcfce7;color:#166534; }
    .btn-act { width:30px;height:30px;padding:0;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;border:none;transition:all 0.15s;cursor:pointer;text-decoration:none; }
    .btn-act-view { background:#eff6ff;color:#3b82f6; } .btn-act-view:hover { background:#3b82f6;color:#fff; }
    .btn-act-edit { background:#fffbeb;color:#d97706; } .btn-act-edit:hover { background:#d97706;color:#fff; }
    .btn-act-delete { background:#fef2f2;color:#dc2626; } .btn-act-delete:hover { background:#dc2626;color:#fff; }
    .text-name { font-size:13px;font-weight:700;color:#1e293b;text-decoration:none;margin:0 0 2px; }
    .text-name:hover { color:#3b82f6; }
    .text-phone { font-size:11px;color:#3b82f6;text-decoration:none; } .text-phone:hover { text-decoration:underline; }
    .text-note { font-size:10.5px;color:#94a3b8;margin:3px 0 0;font-style:italic; }
    .text-time { font-size:13px;font-weight:600;color:#1e293b;margin:5px 0 2px; }
    .text-code { font-size:10px;color:#94a3b8;display:block; }
  </style>
</head>

<body class="g-sidenav-show bg-gray-200">
  <?php include "cek-menu.php"; ?>

  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg" style="display:flex;flex-direction:column;overflow:hidden;">
    <?php
    include "nav-top.php";
    $daftar_bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $todayDate = date('d') . ' ' . $daftar_bulan[(int)date('m')] . ' ' . date('Y');
    ?>

    <!-- Fixed Section: Stat Cards -->
    <div style="flex-shrink:0;padding:0 24px;">
      <?php include 'top-point.php'; ?>
    </div>

    <!-- Scrollable Section -->
    <div style="flex:1;overflow-y:auto;padding:0 24px 24px;">
      <div class="row mb-4">
        <?php include "kegiatan-db-all.php"; ?>
      </div>
      <?php include "footer.php"; ?>
    </div>

<!-- Edit Invoice Modal -->
<div class="modal fade" id="editInvoiceModal" tabindex="-1" aria-labelledby="editInvoiceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editInvoiceModalLabel">Edit Invoice & Garansi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="proses_update_invoice.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="kode_transaksi" id="modal_kode_transaksi">
          <div class="mb-3">
            <label for="modal_invoice" class="form-label">Kode Invoice</label>
            <input type="text" class="form-control border p-2" id="modal_invoice" name="invoice" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Ada Garansi?</label>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="garansi" id="garansi_ya" value="Ya">
              <label class="form-check-label" for="garansi_ya">Ya</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="garansi" id="garansi_tidak" value="Tidak" checked>
              <label class="form-check-label" for="garansi_tidak">Tidak</label>
            </div>
          </div>
          <div class="mb-3">
            <label for="modal_keterangan_garansi" class="form-label">Keterangan Garansi</label>
            <textarea class="form-control border p-2" id="modal_keterangan_garansi" name="keterangan_garansi" rows="3" placeholder="Jelaskan cakupan garansi jika ada..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

  </main>
  <?php
  include "js-include.php";
  ?>
  <script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      var options = {
        damping: '0.5'
      }
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }
  </script>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  
  <script>
    
    $(".view-btn").click(function() {
        var kegiatanId = $(this).data("id");
        window.location.href = "view-kegiatan.php?kode_transaksi=" + kodeTransaksi;
    });
    $(".edit-btn").click(function() {
        var kodeTransaksi = $(this).data("id");
        window.location.href = "edit_kegiatan.php?kode_transaksi=" + kodeTransaksi;
    });
</script>

  <script>
    $(".jadwalkan-btn").click(function() {
      var kegiatanId = $(this).data("id");
      $("#jadwalkanForm")[0].reset();
      $("#jadwalkanForm").attr("data-id", kegiatanId);
      $("#jadwalkanModal").modal("show");

      var tglRequest = $(this).data("tgl-request");
      var tanggalInput = document.getElementById("tanggal");
      var jamInput = document.getElementById("jam");

      if (tglRequest) {
        var tglWaktu = tglRequest.split(" ");
        if (tglWaktu.length === 2) {
          var tanggal = tglWaktu[0];
          var waktu = tglWaktu[1];
          tanggalInput.value = tanggal;
          jamInput.value = waktu;

          handleDateChange();
        }
      }
    });


    $("#submitJadwalkan").click(function() {
      var kegiatanId = $("#jadwalkanForm").data("id");
      var tanggal = $("#tanggal").val();
      var jam = $("#jam").val();
      var selectedTechnicians = $(".teknisi-checkbox:checked").map(function() {
        return this.value;
      }).get();

      $.ajax({
        url: "proses_jadwalkan.php",
        type: "POST",
        data: {
          kegiatanId: kegiatanId,
          teknisi: selectedTechnicians,
          tanggal: tanggal,
          jam: jam
        },
        success: function(response) {
          if (response === "success") {
            $("#jadwalkanModal").modal("hide");
            alert("Berhasil");
            window.location.reload();
          } else {
            alert("Gagal menjadwalkan kegiatan.");
          }
        },
        error: function() {
          alert("Terjadi kesalahan saat menghubungi server.");
        }
      });
    });


    $(".hapus-btn").click(function() {
      var kegiatanId = $(this).data("id");
      var nama = $(this).data("nama");
      var kode = $(this).data("kode");
      if (confirm("Apakah Anda yakin ingin menghapus kegiatan ini?")) {
        $.ajax({
          url: "proses_hapus_kegiatan.php",
          type: "POST",
          data: {
            kegiatanId: kegiatanId,
            nama: nama,
            kode: kode
          },
          success: function(response) {
            if (response === "success") {
              alert("Kegiatan berhasil dihapus.");
              window.location.reload();
            } else {
              alert("Gagal menghapus kegiatan.");
            }
          },
          error: function() {
            alert("Terjadi kesalahan saat menghubungi server.");
          }
        });
      }
    });


$(".delete-btn").click(function() {
  var nama = $(this).data("nama");
  var kode = $(this).data("kode");
  if (confirm("Apakah Anda yakin ingin menghapus kegiatan ini?")) {
    $.ajax({
      url: "delete-kegiatan.php",
      type: "POST",
      data: {
        nama: nama,
        kode: kode
      },
      success: function(response) {
        if (response === "success") {
          alert("Kegiatan berhasil dihapus.");
          window.location.reload();
        } else {
          alert("Gagal menghapus kegiatan.");
        }
      },
      error: function() {
        alert("Terjadi kesalahan saat menghubungi server.");
      }
    });
  }
});
  </script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editModal = document.getElementById('editInvoiceModal');
    
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            // Tombol yang memicu modal
            const button = event.relatedTarget;

            // Ekstrak informasi dari atribut data-* pada tombol
            const kode = button.getAttribute('data-kode');
            const invoice = button.getAttribute('data-invoice');
            const garansi = button.getAttribute('data-garansi');
            const keterangan = button.getAttribute('data-keterangan-garansi');

            // Dapatkan elemen form di dalam modal
            const modalKodeInput = editModal.querySelector('#modal_kode_transaksi');
            const modalInvoiceInput = editModal.querySelector('#modal_invoice');
            const modalKeteranganInput = editModal.querySelector('#modal_keterangan_garansi');
            const radioYa = editModal.querySelector('#garansi_ya');
            const radioTidak = editModal.querySelector('#garansi_tidak');

            // Isi form modal dengan data yang didapat
            modalKodeInput.value = kode;
            modalInvoiceInput.value = invoice;
            modalKeteranganInput.value = keterangan;

            // Logika untuk memilih radio button yang benar
            // Jika kolom 'garansi' di database punya isi (bukan kosong atau NULL), maka pilih "Ya"
            if (garansi && garansi.trim().toLowerCase() !== 'no' && garansi.trim() !== '') {
                radioYa.checked = true;
            } else {
                radioTidak.checked = true;
            }
        });
    }
});
</script>

</body>

</html>