<?php

class Analisis extends Controller {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "/auth/login");
            exit;
        }
        // Only admin and accountant can access
        if (!in_array($_SESSION['user_role'], ['admin', 'accountant'])) {
            die("Akses ditolak. Hanya Admin dan Akuntan yang dapat mengakses menu ini.");
        }
    }

    /**
     * Eksekusi request ke Gemini API dengan fallback cerdas ke Rule Engine lokal jika API Key kosong/gagal
     */
    private function doAnalysis($prompt_text, $session_key, $fallback_metrics = null, $report_type = 'neraca') {
        $apiKey = trim($_SESSION['api_key'] ?? '');
        $model_choice = $_SESSION['model_choice'] ?? 'gemini-3.5-flash-lite';

        // Jika API Key tidak ada atau masih default 'xxx', langsung gunakan Deterministic Fallback Engine
        if (empty($apiKey) || $apiKey === 'xxx') {
            if ($fallback_metrics) {
                $notice = '<div style="background: rgba(245, 158, 11, 0.1); border-left: 4px solid #f59e0b; padding: 0.85rem 1.1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.88rem; color: var(--text-main); display: flex; align-items: center; justify-content: space-between;">
                    <div><strong>Mode Analisis Cerdas (Offline Rule Engine):</strong> Analisis dihasilkan dari perhitungan rasio SAK EMKM deterministik. Masukkan Google Gemini API Key untuk rekomendasi naratif AI generatif.</div>
                </div>';
                return $notice . generate_deterministic_analysis_html($fallback_metrics, $report_type);
            } else {
                return "<div style='color: var(--danger-color); padding: 15px; border-radius: 5px; background: var(--danger-bg); border: 1px solid var(--danger-color);'><strong>Peringatan:</strong> API Key Gemini belum diatur. Silakan atur di menu Pengaturan AI.</div>";
            }
        }
        
        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . urlencode($model_choice) . ":generateContent";
        $data = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt_text]
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature" => 0.2,
                "topP" => 0.8,
                "maxOutputTokens" => 2048
            ]
        ];
        
        $max_retries = 2;
        $retry_delay = 1; // detik
        $attempt = 0;
        $success = false;
        $response = false;
        $curl_err = '';

        while ($attempt < $max_retries && !$success) {
            $attempt++;
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                $curl_err = curl_error($ch);
                sleep($retry_delay);
            } else {
                if ($http_code == 503 || $http_code == 429) {
                    sleep($retry_delay);
                } else {
                    $success = true;
                }
            }
            curl_close($ch);
        }
        
        if ($response) {
            $result = json_decode($response, true);
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                $raw_text = $result['candidates'][0]['content']['parts'][0]['text'];
                
                $raw_text = str_replace(['```html', '```'], '', $raw_text);
                $raw_text = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $raw_text);

                $allowed_tags = '<table><tr><td><th><thead><tbody><tfoot><h3><h4><h5><ul><ol><li><strong><b><i><em><br><p><div><span>';
                $raw_text = strip_tags($raw_text, $allowed_tags);
                
                $raw_text = preg_replace('/on\w+="[^"]*"/i', '', $raw_text);
                $raw_text = preg_replace('/on\w+=\'[^\']*\'/i', '', $raw_text);

                return trim($raw_text);
            } else {
                // Jika API error (kuota habis, invalid key), gunakan fallback otomatis jika tersedia
                if ($fallback_metrics) {
                    $error_msg = $result['error']['message'] ?? 'API Key tidak valid atau kuota terlampaui.';
                    $notice = '<div style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444; padding: 0.85rem 1.1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.88rem; color: var(--text-main); display: flex; align-items: center; justify-content: space-between;">
                        <div><strong>Gemini API Fallback (' . htmlspecialchars($error_msg) . '):</strong> Beralih ke Rule Engine lokal.</div>
                    </div>';
                    return $notice . generate_deterministic_analysis_html($fallback_metrics, $report_type);
                }
                
                if (isset($result['error']['message'])) {
                    return "<div style='color: var(--danger-color); padding: 15px; border-radius: 5px; background: var(--danger-bg); border: 1px solid var(--danger-color);'><strong>Error API Gemini:</strong> " . htmlspecialchars($result['error']['message']) . "</div>";
                }
            }
        }
        
        // Fallback jika koneksi internet terputus
        if ($fallback_metrics) {
            $notice = '<div style="background: rgba(245, 158, 11, 0.1); border-left: 4px solid #f59e0b; padding: 0.85rem 1.1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.88rem; color: var(--text-main);">
                <strong>Koneksi AI Terputus:</strong> Menggunakan perhitungan rasio deterministik lokal.
            </div>';
            return $notice . generate_deterministic_analysis_html($fallback_metrics, $report_type);
        }

        return "<div style='color: var(--danger-color); padding: 15px; border-radius: 5px; background: var(--danger-bg); border: 1px solid var(--danger-color);'><strong>Koneksi Gagal:</strong> " . htmlspecialchars($curl_err) . "</div>";
    }

    /**
     * Endpoint untuk menyimpan konfigurasi API Key & Model Gemini per pengguna
     */
    public function save_settings() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $api_key = trim($_POST['api_key'] ?? '');
            $model_choice = trim($_POST['model_choice'] ?? 'gemini-3.5-flash-lite');

            $_SESSION['api_key'] = $api_key;
            $_SESSION['model_choice'] = $model_choice;

            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => 'Pengaturan Gemini AI berhasil disimpan!']);
                exit;
            }

            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? BASE_URL));
            exit;
        }
    }

    /**
     * Uji koneksi API Key Gemini secara instan
     */
    public function test_connection() {
        header('Content-Type: application/json');
        $apiKey = trim($_POST['api_key'] ?? $_SESSION['api_key'] ?? '');
        $model = trim($_POST['model_choice'] ?? $_SESSION['model_choice'] ?? 'gemini-3.5-flash-lite');

        if (empty($apiKey)) {
            echo json_encode(['status' => 'error', 'message' => 'API Key belum diisi.']);
            exit;
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . urlencode($model) . ":generateContent";
        $data = [
            "contents" => [["parts" => [["text" => "Ping. Balas dengan 1 kata: 'Koneksi Berhasil'"]]]]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-goog-api-key: ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal terhubung: ' . $err]);
            exit;
        }

        $res_json = json_decode($response, true);
        if (isset($res_json['candidates'][0]['content']['parts'][0]['text'])) {
            echo json_encode(['status' => 'success', 'message' => 'Koneksi ke Google Gemini AI Berhasil Aktif! (' . $model . ')']);
        } else {
            $msg = $res_json['error']['message'] ?? 'Respon tidak valid (HTTP ' . $http_code . ')';
            echo json_encode(['status' => 'error', 'message' => 'Gagal: ' . $msg]);
        }
        exit;
    }

    public function jurnal() {
        $db = new Database();
        $conn = $db->getConnection();
        
        if (isset($_GET['tahun'])) {
            $tahun = $_GET['tahun'];
            $bulan = $_GET['bulan'] ?? '';
            $_SESSION['filter_jurnal_tahun'] = $tahun;
            $_SESSION['filter_jurnal_bulan'] = $bulan;
        } else {
            $tahun = $_SESSION['filter_jurnal_tahun'] ?? date('Y');
            $bulan = $_SESSION['filter_jurnal_bulan'] ?? '';
        }

        $nama_bulan = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
        ];

        if (!empty($bulan)) {
            $start_date = $tahun . '-' . $bulan . '-01';
            $end_date = date("Y-m-t", strtotime($start_date));
            $periode_str = $nama_bulan[$bulan] . " " . $tahun;
        } else {
            $start_date = $tahun . '-01-01';
            $end_date = $tahun . '-12-31';
            $periode_str = "Tahun " . $tahun;
        }

        $success = '';
        if (isset($_GET['tahun'])) {
            $success = "Periode sudah berubah jadi " . $periode_str;
        }

        $sql = "SELECT 
                    dt.tanggal,
                    dt.kode_transaksi,
                    dt.deskripsi,
                    t.kode_akun,
                    a.akun,
                    (IF(t.dk='Debit', t.nilai, 0)) as Debit, 
                    (IF(t.dk='Debit', 0, t.nilai)) as Kredit 
                FROM detil_transaksi dt 
                LEFT JOIN transaksi t ON dt.kode_transaksi=t.kode_transaksi
                LEFT JOIN akun a ON t.kode_akun = a.kode_akun
                WHERE dt.tanggal >= ? AND dt.tanggal <= ? AND dt.status_verifikasi = 'sesuai'
                ORDER BY dt.tanggal ASC, dt.kode_transaksi ASC, t.dk ASC";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();

        $jurnal_data = [];
        while ($row = $result->fetch_assoc()) {
            $kode_trx = $row['kode_transaksi'];
            if (!isset($jurnal_data[$kode_trx])) {
                $jurnal_data[$kode_trx] = [
                    'tanggal' => $row['tanggal'],
                    'deskripsi' => $row['deskripsi'],
                    'entries' => []
                ];
            }
            
            $deb = round(floatval($row['Debit']), 0);
            $kre = round(floatval($row['Kredit']), 0);
            
            $jurnal_data[$kode_trx]['entries'][] = [
                'akun' => $row['akun'],
                'debit' => $deb,
                'kredit' => $kre,
                'debit_display' => $deb > 0 ? number_format($deb, 0, ',', '.') : '-',
                'kredit_display' => $kre > 0 ? number_format($kre, 0, ',', '.') : '-'
            ];
        }

        $json_jurnal = [
            'periode' => $periode_str,
            'jurnal' => $jurnal_data
        ];

        $analysis_result = "";
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['analyze'])) {
            csrf_verify();
            $prompt_text = "Anda adalah Auditor & Akuntan Senior. Analisis data Jurnal Umum ini secara mendalam menurut standar akuntansi SAK EMKM dan PSAK.
            Tugas Anda:
            1. Audit Kepatuhan & Kelengkapan: Periksa apakah jurnal berpasangan (debit = kredit) konsisten dan akun yang digunakan sesuai standar.
            2. Deteksi Anomali & Koreksi: Identifikasi transaksi yang janggal, pengeluaran berulang bernilai besar, atau transaksi koreksi.
            3. Tren Arus Keuangan: Berikan ringkasan akun dengan mutasi terbesar dan arah pergerakan dana perusahaan.
            4. Rekomendasi Pengendalian Internal: Berikan saran konkret untuk penguatan SOP pencatatan kasir dan akuntansi.
            
            PENTING:
            - Gunakan angka nominal yang ada di data JSON secara EKSIS/TEPAT.
            - Sajikan data dengan format ribuan bertitik (contoh: Rp 1.000.000).
            - Berikan jawaban dalam format HTML murni yang rapi (gunakan <h3>, <h4>, <table>, <ul>, <li>, <strong>, dll). Jangan gunakan Markdown atau blok ```html.
            
            Data Jurnal:\n" . json_encode($json_jurnal, JSON_PRETTY_PRINT);
            
            $analysis_result = $this->doAnalysis($prompt_text, 'analysis_jurnal', null, 'jurnal');
            $_SESSION['analysis_jurnal'][$tahun][$bulan] = $analysis_result;
        } else {
            if (isset($_SESSION['analysis_jurnal'][$tahun][$bulan])) {
                $analysis_result = $_SESSION['analysis_jurnal'][$tahun][$bulan];
            }
        }

        $data = [
            'title' => 'Analisa Jurnal Umum',
            'tahun' => $tahun,
            'bulan' => $bulan,
            'nama_bulan' => $nama_bulan,
            'analysis_result' => $analysis_result,
            'success' => $success
        ];

        $this->view('analisis/jurnal', $data);
    }

    public function laba_rugi() {
        $db = new Database();
        $conn = $db->getConnection();
        
        if (isset($_GET['tahun'])) {
            $tahun = $_GET['tahun'];
            $bulan = $_GET['bulan'] ?? '';
            $_SESSION['filter_lr_tahun'] = $tahun;
            $_SESSION['filter_lr_bulan'] = $bulan;
        } else {
            $tahun = $_SESSION['filter_lr_tahun'] ?? date('Y');
            $bulan = $_SESSION['filter_lr_bulan'] ?? '';
        }

        $nama_bulan = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
        ];

        if (!empty($bulan)) {
            $start_date = $tahun . '-' . $bulan . '-01';
            $end_date = date("Y-m-t", strtotime($start_date));
            $periode_str = $nama_bulan[$bulan] . " " . $tahun;
        } else {
            $start_date = $tahun . '-01-01';
            $end_date = $tahun . '-12-31';
            $periode_str = "Tahun " . $tahun;
        }

        $success = '';
        if (isset($_GET['tahun'])) {
            $success = "Periode sudah berubah jadi " . $periode_str;
        }

        $sql_lr = "SELECT 
                        SUBSTRING(t.kode_akun, 1, 1) as grup,
                        a.kode_akun,
                        a.akun as nama_akun,
                        SUM(CASE WHEN LOWER(t.dk) = 'kredit' THEN t.nilai ELSE -t.nilai END) as saldo_kredit_normal,
                        SUM(CASE WHEN LOWER(t.dk) = 'debit' THEN t.nilai ELSE -t.nilai END) as saldo_debit_normal
                    FROM transaksi t
                    JOIN akun a ON t.kode_akun = a.kode_akun
                    JOIN detil_transaksi dt ON t.kode_transaksi = dt.kode_transaksi
                    WHERE dt.tanggal >= ? AND dt.tanggal <= ? AND dt.status_verifikasi = 'sesuai'
                      AND (SUBSTRING(t.kode_akun, 1, 1) = '4' OR SUBSTRING(t.kode_akun, 1, 1) = '5')
                    GROUP BY a.kode_akun, a.akun
                    ORDER BY a.kode_akun ASC";
                    
        $stmt = $conn->prepare($sql_lr);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();

        $pendapatan = [];
        $beban = [];
        $total_pendapatan = 0;
        $total_beban = 0;
        $labarugi_rows = [];

        while ($row = $result->fetch_assoc()) {
            if ($row['grup'] == '4') {
                $saldo = floatval($row['saldo_kredit_normal']);
                $pendapatan[] = [
                    'kode' => $row['kode_akun'],
                    'akun' => $row['nama_akun'],
                    'nilai' => $saldo
                ];
                $total_pendapatan += $saldo;
                $labarugi_rows[] = ['kode_akun' => $row['kode_akun'], 'akun' => $row['nama_akun'], 'total_nilai' => $saldo];
            } else if ($row['grup'] == '5') {
                $saldo = floatval($row['saldo_debit_normal']);
                $beban[] = [
                    'kode' => $row['kode_akun'],
                    'akun' => $row['nama_akun'],
                    'nilai' => $saldo
                ];
                $total_beban += $saldo;
                $labarugi_rows[] = ['kode_akun' => $row['kode_akun'], 'akun' => $row['nama_akun'], 'total_nilai' => $saldo];
            }
        }
        $laba_bersih = $total_pendapatan - $total_beban;

        // Ambil data neraca untuk menghitung metrics komprehensif
        $laporanModel = $this->model('LaporanModel');
        $neraca_rows = $laporanModel->getNeraca($end_date);
        $net_income_neraca = $laporanModel->getNetIncomeForNeraca($end_date);
        $metrics = calculate_financial_metrics($neraca_rows, $labarugi_rows, $net_income_neraca);

        $json_lr = [
            'periode' => $periode_str,
            'pendapatan' => [
                'detail' => $pendapatan,
                'total' => $total_pendapatan
            ],
            'beban' => [
                'detail' => $beban,
                'total' => $total_beban
            ],
            'laba_bersih' => $laba_bersih,
            'rasio_terhitung' => [
                'net_profit_margin' => number_format($metrics['ratios']['npm'], 2) . '%',
                'operating_expense_ratio' => number_format($metrics['ratios']['oer'], 2) . '%',
                'skor_kesehatan' => $metrics['health']['score'] . '/100 (' . $metrics['health']['status'] . ')'
            ]
        ];

        $analysis_result = "";
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['analyze'])) {
            csrf_verify();
            $prompt_text = "Anda adalah Konsultan Keuangan & Ahli Analisis Profitabilitas. Analisis Laporan Laba Rugi dan Metrik Finansial ini.
            Metrik Terhitung:
            - Total Pendapatan: Rp " . number_format($total_pendapatan, 0, ',', '.') . "
            - Total Beban Operasional: Rp " . number_format($total_beban, 0, ',', '.') . "
            - Laba Bersih: Rp " . number_format($laba_bersih, 0, ',', '.') . "
            - Net Profit Margin (NPM): " . number_format($metrics['ratios']['npm'], 2) . "%
            - Operating Expense Ratio (OER): " . number_format($metrics['ratios']['oer'], 2) . "%
            - Skor Kesehatan: " . $metrics['health']['score'] . "/100 (" . $metrics['health']['status'] . ")
            
            Tugas Analisis:
            1. Evaluasi Profitabilitas: Nilai kualitas margin laba bersih terhadap pendapatan.
            2. Analisis Efisiensi Beban: Bedah akun-akun beban operasional yang paling dominan mengikis laba.
            3. Analisis Titik Impas (Break-Even Insight): Berikan pandangan terkait batas aman pendapatan operasional.
            4. Rekomendasi Strategis: Berikan langkah taktis untuk meningkatkan margin keuntungan dan efisiensi pengeluaran.
            
            Format Jawaban: HTML murni yang elegan dan rapi (gunakan <h3>, <h4>, <table>, <ul>, <li>, <strong>). Jangan gunakan Markdown atau blok ```html.
            
            Data Laba Rugi Lengkap:\n" . json_encode($json_lr, JSON_PRETTY_PRINT);
            
            $analysis_result = $this->doAnalysis($prompt_text, 'analysis_lr', $metrics, 'laba_rugi');
            $_SESSION['analysis_lr'][$tahun][$bulan] = $analysis_result;
        } else {
            if (isset($_SESSION['analysis_lr'][$tahun][$bulan])) {
                $analysis_result = $_SESSION['analysis_lr'][$tahun][$bulan];
            }
        }

        $data = [
            'title' => 'Analisa Laba Rugi',
            'tahun' => $tahun,
            'bulan' => $bulan,
            'nama_bulan' => $nama_bulan,
            'analysis_result' => $analysis_result,
            'metrics' => $metrics,
            'success' => $success
        ];

        $this->view('analisis/laba_rugi', $data);
    }

    public function neraca() {
        $db = new Database();
        $conn = $db->getConnection();
        
        if (isset($_GET['tahun'])) {
            $tahun = $_GET['tahun'];
            $bulan = $_GET['bulan'] ?? '';
            $_SESSION['filter_neraca_tahun'] = $tahun;
            $_SESSION['filter_neraca_bulan'] = $bulan;
        } else {
            $tahun = $_SESSION['filter_neraca_tahun'] ?? date('Y');
            $bulan = $_SESSION['filter_neraca_bulan'] ?? '';
        }

        $nama_bulan = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
        ];

        if (!empty($bulan)) {
            $start_date = $tahun . '-' . $bulan . '-01';
            $end_date = date("Y-m-t", strtotime($start_date));
            $periode_str = $nama_bulan[$bulan] . " " . $tahun;
        } else {
            $start_date = $tahun . '-01-01';
            $end_date = $tahun . '-12-31';
            $periode_str = "Tahun " . $tahun;
        }

        $success = '';
        if (isset($_GET['tahun'])) {
            $success = "Periode sudah berubah jadi " . $periode_str;
        }

        $laporanModel = $this->model('LaporanModel');
        $neraca_rows = $laporanModel->getNeraca($end_date);
        $net_income_neraca = $laporanModel->getNetIncomeForNeraca($end_date);
        $labarugi_rows = $laporanModel->getLabaRugi($start_date, $end_date);

        // Hitung metrik SAK EMKM deterministik
        $metrics = calculate_financial_metrics($neraca_rows, $labarugi_rows, $net_income_neraca);

        $aset = [];
        $kewajiban = [];
        $modal = [];
        
        $total_aset_lancar = $metrics['summary']['aktiva_lancar'];
        $total_aset_tetap = $metrics['summary']['aktiva_tetap'];
        $total_utang_lancar = $metrics['summary']['kewajiban_lancar'];
        $total_utang_jangka_panjang = $metrics['summary']['total_kewajiban'] - $metrics['summary']['kewajiban_lancar'];
        $total_aset = $metrics['summary']['total_aset'];
        $total_kewajiban = $metrics['summary']['total_kewajiban'];

        foreach ($neraca_rows as $row) {
            $kat = $row['kategori_neraca'] ?? 'Lainnya';
            $kode = $row['kode_akun'];
            $nama = $row['akun'];
            $bal = floatval($row['final_balance']);
            $prefix = substr($kode, 0, 1);

            if ($prefix === '1') {
                if (!isset($aset[$kat])) $aset[$kat] = [];
                $aset[$kat][] = ['kode' => $kode, 'akun' => $nama, 'nilai' => $bal];
            } elseif ($prefix === '2') {
                if (!isset($kewajiban[$kat])) $kewajiban[$kat] = [];
                $kewajiban[$kat][] = ['kode' => $kode, 'akun' => $nama, 'nilai' => $bal];
            } elseif ($prefix === '3') {
                if (!isset($modal[$kat])) $modal[$kat] = [];
                $modal[$kat][] = ['kode' => $kode, 'akun' => $nama, 'nilai' => $bal];
            }
        }

        $json_neraca = [
            'periode_sampai' => $periode_str,
            'aset' => [
                'detail_per_kategori' => $aset,
                'total_aset_lancar' => $total_aset_lancar,
                'total_aset_tetap' => $total_aset_tetap,
                'total_keseluruhan_aset' => $total_aset
            ],
            'kewajiban' => [
                'detail_per_kategori' => $kewajiban,
                'total_utang_lancar' => $total_utang_lancar,
                'total_utang_jangka_panjang' => $total_utang_jangka_panjang,
                'total_keseluruhan_kewajiban' => $total_kewajiban
            ],
            'modal' => [
                'detail' => $modal,
                'laba_berjalan' => $net_income_neraca,
                'total_ekuitas' => $metrics['summary']['total_ekuitas']
            ],
            'rasio_sak_emkm' => [
                'current_ratio' => number_format($metrics['ratios']['current_ratio'], 2) . 'x',
                'quick_ratio' => number_format($metrics['ratios']['quick_ratio'], 2) . 'x',
                'cash_ratio' => number_format($metrics['ratios']['cash_ratio'], 2) . 'x',
                'debt_to_asset' => number_format($metrics['ratios']['dar'], 2) . '%',
                'debt_to_equity' => number_format($metrics['ratios']['der'], 2) . '%',
                'skor_kesehatan' => $metrics['health']['score'] . '/100 (' . $metrics['health']['status'] . ')'
            ]
        ];

        $analysis_result = "";
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['analyze'])) {
            csrf_verify();
            $prompt_text = "Anda adalah Konsultan Keuangan Senior. Analisis Laporan Neraca Keuangan (Balance Sheet) dan Metrik SAK EMKM ini.
            Metrik Finansial Terhitung:
            - Current Ratio (Rasio Lancar): " . number_format($metrics['ratios']['current_ratio'], 2) . "x
            - Quick Ratio (Acid-Test): " . number_format($metrics['ratios']['quick_ratio'], 2) . "x
            - Cash Ratio: " . number_format($metrics['ratios']['cash_ratio'], 2) . "x
            - Modal Kerja Bersih (NWC): Rp " . number_format($metrics['summary']['modal_kerja_bersih'], 0, ',', '.') . "
            - Debt to Asset Ratio (DAR): " . number_format($metrics['ratios']['dar'], 2) . "%
            - Debt to Equity Ratio (DER): " . number_format($metrics['ratios']['der'], 2) . "%
            - Skor Kesehatan Finansial: " . $metrics['health']['score'] . "/100 (" . $metrics['health']['status'] . ")
            
            Tugas Analisis:
            1. Evaluasi Likuiditas & Arus Kas: Jelaskan kemampuan aset lancar dalam menjamin kewajiban jangka pendek.
            2. Evaluasi Solvabilitas & Struktur Modal: Analisis tingkat ketergantungan perusahaan pada utang vs modal sendiri.
            3. Mitigasi Risiko Finansial: Identifikasi titik rawan (misal: piutang tak tertagih atau kas menganggur).
            4. Rekomendasi Manajerial: Berikan saran penataan struktur aset dan likuiditas terbaik untuk manajemen.
            
            PENTING:
            - Format jawaban dalam HTML murni (gunakan <h3>, <h4>, <table>, <ul>, <li>, <strong>). Jangan gunakan Markdown atau blok ```html.
            
            Data Neraca:\n" . json_encode($json_neraca, JSON_PRETTY_PRINT);
            
            $analysis_result = $this->doAnalysis($prompt_text, 'analysis_neraca', $metrics, 'neraca');
            $_SESSION['analysis_neraca'][$tahun][$bulan] = $analysis_result;
        } else {
            if (isset($_SESSION['analysis_neraca'][$tahun][$bulan])) {
                $analysis_result = $_SESSION['analysis_neraca'][$tahun][$bulan];
            }
        }

        $data = [
            'title' => 'Analisa Neraca',
            'tahun' => $tahun,
            'bulan' => $bulan,
            'nama_bulan' => $nama_bulan,
            'analysis_result' => $analysis_result,
            'metrics' => $metrics,
            'success' => $success
        ];

        $this->view('analisis/neraca', $data);
    }
}
