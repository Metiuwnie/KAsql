<?php

class Transaksi extends Controller {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "/auth/login");
            exit;
        }
    }

    public function input() {
        $this->model('JurnalModel');
        $model = new JurnalModel();
        
        $error = '';
        $success = '';

        // Ambil list opsi reaksi dan akun
        $reaksi_opts = [];
        $r_res = $model->query("SELECT * FROM reaksi ORDER BY nama_reaksi ASC");
        if ($r_res) { while($row = $r_res->fetch_assoc()) $reaksi_opts[] = $row; }

        $akun_opts = $model->getAkunList();

        // --- KOREKSI MODE ---
        $is_koreksi = false;
        $koreksi_dari = $_GET['koreksi_dari'] ?? ($_POST['koreksi_dari'] ?? '');
        $koreksi_data = [];
        $koreksi_header = null;

        if (!empty($koreksi_dari)) {
            $is_koreksi = true;
            
            // Load original transaction header
            $stmt_h = $model->prepare("SELECT * FROM detil_transaksi WHERE kode_transaksi = ?");
            $stmt_h->bind_param("s", $koreksi_dari);
            $stmt_h->execute();
            $koreksi_header = $stmt_h->get_result()->fetch_assoc();
            
            // Load original transaction lines
            $stmt_d = $model->prepare("SELECT t.kode_akun, t.dk, t.nilai FROM transaksi t WHERE t.kode_transaksi = ? ORDER BY t.dk ASC");
            $stmt_d->bind_param("s", $koreksi_dari);
            $stmt_d->execute();
            $res_d = $stmt_d->get_result();
            while ($row = $res_d->fetch_assoc()) {
                $koreksi_data[] = $row;
            }
        }

        // --- LOGIKA SIMPAN TRANSAKSI ---
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_transaksi'])) {
            csrf_verify();
            $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
            $deskripsi = $_POST['deskripsi'] ?? '';
            $arr_akun = $_POST['akun'] ?? [];
            $arr_dk = $_POST['dk'] ?? [];
            $arr_rp = $_POST['rupiah'] ?? [];
            $koreksi_dari_post = $_POST['koreksi_dari'] ?? '';

            // Filter baris yang terisi
            $valid_rows = [];
            $total_debit = 0;
            $total_kredit = 0;
            foreach ($arr_akun as $index => $k_akun) {
                $raw_val = trim($arr_rp[$index] ?? '');
                if (strpos($raw_val, '.') !== false && strpos($raw_val, ',') !== false) {
                    $raw_val = str_replace('.', '', $raw_val);
                    $raw_val = str_replace(',', '.', $raw_val);
                } elseif (strpos($raw_val, ',') !== false) {
                    $raw_val = str_replace(',', '.', $raw_val);
                } else {
                    $raw_val = str_replace('.', '', $raw_val);
                }
                $nilai = round(floatval($raw_val), 2);
                
                if (!empty($k_akun) && $nilai > 0) {
                    $dk = $arr_dk[$index];
                    $valid_rows[] = [
                        'kode_akun' => $k_akun,
                        'dk' => $dk,
                        'nilai' => $nilai
                    ];
                    
                    if(strtolower($dk) == 'debit') $total_debit += $nilai;
                    else $total_kredit += $nilai;
                }
            }

            $closed_msg = is_periode_closed($tanggal, $model);
            if (!empty($closed_msg)) {
                $error = "Gagal: " . $closed_msg;
            } else if (count($valid_rows) == 0) {
                $error = "Gagal: Minimal harus ada satu baris akun dengan nominal rupiah yang valid.";
            } else if (empty($tanggal) || empty($deskripsi)) {
                $error = "Gagal: Tanggal dan Deskripsi transaksi tidak boleh kosong.";
            } else if (abs($total_debit - $total_kredit) > 0.001) {
                $error = "Gagal: Transaksi TIDAK SEIMBANG. Selisih: Rp " . number_format(abs($total_debit - $total_kredit), 0, ',', '.');
            } else {
                $retry_count = 0;
                $max_retries = 5;
                $success_save = false;

                while ($retry_count < $max_retries && !$success_save) {
                    $model->begin_transaction();
                    try {
                        $new_kode = "A001";
                        $q_max = $model->query("SELECT MAX(CAST(SUBSTRING(kode_transaksi, 2) AS UNSIGNED)) as max_kode FROM detil_transaksi WHERE kode_transaksi LIKE 'A%' FOR UPDATE");
                        if ($q_max && $r_max = $q_max->fetch_assoc()) {
                            if (!empty($r_max['max_kode'])) {
                                $next_num = $r_max['max_kode'] + 1;
                                $new_kode = "A" . str_pad($next_num, 3, "0", STR_PAD_LEFT);
                            }
                        }
                        
                        $created_by = $_SESSION['user_id'] ?? null;
                        
                        //  Insert Header Transaksi Baru
                        if (!empty($koreksi_dari_post)) {
                            $stmt = $model->prepare("INSERT INTO detil_transaksi (kode_transaksi, tanggal, deskripsi, created_by, status_verifikasi, koreksi_dari_id) VALUES (?, ?, ?, ?, 'pending', ?)");
                            $stmt->bind_param("sssis", $new_kode, $tanggal, $deskripsi, $created_by, $koreksi_dari_post);
                        } else {
                            $stmt = $model->prepare("INSERT INTO detil_transaksi (kode_transaksi, tanggal, deskripsi, created_by, status_verifikasi) VALUES (?, ?, ?, ?, 'pending')");
                            $stmt->bind_param("sssi", $new_kode, $tanggal, $deskripsi, $created_by);
                        }
                        $stmt->execute();
                        
                        $stmt_t = $model->prepare("INSERT INTO transaksi (kode_transaksi, kode_akun, dk, nilai) VALUES (?, ?, ?, ?)");
                        foreach ($valid_rows as $row) {
                            $stmt_t->bind_param("sssd", $new_kode, $row['kode_akun'], $row['dk'], $row['nilai']);
                            $stmt_t->execute();
                        }
                        
                        // Update status transaksi lama menjadi koreksi (void)
                        if (!empty($koreksi_dari_post)) {
                            $user_id = $_SESSION['user_id'] ?? 0;
                            $koreksi_text = "dikoreksi oleh : " . $user_id;
                            $stmt_upd = $model->prepare("UPDATE detil_transaksi SET status_verifikasi = 'koreksi', pengoreksi_id = ? WHERE kode_transaksi = ?");
                            $stmt_upd->bind_param("ss", $koreksi_text, $koreksi_dari_post);
                            $stmt_upd->execute();
                        }
                        
                        $model->commit();
                        $success_save = true;
                        
                        if (!empty($koreksi_dari_post)) {
                            $_SESSION['success_msg_pending'] = "Transaksi koreksi berhasil disimpan (No: " . htmlspecialchars($new_kode) . "). Transaksi " . htmlspecialchars($koreksi_dari_post) . " telah ditandai sebagai 'koreksi'.";
                            header("Location: " . BASE_URL . "/transaksi/pending");
                            exit;
                        } else {
                            $success = "Data transaksi berhasil disimpan ke jurnal (No: " . htmlspecialchars($new_kode) . ")";
                        }

                        $_GET['id_reaksi'] = '';
                        $is_koreksi = false;
                        $koreksi_dari = '';
                        $koreksi_data = [];
                        $koreksi_header = null;
                    } catch (Exception $e) {
                        $model->rollback();
                        $retry_count++;
                        if ($retry_count >= $max_retries) {
                            error_log('[KAsql] Gagal simpan transaksi: ' . $e->getMessage());
                            $error = "Gagal menyimpan transaksi. Silakan coba lagi atau hubungi administrator.";
                            break;
                        }
                    }
                }
            }
        }

        // Tampil Akun (Reaksi)
        $selected_reaksi = $_GET['id_reaksi'] ?? '';
        $loaded_details = [];
        if (!empty($selected_reaksi) && !$is_koreksi) {
            $stmt = $model->prepare("SELECT * FROM detail_reaksi WHERE id_reaksi = ? ORDER BY dk ASC");
            $stmt->bind_param("s", $selected_reaksi);
            $stmt->execute();
            $res = $stmt->get_result();
            while($row = $res->fetch_assoc()) {
                $loaded_details[] = $row;
            }
        }

        $baris_tampil = 4;
        if ($is_koreksi && count($koreksi_data) > 0) {
            $baris_tampil = max(4, count($koreksi_data));
        } else {
            $baris_tampil = max(4, count($loaded_details));
        }

        $data = [
            'title' => $is_koreksi ? 'Koreksi Transaksi' : 'Input Transaksi',
            'error' => $error,
            'success' => $success,
            'reaksi_opts' => $reaksi_opts,
            'akun_opts' => $akun_opts,
            'is_koreksi' => $is_koreksi,
            'koreksi_dari' => $koreksi_dari,
            'koreksi_data' => $koreksi_data,
            'koreksi_header' => $koreksi_header,
            'selected_reaksi' => $selected_reaksi,
            'loaded_details' => $loaded_details,
            'baris_tampil' => $baris_tampil
        ];

        $this->view('transaksi/input', $data);
    }

    public function pending() {
        // Harus admin/accountant untuk memverifikasi
        if (!in_array($_SESSION['user_role'], ['admin', 'accountant'])) {
            die("Akses ditolak. Hanya Admin dan Akuntan yang dapat memverifikasi transaksi.");
        }

        $this->model('JurnalModel');
        $model = new JurnalModel();
        
        $error = '';
        $success = '';

        if (isset($_SESSION['success_msg_pending'])) {
            $success = $_SESSION['success_msg_pending'];
            unset($_SESSION['success_msg_pending']);
        }

        // --- HANDLE POSTING ACTION ---
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_posting'])) {
            csrf_verify();
            $kode = $_POST['kode_transaksi'] ?? '';
            $user_id = $_SESSION['user_id'] ?? 0;
            
            if (!empty($kode)) {
                // Periksa tanggal transaksi dan validasi status periode akuntansi
                $stmt_cek = $model->prepare("SELECT tanggal, status_verifikasi FROM detil_transaksi WHERE kode_transaksi = ?");
                $stmt_cek->bind_param("s", $kode);
                $stmt_cek->execute();
                $trx_data = $stmt_cek->get_result()->fetch_assoc();

                if (!$trx_data) {
                    $error = "Gagal: Transaksi " . htmlspecialchars($kode) . " tidak ditemukan.";
                } elseif ($trx_data['status_verifikasi'] !== 'pending') {
                    $error = "Gagal: Transaksi " . htmlspecialchars($kode) . " sudah diproses sebelumnya.";
                } else {
                    $closed_msg = is_periode_closed($trx_data['tanggal'], $model);
                    if (!empty($closed_msg)) {
                        $error = "Gagal Posting: " . $closed_msg;
                    } else {
                        $pengoreksi_text = "disetujui oleh : " . $user_id;
                        $stmt = $model->prepare("UPDATE detil_transaksi SET status_verifikasi = 'sesuai', pengoreksi_id = ? WHERE kode_transaksi = ? AND status_verifikasi = 'pending'");
                        $stmt->bind_param("ss", $pengoreksi_text, $kode);
                        $stmt->execute();
                        
                        if ($stmt->affected_rows > 0) {
                            $success = "Transaksi " . htmlspecialchars($kode) . " berhasil di-posting (disetujui).";
                        } else {
                            $error = "Gagal memposting transaksi " . htmlspecialchars($kode) . ".";
                        }
                    }
                }
            }
        }

        // --- FETCH ALL TRANSACTIONS (show pending first, then others) ---
        $filter_status = $_GET['status'] ?? 'pending';

        $valid_statuses = ['pending', 'sesuai', 'koreksi', 'semua'];
        if (!in_array($filter_status, $valid_statuses)) {
            $filter_status = 'pending';
        }

        $sql = "SELECT 
                    dt.kode_transaksi,
                    dt.tanggal,
                    dt.deskripsi,
                    dt.status_verifikasi,
                    dt.pengoreksi_id,
                    dt.koreksi_dari_id,
                    dt.created_by,
                    COALESCE(u.nama_lengkap, CONCAT('User #', dt.created_by)) as creator_name,
                    SUM(IF(t.dk='Debit', t.nilai, 0)) as total_debit,
                    SUM(IF(t.dk='Kredit', t.nilai, 0)) as total_kredit
                FROM detil_transaksi dt
                LEFT JOIN transaksi t ON dt.kode_transaksi = t.kode_transaksi
                LEFT JOIN users u ON dt.created_by = u.id";

        if ($filter_status !== 'semua') {
            $sql .= " WHERE dt.status_verifikasi = ?";
        }

        $sql .= " GROUP BY dt.kode_transaksi, dt.tanggal, dt.deskripsi, dt.status_verifikasi, dt.pengoreksi_id, dt.koreksi_dari_id, dt.created_by, creator_name
                   ORDER BY dt.status_verifikasi = 'pending' DESC, dt.tanggal DESC, dt.kode_transaksi DESC";

        $stmt = $model->prepare($sql);
        if ($filter_status !== 'semua') {
            $stmt->bind_param("s", $filter_status);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            // Fetch details for each
            $detail_stmt = $model->prepare("SELECT t.kode_akun, a.akun, t.dk, t.nilai FROM transaksi t LEFT JOIN akun a ON t.kode_akun = a.kode_akun WHERE t.kode_transaksi = ? ORDER BY t.dk ASC");
            $detail_stmt->bind_param("s", $row['kode_transaksi']);
            $detail_stmt->execute();
            $detail_result = $detail_stmt->get_result();
            $details = [];
            while ($det = $detail_result->fetch_assoc()) {
                $details[] = $det;
            }
            $row['details'] = $details;

            // Audit Trail Lookup
            $hasil_koreksi = null;
            if ($row['status_verifikasi'] === 'koreksi') {
                $stmt_new = $model->prepare("
                    SELECT dt.kode_transaksi,
                           SUM(IF(t.dk='Debit', t.nilai, 0)) as total_debit
                    FROM detil_transaksi dt
                    LEFT JOIN transaksi t ON dt.kode_transaksi = t.kode_transaksi
                    WHERE dt.koreksi_dari_id = ?
                    GROUP BY dt.kode_transaksi
                ");
                $stmt_new->bind_param("s", $row['kode_transaksi']);
                $stmt_new->execute();
                $res_new = $stmt_new->get_result();
                if ($res_new && $new_row = $res_new->fetch_assoc()) {
                    $hasil_koreksi = $new_row;
                }
            }
            $row['hasil_koreksi'] = $hasil_koreksi;

            $asal_koreksi = null;
            if (!empty($row['koreksi_dari_id']) && $row['status_verifikasi'] !== 'koreksi') {
                $stmt_old = $model->prepare("
                    SELECT dt.kode_transaksi,
                           SUM(IF(t.dk='Debit', t.nilai, 0)) as total_debit
                    FROM detil_transaksi dt
                    LEFT JOIN transaksi t ON dt.kode_transaksi = t.kode_transaksi
                    WHERE dt.kode_transaksi = ?
                    GROUP BY dt.kode_transaksi
                ");
                $stmt_old->bind_param("s", $row['koreksi_dari_id']);
                $stmt_old->execute();
                $res_old = $stmt_old->get_result();
                if ($res_old && $old_row = $res_old->fetch_assoc()) {
                    $asal_koreksi = $old_row;
                }
            }
            $row['asal_koreksi'] = $asal_koreksi;

            $transactions[] = $row;
        }

        // Status counts for tabs
        $count_sql = "SELECT 
            SUM(status_verifikasi = 'pending') as cnt_pending,
            SUM(status_verifikasi = 'sesuai') as cnt_sesuai,
            SUM(status_verifikasi = 'koreksi') as cnt_koreksi,
            COUNT(*) as cnt_semua
        FROM detil_transaksi";
        $cnt_result = $model->query($count_sql);
        $counts = $cnt_result->fetch_assoc();

        // --- FETCH HISTORY OF VERIFIED TRANSACTIONS (Posted/Corrected) ---
        $hist_sql = "SELECT 
                    dt.kode_transaksi,
                    dt.tanggal,
                    dt.deskripsi,
                    dt.status_verifikasi,
                    dt.pengoreksi_id,
                    dt.koreksi_dari_id,
                    dt.created_by,
                    COALESCE(u.nama_lengkap, CONCAT('User #', dt.created_by)) as creator_name,
                    SUM(IF(t.dk='Debit', t.nilai, 0)) as total_debit,
                    SUM(IF(t.dk='Kredit', t.nilai, 0)) as total_kredit
                FROM detil_transaksi dt
                LEFT JOIN transaksi t ON dt.kode_transaksi = t.kode_transaksi
                LEFT JOIN users u ON dt.created_by = u.id
                WHERE dt.status_verifikasi IN ('sesuai', 'koreksi')
                GROUP BY dt.kode_transaksi, dt.tanggal, dt.deskripsi, dt.status_verifikasi, dt.pengoreksi_id, dt.koreksi_dari_id, dt.created_by, creator_name
                ORDER BY dt.tanggal DESC, dt.kode_transaksi DESC
                LIMIT 20";
        $hist_stmt = $model->prepare($hist_sql);
        $hist_stmt->execute();
        $hist_result = $hist_stmt->get_result();
        $history_trx = [];
        while ($row = $hist_result->fetch_assoc()) {
            // Audit Trail Lookup keterangan hasil 
            $h_hasil_koreksi = null;
            if ($row['status_verifikasi'] === 'koreksi') {
                $stmt_new = $model->prepare("
                    SELECT dt.kode_transaksi,
                           SUM(IF(t.dk='Debit', t.nilai, 0)) as total_debit
                    FROM detil_transaksi dt
                    LEFT JOIN transaksi t ON dt.kode_transaksi = t.kode_transaksi
                    WHERE dt.koreksi_dari_id = ?
                    GROUP BY dt.kode_transaksi
                ");
                $stmt_new->bind_param("s", $row['kode_transaksi']);
                $stmt_new->execute();
                $res_new = $stmt_new->get_result();
                if ($res_new && $new_row = $res_new->fetch_assoc()) {
                    $h_hasil_koreksi = $new_row;
                }
            }
            $row['h_hasil_koreksi'] = $h_hasil_koreksi;

            // Audit Trail Lookup  keterangan awal
            $h_asal_koreksi = null;
            if (!empty($row['koreksi_dari_id']) && $row['status_verifikasi'] !== 'koreksi') {
                $stmt_old = $model->prepare("
                    SELECT dt.kode_transaksi,
                           SUM(IF(t.dk='Debit', t.nilai, 0)) as total_debit
                    FROM detil_transaksi dt
                    LEFT JOIN transaksi t ON dt.kode_transaksi = t.kode_transaksi
                    WHERE dt.kode_transaksi = ?
                    GROUP BY dt.kode_transaksi
                ");
                $stmt_old->bind_param("s", $row['koreksi_dari_id']);
                $stmt_old->execute();
                $res_old = $stmt_old->get_result();
                if ($res_old && $old_row = $res_old->fetch_assoc()) {
                    $h_asal_koreksi = $old_row;
                }
            }
            $row['h_asal_koreksi'] = $h_asal_koreksi;

            $history_trx[] = $row;
        }

        $data = [
            'title' => 'Transaksi Pending - Verifikasi',
            'error' => $error,
            'success' => $success,
            'transactions' => $transactions,
            'counts' => $counts,
            'filter_status' => $filter_status,
            'history_trx' => $history_trx
        ];

        $this->view('transaksi/pending', $data);
    }
}
