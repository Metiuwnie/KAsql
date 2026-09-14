<?php
/**
 * API Controller: Aset Tetap & Prepaid Expense
 */

class AsetApi {
    private $currentUser = null;
    
    public function setCurrentUser($user) {
        $this->currentUser = $user;
    }
    
    /**
     * GET/POST /api/v1/aset/prepaid
     */
    public function prepaid() {
        ApiRouter::requireRole($this->currentUser, ['admin', 'accountant']);
        $db = new Database();
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $data = [];
            $res = $db->query("SELECT * FROM manajemen_prepaid ORDER BY id DESC");
            if ($res) {
                while($row = $res->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            ApiRouter::sendSuccess($data);
        }
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            ApiRouter::requireRole($this->currentUser, ['admin', 'accountant']);
            $body = ApiRouter::getJsonBody();
            
            $nama = $body['nama_prepaid'] ?? '';
            $total_nilai = floatval($body['total_nilai'] ?? 0);
            $lama_bulan = intval($body['lama_bulan'] ?? 0);
            $akun_prepaid = $body['akun_prepaid'] ?? '';
            $akun_beban = $body['akun_beban'] ?? '';
            $tanggal_mulai = $body['tanggal_mulai'] ?? date('Y-m-d');
            
            if (empty($nama) || $total_nilai <= 0 || $lama_bulan <= 0 || empty($akun_prepaid) || empty($akun_beban)) {
                ApiRouter::sendError('Harap lengkapi semua data dengan benar.', 400);
            }
            
            $nilai_per_bulan = $total_nilai / $lama_bulan;
            
            $stmt = $db->prepare("INSERT INTO manajemen_prepaid (nama_prepaid, total_nilai, lama_bulan, nilai_per_bulan, akun_prepaid, akun_beban, tanggal_mulai) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sdidsss", $nama, $total_nilai, $lama_bulan, $nilai_per_bulan, $akun_prepaid, $akun_beban, $tanggal_mulai);
            
            if ($stmt->execute()) {
                ApiRouter::sendSuccess(['message' => 'Data Prepaid berhasil ditambahkan.']);
            } else {
                ApiRouter::sendError('Gagal menambah prepaid: ' . $stmt->error, 500);
            }
        }
    }
    
    /**
     * GET/POST /api/v1/aset/tetap
     */
    public function tetap() {
        ApiRouter::requireRole($this->currentUser, ['admin', 'accountant']);
        $db = new Database();
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $data = [];
            $res = $db->query("SELECT * FROM aset_tetap ORDER BY id DESC");
            if ($res) {
                while($row = $res->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            ApiRouter::sendSuccess($data);
        }
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            ApiRouter::requireRole($this->currentUser, ['admin', 'accountant']);
            $body = ApiRouter::getJsonBody();
            
            $nama = $body['nama_aset'] ?? '';
            $harga = floatval($body['harga_perolehan'] ?? 0);
            $residu = floatval($body['nilai_residu'] ?? 0);
            $umur_tahun = intval($body['umur_tahun'] ?? 0);
            $umur_bulan_input = intval($body['umur_bulan'] ?? 0);
            
            $akun_aset = $body['akun_aset'] ?? '';
            $akun_akumulasi = $body['akun_akumulasi'] ?? '';
            $akun_beban = $body['akun_beban'] ?? '';
            
            $umur_total_bulan = ($umur_tahun * 12) + $umur_bulan_input;
            
            if (empty($nama) || $harga <= 0 || $umur_total_bulan <= 0 || empty($akun_aset) || empty($akun_akumulasi) || empty($akun_beban)) {
                ApiRouter::sendError('Harap lengkapi semua data dengan benar. Umur aset tidak boleh 0.', 400);
            }
            
            $nilai_penyusutan = ($harga - $residu) / $umur_total_bulan;
            
            $stmt = $db->prepare("INSERT INTO aset_tetap (nama_aset, harga_perolehan, nilai_residu, umur_ekonomis_bulan, nilai_penyusutan_per_bulan, akun_aset, akun_akumulasi, akun_beban) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sddidsss", $nama, $harga, $residu, $umur_total_bulan, $nilai_penyusutan, $akun_aset, $akun_akumulasi, $akun_beban);
            
            if ($stmt->execute()) {
                ApiRouter::sendSuccess(['message' => 'Data Aset Tetap berhasil ditambahkan.']);
            } else {
                ApiRouter::sendError('Gagal menambah aset tetap: ' . $stmt->error, 500);
            }
        }
    }
}
