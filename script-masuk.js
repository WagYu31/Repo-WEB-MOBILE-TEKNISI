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
        
        
        // Fungsi untuk mengaktifkan aliran video dari kamera
        function startCameraForStart() {
            navigator.mediaDevices.getUserMedia({ video: true })
                .then(function (stream) {
                    var videoElement = document.getElementById('startCameraFeed');
                    videoElement.srcObject = stream;
                })
                .catch(function (error) {
                    console.error('Error starting camera:', error);
                });
        }
        
        // Fungsi untuk mengambil gambar dari aliran video
        function captureImageForStart() {
            var videoElement = document.getElementById('startCameraFeed');
            var canvas = document.createElement('canvas');
            canvas.width = videoElement.videoWidth;
            canvas.height = videoElement.videoHeight;
            var context = canvas.getContext('2d');
            context.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
            var capturedImage = canvas.toDataURL('image/jpeg');
        
            // Tampilkan gambar yang diambil pada elemen gambar
            var capturedImageElement = document.getElementById('startCapturedImage');
            capturedImageElement.src = capturedImage;
            capturedImageElement.style.display = 'block';
        
            // Sembunyikan video
            videoElement.style.display = 'none';
        
            // Aktifkan input file dan beri gambar yang diambil ke input file
            var imageUpload = document.getElementById('startImageUpload');
            imageUpload.style.display = 'block';
            imageUpload.value = capturedImage;
        }
        
        // Event listener untuk tombol "Capture" pada modal "Start"
        document.getElementById('startCaptureBtn').addEventListener('click', captureImageForStart);
        
        // Panggil fungsi startCameraForStart saat modal "Start" muncul
        $('#startModal').on('shown.bs.modal', function () {
            startCameraForStart();
        });
        
        // Panggil fungsi startCameraForStart saat modal "Start" tertutup untuk menghentikan aliran video
        $('#startModal').on('hidden.bs.modal', function () {
            stopCameraForStart();
        });
        
        // Fungsi untuk menghentikan aliran video pada modal "Start"
        function stopCameraForStart() {
            var videoElement = document.getElementById('startCameraFeed');
            var stream = videoElement.srcObject;
            if (stream) {
                var tracks = stream.getTracks();
                tracks.forEach(function (track) {
                    track.stop();
                });
                videoElement.srcObject = null;
            }
        }
        


        $(document).ready(function() {
            
            $(".start-btn").click(function() {
                var kegiatanId = $(this).data("id");
                var today = new Date();
                var date = today.getFullYear() + '-' + (today.getMonth() + 1) + '-' + today.getDate();
                var time = today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();
                var dateTime = date + ' ' + time;
                $("#startModal").modal("show");
                
                
            // Panggil fungsi ini saat tombol "Simpan" di dalam modal ditekan
            $("#saveStartBtn").click(function() {
                var keteranganMulai = $("#keteranganMulai").val();
                
                if (keteranganMulai !== "") {
                    // Ambil data gambar yang diambil
                    var capturedImageDataUrl = $("#startCapturedImage").attr("src");
            
                    // Buat objek FormData untuk mengunggah gambar
                    var formData = new FormData();
                    formData.append("image", dataURLtoBlob(capturedImageDataUrl)); // Mengonversi data URL menjadi blob
        
                    if ("geolocation" in navigator) {
                        navigator.geolocation.getCurrentPosition(function(position) {
                            var latitude = position.coords.latitude;
                            var longitude = position.coords.longitude;
                            var location = latitude + "," + longitude;
            
                        // Panggil AJAX untuk mengunggah gambar
                        $.ajax({
                            url: "upload_image.php", // Ganti dengan URL upload gambar di server Anda
                            type: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(imageFilename) {
                                // Gambar telah diunggah, sekarang simpan nama gambar ke database
                                $.ajax({
                                    url: "update_status.php", // Ganti dengan URL untuk menyimpan data kegiatan ke database
                                    type: "POST",
                                    data: {
                                        kegiatanId: kegiatanId,
                                        status: "Clear",
                                        tgl_mulai: dateTime,
                                        lokasi_mulai: location,
                                        ket_start: keteranganMulai,
                                        tgl_selesai: "",
                                        lokasi_selesai: "",
                                        gambar_start: imageFilename // Nama gambar yang telah diunggah
                                    },
                                    success: function(response) {
                                        if (response === "success") {
                                            window.location.href = window.location.href;
                                        } else {
                                            alert("Gagal memperbarui status.");
                                        }
                                    },
                                    error: function() {
                                        alert("Terjadi kesalahan saat menghubungi server.");
                                    }
                                });
                            },
                            error: function() {
                                alert("Terjadi kesalahan saat mengunggah gambar.");
                            }
                        });
                    });
                    } else {
                        alert("Geolocation tidak didukung oleh perangkat Anda.");
                    }
                } else {
                    alert("Harap isi keterangan mulai sebelum menyimpan.");
                }
            });
            });

            
            // Fungsi untuk mengonversi data URL menjadi blob
            function dataURLtoBlob(dataURL) {
                var arr = dataURL.split(",");
                var mime = arr[0].match(/:(.*?);/)[1];
                var bstr = atob(arr[1]);
                var n = bstr.length;
                var u8arr = new Uint8Array(n);
                while (n--) {
                    u8arr[n] = bstr.charCodeAt(n);
                }
                return new Blob([u8arr], { type: mime });
            }

            });

            $(".view-btn").click(function() {
                var kegiatanId = $(this).data("id");
                window.location.href = "detail_kegiatan.php?id_kegiatan=" + kegiatanId;
            });
        });
        });