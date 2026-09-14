<?php
/**
 * CSRF Protection Helper
 * 
 * Menyediakan fungsi untuk membuat dan memvalidasi token CSRF
 * guna mencegah serangan Cross-Site Request Forgery.
 * 
 * Usage:
 *   - Di form: <?= csrf_field() ?>
 *   - Di controller: csrf_verify() sebelum memproses POST
 */

/**
 * Generate atau ambil token CSRF yang sudah ada dari session.
 * Token berlaku selama session aktif.
 * 
 * @return string Token CSRF
 */
function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['_csrf_token'];
}

/**
 * Menghasilkan HTML hidden input berisi token CSRF.
 * Gunakan di dalam tag <form>.
 * 
 * @return string HTML hidden input
 */
function csrf_field(): string {
    return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Memverifikasi token CSRF dari request POST.
 * Jika token tidak valid, menghentikan eksekusi dengan HTTP 403.
 * 
 * @return bool True jika valid
 */
function csrf_verify(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $token = $_POST['_csrf_token'] ?? '';
    $session_token = $_SESSION['_csrf_token'] ?? '';
    
    if (empty($token) || empty($session_token) || !hash_equals($session_token, $token)) {
        http_response_code(403);
        die('Permintaan tidak valid (CSRF token mismatch). Silakan muat ulang halaman dan coba lagi.');
    }
    
    return true;
}

/**
 * Memverifikasi CSRF secara lunak (mengembalikan boolean, bukan die()).
 * Berguna untuk alur yang membutuhkan penanganan error kustom.
 * 
 * @return bool True jika valid, false jika tidak
 */
function csrf_check(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $token = $_POST['_csrf_token'] ?? '';
    $session_token = $_SESSION['_csrf_token'] ?? '';
    
    if (empty($token) || empty($session_token) || !hash_equals($session_token, $token)) {
        return false;
    }
    
    return true;
}
