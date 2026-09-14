<!DOCTYPE html>
<html lang="id">
<head>
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data['title'] ?> - <?= htmlspecialchars($_SESSION['nama_perusahaan'] ?? 'USAHA') ?></title>
    <meta name="description" content="Menu closing periode akuntansi untuk menutup dan mengunci periode bulanan">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css?v=<?= time() ?>">
    <script src="<?= BASE_URL ?>/public/js/script.js?v=2.0" defer></script>
    <style>
        .closing-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        /* ======== Stat Cards ======== */
        .period-stats,
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 1.25rem 1.4rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: rgba(255, 255, 255, 0.2);
        }
        [data-theme="light"] .stat-card:hover {
            border-color: rgba(0, 0, 0, 0.15);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
        }
        .stat-card .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
        }
        .stat-card .stat-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        .stat-card .stat-number {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-main);
            margin-top: 0.25rem;
        }
        .stat-card.stat-open .stat-number {
            background: linear-gradient(135deg, #10b981, #34d399);
            -webkit-background-clip: text;
            background-clip: text;
        }
        .stat-card.stat-closed .stat-number {
            color: var(--text-main);
        }
        .stat-card.stat-laba .stat-number {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            -webkit-background-clip: text;
            background-clip: text;
        }

        /* ======== Status Badges ======== */
        .badge-open {
            background: rgba(16, 185, 129, 0.15);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .badge-closed {
            background: #ebebe8;
            color: #52525b;
            border: 1px solid rgba(0, 0, 0, 0.08);
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        [data-theme="dark"] .badge-closed {
            background: rgba(255, 255, 255, 0.08);
            color: #d4d4d8;
            border-color: rgba(255, 255, 255, 0.12);
        }
        .badge-reopened {
            background: rgba(245, 158, 11, 0.15);
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.3);
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        [data-theme="dark"] .badge-open {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
        }
        [data-theme="dark"] .badge-reopened {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }

        /* ======== Action Buttons ======== */
        .btn-close-periode {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .btn-close-periode:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
            background: linear-gradient(135deg, #dc2626, #b91c1c);
        }
        .btn-close-periode:active {
            transform: translateY(0);
        }
        .btn-locked {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.5rem 1rem;
            background: var(--btn-disabled-bg);
            color: var(--btn-disabled-text);
            border: 1px solid var(--btn-disabled-border);
            border-radius: var(--radius-sm);
            font-size: 0.82rem;
            font-weight: 600;
            cursor: not-allowed;
            opacity: 0.7;
        }
        [data-theme="dark"] .btn-locked {
            background: rgba(51, 65, 85, 0.5);
            color: #64748b;
            border-color: rgba(71, 85, 105, 0.5);
        }

        /* ======== Generate Form ======== */
        .generate-form {
            display: flex;
            align-items: flex-end;
            gap: 12px;
            flex-wrap: wrap;
        }
        .generate-form .form-group {
            margin-bottom: 0;
        }

        /* ======== Confirmation Modal ======== */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            animation: modalFadeIn 0.3s ease;
        }
        .modal-overlay.active {
            display: flex;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes modalSlideIn {
            from { opacity: 0; transform: translateY(-30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .modal-content {
            background: var(--bg-surface);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-lg);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            margin: 20px;
            animation: modalSlideIn 0.4s ease;
        }
        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s ease;
            line-height: 1;
        }
        .modal-close:hover {
            background: var(--bg-surface-hover);
            color: var(--danger-color);
        }
        .modal-body {
            padding: 1.5rem 2rem;
        }
        .modal-footer {
            padding: 1.25rem 2rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Modal Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .info-item {
            padding: 0.85rem 1rem;
            background: var(--bg-surface-hover);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
        }
        .info-item .info-label {
            font-size: 0.72rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .info-item .info-value {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-main);
        }
        .info-item.span-2 {
            grid-column: span 2;
        }

        /* Financial Summary in Modal */
        .finance-summary {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .finance-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.85rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
        }
        .finance-row:last-child {
            border-bottom: none;
        }
        .finance-row .fin-label {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-main);
        }
        .finance-row .fin-value {
            font-size: 0.95rem;
            font-weight: 700;
        }
        .finance-row.row-pendapatan .fin-value { color: var(--success-color); }
        .finance-row.row-beban .fin-value { color: var(--danger-color); }
        .finance-row.row-laba {
            background: var(--bg-surface-hover);
            font-size: 1rem;
        }
        .finance-row.row-laba .fin-label { font-weight: 700; }
        .finance-row.row-laba .fin-value { font-size: 1.1rem; }
        .fin-laba { color: var(--success-color); }
        .fin-rugi { color: var(--danger-color); }

        /* Warning Box */
        .warning-box {
            background: var(--warning-bg);
            border: 1px solid rgba(245, 158, 11, 0.4);
            border-radius: var(--radius-sm);
            padding: 1rem 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 0.5rem;
        }
        .warning-box .warn-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .warning-box .warn-text {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--warning-color);
            line-height: 1.5;
        }

        /* Validation Badge in Modal */
        .validation-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.3rem 0.6rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .validation-ok {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success-color);
        }
        .validation-fail {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger-color);
        }

        /* Loading State */
        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }
        .btn-loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }
        @keyframes spin {
            to { transform: translateY(-50%) rotate(360deg); }
        }

        /* Laba/Rugi in table */
        .laba-positif { color: var(--success-color); font-weight: 600; }
        .laba-negatif { color: var(--danger-color); font-weight: 600; }

        /* Closed info tooltip */
        .closed-info {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 3px;
        }

        /* Progress bar for validation */
        .progress-bar-container {
            width: 100%;
            height: 6px;
            background: var(--border-color);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 6px;
        }
        .progress-bar-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.5s ease;
        }
        .progress-ok { background: linear-gradient(90deg, #10b981, #34d399); }
        .progress-fail { background: linear-gradient(90deg, #ef4444, #f87171); }

        @media print {
            .no-print { display: none !important; }
        }

        .generate-form {
            display: flex;
            align-items: flex-end;
            gap: 12px;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .info-grid { grid-template-columns: 1fr; }
            .info-item.span-2 { grid-column: span 1; }
            .period-stats { grid-template-columns: repeat(2, 1fr); }
            .generate-form { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/../layout/sidebar.php'; ?>

<div class="main-content">
    <?php include __DIR__ . '/../layout/header.php'; ?>
    
    <div class="container">
        <!-- Page Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); margin-bottom: 1.75rem; padding-bottom: 1rem;">
            <h2 style="border: none; margin: 0; padding: 0;" class="page-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px; color: var(--primary-color);">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                    <path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"></path>
                </svg>
                Closing Periode Akuntansi
            </h2>
        </div>

        <!-- Alerts -->
        <?php if($data['error']): ?>
            <div style="background-color: #fee2e2; border: 1px solid #ef4444; color: #b91c1c; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 4px rgba(239, 68, 68, 0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <span><?= htmlspecialchars($data['error']) ?></span>
            </div>
        <?php endif; ?>
        <?php if($data['success']): ?>
            <div style="background-color: #d1fae5; border: 1px solid #10b981; color: #065f46; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <span><?= htmlspecialchars($data['success']) ?></span>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <?php
            $count_open = 0; $count_closed = 0; $total_laba = 0;
            foreach ($data['periodes'] as $p) {
                $is_past_locked = (strtotime($p['tanggal_selesai']) <= strtotime('2026-05-31'));
                if ($p['status'] === 'closed' || $is_past_locked) { 
                    $count_closed++; 
                    $total_laba += floatval($p['laba_periode']); 
                } else if ($p['status'] === 'open' || $p['status'] === 'reopened') {
                    $count_open++;
                }
            }
        ?>
        <div class="period-stats">
            <div class="stat-card">
                <div class="stat-number"><?= count($data['periodes']) ?></div>
                <div class="stat-label">Total Periode</div>
            </div>
            <div class="stat-card stat-open">
                <div class="stat-number"><?= $count_open ?></div>
                <div class="stat-label">Periode Terbuka</div>
            </div>
            <div class="stat-card stat-closed">
                <div class="stat-number"><?= $count_closed ?></div>
                <div class="stat-label">Periode Tertutup</div>
            </div>
            <div class="stat-card stat-laba">
                <div class="stat-number">Rp <?= number_format(abs($total_laba), 0, ',', '.') ?></div>
                <div class="stat-label">Total <?= $total_laba >= 0 ? 'Laba' : 'Rugi' ?> Kumulatif</div>
            </div>
        </div>

        <!-- Filter & Generate Card -->
        <div class="card no-print" style="margin-bottom: 2rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                <!-- Filter Section -->
                <div>
                    <h3 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary-color);">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                        Filter Periode
                    </h3>
                    <form method="GET" action="" class="form-row" id="formFilterPeriode">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="filter_tahun" class="form-label">Tahun:</label>
                            <select name="tahun" id="filter_tahun" class="form-control" style="width: auto; min-width: 140px;" onchange="this.form.submit()">
                                <option value="semua" <?= $data['filter_tahun'] === 'semua' ? 'selected' : '' ?>>-- Semua Tahun --</option>
                                <?php foreach($data['years_available'] as $y): ?>
                                    <option value="<?= $y ?>" <?= strval($data['filter_tahun']) === strval($y) ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                
                <!-- Generate Section (Admin Only) -->
                <?php 
                $curr_user_role = $_SESSION['user_role'] ?? 'cashier';
                $is_admin_user = ($curr_user_role === 'admin');
                if ($is_admin_user): 
                ?>
                <div>
                    <h3 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--success-color);">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="16"></line>
                            <line x1="8" y1="12" x2="16" y2="12"></line>
                        </svg>
                        Buat Periode Baru
                    </h3>
                    <form method="POST" class="generate-form" id="formGeneratePeriode">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="generate_periode">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="tahun_generate" class="form-label">Tahun:</label>
                            <select name="tahun" id="tahun_generate" class="form-control" style="width: auto; min-width: 120px;" required>
                                <?php 
                                $current_year = intval(date('Y'));
                                for($y = $current_year - 2; $y <= $current_year + 2; $y++): ?>
                                    <option value="<?= $y ?>" <?= $y == $current_year ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="16"></line>
                                <line x1="8" y1="12" x2="16" y2="12"></line>
                            </svg>
                            Generate 12 Bulan
                        </button>
                    </form>
                </div>
                <?php else: ?>
                <div style="display: flex; align-items: center; background: var(--bg-surface-hover); border-radius: var(--radius-md); padding: 1.25rem; border: 1px dashed var(--border-color);">
                    <div style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.5;">
                        <strong style="color: var(--text-main);">Mode Monitoring (Akuntan):</strong><br>
                        Pembuatan dan eksekusi penutupan periode akuntansi dikhususkan untuk role <strong>Administrator</strong>.
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Periode Table -->
        <?php if (count($data['periodes']) > 0): ?>
        <div class="table-container">
            <table class="table" id="tabel-periode">
                <thead>
                    <tr>
                        <th style="width: 5%">No</th>
                        <th style="width: 20%">Nama Periode</th>
                        <th style="width: 12%">Tanggal Mulai</th>
                        <th style="width: 12%">Tanggal Selesai</th>
                        <th style="width: 10%" class="text-center">Status</th>
                        <th style="width: 15%" class="text-right">Laba/Rugi</th>
                        <th style="width: 13%" class="text-center no-print">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['periodes'] as $idx => $p): 
                        $status = $p['status'];
                        $is_open = ($status === 'open' || $status === 'reopened');
                        $is_past_locked = (strtotime($p['tanggal_selesai']) <= strtotime('2026-05-31'));
                        
                        $is_admin_user = ($_SESSION['user_role'] ?? 'cashier') === 'admin';
                        $can_close_ui = $is_admin_user && $is_open && !$is_past_locked;
                        
                        $laba = floatval($p['laba_periode']);
                    ?>
                    <tr>
                        <td class="text-center"><?= $idx + 1 ?></td>
                        <td style="font-weight: 600;"><?= htmlspecialchars($p['nama_periode']) ?></td>
                        <td class="text-center"><?= date('d M Y', strtotime($p['tanggal_mulai'])) ?></td>
                        <td class="text-center"><?= date('d M Y', strtotime($p['tanggal_selesai'])) ?></td>
                        <td class="text-center">
                            <?php if ($is_past_locked): ?>
                                <span class="badge-closed" style="background: rgba(100, 116, 139, 0.12); color: var(--text-muted); border-color: rgba(100, 116, 139, 0.25);" title="Periode lampau dikunci oleh sistem">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; vertical-align: middle;">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                    LOCKED (SISTEM)
                                </span>
                            <?php elseif ($status === 'open'): ?>
                                <span class="badge-open">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 8 8" fill="currentColor" style="margin-right: 4px;">
                                        <circle cx="4" cy="4" r="4"></circle>
                                    </svg>
                                    OPEN
                                </span>
                            <?php elseif ($status === 'closed'): ?>
                                <span class="badge-closed">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; vertical-align: middle;">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                    CLOSED
                                </span>
                                <?php if ($p['closed_by_name']): ?>
                                    <div class="closed-info">oleh: <?= htmlspecialchars($p['closed_by_name']) ?></div>
                                    <div class="closed-info"><?= $p['tanggal_closed'] ? date('d/m/Y H:i', strtotime($p['tanggal_closed'])) : '' ?></div>
                                <?php endif; ?>
                            <?php elseif ($status === 'reopened'): ?>
                                <span class="badge-reopened">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; vertical-align: middle;">
                                        <path d="M23 4v6h-6"></path>
                                        <path d="M1 20v-6h6"></path>
                                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                                    </svg>
                                    REOPENED
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <?php if ($status === 'closed'): ?>
                                <span class="<?= $laba >= 0 ? 'laba-positif' : 'laba-negatif' ?>">
                                    <?= $laba >= 0 ? '' : '(' ?>Rp <?= number_format(abs($laba), 0, ',', '.') ?><?= $laba >= 0 ? '' : ')' ?>
                                </span>
                            <?php else: ?>
                                <span style="color: var(--text-muted);">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center no-print">
                            <?php if ($can_close_ui): ?>
                                <button type="button" class="btn-close-periode" onclick="openClosingModal(<?= $p['id'] ?>)" id="btn-close-<?= $p['id'] ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                    Tutup Periode
                                </button>
                            <?php elseif ($is_open && !$is_past_locked && !$is_admin_user): ?>
                                <span class="badge-reopened" style="font-size: 0.75rem; padding: 0.3rem 0.6rem; cursor: not-allowed;" title="Hanya Administrator yang dapat mengeksekusi penutupan periode">
                                    Akses Admin
                                </span>
                            <?php else: ?>
                                <span class="btn-locked" title="<?= $is_past_locked ? 'Periode lampau dikunci oleh sistem' : 'Periode sudah terkunci' ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                    Terkunci
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="card" style="text-align: center; color: var(--text-muted); padding: 3rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 1rem; opacity: 0.4;">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">Belum ada periode akuntansi.</p>
            <p style="font-size: 0.9rem;">Gunakan form di atas untuk membuat periode baru.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL KONFIRMASI CLOSING -->
<!-- ============================================================ -->
<div class="modal-overlay" id="closingModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--danger-color);">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                Konfirmasi Tutup Periode
            </h3>
            <button type="button" class="modal-close" onclick="closeClosingModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBodyContent">
            <!-- Content loaded via JS -->
            <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                <div style="width: 40px; height: 40px; border: 3px solid var(--border-color); border-top-color: var(--primary-color); border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 1rem;"></div>
                Memuat data ringkasan...
            </div>
        </div>
        <div class="modal-footer" id="modalFooter" style="display: none;">
            <button type="button" class="btn btn-secondary" onclick="closeClosingModal()">Batal</button>
            <form method="POST" id="formClosePeriode" action="<?= BASE_URL ?>/closing" style="display: inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="close_periode">
                <input type="hidden" name="periode_id" id="input_periode_id" value="">
                <button type="submit" class="btn-close-periode" id="btnConfirmClose" style="padding: 0.6rem 1.5rem; font-size: 0.9rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    Ya, Tutup Periode Sekarang
                </button>
            </form>
        </div>
    </div>
</div>

<script>
let currentPeriodeId = null;

function formatRupiah(num) {
    const isNegative = num < 0;
    return (isNegative ? '- Rp ' : 'Rp ') + Math.abs(num).toLocaleString('id-ID');
}

function openClosingModal(periodeId) {
    currentPeriodeId = periodeId;
    const modal = document.getElementById('closingModal');
    const body = document.getElementById('modalBodyContent');
    const footer = document.getElementById('modalFooter');
    
    modal.classList.add('active');
    footer.style.display = 'none';
    
    // Show loading
    body.innerHTML = `
        <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
            <div style="width: 40px; height: 40px; border: 3px solid var(--border-color); border-top-color: var(--primary-color); border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 1rem;"></div>
            Memuat data ringkasan...
        </div>
    `;
    
    // Fetch preview data
    fetch(`<?= BASE_URL ?>/closing?action=get_preview&periode_id=${periodeId}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                body.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
                return;
            }
            
            const p = data.periode;
            const canClose = data.total_pending === 0;
            const isLaba = data.laba >= 0;
            const startDate = new Date(p.tanggal_mulai).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'});
            const endDate = new Date(p.tanggal_selesai).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'});
            
            // SVG Icons (Lucide outline style)
            const iconCheck = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
            const iconX = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>`;
            const iconPending = `<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>`;
            const iconKoreksi = `<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`;
            const iconTrendUp = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; color: var(--success-color);"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>`;
            const iconTrendDown = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; color: var(--danger-color);"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline><polyline points="17 18 23 18 23 12"></polyline></svg>`;
            const iconDollar = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; color: var(--success-color);"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>`;
            const iconAlertCircle = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; color: var(--danger-color);"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`;
            const iconAlertTriangle = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; color: var(--warning-color);"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`;

            let validationHtml = '';
            if (data.total_transaksi === 0) {
                validationHtml = `<span class="validation-badge validation-ok">${iconCheck} Tidak ada transaksi</span>`;
            } else if (canClose) {
                validationHtml = `<span class="validation-badge validation-ok">${iconCheck} 100% Terverifikasi</span>`;
            } else {
                validationHtml = `<span class="validation-badge validation-fail">${iconX} ${data.persen_sesuai}% — Belum lengkap</span>`;
            }
            
            let detailValidasi = '';
            if (data.total_pending > 0) {
                detailValidasi += `<div style="font-size: 0.8rem; color: var(--warning-color); margin-top: 4px; display: inline-flex; align-items: center; gap: 4px;">${iconPending} ${data.total_pending} transaksi pending</div>`;
            }
            if (data.total_koreksi > 0) {
                const marginTop = data.total_pending > 0 ? '6px' : '4px';
                detailValidasi += `<div style="font-size: 0.8rem; color: var(--danger-color); margin-top: ${marginTop}; display: inline-flex; align-items: center; gap: 4px;">${iconKoreksi} ${data.total_koreksi} transaksi koreksi</div>`;
            }
            
            body.innerHTML = `
                <div class="info-grid">
                    <div class="info-item span-2">
                        <div class="info-label">Nama Periode</div>
                        <div class="info-value">${p.nama_periode}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Tanggal Mulai</div>
                        <div class="info-value">${startDate}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Tanggal Selesai</div>
                        <div class="info-value">${endDate}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Total Transaksi</div>
                        <div class="info-value">${data.total_transaksi}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Validasi Transaksi</div>
                        <div class="info-value">
                            ${validationHtml}
                            ${detailValidasi}
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill ${canClose ? 'progress-ok' : 'progress-fail'}" style="width: ${data.persen_sesuai}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="finance-summary">
                    <div class="finance-row row-pendapatan">
                        <span class="fin-label" style="display: inline-flex; align-items: center; gap: 8px;">
                            ${iconTrendUp} Total Pendapatan
                        </span>
                        <span class="fin-value">${formatRupiah(data.pendapatan)}</span>
                    </div>
                    <div class="finance-row row-beban">
                        <span class="fin-label" style="display: inline-flex; align-items: center; gap: 8px;">
                            ${iconTrendDown} Total Beban
                        </span>
                        <span class="fin-value">${formatRupiah(data.beban)}</span>
                    </div>
                    <div class="finance-row row-laba">
                        <span class="fin-label" style="display: inline-flex; align-items: center; gap: 8px;">
                            ${isLaba ? iconDollar : iconAlertCircle} ${isLaba ? 'Estimasi Laba Bersih' : 'Estimasi Rugi Bersih'}
                        </span>
                        <span class="fin-value ${isLaba ? 'fin-laba' : 'fin-rugi'}">${isLaba ? '' : '('}${formatRupiah(data.laba)}${isLaba ? '' : ')'}</span>
                    </div>
                </div>
                
                <div class="warning-box">
                    <span class="warn-icon">${iconAlertTriangle}</span>
                    <span class="warn-text">
                        Setelah ditutup, Anda <strong>TIDAK BISA</strong> mengedit transaksi pada periode ini lagi! 
                        Saldo akhir akan disimpan sebagai snapshot permanen.
                    </span>
                </div>
            `;
            
            // Show/hide footer
            document.getElementById('input_periode_id').value = periodeId;
            footer.style.display = 'flex';
            
            const btnConfirm = document.getElementById('btnConfirmClose');
            if (!canClose) {
                btnConfirm.disabled = true;
                btnConfirm.style.opacity = '0.5';
                btnConfirm.style.cursor = 'not-allowed';
                btnConfirm.title = 'Semua transaksi harus berstatus "sesuai" sebelum closing.';
            } else {
                btnConfirm.disabled = false;
                btnConfirm.style.opacity = '1';
                btnConfirm.style.cursor = 'pointer';
                btnConfirm.title = '';
            }
        })
        .catch(err => {
            body.innerHTML = `<div class="alert alert-error">Gagal memuat data: ${err.message}</div>`;
        });
}

function closeClosingModal() {
    document.getElementById('closingModal').classList.remove('active');
    currentPeriodeId = null;
}

// Close modal on overlay click
document.getElementById('closingModal').addEventListener('click', function(e) {
    if (e.target === this) closeClosingModal();
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeClosingModal();
});

// Form submit loading state
document.getElementById('formClosePeriode').addEventListener('submit', function(e) {
    const btn = document.getElementById('btnConfirmClose');
    if (btn.disabled) {
        e.preventDefault();
        return;
    }
    btn.classList.add('btn-loading');
    btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> Memproses...';
});
</script>

</body>
</html>


