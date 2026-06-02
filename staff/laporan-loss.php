<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Laporan";
$currentPage = "Today";
$role = $jabatan;

if (isset($_GET['error'])) {
    $error_code = $_GET['error'];
    if ($error_code == 1) {
        echo "<script>alert('Gagal memproses data. Silakan coba lagi.');</script>";
    } elseif ($error_code == 2) {
        echo "<script>alert('Gagal. Data yang diperlukan tidak lengkap.');</script>";
    } elseif ($error_code == 3) {
        echo "<script>alert('Permintaan tidak valid. Silakan coba lagi.');</script>";
    }
} elseif (isset($_GET['success'])) {
    echo "<script>alert('Berhasil menambahkan invoice.');</script>";
}

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
        ul#data-rincian li:nth-child(odd) {
            background-color: white;
        }

        ul#data-rincian li:nth-child(even) {
            background-color: #efefef;
            border-radius: 0;
        }

        ul#data-tek li {
            border-radius: 0;
            border: 0;
            border-top: 1px solid #333;
        }

        #toggleLoadMore {
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }

        .modal-lg {
            width: 60vw !important;
        }

        .custom-border-radius {
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }
        
        .custom-border-radius-1 {
            border-top-left-radius: 0;
            border-top-right-radius: 0;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }

        .custom-border-radius-2 {
            border-top-left-radius: 0;
            border-top-right-radius: 0.5rem;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }

        /* CSS untuk mengatur lebar modal menjadi 100vw pada perangkat mobile */
        @media (max-width: 767px) {
            .modal-lg {
                width: 95vw !important;
            }
        }
        <?php include "css/floating-menu2.css";?>
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
        setlocale(LC_TIME, 'id_ID'); // Set locale ke Indonesia
        $todayDate = strftime('%d %B %Y');
        ?>
        <!-- End Navbar -->
        <div class="container-fluid py-4">
            <div class="row mt-2">
                <div class="col-md-8 col-12 d-flex justify-content-start align-items-center">
                    <a href="laporan-kegiatan.php" class="btn bg-gradient-dark w-md-35 w-50 custom-border-radius">Belum Input Invoice</a>
                    <a href="laporan-kegiatan-selesai.php" class="btn bg-gradient-dark w-md-35 w-45 custom-border-radius-1">Selesai</a>
                    <a href="laporan-loss.php" class="btn bg-gradient-danger w-md-35 w-45 custom-border-radius-1">Tidak Selesai</a>
                    <a href="print-laporan-kegiatan.php" class="btn bg-gradient-dark w-md-35 w-45 me-2 custom-border-radius-2" target="_blank">Print Preview</a>
                </div>
            </div>
            <div class="row mb-4 mt-n3">

                <?php
                include "laporan-db-loss.php";
                ?>

            </div>
                <?php
                    include "floating-menu.php";
            include "footer.php";
            ?>
        </div>



        <!-- Modal untuk memasukkan bonus -->
        <div class="modal fade" id="bonusModal" tabindex="-1" aria-labelledby="bonusModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bonusModalLabel">Denda</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="bonusForm">
                        <div class="modal-body">
                            <div class="input-group d-flex flex-row justify-content-start align-items-start mt-3">
                                <label class="col-12">Denda</label>
                                <span class="text-center w-10 p-2 bg-gradient-info text-white border-end-0" style="border-radius: 7px 0 0 7px;">Rp</span>
                                <input type="number" id="dendaInput" name="denda" class="form-control border p-2 text-start w-70" placeholder="Masukkan nominal denda">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn bg-gradient-danger" data-bs-dismiss="modal">Tutup</button>
                            <button type="button" class="btn bg-gradient-info" id="submitBonus">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <!-- Modal untuk memasukkan invoice -->
        <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true" style="overflow-y:auto;">
            <div class="modal-dialog modal-dialog-centered modal-lg modalDetail">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="detailModalLabel">Rincian Pekerjaan</h5>
                        <button type="button" class="btn bg-gradient-danger p-2" data-bs-dismiss="modal" aria-label="Close"><i class="material-icons opacity-10">close</i></button>
                    </div>
                    <form id="detailForm" data-kode="">
                        <div class="modal-body">
                            <div class="input-group d-flex flex-row justify-content-start align-items-start">
                                <div id="dataDetailTek" class="px-3" style="width: 100%;"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn bg-gradient-danger" data-bs-dismiss="modal">Tutup</button>
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

    <script>
        $(document).ready(function() {
            $('.bonus-btn').click(function() {
                var tekId = $(this).data("id");
                var kodeTran = $(this).data("kode");
                // Reset form dan atur nilai data-id
                $("#bonusForm")[0].reset();
                $("#bonusForm").attr("data-id", tekId);
                $("#bonusForm").attr("data-kode", kodeTran);
                $("#bonusModal").modal("show");
            });

            // Fungsi untuk menangani klik pada tombol submit
            $("#submitBonus").click(function() {
                var tekId = $("#bonusForm").data("id");
                var kodeTran = $("#bonusForm").data("kode");
                var denda = $("#dendaInput").val();

                $.ajax({
                    url: "proses_update_bonus.php",
                    type: "POST",
                    data: {
                        tekId: tekId,
                        kodeTran: kodeTran,
                        denda: denda
                    },
                    success: function(response) {
                        if (response.trim() === "success") {
                            $("#bonusModal").modal("hide");
                            alert("Berhasil memperbarui denda kegiatan.");
                            window.location.reload();
                        } else {
                            alert("Gagal memperbarui denda kegiatan.");
                        }
                    },
                    error: function() {
                        alert("Terjadi kesalahan saat menghubungi server.");
                    }
                });
            });



            $(".detailBtn").click(function() {
                var kode_transaksi = $(this).data('kode'); // Ambil kode transaksi dari data-kode

                // Kirim permintaan AJAX untuk mendapatkan data berdasarkan id_teknisi dan kode transaksi
                $.ajax({
                    url: 'get-detail-pekerjaan.php', // Ganti dengan URL skrip PHP yang mengambil data dari database
                    type: 'POST',
                    data: {
                        kode_transaksi: kode_transaksi
                    },
                    success: function(response) {
                        // Isi div #dataTek dengan data yang diterima dari server
                        $("#dataDetailTek").html(response);
                    },
                    error: function(xhr, status, error) {
                        // Tangani kesalahan jika ada
                        console.error(xhr.responseText);
                    }
                });
            });
        });
    </script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</body>

</html>