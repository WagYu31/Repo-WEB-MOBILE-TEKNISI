<?php
// ════════════════════════════════════════════════════════
// FILE INI DINONAKTIFKAN KARENA ALASAN KEAMANAN
// Auto-login tanpa password = celah keamanan kritis
// ════════════════════════════════════════════════════════
http_response_code(403);
echo "Access Denied";
exit;
?>
