#!/bin/bash

# Pastikan berada di root proyek
cd "$(dirname "$0")"

# Ekstrak versi dari pubspec.yaml
VERSION=$(grep 'version: ' loewix-teknisi-mobile-main/pubspec.yaml | sed 's/version: //' | cut -d'+' -f1 | xargs)
echo "=== Memulai proses build untuk versi: $VERSION ==="

echo "=== 1. Membersihkan cache Flutter ==="
cd loewix-teknisi-mobile-main
flutter clean

echo "=== 2. Melakukan compile/build APK Release ==="
flutter build apk --release

if [ $? -eq 0 ]; then
  echo "✓ Build APK Berhasil!"
  
  echo "=== 3. Menyalin APK ke folder server lokal ==="
  cd ..
  # Salin ke latest
  cp loewix-teknisi-mobile-main/build/app/outputs/flutter-apk/app-release.apk staff/download/teknisi-latest.apk
  cp loewix-teknisi-mobile-main/build/app/outputs/flutter-apk/app-release.apk staff/apk/teknisi.apk
  
  # Salin ke versioned (untuk bypass cache)
  cp loewix-teknisi-mobile-main/build/app/outputs/flutter-apk/app-release.apk staff/download/teknisi-v${VERSION}.apk
  cp loewix-teknisi-mobile-main/build/app/outputs/flutter-apk/app-release.apk staff/apk/teknisi-v${VERSION}.apk
  echo "✓ APK berhasil disalin ke server lokal (latest & versioned)"
  
  echo "=== 4. Menyalin APK ke Desktop Anda ==="
  cp loewix-teknisi-mobile-main/build/app/outputs/flutter-apk/app-release.apk ~/Desktop/teknisi-v${VERSION}.apk
  echo "✓ APK berhasil disalin ke Desktop sebagai: teknisi-v${VERSION}.apk"
  
  echo "=== 5. Silakan push commit terbaru Anda ke GitHub ==="
  echo "Jalankan perintah berikut di terminal lokal Anda:"
  echo "git add staff/download/teknisi-latest.apk staff/apk/teknisi.apk staff/download/teknisi-v${VERSION}.apk staff/apk/teknisi-v${VERSION}.apk staff/api_app_version.php build_and_deploy.sh"
  echo "git commit -m 'chore: update APK files to v'${VERSION}"
  echo "git push origin main"
else
  echo "✗ Gagal melakukan build APK. Silakan periksa error Gradle di atas."
fi
