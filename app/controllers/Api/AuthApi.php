<?php
/**
 * API Controller: Authentication
 * Handles Firebase Auth token exchange and user session management.
 */

class AuthApi {
    private $currentUser = null;
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function setCurrentUser($user) {
        $this->currentUser = $user;
    }

    /**
     * POST /api/v1/auth/firebase-login
     * Body: { "firebase_uid": "...", "email": "...", "display_name": "..." }
     * 
     * Exchanges Firebase UID for local user data.
     * Creates user record if first login via Firebase.
     */
    public function firebase_login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiRouter::sendError('Method not allowed. Use POST.', 405);
        }
        
        $body = ApiRouter::getJsonBody();
        $firebaseUid = $body['firebase_uid'] ?? '';
        $email = $body['email'] ?? '';
        $displayName = $body['display_name'] ?? '';
        
        if (empty($firebaseUid)) {
            ApiRouter::sendError('firebase_uid diperlukan.', 400);
        }
        
        // Check if user exists with this Firebase UID
        $stmt = $this->db->prepare("SELECT id, username, nama_lengkap, role, firebase_uid FROM users WHERE firebase_uid = ?");
        $stmt->bind_param("s", $firebaseUid);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!$user) {
            // Jika akun belum terhubung dengan firebase_uid ini:
            // Cegah Auth Bypass: wajibkan verifikasi password untuk menautkan akun pertama kali
            $password = $body['password'] ?? '';
            
            if (!empty($email) && !empty($password)) {
                $usernameOnly = str_replace('@kasql.local', '', $email);
                
                $stmt2 = $this->db->prepare("SELECT id, username, password, nama_lengkap, role, firebase_uid FROM users WHERE username = ? OR username = ?");
                $stmt2->bind_param("ss", $email, $usernameOnly);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                $candidate = $result2->fetch_assoc();
                
                if ($candidate && password_verify($password, $candidate['password'])) {
                    // Verifikasi password sukses, aman untuk menautkan firebase_uid
                    $updateStmt = $this->db->prepare("UPDATE users SET firebase_uid = ? WHERE id = ?");
                    $updateStmt->bind_param("si", $firebaseUid, $candidate['id']);
                    $updateStmt->execute();
                    $candidate['firebase_uid'] = $firebaseUid;
                    unset($candidate['password']);
                    $user = $candidate;
                } else {
                    ApiRouter::sendError('Verifikasi akun gagal: Password salah.', 401);
                }
            } elseif (!empty($email)) {
                ApiRouter::sendError('Akun belum terhubung dengan Firebase UID ini. Silakan login konvensional atau sertakan password untuk menautkan akun.', 401);
            }
        }
        
        if (!$user) {
            ApiRouter::sendError('User tidak ditemukan. Hubungi Admin untuk mendaftarkan akun.', 404);
        }
        
        // Generate dev token for API access
        $token = FirebaseAuthHelper::generateDevToken($user['id'], $user['role']);
        
        // Get company name
        $companyName = 'USAHA';
        $settRes = $this->db->query("SELECT nama_perusahaan FROM pengaturan WHERE id = 1");
        if ($settRes && $row = $settRes->fetch_assoc()) {
            $companyName = $row['nama_perusahaan'];
        }
        
        ApiRouter::sendSuccess([
            'user' => [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'nama_lengkap' => $user['nama_lengkap'],
                'role' => $user['role'],
            ],
            'token' => $token,
            'company_name' => $companyName
        ]);
    }
    
    /**
     * POST /api/v1/auth/login
     * Body: { "username": "...", "password": "..." }
     * 
     * Traditional login for development/testing without Firebase.
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiRouter::sendError('Method not allowed. Use POST.', 405);
        }
        
        $body = ApiRouter::getJsonBody();
        $username = $body['username'] ?? '';
        $password = $body['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            ApiRouter::sendError('Username dan password diperlukan.', 400);
        }
        
        $stmt = $this->db->prepare("SELECT id, username, password, nama_lengkap, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!$user || !password_verify($password, $user['password'])) {
            ApiRouter::sendError('Username atau password salah.', 401);
        }
        
        // Generate token
        $token = FirebaseAuthHelper::generateDevToken($user['id'], $user['role']);
        
        // Get company name
        $companyName = 'USAHA';
        $settRes = $this->db->query("SELECT nama_perusahaan FROM pengaturan WHERE id = 1");
        if ($settRes && $row = $settRes->fetch_assoc()) {
            $companyName = $row['nama_perusahaan'];
        }
        
        ApiRouter::sendSuccess([
            'user' => [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'nama_lengkap' => $user['nama_lengkap'],
                'role' => $user['role'],
            ],
            'token' => $token,
            'company_name' => $companyName
        ]);
    }
    
    /**
     * GET /api/v1/auth/me
     * Returns current authenticated user data
     */
    public function me() {
        if (!$this->currentUser) {
            ApiRouter::sendError('Tidak terautentikasi.', 401);
        }
        
        ApiRouter::sendSuccess([
            'user' => $this->currentUser
        ]);
    }
}
?>
