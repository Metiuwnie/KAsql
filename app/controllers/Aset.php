<?php

class Aset extends Controller {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "/auth/login");
            exit;
        }
        
        require_once __DIR__ . '/../../auth.php';
        require_role(['admin', 'accountant']);
    }

    public function tetap() {
        $this->model('AkunModel');
        $db = new Database();
        $conn = $db->getConnection();
        
        $error = '';
        $success = '';

        // Ambil list akun
        $akun_list = [];
        $res_akun = $conn->query("SELECT kode_akun, akun, kategori_neraca FROM akun ORDER BY kode_akun ASC");
        if ($res_akun) {
            while($row = $res_akun->fetch_assoc()) {
                $akun_list[] = $row;
            }
        }

        // Handle submit
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_aset'])) {
            csrf_verify();
            $nama = $_POST['nama_aset'] ?? '';
            $harga = floatval(str_replace(['.', ','], '', $_POST['harga_perolehan'] ?? '0'));
            $residu = floatval(str_replace(['.', ','], '', $_POST['nilai_residu'] ?? '0'));
            $umur_tahun = intval($_POST['umur_tahun'] ?? 0);
            $umur_bulan_input = intval($_POST['umur_bulan'] ?? 0);
            
            $akun_aset = $_POST['akun_aset'] ?? '';
            $akun_akumulasi = $_POST['akun_akumulasi'] ?? '';
            $akun_beban = $_POST['akun_beban'] ?? '';
            
            $umur_total_bulan = ($umur_tahun * 12) + $umur_bulan_input;

            if (empty($nama) || $harga <= 0 || $umur_total_bulan <= 0 || empty($akun_aset) || empty($akun_akumulasi) || empty($akun_beban)) {
                $error = "Gagal: Harap lengkapi semua data dengan benar. Umur aset tidak boleh 0.";
            } else {
                $nilai_penyusutan = ($harga - $residu) / $umur_total_bulan;
                $stmt = $conn->prepare("INSERT INTO aset_tetap (nama_aset, harga_perolehan, nilai_residu, umur_ekonomis_bulan, nilai_penyusutan_per_bulan, akun_aset, akun_akumulasi, akun_beban) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sddidsss", $nama, $harga, $residu, $umur_total_bulan, $nilai_penyusutan, $akun_aset, $akun_akumulasi, $akun_beban);
                if ($stmt->execute()) {
                    $success = "Data Aset Tetap berhasil ditambahkan.";
                } else {
                    $error = "Error: " . $stmt->error;
                }
            }
        }

        // Flash message dari session
        if (isset($_SESSION['success_msg_aset'])) {
            $success = $_SESSION['success_msg_aset'];
            unset($_SESSION['success_msg_aset']);
        }
        if (isset($_SESSION['error_msg_aset'])) {
            $error = $_SESSION['error_msg_aset'];
            unset($_SESSION['error_msg_aset']);
        }

        // Handle POST delete
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_aset' && isset($_POST['id'])) {
            csrf_verify();
            $id_del = intval($_POST['id']);
            try {
                // Safety check: Pastikan aset belum memiliki riwayat jurnal penyusutan
                $stmt_cek = $conn->prepare("SELECT last_adjusted_month, nama_aset FROM aset_tetap WHERE id = ?");
                $stmt_cek->bind_param("i", $id_del);
                $stmt_cek->execute();
                $aset_row = $stmt_cek->get_result()->fetch_assoc();

                if (!$aset_row) {
                    throw new Exception("Data aset tidak ditemukan.");
                }

                if (!empty($aset_row['last_adjusted_month'])) {
                    throw new Exception("Aset '{$aset_row['nama_aset']}' tidak dapat dihapus karena sudah memiliki riwayat penyusutan di jurnal akuntansi (Terakhir disusutkan: {$aset_row['last_adjusted_month']}).");
                }

                $stmt = $conn->prepare("DELETE FROM aset_tetap WHERE id = ?");
                $stmt->bind_param("i", $id_del);
                $stmt->execute();
                $_SESSION['success_msg_aset'] = "Data Aset Tetap berhasil dihapus.";
            } catch (Exception $e) {
                $_SESSION['error_msg_aset'] = "Gagal menghapus: " . $e->getMessage();
            }
            header("Location: " . BASE_URL . "/aset/tetap");
            exit;
        }

        // Ambil data aset
        $aset_data = [];
        $res_aset = $conn->query("SELECT * FROM aset_tetap ORDER BY id DESC");
        if ($res_aset) {
            while($row = $res_aset->fetch_assoc()) {
                $aset_data[] = $row;
            }
        }

        $current_month = date('Y-m');

        $data = [
            'title' => 'Manajemen Aset Tetap & Depresiasi',
            'error' => $error,
            'success' => $success,
            'akun_list' => $akun_list,
            'aset_data' => $aset_data,
            'current_month' => $current_month
        ];

        $this->view('aset/tetap', $data);
    }

    public function prepaid() {
        $this->model('AkunModel');
        $db = new Database();
        $conn = $db->getConnection();
        
        $error = '';
        $success = '';

        // Ambil list akun
        $akun_list = [];
        $res_akun = $conn->query("SELECT kode_akun, akun, kategori_neraca FROM akun ORDER BY kode_akun ASC");
        if ($res_akun) {
            while($row = $res_akun->fetch_assoc()) {
                $akun_list[] = $row;
            }
        }

        // Handle submit
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_prepaid'])) {
            csrf_verify();
            $nama = $_POST['nama_prepaid'] ?? '';
            $total_nilai = floatval(str_replace(['.', ','], '', $_POST['total_nilai'] ?? '0'));
            $lama_bulan = intval($_POST['lama_bulan'] ?? 0);
            $akun_prepaid = $_POST['akun_prepaid'] ?? '';
            $akun_beban = $_POST['akun_beban'] ?? '';
            
            if (empty($nama) || $total_nilai <= 0 || $lama_bulan <= 0 || empty($akun_prepaid) || empty($akun_beban)) {
                $error = "Gagal: Harap lengkapi semua data dengan benar.";
            } else {
                $nilai_per_bulan = $total_nilai / $lama_bulan;
                $tanggal_mulai = $_POST['tanggal_mulai'] ?? date('Y-m-d');
                $stmt = $conn->prepare("INSERT INTO manajemen_prepaid (nama_prepaid, total_nilai, lama_bulan, nilai_per_bulan, akun_prepaid, akun_beban, tanggal_mulai) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sdidsss", $nama, $total_nilai, $lama_bulan, $nilai_per_bulan, $akun_prepaid, $akun_beban, $tanggal_mulai);
                if ($stmt->execute()) {
                    $success = "Data Prepaid berhasil ditambahkan.";
                } else {
                    $error = "Error: " . $stmt->error;
                }
            }
        }

        // Flash message dari session
        if (isset($_SESSION['success_msg_prepaid'])) {
            $success = $_SESSION['success_msg_prepaid'];
            unset($_SESSION['success_msg_prepaid']);
        }
        if (isset($_SESSION['error_msg_prepaid'])) {
            $error = $_SESSION['error_msg_prepaid'];
            unset($_SESSION['error_msg_prepaid']);
        }

        // Handle POST delete
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_prepaid' && isset($_POST['id'])) {
            csrf_verify();
            $id_del = intval($_POST['id']);
            try {
                $stmt = $conn->prepare("DELETE FROM manajemen_prepaid WHERE id = ?");
                $stmt->bind_param("i", $id_del);
                $stmt->execute();
                $_SESSION['success_msg_prepaid'] = "Data Prepaid berhasil dihapus.";
            } catch (Exception $e) {
                $_SESSION['error_msg_prepaid'] = "Gagal menghapus data: " . $e->getMessage();
            }
            header("Location: " . BASE_URL . "/aset/prepaid");
            exit;
        }

        // Ambil data prepaid
        $prepaid_data = [];
        $res_prep = $conn->query("SELECT * FROM manajemen_prepaid ORDER BY id DESC");
        if ($res_prep) {
            while($row = $res_prep->fetch_assoc()) {
                $prepaid_data[] = $row;
            }
        }

        $data = [
            'title' => 'Manajemen Prepaid Expense',
            'error' => $error,
            'success' => $success,
            'akun_list' => $akun_list,
            'prepaid_data' => $prepaid_data
        ];

        $this->view('aset/prepaid', $data);
    }
}
