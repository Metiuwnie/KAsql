<?php
class Controller {
    public function view($view, $data = []) {
        // Extract data array to variables so view can access them
        if (!empty($data)) {
            extract($data);
        }
        
        $viewFile = __DIR__ . '/../app/views/' . $view . '.php';
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            error_log('[KAsql] View tidak ditemukan: ' . $viewFile);
            http_response_code(404);
            die('Halaman tidak ditemukan.');
        }
    }

    public function model($model) {
        $modelFile = __DIR__ . '/../app/models/' . $model . '.php';
        if (file_exists($modelFile)) {
            require_once $modelFile;
            return new $model();
        } else {
            error_log('[KAsql] Model tidak ditemukan: ' . $modelFile);
            die('Terjadi kesalahan internal pada server.');
        }
    }

    // Helper for redirecting
    public function redirect($url) {
        // Assume BASE_URL is defined in config
        if(defined('BASE_URL')) {
            header('Location: ' . BASE_URL . '/' . $url);
        } else {
            // Fallback for relative
            header('Location: ' . $url);
        }
        exit;
    }
}
