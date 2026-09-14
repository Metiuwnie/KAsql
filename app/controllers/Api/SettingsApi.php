<?php
require_once __DIR__ . '/../../../core/Database.php';

class SettingsApi {
    private $currentUser = null;
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function setCurrentUser($user) {
        $this->currentUser = $user;
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiRouter::sendError('Metode tidak diizinkan. Gunakan POST.', 405);
        }

        // Only Admin can update settings
        ApiRouter::requireRole($this->currentUser, ['admin']);

        $data = ApiRouter::getJsonBody();
        if (!isset($data['nama_perusahaan']) || trim($data['nama_perusahaan']) === '') {
            ApiRouter::sendError('Nama perusahaan tidak boleh kosong', 400);
        }

        $namaPerusahaan = trim($data['nama_perusahaan']);
        
        try {
            $stmt = $this->db->prepare("UPDATE pengaturan SET nama_perusahaan = ? WHERE id = 1");
            $stmt->bind_param("s", $namaPerusahaan);
            $result = $stmt->execute();
            
            if ($result) {
                ApiRouter::sendSuccess(['message' => 'Nama perusahaan berhasil diperbarui']);
            } else {
                ApiRouter::sendError('Gagal memperbarui nama perusahaan', 500);
            }
        } catch (Exception $e) {
            error_log('[KAsql Settings Error] ' . $e->getMessage());
            $isDebug = function_exists('env') ? env('APP_DEBUG', true) : true;
            $msg = $isDebug ? 'Gagal: ' . $e->getMessage() : 'Gagal memperbarui nama perusahaan.';
            ApiRouter::sendError($msg, 500);
        }
    }
}
