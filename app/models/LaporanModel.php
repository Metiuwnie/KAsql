<?php

class LaporanModel extends Database {
    public function __construct() {
        parent::__construct();
    }

    public function getDaftarAkun() {
        $semua_akun = [];
        $res = $this->query("SELECT kode_akun, akun FROM akun ORDER BY kode_akun ASC");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $semua_akun[$row['kode_akun']] = $row['akun'];
            }
        }
        return $semua_akun;
    }

    // --- BUKU BESAR ---
    public function getSaldoAwalBukuBesar($akun_list, $start_date) {
        $saldo_awals = [];
        foreach($akun_list as $sa) $saldo_awals[$sa] = 0;

        if (empty($akun_list)) return $saldo_awals;

        $in_placeholders = implode(',', array_fill(0, count($akun_list), '?'));
        $types_in = str_repeat('s', count($akun_list));

        $sql = "SELECT t.kode_akun, 
             SUM(
                CASE 
                    WHEN LOWER(t.dk) = 'debit' AND SUBSTRING(t.kode_akun, 1, 1) IN ('1', '5') AND t.kode_akun != '106' THEN t.nilai
                    WHEN LOWER(t.dk) = 'debit' AND (SUBSTRING(t.kode_akun, 1, 1) NOT IN ('1', '5') OR t.kode_akun = '106') THEN -t.nilai
                    WHEN LOWER(t.dk) = 'kredit' AND SUBSTRING(t.kode_akun, 1, 1) IN ('1', '5') AND t.kode_akun != '106' THEN -t.nilai
                    WHEN LOWER(t.dk) = 'kredit' AND (SUBSTRING(t.kode_akun, 1, 1) NOT IN ('1', '5') OR t.kode_akun = '106') THEN t.nilai
                    ELSE 0 
                END
             ) as saldo_awal_calculated
             FROM transaksi t 
             JOIN detil_transaksi dt ON t.kode_transaksi = dt.kode_transaksi 
             WHERE dt.tanggal < ? AND t.kode_akun IN ($in_placeholders) AND dt.status_verifikasi = 'sesuai'
             GROUP BY t.kode_akun";
        
        $stmt = $this->prepare($sql);
        $params = array_merge([$start_date], $akun_list);
        $bind_types = 's' . $types_in;
        $stmt->bind_param($bind_types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $saldo_awals[$row['kode_akun']] = floatval($row['saldo_awal_calculated']);
        }
        return $saldo_awals;
    }

    public function getTransaksiBukuBesar($akun_list, $start_date, $end_date) {
        $transactions = [];
        foreach($akun_list as $sa) $transactions[$sa] = [];
        if (empty($akun_list)) return $transactions;

        $in_placeholders = implode(',', array_fill(0, count($akun_list), '?'));
        $types_in = str_repeat('s', count($akun_list));

        $sql = "SELECT dt.tanggal, dt.kode_transaksi, dt.deskripsi, t.kode_akun, t.dk, t.nilai 
                    FROM transaksi t 
                    JOIN detil_transaksi dt ON t.kode_transaksi = dt.kode_transaksi 
                    WHERE t.kode_akun IN ($in_placeholders) AND dt.tanggal >= ? AND dt.tanggal <= ? AND dt.status_verifikasi = 'sesuai'
                    ORDER BY t.kode_akun ASC, dt.tanggal ASC, dt.kode_transaksi ASC";
        
        $params = $akun_list;
        $params[] = $start_date;
        $params[] = $end_date;
        $bind_types = $types_in . 'ss';

        $stmt = $this->prepare($sql);
        $stmt->bind_param($bind_types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $transactions[$row['kode_akun']][] = $row;
        }
        return $transactions;
    }

    // --- LABA RUGI ---
    public function getLabaRugi($start_date, $end_date) {
        $sql = "SELECT a.kode_akun, a.akun, IFNULL(bal.total_nilai, 0) as total_nilai
                FROM akun a
                LEFT JOIN (
                    SELECT t.kode_akun,
                    SUM(
                        CASE 
                            WHEN SUBSTRING(t.kode_akun, 1, 1) = '4' THEN
                                (CASE WHEN LOWER(t.dk) = 'kredit' THEN t.nilai ELSE -t.nilai END)
                            WHEN SUBSTRING(t.kode_akun, 1, 1) = '5' THEN
                                (CASE WHEN LOWER(t.dk) = 'debit' THEN t.nilai ELSE -t.nilai END)
                            ELSE 0
                        END
                    ) as total_nilai
                    FROM transaksi t
                    JOIN detil_transaksi dt ON t.kode_transaksi = dt.kode_transaksi
                    WHERE dt.tanggal >= ? AND dt.tanggal <= ? AND dt.status_verifikasi = 'sesuai'
                    GROUP BY t.kode_akun
                ) bal ON a.kode_akun = bal.kode_akun
                WHERE SUBSTRING(a.kode_akun, 1, 1) IN ('4', '5')
                ORDER BY a.kode_akun ASC";

        $stmt = $this->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $data = [];
        while ($row = $res->fetch_assoc()) {
            if ($row['total_nilai'] != 0) {
                $data[] = $row;
            }
        }
        return $data;
    }

    // --- NERACA SALDO ---
    public function getNeracaSaldo($start_date, $end_date) {
        $sql = "
            SELECT 
                a.kode_akun, 
                a.akun, 
                a.kategori_neraca,
                COALESCE(m.mutasi_debet, 0) as mutasi_debet,
                COALESCE(m.mutasi_kredit, 0) as mutasi_kredit
            FROM akun a
            LEFT JOIN (
                SELECT 
                    t.kode_akun,
                    SUM(CASE WHEN LOWER(t.dk) = 'debit' THEN t.nilai ELSE 0 END) as mutasi_debet,
                    SUM(CASE WHEN LOWER(t.dk) = 'kredit' THEN t.nilai ELSE 0 END) as mutasi_kredit
                FROM transaksi t
                JOIN detil_transaksi dt ON t.kode_transaksi = dt.kode_transaksi
                WHERE dt.status_verifikasi = 'sesuai' 
                  AND dt.tanggal >= ? 
                  AND dt.tanggal <= ?
                GROUP BY t.kode_akun
            ) m ON a.kode_akun = m.kode_akun
            ORDER BY a.kode_akun ASC
        ";
        
        $stmt = $this->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $data = [];
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    // --- NERACA ---
    public function getNeraca($end_date) {
        $sql = "SELECT a.kode_akun, a.akun, a.kategori_neraca, a.aktiva_pasiva, IFNULL(bal.final_balance, 0) as final_balance 
                FROM akun a
                LEFT JOIN (
                    SELECT t.kode_akun,
                    SUM(
                        CASE 
                            WHEN LOWER(t.dk) = 'debit' AND SUBSTRING(t.kode_akun, 1, 1) IN ('1', '5') THEN t.nilai
                            WHEN LOWER(t.dk) = 'debit' AND SUBSTRING(t.kode_akun, 1, 1) NOT IN ('1', '5') THEN -t.nilai
                            WHEN LOWER(t.dk) = 'kredit' AND SUBSTRING(t.kode_akun, 1, 1) IN ('1', '5') THEN -t.nilai
                            WHEN LOWER(t.dk) = 'kredit' AND SUBSTRING(t.kode_akun, 1, 1) NOT IN ('1', '5') THEN t.nilai
                            ELSE 0 
                        END
                    ) as final_balance 
                    FROM transaksi t
                    JOIN detil_transaksi dt ON t.kode_transaksi = dt.kode_transaksi
                    WHERE dt.tanggal <= ? AND dt.status_verifikasi = 'sesuai'
                    GROUP BY t.kode_akun
                ) bal ON a.kode_akun = bal.kode_akun
                WHERE SUBSTRING(a.kode_akun, 1, 1) IN ('1', '2', '3')
                ORDER BY a.kode_akun ASC";
                
        $stmt = $this->prepare($sql);
        $stmt->bind_param("s", $end_date);
        $stmt->execute();
        $res = $stmt->get_result();

        $data = [];
        while ($row = $res->fetch_assoc()) {
            if (abs($row['final_balance']) >= 0.01) {
                $data[] = $row;
            }
        }
        return $data;
    }

    public function getNetIncomeForNeraca($end_date) {
        $sql = "SELECT SUM(
                        CASE 
                            WHEN LOWER(t.dk) = 'debit' THEN t.nilai
                            WHEN LOWER(t.dk) = 'kredit' THEN -t.nilai
                            ELSE 0 
                        END
                    ) as net_income_dr_cr 
                   FROM transaksi t
                   JOIN detil_transaksi dt ON t.kode_transaksi = dt.kode_transaksi
                   WHERE SUBSTRING(t.kode_akun, 1, 1) IN ('4', '5') AND dt.tanggal <= ? AND dt.status_verifikasi = 'sesuai'";
        $stmt = $this->prepare($sql);
        $stmt->bind_param("s", $end_date);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        return floatval($row['net_income_dr_cr']);
    }
}
