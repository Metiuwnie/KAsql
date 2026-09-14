<?php
/**
 * API Controller: Transaksi
 * CRUD operations for transactions via JSON.
 */

class TransaksiApi {
    private $currentUser = null;
    private $model;
    
    public function __construct() {
        require_once __DIR__ . '/../../models/JurnalModel.php';
        $this->model = new JurnalModel();
    }
    
    public function setCurrentUser($user) {
        $this->currentUser = $user;
    }
    
    /**
     * GET /api/v1/transaksi?tahun=2026&bulan=6
     */
    public function index() {
        $tahun = $_GET['tahun'] ?? date('Y');
        $bulan = $_GET['bulan'] ?? '';
        
        if (!empty($bulan)) {
            $startDate = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT) . '-01';
            $endDate = date("Y-m-t", strtotime($startDate));
        } else {
            $startDate = $tahun . '-01-01';
            $endDate = $tahun . '-12-31';
        }
        
        // Apply role filter for cashier
        $userId = null;
        if ($this->currentUser && $this->currentUser['role'] === 'cashier') {
            $userId = $this->currentUser['id'];
        }
        
        $list = $this->model->getJurnalUmum($startDate, $endDate, $userId);
        
        // Group by kode_transaksi
        $grouped = [];
        foreach ($list as $row) {
            $kode = $row['kode_transaksi'];
            if (!isset($grouped[$kode])) {
                $grouped[$kode] = [
                    'kode_transaksi' => $kode,
                    'tanggal' => $row['tanggal'],
                    'deskripsi' => $row['deskripsi'],
                    'status_verifikasi' => $row['status_verifikasi'],
                    'jenis_jurnal' => $row['jenis_jurnal'] ?? 'umum',
                    'details' => []
                ];
            }
            $grouped[$kode]['details'][] = [
                'kode_akun' => $row['kode_akun'],
                'nama_akun' => $row['akun'],
                'debit' => (float)$row['Debit'],
                'kredit' => (float)$row['Kredit'],
            ];
        }
        
        ApiRouter::sendSuccess(array_values($grouped));
    }
    
    /**
     * GET /api/v1/transaksi/show/{kode}
     */
    public function show($kode = null) {
        if (!$kode) ApiRouter::sendError('Kode transaksi diperlukan.', 400);
        
        $stmt = $this->model->prepare("SELECT dt.*, t.kode_akun, a.akun, t.dk, t.nilai 
            FROM detil_transaksi dt 
            LEFT JOIN transaksi t ON dt.kode_transaksi = t.kode_transaksi 
            LEFT JOIN akun a ON t.kode_akun = a.kode_akun 
            WHERE dt.kode_transaksi = ?
            ORDER BY t.dk ASC");
        $stmt->bind_param("s", $kode);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $header = null;
        $details = [];
        while ($row = $result->fetch_assoc()) {
            if (!$header) {
                $header = [
                    'kode_transaksi' => $row['kode_transaksi'],
                    'tanggal' => $row['tanggal'],
                    'deskripsi' => $row['deskripsi'],
                    'status_verifikasi' => $row['status_verifikasi'] ?? 'sesuai',
                ];
            }
            $details[] = [
                'kode_akun' => $row['kode_akun'],
                'nama_akun' => $row['akun'],
                'dk' => $row['dk'],
                'nilai' => (float)$row['nilai'],
            ];
        }
        
        if (!$header) ApiRouter::sendError('Transaksi tidak ditemukan.', 404);
        
        $header['details'] = $details;
        ApiRouter::sendSuccess($header);
    }
    
    /**
     * POST /api/v1/transaksi
     * Body: { "tanggal": "2026-01-01", "deskripsi": "...", "details": [{ "kode_akun": "101", "dk": "Debit", "nilai": 1000000 }] }
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiRouter::sendError('Method not allowed. Use POST.', 405);
        }
        
        $body = ApiRouter::getJsonBody();
        $tanggal = $body['tanggal'] ?? date('Y-m-d');
        $deskripsi = $body['deskripsi'] ?? '';
        $details = $body['details'] ?? [];
        $koreksi_dari = $body['koreksi_dari'] ?? '';
        
        if (empty($deskripsi)) ApiRouter::sendError('Deskripsi diperlukan.', 400);
        if (empty($details)) ApiRouter::sendError('Detail transaksi diperlukan (min 1 baris).', 400);
        
        // Validate balance
        $totalDebit = 0;
        $totalKredit = 0;
        foreach ($details as $d) {
            if (empty($d['kode_akun']) || empty($d['dk']) || !isset($d['nilai'])) {
                ApiRouter::sendError('Setiap detail harus memiliki kode_akun, dk, dan nilai.', 400);
            }
            $val = (float)$d['nilai'];
            if ($val <= 0) {
                ApiRouter::sendError('Nilai transaksi harus berupa angka positif lebih dari 0.', 400);
            }
            if (strtolower($d['dk']) === 'debit') $totalDebit += $val;
            else $totalKredit += $val;
        }
        
        if (abs($totalDebit - $totalKredit) > 0.01) {
            ApiRouter::sendError('Transaksi TIDAK SEIMBANG. Total Debit: ' . $totalDebit . ', Total Kredit: ' . $totalKredit, 400);
        }
        
        $closed_msg = is_periode_closed($tanggal, $this->model);
        if (!empty($closed_msg)) {
            ApiRouter::sendError($closed_msg, 400);
        }
        
        $this->model->begin_transaction();
        try {
            // Generate kode
            $newKode = "A001";
            $qMax = $this->model->query("SELECT MAX(CAST(SUBSTRING(kode_transaksi, 2) AS UNSIGNED)) as max_kode FROM detil_transaksi WHERE kode_transaksi LIKE 'A%' FOR UPDATE");
            if ($qMax && $rMax = $qMax->fetch_assoc()) {
                if (!empty($rMax['max_kode'])) {
                    $nextNum = $rMax['max_kode'] + 1;
                    $newKode = "A" . str_pad($nextNum, 3, "0", STR_PAD_LEFT);
                }
            }
            
            $createdBy = $this->currentUser['id'] ?? null;
            
            if (!empty($koreksi_dari)) {
                $stmt = $this->model->prepare("INSERT INTO detil_transaksi (kode_transaksi, tanggal, deskripsi, created_by, status_verifikasi, koreksi_dari_id) VALUES (?, ?, ?, ?, 'pending', ?)");
                $stmt->bind_param("sssis", $newKode, $tanggal, $deskripsi, $createdBy, $koreksi_dari);
            } else {
                $stmt = $this->model->prepare("INSERT INTO detil_transaksi (kode_transaksi, tanggal, deskripsi, created_by, status_verifikasi) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->bind_param("sssi", $newKode, $tanggal, $deskripsi, $createdBy);
            }
            $stmt->execute();
            
            $stmtT = $this->model->prepare("INSERT INTO transaksi (kode_transaksi, kode_akun, dk, nilai) VALUES (?, ?, ?, ?)");
            foreach ($details as $d) {
                $stmtT->bind_param("sssd", $newKode, $d['kode_akun'], $d['dk'], $d['nilai']);
                $stmtT->execute();
            }
            
            if (!empty($koreksi_dari)) {
                $koreksi_text = "dikoreksi oleh : " . $createdBy;
                $stmtUpd = $this->model->prepare("UPDATE detil_transaksi SET status_verifikasi = 'koreksi', pengoreksi_id = ? WHERE kode_transaksi = ?");
                $stmtUpd->bind_param("ss", $koreksi_text, $koreksi_dari);
                $stmtUpd->execute();
            }
            
            $this->model->commit();
            
            ApiRouter::sendSuccess([
                'kode_transaksi' => $newKode,
                'message' => 'Transaksi berhasil disimpan.'
            ], 201);
        } catch (Exception $e) {
            $this->model->rollback();
            ApiRouter::sendError('Gagal simpan: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * GET /api/v1/transaksi/pending?status=pending
     */
    public function pending() {
        ApiRouter::requireRole($this->currentUser, ['admin', 'accountant']);
        
        $filterStatus = $_GET['status'] ?? 'pending';
        $validStatuses = ['pending', 'sesuai', 'koreksi', 'semua'];
        if (!in_array($filterStatus, $validStatuses)) $filterStatus = 'pending';
        
        $sql = "SELECT dt.kode_transaksi, dt.tanggal, dt.deskripsi, dt.status_verifikasi,
                    SUM(IF(t.dk='Debit', t.nilai, 0)) as total_debit,
                    SUM(IF(t.dk='Kredit', t.nilai, 0)) as total_kredit
                FROM detil_transaksi dt
                LEFT JOIN transaksi t ON dt.kode_transaksi = t.kode_transaksi";
        
        if ($filterStatus !== 'semua') {
            $sql .= " WHERE dt.status_verifikasi = ?";
        }
        
        $sql .= " GROUP BY dt.kode_transaksi, dt.tanggal, dt.deskripsi, dt.status_verifikasi
                   ORDER BY dt.tanggal DESC, dt.kode_transaksi DESC";
        
        $stmt = $this->model->prepare($sql);
        if ($filterStatus !== 'semua') {
            $stmt->bind_param("s", $filterStatus);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $row['total_debit'] = (float)$row['total_debit'];
            $row['total_kredit'] = (float)$row['total_kredit'];
            $transactions[] = $row;
        }
        
        // Status counts
        $cntResult = $this->model->query("SELECT 
            SUM(status_verifikasi = 'pending') as cnt_pending,
            SUM(status_verifikasi = 'sesuai') as cnt_sesuai,
            SUM(status_verifikasi = 'koreksi') as cnt_koreksi,
            COUNT(*) as cnt_semua
        FROM detil_transaksi");
        $counts = $cntResult->fetch_assoc();
        
        ApiRouter::sendSuccess([
            'transactions' => $transactions,
            'counts' => [
                'pending' => (int)($counts['cnt_pending'] ?? 0),
                'sesuai' => (int)($counts['cnt_sesuai'] ?? 0),
                'koreksi' => (int)($counts['cnt_koreksi'] ?? 0),
                'semua' => (int)($counts['cnt_semua'] ?? 0),
            ]
        ]);
    }
    
    /**
     * POST /api/v1/transaksi/verify
     * Body: { "kode_transaksi": "A001" }
     */
    public function verify() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiRouter::sendError('Method not allowed. Use POST.', 405);
        }
        ApiRouter::requireRole($this->currentUser, ['admin', 'accountant']);
        
        $body = ApiRouter::getJsonBody();
        $kode = $body['kode_transaksi'] ?? '';
        
        if (empty($kode)) ApiRouter::sendError('kode_transaksi diperlukan.', 400);
        
        $stmt_cek = $this->model->prepare("SELECT tanggal, status_verifikasi FROM detil_transaksi WHERE kode_transaksi = ?");
        $stmt_cek->bind_param("s", $kode);
        $stmt_cek->execute();
        $trx_data = $stmt_cek->get_result()->fetch_assoc();

        if (!$trx_data) {
            ApiRouter::sendError("Transaksi $kode tidak ditemukan.", 404);
        }
        if ($trx_data['status_verifikasi'] !== 'pending') {
            ApiRouter::sendError("Transaksi $kode sudah diproses sebelumnya.", 400);
        }

        $closed_msg = is_periode_closed($trx_data['tanggal'], $this->model);
        if (!empty($closed_msg)) {
            ApiRouter::sendError("Gagal: " . $closed_msg, 400);
        }
        
        $userId = $this->currentUser['id'];
        $pengoreksi = "disetujui oleh : " . $userId;
        $stmt = $this->model->prepare("UPDATE detil_transaksi SET status_verifikasi = 'sesuai', pengoreksi_id = ? WHERE kode_transaksi = ? AND status_verifikasi = 'pending'");
        $stmt->bind_param("ss", $pengoreksi, $kode);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            ApiRouter::sendSuccess(['message' => "Transaksi $kode berhasil diposting."]);
        } else {
            ApiRouter::sendError("Gagal memposting $kode. Mungkin sudah diproses.", 400);
        }
    }
    
    /**
     * GET /api/v1/transaksi/reaksi
     */
    public function reaksi() {
        $result = $this->model->query("SELECT r.id_reaksi, r.nama_reaksi, dr.kode_akun, dr.dk 
            FROM reaksi r 
            LEFT JOIN detail_reaksi dr ON r.id_reaksi = dr.id_reaksi 
            ORDER BY r.id_reaksi, dr.dk ASC");
        
        $grouped = [];
        while ($row = $result->fetch_assoc()) {
            $id = $row['id_reaksi'];
            if (!isset($grouped[$id])) {
                $grouped[$id] = [
                    'id_reaksi' => $id,
                    'nama_reaksi' => $row['nama_reaksi'],
                    'details' => []
                ];
            }
            $grouped[$id]['details'][] = [
                'kode_akun' => $row['kode_akun'],
                'dk' => $row['dk'],
            ];
        }
        
        ApiRouter::sendSuccess(array_values($grouped));
    }
}
?>
