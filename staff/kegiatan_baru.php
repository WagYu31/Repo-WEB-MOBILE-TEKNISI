<?php
include "conn.php";
include "session.php";
$pageNow = "Kegiatan Baru";
include "get-user-data.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $customer_id = $_POST['nama_cust'];
    $kegiatan = $_POST['kegiatan'];
    $jadwal = $_POST['tanggal'];
    $keterangan = $_POST['keterangan'];
    // Ambil data relasi, jika kosong simpan sebagai NULL
    $kegiatan_relasi = !empty($_POST['kegiatan_relasi']) ? $_POST['kegiatan_relasi'] : NULL;
    
    $status = 'waiting';
    date_default_timezone_set('Asia/Jakarta');
    $now = date('Y-m-d H:i:s');

    // Buat kode acak 6 digit
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $kode = substr(str_shuffle($characters), 0, 6);

    // Mencegah SQL Injection dengan Prepared Statements
    // Menambahkan kolom 'relasi' ke dalam query
    $sql = "INSERT INTO kegiatan (customer_id, jadwal, kegiatan, keterangan, request, status, kode, relasi, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    // Tipe data di bind_param diubah: 'isssssssss' -> i: int, s: string
    mysqli_stmt_bind_param($stmt, "isssssssss", $customer_id, $jadwal, $kegiatan, $keterangan, $nmUser, $status, $kode, $kegiatan_relasi, $now, $now);

    if (!mysqli_stmt_execute($stmt)) {
        echo "Error: " . mysqli_stmt_error($stmt);
    }
    
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php include "head.php"; ?>
    <style>
        <?php include "css/floating-menu2.css"; ?>
        <?php include "css/kegiatan-baru-styles.css"; ?>
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <?php
        include "nav-top.php";
        $todayDate = formatTanggal('dd MMMM yyyy');
        ?>
        <div class="container-fluid py-4">
            <div class="row d-flex flex-row align-items-start">
                <div class="col-12 col-md-5 mt-4 mb-4">
                    <div class="card z-index-2">
                        <div class="card-header col-9 col-md-7 p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
                            <div class="bg-gradient-info shadow-info border-radius-lg py-3 pe-1">
                                <div class="chart px-3">
                                    <h5 class="text-light text-bold">Tambah Kegiatan Baru</h5>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="kegiatanForm">

                                <div class="form-group">
                                    <div class="dropdown">
                                        <label for="nama_customer">Nama Customer</label>
                                        <button type="button" class="dropdown-button btn btn-primary">
                                            Pilih Customer ▼
                                        </button>
                                        <div id="dropdownItems">
                                            <input type="text" id="dropdownSearch" placeholder="Cari customer...">
                                            <?php
                                            $sql_cust = "SELECT id, nama, telp FROM customer ORDER BY nama ASC";
                                            $result_cust = mysqli_query($conn, $sql_cust);
                                            if (mysqli_num_rows($result_cust) > 0) {
                                                while ($row = mysqli_fetch_assoc($result_cust)) {
                                                    echo "<div class='dropdown-item' data-id='{$row['id']}'>{$row['nama']} - {$row['telp']}</div>";
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <input type="hidden" id="nama_cust" name="nama_cust" required>
                                </div>

                                <div class="form-group mt-2">
                                    <label for="kegiatan">Jenis Kegiatan</label>
                                    <select class="form-control border p-2" id="kegiatan" name="kegiatan">
                                        <option value="survey">Survey</option>
                                        <option value="pasang baru">Pasang Baru</option>
                                        <option value="service">Service</option>
                                    </select>
                                </div>

                                <div class="form-group mt-2">
                                    <label for="kegiatan_relasi">Terkait dengan Kegiatan (Opsional)</label>
                                    <select class="form-control border p-2" id="kegiatan_relasi" name="kegiatan_relasi" disabled>
                                        <option value="">Pilih Customer Dahulu</option>
                                    </select>
                                </div>

                                <div class="form-row-container mt-2">
                                    <div class="form-group">
                                        <label for="tanggal_survey_date">Tanggal</label>
                                        <input type="date" class="form-control border p-2" id="tanggal_survey_date" name="tanggal_survey_date">
                                    </div>
                                    <div class="form-group">
                                        <label for="tanggal_survey_time_hour">Jam</label>
                                        <select class="form-control border p-2" id="tanggal_survey_time_hour" name="tanggal_survey_time_hour"></select>
                                    </div>
                                    <div class="form-group">
                                        <label for="tanggal_survey_time_minute">Menit</label>
                                        <select class="form-control border p-2" id="tanggal_survey_time_minute" name="tanggal_survey_time_minute">
                                            <option value="00">00</option>
                                            <option value="15">15</option>
                                            <option value="30">30</option>
                                            <option value="45">45</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <input type="hidden" id="tanggal_survey_datetime_combined" name="tanggal">
                                
                                <div class="form-group my-4">
                                    <label for="keterangan">Keterangan</label>
                                    <textarea class="form-control border p-2" id="keterangan" rows="3" name="keterangan" placeholder="Keterangan tambahan"></textarea>
                                </div>

                                <button type="submit" class="btn bg-gradient-info">Submit</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-7 mt-4 mb-4" id="outputData">
                    </div>
            </div>
            
            <?php include "floating-menu.php"; ?>
            <?php include "footer.php"; ?>
        </div>
    </main>
    <?php include "js-include.php"; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {

        // --- Definisi Elemen ---
        const dropdownContainer = document.querySelector('.dropdown');
        const dropdownButton = document.querySelector('.dropdown-button');
        const dropdownItems = document.getElementById('dropdownItems');
        const dropdownSearch = document.getElementById('dropdownSearch');
        const outputDataContainer = document.getElementById('outputData');
        
        // [MODIFIKASI] Referensi ke select box relasi
        const relasiSelect = document.getElementById('kegiatan_relasi');

        if (!dropdownContainer || !dropdownButton || !dropdownItems || !dropdownSearch || !relasiSelect) {
            console.error("Elemen dropdown atau relasi tidak dapat ditemukan.");
            return;
        }

        // --- DROPDOWN LOGIC ---
        function toggleDropdown(event) {
            event.stopPropagation();
            const isVisible = dropdownItems.style.display === 'block';
            dropdownItems.style.display = isVisible ? 'none' : 'block';
            if (!isVisible) {
                dropdownSearch.focus();
            }
        }

        function filterFunction() {
            const filter = dropdownSearch.value.toUpperCase();
            const items = dropdownItems.getElementsByClassName('dropdown-item');
            for (let i = 0; i < items.length; i++) {
                const txtValue = items[i].textContent || items[i].innerText;
                items[i].style.display = txtValue.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
            }
        }
        
        // [MODIFIKASI] Fungsi untuk mereset/mengisi select box relasi
        function populateRelasiSelect(options = []) {
            // Kosongkan opsi yang ada
            relasiSelect.innerHTML = ''; 

            if (options.length > 0) {
                relasiSelect.disabled = false;
                // Tambahkan opsi default
                relasiSelect.add(new Option('Pilih kegiatan terkait...', ''));

                // Isi dengan opsi dari data
                options.forEach(opt => {
                    relasiSelect.add(new Option(opt.teks, opt.kode));
                });
            } else {
                relasiSelect.disabled = true;
                relasiSelect.add(new Option('Tidak ada riwayat kegiatan', ''));
            }
        }

        dropdownButton.addEventListener('click', toggleDropdown);
        dropdownSearch.addEventListener('keyup', filterFunction);

        window.addEventListener('click', function(event) {
            if (!dropdownContainer.contains(event.target)) {
                dropdownItems.style.display = 'none';
            }
        });

        // [MODIFIKASI] Event listener untuk memilih item dan mengambil data
        dropdownItems.addEventListener('click', function(event) {
            const target = event.target;
            if (target.classList.contains('dropdown-item')) {
                dropdownButton.innerText = target.innerText;
                document.getElementById('nama_cust').value = target.getAttribute('data-id');
                dropdownItems.style.display = 'none';

                // Reset tampilan sebelum fetch data baru
                outputDataContainer.innerHTML = '<p class="p-4">Memuat riwayat...</p>';
                populateRelasiSelect(); // Kosongkan dan disable select relasi
                relasiSelect.firstElementChild.innerText = 'Memuat...';

                const customerId = target.getAttribute('data-id');
                // Mengambil data dalam format JSON
                fetch('data_kegiatan_cust.php?customer_id=' + customerId)
                    .then(response => response.ok ? response.json() : Promise.reject('Gagal mengambil data.'))
                    .then(data => {
                        // 'data' kini adalah objek JSON: { displayHtml: "...", relasiOptions: [...] }
                        outputDataContainer.innerHTML = data.displayHtml;
                        populateRelasiSelect(data.relasiOptions);
                    })
                    .catch(error => {
                        console.error('Error fetching data:', error);
                        outputDataContainer.innerHTML = "<div class='alert alert-danger mx-4'>Gagal memuat riwayat kegiatan.</div>";
                        populateRelasiSelect(); // Kosongkan jika error
                        relasiSelect.firstElementChild.innerText = 'Gagal memuat';
                    });
            }
        });

        // --- DATE TIME LOGIC ---
        const hourSelect = document.getElementById('tanggal_survey_time_hour');
        for (let i = 0; i < 24; i++) {
            const hourValue = i.toString().padStart(2, '0');
            hourSelect.add(new Option(hourValue, hourValue));
        }

        function combineDateTime() {
            const dateValue = document.getElementById('tanggal_survey_date').value;
            const hourValue = document.getElementById('tanggal_survey_time_hour').value;
            const minuteValue = document.getElementById('tanggal_survey_time_minute').value;
            const combinedInput = document.getElementById('tanggal_survey_datetime_combined');

            if (dateValue && hourValue && minuteValue) {
                combinedInput.value = `${dateValue}T${hourValue}:${minuteValue}`;
            } else {
                combinedInput.value = '';
            }
        }

        ['change', 'input'].forEach(evt => {
            document.getElementById('tanggal_survey_date').addEventListener(evt, combineDateTime);
            document.getElementById('tanggal_survey_time_hour').addEventListener(evt, combineDateTime);
            document.getElementById('tanggal_survey_time_minute').addEventListener(evt, combineDateTime);
        });

    });
    </script>
</body>
</html>