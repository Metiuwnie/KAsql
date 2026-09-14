<?php

class UserModel extends Database {
    public function __construct() {
        parent::__construct();
    }

    /**
     * Get user data by username
     */
    public function getUserByUsername($username) {
        $stmt = $this->prepare("SELECT id, username, password, nama_lengkap, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }
        
        return null;
    }

    /**
     * Get company name from settings
     */
    public function getCompanyName() {
        // Asumsikan ada tabel pengaturan dengan baris id = 1
        $sql = "SELECT nama_perusahaan FROM pengaturan WHERE id = 1";
        $result = $this->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['nama_perusahaan'];
        }
        return 'USAHA';
    }

    public function getAllUsers() {
        $sql = "SELECT * FROM users ORDER BY role ASC, username ASC";
        $result = $this->query($sql);
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    public function getUserById($id) {
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    public function checkUsername($username, $exclude_id = null) {
        if ($exclude_id) {
            $sql = "SELECT id FROM users WHERE username = ? AND id != ?";
            $stmt = $this->prepare($sql);
            $stmt->bind_param("si", $username, $exclude_id);
        } else {
            $sql = "SELECT id FROM users WHERE username = ?";
            $stmt = $this->prepare($sql);
            $stmt->bind_param("s", $username);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    public function addUser($username, $nama_lengkap, $role, $password) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)";
        $stmt = $this->prepare($sql);
        $stmt->bind_param("ssss", $username, $hashed, $nama_lengkap, $role);
        return $stmt->execute();
    }

    public function updateUser($id, $username, $nama_lengkap, $role, $password = null) {
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $sql = "UPDATE users SET username = ?, nama_lengkap = ?, role = ?, password = ? WHERE id = ?";
            $stmt = $this->prepare($sql);
            $stmt->bind_param("ssssi", $username, $nama_lengkap, $role, $hashed, $id);
        } else {
            $sql = "UPDATE users SET username = ?, nama_lengkap = ?, role = ? WHERE id = ?";
            $stmt = $this->prepare($sql);
            $stmt->bind_param("sssi", $username, $nama_lengkap, $role, $id);
        }
        return $stmt->execute();
    }

    public function deleteUser($id) {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $this->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
