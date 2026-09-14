# KAsql Mobile (Flutter)

Aplikasi seluler pendamping resmi untuk sistem akuntansi **KAsql**. Aplikasi ini dirancang khusus untuk memfasilitasi aktivitas operasional akuntan dan kasir secara fleksibel melalui perangkat seluler, dengan dukungan penuh untuk lingkungan tanpa internet (**Offline-First**).

## Akun dan Password untuk login untuk flutter mobile version

- Admin
  - email: admin@kasql.local
  - password: adminsigma123
- Akuntan
  - email: akuntan1@kasql.local
  - password: akuntan1123
- Kasir
  - email: kasir1@kasql.local
  - password: kasir1123

## ✨ Fitur Unggulan Mobile

- **Offline-First Architecture**: Input transaksi harian, aset tetap, dan prepaid expense dapat dilakukan tanpa koneksi internet. Data akan disimpan aman di SQLite lokal.
- **Smart Background Sync**: Aplikasi akan mendeteksi ketika koneksi internet kembali aktif dan otomatis mensinkronisasikan (*push*) data pending ke server MySQL utama.
- **Role-Based Access Control**: Tata letak dan akses fitur menyesuaikan jabatan pengguna (Admin, Akuntan, atau Kasir).
- **REST API Integration**: Berkomunikasi 100% dengan *server* utama (PHP) melalui antarmuka **RESTful API** berbasis JSON. Menjamin lalu lintas data yang tersentralisasi dan aman.
- **Integrasi Firebase**: Sistem *login* aman dan *password* tersandi dikelola langsung oleh Firebase Authentication.
- **Premium Aesthetics**: Menggunakan skema desain editorial dengan perpaduan warna Navy/Teal, *micro-animations*, dan gaya tipografi modern.

## 🛠️ Persyaratan Sistem

- Flutter SDK (Versi stabil terbaru)
- Android Studio / VS Code
- Akses ke jaringan lokal/publik server API KAsql (PHP)

## 🚀 Panduan Instalasi & Eksekusi

1. **Konfigurasi API Endpoint**:
   Buka file `lib/config/api_config.dart` dan pastikan IP/Domain *baseUrl* mengarah ke server PHP Anda:
   ```dart
   static const String baseUrl = 'http://10.0.2.2/KAsql/api/v1'; // Contoh untuk Android Emulator ke Localhost
   ```
2. **Konfigurasi Firebase**:
   Pastikan file `google-services.json` (Android) atau `GoogleService-Info.plist` (iOS) dari proyek Firebase Anda telah diletakkan di direktori yang tepat, karena aplikasi membutuhkan konfigurasi ini untuk fitur *Login*.
3. **Instal Dependensi**:
   Buka terminal di dalam direktori `kasql_flutter` dan jalankan:
   ```bash
   flutter pub get
   ```
4. **Jalankan Aplikasi**:
   ```bash
   flutter run
   ```

## 📂 Struktur Direktori Flutter

- `/lib/config/` - Tema aplikasi (*colors*, *typography*) dan URL API.
- `/lib/models/` - Struktur data dinamis (saat ini difasilitasi lewat `Map<String, dynamic>`).
- `/lib/screens/` - Halaman/antarmuka utama aplikasi (Dashboard, Jurnal, Login, dll).
- `/lib/services/` - Logika *backend* klien (API calls, SQLite handler, Auth provider).
- `/lib/utils/` - Fungsi-fungsi pembantu umum (format Rupiah, format tanggal).
- `/lib/widgets/` - Komponen UI *reusable* (AppDrawer, OfflineBanner).
