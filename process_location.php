<?php
// Check if latitude and longitude are set
if (isset($_POST['latitude']) && isset($_POST['longitude'])) {
    // Get latitude and longitude values
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    // Combine latitude and longitude into one variable (e.g., as a string)
    $location = $latitude . ',' . $longitude;

    // You can use $location as needed, such as storing it in a database or processing further
    echo $location;
} else {
    echo 'Error: Latitude and longitude not set.';
}
?>
