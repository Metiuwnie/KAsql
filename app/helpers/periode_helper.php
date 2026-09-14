<?php
/**
 * Periode Akuntansi Helper
 * 
 * Membantu memvalidasi tanggal transaksi terhadap periode akuntansi yang berstatus 'closed'
 * atau periode yang dikunci oleh sistem (Mei 2026 ke bawah).
 */

/**
 * Memeriksa apakah suatu tanggal berada pada periode yang sudah ditutup.
 * 
 * @param string $tanggal Format YYYY-MM-DD
 * @param mixed $db Optional Database instance or mysqli connection
 * @return string|false Mengembalikan pesan error (string) jika ditutup/dikunci, atau false jika terbuka (aman).
 */
function is_periode_closed(string $tanggal, $db = null) {
    if (empty($tanggal)) {
        return false;
    }

    // Periode Mei 2026 ke bawah dikunci sistem
    if (strtotime($tanggal) <= strtotime('2026-05-31')) {
        return "Periode tanggal " . date('d-m-Y', strtotime($tanggal)) . " (Mei 2026 ke bawah) dikunci oleh sistem dan tidak dapat diubah.";
    }

    $conn = null;
    if (is_object($db) && method_exists($db, 'getConnection')) {
        $conn = $db->getConnection();
    } elseif ($db instanceof mysqli) {
        $conn = $db;
    } else {
        $tmpDb = new Database();
        $conn = $tmpDb->getConnection();
    }

    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare("SELECT nama_periode, status FROM periode_akuntansi WHERE ? BETWEEN tanggal_mulai AND tanggal_selesai AND status = 'closed' LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $tanggal);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            return "Periode \"" . htmlspecialchars($row['nama_periode']) . "\" sudah ditutup (closed). Transaksi tidak dapat disimpan atau diubah pada periode ini.";
        }
    }

    return false;
}
