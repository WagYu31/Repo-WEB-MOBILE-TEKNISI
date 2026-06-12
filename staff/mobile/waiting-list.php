<?php
include "../conn.php";
include "../session.php";
include "../get-user-data.php";
$pageNow = "Waiting List";
// Set locale ke bahasa Indonesia untuk format tanggal
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Waiting List - Mobile View</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <style>
        body {
            background-color: #f0f2f5; /* Warna latar yang lebih soft */
        }
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .card-header, .card-footer {
            background-color: #ffffff;
            border-bottom: 1px solid #f0f2f5;
        }
        .customer-name {
            font-weight: 600;
            color: #0d6efd;
        }
        .info-item i.fa-fw {
            color: #6c757d; /* Warna ikon info */
        }
        .date-overdue { color: #dc3545; font-weight: bold; }
        .date-upcoming { color: #0d6efd; font-weight: bold; }
        .date-normal { color: #343a40; }

        /* Memastikan modal tidak tertutup oleh elemen lain */
        .modal { z-index: 1060; }
        .modal-backdrop { z-index: 1055; }
    </style>
</head>

<body>
    <main class="main-content mb-5">
        <div class="container-fluid py-3">

            <div class="row mb-3">
                <div class="col-12">
                    <h4 class="mb-0"><i class="fa-solid fa-hourglass-half me-2"></i>Waiting List</h4>
                    <p class="text-muted mb-0">Tugas yang perlu dijadwalkan</p>
                </div>
            </div>

            <?php
            // --- Kueri PHP untuk mengambil data (TIDAK ADA PERUBAHAN) ---
            $sql = "SELECT k.*, c.nama as nama_customer, c.telp as cust_telp, c.alamat as cust_alamat
                    FROM kegiatan k
                    LEFT JOIN customer c ON k.customer_id = c.id
                    WHERE k.status = 'waiting' AND k.deleted_at IS NULL
                    ORDER BY 
                        CASE 
                            WHEN DATE(k.jadwal) = CURDATE() THEN 1
                            WHEN DATE(k.jadwal) = CURDATE() + INTERVAL 1 DAY THEN 2
                            WHEN DATE(k.jadwal) > CURDATE() THEN 3
                            ELSE 4
                        END ASC,
                        k.created_at ASC";

            $result = mysqli_query($conn, $sql);

            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
            ?>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 customer-name"><?= htmlspecialchars($row['nama_customer']); ?></h6>
                        <small class="text-muted"><?= ucwords(htmlspecialchars($row['kegiatan'])); ?></small>
                    </div>
                    <div class="text-end">
                        <?php
                        // --- Logika Penampilan Tanggal (TIDAK ADA PERUBAHAN FUNGSI) ---
                        $tgl_request = strtotime($row["jadwal"]);
                        if ($row["jadwal"] == '0000-00-00 00:00:00') {
                            echo '<span class="date-normal">' . date('d M Y', strtotime($row["created_at"])) . '</span>';
                            echo '<br><small class="text-muted">Dilaporkan</small>';
                        } else {
                            $cekDate = date('Y-m-d', $tgl_request);
                            $current_date = date('Y-m-d');
                            $date_class = 'date-normal';
                            if ($cekDate < $current_date) {
                                $date_class = 'date-overdue'; // Terlewat
                            } elseif ($cekDate <= date('Y-m-d', strtotime('+2 days'))) {
                                $date_class = 'date-upcoming'; // Segera
                            }
                            echo '<span class="' . $date_class . '">' . date('d M Y', $tgl_request) . '</span>';
                            echo '<br><small class="text-muted">' . date('H:i', $tgl_request) . ' WIB</small>';
                        }
                        ?>
                    </div>
                </div>
                <div class="card-body py-2">
                    <p class="mb-1 info-item"><i class="fa-solid fa-location-dot fa-fw me-2"></i><?= htmlspecialchars($row['cust_alamat']); ?></p>
                    <p class="mb-1 info-item"><i class="fa-solid fa-circle-info fa-fw me-2"></i><?= htmlspecialchars($row['keterangan']); ?></p>
                    <p class="mb-0 info-item"><i class="fa-solid fa-user-check fa-fw me-2"></i>Request: <?= htmlspecialchars($row['request']); ?></p>
                </div>
                <div class="card-footer d-grid gap-2">
                    <button class="btn btn-primary jadwalkan-btn" 
                        data-id="<?= $row["id"]; ?>" 
                        data-tgl-request="<?= $row["jadwal"]; ?>">
                        <i class="fa-solid fa-calendar-check me-2"></i>Jadwalkan
                    </button>
                    <button class="btn btn-outline-danger hapus-btn" 
                        data-id="<?= $row["id"]; ?>" 
                        data-kode="<?= $row["kode"]; ?>" 
                        data-nama="<?= $nmUser; ?>">
                        <i class="fa-solid fa-trash-alt me-2"></i>Hapus
                    </button>
                </div>
            </div>

            <?php
                } // End while loop
            } else {
                echo "<div class='alert alert-success text-center'><i class='fa-solid fa-check-circle me-2'></i>Tidak ada tugas dalam antrian.</div>";
            }
            ?>
        </div> <?php include "bottom-navbar.php"; ?>
    </main>

    <div class="modal fade" id="jadwalkanModal" tabindex="-1" aria-labelledby="jadwalkanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="jadwalkanModalLabel">Jadwalkan Kegiatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="jadwalkanForm">
                        <div class="mb-3">
                            <label for="tanggal" class="form-label">Tanggal:</label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" required>
                        </div>
                        <div class="mb-3">
                            <label for="jam" class="form-label">Jam:</label>
                            <input type="time" class="form-control" id="jam" name="jam" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pilih Teknisi (yang tersedia):</label>
                            <div id="technician-list-container">
                                <?php
                                $sql_teknisi = "SELECT id, nama FROM teknisi WHERE deleted_at IS NULL ORDER BY nama ASC";
                                $result_teknisi = mysqli_query($conn, $sql_teknisi);
                                if ($result_teknisi && mysqli_num_rows($result_teknisi) > 0) {
                                    while ($row_teknisi = mysqli_fetch_assoc($result_teknisi)) {
                                        $id_teknisi = $row_teknisi['id'];
                                        $nama_teknisi = htmlspecialchars($row_teknisi['nama']);
                                        echo "<div class='form-check'>";
                                        echo "<input class='form-check-input teknisi-checkbox' type='checkbox' name='teknisi[]' value='$id_teknisi' id='teknisi$id_teknisi'>";
                                        echo "<label class='form-check-label' for='teknisi$id_teknisi'>$nama_teknisi</label>";
                                        echo "<div class='text-muted text-xs ms-4' id='jadwal-teknisi-$id_teknisi'></div>";
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    $(document).ready(function() {
        // --- Script untuk membuka modal penjadwalan ---
        $(".jadwalkan-btn").click(function() {
            var kegiatanId = $(this).data("id");
            var tglRequest = $(this).data("tgl-request");

            $("#jadwalkanForm")[0].reset();
            $("#jadwalkanForm").attr("data-id", kegiatanId);

            var tanggalInput = document.getElementById("tanggal");
            var jamInput = document.getElementById("jam");

            if (tglRequest && tglRequest !== '0000-00-00 00:00:00') {
                var tglWaktu = tglRequest.split(" ");
                if (tglWaktu.length === 2) {
                    tanggalInput.value = tglWaktu[0];
                    jamInput.value = tglWaktu[1].substring(0, 5);
                }
            } else {
                tanggalInput.value = new Date().toISOString().split('T')[0];
            }

            var jadwalkanModal = new bootstrap.Modal(document.getElementById('jadwalkanModal'));
            jadwalkanModal.show();
        });

        // --- Script untuk submit penjadwalan ---
        $("#submitJadwalkan").click(function() {
            var kegiatanId = $("#jadwalkanForm").attr("data-id");
            var tanggal = $("#tanggal").val();
            var jam = $("#jam").val();
            var selectedTechnicians = $(".teknisi-checkbox:checked").map(function() { return this.value; }).get();

            if (!tanggal || !jam || selectedTechnicians.length === 0) {
                alert("Harap lengkapi tanggal, jam, dan pilih minimal satu teknisi.");
                return;
            }

            $.ajax({
                url: "proses_jadwalkan.php",
                type: "POST",
                data: { kegiatanId: kegiatanId, teknisi: selectedTechnicians, tanggal: tanggal, jam: jam },
                success: function(response) {
                    if (response.trim() === "success") {
                        // Opsi: Kirim notifikasi WA setelah sukses
                        $.post("wa-msg.php", { teknisi: selectedTechnicians, kegiatanId: kegiatanId, tanggal: tanggal, jam: jam })
                        .always(function() {
                            alert("Kegiatan berhasil dijadwalkan!");
                            window.location.reload();
                        });
                    } else {
                        alert("Gagal menjadwalkan kegiatan: " + response);
                    }
                },
                error: function() { alert("Terjadi kesalahan server saat menjadwalkan."); }
            });
        });

        // --- Script untuk hapus kegiatan ---
        $(".hapus-btn").click(function() {
            var kegiatanId = $(this).data("id");
            var nama = $(this).data("nama");
            var kode = $(this).data("kode");

            if (confirm("Apakah Anda yakin ingin menghapus kegiatan ini dari waiting list?")) {
                $.ajax({
                    url: "proses_hapus_kegiatan.php",
                    type: "POST",
                    data: { kegiatanId: kegiatanId, nama: nama, kode: kode },
                    success: function(response) {
                        if (response.trim() === "success") {
                            alert("Kegiatan berhasil dihapus.");
                            window.location.reload();
                        } else {
                            alert("Gagal menghapus kegiatan: " + response);
                        }
                    },
                    error: function() { alert("Terjadi kesalahan server saat menghapus."); }
                });
            }
        });

        // --- Script untuk cek jadwal teknisi di modal ---
        async function fetchAndDisplaySchedules() {
            const selectedDate = $('#tanggal').val();
            if (!selectedDate) return;
            
            const technicianContainer = $('#technician-list-container');
            technicianContainer.find('[id^="jadwal-teknisi-"]').html('<span class="text-info">Mengecek jadwal...</span>');
            
            try {
                const response = await fetch(`cek_jadwal_teknisi.php?tanggal=${selectedDate}`);
                const schedules = await response.json();

                technicianContainer.find('[id^="jadwal-teknisi-"]').html(''); // Clear all placeholders

                for (const teknisiId in schedules) {
                    const teknisiScheduleDiv = $(`#jadwal-teknisi-${teknisiId}`);
                    if (teknisiScheduleDiv.length) {
                        const scheduleText = schedules[teknisiId].map(item => `(${item.customer} | ${item.waktu})`).join(', ');
                        teknisiScheduleDiv.html(`<span class="text-danger fw-bold">${scheduleText}</span>`);
                    }
                }
            } catch (error) {
                console.error('Gagal mengambil data jadwal:', error);
                technicianContainer.find('[id^="jadwal-teknisi-"]').html('<span class="text-danger">Gagal memuat jadwal.</span>');
            }
        }

        $('#tanggal').on('change', fetchAndDisplaySchedules);

        $('#jadwalkanModal').on('show.bs.modal', function () {
            if (!$('#tanggal').val()) {
                $('#tanggal').val(new Date().toISOString().split('T')[0]);
            }
            fetchAndDisplaySchedules();
        });
        
        // --- Script validasi jam ---
        $("#jam").on("input", function() {
            const selectedTime = $(this).val();
            if (selectedTime && (selectedTime < "07:00" || selectedTime > "20:00")) {
                alert("Jam kerja adalah antara 07:00 pagi hingga 20:00 malam.");
                $(this).val("");
            }
        });
    });
    </script>
</body>
</html>