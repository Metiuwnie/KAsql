<?php
class DashboardModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAvailableYears() {
        $years = [];
        $res = $this->db->query("SELECT DISTINCT YEAR(tanggal) as tahun FROM detil_transaksi WHERE status_verifikasi = 'sesuai' ORDER BY tahun DESC");
        if ($res && $res->num_rows > 0) {
            while($row = $res->fetch_assoc()) {
                $years[] = $row['tahun'];
            }
        }
        return $years;
    }

    public function getTotalAset($year) {
        $sql = "SELECT SUM(IF(t.dk='Debit', t.nilai, 0)) - SUM(IF(t.dk='Kredit', t.nilai, 0)) as total FROM transaksi t JOIN akun a ON t.kode_akun=a.kode_akun JOIN detil_transaksi dt ON t.kode_transaksi=dt.kode_transaksi WHERE a.aktiva_pasiva='A' AND YEAR(dt.tanggal) <= ? AND dt.status_verifikasi = 'sesuai'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $year);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    }

    public function getTotalKewajiban($year) {
        $sql = "SELECT SUM(IF(t.dk='Kredit', t.nilai, 0)) - SUM(IF(t.dk='Debit', t.nilai, 0)) as total FROM transaksi t JOIN akun a ON t.kode_akun=a.kode_akun JOIN detil_transaksi dt ON t.kode_transaksi=dt.kode_transaksi WHERE a.aktiva_pasiva='P' AND a.kategori_neraca LIKE 'Utang%' AND YEAR(dt.tanggal) <= ? AND dt.status_verifikasi = 'sesuai'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $year);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    }

    public function getTotalPendapatan($year) {
        $sql = "SELECT SUM(IF(t.dk='Kredit', t.nilai, 0)) - SUM(IF(t.dk='Debit', t.nilai, 0)) as total FROM transaksi t JOIN akun a ON t.kode_akun=a.kode_akun JOIN detil_transaksi dt ON t.kode_transaksi=dt.kode_transaksi WHERE a.kategori_neraca = 'Pendapatan' AND YEAR(dt.tanggal) = ? AND dt.status_verifikasi = 'sesuai'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $year);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    }

    public function getTotalBeban($year) {
        $sql = "SELECT SUM(IF(t.dk='Debit', t.nilai, 0)) - SUM(IF(t.dk='Kredit', t.nilai, 0)) as total FROM transaksi t JOIN akun a ON t.kode_akun=a.kode_akun JOIN detil_transaksi dt ON t.kode_transaksi=dt.kode_transaksi WHERE a.kategori_neraca = 'Beban' AND YEAR(dt.tanggal) = ? AND dt.status_verifikasi = 'sesuai'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $year);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    }

    public function getMonthlyChartData($year) {
        $sql = "SELECT 
                    MONTH(dt.tanggal) as bulan, 
                    SUM(IF(a.kategori_neraca='Pendapatan', IF(t.dk='Kredit', t.nilai, -t.nilai), 0)) as pendapatan, 
                    SUM(IF(a.kategori_neraca='Beban', IF(t.dk='Debit', t.nilai, -t.nilai), 0)) as beban 
                FROM detil_transaksi dt 
                JOIN transaksi t ON dt.kode_transaksi=t.kode_transaksi 
                JOIN akun a ON t.kode_akun=a.kode_akun 
                WHERE YEAR(dt.tanggal) = ? AND dt.status_verifikasi = 'sesuai'
                GROUP BY bulan ORDER BY bulan ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $year);
        $stmt->execute();
        $res = $stmt->get_result();

        $chart_pendapatan = array_fill(0, 12, 0);
        $chart_beban = array_fill(0, 12, 0);

        while($row = $res->fetch_assoc()) {
            $idx = $row['bulan'] - 1;
            $chart_pendapatan[$idx] = max(0, $row['pendapatan']);
            $chart_beban[$idx] = max(0, $row['beban']);
        }

        return ['pendapatan' => $chart_pendapatan, 'beban' => $chart_beban];
    }

    public function getAssetComposition($year) {
        $sql = "SELECT a.akun, (SUM(IF(t.dk='Debit', t.nilai, 0)) - SUM(IF(t.dk='Kredit', t.nilai, 0))) as saldo 
                  FROM transaksi t JOIN akun a ON t.kode_akun=a.kode_akun 
                  JOIN detil_transaksi dt ON t.kode_transaksi=dt.kode_transaksi
                  WHERE a.aktiva_pasiva='A' AND YEAR(dt.tanggal) <= ? AND dt.status_verifikasi = 'sesuai'
                  GROUP BY a.akun HAVING saldo > 0 ORDER BY saldo DESC LIMIT 5";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $year);
        $stmt->execute();
        $res = $stmt->get_result();

        $labels = [];
        $data = [];
        while($row = $res->fetch_assoc()) {
            $labels[] = $row['akun'];
            $data[] = $row['saldo'];
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function getDailyTransactionTrend($year, $month) {
        $sql = "SELECT 
                    DAY(dt.tanggal) as hari,
                    COUNT(DISTINCT dt.kode_transaksi) as total_transaksi,
                    SUM(IF(a.kategori_neraca='Pendapatan', IF(t.dk='Kredit', t.nilai, 0), 0)) as pendapatan,
                    SUM(IF(a.kategori_neraca='Beban', IF(t.dk='Debit', t.nilai, 0), 0)) as beban,
                    SUM(t.nilai) / 2 as total_volume
                FROM detil_transaksi dt 
                JOIN transaksi t ON dt.kode_transaksi=t.kode_transaksi 
                JOIN akun a ON t.kode_akun=a.kode_akun 
                WHERE YEAR(dt.tanggal) = ? AND MONTH(dt.tanggal) = ? AND dt.status_verifikasi = 'sesuai'
                GROUP BY hari ORDER BY hari ASC";
        $stmt = $this->db->prepare($sql);
        $month_int = (int)$month;
        $stmt->bind_param("si", $year, $month_int);
        $stmt->execute();
        $res = $stmt->get_result();

        $daysInMonth = (int)date('t', strtotime("$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01"));
        $days = [];
        $volume = [];
        $pendapatan = [];
        $beban = [];
        $trx_count = [];

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $days[$d] = str_pad($d, 2, '0', STR_PAD_LEFT);
            $volume[$d] = 0;
            $pendapatan[$d] = 0;
            $beban[$d] = 0;
            $trx_count[$d] = 0;
        }

        while ($row = $res->fetch_assoc()) {
            $h = (int)$row['hari'];
            if (isset($days[$h])) {
                $volume[$h] = floatval($row['total_volume']);
                $pendapatan[$h] = floatval($row['pendapatan']);
                $beban[$h] = floatval($row['beban']);
                $trx_count[$h] = intval($row['total_transaksi']);
            }
        }

        $nama_bulan = [
            '01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April',
            '05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus',
            '09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'
        ];
        $pad_m = str_pad($month, 2, '0', STR_PAD_LEFT);

        return [
            'days' => array_values($days),
            'volume' => array_values($volume),
            'pendapatan' => array_values($pendapatan),
            'beban' => array_values($beban),
            'trx_count' => array_values($trx_count),
            'month_num' => $pad_m,
            'month_name' => $nama_bulan[$pad_m] ?? 'Bulan ' . $pad_m,
            'total_volume_bulan' => array_sum($volume),
            'total_trx_bulan' => array_sum($trx_count)
        ];
    }
}
