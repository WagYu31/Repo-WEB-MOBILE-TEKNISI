<?php
include "staff/conn.php";

echo "--- MIGRATION: Adding Image Columns to pelaksanaan_sales Table ---\n";

$queries = [
    "ALTER TABLE pelaksanaan_sales ADD COLUMN IF NOT EXISTS image_1 VARCHAR(255) NULL AFTER catatan_visit",
    "ALTER TABLE pelaksanaan_sales ADD COLUMN IF NOT EXISTS image_2 VARCHAR(255) NULL AFTER image_1",
    "ALTER TABLE pelaksanaan_sales ADD COLUMN IF NOT EXISTS image_3 VARCHAR(255) NULL AFTER image_2",
    "ALTER TABLE pelaksanaan_sales ADD COLUMN IF NOT EXISTS image_4 VARCHAR(255) NULL AFTER image_3",
    "ALTER TABLE pelaksanaan_sales ADD COLUMN IF NOT EXISTS image_5 VARCHAR(255) NULL AFTER image_4"
];

foreach ($queries as $q) {
    if (mysqli_query($conn, $q)) {
        echo "SUCCESS: $q\n";
    } else {
        echo "ERROR: " . mysqli_error($conn) . " on query: $q\n";
    }
}

echo "\nMigration finished!\n";
?>
