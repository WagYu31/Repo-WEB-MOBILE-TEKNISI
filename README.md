<p align="center">
  <img src="staff/assets/img/Logo.png" alt="Loewix Logo" width="180"/>
</p>

<h1 align="center">Loewix — Sistem Manajemen Teknisi</h1>

<p align="center">
  <strong>Platform penjadwalan, pelacakan, dan pelaporan kegiatan teknisi secara real-time.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Version-4.0.17-blue?style=for-the-badge" alt="Version"/>
  <img src="https://img.shields.io/badge/Flutter-3.6+-02569B?style=for-the-badge&logo=flutter" alt="Flutter"/>
  <img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php" alt="PHP"/>
  <img src="https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL"/>
  <img src="https://img.shields.io/badge/Platform-Android-3DDC84?style=for-the-badge&logo=android&logoColor=white" alt="Android"/>
</p>

---

## 📖 Deskripsi

**Loewix** adalah sistem manajemen operasional teknisi yang mencakup **web admin panel** dan **aplikasi mobile Android**. Dibangun untuk mengelola seluruh siklus kegiatan teknisi — mulai dari penjadwalan, pelaksanaan di lapangan, pelaporan, hingga penagihan invoice.

### Highlights
- 📅 **Penjadwalan Cerdas** — Auto-generate kode kegiatan berdasarkan riwayat customer
- 📍 **Real-time Tracking** — Pelacakan lokasi teknisi via GPS (Mapbox)
- 📸 **Dokumentasi Lapangan** — Foto sebelum/sesudah, voice recording, catatan
- 📊 **Dashboard Analytics** — Statistik performa teknisi, progres kegiatan, chart
- 🔔 **Notifikasi Push** — Tugas aktif & laporan belum diupload (dual-channel)
- 📱 **Auto-Update** — Sistem update APK otomatis dari server

---

## 🏗️ Arsitektur

```
┌─────────────────────────────────────────────────────────┐
│                      PRODUCTION                         │
├──────────────────┬──────────────────────────────────────┤
│                  │                                      │
│  📱 Mobile App   │  🌐 Web Admin Panel                  │
│  (Flutter/Dart)  │  (PHP + MySQL)                       │
│                  │                                      │
│  ┌────────────┐  │  ┌──────────────┐ ┌───────────────┐  │
│  │ Teknisi App│  │  │ Staff Panel  │ │ Teknisi Web   │  │
│  │ Android    │  │  │ /staff/      │ │ /staff/teknisi│  │
│  └─────┬──────┘  │  └──────┬───────┘ └───────┬───────┘  │
│        │         │         │                 │          │
│        ▼         │         ▼                 ▼          │
│  ┌────────────────────────────────────────────────────┐ │
│  │            REST API (Laravel/PHP)                  │ │
│  │         api-teknisi.id-giti.com/api/v4             │ │
│  └────────────────────┬───────────────────────────────┘ │
│                       │                                 │
│                       ▼                                 │
│              ┌─────────────────┐                        │
│              │  MySQL Database │                        │
│              │  teknisi_api_root│                       │
│              └─────────────────┘                        │
└─────────────────────────────────────────────────────────┘
```

---

## 📂 Struktur Folder

```
jadwal.id-giti.com/
├── staff/                          # 🌐 Web Admin Panel (Super Admin)
│   ├── index.php                   #    Dashboard utama
│   ├── kegiatan-baru.php           #    Tambah kegiatan baru
│   ├── kegiatan-db.php             #    Database kegiatan (CRUD)
│   ├── waiting-list.php            #    Antrian kegiatan
│   ├── lap-kegiatan.php            #    Laporan kegiatan
│   ├── lap-progress.php            #    Progres kegiatan
│   ├── customer-detail.php         #    Riwayat customer
│   ├── data-teknisi.php            #    Manajemen data teknisi
│   ├── inventory.php               #    Stok barang & peminjaman
│   ├── tutorial.php                #    Modul tutorial teknisi
│   ├── mobile/                     #    Versi mobile-responsive
│   ├── sales/                      #    Modul sales
│   ├── teknisi/                    #    Panel web untuk teknisi
│   ├── api_app_version.php         #    API cek versi APK
│   ├── apk/                        #    Storage APK untuk auto-update
│   └── assets/                     #    CSS, JS, Images
│
├── loewix-teknisi-mobile-main/     # 📱 Aplikasi Mobile (Flutter)
│   ├── lib/
│   │   ├── main.dart               #    Entry point aplikasi
│   │   ├── constants/              #    Konfigurasi (API URL, Mapbox, dll)
│   │   ├── page/                   #    Halaman-halaman UI
│   │   │   ├── Auth/               #      Login, Register
│   │   │   ├── Dashboard/          #      Dashboard teknisi
│   │   │   ├── Task/               #      Detail tugas, laporan
│   │   │   ├── Maps/               #      Peta lokasi customer
│   │   │   ├── History/            #      Riwayat kegiatan
│   │   │   ├── Invoice/            #      Invoice / payment
│   │   │   ├── Statistik/          #      Chart performa
│   │   │   ├── Pinjam_Barang/      #      Peminjaman barang
│   │   │   ├── reimburse/          #      Claim reimbursement
│   │   │   └── tutor/              #      Tutorial & panduan
│   │   ├── service/
│   │   │   ├── api/                #      API service classes
│   │   │   ├── model/              #      Data models
│   │   │   ├── provider/           #      State management (Provider)
│   │   │   ├── notification/       #      Push notification (WorkManager)
│   │   │   └── update/             #      Auto-update APK
│   │   ├── widget/                 #    Reusable widgets
│   │   └── utils/                  #    Helper utilities
│   ├── android/                    #    Android native config
│   ├── assets/                     #    Icons, images
│   └── pubspec.yaml                #    Dependencies
│
├── uploads/                        # 📁 Upload storage (foto, dokumen)
├── img/                            # 🖼️ Static images
├── css/                            # 🎨 Stylesheets
├── js/                             # ⚡ JavaScript files
└── .htaccess                       # 🔒 Security configuration
```

---

## 📱 Aplikasi Mobile — Fitur

| Fitur | Deskripsi |
|-------|-----------|
| **Dashboard** | Ringkasan tugas hari ini, statistik performa, chart |
| **Daftar Tugas** | Lihat tugas dijadwalkan, sedang berjalan, selesai |
| **Mulai Tugas** | Slide-to-start dengan validasi lokasi GPS |
| **Laporan** | Upload foto (5 slot), catatan, permasalahan & solusi |
| **Lanjut Nanti** | Pause tugas, lanjutkan di hari berikutnya |
| **Reschedule** | Jadwalkan ulang tugas ke tanggal lain |
| **Peta** | Navigasi ke lokasi customer (Mapbox / OpenStreetMap) |
| **Peminjaman Barang** | Request & kembalikan alat/barang dari gudang |
| **Reimbursement** | Claim biaya operasional dengan bukti foto |
| **Invoice** | Lihat status invoice & payment |
| **Statistik** | Chart performa bulanan, pencapaian target |
| **Tutorial** | Panduan kerja, SOP, video tutorial |
| **Notifikasi** | Push notification tugas aktif & laporan pending |
| **Auto-Update** | Download APK terbaru dari server otomatis |

---

## 🌐 Web Admin — Fitur

| Modul | Deskripsi |
|-------|-----------|
| **Dashboard** | Overview kegiatan harian + chart |
| **Kegiatan** | CRUD kegiatan, assign teknisi, jadwalkan |
| **Waiting List** | Antrian kegiatan belum dijadwalkan |
| **Laporan** | Laporan kegiatan, progres, No Payment tracking |
| **Customer** | Database customer + riwayat kegiatan lengkap |
| **Teknisi** | Manajemen teknisi, detail performa, soft-delete |
| **Stok Barang** | Inventaris alat + tracking peminjaman |
| **Tutorial** | Upload materi training untuk teknisi |
| **Pendapatan** | Invoice, tracking pembayaran, laporan keuangan |
| **Export** | Export laporan ke Excel/PDF |

---

## 🎨 UI/UX — Aplikasi Mobile

### Design System

Aplikasi menggunakan **custom design system** berbasis Material Design dengan sentuhan modern:

#### Color Palette
| Warna | Hex | Penggunaan |
|-------|-----|------------|
| 🔵 Brand Blue | `#1E40AF` | Header, gradient utama, elemen primer |
| 🔵 Brand Cyan | `#0891B2` | Aksen gradient, secondary actions |
| 🔵 Sky Blue | `#0EA5E9` | Loading indicator, link aktif |
| 🟢 Teal | `#14B8A6` | Status berhasil, indikator selesai |
| 🟠 Warm Orange | `#F97316` | Warning, badge urgent, notifikasi |
| 🟣 Indigo | `#6366F1` | Elemen dekoratif, chart accent |
| ⬜ Background | `#F4F6F8` | Base background, clean & minimal |
| ⬛ Text Primary | `#1E293B` | Judul, teks utama |
| 🔘 Text Secondary | `#64748B` | Subtitle, keterangan |

#### Typography
- **Font**: `Poppins` — modern, clean, highly readable
- **Heading**: 18-22px, `FontWeight.w700`
- **Body**: 13-15px, `FontWeight.w500`
- **Caption**: 11-12px, `FontWeight.w400`

### Komponen UI Utama

#### 📊 Dashboard
- **Premium Gradient Header** — gradient 3 warna (`#1E40AF` → `#0369A1` → `#0891B2`) dengan rounded bottom corners
- **Floating Stats Card** — glassmorphism card dengan overlap effect, menampilkan: Kegiatan, Pendapatan, Bonus, Target
- **Progress Bar** — animasi progress target bulanan
- **Motivational Messages** — pesan motivasi harian yang berganti otomatis
- **Greeting Dynamic** — "Selamat Pagi/Siang/Sore/Malam 🔥" berdasarkan jam

#### 📋 Card Task (Kartu Tugas)
- **Gradient accent border** — garis kiri dengan warna sesuai status
- **Status badge** — pill badge dengan warna dinamis:
  - 🔵 Dijadwalkan — blue gradient
  - 🟢 Berjalan — teal/green
  - 🟠 Menunggu Laporan — orange
  - ⚫ Tidak Selesai — dark gray
- **Customer info** — nama, alamat, jenis kegiatan
- **Reorderable** — drag & drop untuk ubah prioritas (handle titik 3)
- **Smooth animation** — fade & slide transition saat data berubah

#### 🗺️ Maps Integration
- **Mapbox GL** — peta premium dengan style `streets-v12`
- **Fallback OSM** — OpenStreetMap sebagai alternatif
- **GPS tracking** — real-time lokasi teknisi
- **Route navigation** — buka di Google Maps/Waze

#### 🎓 Onboarding Coach Mark
- **Tutorial overlay** — 4-step guided tour saat pertama buka
- **Custom tooltip** — rounded card dengan arrow indicator
- **Step counter** — "1/4", "2/4", dst
- **Non-intrusive** — tap anywhere to skip

### Halaman Utama

| Halaman | Deskripsi UI |
|---------|-------------|
| **Splash Screen** | Logo Loewix + loading animation |
| **Login** | Clean form, Poppins font, gradient button |
| **Dashboard** | Header gradient + floating stats + task list |
| **Detail Tugas** | Info customer, peta, slide-to-start, timer |
| **Laporan** | 5 foto slots, text fields, voice recording |
| **History** | Calendar view + scrollable list |
| **Statistik** | FL Chart — bar & line charts, tabel |
| **Profile** | Avatar, info teknisi, ganti password |
| **Pinjam Barang** | List item + status tracking |
| **Reimburse** | Form + upload bukti foto |

### Navigasi

```
┌─────────────────────────────────────────┐
│              ┌──────────┐               │
│              │  Drawer  │               │
│              │  Menu    │               │
│              │          │               │
│  ┌───┐       │ • Dashboard              │
│  │ ≡ │◄──────│ • Tugas Saya             │
│  └───┘       │ • Riwayat                │
│              │ • Pinjam Barang           │
│              │ • Statistik               │
│              │ • Reimburse               │
│              │ • Tutorial                │
│              │ • Profile                 │
│              │ • Logout                  │
│              └──────────┘               │
│                                         │
│  ┌─────┬─────┬─────┬─────┐             │
│  │ 🏠  │ 📋  │ 📊  │ 👤  │ Bottom Nav  │
│  │Home │Task │Stats│Prof │             │
│  └─────┴─────┴─────┴─────┘             │
└─────────────────────────────────────────┘
```

### Special UI Features

- **🔄 Pull-to-Refresh** — BouncingScrollPhysics dengan custom indicator (white on blue)
- **📱 Slide to Act** — geser untuk mulai tugas (mencegah tap tidak sengaja)
- **🔔 Dual Notification Channel** — kanal terpisah untuk Tugas Aktif & Laporan
- **🎞️ Video Player** — preview video tutorial in-app (Chewie player)
- **📸 Image Picker** — ambil foto dari kamera/galeri dengan kompresi
- **🎤 Voice Recording** — rekam suara untuk catatan lapangan
- **✨ Micro-animations** — smooth transitions, fade effects, gradient shifts

---

## 🎨 UI/UX — Web Admin Panel

### Design Framework

Web admin menggunakan **Material Dashboard 3.1** (Soft UI) dengan kustomisasi:

#### Core Framework
| Teknologi | Versi | Kegunaan |
|-----------|-------|----------|
| Bootstrap | 5.3 | Grid system, responsive layout |
| Material Dashboard | 3.1.0 | Admin template, sidebar, cards |
| Material Icons Round | - | Ikon navigasi & UI |
| Font Awesome | 6.5.2 | Extended icon library |
| Roboto | 400/500/700 | Typography utama |

#### Theme & Styling
- **Color scheme**: Dark navy sidebar (`#1a1a2e`) + light content area
- **Card style**: White cards dengan soft shadow, rounded corners
- **Gradient badges**: Status badge menggunakan `bg-gradient-*` classes
- **Responsive**: Mobile-first, sidebar auto-hide di ≤991px

### Layout Components

#### 🔲 Sidebar Navigation
```
┌──────────────────┐
│  🔷 Loewix Logo  │
│──────────────────│
│  OPERASIONAL     │
│  📊 Dashboard    │
│  ➕ Tambah       │
│  ⏳ Waiting List │
│──────────────────│
│  LAPORAN         │
│  📋 Kegiatan     │
│  📑 Laporan      │
│  💰 Pendapatan   │
│  📈 Progress     │
│──────────────────│
│  MANAJEMEN ASET  │
│  📦 Stok Barang  │
│  🔄 Peminjaman   │
│  📚 Tutorial     │
│──────────────────│
│  DATA MASTER     │
│  👷 Teknisi      │
│  👤 Customer     │
└──────────────────┘
```

#### 📱 Mobile Responsive
- **Bottom Navigation** — fixed bottom bar di mobile (radius 20px top)
- **Sidebar hidden** — auto-hide di layar < 992px
- **Samsung Fold support** — khusus breakpoint ≤400px
- **PWA capable** — installable sebagai web app (manifest.json + service worker)

### Web Admin Special Features

| Fitur | Deskripsi |
|-------|-----------|
| **PWA** | Progressive Web App — bisa di-install di homescreen |
| **Responsive** | Otomatis switch sidebar ↔ bottom nav |
| **DataTable** | Server-side pagination, search, filter |
| **Export Excel** | Download laporan dalam format `.xlsx` |
| **Print View** | Halaman cetak laporan yang optimized |
| **Map Integration** | Leaflet maps untuk lokasi customer |
| **Rich Text Editor** | CKEditor 5 untuk konten tutorial |
| **Chart Dashboard** | Chart.js — donut, bar, line charts |

---

## 🛠️ Tech Stack

### Mobile App
| Teknologi | Versi | Kegunaan |
|-----------|-------|----------|
| Flutter | 3.6+ | UI Framework |
| Dart | ^3.6.0 | Programming Language |
| Provider | 6.1.2 | State Management |
| Mapbox GL | - | Premium Maps & Navigation |
| WorkManager | 0.5.2 | Background Task Scheduling |
| flutter_local_notifications | 18.0.1 | Push Notifications (Dual Channel) |
| fl_chart | 0.64.0 | Charts & Graphs (Bar, Line, Pie) |
| Geolocator | 10.0.1 | GPS Location Tracking |
| image_picker | 1.0.7 | Camera & Gallery Access |
| shared_preferences | 2.2.3 | Local Key-Value Storage |
| slide_to_act | 2.0.2 | Swipe-to-Start Gesture |
| table_calendar | 3.1.1 | Calendar Widget |
| flutter_map | 6.1.0 | OpenStreetMap Fallback |
| chewie | 1.7.5 | Video Player |
| flutter_sound | 9.2.13 | Audio Recording |
| tutorial_coach_mark | 1.2.11 | Onboarding Coach Mark |
| iconsax | 0.0.8 | Modern Icon Pack |
| quickalert | 1.1.0 | Alert Dialog |
| webview_flutter | 4.0.0 | In-App Web Content |
| intl | 0.19.0 | Indonesian Locale / Date Format |

### Web Admin
| Teknologi | Versi | Kegunaan |
|-----------|-------|----------|
| PHP | 8.x | Backend & SSR |
| MySQL | 5.7+ | Relational Database |
| Bootstrap | 5.3 | CSS Framework |
| Material Dashboard | 3.1.0 | Admin Template (Soft UI) |
| jQuery | 3.6.0 | DOM Manipulation & AJAX |
| Chart.js | - | Dashboard Analytics Charts |
| Font Awesome | 6.5.2 | Extended Icon Library |
| Roboto (Google Fonts) | - | Typography |
| CKEditor 5 | - | Rich Text Editor (Tutorial) |
| Leaflet.js | - | Interactive Maps |
| PhpSpreadsheet | - | Excel Export (.xlsx) |

### Infrastructure
| Layanan | Kegunaan |
|---------|----------|
| aaPanel | Server Management GUI |
| Ubuntu 24.04 LTS | Production OS |
| Apache/Nginx | Web Server |
| Let's Encrypt | SSL Certificate (Auto-renew) |
| GitHub | Version Control & CI |

---

## ⚙️ Setup & Installation

### Prerequisites
- **Flutter SDK** ≥ 3.6.0
- **PHP** ≥ 8.0
- **MySQL** ≥ 5.7
- **Composer** (PHP dependency manager)
- **Android Studio** / VS Code

### Mobile App Setup

```bash
# Clone repository
git clone https://github.com/WagYu31/Repo-WEB-MOBILE-TEKNISI.git
cd Repo-WEB-MOBILE-TEKNISI/loewix-teknisi-mobile-main

# Install dependencies
flutter pub get

# Konfigurasi API URL
# Edit lib/constants/app_constants.dart
# Ubah apiBaseUrl sesuai environment

# Run di development
flutter run

# Build APK Release
flutter build apk --release
```

### Web Admin Setup

```bash
# Setup di web server (Apache/Nginx)
# 1. Point domain ke folder /staff/
# 2. Import database schema
# 3. Konfigurasi conn.php dengan credentials database
# 4. Set permissions: chmod 755 uploads/
```

---

## 🔐 Security

- ✅ `.htaccess` protection — blokir akses file sensitif (`.sql`, `.env`, `.log`)
- ✅ Security Headers — `X-Frame-Options`, `X-XSS-Protection`, `CSP`
- ✅ PHP execution blocked di `/uploads/`
- ✅ Session-based authentication
- ✅ Prepared statements (SQL injection prevention)
- ✅ Input sanitization (`htmlspecialchars`)
- ✅ Backup file access blocked

---

## 📦 Deployment

### Build APK
```bash
cd loewix-teknisi-mobile-main
flutter build apk --release
# Output: build/app/outputs/flutter-apk/app-release.apk
```

### Auto-Update Server
APK di-upload ke `/staff/apk/` dan versi diatur di `api_app_version.php`:
```php
"version" => "4.0.17",
"url" => "https://jadwal.id-giti.com/staff/apk/teknisi-v4.0.17.apk"
```

Aplikasi mobile otomatis cek versi dan prompt download jika ada update.

---

## 📋 Database Schema (Key Tables)

| Tabel | Deskripsi |
|-------|-----------|
| `kegiatan` | Master data kegiatan/tugas |
| `pelaksanaan_kegiatan` | Record pelaksanaan (absensi, status, foto) |
| `customer` | Data pelanggan |
| `teknisi` | Data teknisi |
| `pendapatan_kegiatan` | Invoice & pembayaran |
| `barang` | Inventaris stok barang |
| `peminjaman_barang` | Tracking peminjaman alat |
| `reimburse` | Klaim reimbursement |
| `kegiatan_reasons` | Alasan reschedule/lanjut nanti |
| `log_kegiatan` | Audit log perubahan |
| `progress_kegiatan` | Tracking progres per kegiatan |

---

## 🔄 Alur Kerja (Workflow)

```mermaid
graph TD
    A[📋 Buat Kegiatan] --> B[📅 Jadwalkan & Assign Teknisi]
    B --> C[📱 Notifikasi ke Teknisi]
    C --> D[🚀 Teknisi Mulai Tugas]
    D --> E{Status?}
    E -->|Selesai| F[📝 Upload Laporan]
    E -->|Lanjut Nanti| G[⏸️ Pause & Lanjut Besok]
    E -->|Reschedule| H[📅 Jadwal Ulang]
    G --> D
    H --> B
    F --> I[✅ Admin Review Laporan]
    I --> J[💰 Generate Invoice]
    J --> K[✅ Selesai / Lunas]
```

---

## 👥 User Roles

| Role | Akses |
|------|-------|
| **Super Admin** | Full access — semua modul web admin |
| **Admin** | Manajemen kegiatan, teknisi, laporan |
| **Teknisi** | Aplikasi mobile — tugas, laporan, absensi |
| **Sales** | Modul sales — customer, kegiatan sales |

---

## 📄 License

Proprietary — **PT. Loewix / id-giti.com**. All rights reserved.

---

<p align="center">
  <sub>Built with ❤️ by <strong>Loewix Dev Team</strong></sub>
</p>
