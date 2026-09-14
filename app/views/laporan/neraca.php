<?php
$nama_bulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <script>
        // Mencegah FOUC (Flash of Unstyled Content)
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neraca — <?= htmlspecialchars($_SESSION['nama_perusahaan'] ?? 'USAHA') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css?v=<?= time() ?>">
    <script src="<?= BASE_URL ?>/public/js/script.js?v=2.0" defer></script>
    <style>
        /* High-Contrast Semantic Colors for Balance Sheet */
        :root {
            --fin-positive-color: #047857;
            --fin-negative-color: #b91c1c;
            --fin-warning-color: #b45309;

            --kpi-good-text: #047857;
            --kpi-good-bg: #ecfdf5;
            --kpi-good-border: #a7f3d0;

            --kpi-bad-text: #b91c1c;
            --kpi-bad-bg: #fef2f2;
            --kpi-bad-border: #fecaca;

            --balance-ok-bg: #ecfdf5;
            --balance-ok-text: #065f46;
            --balance-ok-border: #059669;

            --balance-err-bg: #fef2f2;
            --balance-err-text: #991b1b;
            --balance-err-border: #dc2626;
        }

        [data-theme="dark"] {
            --fin-positive-color: #34d399;
            --fin-negative-color: #f87171;
            --fin-warning-color: #fbbf24;

            --kpi-good-text: #34d399;
            --kpi-good-bg: rgba(16, 185, 129, 0.12);
            --kpi-good-border: rgba(52, 211, 153, 0.25);

            --kpi-bad-text: #f87171;
            --kpi-bad-bg: rgba(239, 68, 68, 0.12);
            --kpi-bad-border: rgba(248, 113, 113, 0.25);

            --balance-ok-bg: rgba(16, 185, 129, 0.12);
            --balance-ok-text: #34d399;
            --balance-ok-border: rgba(52, 211, 153, 0.35);

            --balance-err-bg: rgba(239, 68, 68, 0.12);
            --balance-err-text: #f87171;
            --balance-err-border: rgba(248, 113, 113, 0.35);
        }

        .neraca-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2.25rem;
            margin-bottom: 2rem;
            align-items: start;
            position: relative;
        }
        @media (min-width: 901px) {
            .neraca-container::after {
                content: '';
                position: absolute;
                top: 0;
                bottom: 24px;
                left: 50%;
                width: 1px;
                background: var(--border-color);
                transform: translateX(-50%);
                opacity: 0.65;
                pointer-events: none;
            }
        }
        @media (max-width: 900px) {
            .neraca-container {
                grid-template-columns: 1fr;
                gap: 1.75rem;
            }
        }
        .neraca-col {
            width: 100%;
        }
        .neraca-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: var(--bg-surface);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
            border: 1.5px solid var(--border-color);
        }
        .neraca-table th, .neraca-table td {
            padding: 11px 16px;
            border-bottom: 1px solid var(--border-color);
        }
        /* Vertical Column Separation between Account and Nominal */
        .neraca-table th:last-child,
        .neraca-table td:last-child {
            border-left: 1px solid var(--border-color);
            width: 36%;
        }
        .neraca-header {
            background-color: #18181b;
            color: #ffffff;
            font-weight: 800;
            font-size: 0.95rem;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 14px 18px !important;
            border: none !important;
            border-top: 3.5px solid #059669 !important; /* Emerald accent for Aktiva */
            border-left: none !important;
        }
        .neraca-header.pasiva {
            background-color: #18181b;
            color: #ffffff;
            border: none !important;
            border-top: 3.5px solid #3b82f6 !important; /* Blue accent for Pasiva */
            border-left: none !important;
        }
        [data-theme="dark"] .neraca-header {
            background-color: #18181b;
            color: #ffffff;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-top: 3.5px solid #10b981 !important;
        }
        [data-theme="dark"] .neraca-header.pasiva {
            background-color: #18181b;
            color: #ffffff;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-top: 3.5px solid #60a5fa !important;
        }
        .neraca-subhead th {
            background: var(--table-header-bg);
            color: var(--text-muted);
            font-size: 0.74rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 9px 16px;
            border-bottom: 1.5px solid var(--border-color);
        }
        [data-theme="dark"] .neraca-subhead th {
            background: rgba(255, 255, 255, 0.04);
            color: #a1a1aa;
        }
        .neraca-group td {
            font-weight: 800;
            font-size: 0.82rem;
            color: var(--text-heading);
            text-transform: uppercase;
            padding: 12px 18px;
            border-top: 3px solid var(--border-color);
            border-bottom: 1.5px solid var(--border-color);
            background-color: var(--table-header-bg);
            letter-spacing: 0.07em;
            border-left: none !important;
        }
        .neraca-group.first-group td {
            border-top: none !important;
        }
        [data-theme="dark"] .neraca-group td {
            background-color: rgba(255, 255, 255, 0.04);
            color: #ffffff;
            border-top: 3px solid rgba(255, 255, 255, 0.12);
            border-bottom: 1.5px solid rgba(255, 255, 255, 0.08);
            border-left: none !important;
        }
        [data-theme="dark"] .neraca-group.first-group td {
            border-top: none !important;
        }
        .neraca-item {
            transition: background 0.15s ease;
        }
        .neraca-item:nth-child(even) {
            background-color: rgba(125, 125, 125, 0.02);
        }
        .neraca-item:hover {
            background-color: var(--bg-surface-hover);
        }
        .neraca-item td:first-child {
            padding-left: 28px;
            color: var(--text-main);
            font-weight: 500;
            font-size: 0.92rem;
        }
        .neraca-item td:last-child {
            font-family: var(--font-mono, monospace);
            font-variant-numeric: tabular-nums;
            font-size: 0.92rem;
            color: var(--text-heading);
            font-weight: 600;
        }
        .neraca-subtotal td {
            font-weight: 700;
            background-color: #f4f4f2;
            border-top: 1px solid var(--border-color);
            border-bottom: 2px solid var(--border-color);
            font-size: 0.92rem;
        }
        .neraca-subtotal td:last-child {
            font-family: var(--font-mono, monospace);
            font-variant-numeric: tabular-nums;
            color: var(--text-heading);
            font-weight: 800;
        }
        [data-theme="dark"] .neraca-subtotal td {
            background-color: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.1);
        }
        .neraca-total {
            background-color: #18181b;
            color: #ffffff;
            font-weight: 800;
            font-size: 1.05rem;
            border: none;
        }
        .neraca-total td {
            color: #ffffff !important;
            padding: 14px 18px !important;
            border: none !important;
        }
        .neraca-total td:last-child {
            border-left: 1px solid rgba(255, 255, 255, 0.15) !important;
            font-family: var(--font-mono, monospace);
            font-variant-numeric: tabular-nums;
            font-size: 1.1rem;
        }
        [data-theme="dark"] .neraca-total {
            background-color: #27272a;
        }
        .text-right { text-align: right; }
        
        /* Print Styles for Accounting Report */
        @media screen {
            .print-only { display: none !important; }
        }
        @media print {
            @page { size: A4 portrait; margin: 1.2cm; }
            body { 
                font-family: "Times New Roman", Times, serif !important; 
                background: #fff !important; 
                color: #000 !important; 
                font-size: 11pt !important;
            }
            .no-print, nav, .navbar, .page-title, form, .form-row, .header-info-card, .sidebar { display: none !important; }
            
            .container { 
                width: 100% !important; 
                max-width: none !important; 
                margin: 0 !important; 
                padding: 0 !important; 
                box-shadow: none !important;
                border: none !important;
            }
            
            div[style*="border-bottom: 2px solid var(--border-color)"] { display: none !important; }

            .print-only { display: block !important; margin-bottom: 10px !important; }
            
            /* Neraca Layout Fixes for PDF Print */
            .neraca-container { 
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                gap: 20px !important;
                margin-bottom: 20px !important; 
                align-items: flex-start !important; 
                page-break-inside: avoid !important;
            }
            .neraca-col { 
                width: 48% !important; 
                display: block !important;
            }
            
            .neraca-table { 
                width: 100% !important;
                border: 2px solid #000 !important;
                border-collapse: collapse !important;
                background: transparent !important;
                margin: 0 !important;
                border-radius: 0 !important;
                overflow: visible !important;
            }
            
            .neraca-table th, .neraca-table td { 
                border: 1px solid #000 !important;
                padding: 6px 8px !important; 
                color: #000 !important; 
                font-size: 10.5pt !important;
                line-height: 1.35 !important;
            }
            
            .neraca-spacer td {
                height: 15px !important;
                border-bottom: none !important;
                border-top: none !important;
                background-color: #fff !important;
                padding: 0 !important;
            }
            
            .neraca-header, .neraca-header.pasiva { 
                background-color: #e2e8f0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                font-size: 11.5pt !important;
                text-align: center !important;
                padding: 8px 0 !important;
                font-weight: bold !important;
                color: #000 !important;
            }
            
            .neraca-group { background: transparent !important; }
            .neraca-group td { 
                font-weight: bold !important;
                text-transform: uppercase !important;
                padding-top: 8px !important;
                padding-bottom: 8px !important;
                background-color: #f8fafc !important;
                text-align: left !important;
                color: #000 !important;
                font-size: 10.5pt !important;
            }
            
            .neraca-item td { color: #000 !important; }
            .neraca-item td:first-child { padding-left: 15px !important; text-align: left !important; }
            .neraca-item td:last-child { text-align: right !important; }
            
            .neraca-subtotal { background: transparent !important; }
            .neraca-subtotal td { 
                font-weight: bold !important;
                font-style: italic !important;
                background-color: #f1f5f9 !important;
            }
            
            .neraca-total { background: #cbd5e1 !important; }
            .neraca-total td { 
                font-weight: bold !important; 
                font-size: 11pt !important;
                padding-top: 8px !important;
                padding-bottom: 8px !important;
                color: #000 !important;
            }
            
            .balance-status {
                display: none !important;
            }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../layout/sidebar.php'; ?>

<div class="main-content">
    <?php include __DIR__ . '/../layout/header.php'; ?>
<div class="container">
    <div class="report-header-row">
        <div>
            <h2 style="border: none; margin: 0; padding: 0;" class="page-title">Neraca (Balance Sheet)</h2>
        </div>
        <button type="button" onclick="window.print()" class="btn-print no-print" title="Cetak Laporan atau Simpan PDF">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            <span>Cetak Laporan</span>
        </button>
    </div>
    
    <!-- Filter Periode Form -->
    <div class="card no-print" style="margin-bottom: 1.75rem;">
        <form method="GET" action="<?= BASE_URL ?>/laporan/neraca" class="form-row">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="bulan" class="form-label">Bulan:</label>
                <select name="bulan" id="bulan" class="form-control" style="width: auto;">
                    <option value="">-- Akhir Tahun --</option>
                    <?php foreach($nama_bulan as $num => $name): ?>
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
            <button type="submit" class="btn btn-primary">Tampilkan Neraca</button>
        </form>
    </div>

    <!-- Interactive Financial Health Card (SAK EMKM KPI & AI Integration) -->
    <?php if (!empty($data['metrics'])): 
        $m = $data['metrics'];
        $h = $m['health'];
        $r = $m['ratios'];
        $s = $m['summary'];
        $is_health_good = ($h['score'] ?? 0) >= 60;
        $health_bg = $is_health_good ? 'var(--kpi-good-bg)' : (($h['score'] ?? 0) >= 40 ? 'var(--warning-bg)' : 'var(--kpi-bad-bg)');
        $health_text = $is_health_good ? 'var(--kpi-good-text)' : (($h['score'] ?? 0) >= 40 ? 'var(--fin-warning-color)' : 'var(--kpi-bad-text)');
        $health_border = $is_health_good ? 'var(--kpi-good-border)' : (($h['score'] ?? 0) >= 40 ? 'var(--warning-color)' : 'var(--kpi-bad-border)');
    ?>
    <div class="card no-print financial-health-card" style="margin-bottom: 2rem; background: var(--bg-surface); border: 1.5px solid var(--border-color); border-radius: 16px; padding: 1.35rem 1.6rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 1.15rem;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: <?= $health_bg ?>; color: <?= $health_text ?>; border: 1.5px solid <?= $health_border ?>; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; font-weight: 800;">
                    <?= $h['score'] ?>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main);">Kesehatan Finansial: <?= htmlspecialchars($h['status']) ?></h3>
                        <span class="badge" style="background: <?= $health_bg ?>; color: <?= $health_text ?>; border: 1px solid <?= $health_border ?>; font-size: 0.72rem; padding: 3px 9px; font-weight: 700; border-radius: 6px;">Skor <?= $h['score'] ?>/100</span>
                    </div>
                    <p style="margin: 3px 0 0 0; font-size: 0.84rem; color: var(--text-muted);"><?= htmlspecialchars($h['desc']) ?></p>
                </div>
            </div>
            
            <div>
                <a href="<?= BASE_URL ?>/analisis/neraca?tahun=<?= $data['tahun'] ?>&bulan=<?= $data['bulan'] ?>" class="btn btn-gemini">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2c-.3 5.4-4.6 9.7-10 10 5.4.3 9.7 4.6 10 10 .3-5.4 4.6-9.7 10-10-5.4-.3-9.7-4.6-10-10z"/>
                    </svg>
                    <span>Analisis dengan AI</span>
                </a>
            </div>
        </div>

        <!-- KPI Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.9rem;">
            <div style="background: var(--bg-main); padding: 0.75rem 0.95rem; border-radius: 10px; border: 1px solid var(--border-color);">
                <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Current Ratio</div>
                <div style="font-size: 1.15rem; font-weight: 800; color: <?= $r['current_ratio'] >= 1.5 ? 'var(--fin-positive-color)' : 'var(--fin-warning-color)' ?>; margin-top: 2px;">
                    <?= $r['current_ratio'] > 50 ? '> 50x' : number_format($r['current_ratio'], 2) . 'x' ?>
                </div>
                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">Acuan aman ≥ 1.50x</div>
            </div>
            <div style="background: var(--bg-main); padding: 0.75rem 0.95rem; border-radius: 10px; border: 1px solid var(--border-color);">
                <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Cash Ratio</div>
                <div style="font-size: 1.15rem; font-weight: 800; color: <?= $r['cash_ratio'] >= 0.5 ? 'var(--fin-positive-color)' : 'var(--fin-warning-color)' ?>; margin-top: 2px;">
                    <?= $r['cash_ratio'] > 50 ? '> 50x' : number_format($r['cash_ratio'], 2) . 'x' ?>
                </div>
                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">Kas vs Utang Lancar</div>
            </div>
            <div style="background: var(--bg-main); padding: 0.75rem 0.95rem; border-radius: 10px; border: 1px solid var(--border-color);">
                <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Debt to Asset (DAR)</div>
                <div style="font-size: 1.15rem; font-weight: 800; color: <?= $r['dar'] <= 50 ? 'var(--fin-positive-color)' : 'var(--fin-negative-color)' ?>; margin-top: 2px;">
                    <?= number_format($r['dar'], 2) ?>%
                </div>
                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">Batas aman ≤ 50%</div>
            </div>
            <div style="background: var(--bg-main); padding: 0.75rem 0.95rem; border-radius: 10px; border: 1px solid var(--border-color);">
                <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Modal Kerja (NWC)</div>
                <div style="font-size: 1.1rem; font-weight: 800; color: var(--text-heading); margin-top: 2px;">
                    Rp <?= number_format($s['modal_kerja_bersih'], 0, ',', '.') ?>
                </div>
                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">Aktiva Lancar - Utang</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php 
        if (!empty($data['bulan'])) {
            $last_day = date("t", strtotime($data['tahun'] . "-" . $data['bulan'] . "-01"));
            $periode_str = "Per " . $last_day . " " . $nama_bulan[$data['bulan']] . " " . $data['tahun'];
        } else {
            $periode_str = "Per 31 Desember " . $data['tahun'];
        }

        $aktiva_lancar = [];
        $aktiva_tetap = [];
        $aktiva_lainnya = [];
        
        $utang_lancar = [];
        $utang_jp = [];
        $ekuitas = [];

        foreach ($data['neraca_data'] as $row) {
            $kode = $row['kode_akun'];
            $nama = $row['akun'];
            $bal = floatval($row['final_balance']);
            $kategori = trim($row['kategori_neraca'] ?? '');
            
            if (abs($bal) < 0.01) continue; 
            
            if ($kategori === 'Aktiva Tetap') {
                $aktiva_tetap[] = ['nama' => $nama, 'saldo' => $bal];
            } else if ($kategori === 'Aktiva Lancar') {
                $aktiva_lancar[] = ['nama' => $nama, 'saldo' => $bal];
            } else if ($kategori === 'Utang Jangka Panjang') {
                $utang_jp[] = ['nama' => $nama, 'saldo' => $bal];
            } else if ($kategori === 'Utang Lancar') {
                $utang_lancar[] = ['nama' => $nama, 'saldo' => $bal];
            } else if ($kategori === 'Ekuitas') {
                $ekuitas[] = ['nama' => $nama, 'saldo' => $bal];
            } else {
                if (substr($kode, 0, 1) === '1') {
                    if (in_array($kode, ['104', '106'])) {
                        $aktiva_tetap[] = ['nama' => $nama, 'saldo' => $bal];
                    } else {
                        $aktiva_lancar[] = ['nama' => $nama, 'saldo' => $bal];
                    }
                } else if (substr($kode, 0, 1) === '2') {
                    if (in_array($kode, ['251'])) {
                        $utang_jp[] = ['nama' => $nama, 'saldo' => $bal];
                    } else {
                        $utang_lancar[] = ['nama' => $nama, 'saldo' => $bal];
                    }
                } else if (substr($kode, 0, 1) === '3') {
                    $ekuitas[] = ['nama' => $nama, 'saldo' => $bal];
                }
            }
        }

        $net_income = $data['net_income_for_neraca'] ?? 0;
        $laba_rugi_berjalan = -($net_income);
        
        if (abs($laba_rugi_berjalan) >= 0.01) {
            $ekuitas[] = ['nama' => 'Laba/Rugi Berjalan', 'saldo' => $laba_rugi_berjalan];
        }

        function renderCategory($title, $items, &$grandTotal, $is_first = false) {
            $html = '';
            $group_class = $is_first ? 'neraca-group first-group' : 'neraca-group';
            $html .= '<tr class="' . $group_class . '">
                        <td colspan="2">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="display: inline-block; width: 4px; height: 13px; border-radius: 2px; background: var(--text-muted);"></span>
                                    <span>' . htmlspecialchars($title) . '</span>
                                </div>
                                <span style="font-size: 0.68rem; font-weight: 700; color: var(--text-muted); letter-spacing: 0.06em;">KATEGORI</span>
                            </div>
                        </td>
                      </tr>';
            $subtotal = 0;
            if (count($items) > 0) {
                foreach ($items as $item) {
                    $saldo = $item['saldo'];
                    $subtotal += $saldo;
                    $grandTotal += $saldo;
                    $display_val = $saldo < 0 ? '(' . number_format(abs($saldo), 0, ',', '.') . ')' : number_format($saldo, 0, ',', '.');
                    $html .= '<tr class="neraca-item">
                                <td>' . htmlspecialchars($item['nama']) . '</td>
                                <td class="text-right">' . $display_val . '</td>
                              </tr>';
                }
            } else {
                $html .= '<tr class="neraca-item">
                            <td style="font-style:italic; color:var(--text-muted);">- Tidak ada akun -</td>
                            <td class="text-right">-</td>
                          </tr>';
            }
            $display_subtotal = $subtotal < 0 ? '(' . number_format(abs($subtotal), 0, ',', '.') . ')' : number_format($subtotal, 0, ',', '.');
            $html .= '<tr class="neraca-subtotal">
                        <td>TOTAL ' . htmlspecialchars(strtoupper($title)) . '</td>
                        <td class="text-right">' . $display_subtotal . '</td>
                      </tr>';
            return $html;
        }
    ?>

    <!-- Formal Print Header (A4 Accounting Standard) -->
    <div class="print-only formal-print-header" style="text-align: center; margin-bottom: 20px;">
        <h1 style="font-family: 'Times New Roman', Times, serif; font-size: 16pt; font-weight: bold; margin: 0; text-transform: uppercase;"><?= htmlspecialchars($_SESSION['nama_perusahaan'] ?? 'USAHA') ?></h1>
        <p style="font-family: 'Times New Roman', Times, serif; font-size: 9pt; margin: 3px 0 0 0; color: #333;">Laporan Keuangan Resmi SAK EMKM | <?= htmlspecialchars($_SESSION['nama_perusahaan'] ?? 'USAHA') ?></p>
        <div style="border-bottom: 2px solid #000; border-top: 1px solid #000; height: 3px; margin: 8px 0 14px 0;"></div>
        <h2 style="font-family: 'Times New Roman', Times, serif; font-size: 13pt; font-weight: bold; margin: 0; text-transform: uppercase;">LAPORAN POSISI KEUANGAN (NERACA)</h2>
        <div style="font-family: 'Times New Roman', Times, serif; font-size: 10pt; font-style: italic; margin-top: 4px;"><?= $periode_str ?> (Mata Uang: IDR / Rupiah)</div>
    </div>

    <!-- MAIN NERACA TWO-COLUMN TABLE -->
    <div class="neraca-container">
        <!-- AKTIVA COLUMN -->
        <div class="neraca-col">
            <table class="neraca-table">
                <thead>
                    <tr>
                        <th colspan="2" class="neraca-header">AKTIVA</th>
                    </tr>
                    <tr class="neraca-subhead">
                        <th>Komponen Akun</th>
                        <th class="text-right">Saldo Akhir (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $total_aktiva = 0;
                        echo renderCategory("Aktiva Lancar", $aktiva_lancar, $total_aktiva, true);
                        echo renderCategory("Aktiva Tetap", $aktiva_tetap, $total_aktiva);
                        echo renderCategory("Aktiva Lainnya", $aktiva_lainnya, $total_aktiva);
                    ?>
                    <tr class="neraca-total">
                        <td>TOTAL AKTIVA</td>
                        <td class="text-right">
                            <?= $total_aktiva < 0 ? '(' . number_format(abs($total_aktiva), 0, ',', '.') . ')' : number_format($total_aktiva, 0, ',', '.') ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- PASIVA COLUMN -->
        <div class="neraca-col">
            <table class="neraca-table">
                <thead>
                    <tr>
                        <th colspan="2" class="neraca-header pasiva">PASIVA</th>
                    </tr>
                    <tr class="neraca-subhead">
                        <th>Komponen Akun</th>
                        <th class="text-right">Saldo Akhir (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $total_pasiva = 0;
                        echo renderCategory("Utang Lancar", $utang_lancar, $total_pasiva, true);
                        echo renderCategory("Utang Jangka Panjang", $utang_jp, $total_pasiva);
                        echo renderCategory("Ekuitas", $ekuitas, $total_pasiva);
                    ?>
                    <tr class="neraca-total">
                        <td>TOTAL PASIVA</td>
                        <td class="text-right">
                            <?= $total_pasiva < 0 ? '(' . number_format(abs($total_pasiva), 0, ',', '.') . ')' : number_format($total_pasiva, 0, ',', '.') ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <?php 
        $selisih = abs($total_aktiva - $total_pasiva);
        $is_balanced = ($selisih < 0.01);
        $bal_bg = $is_balanced ? 'var(--balance-ok-bg)' : 'var(--balance-err-bg)';
        $bal_text = $is_balanced ? 'var(--balance-ok-text)' : 'var(--balance-err-text)';
        $bal_border = $is_balanced ? 'var(--balance-ok-border)' : 'var(--balance-err-border)';
    ?>
    <div class="balance-status" style="text-align: center; font-weight: 800; padding: 16px 20px; border-radius: 12px; background-color: <?= $bal_bg ?>; color: <?= $bal_text ?>; font-size: 1.05rem; border: 2px solid <?= $bal_border ?>; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: var(--shadow-sm);">
        <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: <?= $bal_border ?>;"></span>
        <span><?= $is_balanced ? 'NERACA SEIMBANG (BALANCE) ✔' : 'NERACA TIDAK SEIMBANG ✖ (Selisih: Rp ' . number_format($selisih, 0, ',', '.') . ')' ?></span>
    </div>

    <!-- Formal Signature Block for Printing -->
    <div class="print-only formal-signature-block" style="margin-top: 35px; display: flex; justify-content: space-between; page-break-inside: avoid;">
        <div style="width: 40%; text-align: center; font-family: 'Times New Roman', Times, serif;">
            <p style="margin: 0; font-size: 10pt;">Dibuat & Diverifikasi Oleh,</p>
            <div style="height: 60px;"></div>
            <p style="margin: 0; font-size: 11pt; font-weight: bold; text-decoration: underline;">Akuntan Perusahaan</p>
            <p style="margin: 2px 0 0 0; font-size: 9pt; color: #444;">Bagian Akuntansi & Keuangan</p>
        </div>
        <div style="width: 40%; text-align: center; font-family: 'Times New Roman', Times, serif;">
            <p style="margin: 0; font-size: 10pt;">Jakarta, <?= date('d F Y') ?><br>Disetujui & Disahkan Oleh,</p>
            <div style="height: 60px;"></div>
            <p style="margin: 0; font-size: 11pt; font-weight: bold; text-decoration: underline;">Direktur Utama</p>
            <p style="margin: 2px 0 0 0; font-size: 9pt; color: #444;"><?= htmlspecialchars($_SESSION['nama_perusahaan'] ?? 'USAHA') ?></p>
        </div>
    </div>

</div>
</div>
</body>
</html>
