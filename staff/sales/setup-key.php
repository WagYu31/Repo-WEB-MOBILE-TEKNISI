<?php
header('Content-Type: text/plain');

$privateKey = "-----BEGIN PRIVATE KEY-----\n"
    . "MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC/IDLbNt4BlS/s\n"
    . "jRv+ScyAK05CyEBCEgW71N9XA9QH6j6935sLNAZJ+Czi+nGueNiGbyr1pSUO7We+\n"
    . "1d2B6CroznuhsB6Osva5KOYdQpYM/PwUWMORG2BpTlsmbDcXBdwOeArpEZhozC9I\n"
    . "ydrbMRiBuAP9qqMawad++mwEKjMEwsnt16VE23cwjDqOCs/d9cgtoI9qbUzP0w0M\n"
    . "GzHcuA3/4I9C/PMXZy1mV0TnIDe5oFXQWGP+5iG68XWCXp6sII+wFQlhX2r2lvz/\n"
    . "pYkiGEDUz8uOZihKIGqU9g0nImfhOqFvn4//QOGs9GzxMXdXOsiUpIQXebvjMnZd\n"
    . "/0vXGbN5AgMBAAECggEAMM3pDp85G5Bb93yk/E8eAFCOoHEAL57ohek6yr54gzjm\n"
    . "opeZwMedH4BW/fLT2qiTKejzQFzWVLR8vHdiI80EQASR/1y4wNmkNO3jrO8W3+Qt\n"
    . "/ogYEFK8UMeocOxLOP7PDYbLym4qy//vxxd8YmJsDpvNsRXEpo1y1vLdaEbaVkly\n"
    . "RawOIvDP48gApIWP0NRF1tSYzxG4bSgxUcyTDuPYwCebzJMcU32PRmbp6GQmk30M\n"
    . "hP8NzkVxQbHOlg1WQK23bNfKoKbaFeMfmoKStyAL+LvyIBydMAgesBBy+fYDNpDX\n"
    . "tDYwVIq7/vRztEs7XPOmUr9mch/DEkecof/dz+w7DQKBgQDiQiJZLy5PTi0nyzP0\n"
    . "0yqy//G7AdyznGjJVPpU2ZIy4mGZs3fuh9ALDhH/c+26Ud3mrG1/UpV8ANnjHGn9\n"
    . "1UltXJDcigflj4GDh3gXwJ4VF7qYzYTn4ytWxBoebvJnHcd6BtM+p5U7k2+PEARZ\n"
    . "6SQltQfcWanxriEtFxsYdmgUbwKBgQDYP8+73KoJqb4UBdKsn82BOLiMjAQOA0Pc\n"
    . "baBmIUj0EKfw++MNPZVZFD2eL1KB8Fbg+2LjXiw3aoeqgkmN2uUFvgsTHqKaq315\n"
    . "I3VeBN8CUSGTbm54s5t4YJsGh/SSBIIgOARIPqSmVXo9hadQnKc05S8zQosCo2li\n"
    . "dlIVfXO6lwKBgEtgLuM5NZtT9vUf3BI+2yXA4H4lc6oefEY+Whs0VGFBS7SRtm2v\n"
    . "rx5PtK0+qL7+kQdNADl/gK9L9UqU57aZfJnDUbs2/MR8V3BDDD4VfFSYkCBhr1o1\n"
    . "MvX35J+o5HZ31EGRzoQ7/hpX1r15X4m4gsNRGOsOsLAEC6di4DL5F1lfAoGAe92Z\n"
    . "PM85qo1K7icjtHNYgDMgKoks7WXbYhB9NuLL6dj8iWGOfZAP0tVwMgKDLSCgcwAr\n"
    . "ndXcEtr1Tdkxom1ONqYtgxpPeqd+e4Ft6J998adRU+iDgME0YuYwEYGpRoa3pZ1EO\n"
    . "yt7u6sO9YC4FV3xTnk3EioUMIPe/LoH9pIeWWYMCgYEAr9FkRvURswJILrxBQLE8\n"
    . "e4FPuAUD7St+Y5jhubOuiOmC8so4lxVJ8f1QRXP8zdODhdsbiOsye3TxpG/JWJEy\n"
    . "DwstiHa36SzMmH2E1yroczPEjbljCz0wd9GMtQufWau6RHw08Q83X8IxytkeHqHi\n"
    . "GGN6vN2BaR/rb4djQ7J4EmY=\n"
    . "-----END PRIVATE KEY-----\n";

$keyData = [
    "type" => "service_account",
    "project_id" => "loewix-sales",
    "private_key_id" => "a9d24f74ac3d119e60be097a0b13d5953bef90ee",
    "private_key" => $privateKey,
    "client_email" => "sheets-sync@loewix-sales.iam.gserviceaccount.com",
    "client_id" => "110662467474583422390",
    "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
    "token_uri" => "https://oauth2.googleapis.com/token",
    "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
    "client_x509_cert_url" => "https://www.googleapis.com/robot/v1/metadata/x509/sheets-sync%40loewix-sales.iam.gserviceaccount.com",
    "universe_domain" => "googleapis.com"
];

$targetDir = __DIR__ . '/../config';
$targetFile = $targetDir . '/google-sheets-key.json';

if (!file_exists($targetDir)) {
    mkdir($targetDir, 0755, true);
}

// Convert JSON array with correct formatting
$jsonString = json_encode($keyData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

if (file_put_contents($targetFile, $jsonString) !== false) {
    chmod($targetFile, 0644);
    echo "Setup Google Sheets Key: SUCCESS!\n";
    echo "Path: " . $targetFile . "\n";
    echo "Size: " . filesize($targetFile) . " bytes\n";
} else {
    echo "Setup Google Sheets Key: FAILED!\n";
}
