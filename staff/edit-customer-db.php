<div class="container mt-n4">
    <div class="card p-4 col-12 col-md-11">
        <div class="card-header pb-0 p-3">
            <div class="row">
                <div class="col-12 d-flex align-items-center">
                    <h6 class="mb-0 mx-1 ms-n3 lead font-weight-bold text-uppercase">Edit Data Customer Baru</h6>
                </div>
            </div>
        </div>
        <div class="card-body pb-0 p-0 mt-3">
            <?php
            $sqlCustomer = "SELECT * FROM customer WHERE id = $id_customer";
            $resultCustomer = mysqli_query($conn, $sqlCustomer);
            while ($rowCust = mysqli_fetch_assoc($resultCustomer)) {
                $namaCust = $rowCust['nama'];
                $nomorTlp = $rowCust['telp'];
                $alamatCust = $rowCust['alamat'];
            ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="nama">Nama</label>
                        <input type="text" class="form-control border p-2" id="nama" name="nama" placeholder="Masukkan nama" value="<?php echo $namaCust; ?>" required>
                    </div>
                        <div class="form-group mt-2">
                            <label for="no_whatsapp">No WhatsApp</label>
                            <div class="d-flex flex-row">
                                <div class="">
                                    <span class="input-group-text bg-gradient-info px-2 text-white" style="border-radius: 7px; border-bottom-right-radius:0; border-top-right-radius: 0;">+62</span>
                                </div>
                                <input type="text" class="form-control border px-2 py-3" style="border-radius: 7px; border-bottom-left-radius:0; border-top-left-radius: 0;" id="no_whatsapp" name="no_tlp" placeholder="Masukkan No WhatsApp" value="<?php echo $nomorTlp; ?>" required>
                            </div>
                        </div>
                    <!-- <div class="form-row d-flex flex-row align-items-center justify-content-between mt-2"> -->
                        
                        <!-- <div class="form-group mt-2 w-50 ms-4">
                            <label for="noTlp">No Telepon</label>
                            <div class="d-flex flex-row">
                                <div class="">
                                    <span class="input-group-text bg-gradient-info px-2 text-white" style="border-radius: 7px; border-bottom-right-radius:0; border-top-right-radius: 0;">+62</span>
                                </div>
                                <input type="text" class="form-control border p-2" style="border-radius: 7px; border-bottom-left-radius:0; border-top-left-radius: 0;" id="noTlp" name="noTlp" placeholder="Masukkan No Telepon" value="<?php echo $rowCust['noTlp']; ?>">
                            </div>
                        </div> -->
                    <!-- </div> -->
                
                <div class="form-group mt-2">
                    <label for="alamat">Alamat</label>
                    <textarea class="form-control border p-2" id="alamat" rows="3" name="alamat" placeholder="Masukkan alamat"><?php echo $alamatCust; ?></textarea>
                </div>
                <div class="form-row d-flex flex-row align-items-center justify-content-between mt-2">
                    <div class="form-group col-md-5 col-12">
                        <label for="kota">Kota</label>
                        <input type="text" class="form-control border p-2" id="kota" name="kota" placeholder="Kota" value="<?php echo $rowCust['kota']; ?>">
                    </div>
                    <div class="form-group col-md-4 col-6">
                        <label for="provinsi">Provinsi</label>
                        <input type="text" class="form-control border p-2" id="provinsi" name="provinsi" placeholder="Provinsi" value="<?php echo $rowCust['provinsi']; ?>">
                    </div>
                    <div class="form-group col-md-2 col-6">
                        <label for="kodePos">Kode Pos</label>
                        <input type="text" class="form-control border p-2" id="kodePos" name="kodePos" placeholder="Kode Pos" value="<?php echo $rowCust['kodepos']; ?>">
                    </div>
                </div>

                <div class="form-group mt-2">
                    <label for="kategori">Kategori</label>
                    <select class="form-control border p-2" id="kategori" name="kategori">
                        <option value="kantor" <?php echo ($rowCust['kategori'] == 'kantor') ? 'selected' : ''; ?>>Kantor</option>
                        <option value="rumah" <?php echo ($rowCust['kategori'] == 'rumah') ? 'selected' : ''; ?>>Rumah</option>
                        <option value="gedung" <?php echo ($rowCust['kategori'] == 'gedung') ? 'selected' : ''; ?>>Gedung</option>
                        <option value="pabrik" <?php echo ($rowCust['kategori'] == 'pabrik') ? 'selected' : ''; ?>>Pabrik</option>
                        <option value="umum" <?php echo ($rowCust['kategori'] == 'umum') ? 'selected' : ''; ?>>Prasarana Umum</option>
                        <option value="sekolah" <?php echo ($rowCust['kategori'] == 'Sekolah') ? 'selected' : ''; ?>>Sekolah</option>
                        <option value="rs" <?php echo ($rowCust['kategori'] == 'rs') ? 'selected' : ''; ?>>Rumah Sakit</option>
                        <option value="hotel" <?php echo ($rowCust['kategori'] == 'hotel') ? 'selected' : ''; ?>>Hotel</option>
                    </select>
                </div>

                <div class="form-group mt-2">
                    <label for="email">Email</label>
                    <input type="mail" class="form-control border p-2" id="email" name="email" placeholder="Email"  value="<?php echo $rowCust['email']; ?>">
                </div>
                
                <!-- <div class="form-group mt-2">
                    <label for="web">Website</label>
                    <input type="text" class="form-control border p-2" id="web" name="web" placeholder="Website"  value="<?php echo $rowCust['website']; ?>">
                </div> -->
                
                <button type="submit" class="btn bg-gradient-primary mt-4">Simpan</button>
            </form>
            <?php
            }
            ?>
        </div>
    </div>
</div>