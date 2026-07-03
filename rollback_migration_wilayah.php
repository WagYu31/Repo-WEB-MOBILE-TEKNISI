<?php
include "conn.php";

echo "<h2>Rollback Database: Menghapus Fitur Wilayah</h2>";

// 1. Hapus kolom id_wilayah di tabel sales
$checkSalesCol = $conn->query("SHOW COLUMNS FROM sales LIKE 'id_wilayah'");
if ($checkSalesCol->num_rows > 0) {
    if ($conn->query("ALTER TABLE sales DROP COLUMN id_wilayah")) {
        echo "<p style='color: green;'>✔ Kolom 'id_wilayah' berhasil dihapus dari tabel 'sales'.</p>";
    } else {
        echo "<p style='color: red;'>✘ Gagal menghapus kolom dari tabel 'sales': " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ Kolom 'id_wilayah' memang tidak ada di tabel 'sales'.</p>";
}

// 2. Hapus kolom id_wilayah di tabel sales_customer
$checkCustCol = $conn->query("SHOW COLUMNS FROM sales_customer LIKE 'id_wilayah'");
if ($checkCustCol->num_rows > 0) {
    if ($conn->query("ALTER TABLE sales_customer DROP COLUMN id_wilayah")) {
        echo "<p style='color: green;'>✔ Kolom 'id_wilayah' berhasil dihapus dari tabel 'sales_customer'.</p>";
    } else {
        echo "<p style='color: red;'>✘ Gagal menghapus kolom dari tabel 'sales_customer': " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ Kolom 'id_wilayah' memang tidak ada di tabel 'sales_customer'.</p>";
}

// 3. Drop tabel wilayah
$conn->query("DROP TABLE IF EXISTS wilayah");
echo "<p style='color: green;'>✔ Tabel 'wilayah' berhasil dihapus.</p>";

echo "<hr><p style='color: darkred; font-weight: bold;'>Proses rollback selesai. Database kembali ke keadaan semula.</p>";
?>
