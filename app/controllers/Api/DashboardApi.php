<?php
/**
 * API Controller: Dashboard
 * Returns financial summary data for mobile dashboard.
 */

class DashboardApi {
    private $currentUser = null;
    
    public function setCurrentUser($user) {
        $this->currentUser = $user;
    }
    
    /**
     * GET /api/v1/dashboard?tahun=2026
     */
    public function index() {
        require_once __DIR__ . '/../../models/DashboardModel.php';
        $model = new DashboardModel();
        
        $years = $model->getAvailableYears();
        $calendarYear = intval(date('Y'));
        if (!in_array($calendarYear, $years)) {
            $years[] = $calendarYear;
            rsort($years);
        }
        $currentYear = isset($_GET['tahun']) && !empty($_GET['tahun']) ? intval($_GET['tahun']) : $calendarYear;
        
        $totalAset = $model->getTotalAset($currentYear);
        $totalKewajiban = $model->getTotalKewajiban($currentYear);
        $totalPendapatan = $model->getTotalPendapatan($currentYear);
        $totalBeban = $model->getTotalBeban($currentYear);
        $labaRugiBersih = $totalPendapatan - $totalBeban;
        $totalEkuitas = $totalAset - $totalKewajiban;
        
        $chartData = $model->getMonthlyChartData($currentYear);
        $assetComposition = $model->getAssetComposition($currentYear);
        
        // Get company name
        $db = new Database();
        $companyName = 'USAHA';
        $settRes = $db->query("SELECT nama_perusahaan FROM pengaturan WHERE id = 1");
        if ($settRes && $row = $settRes->fetch_assoc()) {
            $companyName = $row['nama_perusahaan'];
        }
        
        ApiRouter::sendSuccess([
            'company_name' => $companyName,
            'current_year' => (int)$currentYear,
            'available_years' => array_map('intval', $years),
            'summary' => [
                'total_aset' => (float)$totalAset,
                'total_kewajiban' => (float)$totalKewajiban,
                'total_pendapatan' => (float)$totalPendapatan,
                'total_beban' => (float)$totalBeban,
                'laba_rugi_bersih' => (float)$labaRugiBersih,
                'total_ekuitas' => (float)$totalEkuitas,
            ],
            'chart' => [
                'labels' => ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'],
                'pendapatan' => array_map('floatval', $chartData['pendapatan']),
                'beban' => array_map('floatval', $chartData['beban']),
            ],
            'asset_composition' => [
                'labels' => $assetComposition['labels'],
                'data' => array_map('floatval', $assetComposition['data']),
            ]
        ]);
    }
}
?>
