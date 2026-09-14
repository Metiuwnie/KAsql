<?php

class Closing extends Controller {
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

    public function index() {
        $db = new Database();
        $conn = $db->getConnection();
        
        $error = '';
        $success = '';
        $user_id = $_SESSION['user_id'] ?? 0;
        $user_role = $_SESSION['user_role'] ?? '';

        // ============================================================
        // AJAX: Get preview data for confirmation modal
        // ============================================================
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_preview') {
            header('Content-Type: application/json');
            $periode_id = intval($_GET['periode_id'] ?? 0);
            
            if ($periode_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID periode tidak valid.']);
                exit;
            }
            
            // Get periode info
            $stmt = $conn->prepare("SELECT * FROM periode_akuntansi WHERE id = ?");
            $stmt->bind_param("i", $periode_id);
            $stmt->execute();
            $periode = $stmt->get_result()->fetch_assoc();
            
            if (!$periode) {
                echo json_encode(['success' => false, 'message' => 'Periode tidak ditemukan.']);
                exit;
            }
            
            if ($periode['status'] !== 'open' && $periode['status'] !== 'reopened') {
                echo json_encode(['success' => false, 'message' => 'Periode sudah ditutup.']);
                exit;
            }
            
            if (strtotime($periode['tanggal_selesai']) <= strtotime('2026-05-31')) {
                echo json_encode(['success' => false, 'message' => 'Periode Mei 2026 ke bawah dikunci oleh sistem.']);
                exit;
            }
            
            $start_date = $periode['tanggal_mulai'];
            $end_date = $periode['tanggal_selesai'];
            
            // Count total transactions in period
            $stmt_total = $conn->prepare("SELECT COUNT(*) as total FROM detil_transaksi WHERE tanggal >= ? AND tanggal <= ?");
            $stmt_total->bind_param("ss", $start_date, $end_date);
            $stmt_total->execute();
            $total_trx = $stmt_total->get_result()->fetch_assoc()['total'];
            
            // Count transactions with status 'sesuai'
            $stmt_sesuai = $conn->prepare("SELECT COUNT(*) as total FROM detil_transaksi WHERE tanggal >= ? AND tanggal <= ? AND status_verifikasi = 'sesuai'");
            $stmt_sesuai->bind_param("ss", $start_date, $end_date);
            $stmt_sesuai->execute();
            $total_sesuai = $stmt_sesuai->get_result()->fetch_assoc()['total'];
            
            // Count pending
            $stmt_pending = $conn->prepare("SELECT COUNT(*) as total FROM detil_transaksi WHERE tanggal >= ? AND tanggal <= ? AND status_verifikasi = 'pending'");
            $stmt_pending->bind_param("ss", $start_date, $end_date);
            $stmt_pending->execute();
            $total_pending = $stmt_pending->get_result()->fetch_assoc()['total'];
            
            // Count koreksi
            $stmt_koreksi = $conn->prepare("SELECT COUNT(*) as total FROM detil_transaksi WHERE tanggal >= ? AND tanggal <= ? AND status_verifikasi = 'koreksi'");
            $stmt_koreksi->bind_param("ss", $start_date, $end_date);
            $stmt_koreksi->execute();
            $total_koreksi = $stmt_koreksi->get_result()->fetch_assoc()['total'];
            
            $total_aktif = $total_sesuai + $total_pending;
            $persen_sesuai = ($total_aktif > 0) ? round(($total_sesuai / $total_aktif) * 100, 1) : 100;
            
            // Calculate Pendapatan
            $sql_pendapatan = "SELECT SUM(
                CASE 
                    WHEN LOWER(t.dk) = 'kredit' THEN t.nilai 
                    ELSE -t.nilai 
                END
            ) as total
            FROM transaksi t
            JOIN detil_transaksi dt ON t.kode_transaksi = dt.kode_transaksi
            WHERE SUBSTRING(t.kode_akun, 1, 1) = '4' 
              AND dt.tanggal >= ? AND dt.tanggal <= ? 
              AND dt.status_verifikasi = 'sesuai'";
            $stmt_p = $conn->prepare($sql_pendapatan);
            $stmt_p->bind_param("ss", $start_date, $end_date);
            $stmt_p->execute();
            $pendapatan = floatval($stmt_p->get_result()->fetch_assoc()['total'] ?? 0);
            
            // Calculate Beban
            $sql_beban = "SELECT SUM(
                CASE 
                    WHEN LOWER(t.dk) = 'debit' THEN t.nilai 
                    ELSE -t.nilai 
                END
            ) as total
            FROM transaksi t
            JOIN detil_transaksi dt ON t.kode_transaksi = dt.kode_transaksi
            WHERE SUBSTRING(t.kode_akun, 1, 1) = '5' 
              AND dt.tanggal >= ? AND dt.tanggal <= ? 
              AND dt.status_verifikasi = 'sesuai'";
            $stmt_b = $conn->prepare($sql_beban);
            $stmt_b->bind_param("ss", $start_date, $end_date);
            $stmt_b->execute();
            $beban = floatval($stmt_b->get_result()->fetch_assoc()['total'] ?? 0);
            
            $laba = $pendapatan - $beban;
            
            echo json_encode([
                'success' => true,
                'periode' => $periode,
                'total_transaksi' => $total_trx,
                'total_sesuai' => $total_sesuai,
                'total_pending' => $total_pending,
                'total_koreksi' => $total_koreksi,
                'persen_sesuai' => $persen_sesuai,
                'pendapatan' => $pendapatan,
                'beban' => $beban,
                'laba' => $laba
            ]);
            exit;
        }

        // ============================================================
        // POST: Generate Periode for a Year
        // ============================================================
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_periode') {
            csrf_verify();
            if ($user_role !== 'admin') {
                $error = "Akses ditolak: Hanya Administrator yang berhak membuat periode akuntansi.";
            } else {
                $tahun = intval($_POST['tahun'] ?? 0);
                
                if ($tahun < 2020 || $tahun > 2099) {
                    $error = "Tahun tidak valid.";
                } else {
                    $nama_bulan = [
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ];
                    
                    $inserted = 0;
                    $skipped = 0;
                    
                    for ($m = 1; $m <= 12; $m++) {
                        $nama = $nama_bulan[$m] . ' ' . $tahun;
                        $tgl_mulai = sprintf('%04d-%02d-01', $tahun, $m);
                        $tgl_selesai = date('Y-m-t', strtotime($tgl_mulai));
                        
                        // Check if already exists
                        $check = $conn->prepare("SELECT id FROM periode_akuntansi WHERE tanggal_mulai = ? AND tanggal_selesai = ?");
                        $check->bind_param("ss", $tgl_mulai, $tgl_selesai);
                        $check->execute();
                        if ($check->get_result()->num_rows > 0) {
                            $skipped++;
                            continue;
                        }
                        
                        $stmt = $conn->prepare("INSERT INTO periode_akuntansi (nama_periode, tanggal_mulai, tanggal_selesai, status) VALUES (?, ?, ?, 'open')");
                        $stmt->bind_param("sss", $nama, $tgl_mulai, $tgl_selesai);
                        $stmt->execute();
                        $inserted++;
                    }
                    
                    if ($inserted > 0) {
                        $success = "Berhasil membuat $inserted periode untuk tahun $tahun." . ($skipped > 0 ? " ($skipped periode sudah ada, dilewati)" : "");
                    } else {
                        $error = "Semua periode untuk tahun $tahun sudah ada.";
                    }
                }
            }
        }

        // ============================================================
        // POST: Close Periode
        // ============================================================
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'close_periode') {
            csrf_verify();
            $periode_id = intval($_POST['periode_id'] ?? 0);
            
            $conn->begin_transaction();
            
            try {
                // ---- LANGKAH 1: Validasi Akses (Admin Only) ----
                if ($user_role !== 'admin') {
                    throw new Exception("Akses ditolak: Hanya Administrator yang berhak menutup periode akuntansi.");
                }
                
                // Get periode data
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
                
                // ---- LANGKAH 2: Validasi Transaksi ----
                $stmt_check = $conn->prepare("SELECT COUNT(*) as cnt FROM detil_transaksi WHERE tanggal >= ? AND tanggal <= ? AND status_verifikasi = 'pending'");
                $stmt_check->bind_param("ss", $start_date, $end_date);
                $stmt_check->execute();
                $pending_trx = $stmt_check->get_result()->fetch_assoc()['cnt'];
                
                if ($pending_trx > 0) {
                    throw new Exception("Masih ada $pending_trx transaksi pending yang belum diverifikasi. Silakan selesaikan semua transaksi terlebih dahulu.");
                }
                
                // ---- LANGKAH 3: Perhitungan Laba/Rugi ----
                // Pendapatan (akun 4xx)
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
                
                // Beban (akun 5xx)
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
                
                // ---- LANGKAH 4: Simpan Snapshot Saldo ----
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
                
                // ---- LANGKAH 5: Lock Periode ----
                $stmt_lock = $conn->prepare("UPDATE periode_akuntansi SET status = 'closed', tanggal_closed = NOW(), closed_by = ?, laba_periode = ? WHERE id = ?");
                $stmt_lock->bind_param("idi", $user_id, $laba, $periode_id);
                $stmt_lock->execute();
                
                // ---- LANGKAH 6: Logging ----
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
                
                $laba_fmt = number_format(abs($laba), 0, ',', '.');
                $laba_label = $laba >= 0 ? "Laba" : "Rugi";
                $success = "Periode \"" . htmlspecialchars($periode['nama_periode']) . "\" berhasil ditutup! $laba_label: Rp $laba_fmt";
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }

        // ============================================================
        // Fetch all periods for display
        // ============================================================
        $years_sql = "SELECT DISTINCT YEAR(tanggal_mulai) as tahun FROM periode_akuntansi ORDER BY tahun DESC";
        $years_res = $conn->query($years_sql);
        $years_available = [];
        if ($years_res) {
            while ($row = $years_res->fetch_assoc()) {
                $years_available[] = intval($row['tahun']);
            }
        }
        
        if (empty($years_available)) {
            $years_available[] = intval(date('Y'));
        }

        $filter_tahun = $_GET['tahun'] ?? 'semua';
        if ($filter_tahun !== 'semua' && !in_array(intval($filter_tahun), $years_available)) {
            $filter_tahun = 'semua';
        }

        $sql_list = "SELECT pa.*, u.nama_lengkap as closed_by_name 
                     FROM periode_akuntansi pa 
                     LEFT JOIN users u ON pa.closed_by = u.id";

        if ($filter_tahun !== 'semua') {
            $sql_list .= " WHERE YEAR(pa.tanggal_mulai) = ?";
        }
        $sql_list .= " ORDER BY pa.tanggal_mulai DESC";

        $stmt_list = $conn->prepare($sql_list);
        if ($filter_tahun !== 'semua') {
            $stmt_list->bind_param("i", $filter_tahun);
        }
        $stmt_list->execute();
        $result_list = $stmt_list->get_result();
        $periodes = [];
        while ($row = $result_list->fetch_assoc()) {
            $periodes[] = $row;
        }

        $data = [
            'title' => 'Closing Periode Akuntansi',
            'error' => $error,
            'success' => $success,
            'years_available' => $years_available,
            'filter_tahun' => $filter_tahun,
            'periodes' => $periodes
        ];

        $this->view('closing/index', $data);
    }
}
