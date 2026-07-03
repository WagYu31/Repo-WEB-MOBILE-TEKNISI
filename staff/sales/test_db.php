<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "conn.php";
echo "Active database connection: " . mysqli_get_host_info($conn) . "\n";
echo "Database name: ";
if ($result = mysqli_query($conn, "SELECT DATABASE()")) {
    $row = mysqli_fetch_row($result);
    echo $row[0] . "\n";
} else {
    echo "ERROR getting database name\n";
}

echo "Tables in database:\n";
$result = mysqli_query($conn, "SHOW TABLES");
if ($result) {
    while ($row = mysqli_fetch_row($result)) {
        echo "- " . $row[0] . "\n";
    }
} else {
    echo "ERROR showing tables: " . mysqli_error($conn) . "\n";
}
?>
