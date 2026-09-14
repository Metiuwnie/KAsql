<?php
/**
 * AnalisisApi - Handles AI Analysis features for Mobile
 */
require_once __DIR__ . '/../../../core/Database.php';

class AnalisisApi {
    
    private $currentUser;
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function setCurrentUser($user) {
        $this->currentUser = $user;
    }

    private function doAnalysis($prompt_text, $apiKey) {
        if (empty($apiKey)) {
            ApiRouter::sendError('API Key tidak boleh kosong', 400);
        }
        
        $model_choice = 'gemini-3.5-flash-lite';
        
        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . $model_choice . ":generateContent";
        $data = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt_text]
                    ]
                ]
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
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                $curl_err = curl_error($ch);
                sleep($retry_delay);
            } else {
                if ($http_code == 503 || $http_code == 429) {
                    sleep($retry_delay);
                } else if ($http_code == 200) {
                    $success = true;
                } else {
                    $success = true; // Akan ditangkap sebagai error saat parsing
                }
            }
            curl_close($ch);
        }

        if (!$success) {
            return "Error: " . ($curl_err ? $curl_err : "Koneksi ke AI gagal (Service Unavailable).");
        }

        $res_json = json_decode($response, true);
        if (isset($res_json['error'])) {
            return "API Error: " . $res_json['error']['message'];
        }

        if (isset($res_json['candidates'][0]['content']['parts'][0]['text'])) {
            return $res_json['candidates'][0]['content']['parts'][0]['text'];
        }

        return "Error: Respons tidak valid dari AI.";
    }

    public function jurnal() {
        ApiRouter::requireRole($this->currentUser, ['admin', 'accountant']);
        $body = ApiRouter::getJsonBody();
        
        $tahun = $body['tahun'] ?? date('Y');
        $bulan = $body['bulan'] ?? '';
        $apiKey = $body['api_key'] ?? '';

        $nama_bulan = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
        ];

        if (!empty($bulan)) {
            $start_date = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT) . '-01';
            $end_date = date("Y-m-t", strtotime($start_date));
            $periode_str = $nama_bulan[str_pad($bulan, 2, '0', STR_PAD_LEFT)] . " " . $tahun;
        } else {
            $start_date = $tahun . '-01-01';
            $end_date = $tahun . '-12-31';
            $periode_str = "Tahun " . $tahun;
        }

        $conn = $this->db->getConnection();
        $sql = "SELECT 
                    dt.tanggal, dt.kode_transaksi, dt.deskripsi, t.kode_akun, a.akun,
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
        
        if ($result->num_rows == 0) {
            ApiRouter::sendSuccess("Tidak ada data jurnal untuk periode ini.");
        }

        $prompt = "Berikut adalah data Jurnal Umum untuk periode $periode_str:\n\n";
        while($row = $result->fetch_assoc()) {
            $prompt .= $row['tanggal'] . " | " . $row['kode_transaksi'] . " | " . $row['deskripsi'] . " | " . $row['akun'] . " | D: " . $row['Debit'] . " | K: " . $row['Kredit'] . "\n";
        }
        $prompt .= "\nTolong berikan analisis singkat (maksimal 3 paragraf) mengenai kewajaran transaksi ini, apakah ada anomali atau pola pengeluaran/pemasukan yang perlu diperhatikan? Gunakan bahasa Indonesia.";

        $analysis = $this->doAnalysis($prompt, $apiKey);
        ApiRouter::sendSuccess($analysis);
    }

    public function laba_rugi() {
        ApiRouter::requireRole($this->currentUser, ['admin', 'accountant']);
        $body = ApiRouter::getJsonBody();
        
        $tahun = $body['tahun'] ?? date('Y');
        $bulan = $body['bulan'] ?? '';
        $apiKey = $body['api_key'] ?? '';

        if (empty($bulan)) {
            $start_date = $tahun . '-01-01';
            $end_date = $tahun . '-12-31';
            $periode_str = "Tahun " . $tahun;
        } else {
            $bulan = str_pad($bulan, 2, '0', STR_PAD_LEFT);
            $start_date = $tahun . '-' . $bulan . '-01';
            $end_date = date("Y-m-t", strtotime($start_date));
            $periode_str = "Bulan " . $bulan . " Tahun " . $tahun;
        }

        $conn = $this->db->getConnection();
        $sql = "SELECT a.kode_akun, a.akun, k.kategori,
                    SUM(IF(t.dk='Debit', t.nilai, 0)) AS total_debit,
                    SUM(IF(t.dk='Kredit', t.nilai, 0)) AS total_kredit,
                    (SUM(IF(t.dk='Kredit', t.nilai, 0)) - SUM(IF(t.dk='Debit', t.nilai, 0))) as saldo_pendapatan,
                    (SUM(IF(t.dk='Debit', t.nilai, 0)) - SUM(IF(t.dk='Kredit', t.nilai, 0))) as saldo_beban
                FROM akun a
                JOIN master_akun m ON a.id_kategori = m.id
                JOIN kategori k ON m.id_kategori = k.id
                LEFT JOIN transaksi t ON a.kode_akun = t.kode_akun
                LEFT JOIN detil_transaksi dt ON t.kode_transaksi = dt.kode_transaksi 
                    AND dt.tanggal >= ? AND dt.tanggal <= ? AND dt.status_verifikasi = 'sesuai'
                WHERE k.kategori IN ('Pendapatan', 'Beban')
                GROUP BY a.kode_akun, a.akun, k.kategori
                HAVING total_debit > 0 OR total_kredit > 0";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            ApiRouter::sendSuccess("Tidak ada data Laba Rugi untuk periode ini.");
        }

        $prompt = "Berikut adalah data Laba Rugi untuk periode $periode_str:\n\n";
        while($row = $result->fetch_assoc()) {
            $saldo = ($row['kategori'] == 'Pendapatan') ? $row['saldo_pendapatan'] : $row['saldo_beban'];
            $prompt .= $row['kategori'] . " | " . $row['kode_akun'] . " - " . $row['akun'] . " | Saldo: " . $saldo . "\n";
        }
        $prompt .= "\nTolong berikan analisis kinerja keuangan perusahaan dari data di atas (maks 3 paragraf). Apakah perusahaan untung/rugi? Apa penyumbang beban/pendapatan terbesar? Gunakan bahasa Indonesia.";

        $analysis = $this->doAnalysis($prompt, $apiKey);
        ApiRouter::sendSuccess($analysis);
    }

    public function neraca() {
        ApiRouter::requireRole($this->currentUser, ['admin', 'accountant']);
        $body = ApiRouter::getJsonBody();
        
        $tahun = $body['tahun'] ?? date('Y');
        $bulan = $body['bulan'] ?? '';
        $apiKey = $body['api_key'] ?? '';

        if (empty($bulan)) {
            $start_date = $tahun . '-01-01';
            $end_date = $tahun . '-12-31';
            $periode_str = "Tahun " . $tahun;
        } else {
            $bulan = str_pad($bulan, 2, '0', STR_PAD_LEFT);
            $start_date = $tahun . '-' . $bulan . '-01';
            $end_date = date("Y-m-t", strtotime($start_date));
            $periode_str = "Bulan " . $bulan . " Tahun " . $tahun;
        }

        $conn = $this->db->getConnection();
        $sql = "SELECT a.kode_akun, a.akun, k.kategori,
                    SUM(IF(t.dk='Debit', t.nilai, 0)) AS total_debit,
                    SUM(IF(t.dk='Kredit', t.nilai, 0)) AS total_kredit,
                    (SUM(IF(t.dk='Debit', t.nilai, 0)) - SUM(IF(t.dk='Kredit', t.nilai, 0))) as saldo_aset,
                    (SUM(IF(t.dk='Kredit', t.nilai, 0)) - SUM(IF(t.dk='Debit', t.nilai, 0))) as saldo_kewajiban_modal
                FROM akun a
                JOIN master_akun m ON a.id_kategori = m.id
                JOIN kategori k ON m.id_kategori = k.id
                LEFT JOIN transaksi t ON a.kode_akun = t.kode_akun
                LEFT JOIN detil_transaksi dt ON t.kode_transaksi = dt.kode_transaksi 
                    AND dt.tanggal <= ? AND dt.status_verifikasi = 'sesuai'
                WHERE k.kategori IN ('Aset', 'Kewajiban', 'Ekuitas')
                GROUP BY a.kode_akun, a.akun, k.kategori
                HAVING total_debit > 0 OR total_kredit > 0";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            ApiRouter::sendSuccess("Tidak ada data Neraca untuk periode ini.");
        }

        $prompt = "Berikut adalah saldo akun-akun Neraca (Aset, Kewajiban, Ekuitas) per $end_date:\n\n";
        while($row = $result->fetch_assoc()) {
            $saldo = ($row['kategori'] == 'Aset') ? $row['saldo_aset'] : $row['saldo_kewajiban_modal'];
            $prompt .= $row['kategori'] . " | " . $row['kode_akun'] . " - " . $row['akun'] . " | Saldo: " . $saldo . "\n";
        }
        $prompt .= "\nTolong berikan ulasan kesehatan finansial dari posisi neraca di atas (likuiditas, solvabilitas, permodalan) dalam maksimal 3 paragraf bahasa Indonesia.";

        $analysis = $this->doAnalysis($prompt, $apiKey);
        ApiRouter::sendSuccess($analysis);
    }
}
?>
