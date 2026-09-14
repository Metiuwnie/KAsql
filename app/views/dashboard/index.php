<?php
function formatRp($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}
function formatShortRp($val) {
    $val = abs($val);
    if ($val >= 1000000000) {
        return number_format($val / 1000000000, 1, ',', '.') . ' M';
    } elseif ($val >= 1000000) {
        return number_format($val / 1000000, 1, ',', '.') . ' Jt';
    } elseif ($val >= 1000) {
        return number_format($val / 1000, 0, ',', '.') . ' Rb';
    }
    return number_format($val, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Adjust path for CSS/JS to point to base url -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css?v=<?= time() ?>">
    <script src="<?= BASE_URL ?>/public/js/script.js?v=2.0" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Modern Premium CSS untuk Dashboard */
        .dashboard-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 2rem 2rem 2rem;
            animation: fadeIn 0.5s ease-out;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .dashboard-header h2 {
            margin: 0;
            font-size: 1.8rem;
            color: var(--text-heading);
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .custom-dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-selected {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--bg-surface);
            padding: 8px 18px;
            border-radius: 9999px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
            cursor: pointer;
            color: var(--text-main);
            font-weight: 600;
            font-size: 0.9rem;
            user-select: none;
        }

        .dropdown-selected:hover {
            border-color: var(--primary-color);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        [data-theme="dark"] .dropdown-selected {
            background: #111114;
            border-color: rgba(255, 255, 255, 0.12);
        }

        [data-theme="dark"] .dropdown-selected:hover {
            border-color: rgba(255, 255, 255, 0.25);
            box-shadow: 0 4px 14px rgba(0,0,0,0.5);
        }

        .dropdown-selected .calendar-icon {
            color: var(--primary-color);
            transition: all 0.2s ease;
        }
        
        .dropdown-selected .chevron-icon {
            color: var(--text-muted);
            margin-left: 2px;
            transition: transform 0.25s ease;
        }

        .custom-dropdown.open .dropdown-selected .chevron-icon {
            transform: rotate(180deg);
        }

        .custom-dropdown.open .dropdown-selected {
            border-color: var(--primary-color);
        }

        .dropdown-options {
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            background: var(--bg-surface);
            min-width: 205px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            border: 1px solid var(--border-color);
            padding: 5px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 100;
        }
        
        [data-theme="dark"] .dropdown-options {
            box-shadow: 0 12px 30px rgba(0,0,0,0.6);
            background: #18181b;
            border-color: rgba(255, 255, 255, 0.1);
        }

        .custom-dropdown.open .dropdown-options {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-option {
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            color: var(--text-main);
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.15s ease;
            font-size: 0.88rem;
            margin-bottom: 2px;
        }

        .dropdown-option:last-child {
            margin-bottom: 0;
        }

        .dropdown-option:hover {
            background: #f4f4f2;
            color: #18181b;
        }

        .dropdown-option.active {
            background: #ebebe8;
            color: #18181b;
            font-weight: 700;
        }

        [data-theme="dark"] .dropdown-option:hover {
            background: rgba(255, 255, 255, 0.06);
            color: #ffffff;
        }

        [data-theme="dark"] .dropdown-option.active {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            font-weight: 700;
        }

        /* Summary Cards */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--bg-surface);
            border-radius: 16px;
            padding: 1.25rem 1.4rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-glass);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.25s ease;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        [data-theme="dark"] .stat-card {
            background: var(--bg-surface);
            border-color: rgba(255, 255, 255, 0.07);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            border-color: rgba(0, 0, 0, 0.15);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
        }
        [data-theme="dark"] .stat-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
            border-color: rgba(255, 94, 26, 0.5);
        }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.85rem;
        }

        .stat-card-body {
            display: flex;
            flex-direction: column;
        }

        .stat-icon-wrapper {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.2s ease;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: var(--text-main);
        }
        [data-theme="light"] .stat-icon-wrapper {
            background: #f4f4f6;
            border-color: rgba(0, 0, 0, 0.08);
            color: #18181b;
        }

        .stat-title {
            color: var(--text-muted);
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 0.35rem;
        }

        .stat-value {
            color: var(--text-main);
            font-size: 1.65rem;
            font-weight: 800;
            font-feature-settings: "tnum" 1;
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        [data-theme="dark"] .stat-value {
            color: #ffffff !important;
        }

        /* KPI Equalizer Sparkline */
        .kpi-equalizer {
            display: flex;
            align-items: flex-end;
            gap: 3px;
            height: 34px;
            padding-left: 0.5rem;
        }
        .eq-bar {
            width: 3px;
            border-radius: 2px;
            background: rgba(255, 255, 255, 0.12);
            transition: height 0.4s ease;
        }
        [data-theme="light"] .eq-bar {
            background: rgba(0, 0, 0, 0.1);
        }
        .eq-bar.active {
            background: var(--primary-color);
        }
        .eq-bar.negative {
            background: #ef4444 !important;
            box-shadow: 0 0 6px rgba(239, 68, 68, 0.4);
        }
        .eq-bar.zero {
            background: rgba(125, 125, 125, 0.2) !important;
            height: 4px !important;
        }

        /* Stat Card YoY Comparison Footer - matching reference card */
        .stat-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1.15rem;
            margin-left: -1.4rem;
            margin-right: -1.4rem;
            margin-bottom: -1.25rem;
            padding: 0.6rem 1.4rem;
            border-top: 1px solid rgba(125, 125, 125, 0.1);
            background: rgba(125, 125, 125, 0.03);
            border-bottom-left-radius: 15px;
            border-bottom-right-radius: 15px;
            transition: all 0.2s ease;
        }

        [data-theme="light"] .stat-card-footer {
            border-top-color: rgba(0, 0, 0, 0.06);
            background: #fafaf9;
        }

        [data-theme="dark"] .stat-card-footer {
            border-top-color: rgba(255, 255, 255, 0.06);
            background: rgba(255, 255, 255, 0.02);
        }

        .stat-card:hover .stat-card-footer {
            background: rgba(125, 125, 125, 0.06);
        }
        [data-theme="light"] .stat-card:hover .stat-card-footer {
            background: #f4f4f5;
        }
        [data-theme="dark"] .stat-card:hover .stat-card-footer {
            background: rgba(255, 255, 255, 0.04);
        }

        .stat-yoy-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .stat-yoy-badge.success {
            background: rgba(16, 185, 129, 0.12);
            color: #10b981;
        }
        .stat-yoy-badge.danger {
            background: rgba(239, 68, 68, 0.12);
            color: #ef4444;
        }
        .stat-yoy-badge.neutral {
            background: rgba(161, 161, 170, 0.12);
            color: #a1a1aa;
        }

        [data-theme="light"] .stat-yoy-badge.success {
            background: #ecfdf5;
            color: #059669;
        }
        [data-theme="light"] .stat-yoy-badge.danger {
            background: #fef2f2;
            color: #dc2626;
        }
        [data-theme="light"] .stat-yoy-badge.neutral {
            background: #f4f4f5;
            color: #71717a;
        }

        .stat-yoy-text {
            font-size: 0.74rem;
            font-weight: 600;
            letter-spacing: 0.01em;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .stat-yoy-val.success {
            color: #10b981;
        }
        [data-theme="light"] .stat-yoy-val.success {
            color: #059669;
        }

        .stat-yoy-val.danger {
            color: #ef4444;
        }
        [data-theme="light"] .stat-yoy-val.danger {
            color: #dc2626;
        }

        .stat-yoy-val.neutral {
            color: var(--text-muted);
        }

        .stat-yoy-sub {
            font-weight: 600;
            color: #000000;
        }

        [data-theme="light"] .stat-yoy-sub {
            color: #000000 !important;
        }

        [data-theme="dark"] .stat-yoy-sub {
            color: var(--text-muted) !important;
        }

        /* Main Dashboard Charts (80:20 Split: Tren Transaksi & Komposisi Aset) */
        .dashboard-main-charts {
            display: grid;
            grid-template-columns: 4fr 1fr;
            gap: 1.25rem;
            margin-bottom: 2rem;
            align-items: stretch;
        }

        @media (max-width: 1280px) {
            .dashboard-main-charts {
                grid-template-columns: 1fr;
                gap: 1.25rem;
            }
        }

        /* Responsive Layout for Komposisi Aset on Tablets & Medium Screens */
        @media (min-width: 640px) and (max-width: 1280px) {
            .asset-chart-body {
                display: grid !important;
                grid-template-columns: 240px 1fr !important;
                align-items: center !important;
                gap: 1.5rem !important;
            }
            .asset-card-footer {
                grid-column: span 2;
            }
        }

        /* Mobile Adjustments for Tren Transaksi & Komposisi Aset */
        @media (max-width: 640px) {
            .dashboard-main-charts {
                gap: 1rem;
                margin-bottom: 1.5rem;
            }

            .chart-card-trend,
            .chart-card-asset {
                padding: 1rem 0.85rem !important;
                border-radius: 12px;
            }

            .trend-header-top {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 0.75rem !important;
            }

            .trend-header-controls {
                width: 100%;
                justify-content: space-between;
            }

            .trend-metric-row {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 0.75rem !important;
            }

            .trend-metric-value {
                font-size: 1.45rem !important;
            }

            .chart-card-trend .chart-container {
                height: 275px !important;
            }

            .doughnut-wrapper {
                height: 180px !important;
            }
        }

        .chart-card-trend {
            margin-bottom: 0 !important;
        }

        .chart-card-asset {
            display: flex;
            flex-direction: column;
            padding: 1.25rem 1.15rem !important;
        }

        .asset-label {
            color: var(--text-main);
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
            min-width: 0;
        }

        @media (min-width: 1281px) {
            .asset-label {
                max-width: 105px;
            }
        }

        .asset-list-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
            padding: 6px 10px;
            border-radius: 8px;
            background: rgba(125, 125, 125, 0.05);
            border: 1px solid var(--border-glass, rgba(125, 125, 125, 0.1));
            transition: all 0.2s ease;
        }

        .asset-list-item:hover {
            background: var(--bg-surface-hover);
            border-color: rgba(125, 125, 125, 0.25);
            transform: translateY(-1px);
        }

        /* Dark mode ONLY - Orange hero + distinct vivid colors for top 5 composition */
        [data-theme="dark"] .asset-dot[data-idx="0"],
        [data-theme="dark"] .asset-bar-fill[data-idx="0"] { background: #ff5e1a !important; }
        [data-theme="dark"] .asset-dot[data-idx="1"],
        [data-theme="dark"] .asset-bar-fill[data-idx="1"] { background: #38bdf8 !important; }
        [data-theme="dark"] .asset-dot[data-idx="2"],
        [data-theme="dark"] .asset-bar-fill[data-idx="2"] { background: #34d399 !important; }
        [data-theme="dark"] .asset-dot[data-idx="3"],
        [data-theme="dark"] .asset-bar-fill[data-idx="3"] { background: #a78bfa !important; }
        [data-theme="dark"] .asset-dot[data-idx="4"],
        [data-theme="dark"] .asset-bar-fill[data-idx="4"] { background: #fbbf24 !important; }

        [data-theme="dark"] .asset-pct[data-idx="0"] { color: #ff5e1a !important; }
        [data-theme="dark"] .asset-pct[data-idx="1"] { color: #38bdf8 !important; }
        [data-theme="dark"] .asset-pct[data-idx="2"] { color: #34d399 !important; }
        [data-theme="dark"] .asset-pct[data-idx="3"] { color: #a78bfa !important; }
        [data-theme="dark"] .asset-pct[data-idx="4"] { color: #fbbf24 !important; }

        .chart-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        [data-theme="dark"] .chart-card {
            background: var(--bg-surface);
            border-color: rgba(255, 255, 255, 0.07);
        }

        .chart-card:hover {
            box-shadow: var(--shadow-md);
            border-color: rgba(100, 116, 139, 0.3);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .chart-title {
            color: var(--text-main);
            font-weight: 700;
            font-size: 1.15rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-container {
            position: relative;
            height: 320px;
            width: 100%;
        }

        /* Matrix Chart Styles */
        .matrix-tooltip {
            position: absolute;
            background: var(--bg-surface);
            border: 1px solid var(--border-glass);
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 0.82rem;
            pointer-events: none;
            opacity: 0;
            transform: translate(-50%, -100%) scale(0.95);
            transition: opacity 0.15s ease, transform 0.15s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            z-index: 100;
            white-space: nowrap;
        }
        [data-theme="dark"] .matrix-tooltip {
            background: rgba(17, 17, 20, 0.95);
            border-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.7);
        }
        .matrix-tooltip.visible {
            opacity: 1;
            transform: translate(-50%, -100%) scale(1);
        }
        .matrix-tooltip .tooltip-header {
            font-weight: 700;
            margin-bottom: 6px;
            color: var(--text-main);
            font-size: 0.86rem;
        }
        .matrix-tooltip .tooltip-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 4px;
            color: var(--text-muted);
        }
        .matrix-tooltip .tooltip-row span.val {
            font-weight: 700;
            color: var(--text-main);
            margin-left: auto;
        }
        .matrix-tooltip .tooltip-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }
        .matrix-pill-btn,
        .matrix-tab-btn {
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-family: inherit;
            font-size: 0.82rem;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: 9999px;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }
        .matrix-pill-btn:hover,
        .matrix-tab-btn:hover {
            color: var(--text-main);
        }
        .matrix-pill-btn.active,
        .matrix-tab-btn.active {
            background: #ffffff;
            color: #18181b;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        [data-theme="dark"] .matrix-pill-btn.active,
        [data-theme="dark"] .matrix-tab-btn.active {
            background: #27272a;
            color: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }

    </style>
</head>
<body>
<?php include 'app/views/layout/sidebar.php'; ?>

<div class="main-content">
    <?php include 'app/views/layout/header.php'; ?>
    
    <div class="dashboard-container">
        <!-- Header & Filter -->
        <div class="dashboard-header">
            <div>
                <h2>Selamat Datang, <?= htmlspecialchars($_SESSION['user_nama'] ?? 'User') ?></h2>
                
            </div>
            
            <form method="GET" action="<?= BASE_URL ?>/dashboard" id="yearFilterForm">
                <div class="custom-dropdown" id="yearDropdown">
                    <div class="dropdown-selected">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="calendar-icon"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <span>Tahun Finansial <?= $current_year ?></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="chevron-icon"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </div>
                    <div class="dropdown-options">
                        <?php foreach($years as $y): ?>
                            <div class="dropdown-option <?= ($y == $current_year) ? 'active' : '' ?>" onclick="selectYear('<?= $y ?>')">
                                <span>Tahun Finansial <?= $y ?></span>
                                <?php if($y == $current_year): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-left: 8px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if(empty($years)): ?>
                            <div class="dropdown-option active" onclick="selectYear('<?= $current_year ?>')">
                                <?= $current_year ?> (Data Kosong)
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <input type="hidden" name="tahun" id="tahunInput" value="<?= $current_year ?>">
                <input type="hidden" name="bulan" value="<?= $current_month ?>">
            </form>
        </div>

        <!-- Summary Stat Cards -->
        <div class="dashboard-grid">
            <div class="stat-card asset">
                <div class="stat-card-header">
                    <div class="stat-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                    </div>
                    <div class="kpi-equalizer" id="eq_asset"></div>
                </div>
                <div class="stat-card-body">
                    <div class="stat-title">Total Kekayaan (Aset)</div>
                    <div class="stat-value counter" data-target="<?= $total_aset ?>">Rp 0</div>
                </div>
                <?php if (isset($yoy['aset'])): ?>
                <div class="stat-card-footer" title="Tahun <?= $prev_year ?>: Rp <?= number_format($yoy['aset']['prev_val'], 0, ',', '.') ?> (Selisih: <?= ($yoy['aset']['diff'] >= 0 ? '+' : '') ?>Rp <?= number_format($yoy['aset']['diff'], 0, ',', '.') ?>)">
                    <div class="stat-yoy-badge <?= $yoy['aset']['status'] ?>">
                        <?php if ($yoy['aset']['direction'] === 'up'): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
                        <?php elseif ($yoy['aset']['direction'] === 'down'): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                        <?php else: ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                        <?php endif; ?>
                    </div>
                    <div class="stat-yoy-text">
                        <span class="stat-yoy-val <?= $yoy['aset']['status'] ?>"><?= $yoy['aset']['pct_text'] ?? $yoy['aset']['text'] ?></span>
                        <span class="stat-yoy-sub"><?= $yoy['aset']['sub_text'] ?? 'thn lalu' ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="stat-card liability">
                <div class="stat-card-header">
                    <div class="stat-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    </div>
                    <div class="kpi-equalizer" id="eq_liability"></div>
                </div>
                <div class="stat-card-body">
                    <div class="stat-title">Total Liabilitas (Utang)</div>
                    <div class="stat-value counter" data-target="<?= $total_kewajiban ?>">Rp 0</div>
                </div>
                <?php if (isset($yoy['kewajiban'])): ?>
                <div class="stat-card-footer" title="Tahun <?= $prev_year ?>: Rp <?= number_format($yoy['kewajiban']['prev_val'], 0, ',', '.') ?> (Selisih: <?= ($yoy['kewajiban']['diff'] >= 0 ? '+' : '') ?>Rp <?= number_format($yoy['kewajiban']['diff'], 0, ',', '.') ?>)">
                    <div class="stat-yoy-badge <?= $yoy['kewajiban']['status'] ?>">
                        <?php if ($yoy['kewajiban']['direction'] === 'up'): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
                        <?php elseif ($yoy['kewajiban']['direction'] === 'down'): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                        <?php else: ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                        <?php endif; ?>
                    </div>
                    <div class="stat-yoy-text">
                        <span class="stat-yoy-val <?= $yoy['kewajiban']['status'] ?>"><?= $yoy['kewajiban']['pct_text'] ?? $yoy['kewajiban']['text'] ?></span>
                        <span class="stat-yoy-sub"><?= $yoy['kewajiban']['sub_text'] ?? 'thn lalu' ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="stat-card revenue">
                <div class="stat-card-header">
                    <div class="stat-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                    </div>
                    <div class="kpi-equalizer" id="eq_revenue"></div>
                </div>
                <div class="stat-card-body">
                    <div class="stat-title">Total Pendapatan (<?= $current_year ?>)</div>
                    <div class="stat-value counter" data-target="<?= $total_pendapatan ?>">Rp 0</div>
                </div>
                <?php if (isset($yoy['pendapatan'])): ?>
                <div class="stat-card-footer" title="Tahun <?= $prev_year ?>: Rp <?= number_format($yoy['pendapatan']['prev_val'], 0, ',', '.') ?> (Selisih: <?= ($yoy['pendapatan']['diff'] >= 0 ? '+' : '') ?>Rp <?= number_format($yoy['pendapatan']['diff'], 0, ',', '.') ?>)">
                    <div class="stat-yoy-badge <?= $yoy['pendapatan']['status'] ?>">
                        <?php if ($yoy['pendapatan']['direction'] === 'up'): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
                        <?php elseif ($yoy['pendapatan']['direction'] === 'down'): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                        <?php else: ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                        <?php endif; ?>
                    </div>
                    <div class="stat-yoy-text">
                        <span class="stat-yoy-val <?= $yoy['pendapatan']['status'] ?>"><?= $yoy['pendapatan']['pct_text'] ?? $yoy['pendapatan']['text'] ?></span>
                        <span class="stat-yoy-sub"><?= $yoy['pendapatan']['sub_text'] ?? 'thn lalu' ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="stat-card income">
                <div class="stat-card-header">
                    <div class="stat-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    </div>
                    <div class="kpi-equalizer" id="eq_income"></div>
                </div>
                <div class="stat-card-body">
                    <div class="stat-title">Laba Bersih (<?= $current_year ?>)</div>
                    <div class="stat-value counter" data-target="<?= $laba_rugi_bersih ?>">Rp 0</div>
                </div>
                <?php if (isset($yoy['laba'])): ?>
                <div class="stat-card-footer" title="Tahun <?= $prev_year ?>: Rp <?= number_format($yoy['laba']['prev_val'], 0, ',', '.') ?> (Selisih: <?= ($yoy['laba']['diff'] >= 0 ? '+' : '') ?>Rp <?= number_format($yoy['laba']['diff'], 0, ',', '.') ?>)">
                    <div class="stat-yoy-badge <?= $yoy['laba']['status'] ?>">
                        <?php if ($yoy['laba']['direction'] === 'up'): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
                        <?php elseif ($yoy['laba']['direction'] === 'down'): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                        <?php else: ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                        <?php endif; ?>
                    </div>
                    <div class="stat-yoy-text">
                        <span class="stat-yoy-val <?= $yoy['laba']['status'] ?>"><?= $yoy['laba']['pct_text'] ?? $yoy['laba']['text'] ?></span>
                        <span class="stat-yoy-sub"><?= $yoy['laba']['sub_text'] ?? 'thn lalu' ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="stat-card equity">
                <div class="stat-card-header">
                    <div class="stat-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18v18H3z"></path><path d="M3 9h18"></path><path d="M9 21V9"></path></svg>
                    </div>
                    <div class="kpi-equalizer" id="eq_equity"></div>
                </div>
                <div class="stat-card-body">
                    <div class="stat-title">Total Ekuitas (Modal)</div>
                    <div class="stat-value counter" data-target="<?= $total_ekuitas ?>">Rp 0</div>
                </div>
                <?php if (isset($yoy['ekuitas'])): ?>
                <div class="stat-card-footer" title="Tahun <?= $prev_year ?>: Rp <?= number_format($yoy['ekuitas']['prev_val'], 0, ',', '.') ?> (Selisih: <?= ($yoy['ekuitas']['diff'] >= 0 ? '+' : '') ?>Rp <?= number_format($yoy['ekuitas']['diff'], 0, ',', '.') ?>)">
                    <div class="stat-yoy-badge <?= $yoy['ekuitas']['status'] ?>">
                        <?php if ($yoy['ekuitas']['direction'] === 'up'): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
                        <?php elseif ($yoy['ekuitas']['direction'] === 'down'): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                        <?php else: ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                        <?php endif; ?>
                    </div>
                    <div class="stat-yoy-text">
                        <span class="stat-yoy-val <?= $yoy['ekuitas']['status'] ?>"><?= $yoy['ekuitas']['pct_text'] ?? $yoy['ekuitas']['text'] ?></span>
                        <span class="stat-yoy-sub"><?= $yoy['ekuitas']['sub_text'] ?? 'thn lalu' ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>



        <!-- Main Dashboard Charts: Tren Transaksi & Komposisi Aset Side-by-Side -->
        <div class="dashboard-main-charts">
            <!-- Kiri: Grafik Batang Tren Transaksi -->
            <div class="chart-card chart-card-trend">
                <!-- Top Line: Section Tagline & Controls (Mode Switcher + Month Selector) -->
                <div class="trend-header-top" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 0.75rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-muted); display: inline-flex; align-items: center; gap: 6px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                            TREN TRANSAKSI
                        </span>
                        <span id="trendSubtitle" style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-left: 2px;">
                            (<?= htmlspecialchars($daily_trend['month_name']) ?> <?= $current_year ?>)
                        </span>
                    </div>

                    <!-- Right Controls: Pills & Month Select -->
                    <div class="trend-header-controls" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <div style="display: inline-flex; background: var(--bg-main); border: 1px solid var(--border-color); border-radius: 9999px; padding: 3px; gap: 2px;">
                            <button type="button" class="matrix-pill-btn active" id="btnMatrixDaily" onclick="switchKasqlMatrixMode('daily')">Harian</button>
                            <button type="button" class="matrix-pill-btn" id="btnMatrixMonthly" onclick="switchKasqlMatrixMode('monthly')">Bulanan</button>
                        </div>

                        <!-- Month Selector Form (Hanya aktif untuk mode Harian) -->
                        <div id="monthFilterContainer" style="display: inline-flex; align-items: center;">
                            <form method="GET" action="<?= BASE_URL ?>/dashboard" id="monthFilterForm" style="display: flex; align-items: center; margin: 0;">
                                <input type="hidden" name="tahun" value="<?= $current_year ?>">
                                <select name="bulan" id="monthFilterSelect" class="form-control" style="width: auto; padding: 4px 12px; font-size: 0.82rem; font-weight: 600; border-radius: 9999px; height: 32px;" onchange="this.form.submit()">
                                    <?php 
                                    $nama_bulan_list = [
                                        '01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April',
                                        '05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus',
                                        '09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'
                                    ];
                                    foreach($nama_bulan_list as $m_num => $m_name): ?>
                                        <option value="<?= $m_num ?>" <?= ($current_month == $m_num) ? 'selected' : '' ?>>
                                             <?= $m_name ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Second Line: Big Metric Highlight + Inline Legend -->
                <div class="trend-metric-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
                    <!-- Left: Big Metric Highlight -->
                    <div style="display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap;">
                        <span id="trendMetricLabel" style="font-size: 0.95rem; font-weight: 600; color: var(--text-muted);">Total Volume :</span>
                        <span id="trendMetricValue" class="trend-metric-value" style="font-size: 1.85rem; font-weight: 800; color: var(--text-heading); letter-spacing: -0.03em; font-feature-settings: 'tnum' 1;">
                            Rp <?= number_format($daily_trend['total_volume_bulan'], 0, ',', '.') ?>
                        </span>
                        <span id="trendMetricBadge" class="badge" style="background: var(--bg-surface-hover); color: var(--text-main); border: 1px solid var(--border-color); font-weight: 700; padding: 4px 10px; border-radius: 8px; font-size: 0.8rem;">
                            <?= number_format($daily_trend['total_trx_bulan']) ?> Transaksi
                        </span>
                    </div>

                    <!-- Right / Center: Inline Minimalist Legend -->
                    <div style="display: flex; align-items: center; gap: 1.25rem;">
                        <div style="display: flex; align-items: center; gap: 7px; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);">
                            <span style="width: 8px; height: 8px; border-radius: 2px; background: #10b981; display: inline-block;"></span>
                            <span>Pendapatan</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 7px; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);">
                            <span style="width: 8px; height: 8px; border-radius: 2px; background: #ef4444; display: inline-block;"></span>
                            <span>Beban</span>
                        </div>
                    </div>
                </div>

                <!-- Continuous Matrix Chart Stage -->
                <div class="chart-container" style="height: 330px; position: relative;">
                    <canvas id="dailyMatrixCanvas"></canvas>
                    
                    <!-- Floating Glassmorphic Tooltip -->
                    <div class="matrix-tooltip" id="kasqlMatrixTooltip">
                        <div class="tooltip-header" id="kmtTitle">Tgl 1</div>
                        <div class="tooltip-row">
                            <span class="tooltip-dot" style="background: #10b981;"></span>
                            <span>Pendapatan:</span>
                            <span class="val" id="kmtIncome">Rp 0</span>
                        </div>
                        <div class="tooltip-row">
                            <span class="tooltip-dot" style="background: #ef4444;"></span>
                            <span>Beban:</span>
                            <span class="val" id="kmtExpense">Rp 0</span>
                        </div>
                        <div class="tooltip-row" style="border-top: 1px dashed var(--border-color); padding-top: 5px; margin-top: 5px;">
                            <span>Laba Bersih:</span>
                            <span class="val" id="kmtNet">Rp 0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kanan: Komposisi Aset Top 5 (20% Lebar Kolom) -->
            <div class="chart-card chart-card-asset">
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.5rem;">
                    <span style="font-size: 0.72rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-muted);">
                        KOMPOSISI ASET
                    </span>
                    <span style="font-size: 0.72rem; color: var(--text-muted); font-weight: 600;">Top 5</span>
                </div>

                <div class="asset-chart-body" style="display: flex; flex-direction: column; flex: 1; gap: 0.85rem;">
                    <!-- Donut Container with Center Stat -->
                    <div class="doughnut-wrapper" style="position: relative; height: 200px; display: flex; align-items: center; justify-content: center;">
                        <canvas id="doughnutChart"></canvas>
                        <div class="doughnut-center-stat" style="position: absolute; text-align: center; pointer-events: none;">
                            <div style="font-size: 0.62rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 2px;">TOTAL ASET</div>
                            <div style="font-size: 1.05rem; font-weight: 800; color: var(--text-heading); letter-spacing: -0.02em; white-space: nowrap;">Rp <?= formatShortRp($total_aset) ?></div>
                        </div>
                    </div>

                    <!-- Clean Minimalist Breakdown List with Visual Bar (100% Theme-Unified) -->
                    <div class="asset-breakdown-list" style="display: flex; flex-direction: column; gap: 8px;">
                        <?php 
                        $palette_light = ['#ff5e1a', '#0284c7', '#059669', '#7c3aed', '#d97706'];
                        if (!empty($aset_labels)):
                            $sum_aset = array_sum($aset_data);
                            foreach($aset_labels as $idx => $label): 
                                $val = floatval($aset_data[$idx]);
                                $pct = $sum_aset > 0 ? round(($val / $sum_aset) * 100) : 0;
                                $color = $palette_light[$idx % count($palette_light)];
                        ?>
                            <div class="asset-list-item">
                                <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.75rem;">
                                    <div style="display: flex; align-items: center; gap: 7px; min-width: 0; flex: 1;">
                                        <span class="asset-dot" data-idx="<?= $idx ?>" style="width: 7px; height: 7px; border-radius: 50%; background: <?= $color ?>; flex-shrink: 0;"></span>
                                        <span class="asset-label" title="<?= htmlspecialchars($label) ?> (Rp <?= number_format($val, 0, ',', '.') ?>)"><?= htmlspecialchars($label) ?></span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                                        <span style="color: var(--text-muted); font-size: 0.72rem; font-weight: 500;">Rp <?= formatShortRp($val) ?></span>
                                        <span class="asset-pct" data-idx="<?= $idx ?>" style="font-weight: 700; font-size: 0.75rem; min-width: 26px; text-align: right;"><?= $pct ?>%</span>
                                    </div>
                                </div>
                                <div style="width: 100%; height: 3.5px; background: rgba(125, 125, 125, 0.15); border-radius: 3px; overflow: hidden;">
                                    <div class="asset-bar-fill" data-idx="<?= $idx ?>" style="width: <?= max($pct, 2) ?>%; height: 100%; background: <?= $color ?>; border-radius: 3px;"></div>
                                </div>
                            </div>
                        <?php 
                            endforeach;
                        else: 
                        ?>
                            <div style="text-align: center; color: var(--text-muted); font-size: 0.8rem; padding: 0.5rem 0;">Belum ada data aset</div>
                        <?php endif; ?>
                    </div>

                    <!-- Micro-Summary Footer Anchor -->
                    <div class="asset-card-footer" style="margin-top: auto; padding-top: 12px; border-top: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; font-size: 0.72rem; color: var(--text-muted);">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #22c55e;"></span>
                            <span style="font-weight: 500;">Total Akumulasi Aset</span>
                        </div>
                        <span style="font-weight: 700; color: var(--text-heading); font-family: var(--font-mono, monospace); font-size: 0.78rem;">Rp <?= number_format($total_aset, 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. ANIMATED COUNTER
    const formatUang = (num) => {
        const isNegative = num < 0;
        const absNum = Math.abs(num);
        let formatted = new Intl.NumberFormat('id-ID').format(Math.floor(absNum));
        return (isNegative ? '- Rp ' : 'Rp ') + formatted;
    };

    const counters = document.querySelectorAll('.counter');
    counters.forEach(counter => {
        const target = +counter.getAttribute('data-target');
        const duration = 1200; // ms
        const steps = 50;
        const stepTime = Math.abs(Math.floor(duration / steps));
        let current = 0;
        
        const easeOutQuad = t => t * (2 - t);
        
        let stepCount = 0;
        const timer = setInterval(() => {
            stepCount++;
            let progress = stepCount / steps;
            current = target * easeOutQuad(progress);
            
            counter.innerText = formatUang(current);
            
            if (stepCount >= steps) {
                counter.innerText = formatUang(target);
                clearInterval(timer);
            }
        }, stepTime);
    });

    // 2. CUSTOM DROPDOWN SCRIPT
    const dropdown = document.getElementById('yearDropdown');
    if (dropdown) {
        const selected = dropdown.querySelector('.dropdown-selected');
        selected.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('open');
        });

        document.addEventListener('click', () => {
            dropdown.classList.remove('open');
        });
    }

    window.selectYear = function(year) {
        document.getElementById('tahunInput').value = year;
        document.getElementById('yearFilterForm').submit();
    };

    // 3. CHART THEME CONFIG
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#cbd5e1' : '#475569';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.05)';
    
    Chart.defaults.color = textColor;
    Chart.defaults.font.family = "'Inter', sans-serif";

    // ==========================================
    // 4. MATRIX BRICK BAR CHART: TREN TRANSAKSI (HARIAN & BULANAN)
    // ==========================================
    const dailyDays = <?= json_encode($daily_trend['days']) ?>;
    const dailyIncome = <?= json_encode($daily_trend['pendapatan']) ?>;
    const dailyExpense = <?= json_encode($daily_trend['beban']) ?>;
    const currentMonthName = <?= json_encode($daily_trend['month_name']) ?>;
    const currentYear = <?= json_encode($current_year) ?>;

    const annualLabels = <?= json_encode($chart_labels) ?>;
    const annualIncome = <?= json_encode($chart_pendapatan) ?>;
    const annualExpense = <?= json_encode($chart_beban) ?>;

    // KPI Mini-Equalizer Sparklines yang merespons status nominal (Positif, Negatif, Nol)
    function populateEqualizer(id, data, customNominal) {
        const el = document.getElementById(id);
        if (!el) return;
        el.innerHTML = '';

        const card = el.closest('.stat-card');
        const cardValEl = card ? card.querySelector('.stat-value') : null;
        let nominal = customNominal;
        if (nominal === undefined && cardValEl) {
            nominal = parseFloat(cardValEl.getAttribute('data-target')) || 0;
        }

        const isZero = Math.abs(nominal) < 0.01;
        const isNegative = nominal < -0.01;

        let bars = [...data];
        if (isZero) {
            bars = [1, 1, 1, 1, 1, 1, 1]; // Rata datar jika Rp 0
        } else if (isNegative) {
            // Pola menurun untuk merefleksikan nilai negatif / defisit
            bars = [85, 70, 58, 48, 38, 25, 14];
        }

        const max = Math.max(...bars, 1);
        bars.forEach((val, idx) => {
            const bar = document.createElement('div');
            let stateClass = '';
            if (isZero) {
                stateClass = ' zero';
            } else if (idx >= bars.length - 2) {
                stateClass = isNegative ? ' negative' : ' active';
            }
            bar.className = 'eq-bar' + stateClass;
            bar.style.height = `${Math.max(Math.round((val / max) * 32), 4)}px`;
            el.appendChild(bar);
        });
    }

    const currentMonthNum = <?= intval($current_month) ?>;
    const getTrendSlice = (dataArray) => {
        if (!dataArray || !dataArray.length) return [10, 20, 30, 40, 50, 60, 70];
        const numArr = dataArray.map(v => Math.max(parseFloat(v) || 0, 0));
        const upToCurrent = numArr.slice(0, currentMonthNum);
        return upToCurrent.length >= 7 ? upToCurrent.slice(-7) : upToCurrent;
    };

    const revTrend = (annualIncome && annualIncome.length) ? getTrendSlice(annualIncome) : [20, 35, 55, 45, 70, 75, 90];
    const expTrend = (annualExpense && annualExpense.length) ? getTrendSlice(annualExpense) : [15, 25, 40, 30, 50, 45, 60];
    const netTrend = revTrend.map((r, i) => Math.max(r - (expTrend[i] || 0), 10));

    populateEqualizer('eq_asset', [40, 48, 55, 62, 70, 82, 95]);
    populateEqualizer('eq_liability', expTrend);
    populateEqualizer('eq_revenue', revTrend);
    populateEqualizer('eq_income', netTrend);
    populateEqualizer('eq_equity', [35, 42, 50, 60, 68, 76, 88]);

    class KasqlMatrixBarChart {
        constructor(canvasId, tooltipId) {
            this.canvas = document.getElementById(canvasId);
            if (!this.canvas) return;
            this.ctx = this.canvas.getContext('2d');
            this.tooltip = document.getElementById(tooltipId);
            this.mode = 'daily'; // 'daily' or 'monthly'
            this.hoveredIdx = null;
            this.animProgress = 1;
            this.gap = 2;

            this.bindEvents();
            this.resize();
            window.addEventListener('resize', () => this.resize());
        }

        getPadding() {
            const isSmall = (this.width < 500);
            return {
                top: 15,
                right: isSmall ? 12 : 25,
                bottom: 30,
                left: isSmall ? 48 : 65
            };
        }

        getData() {
            if (this.mode === 'monthly') {
                return annualLabels.map((lbl, idx) => ({
                    label: lbl,
                    fullLabel: lbl + ' ' + currentYear,
                    income: parseFloat(annualIncome[idx] || 0),
                    expense: parseFloat(annualExpense[idx] || 0),
                    total: parseFloat(annualIncome[idx] || 0) + parseFloat(annualExpense[idx] || 0)
                }));
            } else {
                return dailyDays.map((d, idx) => ({
                    label: d.toString(),
                    fullLabel: 'Tgl ' + d + ' ' + currentMonthName + ' ' + currentYear,
                    income: parseFloat(dailyIncome[idx] || 0),
                    expense: parseFloat(dailyExpense[idx] || 0),
                    total: parseFloat(dailyIncome[idx] || 0) + parseFloat(dailyExpense[idx] || 0)
                }));
            }
        }

        setMode(mode) {
            this.mode = mode;
            this.hoveredIdx = null;
            this.animate();
        }

        resize() {
            if (!this.canvas) return;
            const rect = this.canvas.parentElement.getBoundingClientRect();
            const dpr = window.devicePixelRatio || 1;
            this.canvas.width = rect.width * dpr;
            this.canvas.height = rect.height * dpr;
            this.ctx.resetTransform();
            this.ctx.scale(dpr, dpr);
            this.width = rect.width;
            this.height = rect.height;
            this.draw();
        }

        animate() {
            let startTime = null;
            const duration = 450;
            const step = (ts) => {
                if (!startTime) startTime = ts;
                const p = Math.min((ts - startTime) / duration, 1);
                this.animProgress = 1 - Math.pow(1 - p, 3);
                this.draw();
                if (p < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        }

        getGridMetrics(dataLength) {
            const pad = this.getPadding();
            const plotW = this.width - pad.left - pad.right;
            const plotH = this.height - pad.top - pad.bottom;
            const isSmall = (this.width < 500);
            const gap = isSmall ? 1.5 : 2;
            
            let subCols;
            if (this.mode === 'monthly') {
                subCols = plotW > 800 ? 6 : (plotW > 500 ? 5 : (isSmall ? 2 : 3));
            } else {
                subCols = plotW > 800 ? 3 : (plotW > 500 ? 2 : 1);
            }

            const totalGridCols = dataLength * subCols;
            const blockSize = Math.max((plotW - (totalGridCols - 1) * gap) / totalGridCols, 3.5);
            const stepX = blockSize + gap;
            const stepY = blockSize + gap; // 1:1 Small Square!
            const totalRows = Math.max(Math.floor(plotH / stepY), 4);
            
            return { pad, plotW, plotH, subCols, totalGridCols, blockSize, stepX, stepY, totalRows, gap };
        }

        getItemPos(itemIdx, dataLength) {
            const { pad, subCols, blockSize, stepX } = this.getGridMetrics(dataLength);
            const startCol = itemIdx * subCols;
            const startX = pad.left + startCol * stepX;
            const endX = startX + (subCols - 1) * stepX + blockSize;
            const centerX = (startX + endX) / 2;
            return { startCol, startX, endX, centerX, subCols, blockSize, stepX };
        }

        bindEvents() {
            const handlePointer = (e) => {
                const rect = this.canvas.getBoundingClientRect();
                const mx = e.clientX - rect.left;
                const data = this.getData();
                if (!data || !data.length) return;

                const { pad, subCols, stepX } = this.getGridMetrics(data.length);
                const plotX = pad.left;
                if (mx >= plotX && mx <= this.width - pad.right) {
                    const gridCol = Math.floor((mx - plotX) / stepX);
                    const idx = Math.floor(gridCol / subCols);
                    if (idx >= 0 && idx < data.length) {
                        this.hoveredIdx = idx;
                        this.draw();
                        this.updateTooltip(idx);
                    }
                }
            };

            this.canvas.addEventListener('mousemove', handlePointer);
            this.canvas.addEventListener('touchstart', (e) => {
                if (e.touches.length > 0) handlePointer(e.touches[0]);
            }, { passive: true });
            this.canvas.addEventListener('touchmove', (e) => {
                if (e.touches.length > 0) handlePointer(e.touches[0]);
            }, { passive: true });
            this.canvas.addEventListener('mouseleave', () => {
                if (this.tooltip) this.tooltip.classList.remove('visible');
                this.hoveredIdx = null;
                this.draw();
            });

            window.addEventListener('touchstart', (e) => {
                if (this.canvas && !this.canvas.contains(e.target) && (!this.tooltip || !this.tooltip.contains(e.target))) {
                    if (this.tooltip) this.tooltip.classList.remove('visible');
                    this.hoveredIdx = null;
                    this.draw();
                }
            }, { passive: true });
        }

        updateTooltip(idx) {
            const data = this.getData();
            const item = data[idx];
            if (!item || !this.tooltip) return;

            const { centerX } = this.getItemPos(idx, data.length);
            const { pad, stepY, totalRows } = this.getGridMetrics(data.length);
            const maxVal = this.getMaxVal(data);
            const valPerRow = maxVal / Math.max(totalRows, 1);

            const expRows = item.expense > 0 ? Math.max(Math.round((item.expense * this.animProgress) / valPerRow), 1) : 0;
            const incRows = item.income > 0 ? Math.max(Math.round((item.income * this.animProgress) / valPerRow), 1) : 0;
            const totalHRows = Math.max(expRows + incRows, 1);
            const topY = (this.height - pad.bottom) - totalHRows * stepY;

            document.getElementById('kmtTitle').innerText = item.fullLabel;
            document.getElementById('kmtIncome').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(item.income);
            document.getElementById('kmtExpense').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(item.expense);

            const net = item.income - item.expense;
            const netEl = document.getElementById('kmtNet');
            netEl.innerText = (net < 0 ? '- Rp ' : 'Rp ') + new Intl.NumberFormat('id-ID').format(Math.abs(net));
            netEl.style.color = net >= 0 ? '#10b981' : '#ef4444';

            // Safe clamping to prevent tooltip from overflowing on phone edges
            const halfW = 85;
            const clampedX = Math.max(halfW + 8, Math.min(this.width - halfW - 8, centerX));
            this.tooltip.style.left = `${clampedX}px`;
            this.tooltip.style.top = `${Math.max(topY - 12, 10)}px`;
            this.tooltip.classList.add('visible');
        }

        getMaxVal(data) {
            let max = 100000;
            data.forEach(d => {
                if (d.total > max) max = d.total;
            });
            const mag = Math.pow(10, Math.floor(Math.log10(max)));
            return Math.ceil(max / mag) * mag;
        }

        draw() {
            if (!this.canvas) return;
            const ctx = this.ctx;
            const w = this.width;
            const h = this.height;

            ctx.clearRect(0, 0, w, h);

            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const theme = {
                textMuted: isDark ? '#8e8e93' : '#64748b',
                axisLine: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.06)',
                incomeColor: '#10b981', // Emerald Green
                expenseColor: '#ef4444', // Rose Red
                hoverLine: isDark ? 'rgba(255, 255, 255, 0.35)' : 'rgba(0, 0, 0, 0.35)',
                markerBorder: isDark ? '#ff5e1a' : '#1e293b',
                markerFill: isDark ? '#080809' : '#ffffff',
                labelColor: isDark ? '#8e8e93' : '#475569',
                labelActive: isDark ? '#ffffff' : '#0f172a',
                separatorDot: isDark ? '#3f3f46' : '#cbd5e1'
            };

            const pad = this.getPadding();
            const plotX = pad.left;
            const plotY = pad.top;
            const data = this.getData();
            if (!data || !data.length) return;

            const { plotW, plotH, subCols, blockSize, stepX, stepY, totalRows } = this.getGridMetrics(data.length);
            const maxVal = this.getMaxVal(data);
            const isSmall = this.width < 500;

            // 1. Draw Y-axis Labels & Faint Horizontal Grid Lines
            ctx.font = isSmall ? '500 9.5px Inter, sans-serif' : '500 11px Inter, sans-serif';
            ctx.fillStyle = theme.textMuted;
            ctx.textAlign = 'right';
            ctx.textBaseline = 'middle';

            const yTicks = [0, 0.25, 0.5, 0.75, 1].map(f => f * maxVal);
            yTicks.forEach(tick => {
                const tickY = (plotY + plotH) - (tick / maxVal) * (totalRows * stepY);
                let label = 'Rp 0';
                if (tick >= 1000000) label = 'Rp ' + (tick / 1000000).toLocaleString('id-ID') + 'Jt';
                else if (tick >= 1000) label = 'Rp ' + (tick / 1000).toLocaleString('id-ID') + 'Rb';
                else if (tick > 0) label = 'Rp ' + tick;

                ctx.fillText(label, plotX - (isSmall ? 8 : 16), tickY);

                // Small tick dot
                ctx.beginPath();
                ctx.arc(plotX - (isSmall ? 4 : 8), tickY, 1.5, 0, Math.PI * 2);
                ctx.fillStyle = theme.separatorDot;
                ctx.fill();

                // Subtle grid line
                ctx.strokeStyle = theme.axisLine;
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.moveTo(plotX - 2, tickY);
                ctx.lineTo(plotX + plotW, tickY);
                ctx.stroke();
            });

            // 2. Draw Active Stacked Matrix Blocks (Distributed Non-flat Equalizer Silhouette)
            const valPerRow = maxVal / Math.max(totalRows, 1);
            const emptyBaseColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';

            for (let i = 0; i < data.length; i++) {
                const item = data[i];
                const hasActivity = (item.income > 0 || item.expense > 0);

                // Draw each sub-column for this data item
                for (let sc = 0; sc < subCols; sc++) {
                    const gridCol = i * subCols + sc;
                    const colX = plotX + gridCol * stepX;

                    if (!hasActivity) {
                        // Empty month/day: 1 solid baseline square
                        ctx.fillStyle = emptyBaseColor;
                        const cy = (plotY + plotH) - stepY;
                        this.drawBrick(ctx, colX, cy, blockSize, blockSize, 1.5);
                        continue;
                    }

                    // Equalizer distribution profile across the sub-columns (Never flat!)
                    let factor;
                    if (subCols === 6) {
                        const baseWeights = [0.35, 0.70, 1.0, 0.92, 0.65, 0.38];
                        const nuance = Math.sin(i * 2.3 + sc * 1.5) * 0.08;
                        factor = Math.max(0.25, Math.min(1.0, baseWeights[sc] + nuance));
                    } else if (subCols === 3) {
                        const baseWeights = [0.60, 1.0, 0.75];
                        const nuance = Math.sin(i * 1.9 + sc * 1.7) * 0.06;
                        factor = Math.max(0.35, Math.min(1.0, baseWeights[sc] + nuance));
                    } else if (subCols > 1) {
                        const mid = (subCols - 1) / 2;
                        const dist = Math.abs(sc - mid) / mid;
                        factor = Math.max(0.35, 1 - dist * 0.65);
                    } else {
                        factor = 1.0;
                    }

                    const scExpense = item.expense * factor;
                    const scIncome = item.income * factor;

                    const expUnits = Math.round((scExpense * this.animProgress) / valPerRow);
                    const incUnits = Math.round((scIncome * this.animProgress) / valPerRow);

                    const isPeakCol = (factor >= 0.65);
                    const expenseRows = scExpense > 0 ? (isPeakCol ? Math.max(expUnits, 1) : expUnits) : 0;
                    const incomeRows = scIncome > 0 ? (isPeakCol ? Math.max(incUnits, 1) : incUnits) : 0;

                    if (expenseRows === 0 && incomeRows === 0) {
                        ctx.fillStyle = emptyBaseColor;
                        const cy = (plotY + plotH) - stepY;
                        this.drawBrick(ctx, colX, cy, blockSize, blockSize, 1.5);
                        continue;
                    }

                    // Bottom: Beban (Expense)
                    if (expenseRows > 0) {
                        ctx.fillStyle = theme.expenseColor;
                        for (let r = 0; r < expenseRows; r++) {
                            const cy = (plotY + plotH) - (r + 1) * stepY;
                            this.drawBrick(ctx, colX, cy, blockSize, blockSize, 1.5);
                        }
                    }

                    // Top: Pendapatan (Income stacked on top of expense)
                    if (incomeRows > 0) {
                        ctx.fillStyle = theme.incomeColor;
                        for (let r = expenseRows; r < (expenseRows + incomeRows); r++) {
                            const cy = (plotY + plotH) - (r + 1) * stepY;
                            this.drawBrick(ctx, colX, cy, blockSize, blockSize, 1.5);
                        }
                    }
                }
            }

            // 3. Draw Hover Indicator
            if (this.hoveredIdx !== null && this.hoveredIdx < data.length) {
                const hItem = data[this.hoveredIdx];
                const { centerX } = this.getItemPos(this.hoveredIdx, data.length);

                const expRows = hItem.expense > 0 ? Math.max(Math.round((hItem.expense * this.animProgress) / valPerRow), 1) : 0;
                const incRows = hItem.income > 0 ? Math.max(Math.round((hItem.income * this.animProgress) / valPerRow), 1) : 0;
                const totalHRows = Math.max(expRows + incRows, 1);
                const topY = (plotY + plotH) - totalHRows * stepY;

                // Dashed guide line
                ctx.save();
                ctx.setLineDash([3, 3]);
                ctx.strokeStyle = theme.hoverLine;
                ctx.lineWidth = 1.2;
                ctx.beginPath();
                ctx.moveTo(centerX, plotY);
                ctx.lineTo(centerX, plotY + plotH);
                ctx.stroke();
                ctx.restore();

                // Marker ring
                ctx.beginPath();
                ctx.arc(centerX, topY - 5, 4.5, 0, Math.PI * 2);
                ctx.fillStyle = theme.markerFill;
                ctx.fill();
                ctx.strokeStyle = theme.incomeColor;
                ctx.lineWidth = 2.5;
                ctx.stroke();
            }

            // 4. Draw X-Axis Labels
            ctx.font = isSmall ? '600 10px Inter, sans-serif' : '600 11px Inter, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'top';

            if (this.mode === 'monthly') {
                data.forEach((item, mIdx) => {
                    const { centerX } = this.getItemPos(mIdx, data.length);
                    const isHovered = (this.hoveredIdx === mIdx);

                    ctx.fillStyle = isHovered ? theme.labelActive : theme.labelColor;
                    ctx.font = isHovered ? (isSmall ? '700 10px Inter, sans-serif' : '700 11px Inter, sans-serif') : (isSmall ? '600 9.5px Inter, sans-serif' : '600 10.5px Inter, sans-serif');
                    const monthText = isSmall ? item.label.substring(0, 3) : item.label;
                    ctx.fillText(monthText, centerX, plotY + plotH + 12);

                    if (mIdx < data.length - 1 && !isSmall) {
                        const nextPos = this.getItemPos(mIdx + 1, data.length);
                        const dotX = (centerX + nextPos.centerX) / 2;
                        ctx.fillStyle = theme.separatorDot;
                        ctx.beginPath();
                        ctx.arc(dotX, plotY + plotH + 18, 1.5, 0, Math.PI * 2);
                        ctx.fill();
                    }
                });
            } else {
                const stepDays = (this.width < 450) ? 5 : 3;
                data.forEach((item, dIdx) => {
                    const dayNum = parseInt(item.label);
                    const shouldShowLabel = (dayNum === 1 || dayNum % stepDays === 0 || dayNum === data.length);
                    if (shouldShowLabel) {
                        const { centerX } = this.getItemPos(dIdx, data.length);
                        const isHovered = (this.hoveredIdx === dIdx);

                        ctx.fillStyle = isHovered ? theme.labelActive : theme.labelColor;
                        ctx.font = isHovered ? '700 10px Inter, sans-serif' : '600 9.5px Inter, sans-serif';
                        ctx.fillText(item.label, centerX, plotY + plotH + 12);
                    }
                });
            }
        }

        drawBrick(ctx, x, y, w, h, r) {
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.lineTo(x + w - r, y);
            ctx.quadraticCurveTo(x + w, y, x + w, y + r);
            ctx.lineTo(x + w, y + h - r);
            ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
            ctx.lineTo(x + r, y + h);
            ctx.quadraticCurveTo(x, y + h, x, y + h - r);
            ctx.lineTo(x, y + r);
            ctx.quadraticCurveTo(x, y, x + r, y);
            ctx.closePath();
            ctx.fill();
        }
    }

    window.kasqlMatrixInstance = new KasqlMatrixBarChart('dailyMatrixCanvas', 'kasqlMatrixTooltip');

    window.switchKasqlMatrixMode = function(mode) {
        const isMonthly = (mode === 'monthly');
        const btnDaily = document.getElementById('btnMatrixDaily');
        const btnMonthly = document.getElementById('btnMatrixMonthly');
        if (btnDaily) btnDaily.classList.toggle('active', !isMonthly);
        if (btnMonthly) btnMonthly.classList.toggle('active', isMonthly);

        const subtitleEl = document.getElementById('trendSubtitle');
        const labelEl = document.getElementById('trendMetricLabel');
        const valEl = document.getElementById('trendMetricValue');
        const badgeEl = document.getElementById('trendMetricBadge');
        const monthContainer = document.getElementById('monthFilterContainer');

        if (isMonthly) {
            // Mode Bulanan: Tampilkan konteks Tahun Finansial penuh
            if (subtitleEl) subtitleEl.innerText = `(Tahun Finansial ${currentYear})`;
            if (labelEl) labelEl.innerText = 'Total Pendapatan :';
            if (valEl) valEl.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(<?= $total_pendapatan ?>);
            if (badgeEl) badgeEl.innerText = '12 Bulan (Jan - Des)';
            if (monthContainer) monthContainer.style.display = 'none'; // Sembunyikan filter bulan agar tidak membingungkan
        } else {
            // Mode Harian: Tampilkan konteks bulan yang dipilih
            if (subtitleEl) subtitleEl.innerText = `(${currentMonthName} ${currentYear})`;
            if (labelEl) labelEl.innerText = 'Total Volume :';
            if (valEl) valEl.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(<?= $daily_trend['total_volume_bulan'] ?>);
            if (badgeEl) badgeEl.innerText = '<?= number_format($daily_trend['total_trx_bulan']) ?> Transaksi';
            if (monthContainer) monthContainer.style.display = 'inline-flex'; // Tampilkan kembali
        }

        if (window.kasqlMatrixInstance) {
            window.kasqlMatrixInstance.setMode(mode);
        }
    };

    // ==========================================
    // 5. DOUGHNUT CHART: KOMPOSISI ASET
    // ==========================================
    const assetLabels = <?= json_encode($aset_labels) ?>;
    const assetData = <?= json_encode($aset_data) ?>;
    
    let assetLabelsWithPercent = ['Tidak ada data'];
    if (assetData.length > 0) {
        const totalAsset = assetData.reduce((a, b) => a + parseFloat(b), 0);
        assetLabelsWithPercent = assetLabels.map((label, index) => {
            const val = parseFloat(assetData[index]);
            const percent = totalAsset > 0 ? Math.round((val / totalAsset) * 100) : 0;
            return `${label} (${percent}%)`;
        });
    }

    const ctxDoughnut = document.getElementById('doughnutChart').getContext('2d');
    
    const doughnutPaletteLight = ['#ff5e1a', '#0284c7', '#059669', '#7c3aed', '#d97706'];
    const doughnutPaletteDark = ['#ff5e1a', '#38bdf8', '#34d399', '#a78bfa', '#fbbf24'];

    const doughnutChartInstance = new Chart(ctxDoughnut, {
        type: 'doughnut',
        data: {
            labels: assetLabelsWithPercent,
            datasets: [{
                data: assetData.length > 0 ? assetData : [1],
                backgroundColor: assetData.length > 0 ? (isDark ? doughnutPaletteDark : doughnutPaletteLight) : ['#71717a'],
                borderWidth: 0,
                hoverOffset: 4,
                borderRadius: 4,
                spacing: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: {
                    display: false // Menggunakan daftar breakdown custom yang 100% selaras dengan tema
                },
                tooltip: {
                    backgroundColor: isDark ? 'rgba(17, 17, 20, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                    titleColor: isDark ? '#fff' : '#000',
                    bodyColor: isDark ? '#a1a1aa' : '#475569',
                    borderColor: isDark ? 'rgba(255, 255, 255, 0.1)' : '#e2e8f0',
                    borderWidth: 1,
                    padding: 10,
                    callbacks: {
                        label: function(context) {
                            if(assetData.length === 0) return 'Kosong';
                            let label = context.label || '';
                            label = label.replace(/\s\(\d+%\)$/, '');
                            let value = parseFloat(context.parsed);
                            let total = context.dataset.data.reduce((a, b) => a + parseFloat(b), 0);
                            let percentage = total > 0 ? Math.round((value / total) * 100) + '%' : '0%';
                            return ` ${label}: Rp ${new Intl.NumberFormat('id-ID').format(value)} (${percentage})`;
                        }
                    }
                }
            }
        }
    });

    // Handle Theme Change Without Reload
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'data-theme') {
                const nowDark = document.documentElement.getAttribute('data-theme') === 'dark';
                
                // Update global colors
                Chart.defaults.color = nowDark ? '#a1a1aa' : '#475569';
                const newTooltipBg = nowDark ? 'rgba(17, 17, 20, 0.95)' : 'rgba(255, 255, 255, 0.95)';
                const newTooltipTitle = nowDark ? '#fff' : '#000';
                const newTooltipBody = nowDark ? '#a1a1aa' : '#475569';
                const newTooltipBorder = nowDark ? 'rgba(255, 255, 255, 0.1)' : '#e2e8f0';

                // Update Matrix Chart
                if (window.kasqlMatrixInstance) {
                    window.kasqlMatrixInstance.draw();
                }

                // Update Doughnut Chart (Theme-Unified Colors)
                doughnutChartInstance.data.datasets[0].backgroundColor = assetData.length > 0 ? (nowDark ? doughnutPaletteDark : doughnutPaletteLight) : ['#71717a'];
                doughnutChartInstance.options.plugins.tooltip.backgroundColor = newTooltipBg;
                doughnutChartInstance.options.plugins.tooltip.titleColor = newTooltipTitle;
                doughnutChartInstance.options.plugins.tooltip.bodyColor = newTooltipBody;
                doughnutChartInstance.options.plugins.tooltip.borderColor = newTooltipBorder;
                doughnutChartInstance.update();
            }
        });
    });
    observer.observe(document.documentElement, { attributes: true });
});
</script>
</body>
</html>
