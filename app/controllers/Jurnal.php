<?php

class Jurnal extends Controller {

    public function __construct() {
        // Ensure only logged in users can access Jurnal
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "/auth/login");
            exit;
        }
    }

    public function umum() {
        require_once __DIR__ . '/../../auth.php';
        require_role(['admin', 'accountant']);

        $model = $this->model('JurnalModel');
        
        $raw_tahun = intval($_GET['tahun'] ?? $_SESSION['filter_tahun'] ?? date('Y'));
        $tahun = ($raw_tahun >= 2020 && $raw_tahun <= 2099) ? $raw_tahun : intval(date('Y'));

        $raw_bulan = $_GET['bulan'] ?? $_SESSION['filter_bulan'] ?? '';
        $bulan = ($raw_bulan !== '' && intval($raw_bulan) >= 1 && intval($raw_bulan) <= 12) ? str_pad(intval($raw_bulan), 2, '0', STR_PAD_LEFT) : '';
        
        $_SESSION['filter_tahun'] = $tahun;
        $_SESSION['filter_bulan'] = $bulan;

        if (!empty($bulan)) {
            $start_date = $tahun . '-' . $bulan . '-01';
            $end_date = date("Y-m-t", strtotime($start_date));
        } else {
            $start_date = $tahun . '-01-01';
            $end_date = $tahun . '-12-31';
        }

        $user_id = null;
        if ($_SESSION['user_role'] === 'cashier') {
            $user_id = $_SESSION['user_id'];
        }

        $jurnal_list = $model->getJurnalUmum($start_date, $end_date, $user_id);

        $data = [
            'title' => 'Jurnal Umum',
            'tahun' => $tahun,
            'bulan' => $bulan,
            'jurnal_list' => $jurnal_list
        ];

        $this->view('jurnal/umum', $data);
    }

    public function penyesuaian() {
        // Only admin and accountant can access
        if (!in_array($_SESSION['user_role'], ['admin', 'accountant'])) {
            die("Akses ditolak. Anda tidak memiliki izin.");
        }

        $model = $this->model('JurnalModel');
        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_penyesuaian'])) {
            csrf_verify();
            $tanggal = $_POST['tanggal'] ?? '';
            $jenis_penyesuaian = $_POST['jenis_penyesuaian'] ?? '';
            $deskripsi = $_POST['final_deskripsi'] ?? 'Jurnal Penyesuaian';
            $akun_debet = $_POST['final_akun_debet'] ?? '';
            $akun_kredit = $_POST['final_akun_kredit'] ?? '';
            $raw_val = trim($_POST['final_nilai'] ?? '0');
            if (strpos($raw_val, '.') !== false && strpos($raw_val, ',') !== false) {
                $raw_val = str_replace('.', '', $raw_val);
                $raw_val = str_replace(',', '.', $raw_val);
            } elseif (strpos($raw_val, ',') !== false) {
                $raw_val = str_replace(',', '.', $raw_val);
            } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $raw_val)) {
                $raw_val = str_replace('.', '', $raw_val);
            }
            $nilai = floatval($raw_val);
            $referensi_id = $_POST['referensi_id'] ?? '';

            $akrual_types = ['beban_akrual', 'pendapatan_akrual'];
            $is_reversing = (isset($_POST['is_reversing']) && $_POST['is_reversing'] === '1'
                            && in_array($jenis_penyesuaian, $akrual_types)) ? 1 : 0;

            $closed_msg = is_periode_closed($tanggal, $model);
            if (!empty($closed_msg)) {
                $error = "Gagal: " . $closed_msg;
            } else if (empty($tanggal) || empty($jenis_penyesuaian) || empty($akun_debet) || empty($akun_kredit) || $nilai <= 0) {
                $error = "Gagal: Pastikan semua data lengkap dan nominal penyesuaian lebih besar dari 0.";
            } else {
                $created_by = $_SESSION['user_id'] ?? null;
                $res = $model->addJurnalPenyesuaian($tanggal, $deskripsi, $jenis_penyesuaian, $is_reversing, $akun_debet, $akun_kredit, $nilai, $referensi_id, $created_by);
                
                if ($res['status']) {
                    $reversing_note = $is_reversing ? ' | ✓ Jurnal Pembalik dijadwalkan otomatis.' : '';
                    $success = "Data Jurnal Penyesuaian berhasil disimpan (No: " . htmlspecialchars($res['kode']) . ")" . $reversing_note;
                } else {
                    $error = "Kritis (Gagal simpan): " . $res['message'];
                }
            }
        }

        $data = [
            'title' => 'Jurnal Penyesuaian Dinamis',
            'akun_list' => $model->getAkunList(),
            'aset_list' => $model->getAsetList(),
            'prepaid_list' => $model->getPrepaidList(),
            'error' => $error,
            'success' => $success
        ];

        $this->view('jurnal/penyesuaian', $data);
    }

    public function pembalik() {
        // Only admin and accountant can access
        if (!in_array($_SESSION['user_role'], ['admin', 'accountant'])) {
            die("Akses ditolak. Anda tidak memiliki izin.");
        }

        $model = $this->model('JurnalModel');
        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trigger_reversal'])) {
            csrf_verify();
            $kode_asal = trim($_POST['kode_asal'] ?? '');
            if (empty($kode_asal)) {
                $error = 'Kode transaksi tidak valid.';
            } else {
                $res = $model->prosesPembalikManual($kode_asal);
                if ($res['status']) {
                    $success = "✓ Jurnal pembalik berhasil dibuat: <strong>{$res['kode']}</strong> (tanggal: {$res['tanggal']})";
                } else {
                    $error = 'Gagal: ' . $res['message'];
                }
            }
        }

        $data = [
            'title' => 'Jurnal Pembalik',
            'pending' => $model->getPembalikPending(),
            'sudah_balik' => $model->getPembalikDone(),
            'error' => $error,
            'success' => $success
        ];

        $this->view('jurnal/pembalik', $data);
    }
}
