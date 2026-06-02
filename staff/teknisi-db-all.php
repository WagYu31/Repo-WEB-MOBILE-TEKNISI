<div class="container mt-0 mt-n4">
    <div class="row row-equal-height">
            <!-- Kartu Form Tambah Teknisi -->
            <div class="col-md-4 col-12">
                <div class="card p-2 card-equal-height">
                    <div class="card-header pb-0 p-3">
                        <h6 class="lead font-weight-bold text-uppercase text-center">Tambah Personil Teknisi</h6>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" method="POST">
                            <div class="col-md-12">
                                <label for="nik" class="form-label">NIK</label>
                                <input type="text" class="form-control border p-2" id="nik" name="nik" placeholder="Nomor Induk Karyawan">
                            </div>
                            <div class="col-md-12">
                                <label for="nama" class="form-label">Nama</label>
                                <input type="text" class="form-control border p-2" id="nama" name="nama" placeholder="Nama">
                            </div>
                            <div class="col-md-12">
                                <label for="no_wa" class="form-label">Nomor WhatsApp</label>
                                <div class="input-group">
                                    <span class="text-center d-flex justify-content-center align-items-center p-0 bg-gradient-info text-white w-15" style="border-radius:6px; border-top-right-radius:0; border-bottom-right-radius:0;">+62</span>
                                    <input type="text" class="form-control border p-2 w-85" style="border-radius:6px; border-top-left-radius:0; border-bottom-left-radius:0;" id="no_wa" name="no_wa" placeholder="Nomor WhatsApp">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label for="ktp" class="form-label">No KTP</label>
                                <input type="text" class="form-control border p-2" id="ktp" name="ktp" placeholder="No KTP">
                            </div>
                            <div class="col-12 text-start">
                                <button type="submit" class="btn bg-gradient-info"><i class="bx bx-plus nav_icon"></i> Tambah</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Kartu Grafik Teknisi -->
            <div class="col-md-8 col-12">
                <div class="card p-4 card-equal-height">
                    <h5 class="text-center mb-4" style="text-transform:uppercase;">Grafik Target, Pendapatan, dan Bonus Teknisi</h5>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="filterMonth" class="form-label">Pilih Bulan</label>
                            <input type="month" id="filterMonth" class="form-control border p-2" value="<?php echo date('Y-m'); ?>">
                        </div>
                        <div class="col-md-6 d-flex justify-content-start align-items-end pt-4">
                            <button id="filterBtn" class="btn btn-primary w-40 filter-btn">Lihat</button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <canvas id="technicianChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    

    <div class="card p-4 mt-4">
        <div class="card-header pb-0 p-3">
            <div class="row">
                <div class="col-12 d-flex align-items-center">
                    <h6 class="mb-0 mx-1 ms-n3 lead font-weight-bold text-uppercase">Data Teknisi</h6>
                </div>
            </div>
        </div>
        <div class="card-body pb-0 p-0">
            <!-- Tabel data teknisi -->
            <div class="table-responsive mt-3">
                <table class="table text-center">
                    <thead class="text-dark">
                        <tr>
                            <th scope="col">No</th>
                            <th scope="col">NIK</th>
                            <th scope="col">Nama</th>
                            <th scope="col">Jumlah Kegiatan</th>
                            <th scope="col">Nomor Telepon</th>
                            <th scope="col" colspan="2">Target</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-dark">
                        <?php
                        // Query SQL untuk mengambil data teknisi dan total kegiatan, diurutkan berdasarkan total kegiatan secara menurun
                        $sql = "SELECT t.id, t.nik, t.nama, t.telp, COUNT(p.id) AS total_kegiatan, t.target
                        FROM teknisi t
                        LEFT JOIN pelaksanaan_kegiatan p ON t.id = p.teknisi_id AND p.status != 'N'
                        GROUP BY t.id, t.nik, t.nama, t.telp
                        ORDER BY total_kegiatan DESC";


                        $result = mysqli_query($conn, $sql);
                        $no = 1;
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $nik = $row["nik"];
                                $id_teknisi = $row["id"];
                                echo "<tr>";
                                echo "<td>" . $no . "</td>";
                                echo "<td>" . $row["nik"] . "</td>";
                                echo "<td><a href='list-kegiatan-teknisi.php?idTek=" . $id_teknisi . "'>" . $row['nama'] . "</a></td>";

                                $queryTotalKegiatan = "SELECT COUNT(*) AS total_kegiatan FROM pelaksanaan_kegiatan WHERE FIND_IN_SET('$id_teknisi', teknisi_id) > 0";
                                $resultTotalKegiatan = mysqli_query($conn, $queryTotalKegiatan);

                                if ($resultTotalKegiatan) {
                                    $totalKegiatanData = mysqli_fetch_assoc($resultTotalKegiatan);
                                    $totalKegiatan = $totalKegiatanData['total_kegiatan'];
                                } else {
                                    // Penanganan kesalahan jika query gagal
                                    $totalKegiatan = 0;
                                    echo "Error: " . mysqli_error($conn);
                                }



                                echo "<td style='text-align:center;'>" . $totalKegiatan . "</td>"; // Tampilkan total kegiatan

                                $nomorHandphone = $row['telp'];

                                // Cek apakah nomor handphone dimulai dengan angka 0
                                if (substr($nomorHandphone, 0, 1) === '0') {
                                    // Ganti angka 0 dengan 62
                                    $nomorHandphone = '62' . substr($nomorHandphone, 1);
                                }

                                echo "<td><a href='https://api.whatsapp.com/send?phone=$nomorHandphone' target='_blank'>";
                                echo $row['telp'];
                                echo "</a></td>";
                                // Tombol Delete
                        ?>
                                <td>
                                    <?php 
                                        echo $row['target'] !== null ? "Rp " . number_format($row['target'], 0, ',', '.') : "Rp 0";
                                    ?>
                                </td>
                                <td><button class='btn bg-gradient-primary p-2' onclick='openTargetModal("<?php echo $nik; ?>")'><i class="material-icons opacity-10">edit</i></button></td>
                                <td><button class='btn bg-gradient-danger p-2' onclick='deleteTek("<?php echo $nik; ?>")'><i class="material-icons opacity-10">delete</i></button></td>
                                <!-- Modal untuk Input Target -->

                                <div class="modal fade" id="targetModal" tabindex="-1" role="dialog" aria-labelledby="targetModalLabel" aria-hidden="true">
                                  <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                      <div class="modal-header">
                                        <h5 class="modal-title" id="targetModalLabel">Input Nominal Target</h5>
                                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                          <span aria-hidden="true">&times;</span>
                                        </button>
                                      </div>
                                      <div class="modal-body">
                                        <form id="targetForm">
                                          <div class="form-group">
                                                <label for="targetInput">Nominal Target</label>
                                                <input type="text" class="border p-2 form-control" id="targetInput" name="target" required>
                                            </div>
                                          <input type="hidden" id="nikInput" name="nik">
                                          <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-primary">Submit</button>
                                          </div>
                                        </form>
                                      </div>
                                    </div>
                                  </div>
                                </div>

                        <?php
                                echo "</tr>";
                                $no++;
                            }
                        } else {
                            echo "<tr><td colspan='4'>Tidak ada data teknisi.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Script Chart.js dan AJAX untuk Memuat Data -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Event untuk memuat data sesuai bulan dan tahun
        document.getElementById('filterBtn').addEventListener('click', loadChartData);

        async function loadChartData() {
            const selectedDate = document.getElementById('filterMonth').value;
            const response = await fetch(`get_chart_data.php?date=${selectedDate}`);
            const data = await response.json();

            updateChart(data);
        }

        // Inisialisasi Chart.js
        const ctx = document.getElementById('technicianChart').getContext('2d');
        let technicianChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [
                    { label: 'Target', data: [], backgroundColor: 'rgba(54, 162, 235, 0.6)' },
                    { label: 'Pendapatan', data: [], backgroundColor: 'rgba(75, 192, 192, 0.6)' },
                    { label: 'Bonus', data: [], backgroundColor: 'rgba(255, 206, 86, 0.6)' }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Fungsi untuk Memperbarui Chart
        function updateChart(data) {
            technicianChart.data.labels = data.labels;
            technicianChart.data.datasets[0].data = data.targets;
            technicianChart.data.datasets[1].data = data.pendapatan;
            technicianChart.data.datasets[2].data = data.bonus;
            technicianChart.update();
        }

        // Memuat data awal (bulan ini)
        loadChartData();
    </script>