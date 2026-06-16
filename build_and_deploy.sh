#!/bin/bash

# Pastikan berada di root proyek
cd "$(dirname "$0")"

echo "=== 1. Membersihkan cache Flutter ==="
cd loewix-teknisi-mobile-main
flutter clean

echo "=== 2. Melakukan compile/build APK Release ==="
flutter build apk --release

if [ $? -eq 0 ]; then
  echo "✓ Build APK Berhasil!"
  
  echo "=== 3. Menyalin APK ke folder server lokal ==="
  cd ..
  cp loewix-teknisi-mobile-main/build/app/outputs/flutter-apk/app-release.apk staff/download/teknisi-latest.apk
  echo "✓ APK berhasil disalin ke staff/download/teknisi-latest.apk"
  
  echo "=== 4. Menyalin APK ke Desktop Anda ==="
  cp loewix-teknisi-mobile-main/build/app/outputs/flutter-apk/app-release.apk ~/Desktop/teknisi-v4.0.20.apk
  echo "✓ APK berhasil disalin ke Desktop sebagai: teknisi-v4.0.20.apk"
  
  echo "=== 5. Silakan push commit terbaru Anda ke GitHub ==="
  echo "Jalankan perintah berikut di terminal lokal Anda:"
  echo "git add staff/download/teknisi-latest.apk"
  echo "git commit -m 'chore: update APK file to v4.0.20'"
  echo "git push origin main"
else
  echo "✗ Gagal melakukan build APK. Silakan periksa error Gradle di atas."
fi
