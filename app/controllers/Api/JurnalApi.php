<?php
/**
 * API Controller: Jurnal Penyesuaian & Pembalik
 */

class JurnalApi {
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
     * POST /api/v1/jurnal/penyesuaian
     */
    public function penyesuaian() {
        ApiRouter::requireRole($this->currentUser, ['admin', 'accountant']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $body = ApiRouter::getJsonBody();
            
            $tanggal = $body['tanggal'] ?? '';
            $deskripsi = $body['deskripsi'] ?? 'Jurnal Penyesuaian';
            $jenisPenyesuaian = $body['jenis_penyesuaian'] ?? '';
            $akunDebet = $body['akun_debet'] ?? '';
            $akunKredit = $body['akun_kredit'] ?? '';
            $nilai = (float)($body['nilai'] ?? 0);
            $referensiId = $body['referensi_id'] ?? '';
            
            $akrualTypes = ['beban_akrual', 'pendapatan_akrual'];
            $isReversing = (isset($body['is_reversing']) && $body['is_reversing'] && in_array($jenisPenyesuaian, $akrualTypes)) ? 1 : 0;
            
            if (empty($tanggal) || empty($jenisPenyesuaian) || empty($akunDebet) || empty($akunKredit) || $nilai <= 0) {
                ApiRouter::sendError('Data tidak lengkap. Pastikan tanggal, jenis, akun debet/kredit, dan nilai terisi.', 400);
            }
            
            $createdBy = $this->currentUser['id'] ?? null;
            $res = $this->model->addJurnalPenyesuaian($tanggal, $deskripsi, $jenisPenyesuaian, $isReversing, $akunDebet, $akunKredit, $nilai, $referensiId, $createdBy);
            
            if ($res['status']) {
                ApiRouter::sendSuccess([
                    'kode' => $res['kode'],
                    'is_reversing' => $isReversing,
                    'message' => 'Jurnal penyesuaian berhasil disimpan.'
                ], 201);
            } else {
                ApiRouter::sendError('Gagal: ' . $res['message'], 500);
            }
        } else {
            // GET — return data needed for the form
            $data = [
                'akun_list' => $this->model->getAkunList(),
            ];
            
            try {
                $data['aset_list'] = $this->model->getAsetList();
            } catch (Exception $e) {
                $data['aset_list'] = [];
            }
            
            try {
                $data['prepaid_list'] = $this->model->getPrepaidList();
            } catch (Exception $e) {
                $data['prepaid_list'] = [];
            }
            
            ApiRouter::sendSuccess($data);
        }
    }
    
    /**
     * GET /api/v1/jurnal/pembalik_pending
     */
    public function pembalik_pending() {
        ApiRouter::requireRole($this->currentUser, ['admin', 'accountant']);
        $list = $this->model->getPembalikPending();
        
        $result = [];
        foreach ($list as $row) {
            $result[] = [
                'kode_transaksi' => $row['kode_transaksi'],
                'tanggal' => $row['tanggal'],
                'deskripsi' => $row['deskripsi'],
                'sub_jenis' => $row['sub_jenis'] ?? '',
                'tgl_pembalik' => $row['tgl_pembalik'] ?? '',
                'total_debet' => (float)($row['total_debet'] ?? 0),
                'total_kredit' => (float)($row['total_kredit'] ?? 0),
            ];
        }
        
        ApiRouter::sendSuccess($result);
    }
    
    /**
     * GET /api/v1/jurnal/pembalik_done
     */
    public function pembalik_done() {
        ApiRouter::requireRole($this->currentUser, ['admin', 'accountant']);
        $list = $this->model->getPembalikDone();
        
        $result = [];
        foreach ($list as $row) {
            $result[] = [
                'kode_transaksi' => $row['kode_transaksi'],
                'tanggal' => $row['tanggal'],
                'deskripsi' => $row['deskripsi'],
                'reversed_at' => $row['reversed_at'] ?? '',
                'reversed_jurnal_kode' => $row['reversed_jurnal_kode'] ?? '',
                'total_debet' => (float)($row['total_debet'] ?? 0),
                'total_kredit' => (float)($row['total_kredit'] ?? 0),
            ];
        }
        
        ApiRouter::sendSuccess($result);
    }
    
    /**
     * POST /api/v1/jurnal/pembalik_proses
     * Body: { "kode_asal": "ADJ-001" }
     */
    public function pembalik_proses() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiRouter::sendError('Method not allowed. Use POST.', 405);
        }
        ApiRouter::requireRole($this->currentUser, ['admin', 'accountant']);
        
        $body = ApiRouter::getJsonBody();
        $kodeAsal = $body['kode_asal'] ?? '';
        
        if (empty($kodeAsal)) ApiRouter::sendError('kode_asal diperlukan.', 400);
        
        $res = $this->model->prosesPembalikManual($kodeAsal);
        
        if ($res['status']) {
            ApiRouter::sendSuccess([
                'kode' => $res['kode'],
                'tanggal' => $res['tanggal'],
                'message' => "Jurnal pembalik berhasil dibuat: {$res['kode']}"
            ]);
        } else {
            ApiRouter::sendError('Gagal: ' . $res['message'], 500);
        }
    }
}
?>
