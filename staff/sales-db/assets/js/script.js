function handleAjaxError(xhr, status, error) {
  console.error("AJAX Error:", status, error);
  alert("Terjadi kesalahan saat memproses permintaan. Silakan coba lagi.");
}

function refreshItemsList() {
  window.location.reload();
}

document.addEventListener("DOMContentLoaded", function () {});
