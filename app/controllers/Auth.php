<?php

class Auth extends Controller {
    public function login() {
        // If already logged in, redirect to appropriate page
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user_id'])) {
            if ($_SESSION['user_role'] === 'cashier') {
                header('Location: ' . BASE_URL . '/transaksi/input');
            } else {
                header('Location: ' . BASE_URL . '/');
            }
            exit;
        }

        $error = '';
        $userModel = $this->model('UserModel');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                $error = 'Username dan password harus diisi.';
            } else {
                $user = $userModel->getUserByUsername($username);
                
                if ($user) {
                    if (password_verify($password, $user['password'])) {
                        // Prevent Session Fixation attack
                        session_regenerate_id(true);

                        // Login success - set session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_username'] = $user['username'];
                        $_SESSION['user_nama'] = $user['nama_lengkap'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['last_activity'] = time();
                        
                        // Redirect based on role
                        if ($user['role'] === 'cashier') {
                            header('Location: ' . BASE_URL . '/transaksi/input');
                        } else {
                            header('Location: ' . BASE_URL . '/');
                        }
                        exit;
                    } else {
                        $error = 'Password yang Anda masukkan salah.';
                    }
                } else {
                    $error = 'Username tidak ditemukan.';
                }
            }
        }

        $company_name = $userModel->getCompanyName();

        $this->view('auth/login', [
            'error' => $error,
            'company_name' => $company_name
        ]);
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Clear all session data
        $_SESSION = [];

        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Destroy session
        session_destroy();

        // Redirect to login
        if (!headers_sent()) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        } else {
            echo '<script>window.location.href = "' . BASE_URL . '/auth/login";</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . BASE_URL . '/auth/login"></noscript>';
            exit;
        }
    }
}
