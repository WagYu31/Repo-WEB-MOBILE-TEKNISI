<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Dashboard";
$currentPage = "Today";
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <?php
  include "head.php";
  ?>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <style>
    ul#data-tek li:nth-child(odd) {
      background-color: white;
    }

    ul#data-tek li:nth-child(even) {
      background-color: #efefef;
      border-radius: 0;
    }

    #toggleLoadMore,
    #toggleLoadMore1,
    #toggleLoadMore2 {
      border-bottom-left-radius: 0;
      border-bottom-right-radius: 0;
    }

    input[type="checkbox"] {
      -webkit-appearance: checkbox;
      -moz-appearance: checkbox;
      appearance: checkbox;
    }
        <?php include "css/floating-menu2.css";?>
  </style>
</head>

<body class="g-sidenav-show  bg-gray-200">
  <?php
  include "cek-menu.php";
  ?>

  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">

    <?php
    include "nav-top.php";
    setlocale(LC_TIME, 'id_ID');
    $todayDate = strftime('%d %B %Y');
    ?>

      <div class="container-fluid py-4">
        <?php include 'top-point.php'; ?>
        <div class="row mb-4 mt-4">
          <?php
          include "kegiatan-db2.php";
        //   include "waiting-list-db.php";
          ?>
        </div>
        <?php
        include "floating-menu.php";
        include "footer.php";
        ?>
      </div>
    <?php
    // }
    ?>

<div class="modal fade" id="jadwalkanModal" tabindex="-1" role="dialog" aria-labelledby="jadwalkanModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="jadwalkanModalLabel">Jadwalkan Kegiatan</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="jadwalkanForm">
                    <div class="form-group">
                        <label for="tanggal">Tanggal:</label>
                        <input type="date" class="form-control px-2 mt-n1" style="border:1px solid #ced4da;" id="tanggal" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group mt-2">
                        <label for="jam">Jam:</label>
                        <input type="time" class="form-control px-2 mt-n1" style="border:1px solid #ced4da;" id="jam" name="jam" required>
                    </div>
                    <div class="form-group mt-2">
                        <label for="nama_teknisi">Pilih Teknisi (yang tersedia)</label>
                        <div id="technician-list-container">
                            <?php
                            $sql_teknisi = "SELECT id, nama FROM teknisi WHERE deleted_at IS NULL ORDER BY nama ASC";
                            $result_teknisi = mysqli_query($conn, $sql_teknisi);
                            if ($result_teknisi && mysqli_num_rows($result_teknisi) > 0) {
                                while ($row = mysqli_fetch_assoc($result_teknisi)) {
                                    $id_teknisi = $row['id'];
                                    $nama_teknisi = htmlspecialchars($row['nama']);
                                    
                                    // [MODIFIKASI] Struktur HTML untuk setiap teknisi
                                    echo "<div class='form-check mt-2'>";
                                    echo "  <input class='form-check-input teknisi-checkbox' type='checkbox' name='teknisi[]' value='$id_teknisi' id='teknisi$id_teknisi'>";
                                    echo "  <label class='form-check-label' for='teknisi$id_teknisi'>$nama_teknisi</label>";
                                    // Placeholder untuk jadwal yang akan diisi oleh JavaScript
                                    echo "  <div class='text-muted text-xs ms-4' id='jadwal-teknisi-$id_teknisi'></div>";
                                    echo "</div>";
                                }
                            } else {
                                echo "Tidak ada teknisi tersedia.";
                            }
                            ?>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="submitJadwalkan">Jadwalkan</button>
            </div>
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
    $(document).ready(function() {
      $("#toggleLoadMore").click(function() {
        $("#loadMoreX").toggle();
        $(this).toggleClass("rounded");
      });
    });
    $(document).ready(function() {
      $("#toggleLoadMore1").click(function() {
        $("#loadMoreX1").toggle();
        $(this).toggleClass("rounded");
      });
    });
    $(document).ready(function() {
      $("#toggleLoadMore2").click(function() {
        $("#loadMoreX2").toggle();
        $(this).toggleClass("rounded");
      });
    });
  </script>

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
    $(document).ready(function() {
      $(".jadwalkan-btn").click(function() {

        var kegiatanId = $(this).data("id");
        var tglRequest = $(this).data("tgl-request");

        $("#jadwalkanForm")[0].reset();
        $("#jadwalkanForm").attr("data-id", kegiatanId);

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

        $("#jadwalkanModal").modal("show");
      });

      $("#submitJadwalkan").click(function() {
        var kegiatanId = $("#jadwalkanForm").data("id");
        var tanggal = $("#tanggal").val();
        var jam = $("#jam").val();
        var selectedTechnicians = $(".teknisi-checkbox:checked").map(function() {
          return this.value;
        }).get();

        if (!tanggal || !jam || selectedTechnicians.length === 0) {
          alert("Lengkapi Form!");
          return;
        }

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
              $.ajax({
                url: "wa-msg.php",
                type: "POST",
                data: {
                  teknisi: selectedTechnicians,
                  kegiatanId: kegiatanId,
                  tanggal: tanggal,
                  jam: jam
                },
                success: function(msgResponse) {
                  if (msgResponse === "success") {
                    $("#jadwalkanModal").modal("hide");
                    alert("Berhasil");
                    window.location.reload();
                  } else {
                    window.location.reload();
                  }
                },
                error: function() {
                  alert("Terjadi kesalahan saat menghubungi server.");
                }
              });
            } else {
              alert("Gagal menjadwalkan kegiatan: " + response);
            }
          },
          error: function() {
            alert("Terjadi kesalahan saat menghubungi server.");
          }
        });
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

  </script>
  <script>
    function handleDateChange() {
      var selectedDate = document.getElementById("tanggal").value;
      var selectedTime = document.getElementById("jam").value;

      var checkboxes = document.querySelectorAll(".teknisi-checkbox");

      checkboxes.forEach(function(checkbox) {
        checkbox.disabled = false;
      });

      var xhr = new XMLHttpRequest();
      xhr.open("GET", "get-kegiatan-teknisi.php?tanggal=" + selectedDate, true);

      xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
          var kegiatanData = JSON.parse(xhr.responseText);

          checkboxes.forEach(function(checkbox) {
            var id_teknisi = checkbox.value;
            var teknisiData = kegiatanData.find(function(data) {
              return data.id_teknisi == id_teknisi;
            });

            if (teknisiData) {
              checkbox.disabled = false;
              // Format ulang teks label
              var formattedText = " ( ";
              if (teknisiData.jenis) {
                formattedText += teknisiData.jenis + " ";
              }
              formattedText += ` jam ${teknisiData.tgl_request.substring(11, 16)}`;
              if (teknisiData.status == "Pending") {
                formattedText += " - Dijadwalkan";
              } else if (teknisiData.status == "On Process") {
                formattedText += " - Dalam proses";
              }
              formattedText += ")";

              checkbox.nextElementSibling.textContent = checkbox.nextElementSibling.textContent.replace(/\(.*\)/, "") + formattedText;
            } else {
              checkbox.nextElementSibling.textContent = checkbox.nextElementSibling.textContent.replace(/\(.*\)/, "");
            }
          });
        }
      };

      xhr.send();
    }

    document.getElementById("tanggal").addEventListener("change", handleDateChange);

    window.addEventListener("load", handleDateChange);
  </script>


  <script>
    const jamInput = document.getElementById("jam");

    jamInput.addEventListener("input", function() {
      const selectedTime = new Date(`2000-01-01T${jamInput.value}`);
      const minTime = new Date(`2000-01-01T07:00`);
      const maxTime = new Date(`2000-01-01T20:00`);

      if (selectedTime < minTime || selectedTime > maxTime) {
        alert("Jam harus berada dalam rentang antara jam 07:00 pagi sampai jam 20:00 malam.");
        jamInput.value = "";
      }
    });
  </script>
  
  <script>
document.addEventListener('DOMContentLoaded', function() {
    const jadwalkanModal = document.getElementById('jadwalkanModal');
    const tanggalInput = document.getElementById('tanggal');
    const technicianListContainer = document.getElementById('technician-list-container');

   // Ganti fungsi lama Anda dengan yang ini
    async function fetchAndDisplaySchedules() {
        const selectedDate = tanggalInput.value;
        if (!selectedDate) return;
    
        // Reset tampilan sebelum memuat data baru
        const schedulePlaceholders = technicianListContainer.querySelectorAll('[id^="jadwal-teknisi-"]');
        const checkboxes = technicianListContainer.querySelectorAll('.teknisi-checkbox');
        
        schedulePlaceholders.forEach(el => el.innerHTML = '<span class="text-info">Mengecek jadwal...</span>');
        // Tidak perlu disable checkbox saat loading, agar user tetap bisa berinteraksi
        
        try {
            // Panggil API backend
            const response = await fetch(`cek_jadwal_teknisi.php?tanggal=${selectedDate}`);
            const schedules = await response.json();
    
            // Reset kembali semua placeholder jadwal
            schedulePlaceholders.forEach(el => el.innerHTML = '');
    
            // Loop melalui data jadwal yang diterima
            for (const teknisiId in schedules) {
                const teknisiScheduleDiv = document.getElementById(`jadwal-teknisi-${teknisiId}`);
                
                if (teknisiScheduleDiv) {
                    // Bangun string jadwal untuk ditampilkan sebagai peringatan
                    const scheduleText = schedules[teknisiId].map(item => 
                        `(${item.customer} | ${item.waktu})`
                    ).join(', ');
                    
                    // [PERBAIKAN] Tampilkan jadwal hanya sebagai teks peringatan berwarna merah
                    // Bagian yang menonaktifkan checkbox telah dihapus
                    teknisiScheduleDiv.innerHTML = `<span class="text-danger fw-bold">${scheduleText}</span>`;
                }
            }
        } catch (error) {
            console.error('Gagal mengambil data jadwal:', error);
            schedulePlaceholders.forEach(el => el.innerHTML = '<span class="text-danger">Gagal memuat jadwal.</span>');
        }
    }

    // Panggil fungsi saat tanggal diubah
    tanggalInput.addEventListener('change', fetchAndDisplaySchedules);

    // Panggil fungsi saat modal pertama kali ditampilkan
    jadwalkanModal.addEventListener('show.bs.modal', function () {
        // Set tanggal ke hari ini jika kosong
        if (!tanggalInput.value) {
            const today = new Date().toISOString().split('T')[0];
            tanggalInput.value = today;
        }
        fetchAndDisplaySchedules();
    });
});
</script>

</body>

</html>