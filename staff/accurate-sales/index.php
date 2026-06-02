<?php
require_once 'config.php';

echo '<h1>Accurate Online Integration</h1>';
echo '<p>Choose authentication method:</p>';
echo '<ul>';
echo '<li><a href="auth/oauth.php">Authenticate with OAuth 2.0</a></li>';
echo '<li><a href="auth/token_auth.php">Authenticate with API Token</a></li>';
echo '</ul>';

// Display any messages
if (isset($_SESSION['message'])) {
    echo '<div style="color:green;">' . htmlspecialchars($_SESSION['message']) . '</div>';
    unset($_SESSION['message']);
}

if (isset($_SESSION['error'])) {
    echo '<div style="color:red;">' . htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}
?>