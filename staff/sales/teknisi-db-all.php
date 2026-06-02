<div class="container">
    <div class="card p-4">
        <div class="card-header pb-0 p-3">
            <div class="row">
                <div class="col-12 d-flex align-items-center">
                    <h6 class="mb-0 mx-1 ms-n3 lead font-weight-bold text-uppercase">Tambah Personil Teknisi</h6>
                </div>
            </div>
        </div>
        <div class="card-body pb-0 p-0">
            <!-- Form input data teknisi -->
            <form class="row g-3" method="POST">
                <div class="col-md-4">
                    <label for="nik" class="form-label">NIK</label>
                    <input type="text" class="form-control border p-2" id="nik" name="nik" placeholder="NIK">
                </div>
                <div class="col-md-4">
                    <label for="nama" class="form-label">Nama</label>
                    <input type="text" class="form-control border p-2" id="nama" name="nama" placeholder="Nama">
                </div>
                <div class="col-md-4">
                    <label for="no_wa" class="form-label">Nomor WhatsApp</label>
                    <div class="col-12 col-md-11 d-flex flex-row">
                        <span class="text-start p-2 bg-gradient-info text-white w-14" style="border-radius:6px; border-top-right-radius:0; border-bottom-right-radius:0;">+62</span>
                        <input type="text" class="form-control border p-2 w-86" style="border-radius:6px; border-top-left-radius:0; border-bottom-left-radius:0;" id="no_wa" name="no_wa" placeholder="Nomor WhatsApp">
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn bg-gradient-info"><i class="bx bx-plus nav_icon"></i> Tambah</button>
                </div>
            </form>
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
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Query SQL untuk mengambil data teknisi dan total kegiatan, diurutkan berdasarkan total kegiatan secara menurun
                        $sql = "SELECT t.id_teknisi, t.nik, t.nama, t.no_wa, COUNT(k.id_kegiatan) AS total_kegiatan
                        FROM teknisi t
                        LEFT JOIN kegiatan k ON t.id_teknisi = k.id_teknisi AND k.status != 'N'
                        GROUP BY t.id_teknisi, t.nik, t.nama, t.no_wa
                        ORDER BY total_kegiatan DESC";


                        $result = mysqli_query($conn, $sql);
                        $no = 1;
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $nik = $row["nik"];
                                $id_teknisi = $row["id_teknisi"];
                                echo "<tr>";
                                echo "<td>" . $no . "</td>";
                                echo "<td>" . $row["nik"] . "</td>";
                                echo "<td><a href='teknisi-detail.php?id_teknisi=" . $id_teknisi . "'>" . $row['nama'] . "</a></td>";

                                $queryTotalKegiatan = "SELECT COUNT(*) AS total_kegiatan FROM kegiatan WHERE FIND_IN_SET('$id_teknisi', id_teknisi) > 0";
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

                                $nomorHandphone = $row['no_wa'];

                                // Cek apakah nomor handphone dimulai dengan angka 0
                                if (substr($nomorHandphone, 0, 1) === '0') {
                                    // Ganti angka 0 dengan 62
                                    $nomorHandphone = '62' . substr($nomorHandphone, 1);
                                }

                                echo "<td><a href='https://api.whatsapp.com/send?phone=$nomorHandphone' target='_blank'>";
                                echo $row['no_wa'];
                                echo "</a></td>";
                                // Tombol Delete
                        ?>
                                <td><button class='btn bg-gradient-danger' onclick='deleteTek("<?php echo $nik; ?>")'><i class='far fa-trash-alt'></i></button></td>
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