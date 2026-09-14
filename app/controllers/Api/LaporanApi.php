<?php
/**
 * API Controller: Laporan Keuangan
 * All financial reports returned as JSON.
 */

class LaporanApi {
    private $currentUser = null;
    private $model;
    
    public function __construct() {
        require_once __DIR__ . '/../../models/LaporanModel.php';
        $this->model = new LaporanModel();
    }
    
    public function setCurrentUser($user) {
        $this->currentUser = $user;
    }
    
    private function getPeriodDates() {
        $tahun = $_GET['tahun'] ?? date('Y');
        $bulan = $_GET['bulan'] ?? '';
        
        if (!empty($bulan)) {
            $startDate = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT) . '-01';
            $endDate = date("Y-m-t", strtotime($startDate));
        } else {
            $startDate = $tahun . '-01-01';
            $endDate = $tahun . '-12-31';
        }
        
        return [$startDate, $endDate, $tahun, $bulan];
    }
    
    /**
     * GET /api/v1/laporan/jurnal_umum?tahun=2026&bulan=6
     */
    public function jurnal_umum() {
        ApiRouter::requireRole($this->currentUser, ['admin', 'accountant', 'cashier']);
        
        require_once __DIR__ . '/../../models/JurnalModel.php';
        $jurnalModel = new JurnalModel();
        
        [$startDate, $endDate] = $this->getPeriodDates();
        
        $userId = null;
        if ($this->currentUser['role'] === 'cashier') {
            $userId = $this->currentUser['id'] ?? $this->currentUser['user_id'] ?? null;
        }
        
        $list = $jurnalModel->getJurnalUmum($startDate, $endDate, $userId);
        
        ApiRouter::sendSuccess($list);
    }
    
    /**
     * GET /api/v1/laporan/buku_besar?akun[]=101&akun[]=102&tahun=2026&bulan=6
     */
    public function buku_besar() {
        ApiRouter::requireRole($this->currentUser, ['admin', 'accountant']);
        
        $akunPilihan = $_GET['akun'] ?? [];
        if (!is_array($akunPilihan)) $akunPilihan = [$akunPilihan];
        
        if (empty($akunPilihan)) {
            // Default: all akun
            $semuaAkun = $this->model->getDaftarAkun();
            $akunPilihan = array_keys($semuaAkun);
        }
        
        [$startDate, $endDate] = $this->getPeriodDates();
        
        $saldoAwals = $this->model->getSaldoAwalBukuBesar($akunPilihan, $startDate);
        $transactions = $this->model->getTransaksiBukuBesar($akunPilihan, $startDate, $endDate);
        
        // Get account names
        $semuaAkun = $this->model->getDaftarAkun();
        
        $result = [];
        foreach ($akunPilihan as $kode) {
            $saldoAwal = $saldoAwals[$kode] ?? 0;
            $trxList = $transactions[$kode] ?? [];
            
            // Calculate running balance
            $saldo = $saldoAwal;
            $formattedTrx = [];
            foreach ($trxList as $trx) {
                $isDebitNormal = in_array(substr($kode, 0, 1), ['1', '5']);
                if (strtolower($trx['dk']) === 'debit') {
                    $saldo += $isDebitNormal ? $trx['nilai'] : -$trx['nilai'];
                } else {
                    $saldo += $isDebitNormal ? -$trx['nilai'] : $trx['nilai'];
                }
                
                $formattedTrx[] = [
                    'tanggal' => $trx['tanggal'],
                    'kode_transaksi' => $trx['kode_transaksi'],
                    'deskripsi' => $trx['deskripsi'],
                    'dk' => $trx['dk'],
                    'nilai' => (float)$trx['nilai'],
                    'saldo' => (float)$saldo,
                ];
            }
            
            $result[] = [
                'kode_akun' => $kode,
                'nama_akun' => $semuaAkun[$kode] ?? $kode,
                'saldo_awal' => (float)$saldoAwal,
                'transactions' => $formattedTrx,
                'saldo_akhir' => (float)$saldo,
            ];
        }
        
        ApiRouter::sendSuccess($result);
    }
    
    /**
     * GET /api/v1/laporan/neraca_saldo?tahun=2026&bulan=6
     */
    public function neraca_saldo() {
        ApiRouter::requireRole($this->currentUser, ['admin', 'accountant']);
        
        [$startDate, $endDate] = $this->getPeriodDates();
        
        $semuaAkun = $this->model->getDaftarAkun();
        $akunList = array_keys($semuaAkun);
        $saldoAwals = $this->model->getSaldoAwalBukuBesar($akunList, $startDate);
        $mutasi = $this->model->getNeracaSaldo($startDate, $endDate);
        
        $data = [];
        foreach ($mutasi as $row) {
            $kode = $row['kode_akun'];
            $saldoAwal = $saldoAwals[$kode] ?? 0;
            $isDebitNormal = in_array(substr($kode, 0, 1), ['1', '5']);
            
            $mutasiDebet = (float)$row['mutasi_debet'];
            $mutasiKredit = (float)$row['mutasi_kredit'];
            
            if ($isDebitNormal) {
                $saldoAkhir = $saldoAwal + $mutasiDebet - $mutasiKredit;
            } else {
                $saldoAkhir = $saldoAwal + $mutasiKredit - $mutasiDebet;
            }
            
            $data[] = [
                'kode_akun' => $kode,
                'nama_akun' => $row['akun'],
                'kategori' => $row['kategori_neraca'],
                'saldo_awal' => (float)$saldoAwal,
                'mutasi_debet' => $mutasiDebet,
                'mutasi_kredit' => $mutasiKredit,
                'saldo_akhir_debet' => $saldoAkhir > 0 && $isDebitNormal ? $saldoAkhir : ($saldoAkhir < 0 && !$isDebitNormal ? abs($saldoAkhir) : 0),
                'saldo_akhir_kredit' => $saldoAkhir > 0 && !$isDebitNormal ? $saldoAkhir : ($saldoAkhir < 0 && $isDebitNormal ? abs($saldoAkhir) : 0),
                'saldo_akhir' => (float)$saldoAkhir,
            ];
        }
        
        ApiRouter::sendSuccess($data);
    }
    
    /**
     * GET /api/v1/laporan/laba_rugi?tahun=2026&bulan=6
     */
    public function laba_rugi() {
        ApiRouter::requireRole($this->currentUser, ['admin', 'accountant']);
        
        [$startDate, $endDate] = $this->getPeriodDates();
        $rawData = $this->model->getLabaRugi($startDate, $endDate);
        
        $pendapatan = [];
        $beban = [];
        $totalPendapatan = 0;
        $totalBeban = 0;
        
        foreach ($rawData as $row) {
            $item = [
                'kode_akun' => $row['kode_akun'],
                'nama_akun' => $row['akun'],
                'nilai' => (float)$row['total_nilai'],
            ];
            
            if (substr($row['kode_akun'], 0, 1) === '4') {
                $pendapatan[] = $item;
                $totalPendapatan += $item['nilai'];
            } else {
                $beban[] = $item;
                $totalBeban += $item['nilai'];
            }
        }
        
        ApiRouter::sendSuccess([
            'pendapatan' => $pendapatan,
            'beban' => $beban,
            'total_pendapatan' => $totalPendapatan,
            'total_beban' => $totalBeban,
            'laba_bersih' => $totalPendapatan - $totalBeban,
        ]);
    }
    
    /**
     * GET /api/v1/laporan/neraca?tahun=2026&bulan=6
     */
    public function neraca() {
        ApiRouter::requireRole($this->currentUser, ['admin', 'accountant']);
        
        [$startDate, $endDate] = $this->getPeriodDates();
        
        $neracaData = $this->model->getNeraca($endDate);
        $netIncome = $this->model->getNetIncomeForNeraca($endDate);
        
        $aktiva = [];
        $pasiva = [];
        $totalAktiva = 0;
        $totalPasiva = 0;
        
        foreach ($neracaData as $row) {
            $item = [
                'kode_akun' => $row['kode_akun'],
                'nama_akun' => $row['akun'],
                'saldo' => (float)$row['final_balance'],
            ];
            
            if (substr($row['kode_akun'], 0, 1) === '1') {
                $aktiva[] = $item;
                $totalAktiva += $item['saldo'];
            } else {
                $pasiva[] = $item;
                $totalPasiva += $item['saldo'];
            }
        }
        
        // Add net income to equity side
        $labaBerjalan = -$netIncome; // Negate because of DR/CR perspective
        $totalPasiva += $labaBerjalan;
        
        ApiRouter::sendSuccess([
            'aktiva' => $aktiva,
            'pasiva' => $pasiva,
            'laba_berjalan' => $labaBerjalan,
            'total_aktiva' => $totalAktiva,
            'total_pasiva' => $totalPasiva,
        ]);
    }
    
    /**
     * GET /api/v1/laporan/akun_list
     */
    public function akun_list() {
        $semuaAkun = $this->model->getDaftarAkun();
        $result = [];
        foreach ($semuaAkun as $kode => $nama) {
            $result[] = ['kode_akun' => $kode, 'nama_akun' => $nama];
        }
        ApiRouter::sendSuccess($result);
    }
}
?>
