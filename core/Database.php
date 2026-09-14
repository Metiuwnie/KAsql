<?php
class Database {
    private $conn;

    public function __construct() {
        // Karena config.php saat ini di root, kita bisa menggunakan global conn
        // atau menginisialisasi ulang koneksi di sini untuk lebih aman di MVC
        
        require_once __DIR__ . '/../app/helpers/env_helper.php';
        load_env(__DIR__ . '/../.env');

        $host = env('DB_HOST', 'localhost');
        $user = env('DB_USER', 'root');
        $pass = env('DB_PASS', '');
        $db   = env('DB_NAME', 'komputer_akuntan_fresh');

        try {
            $this->conn = new mysqli($host, $user, $pass, $db);
        } catch (Exception $e) {
            error_log('[KAsql] Koneksi Database (MVC) Gagal: ' . $e->getMessage());
            die("Terjadi kesalahan internal pada server. Silakan hubungi administrator.");
        }
    }

    public function query($sql) {
        return $this->conn->query($sql);
    }

    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }

    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }
    
    public function getConnection() {
        return $this->conn;
    }

    public function begin_transaction() {
        return $this->conn->begin_transaction();
    }

    public function commit() {
        return $this->conn->commit();
    }

    public function rollback() {
        return $this->conn->rollback();
    }
}
