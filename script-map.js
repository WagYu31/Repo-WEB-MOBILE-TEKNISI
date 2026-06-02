
        // Mendapatkan nilai lengkap dari kolom lokasi_mulai
        var lokasiMulai = "<?php echo $row['lokasi_mulai']; ?>";

        // Memisahkan koordinat latitude dan longitude
        var koordinatMulai = lokasiMulai.split(',');

        // Konversi string menjadi float
        var latitudeMulai = parseFloat(koordinatMulai[0]);
        var longitudeMulai = parseFloat(koordinatMulai[1]);

        // Inisialisasi peta dan atur koordinat awal untuk lokasi mulai
        var mapMulai = L.map('map-mulai').setView([latitudeMulai, longitudeMulai], 15);

        // Tambahkan layer peta OSM untuk lokasi mulai
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(mapMulai);

        // Tambahkan marker pada koordinat lokasi mulai
        L.marker([latitudeMulai, longitudeMulai]).addTo(mapMulai)
            .bindPopup('Lokasi Mulai')
            .openPopup();
            
        // Fungsi untuk mengambil alamat dari koordinat
        function getAddressFromCoordinates(lat, lng) {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`)
                .then(response => response.json())
                .then(data => {
                    var address = data.display_name;
                    document.getElementById('locationAddress').innerHTML = address;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('locationAddress').innerHTML = "Tidak dapat mengambil alamat. Pastikan GPS Aktif.";
                });
        }
        
        // Panggil fungsi untuk lokasi mulai
        getAddressFromCoordinates(latitudeMulai, longitudeMulai);

        // Mendapatkan nilai lengkap dari kolom lokasi_selesai
        var lokasiSelesai = "<?php echo $row['lokasi_selesai']; ?>";

        // Memisahkan koordinat latitude dan longitude
        var koordinatSelesai = lokasiSelesai.split(',');

        // Konversi string menjadi float
        var latitudeSelesai = parseFloat(koordinatSelesai[0]);
        var longitudeSelesai = parseFloat(koordinatSelesai[1]);

        // Inisialisasi peta dan atur koordinat awal untuk lokasi selesai
        var mapSelesai = L.map('map-selesai').setView([latitudeSelesai, longitudeSelesai], 15);

        // Tambahkan layer peta OSM untuk lokasi selesai
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(mapSelesai);

        // Tambahkan marker pada koordinat lokasi selesai
        L.marker([latitudeSelesai, longitudeSelesai]).addTo(mapSelesai)
            .bindPopup('Lokasi Selesai')
            .openPopup();
            
        // Fungsi untuk mengambil alamat dari koordinat
        function getAddressFromCoordinates(lat, lng) {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`)
                .then(response => response.json())
                .then(data => {
                    var address = data.display_name;
                    document.getElementById('locationAddress2').innerHTML = address;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('locationAddress2').innerHTML = "Tidak dapat mengambil alamat. Pastikan GPS Aktif.";
                });
        }
        
        // Panggil fungsi untuk lokasi mulai
        getAddressFromCoordinates(latitudeSelesai, longitudeSelesai);
