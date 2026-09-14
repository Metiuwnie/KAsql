<?php
/**
 * Authentication & Authorization Middleware
 * Include this file at the top of every protected page.
 * Usage:
 *   require_once 'auth.php';
 *   require_role(['admin', 'accountant']);
 */

// Ensure config.php is loaded (starts session & connects DB)
require_once __DIR__ . '/config.php';

/**
 * Memeriksa apakah sesi telah kadaluarsa karena tidak ada aktivitas (Idle Timeout).
 */
function check_session_timeout() {
    if (is_logged_in()) {
        $timeout = function_exists('env') ? (int)env('SESSION_LIFETIME', 1800) : 1800; // default 30 menit
        if (isset($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity'] > $timeout)) {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();
            $base_url = defined('BASE_URL') ? BASE_URL : 'http://localhost/KAsql';
            header('Location: ' . $base_url . '/auth/login?expired=1');
            exit;
        }
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Check if user is logged in. If not, redirect to login page.
 */
function require_login() {
    check_session_timeout();
    if (!is_logged_in()) {
        $base_url = defined('BASE_URL') ? BASE_URL : 'http://localhost/KAsql';
        header('Location: ' . $base_url . '/auth/login');
        exit;
    }
}

/**
 * Check if current user has one of the allowed roles.
 * Redirects to login if not logged in, shows 403 if role not allowed.
 * 
 * @param array $allowed_roles e.g. ['admin', 'accountant']
 */
function require_role(array $allowed_roles) {
    require_login();
    
    $current_role = $_SESSION['user_role'] ?? '';
    if (!in_array($current_role, $allowed_roles)) {
        // Redirect cashier to transaksi/input if they try to access restricted pages
        if ($current_role === 'cashier') {
            $base_url = defined('BASE_URL') ? BASE_URL : 'http://localhost/KAsql';
            header('Location: ' . $base_url . '/transaksi/input');
            exit;
        }
        
        http_response_code(403);
        echo '<!DOCTYPE html><html><head><title>Akses Ditolak</title>';
        echo '<style>body{font-family:Inter,sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;background:#0f172a;color:#e2e8f0;}';
        echo '.box{text-align:center;padding:3rem;border-radius:1rem;background:rgba(30,41,59,0.8);border:1px solid rgba(59,130,246,0.3);max-width:450px;}';
        echo 'h1{font-size:4rem;margin:0;background:linear-gradient(135deg,#ef4444,#f97316);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}';
        echo 'p{color:#94a3b8;margin:1rem 0 2rem;}a{color:#3b82f6;text-decoration:none;font-weight:600;}</style></head>';
        echo '<body><div class="box"><h1>403</h1><h2>Akses Ditolak</h2>';
        echo '<p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>';
        echo '<a href="' . (defined('BASE_URL') ? BASE_URL : 'http://localhost/KAsql') . '/transaksi/input">← Kembali</a></div></body></html>';
        exit;
    }
}

/**
 * Check if user is currently logged in.
 * @return bool
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if current user has a specific role.
 * @param string $role
 * @return bool
 */
function is_role(string $role): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Get current logged-in user data from session.
 * @return array|null
 */
function get_current_user_data(): ?array {
    if (!is_logged_in()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['user_username'] ?? '',
        'nama_lengkap' => $_SESSION['user_nama'] ?? '',
        'role' => $_SESSION['user_role'] ?? ''
    ];
}

/**
 * Get display label for a role.
 * @param string $role
 * @return string
 */
function get_role_label(string $role): string {
    $labels = [
        'admin' => 'Administrator',
        'accountant' => 'Akuntan',
        'cashier' => 'Kasir'
    ];
    return $labels[$role] ?? ucfirst($role);
}

// Auto-check login on include (unless we're on login.php or MVC Auth controller)
$current_script = basename($_SERVER['PHP_SELF']);
$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';
$urlParts = explode('/', $url);
$controller = strtolower($urlParts[0] ?? '');

if ($controller !== 'auth' && $current_script !== 'login.php' && $current_script !== 'logout.php') {
    require_login();
}
?>
