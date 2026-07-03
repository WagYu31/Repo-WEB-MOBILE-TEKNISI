<?php
header('Content-Type: text/plain');
echo "--- Loewix Git Pull Deployment Script ---\n";
echo "Current directory: " . getcwd() . "\n";
echo "Executing: git pull origin main\n\n";

$output = [];
$retval = 0;
exec("git pull origin main 2>&1", $output, $retval);

echo implode("\n", $output);
echo "\n\nExit code: " . $retval . "\n";
if ($retval === 0) {
    echo "SUCCESS: Git pull completed successfully!";
} else {
    echo "ERROR: Git pull failed. Please check permissions or git config.";
}
?>
