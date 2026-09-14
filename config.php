<?php
/**
 * Konfigurasi Database Terpusat
 * Ubah nama database di sini untuk mengganti database di seluruh aplikasi.
 */

require_once __DIR__ . '/app/helpers/env_helper.php';
load_env(__DIR__ . '/.env');

$host = env('DB_HOST', 'localhost');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '');
$db   = env('DB_NAME', 'komputer_akuntan_fresh');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $db);
} catch (Exception $e) {
    // Catat error ke log server, JANGAN tampilkan ke pengguna
    error_log('[KAsql] Koneksi database gagal: ' . $e->getMessage());
    die("Terjadi kesalahan internal pada server. Silakan hubungi administrator.");
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

// Load Pengaturan dari Database
try {
    $res_sett = $conn->query("SELECT nama_perusahaan FROM pengaturan WHERE id = 1");
    if ($res_sett && $res_sett->num_rows > 0) {
        $row_sett = $res_sett->fetch_assoc();
        $_SESSION['nama_perusahaan'] = $row_sett['nama_perusahaan'];
    } else {
        // Fallback jika tabel kosong
        $_SESSION['nama_perusahaan'] = $_SESSION['nama_perusahaan'] ?? 'USAHA';
    }
} catch (Exception $e) {
    $_SESSION['nama_perusahaan'] = $_SESSION['nama_perusahaan'] ?? 'USAHA';
}
?>
