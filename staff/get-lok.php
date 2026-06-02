
                    <?php

                    // Fungsi untuk mendapatkan alamat dari koordinat latitude dan longitude
                    ${"getAddressFromCoordinates$rowNumber"} = function ($latitude, $longitude) 
                    {
                        // Membuat User-Agent untuk permintaan HTTP
                        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.71 Safari/537.36';

                        // URL untuk meminta alamat dari koordinat
                        $url = "https://nominatim.openstreetmap.org/reverse?lat=$latitude&lon=$longitude&format=json";

                        // Menginisialisasi opsi untuk permintaan HTTP
                        $options = array(
                            'http' => array(
                                'header' => "User-Agent: $userAgent\r\n"
                            )
                        );

                        // Membuat konteks stream dengan opsi
                        $context = stream_context_create($options);

                        // Membuat permintaan HTTP dengan konteks stream
                        $response = file_get_contents($url, false, $context);

                        // Mengecek apakah permintaan berhasil
                        if ($response === FALSE) {
                            return "Gagal mengambil alamat";
                        } else {
                            // Mendekode respons JSON
                            $json = json_decode($response);
                            // Mendapatkan alamat dari JSON
                            if (isset($json->display_name)) {
                                return $json->display_name;
                            } else {
                                return "Alamat tidak ditemukan";
                            }
                        }
                    }

                    ?>