<?php
// Accurate Online API Configuration
define('ACCURATE_CLIENT_ID', '475a9a8e-49c2-415b-86a8-677b104c2c92');
define('ACCURATE_CLIENT_SECRET', '7b1adb8861d5a6f5709a31dd7ec5440a');
define('ACCURATE_APP_KEY', 'f5b04619-dfdd-44c2-b6c4-28d99ddefec0');
define('ACCURATE_SIGNATURE_SECRET', 'D2wbhPl2cFmp6sCvHV2RplWQD63iPfDsIlylVgYeFazgtFNpOxYGwHMoZzCXZQz6');
define('ACCURATE_API_TOKEN', 'aat.NTA.eyJ2IjoxLCJ1Ijo4Nzc0NDIsImQiOjE3OTA3MzUsImFpIjo1NTA2NiwiYWsiOiJmNWIwNDYxOS1kZmRkLTQ0YzItYjZjNC0yOGQ5OWRkZWZlYzAiLCJhbiI6Imx3eGN1c3QiLCJhcCI6IjQ3NWE5YThlLTQ5YzItNDE1Yi04NmE4LTY3N2IxMDRjMmM5MiIsInQiOjE3NDQxNzQwMDE5MDV9.RNFtt1TdgWzjzGGu0bu41kQzJjALvkDj6RivZ5cLevLSZjAB4FIzsCXF+kepi1DsQBVROFymp11+88ud95AE4aURMjn1z8/77wWEGlD0loHFLsEA8Ztnd+ts4CR2nhkhpWCBYGPumnRCABEqxOVrOcMcPgEx6dibO/TGpA3XYRkq0P8EnSkJg9jVLgOP/rLKEVJ1Itgw7Wc=.N/rLwojif9yjiFiMs67V3yISSGqYztY4c68TD312i1k');

// Database configuration for your website
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_db_name');

// Session configuration
session_start();

// Base URLs
define('BASE_URL', 'http://yourwebsite.com');
define('OAUTH_CALLBACK_URL', BASE_URL . '/auth/callback.php');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>