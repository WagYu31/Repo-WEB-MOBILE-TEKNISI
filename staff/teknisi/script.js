document.addEventListener("DOMContentLoaded", function(event) {
        var map = L.map('map').setView([0, 0], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        function addMarker(lat, lng) {
            var marker = L.marker([lat, lng]).addTo(map);
            var popupContent = "Lokasi Anda";
            getAddressFromCoordinates(lat, lng);
            marker.bindPopup(popupContent).openPopup();
        }

        function getDeviceLocation() {
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        var latitude = position.coords.latitude;
                        var longitude = position.coords.longitude;
                        addMarker(latitude, longitude);
                        map.setView([latitude, longitude], 15);
                    },
                    function(error) {
                        if (error.code === error.PERMISSION_DENIED) {
                            alert("Anda harus mengaktifkan GPS untuk menggunakan fitur ini.");
                        } else {
                            setTimeout(function() {
                                getDeviceLocation();
                            }, 5000);
                        }
                    }
                );
            } else {
                alert("Geolokasi tidak didukung oleh perangkat Anda.");
            }
        }

        function getAddressFromCoordinates(lat, lng) {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`)
                .then(response => response.json())
                .then(data => {
                    var address = data.display_name;
                    document.getElementById('locationAddress').innerHTML = "Lokasi Anda : " + address;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('locationAddress').innerHTML = "Tidak dapat mengambil alamat. Pastikan GPS Aktif.";
                });
        }

        document.getElementById("refreshLocationBtn").addEventListener("click", function() {
            getDeviceLocation();
        });

        getDeviceLocation();
        
});
