<?php
class Dashboard extends Controller {
    public function index() {
        // Harus login (auth masih pakai auth lama buat transisi sementara)
        require_once __DIR__ . '/../../auth.php';
        require_role(['admin', 'accountant']);

        $dashboardModel = $this->model('DashboardModel');

        $years = $dashboardModel->getAvailableYears();
        $calendar_year = intval(date('Y'));

        // Pastikan tahun kalender saat ini selalu tersedia dalam daftar filter tahun
        if (!in_array($calendar_year, $years)) {
            $years[] = $calendar_year;
            rsort($years);
        }

        // Default awal masuk dashboard: mengikuti tahun kalender user, bukan tahun data tertinggi (2027 dsb)
        if (isset($_GET['tahun']) && !empty($_GET['tahun'])) {
            $raw_year = intval($_GET['tahun']);
            $current_year = ($raw_year >= 2020 && $raw_year <= 2099 && in_array($raw_year, $years)) ? $raw_year : $calendar_year;
        } else {
            $current_year = $calendar_year;
        }

        $raw_month = isset($_GET['bulan']) && !empty($_GET['bulan']) ? intval($_GET['bulan']) : intval(date('m'));
        $current_month = ($raw_month >= 1 && $raw_month <= 12) ? str_pad($raw_month, 2, '0', STR_PAD_LEFT) : date('m');

        $total_aset = $dashboardModel->getTotalAset($current_year);
        $total_kewajiban = $dashboardModel->getTotalKewajiban($current_year);
        $total_pendapatan = $dashboardModel->getTotalPendapatan($current_year);
        $total_beban = $dashboardModel->getTotalBeban($current_year);

        $laba_rugi_bersih = $total_pendapatan - $total_beban;
        $total_ekuitas = $total_aset - $total_kewajiban;

        // Data & Perbandingan Tahun Sebelumnya (YoY Comparison)
        $prev_year = $current_year - 1;
        $prev_total_aset = $dashboardModel->getTotalAset($prev_year);
        $prev_total_kewajiban = $dashboardModel->getTotalKewajiban($prev_year);
        $prev_total_pendapatan = $dashboardModel->getTotalPendapatan($prev_year);
        $prev_total_beban = $dashboardModel->getTotalBeban($prev_year);

        $prev_laba_rugi_bersih = $prev_total_pendapatan - $prev_total_beban;
        $prev_total_ekuitas = $prev_total_aset - $prev_total_kewajiban;

        $calcYoY = function($curr, $prev, $invert = false) {
            $curr = floatval($curr);
            $prev = floatval($prev);
            $diff = $curr - $prev;
            if ($prev == 0.0) {
                if ($curr == 0.0) {
                    return [
                        'pct' => 0,
                        'diff' => 0,
                        'pct_text' => '0%',
                        'sub_text' => 'thn lalu',
                        'text' => '0% thn lalu',
                        'status' => 'neutral',
                        'direction' => 'flat',
                        'prev_val' => 0
                    ];
                }
                // Jika tahun sebelumnya belum ada transaksi (Rp 0), bukan kenaikan +100% melainkan data tahun perdana
                return [
                    'pct' => null,
                    'diff' => $diff,
                    'pct_text' => 'Tahun Baru',
                    'sub_text' => 'thn lalu: Rp 0',
                    'text' => 'Tahun Baru',
                    'status' => 'neutral',
                    'direction' => 'flat',
                    'prev_val' => 0
                ];
            }
            $pct = ($diff / abs($prev)) * 100;
            $direction = $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat');
            if ($invert) {
                $status = $pct > 0 ? 'danger' : ($pct < 0 ? 'success' : 'neutral');
            } else {
                $status = $pct > 0 ? 'success' : ($pct < 0 ? 'danger' : 'neutral');
            }
            $formatted_pct = abs($pct) >= 1000 ? number_format(abs($pct), 0, ',', '.') : number_format(abs($pct), 1, ',', '.');
            $sign = $pct > 0 ? '+' : ($pct < 0 ? '-' : '');
            $pct_text = "{$sign}{$formatted_pct}%";
            return [
                'pct' => round($pct, 1),
                'diff' => $diff,
                'pct_text' => $pct_text,
                'sub_text' => 'thn lalu',
                'text' => "{$pct_text} thn lalu",
                'status' => $status,
                'direction' => $direction,
                'prev_val' => $prev
            ];
        };

        $yoy = [
            'prev_year' => $prev_year,
            'aset' => $calcYoY($total_aset, $prev_total_aset),
            'kewajiban' => $calcYoY($total_kewajiban, $prev_total_kewajiban, true),
            'pendapatan' => $calcYoY($total_pendapatan, $prev_total_pendapatan),
            'laba' => $calcYoY($laba_rugi_bersih, $prev_laba_rugi_bersih),
            'ekuitas' => $calcYoY($total_ekuitas, $prev_total_ekuitas)
        ];

        $chart_data = $dashboardModel->getMonthlyChartData($current_year);
        $asset_composition = $dashboardModel->getAssetComposition($current_year);

        // Tren Transaksi Harian Bulan Ini (Bar Chart)
        $daily_trend = $dashboardModel->getDailyTransactionTrend($current_year, $current_month);

        // Analisis Kesehatan Finansial (SAK EMKM Metrics)
        $laporanModel = $this->model('LaporanModel');
        if (isset($_GET['bulan']) && !empty($_GET['bulan'])) {
            $end_day = date('t', strtotime("$current_year-$current_month-01"));
            $end_date = "$current_year-$current_month-$end_day";
            $start_date = "$current_year-01-01";
        } else {
            $end_date = "$current_year-12-31";
            $start_date = "$current_year-01-01";
        }
        $neraca_data = $laporanModel->getNeraca($end_date);
        $net_income_neraca = $laporanModel->getNetIncomeForNeraca($end_date);
        $laba_rugi_data = $laporanModel->getLabaRugi($start_date, $end_date);
        $financial_health = calculate_financial_metrics($neraca_data, $laba_rugi_data, $net_income_neraca);

        $data = [
            'years' => $years,
            'current_year' => $current_year,
            'current_month' => $current_month,
            'total_aset' => $total_aset,
            'total_kewajiban' => $total_kewajiban,
            'total_pendapatan' => $total_pendapatan,
            'total_beban' => $total_beban,
            'laba_rugi_bersih' => $laba_rugi_bersih,
            'total_ekuitas' => $total_ekuitas,
            'yoy' => $yoy,
            'prev_year' => $prev_year,
            'chart_labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'],
            'chart_pendapatan' => $chart_data['pendapatan'],
            'chart_beban' => $chart_data['beban'],
            'aset_labels' => $asset_composition['labels'],
            'aset_data' => $asset_composition['data'],
            'daily_trend' => $daily_trend,
            'financial_health' => $financial_health
        ];

        $this->view('dashboard/index', $data);
    }
}
