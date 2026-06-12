<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Dashboard-Sales";
$currentPage = "Today";
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <?php
  include "head.php";
  ?>
  <style>
    ul#data-tek li:nth-child(odd) {
      background-color: white;
    }

    ul#data-tek li:nth-child(even) {
      background-color: #efefef;
      border-radius: 0;
    }
        #toggleLoadMore, #toggleLoadMore1, #toggleLoadMore2 {
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }
  </style>
</head>

<body class="g-sidenav-show  bg-gray-200">
  <?php
  include "cek-menu.php";
  ?>

  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <!-- Navbar -->
    <?php
    include "nav-top.php";
    $todayDate = formatTanggal('dd MMMM yyyy');
    ?>
    <!-- End Navbar -->
    <div class="container-fluid py-4">


      <div class="row mb-4 mt-0">

        <?php
        include "kegiatan-db-sales-2.php";
        include "kegiatan-db-load-more-sales.php";
        ?>

      </div>
      <?php
      include "footer.php";
      ?>
    </div>


    <!-- Modal Jadwalkan -->
    <div class="modal fade" id="jadwalkanModal" tabindex="-1" role="dialog" aria-labelledby="jadwalkanModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="jadwalkanModalLabel">Jadwalkan Kegiatan</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="location.href='index-sa.php';">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <!-- Form to schedule an activity -->
            <form id="jadwalkanForm">
              <div class="form-group">
                <label for="tanggal">Tanggal:</label>
                <input type="date" class="form-control" id="tanggal" name="tanggal" required>
              </div>
              <div class="form-group">
                <label for="jam">Jam:</label>
                <input type="time" class="form-control" id="jam" name="jam" required>
              </div>
              <div class="form-group">
                <label for="nama_teknisi">Nama Teknisi</label>
                <?php
                // Query to fetch data from the 'teknisi' table
                $sql = "SELECT id_teknisi, nama FROM teknisi";
                $result = mysqli_query($conn, $sql);

                // Check if there are any technicians available
                if (mysqli_num_rows($result) > 0) {
                  while ($row = mysqli_fetch_assoc($result)) {
                    $id_teknisi = $row['id_teknisi'];
                    $nama_teknisi = $row['nama'];

                    // Display checkboxes for each technician
                    echo "<div class='form-check'>";
                    echo "<input class='form-check-input teknisi-checkbox' type='checkbox' name='teknisi[]' value='$id_teknisi' id='teknisi$id_teknisi' disabled>";
                    echo "<label class='form-check-label' for='teknisi$id_teknisi'>$nama_teknisi</label>";
                    echo "</div>";
                  }
                } else {
                  echo "Tidak ada teknisi tersedia.";
                }
                ?>
              </div>
            </form>
          </div>


          <div class="modal-footer">
            <!--<button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>-->
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
        $(document).ready(function(){
            $("#toggleLoadMore").click(function(){
                $("#loadMoreX").toggle();
                $(this).toggleClass("rounded");
            });
        });
        $(document).ready(function(){
            $("#toggleLoadMore1").click(function(){
                $("#loadMoreX1").toggle();
                $(this).toggleClass("rounded");
            });
        });
        $(document).ready(function(){
            $("#toggleLoadMore2").click(function(){
                $("#loadMoreX2").toggle();
                $(this).toggleClass("rounded");
            });
        });
    </script>
  
  <script>
    
    $(".view-btn").click(function() {
        var kegiatanId = $(this).data("id");
        window.location.href = "view-kegiatan-sales.php?kode_transaksi=" + kodeTransaksi;
    });
    $(".edit-btn").click(function() {
        var kodeTransaksi = $(this).data("id");
        window.location.href = "edit_kegiatan-sales.php?kode_transaksi=" + kodeTransaksi;
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
        url: "proses_jadwalkan-sales.php",
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
          url: "proses_hapus_kegiatan_sales.php",
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
      url: "delete-kegiatan-sales.php",
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
    function handleDateChange() {
      var selectedDate = document.getElementById("tanggal").value;
      var selectedTime = document.getElementById("jam").value;

      var checkboxes = document.querySelectorAll(".teknisi-checkbox");

      checkboxes.forEach(function(checkbox) {
        checkbox.disabled = false;
      });

      var xhr = new XMLHttpRequest();
      xhr.open("GET", "get-kegiatan-sales.php?tanggal=" + selectedDate, true);

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
              if (teknisiData.status == "dijadwalkan") {
                formattedText += " - Dijadwalkan";
              } else if (teknisiData.status == "on process") {
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

</body>

</html>