<?php
// Load environment helper and variables
require_once __DIR__ . '/app/helpers/env_helper.php';
load_env(__DIR__ . '/.env');

// Kontrol tampilan error berdasarkan konfigurasi environment
$isDebug = env('APP_DEBUG', true);
$isProduction = env('APP_ENV') === 'production';

if (!$isDebug || $isProduction) {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Konfigurasi session yang aman sebelum session dimulai
ini_set('session.cookie_httponly', 1);   // Cegah akses cookie via JavaScript (anti-XSS)
ini_set('session.cookie_samesite', 'Strict'); // Cegah pengiriman cookie lintas situs (anti-CSRF)
ini_set('session.use_strict_mode', 1);   // Tolak session ID yang tidak dikenal
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1); // Aktifkan secure cookie saat HTTPS
}

// Start session globally
session_start();

// Load MVC initialization file
require_once 'app/init.php';

// Instantiate the App class to start routing
$app = new App();
