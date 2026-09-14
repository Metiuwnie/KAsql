<?php
/**
 * API Controller: Sync (Offline-First)
 * Handles batch synchronization of offline transactions.
 */

class SyncApi {
    private $currentUser = null;
    
    public function setCurrentUser($user) {
        $this->currentUser = $user;
    }
    
    /**
     * POST /api/v1/sync/transactions
     * Body: { "transactions": [ { "local_id": 1, "tanggal": "...", "deskripsi": "...", "created_at": "...", "details": [...] }, ... ] }
     */
    public function transactions() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiRouter::sendError('Method not allowed. Use POST.', 405);
        }
        
        $body = ApiRouter::getJsonBody();
        $transactions = $body['transactions'] ?? [];
        
        if (empty($transactions)) {
            ApiRouter::sendSuccess(['synced' => 0, 'message' => 'Tidak ada transaksi untuk disinkronisasi.']);
            return;
        }
        
        require_once __DIR__ . '/../../models/JurnalModel.php';
        $model = new JurnalModel();
        
        $synced = 0;
        $errors = [];
        $results = [];
        
        foreach ($transactions as $idx => $trx) {
            $tanggal = $trx['tanggal'] ?? date('Y-m-d');
            $deskripsi = $trx['deskripsi'] ?? '';
            $details = $trx['details'] ?? [];
            $localId = $trx['local_id'] ?? null;
            $deviceCreatedAt = $trx['created_at'] ?? null;
            
            // Use device created_at for journal chronology
            if ($deviceCreatedAt) {
                $tanggal = date('Y-m-d', strtotime($deviceCreatedAt));
            }
            
            if (empty($details)) {
                $errors[] = "Transaksi #$idx: detail kosong.";
                continue;
            }
            
            // Validate balance
            $totalDebit = 0;
            $totalKredit = 0;
            foreach ($details as $d) {
                if (strtolower($d['dk'] ?? '') === 'debit') $totalDebit += ($d['nilai'] ?? 0);
                else $totalKredit += ($d['nilai'] ?? 0);
            }
            
            if (abs($totalDebit - $totalKredit) > 0.01) {
                $errors[] = "Transaksi #$idx: tidak seimbang (D:$totalDebit K:$totalKredit).";
                continue;
            }
            
            $model->begin_transaction();
            try {
                // Generate kode
                $newKode = "A001";
                $qMax = $model->query("SELECT MAX(CAST(SUBSTRING(kode_transaksi, 2) AS UNSIGNED)) as max_kode FROM detil_transaksi WHERE kode_transaksi LIKE 'A%' FOR UPDATE");
                if ($qMax && $rMax = $qMax->fetch_assoc()) {
                    if (!empty($rMax['max_kode'])) {
                        $nextNum = $rMax['max_kode'] + 1;
                        $newKode = "A" . str_pad($nextNum, 3, "0", STR_PAD_LEFT);
                    }
                }
                
                $createdBy = $this->currentUser['id'] ?? null;
                $stmt = $model->prepare("INSERT INTO detil_transaksi (kode_transaksi, tanggal, deskripsi, created_by, status_verifikasi) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->bind_param("sssi", $newKode, $tanggal, $deskripsi, $createdBy);
                $stmt->execute();
                
                $stmtT = $model->prepare("INSERT INTO transaksi (kode_transaksi, kode_akun, dk, nilai) VALUES (?, ?, ?, ?)");
                foreach ($details as $d) {
                    $stmtT->bind_param("sssd", $newKode, $d['kode_akun'], $d['dk'], $d['nilai']);
                    $stmtT->execute();
                }
                
                $model->commit();
                $synced++;
                
                $results[] = [
                    'local_id' => $localId,
                    'global_id' => $newKode,
                    'status' => 'synced'
                ];
            } catch (Exception $e) {
                $model->rollback();
                $errors[] = "Transaksi #$idx: " . $e->getMessage();
                $results[] = [
                    'local_id' => $localId,
                    'global_id' => null,
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        ApiRouter::sendSuccess([
            'synced' => $synced,
            'total' => count($transactions),
            'errors' => $errors,
            'results' => $results,
        ]);
    }
}
?>
