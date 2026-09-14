<?php
/**
 * API Controller: Closing Periode
 * Handles fetching accounting periods.
 */

class ClosingApi {
    private $currentUser = null;
    
    public function setCurrentUser($user) {
        $this->currentUser = $user;
    }
    
    /**
     * GET /api/v1/closing
     */
    public function index() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $db = new Database();
            $data = [];
            $res = $db->query("SELECT * FROM periode_akuntansi ORDER BY tanggal_mulai DESC");
            if ($res) {
                while($row = $res->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            ApiRouter::sendSuccess($data);
        } else {
            ApiRouter::sendError('Method not allowed', 405);
        }
    }

    /**
     * POST /api/v1/closing/close
     */
    public function close() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiRouter::sendError('Method not allowed', 405);
        }

        ApiRouter::requireRole($this->currentUser, ['admin']);
        $body = ApiRouter::getJsonBody();
        $periode_id = intval($body['periode_id'] ?? 0);
        
        if ($periode_id <= 0) {
            ApiRouter::sendError('ID Periode tidak valid.', 400);
        }

        $db = new Database();
        $conn = $db->getConnection();
        $user_id = $this->currentUser['id'] ?? 0;

        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("SELECT * FROM periode_akuntansi WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $periode_id);
            $stmt->execute();
            $periode = $stmt->get_result()->fetch_assoc();
            
            if (!$periode) {
                throw new Exception("Periode tidak ditemukan.");
            }
            
            if ($periode['status'] === 'closed') {
                throw new Exception("Periode sudah ditutup sebelumnya.");
            }

            if (strtotime($periode['tanggal_selesai']) <= strtotime('2026-05-31')) {
                throw new Exception("Periode Mei 2026 ke bawah dikunci oleh sistem dan tidak dapat ditutup.");
            }
            
            $start_date = $periode['tanggal_mulai'];
            $end_date = $periode['tanggal_selesai'];
            
            $stmt_check = $conn->prepare("SELECT COUNT(*) as cnt FROM detil_transaksi WHERE tanggal >= ? AND tanggal <= ? AND status_verifikasi = 'pending'");
            $stmt_check->bind_param("ss", $start_date, $end_date);
            $stmt_check->execute();
            $pending_trx = $stmt_check->get_result()->fetch_assoc()['cnt'];
            
            if ($pending_trx > 0) {
                throw new Exception("Masih ada $pending_trx transaksi pending yang belum diverifikasi.");
            }
            
            // Perhitungan Pendapatan
            $sql_p = "SELECT IFNULL(SUM(
                CASE 
                    WHEN LOWER(t.dk) = 'kredit' THEN t.nilai 
                    ELSE -t.nilai 
                END
            ), 0) as total
            FROM transaksi t
            JOIN detil_transaksi dt ON t.kode_transaksi = dt.kode_transaksi
            WHERE SUBSTRING(t.kode_akun, 1, 1) = '4' 
              AND dt.tanggal >= ? AND dt.tanggal <= ? 
              AND dt.status_verifikasi = 'sesuai'";
            $stmt_p = $conn->prepare($sql_p);
            $stmt_p->bind_param("ss", $start_date, $end_date);
            $stmt_p->execute();
            $pendapatan = floatval($stmt_p->get_result()->fetch_assoc()['total']);
            
            // Perhitungan Beban
            $sql_b = "SELECT IFNULL(SUM(
                CASE 
                    WHEN LOWER(t.dk) = 'debit' THEN t.nilai 
                    ELSE -t.nilai 
                END
            ), 0) as total
            FROM transaksi t
            JOIN detil_transaksi dt ON t.kode_transaksi = dt.kode_transaksi
            WHERE SUBSTRING(t.kode_akun, 1, 1) = '5' 
              AND dt.tanggal >= ? AND dt.tanggal <= ? 
              AND dt.status_verifikasi = 'sesuai'";
            $stmt_b = $conn->prepare($sql_b);
            $stmt_b->bind_param("ss", $start_date, $end_date);
            $stmt_b->execute();
            $beban = floatval($stmt_b->get_result()->fetch_assoc()['total']);
            
            $laba = $pendapatan - $beban;
            
            // Simpan Snapshot Saldo
            $sql_saldo = "SELECT a.kode_akun, a.akun,
                IFNULL(m.total_debit, 0) as total_debit,
                IFNULL(m.total_kredit, 0) as total_kredit
            FROM akun a
            LEFT JOIN (
                SELECT 
                    t.kode_akun,
                    SUM(CASE WHEN LOWER(t.dk) = 'debit' THEN t.nilai ELSE 0 END) as total_debit,
                    SUM(CASE WHEN LOWER(t.dk) = 'kredit' THEN t.nilai ELSE 0 END) as total_kredit
                FROM transaksi t
                JOIN detil_transaksi dt ON t.kode_transaksi = dt.kode_transaksi
                WHERE dt.tanggal <= ? AND dt.status_verifikasi = 'sesuai'
                GROUP BY t.kode_akun
            ) m ON a.kode_akun = m.kode_akun
            ORDER BY a.kode_akun ASC";
            
            $stmt_saldo = $conn->prepare($sql_saldo);
            $stmt_saldo->bind_param("s", $end_date);
            $stmt_saldo->execute();
            $res_saldo = $stmt_saldo->get_result();
            
            $insert_snapshot = $conn->prepare("INSERT INTO saldo_akun_per_periode 
                (periode_id, akun_id, kode_akun, nama_akun, saldo_debet, saldo_kredit, versi, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, 1, 1)");
            
            while ($row = $res_saldo->fetch_assoc()) {
                $kode = $row['kode_akun'];
                $nama = $row['akun'];
                $debit = $row['total_debit'];
                $kredit = $row['total_kredit'];
                
                if ($debit > 0 || $kredit > 0) {
                    $insert_snapshot->bind_param("isssdd", 
                        $periode_id, $kode, $kode, $nama, $debit, $kredit
                    );
                    $insert_snapshot->execute();
                }
            }
            
            // Lock Periode
            $stmt_lock = $conn->prepare("UPDATE periode_akuntansi SET status = 'closed', tanggal_closed = NOW(), closed_by = ?, laba_periode = ? WHERE id = ?");
            $stmt_lock->bind_param("idi", $user_id, $laba, $periode_id);
            $stmt_lock->execute();
            
            // Logging
            $stmt_any = $conn->prepare("SELECT COUNT(*) as cnt FROM detil_transaksi WHERE tanggal >= ? AND tanggal <= ?");
            $stmt_any->bind_param("ss", $start_date, $end_date);
            $stmt_any->execute();
            $total_trx = $stmt_any->get_result()->fetch_assoc()['cnt'];

            $detail_log = json_encode([
                'periode' => $periode['nama_periode'],
                'tanggal_mulai' => $start_date,
                'tanggal_selesai' => $end_date,
                'total_transaksi' => $total_trx,
                'pendapatan' => $pendapatan,
                'beban' => $beban,
                'laba' => $laba
            ], JSON_UNESCAPED_UNICODE);
            
            $stmt_log = $conn->prepare("INSERT INTO activity_log (user_id, aksi, detail) VALUES (?, 'CLOSE_PERIODE', ?)");
            $stmt_log->bind_param("is", $user_id, $detail_log);
            $stmt_log->execute();
            
            $conn->commit();
            
            ApiRouter::sendSuccess([
                'message' => 'Periode berhasil ditutup.',
                'laba' => $laba
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            ApiRouter::sendError($e->getMessage(), 400);
        }
    }
}
?>
