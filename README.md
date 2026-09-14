# KAsql - Enterprise Monorepo Accounting System

Sistem Informasi Akuntansi komprehensif berbasis arsitektur monorepo yang mengintegrasikan aplikasi web terpusat (PHP MVC) sebagai backend dan pusat komando dengan aplikasi seluler lintas platform (Flutter) berkemampuan offline-first.

Proyek ini dirancang untuk memfasilitasi seluruh siklus akuntansi organisasi, mulai dari pencatatan transaksi kasir, jurnal umum, jurnal penyesuaian, jurnal pembalik, buku besar, neraca saldo, hingga penutupan periode akuntansi (closing) dan penyusunan laporan keuangan otomatis.

---

## Daftar Isi

- [Arsitektur Sistem](#arsitektur-sistem)
- [Fitur Utama](#fitur-utama)
- [Teknologi yang Digunakan](#teknologi-yang-digunakan)
- [Struktur Monorepo](#struktur-monorepo)
- [Persyaratan Sistem](#persyaratan-sistem)
- [Panduan Instalasi Backend](#panduan-instalasi-backend)
- [Panduan Pengaturan Aplikasi Mobile](#panduan-pengaturan-aplikasi-mobile)
- [Akun Pengguna Awal](#akun-pengguna-awal)
- [Standar Keamanan dan Audit](#standar-keamanan-dan-audit)
- [Lisensi](#lisensi)

---

## Arsitektur Sistem

KAsql menerapkan pola arsitektur Monorepo dengan pembagian tanggung jawab yang terstruktur:

1. **Central Server (Web & REST API)**: Menggunakan PHP native berarsitektur Model-View-Controller (MVC) yang melayani antarmuka web administratif sekaligus menyediakan endpoint RESTful API berbasis format JSON untuk aplikasi seluler.
2. **Mobile Client (Flutter)**: Aplikasi seluler yang beroperasi secara mandiri dengan basis data lokal SQLite (offline-first caching) serta melakukan sinkronisasi data dua arah dengan Central Server saat konektivitas tersedia.

---

## Fitur Utama

### Siklus Akuntansi Lengkap
- **Pencatatan Transaksi**: Pencatatan transaksi operasional tunai, kredit, dan bertahap dengan mekanisme reaksi jurnal otomatis.
- **Jurnal Umum**: Pencatatan entri debit dan kredit dengan validasi keseimbangan (balance verification).
- **Jurnal Penyesuaian dan Pembalik**: Pengelolaan beban dibayar di muka (prepaid expense), amortisasi, penyusutan aset tetap, dan pembalikan transaksi otomatis pada awal periode berjalan.
- **Buku Besar dan Neraca Saldo**: Agregasi saldo akun secara dinamis berdasarkan periode akuntansi yang dipilih.
- **Laporan Keuangan Otomatis**: Penyusunan Neraca (Balance Sheet) dan Laporan Laba Rugi (Income Statement) secara real-time.
- **Tutup Buku (Closing Period)**: Mekanisme penguncian transaksi per periode akuntansi dan pembentukan snapshot saldo akhir yang terisolasi.

### Operasional dan Analisis
- **Sinkronisasi Offline-First**: Transaksi tetap dapat diinput melalui perangkat mobile tanpa koneksi internet dan disinkronisasi ke server pusat saat kembali online.
- **Verifikasi Transaksi Bertingkat**: Alur kerja peninjauan dan verifikasi transaksi oleh akuntan sebelum diposting ke jurnal buku besar.
- **Analisis Finansial Cerdas**: Integrasi modul analisis kesehatan keuangan bisnis berbasis Google Gemini AI API.
- **Activity Logging**: Pencatatan jejak audit terstruktur atas setiap aktivitas penting pengguna.

---

## Teknologi yang Digunakan

### Backend dan REST API
- **Bahasa Pemrograman**: PHP 8.1+
- **Arsitektur Perangkat Lunak**: Model-View-Controller (MVC) Native
- **Basis Data**: MySQL 8.x / MariaDB 10.x
- **Format Pertukaran Data**: RESTful API (JSON Payload)
- **Manajemen Konfigurasi**: Environment Variables (`.env`)

### Aplikasi Seluler (Mobile)
- **Framework**: Flutter SDK 3.x (Dart)
- **Manajemen Status**: Provider
- **Penyimpanan Lokal**: SQLite via `sqflite` (Offline-first cache engine)
- **Layanan Autentikasi**: Firebase Authentication & REST Bearer Token Verification

---

## Struktur Monorepo

```text
KAsql/
├── app/                      # Lapisan aplikasi PHP MVC
│   ├── controllers/          # Kontroler tampilan web dan kontroler REST API
│   │   └── Api/              # Endpoint API untuk aplikasi mobile
│   ├── helpers/              # Modul helper (Environment loader, Sanitasi, dsb.)
│   ├── models/               # Abstraksi data dan query basis data
│   └── views/                # Templating antarmuka pengguna web
├── core/                     # Komponen inti framework (Routing, Database, Base Controller)
├── database_clean.sql        # Skema awal dan data master bersih (Clean Seed Data)
├── kasql_flutter/            # Kode sumber aplikasi mobile Flutter
│   ├── lib/                  # Logika aplikasi Flutter (Screens, Widgets, Services)
│   └── pubspec.yaml          # Manajemen dependensi Dart/Flutter
├── migrations/               # Skrip migrasi skema basis data
├── public/                   # Aset publik web (CSS, JavaScript, Gambar)
├── .env.example              # Template variabel konfigurasi lingkungan
├── .gitignore                # Aturan pengecualian berkas Git
├── .htaccess                 # Konfigurasi keamanan dan rewrite Apache
├── auth.php                  # Middleware autentikasi sesi dan kontrol hak akses
├── config.php                # Loader konfigurasi basis data terpusat
├── index.php                 # Titik masuk utama aplikasi web
└── README.md                 # Dokumentasi teknis proyek
```

---

## Persyaratan Sistem

- PHP versi 8.1 atau lebih tinggi dengan ekstensi aktif: `pdo_mysql`, `mysqli`, `curl`, `json`, `mbstring`
- MySQL Server versi 8.0 atau MariaDB versi 10.4 atau lebih tinggi
- Apache Web Server dengan modul `mod_rewrite` aktif
- Lingkungan lokal yang didukung: Laragon (Sangat disarankan), XAMPP, atau Docker
- Flutter SDK versi 3.22 atau lebih tinggi (Untuk kompilasi aplikasi seluler)

---

## Panduan Instalasi Backend

### 1. Kloning Repositori
Letakkan kode sumber pada direktori publik server web lokal:
```bash
git clone https://github.com/Metiuwnie/KAsql.git
cd KAsql
```

### 2. Inisialisasi Basis Data
1. Buka konsol MySQL atau panel administrasi (phpMyAdmin / DBeaver).
2. Buat basis data baru bernama `komputer_akuntan`:
   ```sql
   CREATE DATABASE komputer_akuntan;
   ```
3. Impor berkas `database_clean.sql` ke dalam basis data tersebut.

### 3. Konfigurasi Lingkungan (.env)
Salin berkas template konfigurasi:
```bash
cp .env.example .env
```
Buka berkas `.env` menggunakan editor teks dan sesuaikan parameter berikut:
```ini
APP_ENV=production
APP_DEBUG=false
BASE_URL=http://localhost/KAsql

DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASS=
DB_NAME=komputer_akuntan

GEMINI_API_KEY=kunci_api_gemini_anda
```

### 4. Menjalankan Aplikasi
Pastikan layanan Apache dan MySQL telah berjalan, kemudian akses aplikasi melalui peramban:
```
http://localhost/KAsql
```

---

## Panduan Pengaturan Aplikasi Mobile

1. Buka direktori aplikasi mobile:
   ```bash
   cd kasql_flutter
   ```
2. Pasang seluruh dependensi proyek:
   ```bash
   flutter pub get
   ```
3. Sesuaikan alamat base URL endpoint server backend pada berkas `lib/config/api_config.dart` sesuai IP jaringan lokal mesin server Anda.
4. Jalankan aplikasi:
   ```bash
   flutter run
   ```

---

## Akun Pengguna Awal

Basis data awal telah dilengkapi dengan tiga akun pengujian standar:

| Peran | Username | Kata Sandi Bawaan | Lingkup Wewenang |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `adminsigma123` | Akses penuh konfigurasi sistem, manajemen master data, dan kontrol pengguna |
| **Akuntan** | `akuntan1` | `akuntan1123` | Pembuatan jurnal umum, penyesuaian, verifikasi transaksi kasir, closing, dan laporan |
| **Kasir** | `kasir1` | `kasir1123` | Input transaksi operasional harian kasir |

> **Perhatian**: Untuk implementasi pada server produksi, segera perbarui kata sandi bawaan melalui menu Manajemen Pengguna.

---

## Standar Keamanan dan Audit

Sistem telah diaudit dan diperkuat dengan standar perlindungan berikut:

- **Isolasi Variabel Lingkungan**: Kredensial sensitif dipisahkan ke dalam berkas `.env` yang diblokir dari akses web langsung dan diabaikan dari repositori publik.
- **Pencegahan SQL Injection**: Penggunaan prepared statement dan parameterized queries pada seluruh interaksi basis data.
- **Mitigasi Cross-Site Scripting (XSS)**: Sanitasi input dan proteksi encoding pada seluruh output tampilan.
- **Role-Based Access Control (RBAC)**: Pengendalian hak akses berbasis peran yang ditegakkan secara ketat pada level rute pengontrol maupun tampilan.
- **Pemisahan Wewenang (Segregation of Duties)**: Pengguna kasir dibatasi hanya pada modul transaksi dan tidak dapat mengakses pembukuan atau laporan keuangan.
- **Keamanan Sesi**: Dilengkapi mekanisme rotasi sesi, proteksi session fixation, dan pembatasan durasi idle timeout.
- **Enkripsi Kredensial**: Penyimpanan kata sandi menggunakan algoritma hashing satu arah Bcrypt.
- **Pencegahan Kebocoran Debug**: Pelaporan pesan kesalahan teknis disembunyikan pada lingkungan produksi untuk mencegah eksposur informasi internal sistem.

---

## Lisensi

Hak Cipta Terpelihara. Dikembangkan untuk keperluan manajemen akuntansi profesional.
