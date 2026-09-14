<!DOCTYPE html>
<html lang="id">
<head>
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data['title'] ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css?v=<?= time() ?>">
    <script src="<?= BASE_URL ?>/public/js/script.js?v=2.0" defer></script>
</head>
<body>
<div id="loadingOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.85); z-index:9999; justify-content:center; align-items:center; backdrop-filter: blur(5px);">
    <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <style>
            @keyframes spin-svg { 100% { transform: rotate(360deg); } }
            .svg-spinner { animation: spin-svg 1.2s linear infinite; transform-origin: center; }
            .svg-pulse { animation: pulse-svg 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
            @keyframes pulse-svg { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
        </style>
        <path class="svg-spinner" d="M21 12a9 9 0 1 1-6.219-8.56"></path>
        <circle class="svg-pulse" cx="12" cy="12" r="3" fill="#10b981" stroke="none"></circle>
    </svg>
</div>

<?php include __DIR__ . '/../layout/sidebar.php'; ?>

<div class="main-content">
    <?php include __DIR__ . '/../layout/header.php'; ?>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-color); margin-bottom: 2rem; padding-bottom: 1rem;">
            <h2 style="border: none; margin: 0; padding: 0;" class="page-title"><?= $data['title'] ?></h2>
        </div>
        
        <?php if(!empty($data['success'])): ?>
            <div style="background-color: #d1fae5; border: 1px solid #10b981; color: #065f46; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <span><?= htmlspecialchars($data['success']) ?></span>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <form method="GET" action="" class="form-row">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="bulan" class="form-label">Sampai Bulan:</label>
                    <select name="bulan" id="bulan" class="form-control" style="width: auto;">
                        <option value="">-- Akhir Tahun --</option>
                        <?php foreach($data['nama_bulan'] as $num => $name): ?>
                            <option value="<?= $num ?>" <?= ($data['bulan'] == $num) ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="tahun" class="form-label">Tahun:</label>
                    <select name="tahun" id="tahun" class="form-control" style="width: auto;" required>
                        <?php 
                        $start = 2020;
                        $end = date('Y') + 2;
                        for($y = $start; $y <= $end; $y++): ?>
                            <option value="<?= $y ?>" <?= ($data['tahun'] == $y) ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Pilih Periode</button>
            </form>
        </div>

        <div class="card">
            <h3 style="margin-bottom: 15px;">Analisis Neraca dengan AI</h3>
            
            <form method="POST" action="" id="analyzeForm" onsubmit="document.getElementById('loadingOverlay').style.display='flex';">
                <?= csrf_field() ?>
                
                <div class="ai-status-bar">
                    <div class="ai-status-info">
                        <span class="ai-chip <?= !empty($_SESSION['api_key']) ? 'active' : '' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2c-.3 5.4-4.6 9.7-10 10 5.4.3 9.7 4.6 10 10 .3-5.4 4.6-9.7 10-10-5.4-.3-9.7-4.6-10-10z"/></svg>
                            <?= !empty($_SESSION['api_key']) ? 'Gemini AI Aktif' : 'Validasi Sistem Lokal' ?>
                        </span>
                        <span class="ai-status-desc">
                            <?= !empty($_SESSION['api_key']) ? 'Model: ' . htmlspecialchars($_SESSION['model_choice'] ?? 'gemini-3.5-flash-lite') : 'Analisis rasio SAK EMKM lokal. Hubungkan Gemini API Key untuk rekomendasi mendalam.' ?>
                        </span>
                    </div>
                    <button type="button" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.8rem;" onclick="window.openAiModal && window.openAiModal()">
                        <?= !empty($_SESSION['api_key']) ? 'Ubah Pengaturan' : 'Atur API Key' ?>
                    </button>
                </div>
                
                <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 8px;">
                    <button type="submit" name="analyze" class="btn btn-gemini">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 6px;">
                            <path d="M12 2c-.3 5.4-4.6 9.7-10 10 5.4.3 9.7 4.6 10 10 .3-5.4 4.6-9.7 10-10-5.4-.3-9.7-4.6-10-10z"/>
                        </svg>
                        Jalankan Analisis Finansial Cerdas
                    </button>

                    <div class="ai-disclaimer-note">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        <span>Interpretasi rasio keuangan SAK EMKM sebagai pertimbangan strategis manajemen.</span>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($data['analysis_result']): ?>
        <div class="card">
            <h3 style="margin-bottom: 15px;">Hasil Analisa & Pembuatan Laporan</h3>
            <div class="analysis-content" style="line-height: 1.8; font-size: 1.05rem;">
                <?= $data['analysis_result'] ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>


