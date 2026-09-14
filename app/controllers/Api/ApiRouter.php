<?php
/**
 * API Router — Central routing for REST API endpoints
 * Handles: CORS, JSON content-type, Firebase token auth, error handling
 * 
 * URL Pattern: /api/v1/{resource}/{action}/{param}
 */

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../app/helpers/JwtHelper.php';
require_once __DIR__ . '/../../../app/helpers/periode_helper.php';
require_once __DIR__ . '/../../../app/helpers/env_helper.php';

class ApiRouter {
    
    private $segments = [];
    private $method;
    private $currentUser = null;
    
    // Routes that don't require authentication
    private $publicRoutes = [
        'auth/login',
        'auth/register',
        'auth/firebase-login'
    ];
    
    public function __construct($url) {
        $this->method = $_SERVER['REQUEST_METHOD'];
        
        // Handle CORS preflight
        $this->setCorsHeaders();
        if ($this->method === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        // Set JSON content type
        header('Content-Type: application/json; charset=utf-8');
        
        // Parse URL: remove 'api/v1/' prefix
        $url = ltrim($url, '/');
        if (strpos($url, 'api/v1/') === 0) {
            $url = substr($url, 7); // Remove 'api/v1/'
        } elseif (strpos($url, 'api/') === 0) {
            $url = substr($url, 4); // Remove 'api/'
        }
        
        $this->segments = $url ? explode('/', $url) : [];
        
        // Authenticate (unless public route)
        $routeKey = implode('/', array_slice($this->segments, 0, 2));
        if (!in_array($routeKey, $this->publicRoutes)) {
            $this->authenticate();
        }
        
        // Route to controller
        $this->dispatch();
    }
    
    /**
     * Set CORS headers for cross-origin requests from Flutter
     */
    private function setCorsHeaders() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
    }
    
    /**
     * Authenticate request via Firebase ID token or Dev token
     */
    private function authenticate() {
        $token = FirebaseAuthHelper::getBearerToken();
        
        if (!$token) {
            $this->sendError('Token autentikasi tidak ditemukan. Sertakan header Authorization: Bearer {token}', 401);
        }
        
        // Try Firebase token first
        $payload = FirebaseAuthHelper::verifyIdToken($token);
        
        // Fallback to dev token
        if (!$payload) {
            $payload = FirebaseAuthHelper::verifyDevToken($token);
        }
        
        if (!$payload) {
            $this->sendError('Token tidak valid atau sudah expired.', 401);
        }
        
        // Load user data from database
        $userId = $payload['user_id'] ?? null;
        $firebaseUid = $payload['sub'] ?? $payload['uid'] ?? null;
        
        if ($userId) {
            // Dev token — load by user_id
            $db = new Database();
            $stmt = $db->prepare("SELECT id, username, nama_lengkap, role FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $this->currentUser = $result->fetch_assoc();
        } elseif ($firebaseUid) {
            // Firebase token — load by firebase_uid
            $db = new Database();
            $stmt = $db->prepare("SELECT id, username, nama_lengkap, role, firebase_uid FROM users WHERE firebase_uid = ?");
            $stmt->bind_param("s", $firebaseUid);
            $stmt->execute();
            $result = $stmt->get_result();
            $this->currentUser = $result->fetch_assoc();
        }
        
        if (!$this->currentUser) {
            $this->sendError('User tidak ditemukan di database.', 401);
        }
    }
    
    /**
     * Route request to the appropriate API controller
     */
    private function dispatch() {
        if (empty($this->segments)) {
            $this->sendSuccess(['message' => 'KAsql API v1.0', 'status' => 'running']);
            return;
        }
        
        $resource = $this->segments[0] ?? '';
        $action = $this->segments[1] ?? 'index';
        $param = $this->segments[2] ?? null;
        
        $controllerMap = [
            'auth'       => 'AuthApi',
            'dashboard'  => 'DashboardApi',
            'transaksi'  => 'TransaksiApi',
            'laporan'    => 'LaporanApi',
            'master'     => 'MasterApi',
            'jurnal'     => 'JurnalApi',
            'users'      => 'UserApi',
            'sync'       => 'SyncApi',
            'aset'       => 'AsetApi',
            'closing'    => 'ClosingApi',
            'analisis'   => 'AnalisisApi',
            'settings'   => 'SettingsApi',
        ];
        
        if (!isset($controllerMap[$resource])) {
            $this->sendError("Resource '$resource' tidak ditemukan.", 404);
        }
        
        $controllerName = $controllerMap[$resource];
        $controllerFile = __DIR__ . '/' . $controllerName . '.php';
        
        if (!file_exists($controllerFile)) {
            $this->sendError("Controller '$controllerName' belum diimplementasi.", 501);
        }
        
        require_once $controllerFile;
        
        if (!class_exists($controllerName)) {
            $this->sendError("Class '$controllerName' tidak ditemukan.", 500);
        }
        
        $controller = new $controllerName();
        
        // Inject dependencies
        if (method_exists($controller, 'setCurrentUser')) {
            $controller->setCurrentUser($this->currentUser);
        }
        
        // Convert kebab-case to camelCase for method name
        $methodName = str_replace('-', '_', $action);
        
        if (!method_exists($controller, $methodName)) {
            // Try 'index' as fallback for resources like GET /api/v1/dashboard
            if ($action === $resource && method_exists($controller, 'index')) {
                $methodName = 'index';
            } else {
                $this->sendError("Action '$action' tidak tersedia pada resource '$resource'.", 404);
            }
        }
        
        // Execute
        try {
            if ($param !== null) {
                $controller->$methodName($param);
            } else {
                $controller->$methodName();
            }
        } catch (Exception $e) {
            error_log('[KAsql API Error] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $isDebug = function_exists('env') ? env('APP_DEBUG', true) : true;
            if ($isDebug) {
                $this->sendError('Server error: ' . $e->getMessage(), 500);
            } else {
                $this->sendError('Terjadi kesalahan internal pada server.', 500);
            }
        }
    }
    
    /**
     * Get current authenticated user
     */
    public function getCurrentUser() {
        return $this->currentUser;
    }
    
    // --- Response Helpers (static for use in controllers) ---
    
    public static function sendSuccess($data, $code = 200) {
        http_response_code($code);
        echo json_encode([
            'status' => 'success',
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    public static function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'status' => 'error',
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Get JSON request body
     */
    public static function getJsonBody() {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?: [];
    }
    
    /**
     * Check if user has required role
     */
    public static function requireRole($currentUser, $allowedRoles) {
        if (!$currentUser || !isset($currentUser['role'])) {
            self::sendError('Autentikasi diperlukan.', 401);
        }
        if (!in_array($currentUser['role'], $allowedRoles)) {
            self::sendError('Akses ditolak. Role Anda: ' . $currentUser['role'], 403);
        }
    }
}
?>
