<?php

class JurnalModel extends Database {
    public function __construct() {
        parent::__construct();
    }

    // --- GET LIST DATA ---
    public function getAkunList() {
        $result = $this->query("SELECT * FROM akun ORDER BY kode_akun ASC");
        $list = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $list[] = $row;
            }
        }
        return $list;
    }

    public function getAsetList() {
        $result = $this->query("SELECT * FROM aset_tetap");
        $list = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $list[] = $row;
            }
        }
        return $list;
    }

    public function getPrepaidList() {
        $result = $this->query("SELECT * FROM manajemen_prepaid WHERE bulan_terpakai < lama_bulan");
        $list = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $list[] = $row;
            }
        }
        return $list;
    }

    // --- JURNAL UMUM ---
    public function getJurnalUmum($start_date, $end_date, $user_id = null) {
        $sql = "SELECT 
                    dt.tanggal,
                    dt.kode_transaksi,
                    dt.deskripsi,
                    dt.status_verifikasi,
                    dt.jenis_jurnal,
                    dt.sub_jenis,
                    t.kode_akun,
                    a.akun,
                    (IF(t.dk='Debit', t.nilai, 0)) as Debit, 
                    (IF(t.dk='Debit', 0, t.nilai)) as Kredit 
                FROM detil_transaksi dt 
                LEFT JOIN transaksi t ON dt.kode_transaksi=t.kode_transaksi
                LEFT JOIN akun a ON t.kode_akun = a.kode_akun
                WHERE dt.tanggal >= ? AND dt.tanggal <= ? AND dt.status_verifikasi = 'sesuai'";
        
        $bind_types = "ss";
        $bind_params = [$start_date, $end_date];

        if ($user_id !== null) {
            $sql .= " AND dt.created_by = ?";
            $bind_types .= "i";
            $bind_params[] = $user_id;
        }

        $sql .= " ORDER BY dt.tanggal ASC, dt.kode_transaksi ASC, t.dk ASC";

        $stmt = $this->prepare($sql);
        $stmt->bind_param($bind_types, ...$bind_params);
        $stmt->execute();
        $result = $stmt->get_result();

        $list = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $list[] = $row;
            }
        }
        return $list;
    }

    // --- JURNAL PENYESUAIAN ---
    public function addJurnalPenyesuaian($tanggal, $deskripsi, $jenis_penyesuaian, $is_reversing, $akun_debet, $akun_kredit, $nilai, $referensi_id = '', $created_by = null) {
        $this->begin_transaction();
        try {
            $new_kode = "ADJ-001";
            $q_max = $this->query("SELECT MAX(CAST(SUBSTRING(kode_transaksi, 5) AS UNSIGNED)) as max_kode FROM detil_transaksi WHERE kode_transaksi LIKE 'ADJ-%' FOR UPDATE");
            if ($q_max && $r_max = $q_max->fetch_assoc()) {
                if (!empty($r_max['max_kode'])) {
                    $next_num = $r_max['max_kode'] + 1;
                    $new_kode = "ADJ-" . str_pad($next_num, 3, "0", STR_PAD_LEFT);
                }
            }
            
            $stmt = $this->prepare("INSERT INTO detil_transaksi (kode_transaksi, tanggal, deskripsi, status_verifikasi, jenis_jurnal, sub_jenis, is_reversing, created_by) VALUES (?, ?, ?, 'sesuai', 'penyesuaian', ?, ?, ?)");
            $stmt->bind_param("ssssii", $new_kode, $tanggal, $deskripsi, $jenis_penyesuaian, $is_reversing, $created_by);
            $stmt->execute();
            
            $stmt_t = $this->prepare("INSERT INTO transaksi (kode_transaksi, kode_akun, dk, nilai) VALUES (?, ?, 'Debit', ?)");
            $stmt_t->bind_param("ssd", $new_kode, $akun_debet, $nilai);
            $stmt_t->execute();

            $stmt_t2 = $this->prepare("INSERT INTO transaksi (kode_transaksi, kode_akun, dk, nilai) VALUES (?, ?, 'Kredit', ?)");
            $stmt_t2->bind_param("ssd", $new_kode, $akun_kredit, $nilai);
            $stmt_t2->execute();
            
            // UPDATE referensi
            if (!empty($referensi_id)) {
                $ref_month = date('Y-m', strtotime($tanggal));
                if ($jenis_penyesuaian === 'prepaid_expense') {
                    $upd = $this->prepare("UPDATE manajemen_prepaid SET last_adjusted_month = ?, bulan_terpakai = bulan_terpakai + 1 WHERE id = ?");
                    $upd->bind_param("si", $ref_month, $referensi_id);
                    $upd->execute();
                } else if ($jenis_penyesuaian === 'penyusutan') {
                    $upd = $this->prepare("UPDATE aset_tetap SET last_adjusted_month = ? WHERE id = ?");
                    $upd->bind_param("si", $ref_month, $referensi_id);
                    $upd->execute();
                }
            }

            $this->commit();
            return ['status' => true, 'kode' => $new_kode, 'is_reversing' => $is_reversing];
        } catch (Exception $e) {
            $this->rollback();
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    // --- JURNAL PEMBALIK ---
    public function getPembalikPending() {
        $sql = "SELECT
                    dt.kode_transaksi,
                    dt.tanggal,
                    dt.deskripsi,
                    dt.sub_jenis,
                    DATE_FORMAT(DATE_ADD(LAST_DAY(dt.tanggal), INTERVAL 1 DAY), '%d %b %Y') AS tgl_pembalik,
                    SUM(IF(t.dk='Debit', t.nilai, 0))  AS total_debet,
                    SUM(IF(t.dk='Kredit', t.nilai, 0)) AS total_kredit
                FROM detil_transaksi dt
                LEFT JOIN transaksi t ON t.kode_transaksi = dt.kode_transaksi
                WHERE dt.is_reversing = 1 AND dt.reversed_at IS NULL
                GROUP BY dt.kode_transaksi
                ORDER BY dt.tanggal DESC";
        $result = $this->query($sql);
        $list = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $list[] = $row;
            }
        }
        return $list;
    }

    public function getPembalikDone() {
        $sql = "SELECT
                    dt.kode_transaksi,
                    dt.tanggal,
                    dt.deskripsi,
                    dt.sub_jenis,
                    dt.reversed_at,
                    dt.reversed_jurnal_kode,
                    SUM(IF(t.dk='Debit', t.nilai, 0))  AS total_debet,
                    SUM(IF(t.dk='Kredit', t.nilai, 0)) AS total_kredit
                FROM detil_transaksi dt
                LEFT JOIN transaksi t ON t.kode_transaksi = dt.kode_transaksi
                WHERE dt.is_reversing = 1 AND dt.reversed_at IS NOT NULL
                GROUP BY dt.kode_transaksi
                ORDER BY dt.reversed_at DESC
                LIMIT 50";
        $result = $this->query($sql);
        $list = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $list[] = $row;
            }
        }
        return $list;
    }

    public function prosesPembalikManual($kode_asal) {
        $this->begin_transaction();
        try {
            // Ambil header jurnal asli
            $stmt = $this->prepare(
                "SELECT kode_transaksi, tanggal, deskripsi
                 FROM detil_transaksi
                 WHERE kode_transaksi = ?
                   AND is_reversing = 1
                   AND reversed_at IS NULL
                 LIMIT 1"
            );
            $stmt->bind_param('s', $kode_asal);
            $stmt->execute();
            $res  = $stmt->get_result();
            $orig = $res->fetch_assoc();

            if (!$orig) {
                throw new Exception('Jurnal tidak ditemukan, sudah dibalik, atau tidak bersyarat reversing.');
            }

            // Generate kode REV baru
            $q_max = $this->query(
                "SELECT IFNULL(MAX(CAST(SUBSTRING(kode_transaksi,5) AS UNSIGNED)),0)+1 AS next_num
                 FROM detil_transaksi WHERE kode_transaksi LIKE 'REV-%'"
            );
            $r_max   = $q_max->fetch_assoc();
            $new_kode = 'REV-' . str_pad($r_max['next_num'], 3, '0', STR_PAD_LEFT);

            // Tanggal pembalik = 1 hari pertama bulan berikutnya
            $tgl_pembalik = date('Y-m-01', strtotime($orig['tanggal'] . ' +1 month'));

            // Validasi: Pastikan periode akuntansi target untuk jurnal pembalik belum ditutup
            $closed_msg = is_periode_closed($tgl_pembalik, $this);
            if (!empty($closed_msg)) {
                throw new Exception("Gagal membuat Jurnal Pembalik: " . $closed_msg . " (Target Tanggal: {$tgl_pembalik})");
            }

            // Insert header jurnal pembalik
            $ins_hdr = $this->prepare(
                "INSERT INTO detil_transaksi
                    (kode_transaksi, tanggal, deskripsi, status_verifikasi, jenis_jurnal, sub_jenis, is_reversing)
                 VALUES (?, ?, ?, 'sesuai', 'penyesuaian', 'jurnal_pembalik', 0)"
            );
            $desc_new = '[PEMBALIK] ' . $orig['deskripsi'];
            $ins_hdr->bind_param('sss', $new_kode, $tgl_pembalik, $desc_new);
            $ins_hdr->execute();

            // Insert detail (swap debit ↔ kredit)
            $ins_dtl = $this->prepare(
                "INSERT INTO transaksi (kode_transaksi, kode_akun, dk, nilai)
                 SELECT ?, kode_akun,
                        CASE dk WHEN 'Debit' THEN 'Kredit' WHEN 'Kredit' THEN 'Debit' ELSE dk END,
                        nilai
                 FROM transaksi
                 WHERE kode_transaksi = ?"
            );
            $ins_dtl->bind_param('ss', $new_kode, $kode_asal);
            $ins_dtl->execute();

            // Update jurnal asli
            $upd = $this->prepare(
                "UPDATE detil_transaksi
                 SET reversed_at = NOW(), reversed_jurnal_kode = ?
                 WHERE kode_transaksi = ?"
            );
            $upd->bind_param('ss', $new_kode, $kode_asal);
            $upd->execute();

            $this->commit();
            return ['status' => true, 'kode' => $new_kode, 'tanggal' => $tgl_pembalik];
        } catch (Exception $e) {
            $this->rollback();
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }
}
