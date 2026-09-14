<?php

class AkunModel extends Database {
    public function __construct() {
        parent::__construct();
    }

    public function getAllAkun() {
        $sql = "SELECT * FROM akun ORDER BY kode_akun ASC";
        $result = $this->query($sql);
        
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    public function getAkunByKode($kode_akun) {
        $sql = "SELECT * FROM akun WHERE kode_akun = ?";
        $stmt = $this->prepare($sql);
        $stmt->bind_param("s", $kode_akun);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    public function addAkun($data) {
        $sql = "INSERT INTO akun (kode_akun, akun, aktiva_pasiva, kategori_neraca) VALUES (?, ?, ?, ?)";
        $stmt = $this->prepare($sql);
        $stmt->bind_param("ssss", $data['kode_akun'], $data['nama_akun'], $data['aktiva_pasiva'], $data['kategori_neraca']);
        return $stmt->execute();
    }

    public function updateAkun($old_kode, $data) {
        $sql = "UPDATE akun SET kode_akun = ?, akun = ?, aktiva_pasiva = ?, kategori_neraca = ? WHERE kode_akun = ?";
        $stmt = $this->prepare($sql);
        $stmt->bind_param("sssss", $data['kode_akun'], $data['nama_akun'], $data['aktiva_pasiva'], $data['kategori_neraca'], $old_kode);
        return $stmt->execute();
    }

    public function deleteAkun($kode_akun) {
        // Safety check: Pastikan akun tidak memiliki riwayat transaksi jurnal
        $check_trx = $this->prepare("SELECT COUNT(*) as cnt FROM transaksi WHERE kode_akun = ?");
        $check_trx->bind_param("s", $kode_akun);
        $check_trx->execute();
        $res_trx = $check_trx->get_result()->fetch_assoc();
        if ($res_trx && $res_trx['cnt'] > 0) {
            throw new Exception("Akun '{$kode_akun}' tidak dapat dihapus karena memiliki riwayat " . $res_trx['cnt'] . " baris transaksi di jurnal. Hapus atau pindahkan transaksi terkait terlebih dahulu.");
        }

        // Safety check: Pastikan akun tidak digunakan di detail reaksi (mapping)
        $check_reaksi = $this->prepare("SELECT COUNT(*) as cnt FROM detail_reaksi WHERE kode_akun = ?");
        $check_reaksi->bind_param("s", $kode_akun);
        $check_reaksi->execute();
        $res_r = $check_reaksi->get_result()->fetch_assoc();
        if ($res_r && $res_r['cnt'] > 0) {
            throw new Exception("Akun '{$kode_akun}' tidak dapat dihapus karena masih digunakan dalam mapping Aktivitas/Reaksi.");
        }

        // Safety check: Pastikan akun tidak terdaftar pada modul Aset Tetap
        $check_aset = $this->prepare("SELECT COUNT(*) as cnt FROM aset_tetap WHERE akun_aset = ? OR akun_akumulasi = ? OR akun_beban = ?");
        $check_aset->bind_param("sss", $kode_akun, $kode_akun, $kode_akun);
        $check_aset->execute();
        $res_aset = $check_aset->get_result()->fetch_assoc();
        if ($res_aset && $res_aset['cnt'] > 0) {
            throw new Exception("Akun '{$kode_akun}' tidak dapat dihapus karena terdaftar pada konfigurasi " . $res_aset['cnt'] . " data Aset Tetap.");
        }

        // Safety check: Pastikan akun tidak terdaftar pada modul Biaya Dibayar di Muka (Prepaid)
        $check_prepaid = $this->prepare("SELECT COUNT(*) as cnt FROM manajemen_prepaid WHERE akun_prepaid = ? OR akun_beban = ?");
        $check_prepaid->bind_param("ss", $kode_akun, $kode_akun);
        $check_prepaid->execute();
        $res_prepaid = $check_prepaid->get_result()->fetch_assoc();
        if ($res_prepaid && $res_prepaid['cnt'] > 0) {
            throw new Exception("Akun '{$kode_akun}' tidak dapat dihapus karena terdaftar pada konfigurasi " . $res_prepaid['cnt'] . " Biaya Dibayar di Muka (Prepaid).");
        }

        $sql = "DELETE FROM akun WHERE kode_akun = ?";
        $stmt = $this->prepare($sql);
        $stmt->bind_param("s", $kode_akun);
        return $stmt->execute();
    }
}
