<div class="col-lg-10 d-flex align-items-center">
                                    <h6 class="mb-0 mx-1">Laporan Pembayaran Iuran dan Tagihan Lain Bulan <?php echo $getMonth; ?></h6>
                                </div>
                                <div class="col-2">
                                    <select class="form-select border p-2" id="bulanTahun" name="bulanTahun">
                                        <option value=""></option>
                                        <?php
                                        $bulanIni = date('m');
                                        $tahunIni = date('Y');

                                        for ($i = 0; $i < 12; $i++) {
                                            $bulanTahun = date('m-Y', strtotime("+$i months"));
                                            $namaBulanTahun = date('F Y', strtotime("+$i months"));
                                            echo "<option value=\"$bulanTahun\"";
                                            if ($bulanTahun == "$bulanIni-$tahunIni") {
                                                echo " selected";
                                            }
                                            echo ">$namaBulanTahun</option>";
                                        }
                                        ?>
                                    </select>
                                </div>