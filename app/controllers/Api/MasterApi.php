<?php
/**
 * API Controller: Master Data (Akun & Reaksi)
 */

class MasterApi {
    private $currentUser = null;
    
    public function setCurrentUser($user) {
        $this->currentUser = $user;
    }
    
    /**
     * GET /api/v1/master/akun
     */
    public function akun() {
        require_once __DIR__ . '/../../models/AkunModel.php';
        $model = new AkunModel();
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $data = $model->getAllAkun();
            $result = [];
            foreach ($data as $row) {
                $result[] = [
                    'kode_akun' => $row['kode_akun'],
                    'nama_akun' => $row['akun'],
                    'aktiva_pasiva' => $row['aktiva_pasiva'],
                    'kategori_neraca' => $row['kategori_neraca'],
                ];
            }
            ApiRouter::sendSuccess($result);
        }
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            ApiRouter::requireRole($this->currentUser, ['admin', 'accountant']);
            $body = ApiRouter::getJsonBody();
            
            $data = [
                'kode_akun' => $body['kode_akun'] ?? '',
                'nama_akun' => $body['nama_akun'] ?? '',
                'aktiva_pasiva' => $body['aktiva_pasiva'] ?? 'A',
                'kategori_neraca' => $body['kategori_neraca'] ?? '',
            ];
            
            if (empty($data['kode_akun']) || empty($data['nama_akun'])) {
                ApiRouter::sendError('kode_akun dan nama_akun diperlukan.', 400);
            }
            
            try {
                $model->addAkun($data);
                ApiRouter::sendSuccess(['message' => 'Akun berhasil ditambahkan.'], 201);
            } catch (Exception $e) {
                ApiRouter::sendError('Gagal menambah akun: ' . $e->getMessage(), 500);
            }
        }
    }
    
    /**
     * PUT/DELETE /api/v1/master/akun_detail/{kode}
     */
    public function akun_detail($kode = null) {
        require_once __DIR__ . '/../../models/AkunModel.php';
        $model = new AkunModel();
        ApiRouter::requireRole($this->currentUser, ['admin', 'accountant']);
        
        if (!$kode) ApiRouter::sendError('Kode akun diperlukan.', 400);
        
        if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'POST') {
            $body = ApiRouter::getJsonBody();
            $data = [
                'kode_akun' => $body['kode_akun'] ?? $kode,
                'nama_akun' => $body['nama_akun'] ?? '',
                'aktiva_pasiva' => $body['aktiva_pasiva'] ?? 'A',
                'kategori_neraca' => $body['kategori_neraca'] ?? '',
            ];
            
            try {
                $model->updateAkun($kode, $data);
                ApiRouter::sendSuccess(['message' => 'Akun berhasil diupdate.']);
            } catch (Exception $e) {
                ApiRouter::sendError('Gagal update akun: ' . $e->getMessage(), 500);
            }
        }
        elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            try {
                $model->deleteAkun($kode);
                ApiRouter::sendSuccess(['message' => 'Akun berhasil dihapus.']);
            } catch (Exception $e) {
                ApiRouter::sendError('Gagal hapus akun: ' . $e->getMessage(), 500);
            }
        }
    }
    
    /**
     * GET/POST /api/v1/master/reaksi
     * GET/PUT/DELETE /api/v1/master/reaksi/{id}
     */
    public function reaksi($id = null) {
        $db = new Database();
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $result = $db->query("SELECT r.id_reaksi, r.nama_reaksi, dr.id_detail_reaksi, dr.kode_akun, a.akun, dr.dk 
                FROM reaksi r 
                LEFT JOIN detail_reaksi dr ON r.id_reaksi = dr.id_reaksi 
                LEFT JOIN akun a ON dr.kode_akun = a.kode_akun
                ORDER BY r.id_reaksi, dr.dk ASC");
            
            $grouped = [];
            while ($row = $result->fetch_assoc()) {
                $rid = $row['id_reaksi'];
                if (!isset($grouped[$rid])) {
                    $grouped[$rid] = [
                        'id_reaksi' => $rid,
                        'nama_reaksi' => $row['nama_reaksi'],
                        'details' => []
                    ];
                }
                if ($row['kode_akun']) {
                    $grouped[$rid]['details'][] = [
                        'id_detail_reaksi' => $row['id_detail_reaksi'],
                        'kode_akun' => $row['kode_akun'],
                        'nama_akun' => $row['akun'],
                        'dk' => $row['dk'],
                    ];
                }
            }
            ApiRouter::sendSuccess(array_values($grouped));
        }
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
            ApiRouter::requireRole($this->currentUser, ['admin', 'accountant']);
            $body = ApiRouter::getJsonBody();
            $id_reaksi = $body['id_reaksi'] ?? $id;
            $nama_reaksi = $body['nama_reaksi'] ?? '';
            $is_edit = $body['is_edit'] ?? false;
            
            if (empty($id_reaksi) || empty($nama_reaksi)) {
                ApiRouter::sendError('id_reaksi dan nama_reaksi diperlukan.', 400);
            }
            
            try {
                if ($is_edit) {
                    $stmt = $db->prepare("UPDATE reaksi SET nama_reaksi = ? WHERE id_reaksi = ?");
                    $stmt->bind_param("ss", $nama_reaksi, $id_reaksi);
                    $stmt->execute();
                    ApiRouter::sendSuccess(['message' => 'Reaksi berhasil diupdate.']);
                } else {
                    $check = $db->prepare("SELECT id_reaksi FROM reaksi WHERE id_reaksi = ?");
                    $check->bind_param("s", $id_reaksi);
                    $check->execute();
                    if ($check->get_result()->num_rows > 0) {
                        ApiRouter::sendError('ID Reaksi sudah ada!', 400);
                    }
                    $stmt = $db->prepare("INSERT INTO reaksi (id_reaksi, nama_reaksi) VALUES (?, ?)");
                    $stmt->bind_param("ss", $id_reaksi, $nama_reaksi);
                    $stmt->execute();
                    ApiRouter::sendSuccess(['message' => 'Reaksi berhasil ditambahkan.']);
                }
            } catch (Exception $e) {
                ApiRouter::sendError('Gagal simpan reaksi: ' . $e->getMessage(), 500);
            }
        }
        elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            ApiRouter::requireRole($this->currentUser, ['admin', 'accountant']);
            if (!$id) ApiRouter::sendError('ID Reaksi diperlukan.', 400);
            
            try {
                $check = $db->prepare("SELECT id_reaksi FROM detail_reaksi WHERE id_reaksi = ? LIMIT 1");
                $check->bind_param("s", $id);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    ApiRouter::sendError('Tidak bisa menghapus! Reaksi ini masih digunakan dalam Mapping Akun.', 400);
                }
                
                $stmt = $db->prepare("DELETE FROM reaksi WHERE id_reaksi = ?");
                $stmt->bind_param("s", $id);
                $stmt->execute();
                ApiRouter::sendSuccess(['message' => 'Reaksi berhasil dihapus.']);
            } catch (Exception $e) {
                ApiRouter::sendError('Gagal hapus reaksi: ' . $e->getMessage(), 500);
            }
        }
    }

    /**
     * POST /api/v1/master/detail_reaksi
     * DELETE /api/v1/master/detail_reaksi/{id}
     */
    public function detail_reaksi($id = null) {
        $db = new Database();
        ApiRouter::requireRole($this->currentUser, ['admin', 'accountant']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
            $body = ApiRouter::getJsonBody();
            $id_reaksi = $body['id_reaksi'] ?? '';
            $kode_akun = $body['kode_akun'] ?? '';
            $dk = $body['dk'] ?? '';
            $is_edit = $body['is_edit'] ?? false;
            $id_detail = $body['id_detail_reaksi'] ?? $id;
            
            if (empty($id_reaksi) || empty($kode_akun) || empty($dk)) {
                ApiRouter::sendError('Data tidak lengkap.', 400);
            }
            
            try {
                if ($is_edit) {
                    $stmt = $db->prepare("UPDATE detail_reaksi SET id_reaksi = ?, kode_akun = ?, dk = ? WHERE id_detail_reaksi = ?");
                    $stmt->bind_param("sssi", $id_reaksi, $kode_akun, $dk, $id_detail);
                    $stmt->execute();
                    ApiRouter::sendSuccess(['message' => 'Mapping berhasil diupdate.']);
                } else {
                    $stmt = $db->prepare("INSERT INTO detail_reaksi (id_reaksi, kode_akun, dk) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $id_reaksi, $kode_akun, $dk);
                    $stmt->execute();
                    ApiRouter::sendSuccess(['message' => 'Mapping berhasil ditambahkan.']);
                }
            } catch (Exception $e) {
                ApiRouter::sendError('Gagal simpan mapping: ' . $e->getMessage(), 500);
            }
        }
        elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            if (!$id) ApiRouter::sendError('ID Detail Reaksi diperlukan.', 400);
            try {
                $stmt = $db->prepare("DELETE FROM detail_reaksi WHERE id_detail_reaksi = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                ApiRouter::sendSuccess(['message' => 'Mapping berhasil dihapus.']);
            } catch (Exception $e) {
                ApiRouter::sendError('Gagal hapus mapping: ' . $e->getMessage(), 500);
            }
        }
    }
}
?>
