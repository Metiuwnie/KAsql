<?php

class Master extends Controller {

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "/auth/login");
            exit;
        }
    }

    public function akun() {
        // Hanya admin dan accountant yang bisa akses
        if (!in_array($_SESSION['user_role'], ['admin', 'accountant'])) {
            die("Akses ditolak. Anda tidak memiliki izin untuk melihat halaman ini.");
        }

        $this->model('AkunModel');
        $model = new AkunModel();

        $action = $_GET['action'] ?? '';
        $error = '';
        $success = '';

        // Flash message dari session
        if (isset($_SESSION['success_msg'])) {
            $success = $_SESSION['success_msg'];
            unset($_SESSION['success_msg']);
        }
        if (isset($_SESSION['error_msg'])) {
            $error = $_SESSION['error_msg'];
            unset($_SESSION['error_msg']);
        }

        // Proses POST (Tambah/Edit/Hapus)
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            csrf_verify();
            if (isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['kode'])) {
                try {
                    $model->deleteAkun($_POST['kode']);
                    $_SESSION['success_msg'] = "Data akun berhasil dihapus.";
                } catch (Exception $e) {
                    $_SESSION['error_msg'] = "Gagal menghapus: " . $e->getMessage();
                }
                header("Location: " . BASE_URL . "/master/akun");
                exit;
            } elseif (isset($_POST['submit_akun'])) {
                $is_edit = isset($_POST['is_edit']) && $_POST['is_edit'] == '1';
                
                $data_post = [
                    'kode_akun' => $_POST['kode_akun'],
                    'nama_akun' => $_POST['nama_akun'],
                    'aktiva_pasiva' => (!empty($_POST['aktiva_pasiva'])) ? $_POST['aktiva_pasiva'] : null,
                    'kategori_neraca' => (!empty($_POST['kategori_neraca'])) ? $_POST['kategori_neraca'] : null
                ];

                try {
                    if ($is_edit) {
                        $model->updateAkun($_POST['old_kode'], $data_post);
                        $_SESSION['success_msg'] = "Data akun berhasil diperbarui.";
                    } else {
                        $model->addAkun($data_post);
                        $_SESSION['success_msg'] = "Akun baru berhasil ditambahkan.";
                    }
                } catch (Exception $e) {
                    $_SESSION['error_msg'] = "Gagal menyimpan data: " . $e->getMessage();
                }
                header("Location: " . BASE_URL . "/master/akun");
                exit;
            }
        }

        $edit_data = null;
        if ($action == 'edit' && isset($_GET['kode'])) {
            $edit_data = $model->getAkunByKode($_GET['kode']);
        }

        $data = [
            'title' => 'Manajemen Data Akun',
            'akun_list' => $model->getAllAkun(),
            'edit_data' => $edit_data,
            'success' => $success,
            'error' => $error
        ];

        $this->view('master/akun', $data);
    }

    public function user() {
        // Hanya admin yang bisa akses
        if ($_SESSION['user_role'] !== 'admin') {
            die("Akses ditolak. Hanya Administrator yang dapat mengelola user.");
        }

        $this->model('UserModel');
        $model = new UserModel();

        $action = $_GET['action'] ?? '';
        $error = '';
        $success = '';

        // Flash message
        if (isset($_SESSION['success_msg_user'])) {
            $success = $_SESSION['success_msg_user'];
            unset($_SESSION['success_msg_user']);
        }
        if (isset($_SESSION['error_msg_user'])) {
            $error = $_SESSION['error_msg_user'];
            unset($_SESSION['error_msg_user']);
        }

        // Proses POST
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            csrf_verify();
            if (isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['user_id'])) {
                $del_id = intval($_POST['user_id']);
                if ($del_id == $_SESSION['user_id']) {
                    $_SESSION['error_msg_user'] = "Anda tidak dapat menghapus akun Anda sendiri.";
                } else {
                    try {
                        $model->deleteUser($del_id);
                        $_SESSION['success_msg_user'] = "User berhasil dihapus.";
                    } catch (Exception $e) {
                        $_SESSION['error_msg_user'] = "Gagal menghapus user: " . $e->getMessage();
                    }
                }
                header("Location: " . BASE_URL . "/master/user");
                exit;
            } elseif (isset($_POST['submit_user'])) {
                $username = trim($_POST['username'] ?? '');
                $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
                $role = $_POST['role'] ?? 'cashier';
                $password = $_POST['password'] ?? '';
                $is_edit = isset($_POST['is_edit']) && $_POST['is_edit'] == '1';
                $edit_id = intval($_POST['edit_id'] ?? 0);

                if (empty($username) || empty($nama_lengkap)) {
                    $error = "Username dan Nama Lengkap harus diisi.";
                } elseif (!in_array($role, ['cashier', 'accountant', 'admin'])) {
                    $error = "Role tidak valid.";
                } elseif (!$is_edit && empty($password)) {
                    $error = "Password wajib diisi untuk user baru.";
                } elseif (!empty($password) && strlen($password) < 6) {
                    $error = "Password minimal 6 karakter.";
                } else {
                    try {
                        if ($is_edit) {
                            if ($model->checkUsername($username, $edit_id)) {
                                $error = "Username '$username' sudah digunakan oleh user lain.";
                            } else {
                                $model->updateUser($edit_id, $username, $nama_lengkap, $role, $password);
                                $_SESSION['success_msg_user'] = "Data user berhasil diperbarui.";
                                header("Location: " . BASE_URL . "/master/user");
                                exit;
                            }
                        } else {
                            if ($model->checkUsername($username)) {
                                $error = "Username '$username' sudah digunakan.";
                            } else {
                                $model->addUser($username, $nama_lengkap, $role, $password);
                                $_SESSION['success_msg_user'] = "User baru berhasil ditambahkan.";
                                header("Location: " . BASE_URL . "/master/user");
                                exit;
                            }
                        }
                    } catch (Exception $e) {
                        $error = "Gagal menyimpan data: " . $e->getMessage();
                    }
                }
            }
        }

        $edit_data = null;
        if ($action == 'edit' && isset($_GET['id'])) {
            $edit_data = $model->getUserById($_GET['id']);
        }

        $data = [
            'title' => 'Manajemen User',
            'user_list' => $model->getAllUsers(),
            'edit_data' => $edit_data,
            'success' => $success,
            'error' => $error
        ];

        $this->view('master/user', $data);
    }

    public function reaksi() {
        // Hanya admin dan accountant yang bisa akses
        if (!in_array($_SESSION['user_role'], ['admin', 'accountant'])) {
            die("Akses ditolak. Anda tidak memiliki izin untuk melihat halaman ini.");
        }

        $db = new Database();
        $conn = $db->getConnection();
        
        $action = $_GET['action'] ?? '';
        $error = '';
        $success = '';
        $active_tab = $_GET['tab'] ?? 'detail';

        // Flash message dari session
        if (isset($_SESSION['success_msg_reaksi'])) {
            $success = $_SESSION['success_msg_reaksi'];
            unset($_SESSION['success_msg_reaksi']);
        }
        if (isset($_SESSION['error_msg_reaksi'])) {
            $error = $_SESSION['error_msg_reaksi'];
            unset($_SESSION['error_msg_reaksi']);
        }

        // ==========================================
        // POST: HAPUS REAKSI
        // ==========================================
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_reaksi' && isset($_POST['id_reaksi'])) {
            csrf_verify();
            $id = trim($_POST['id_reaksi']);
            try {
                $check = $conn->prepare("SELECT id_reaksi FROM detail_reaksi WHERE id_reaksi = ? LIMIT 1");
                $check->bind_param("s", $id);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $_SESSION['error_msg_reaksi'] = "Tidak bisa menghapus! Reaksi ini masih digunakan dalam Mapping Akun.";
                } else {
                    $stmt = $conn->prepare("DELETE FROM reaksi WHERE id_reaksi = ?");
                    $stmt->bind_param("s", $id);
                    $stmt->execute();
                    $_SESSION['success_msg_reaksi'] = "Jenis Reaksi berhasil dihapus.";
                }
            } catch (Exception $e) {
                $_SESSION['error_msg_reaksi'] = "Gagal menghapus reaksi: " . $e->getMessage();
            }
            header("Location: " . BASE_URL . "/master/reaksi?tab=reaksi");
            exit;
        }

        // ==========================================
        // POST: HAPUS DETAIL MAPPING
        // ==========================================
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_detail' && isset($_POST['id_detail_reaksi'])) {
            csrf_verify();
            $id = intval($_POST['id_detail_reaksi']);
            try {
                $stmt = $conn->prepare("DELETE FROM detail_reaksi WHERE id_detail_reaksi = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $_SESSION['success_msg_reaksi'] = "Data relasi detail reaksi berhasil dihapus.";
            } catch (Exception $e) {
                $_SESSION['error_msg_reaksi'] = "Gagal menghapus: " . $e->getMessage();
            }
            header("Location: " . BASE_URL . "/master/reaksi?tab=detail");
            exit;
        }

        // ==========================================
        // LOGIC UNTUK TAB REAKSI (JENIS AKTIVITAS)
        // ==========================================
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_reaksi'])) {
            csrf_verify();
            $id_reaksi = trim($_POST['id_reaksi']);
            $nama_reaksi = trim($_POST['nama_reaksi']);
            $is_edit_reaksi = isset($_POST['is_edit_reaksi']) && $_POST['is_edit_reaksi'] == '1';
            
            try {
                if ($is_edit_reaksi) {
                    $stmt = $conn->prepare("UPDATE reaksi SET nama_reaksi = ? WHERE id_reaksi = ?");
                    $stmt->bind_param("ss", $nama_reaksi, $id_reaksi);
                    $stmt->execute();
                    $_SESSION['success_msg_reaksi'] = "Data Jenis Reaksi berhasil diperbarui.";
                } else {
                    $check = $conn->prepare("SELECT id_reaksi FROM reaksi WHERE id_reaksi = ?");
                    $check->bind_param("s", $id_reaksi);
                    $check->execute();
                    if ($check->get_result()->num_rows > 0) {
                        $_SESSION['error_msg_reaksi'] = "ID Reaksi '$id_reaksi' sudah ada!";
                    } else {
                        $stmt = $conn->prepare("INSERT INTO reaksi (id_reaksi, nama_reaksi) VALUES (?, ?)");
                        $stmt->bind_param("ss", $id_reaksi, $nama_reaksi);
                        $stmt->execute();
                        $_SESSION['success_msg_reaksi'] = "Jenis Reaksi baru berhasil ditambahkan.";
                    }
                }
            } catch (Exception $e) {
                $_SESSION['error_msg_reaksi'] = "Gagal memproses reaksi: " . $e->getMessage();
            }
            header("Location: " . BASE_URL . "/master/reaksi?tab=reaksi");
            exit;
        }

        // ==========================================
        // LOGIC UNTUK TAB DETAIL (MAPPING AKUN)
        // ==========================================
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_detail'])) {
            csrf_verify();
            $id_reaksi = $_POST['id_reaksi'];
            $kode_akun = $_POST['kode_akun'];
            $dk = $_POST['dk'];
            $is_edit = isset($_POST['is_edit']) && $_POST['is_edit'] == '1';
            
            try {
                if ($is_edit) {
                    $id = $_POST['id_detail_reaksi'];
                    $stmt = $conn->prepare("UPDATE detail_reaksi SET id_reaksi = ?, kode_akun = ?, dk = ? WHERE id_detail_reaksi = ?");
                    $stmt->bind_param("sssi", $id_reaksi, $kode_akun, $dk, $id);
                    $stmt->execute();
                    $_SESSION['success_msg_reaksi'] = "Data detail reaksi berhasil diperbarui.";
                } else {
                    $stmt = $conn->prepare("INSERT INTO detail_reaksi (id_reaksi, kode_akun, dk) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $id_reaksi, $kode_akun, $dk);
                    $stmt->execute();
                    $_SESSION['success_msg_reaksi'] = "Data detail reaksi baru berhasil ditambahkan.";
                }
            } catch (Exception $e) {
                $_SESSION['error_msg_reaksi'] = "Gagal menyimpan data: " . $e->getMessage();
            }
            header("Location: " . BASE_URL . "/master/reaksi?tab=detail");
            exit;
        }

        $reaksi_list = [];
        $r_res = $conn->query("SELECT * FROM reaksi ORDER BY id_reaksi ASC");
        if ($r_res) { while($row = $r_res->fetch_assoc()) { $reaksi_list[] = $row; } }

        $akun_list = [];
        $a_res = $conn->query("SELECT * FROM akun ORDER BY kode_akun ASC");
        if ($a_res) { while($row = $a_res->fetch_assoc()) { $akun_list[] = $row; } }

        $edit_reaksi = null;
        if ($action == 'edit_reaksi' && isset($_GET['id'])) {
            $stmt = $conn->prepare("SELECT * FROM reaksi WHERE id_reaksi = ?");
            $stmt->bind_param("s", $_GET['id']);
            $stmt->execute();
            $edit_reaksi = $stmt->get_result()->fetch_assoc();
            $active_tab = 'reaksi';
        }

        $edit_detail = null;
        if ($action == 'edit' && isset($_GET['id'])) {
            $stmt = $conn->prepare("SELECT * FROM detail_reaksi WHERE id_detail_reaksi = ?");
            $stmt->bind_param("i", $_GET['id']);
            $stmt->execute();
            $edit_detail = $stmt->get_result()->fetch_assoc();
            $active_tab = 'detail';
        }

        $sql_table = "
            SELECT dr.id_detail_reaksi, dr.dk, r.nama_reaksi, r.id_reaksi, a.akun as nama_akun, dr.kode_akun
            FROM detail_reaksi dr
            LEFT JOIN reaksi r ON dr.id_reaksi = r.id_reaksi
            LEFT JOIN akun a ON dr.kode_akun = a.kode_akun
            ORDER BY dr.id_reaksi ASC, dr.dk DESC
        ";
        
        $mapping_list = [];
        $mapping_result = $conn->query($sql_table);
        if ($mapping_result) {
            while($row = $mapping_result->fetch_assoc()) {
                $mapping_list[] = $row;
            }
        }

        $data = [
            'title' => 'Manajemen Reaksi & Detail',
            'action' => $action,
            'active_tab' => $active_tab,
            'success' => $success,
            'error' => $error,
            'reaksi_list' => $reaksi_list,
            'akun_list' => $akun_list,
            'edit_reaksi' => $edit_reaksi,
            'edit_detail' => $edit_detail,
            'mapping_list' => $mapping_list
        ];

        $this->view('master/reaksi', $data);
    }
}
