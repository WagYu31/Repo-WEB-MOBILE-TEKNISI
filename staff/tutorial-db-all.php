<div class="container mt-0 mt-n4">
    <div class="row row-equal-height">
        <!-- Kartu Form Tambah Data -->
        <div class="col-md-12 col-12">
            <div class="card p-2 card-equal-height">
                <div class="card-header pb-0 p-3">
                    <h6 class="lead font-weight-bold text-uppercase text-center">Tambah Data</h6>
                </div>
                <div class="card-body">
                    <form class="row g-3" method="POST" enctype="multipart/form-data">
                        <div class="col-md-12">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control border p-2" id="title" name="title" placeholder="Title" required>
                        </div>
                        <div class="col-md-12">
                            <label for="media_1" class="form-label">Media 1 (Optional)</label>
                            <input type="file" class="form-control border p-2" id="media_1" name="media_1" accept="image/*,video/*,audio/*,.pdf,.doc,.docx">
                        </div>
                        <div class="col-md-12">
                            <label for="media_2" class="form-label">Media 2 (Optional)</label>
                            <input type="file" class="form-control border p-2" id="media_2" name="media_2" accept="image/*,video/*,audio/*,.pdf,.doc,.docx">
                        </div>
                        <div class="col-md-12">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <textarea class="form-control border p-2" id="description" name="description" rows="4" placeholder="Description"></textarea>
                        </div>
                        <div class="col-12 text-start">
                            <button type="submit" class="btn bg-gradient-info"><i class="bx bx-plus nav_icon"></i> Tambah</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    

    <div class="card p-4 mt-4">
        <div class="card-header pb-0 p-3">
            <div class="row">
                <div class="col-12 d-flex align-items-center">
                    <h6 class="mb-0 mx-1 ms-n3 lead font-weight-bold text-uppercase">Data Tutorial</h6>
                </div>
            </div>
        </div>
        <div class="card-body pb-0 p-0">
            <!-- Tabel data teknisi -->
            <div class="table-responsive mt-3">
                <table class="table text-start" style="font-size:12px">
                    <thead class="text-dark">
                        <tr>
                            <th scope="col">No</th>
                            <th scope="col">Title</th>
                            <th scope="col">Media 1</th>
                            <th scope="col">Media 2</th>
                            <th scope="col">Description</th>
                            <th scope="col" colspan="2">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-dark">
                        <?php
                        // Query SQL untuk mengambil data
                        $sql = "SELECT * FROM data WHERE deleted_at IS NULL ORDER BY created_at DESC";
                    
                        $result = mysqli_query($conn, $sql);
                        $no = 1;
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $idData = $row["id"];
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $no; ?></td>
                                    <td><?php echo htmlspecialchars($row["title"]); ?></td>
                                    <td><?php echo htmlspecialchars($row["media_1"]); ?></td>
                                    <td><?php echo htmlspecialchars($row["media_2"]); ?></td>
                                    <td><?php echo htmlspecialchars($row["description"]); ?></td>
                                    <td>
                                        <!-- Tombol Edit -->
                                        <button class="btn bg-gradient-primary p-2" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal<?php echo $idData; ?>">
                                            <i class="material-icons opacity-10">edit</i>
                                        </button>
                                    </td>
                                    <td>
                                        <!-- Tombol Delete -->
                                        <a class="btn bg-gradient-danger p-2" 
                                                href="delete-tutor.php?id=<?php echo $idData; ?>">
                                            <i class="material-icons opacity-10">delete</i>
                                        </a>
                                    </td>
                                </tr>
                    
                                <!-- Modal Edit -->
                                <div class="modal fade" id="editModal<?php echo $idData; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel<?php echo $idData; ?>" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editModalLabel<?php echo $idData; ?>">Edit Tutorial</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form id="editForm<?php echo $idData; ?>" action="update_tutor.php" method="POST" enctype="multipart/form-data">
                                                    <div class="mb-3">
                                                        <label for="title<?php echo $idData; ?>" class="form-label">Title</label>
                                                        <input type="text" class="form-control border p-2" id="title<?php echo $idData; ?>" name="title" value="<?php echo htmlspecialchars($row['title']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="media_1<?php echo $idData; ?>" class="form-label">Media 1 (Optional)</label>
                                                        <input type="file" class="form-control border p-2" id="media_1<?php echo $idData; ?>" name="media_1" accept="image/*,video/*,audio/*,.pdf,.doc,.docx">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="media_2<?php echo $idData; ?>" class="form-label">Media 2 (Optional)</label>
                                                        <input type="file" class="form-control border p-2" id="media_2<?php echo $idData; ?>" name="media_2" accept="image/*,video/*,audio/*,.pdf,.doc,.docx">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="description<?php echo $idData; ?>" class="form-label">Description (Optional)</label>
                                                        <textarea class="form-control border p-2" id="description<?php echo $idData; ?>" name="description" rows="4"><?php echo htmlspecialchars($row['description']); ?></textarea>
                                                    </div>
                                                    <input type="hidden" name="idData" value="<?php echo $idData; ?>">
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" class="btn btn-primary">Save changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                $no++;
                                
                            }
                        } else {
                            echo "<tr><td colspan='7'>Tidak ada data tutorial.</td></tr>";
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