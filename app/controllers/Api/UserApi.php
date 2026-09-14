<?php
/**
 * API Controller: User Management (Admin only)
 */

class UserApi {
    private $currentUser = null;
    private $model;
    
    public function __construct() {
        require_once __DIR__ . '/../../models/UserModel.php';
        $this->model = new UserModel();
    }
    
    public function setCurrentUser($user) {
        $this->currentUser = $user;
    }
    
    /**
     * GET /api/v1/users
     */
    public function index() {
        ApiRouter::requireRole($this->currentUser, ['admin']);
        
        $users = $this->model->getAllUsers();
        $result = [];
        foreach ($users as $u) {
            $result[] = [
                'id' => (int)$u['id'],
                'username' => $u['username'],
                'nama_lengkap' => $u['nama_lengkap'],
                'role' => $u['role'],
            ];
        }
        
        ApiRouter::sendSuccess($result);
    }
    
    /**
     * POST /api/v1/users/store
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiRouter::sendError('Method not allowed. Use POST.', 405);
        }
        ApiRouter::requireRole($this->currentUser, ['admin']);
        
        $body = ApiRouter::getJsonBody();
        $username = $body['username'] ?? '';
        $namaLengkap = $body['nama_lengkap'] ?? '';
        $role = $body['role'] ?? 'cashier';
        $password = $body['password'] ?? '';
        
        if (empty($username) || empty($namaLengkap) || empty($password)) {
            ApiRouter::sendError('username, nama_lengkap, dan password diperlukan.', 400);
        }
        
        if (strlen($password) < 6) {
            ApiRouter::sendError('Password minimal harus 6 karakter.', 400);
        }
        
        $validRoles = ['admin', 'accountant', 'cashier'];
        if (!in_array($role, $validRoles)) {
            ApiRouter::sendError("Role tidak valid. Pilih: " . implode(', ', $validRoles), 400);
        }
        
        if ($this->model->checkUsername($username)) {
            ApiRouter::sendError('Username sudah digunakan.', 409);
        }
        
        try {
            $this->model->addUser($username, $namaLengkap, $role, $password);
            ApiRouter::sendSuccess(['message' => 'User berhasil ditambahkan.'], 201);
        } catch (Exception $e) {
            ApiRouter::sendError('Gagal: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * PUT /api/v1/users/update/{id}
     */
    public function update($id = null) {
        ApiRouter::requireRole($this->currentUser, ['admin']);
        if (!$id) ApiRouter::sendError('User ID diperlukan.', 400);
        
        $body = ApiRouter::getJsonBody();
        $username = $body['username'] ?? '';
        $namaLengkap = $body['nama_lengkap'] ?? '';
        $role = $body['role'] ?? '';
        $password = $body['password'] ?? null;
        
        if (empty($username) || empty($namaLengkap) || empty($role)) {
            ApiRouter::sendError('username, nama_lengkap, dan role diperlukan.', 400);
        }
        
        if (!empty($password) && strlen($password) < 6) {
            ApiRouter::sendError('Password baru minimal harus 6 karakter.', 400);
        }
        
        if ($this->model->checkUsername($username, $id)) {
            ApiRouter::sendError('Username sudah digunakan oleh user lain.', 409);
        }
        
        try {
            $this->model->updateUser($id, $username, $namaLengkap, $role, $password);
            ApiRouter::sendSuccess(['message' => 'User berhasil diupdate.']);
        } catch (Exception $e) {
            ApiRouter::sendError('Gagal: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * DELETE /api/v1/users/delete/{id}
     */
    public function delete($id = null) {
        ApiRouter::requireRole($this->currentUser, ['admin']);
        if (!$id) ApiRouter::sendError('User ID diperlukan.', 400);
        
        if ((int)$id === (int)$this->currentUser['id']) {
            ApiRouter::sendError('Tidak bisa menghapus akun sendiri.', 400);
        }
        
        try {
            $this->model->deleteUser($id);
            ApiRouter::sendSuccess(['message' => 'User berhasil dihapus.']);
        } catch (Exception $e) {
            ApiRouter::sendError('Gagal: ' . $e->getMessage(), 500);
        }
    }
}
?>
