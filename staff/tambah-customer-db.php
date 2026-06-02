<div class="container mt-n4">
    <div class="card p-4 col-12 col-md-11">
        <div class="card-header pb-0 p-3">
            <div class="row">
                <div class="col-12 d-flex align-items-center">
                    <h6 class="mb-0 mx-1 ms-n3 lead font-weight-bold text-uppercase">Input Data Customer Baru</h6>
                </div>
            </div>
        </div>
        <div class="card-body pb-0 p-0 mt-3">
            <!-- Form input data customer baru -->
            <form method="POST">
                <div class="form-group">
                    <label for="nama">Nama</label>
                    <input type="text" class="form-control border p-2" id="nama" name="nama" placeholder="Masukkan nama" required>
                </div>
                <div class="form-group mt-2">
                    <label for="no_whatsapp">No WhatsApp</label>
                    <div class="d-flex flex-row">
                        <div class="">
                            <span class="input-group-text bg-gradient-info px-2 text-white" style="border-radius: 7px; border-bottom-right-radius:0; border-top-right-radius: 0;">+62</span>
                        </div>
                        <input type="text" class="form-control border px-2 py-3" style="border-radius: 7px; border-bottom-left-radius:0; border-top-left-radius: 0;" id="no_whatsapp" name="no_tlp" placeholder="Masukkan No WhatsApp" required>
                    </div>
                </div>

                <div class="form-group mt-2">
                    <label for="alamat">Alamat</label>
                    <textarea class="form-control border p-2" id="alamat" rows="3" name="alamat" placeholder="Masukkan alamat"></textarea>
                </div>
                <div class="form-row d-flex flex-row align-items-center justify-content-between mt-2">
                    <div class="form-group col-md-5 col-12">
                        <label for="kota">Kota</label>
                        <input type="text" class="form-control border p-2" id="kota" name="kota" placeholder="Kota">
                    </div>
                    <div class="form-group col-md-4 col-6">
                        <label for="provinsi">Provinsi</label>
                        <input type="text" class="form-control border p-2" id="provinsi" name="provinsi" placeholder="Provinsi">
                    </div>
                    <div class="form-group col-md-2 col-6">
                        <label for="kodePos">Kode Pos</label>
                        <input type="text" class="form-control border p-2" id="kodePos" name="kodePos" placeholder="Kode Pos">
                    </div>
                </div>

                <div class="form-group mt-2">
                    <label for="kategori">Kategori</label>
                    <select class="form-control border p-2" id="kategori" name="kategori">
                        <option value="kantor">Kantor</option>
                        <option value="rumah">Rumah</option>
                        <option value="gedung">Gedung</option>
                        <option value="pabrik">Pabrik</option>
                        <option value="umum">Prasarana Umum</option>
                        <option value="Sekolah">Sekolah</option>
                        <option value="rs">Rumah Sakit</option>
                        <option value="hotel">Hotel</option>
                    </select>
                </div>

                <div class="form-group mt-2">
                    <label for="email">Email</label>
                    <input type="mail" class="form-control border p-2" id="email" name="email" placeholder="Email">
                </div>

                <div class="form-group mt-2">
                    <input type="checkbox" id="duplikat" name="duplikat">
                    <label for="duplikat">Duplikat Data</label>
                    <p class="text-xs text-danger">* Centang jika ingin input data customer dengan nama atau alamat berbeda tapi memiliki nomor telepon yang sama.</p>
                </div>
                <button type="submit" class="btn bg-gradient-primary">Simpan</button>
            </form>
        </div>
    </div>
</div>