<?php

// Hilangkan karakter selain angka dari nomor telepon
        $no_tlp = preg_replace("/[^0-9]/", "", $no_tlp);
    
        // Lakukan validasi data (jika diperlukan)
    
        // Ubah format nomor telepon
        if (substr($no_tlp, 0, 1) == "0") {
            // Jika angka pertama adalah 0, biarkan seperti itu
        } elseif (substr($no_tlp, 0, 2) == "62") {
            // Jika angka pertama adalah 62, ganti dengan 0
            $no_tlp = "0" . substr($no_tlp, 2);
        } elseif (substr($no_tlp, 0, 3) == "+62") {
            // Jika angka pertama adalah +62, ganti dengan 0
            $no_tlp = "0" . substr($no_tlp, 3);
        } elseif (substr($no_tlp, 0, 5) == "+6262") {
            // Jika angka pertama adalah +6262, ganti dengan 0
            $no_tlp = "0" . substr($no_tlp, 5);
        } elseif (substr($no_tlp, 0, 4) == "6262") {
            // Jika angka pertama adalah 6162, ganti dengan 0
            $no_tlp = "0" . substr($no_tlp, 4);
        } elseif (substr($no_tlp, 0, 6) == "+62+62") {
            // Jika angka pertama adalah +62+62, ganti dengan 0
            $no_tlp = "0" . substr($no_tlp, 6);
        } else {
            // Jika angka pertama bukan 0, 62, atau +62, tambahkan 0 di depannya
            $no_tlp = "0" . $no_tlp;
        }

        ?>