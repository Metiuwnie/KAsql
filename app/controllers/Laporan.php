<?php

class Laporan extends Controller {

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "/auth/login");
            exit;
        }
        // Pastikan hanya admin & accountant yang bisa mengakses Laporan
        if (!in_array($_SESSION['user_role'], ['admin', 'accountant'])) {
            die("Akses ditolak. Anda tidak memiliki izin.");
        }
    }

    private function sanitizePeriode($param_tahun, $param_bulan, $session_tahun_key, $session_bulan_key) {
        $raw_tahun = intval($param_tahun ?? $_SESSION[$session_tahun_key] ?? date('Y'));
        $tahun = ($raw_tahun >= 2020 && $raw_tahun <= 2099) ? $raw_tahun : intval(date('Y'));

        $raw_bulan = $param_bulan ?? $_SESSION[$session_bulan_key] ?? '';
        $bulan = ($raw_bulan !== '' && intval($raw_bulan) >= 1 && intval($raw_bulan) <= 12) ? str_pad(intval($raw_bulan), 2, '0', STR_PAD_LEFT) : '';

        $_SESSION[$session_tahun_key] = $tahun;
        $_SESSION[$session_bulan_key] = $bulan;

        return [$tahun, $bulan];
    }

    public function buku_besar() {
        $model = $this->model('LaporanModel');
        
        list($tahun, $bulan) = $this->sanitizePeriode($_GET['tahun'] ?? null, $_GET['bulan'] ?? null, 'bb_tahun', 'bb_bulan');
        $akun_pilihan = isset($_GET['akun']) ? (array)$_GET['akun'] : ($_SESSION['bb_akun'] ?? []);
        $_SESSION['bb_akun'] = $akun_pilihan;

        $is_submitted = isset($_GET['tahun']); // if form was submitted
        
        $semua_akun = $model->getDaftarAkun();

        $saldo_awals = [];
        $transactions = [];
        
        if ($is_submitted && !empty($akun_pilihan)) {
            if (!empty($bulan)) {
                $last_day = date("t", strtotime($tahun . "-" . $bulan . "-01"));
                $start_date = $tahun . "-" . $bulan . "-01";
                $end_date = $tahun . "-" . $bulan . "-" . $last_day;
            } else {
                $start_date = $tahun . "-01-01";
                $end_date = $tahun . "-12-31";
            }
            
            $saldo_awals = $model->getSaldoAwalBukuBesar($akun_pilihan, $start_date);
            $transactions = $model->getTransaksiBukuBesar($akun_pilihan, $start_date, $end_date);
        }

        $data = [
            'title' => 'Buku Besar',
            'semua_akun' => $semua_akun,
            'akun_pilihan' => $akun_pilihan,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'is_submitted' => $is_submitted,
            'saldo_awals' => $saldo_awals,
            'transactions' => $transactions
        ];

        $this->view('laporan/buku_besar', $data);
    }

    public function laba_rugi() {
        $model = $this->model('LaporanModel');
        
        list($tahun, $bulan) = $this->sanitizePeriode($_GET['tahun'] ?? null, $_GET['bulan'] ?? null, 'lr_tahun', 'lr_bulan');

        if (!empty($bulan)) {
            $last_day = date("t", strtotime($tahun . "-" . $bulan . "-01"));
            $start_date = $tahun . "-" . $bulan . "-01";
            $end_date = $tahun . "-" . $bulan . "-" . $last_day;
        } else {
            $start_date = $tahun . "-01-01";
            $end_date = $tahun . "-12-31";
        }

        $laba_rugi_data = $model->getLabaRugi($start_date, $end_date);
        $neraca_data = $model->getNeraca($end_date);
        $net_income_neraca = $model->getNetIncomeForNeraca($end_date);
        $metrics = calculate_financial_metrics($neraca_data, $laba_rugi_data, $net_income_neraca);

        $data = [
            'title' => 'Laba Rugi',
            'bulan' => $bulan,
            'tahun' => $tahun,
            'laba_rugi_data' => $laba_rugi_data,
            'metrics' => $metrics
        ];
        
        $this->view('laporan/laba_rugi', $data);
    }

    public function neraca_saldo() {
        $model = $this->model('LaporanModel');
        
        list($tahun, $bulan) = $this->sanitizePeriode($_GET['tahun'] ?? null, $_GET['bulan'] ?? null, 'ns_tahun', 'ns_bulan');

        if (!empty($bulan)) {
            $last_day = date("t", strtotime($tahun . "-" . $bulan . "-01"));
            $start_date = $tahun . "-" . $bulan . "-01";
            $end_date = $tahun . "-" . $bulan . "-" . $last_day;
        } else {
            $start_date = $tahun . "-01-01";
            $end_date = $tahun . "-12-31";
        }

        // Untuk neraca saldo, kita juga butuh saldo awal
        $semua_akun = $model->getDaftarAkun();
        $akun_list = array_keys($semua_akun);
        
        $saldo_awals = $model->getSaldoAwalBukuBesar($akun_list, $start_date);
        $mutasi = $model->getNeracaSaldo($start_date, $end_date);

        $data = [
            'title' => 'Neraca Saldo',
            'bulan' => $bulan,
            'tahun' => $tahun,
            'saldo_awals' => $saldo_awals,
            'mutasi' => $mutasi
        ];

        $this->view('laporan/neraca_saldo', $data);
    }

    public function neraca() {
        $model = $this->model('LaporanModel');
        
        list($tahun, $bulan) = $this->sanitizePeriode($_GET['tahun'] ?? null, $_GET['bulan'] ?? null, 'nr_tahun', 'nr_bulan');

        if (!empty($bulan)) {
            $last_day = date("t", strtotime($tahun . "-" . $bulan . "-01"));
            $start_date = $tahun . "-" . $bulan . "-01";
            $end_date = $tahun . "-" . $bulan . "-" . $last_day;
        } else {
            $start_date = $tahun . "-01-01";
            $end_date = $tahun . "-12-31";
        }

        // Neraca perlu Laba Rugi untuk Laba Ditahan / Laba Berjalan
        $end_date_str = $end_date;
        $neraca_data = $model->getNeraca($end_date_str);
        $net_income_neraca = $model->getNetIncomeForNeraca($end_date_str);
        $laba_rugi_data = $model->getLabaRugi($start_date, $end_date);
        $metrics = calculate_financial_metrics($neraca_data, $laba_rugi_data, $net_income_neraca);

        $data = [
            'title' => 'Neraca',
            'bulan' => $bulan,
            'tahun' => $tahun,
            'neraca_data' => $neraca_data,
            'net_income_for_neraca' => $net_income_neraca,
            'metrics' => $metrics
        ];

        $this->view('laporan/neraca', $data);
    }
}
