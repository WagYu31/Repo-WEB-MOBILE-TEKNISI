<?php
include "conn.php";

echo "<h2>Migrasi Database: Menambahkan Fitur Wilayah &amp; Keamanan</h2>";

// 1. Buat Tabel wilayah
$sqlWilayah = "CREATE TABLE IF NOT EXISTS wilayah (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
)";

if ($conn->query($sqlWilayah)) {
    echo "<p style='color: green;'>✔ Tabel 'wilayah' berhasil dibuat/sudah ada.</p>";
} else {
    echo "<p style='color: red;'>✘ Gagal membuat tabel 'wilayah': " . $conn->error . "</p>";
}

// 2. Tambah kolom password di tabel sales jika belum ada
$checkSalesPass = $conn->query("SHOW COLUMNS FROM sales LIKE 'password'");
if ($checkSalesPass->num_rows == 0) {
    $sqlAddPassSales = "ALTER TABLE sales ADD COLUMN password VARCHAR(255) NULL";
    if ($conn->query($sqlAddPassSales)) {
        echo "<p style='color: green;'>✔ Kolom 'password' berhasil ditambahkan ke tabel 'sales'.</p>";
    } else {
        echo "<p style='color: red;'>✘ Gagal menambah kolom 'password' ke tabel 'sales': " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ Kolom 'password' sudah ada di tabel 'sales'.</p>";
}

// 3. Tambah kolom id_wilayah di tabel sales jika belum ada
$checkSalesCol = $conn->query("SHOW COLUMNS FROM sales LIKE 'id_wilayah'");
if ($checkSalesCol->num_rows == 0) {
    $sqlAddColSales = "ALTER TABLE sales ADD COLUMN id_wilayah INT NULL";
    if ($conn->query($sqlAddColSales)) {
        echo "<p style='color: green;'>✔ Kolom 'id_wilayah' berhasil ditambahkan ke tabel 'sales'.</p>";
    } else {
        echo "<p style='color: red;'>✘ Gagal menambah kolom ke tabel 'sales': " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ Kolom 'id_wilayah' sudah ada di tabel 'sales'.</p>";
}

// 4. Tambah kolom id_wilayah di tabel sales_customer jika belum ada
$checkCustCol = $conn->query("SHOW COLUMNS FROM sales_customer LIKE 'id_wilayah'");
if ($checkCustCol->num_rows == 0) {
    $sqlAddColCust = "ALTER TABLE sales_customer ADD COLUMN id_wilayah INT NULL";
    if ($conn->query($sqlAddColCust)) {
        echo "<p style='color: green;'>✔ Kolom 'id_wilayah' berhasil ditambahkan ke tabel 'sales_customer'.</p>";
    } else {
        echo "<p style='color: red;'>✘ Gagal menambah kolom ke tabel 'sales_customer': " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ Kolom 'id_wilayah' sudah ada di tabel 'sales_customer'.</p>";
}

// 5. Masukkan data wilayah awal jika kosong
$checkData = $conn->query("SELECT COUNT(*) AS total FROM wilayah WHERE deleted_at IS NULL");
$row = $checkData->fetch_assoc();
if ($row['total'] == 0) {
    $defaultWilayah = [
        'Jabodetabek',
        'Jawa Barat',
        'Jawa Tengah',
        'Jawa Timur',
        'Sumatera',
        'Kalimantan',
        'Sulawesi',
        'Bali & Nusa Tenggara'
    ];

    foreach ($defaultWilayah as $w) {
        $stmt = $conn->prepare("INSERT INTO wilayah (nama) VALUES (?)");
        $stmt->bind_param("s", $w);
        $stmt->execute();
        $stmt->close();
    }
    echo "<p style='color: green;'>✔ Data wilayah default berhasil diinput.</p>";
} else {
    echo "<p style='color: blue;'>ℹ Tabel 'wilayah' sudah memiliki data.</p>";
}

// 6. Update data sales & customer lama yang id_wilayah-nya masih NULL ke Jabodetabek (ID 1)
$conn->query("UPDATE sales SET id_wilayah = 1 WHERE id_wilayah IS NULL");
$conn->query("UPDATE sales_customer SET id_wilayah = 1 WHERE id_wilayah IS NULL");
echo "<p style='color: green;'>✔ Mengatur wilayah default (Jabodetabek) untuk sales &amp; customer lama.</p>";

echo "<hr><p style='color: darkgreen; font-weight: bold;'>Proses migrasi selesai dengan sukses!</p>";
?>
