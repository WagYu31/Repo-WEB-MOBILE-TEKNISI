document.addEventListener("DOMContentLoaded", function() {
    var pauseForm = document.getElementById("pauseForm");

    pauseForm.addEventListener("submit", function(event) {
        event.preventDefault(); // Mencegah form untuk dikirim secara langsung

        var formData = new FormData(pauseForm); // Membuat objek FormData dari form

        // Mengirim data menggunakan AJAX
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "get-pause.php", true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    // Respon berhasil dari server
                    console.log(xhr.responseText);
                    // Lakukan tindakan setelah berhasil disimpan, misalnya menutup modal
                    $('#pauseModal').modal('hide');
                    // Setelah menyembunyikan modal, Anda bisa melakukan redirect atau tindakan lainnya
                    // window.location.href = "halaman-lain.php"; // Contoh redirect ke halaman lain
                } else {
                    // Respon gagal dari server
                    console.error("Terjadi kesalahan:", xhr.statusText);
                }
            }
        };
        xhr.send(formData); // Mengirim data form ke server
    });
});
