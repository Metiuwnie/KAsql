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
    <title>Laba Rugi — <?= htmlspecialchars($_SESSION['nama_perusahaan'] ?? 'USAHA') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css?v=<?= time() ?>">
    <script src="<?= BASE_URL ?>/public/js/script.js?v=2.0" defer></script>
    <style>
        /* High-Contrast Semantic Colors for Income Statement */
        :root {
            --fin-revenue-bg: #ecfdf5;
            --fin-revenue-text: #065f46;
            --fin-revenue-border: #a7f3d0;
            --fin-revenue-accent: #059669;

            --fin-expense-bg: #fef2f2;
            --fin-expense-text: #991b1b;
            --fin-expense-border: #fecaca;
            --fin-expense-accent: #dc2626;

            --fin-positive-color: #047857;
            --fin-negative-color: #b91c1c;
            --fin-warning-color: #b45309;

            --kpi-good-text: #047857;
            --kpi-good-bg: #ecfdf5;
            --kpi-good-border: #a7f3d0;

            --kpi-bad-text: #b91c1c;
            --kpi-bad-bg: #fef2f2;
            --kpi-bad-border: #fecaca;
        }

        [data-theme="dark"] {
            --fin-revenue-bg: rgba(16, 185, 129, 0.12);
            --fin-revenue-text: #34d399;
            --fin-revenue-border: rgba(52, 211, 153, 0.25);
            --fin-revenue-accent: #10b981;

            --fin-expense-bg: rgba(239, 68, 68, 0.12);
            --fin-expense-text: #f87171;
            --fin-expense-border: rgba(248, 113, 113, 0.25);
            --fin-expense-accent: #ef4444;

            --fin-positive-color: #34d399;
            --fin-negative-color: #f87171;
            --fin-warning-color: #fbbf24;

            --kpi-good-text: #34d399;
            --kpi-good-bg: rgba(16, 185, 129, 0.12);
            --kpi-good-border: rgba(52, 211, 153, 0.25);

            --kpi-bad-text: #f87171;
            --kpi-bad-bg: rgba(239, 68, 68, 0.12);
            --kpi-bad-border: rgba(248, 113, 113, 0.25);
        }

        .section-banner-revenue {
            background: var(--fin-revenue-bg) !important;
            color: var(--fin-revenue-text) !important;
            border-top: 2.5px solid var(--fin-revenue-accent) !important;
            border-bottom: 1px solid var(--fin-revenue-border) !important;
            font-size: 0.95rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.05em !important;
            padding: 12px 16px !important;
        }

        .section-banner-expense {
            background: var(--fin-expense-bg) !important;
            color: var(--fin-expense-text) !important;
            border-top: 2.5px solid var(--fin-expense-accent) !important;
            border-bottom: 1px solid var(--fin-expense-border) !important;
            font-size: 0.95rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.05em !important;
            padding: 12px 16px !important;
        }

        /* Print Styles for Formal Accounting Report */
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
            
            .table {
                width: 100% !important;
                border: 2px solid #000 !important;
                border-collapse: collapse !important;
                margin-bottom: 15px !important;
            }
            
            .table th, .table td {
                border: 1px solid #000 !important;
                padding: 6px 8px !important;
                color: #000 !important;
                font-size: 10.5pt !important;
            }
            
            .table thead td {
                background: #e2e8f0 !important;
                color: #000 !important;
                font-weight: bold !important;
                font-size: 11pt !important;
                border: 1px solid #000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .table thead th {
                background: #f8fafc !important;
                color: #000 !important;
                font-size: 10.5pt !important;
                border: 1px solid #000 !important;
            }
            
            .font-bold {
                background: #cbd5e1 !important;
                color: #000 !important;
                font-weight: bold !important;
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
            <h2 style="border: none; margin: 0; padding: 0;" class="page-title">Laporan Laba Rugi (Income Statement)</h2>
        </div>
        <button type="button" onclick="window.print()" class="btn-print no-print" title="Cetak Laporan atau Simpan PDF">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            <span>Cetak Laporan</span>
        </button>
    </div>
    
    <!-- Filter Form -->
    <div class="card no-print" style="margin-bottom: 1.75rem;">
        <form method="GET" action="<?= BASE_URL ?>/laporan/laba_rugi" class="form-row">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="bulan" class="form-label">Bulan:</label>
                <select name="bulan" id="bulan" class="form-control" style="width: auto;">
                    <option value="">-- Setahun Penuh --</option>
                    <?php foreach($nama_bulan as $num => $name): ?>
                        <option value="<?= $num ?>" <?= ($data['bulan'] == $num) ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label for="tahun" class="form-label">Tahun:</label>
                <select name="tahun" id="tahun" class="form-control" style="width: auto;" required>
                    <option value="">-- Pilih Tahun --</option>
                    <?php 
                    $start = 2020;
                    $end = date('Y') + 2;
                    for($y = $start; $y <= $end; $y++): ?>
                        <option value="<?= $y ?>" <?= ($data['tahun'] == $y) ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Tampilkan Laporan</button>
        </form>
    </div>

    <!-- Interactive Financial Health Card -->
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
                        <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main);">Evaluasi Profitabilitas: <?= htmlspecialchars($h['status']) ?></h3>
                        <span class="badge" style="background: <?= $health_bg ?>; color: <?= $health_text ?>; border: 1px solid <?= $health_border ?>; font-size: 0.72rem; padding: 3px 9px; font-weight: 700; border-radius: 6px;">Skor <?= $h['score'] ?>/100</span>
                    </div>
                    <p style="margin: 3px 0 0 0; font-size: 0.84rem; color: var(--text-muted);"><?= htmlspecialchars($h['desc']) ?></p>
                </div>
            </div>
            
            <div>
                <a href="<?= BASE_URL ?>/analisis/laba_rugi?tahun=<?= $data['tahun'] ?>&bulan=<?= $data['bulan'] ?>" class="btn btn-gemini">
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
                <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Net Profit Margin (NPM)</div>
                <div style="font-size: 1.15rem; font-weight: 800; color: <?= $r['npm'] >= 15 ? 'var(--fin-positive-color)' : ($r['npm'] > 0 ? 'var(--fin-positive-color)' : 'var(--fin-negative-color)') ?>; margin-top: 2px;">
                    <?= number_format($r['npm'], 2) ?>%
                </div>
                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">Laba Bersih / Pendapatan</div>
            </div>
            <div style="background: var(--bg-main); padding: 0.75rem 0.95rem; border-radius: 10px; border: 1px solid var(--border-color);">
                <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Operating Expense Ratio</div>
                <div style="font-size: 1.15rem; font-weight: 800; color: <?= $r['oer'] <= 70 ? 'var(--fin-positive-color)' : 'var(--fin-warning-color)' ?>; margin-top: 2px;">
                    <?= number_format($r['oer'], 2) ?>%
                </div>
                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">Beban / Pendapatan</div>
            </div>
            <div style="background: var(--bg-main); padding: 0.75rem 0.95rem; border-radius: 10px; border: 1px solid var(--border-color);">
                <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Return on Assets (ROA)</div>
                <div style="font-size: 1.15rem; font-weight: 800; color: <?= $r['roa'] >= 5 ? 'var(--fin-positive-color)' : 'var(--fin-warning-color)' ?>; margin-top: 2px;">
                    <?= number_format($r['roa'], 2) ?>%
                </div>
                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">Laba Bersih / Total Aset</div>
            </div>
            <div style="background: var(--bg-main); padding: 0.75rem 0.95rem; border-radius: 10px; border: 1px solid var(--border-color);">
                <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Total Laba Bersih</div>
                <div style="font-size: 1.1rem; font-weight: 800; color: <?= $s['laba_bersih'] >= 0 ? 'var(--fin-positive-color)' : 'var(--fin-negative-color)' ?>; margin-top: 2px;">
                    Rp <?= number_format($s['laba_bersih'], 0, ',', '.') ?>
                </div>
                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">Net Income Periode Ini</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php 
        if ($data['bulan'] !== '') {
            $periode_str = "Periode Bulan " . $nama_bulan[$data['bulan']] . " " . $data['tahun'];
        } else {
            $periode_str = "Periode Tahun " . $data['tahun'];
        }

        $pendapatan = [];
        $beban = [];

        foreach ($data['laba_rugi_data'] as $row) {
            $kode = $row['kode_akun'];
            $nama = $row['akun'];
            $nilai = floatval($row['total_nilai']);
            
            if (substr($kode, 0, 1) === '4') {
                $pendapatan[] = ['kode' => $kode, 'nama' => $nama, 'nilai' => $nilai];
            } else if (substr($kode, 0, 1) === '5') {
                $beban[] = ['kode' => $kode, 'nama' => $nama, 'nilai' => $nilai];
            }
        }
    ?>
    
    <!-- Formal Print Header (A4 Accounting Standard) -->
    <div class="print-only formal-print-header" style="text-align: center; margin-bottom: 20px;">
        <h1 style="font-family: 'Times New Roman', Times, serif; font-size: 16pt; font-weight: bold; margin: 0; text-transform: uppercase;"><?= htmlspecialchars($_SESSION['nama_perusahaan'] ?? 'USAHA') ?></h1>
        <p style="font-family: 'Times New Roman', Times, serif; font-size: 9pt; margin: 3px 0 0 0; color: #333;">Laporan Keuangan Resmi SAK EMKM | <?= htmlspecialchars($_SESSION['nama_perusahaan'] ?? 'USAHA') ?></p>
        <div style="border-bottom: 2px solid #000; border-top: 1px solid #000; height: 3px; margin: 8px 0 14px 0;"></div>
        <h2 style="font-family: 'Times New Roman', Times, serif; font-size: 13pt; font-weight: bold; margin: 0; text-transform: uppercase;">LAPORAN LABA RUGI (INCOME STATEMENT)</h2>
        <div style="font-family: 'Times New Roman', Times, serif; font-size: 10pt; font-style: italic; margin-top: 4px;"><?= $periode_str ?> (Mata Uang: IDR / Rupiah)</div>
    </div>

    <!-- PENDAPATAN TABLE -->
    <div class="table-container" style="margin-bottom: 1.75rem; overflow: hidden;">
    <table class="table">
        <thead>
            <tr>
                <td colspan="3" class="text-center section-banner-revenue">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--fin-revenue-accent);"></span>
                        <span>PENDAPATAN (REVENUE)</span>
                    </div>
                </td>
            </tr>
            <tr>
                <th style="width: 15%" class="text-center">No. Akun</th>
                <th>Nama Akun</th>
                <th style="width: 25%" class="text-right">Jumlah Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $tot_pendapatan = 0;
            if (count($pendapatan) > 0):
                foreach ($pendapatan as $pend):
                    $tot_pendapatan += $pend['nilai'];
            ?>
                <tr>
                    <td class="text-center"><?= htmlspecialchars($pend['kode']) ?></td>
                    <td><?= htmlspecialchars($pend['nama']) ?></td>
                    <td class="text-right"><?= number_format($pend['nilai'], 0, ',', '.') ?></td>
                </tr>
            <?php 
                endforeach;
            else: 
            ?>
                <tr>
                    <td colspan="3" class="text-center" style="padding: 15px; color: var(--text-muted); font-style: italic;">Tidak ada pendapatan pada periode ini.</td>
                </tr>
            <?php endif; ?>
            
            <!-- TOTAL PENDAPATAN -->
            <tr class="font-bold" style="background-color: var(--table-header-bg);">
                <td colspan="2" class="text-center">TOTAL PENDAPATAN</td>
                <td class="text-right"><?= number_format($tot_pendapatan, 0, ',', '.') ?></td>
            </tr>
        </tbody>
    </table>
    </div>

    <!-- BEBAN TABLE -->
    <div class="table-container" style="margin-bottom: 1.75rem; overflow: hidden;">
    <table class="table">
        <thead>
            <tr>
                <td colspan="3" class="text-center section-banner-expense">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--fin-expense-accent);"></span>
                        <span>BEBAN OPERASIONAL (EXPENSES)</span>
                    </div>
                </td>
            </tr>
            <tr>
                <th style="width: 15%" class="text-center">No. Akun</th>
                <th>Nama Akun</th>
                <th style="width: 25%" class="text-right">Jumlah Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $tot_beban = 0;
            if (count($beban) > 0):
                foreach ($beban as $beb):
                    $tot_beban += $beb['nilai'];
            ?>
                <tr>
                    <td class="text-center"><?= htmlspecialchars($beb['kode']) ?></td>
                    <td><?= htmlspecialchars($beb['nama']) ?></td>
                    <td class="text-right"><?= number_format($beb['nilai'], 0, ',', '.') ?></td>
                </tr>
            <?php 
                endforeach;
            else: 
            ?>
                <tr>
                    <td colspan="3" class="text-center" style="padding: 15px; color: var(--text-muted); font-style: italic;">Tidak ada beban pada periode ini.</td>
                </tr>
            <?php endif; ?>
            
            <!-- TOTAL BEBAN -->
            <tr class="font-bold" style="background-color: var(--table-header-bg);">
                <td colspan="2" class="text-center">TOTAL BEBAN</td>
                <td class="text-right"><?= number_format($tot_beban, 0, ',', '.') ?></td>
            </tr>
        </tbody>
    </table>
    </div>

    <!-- RINGKASAN NET INCOME -->
    <?php 
        $net_income = $tot_pendapatan - $tot_beban;
        $is_profit = $net_income >= 0;
        $summary_bg = $is_profit ? 'var(--fin-revenue-bg)' : 'var(--fin-expense-bg)';
        $summary_color = $is_profit ? 'var(--fin-revenue-text)' : 'var(--fin-expense-text)';
        $summary_accent = $is_profit ? 'var(--fin-revenue-accent)' : 'var(--fin-expense-accent)';
    ?>
    <div class="table-container" style="width: 60%; margin: 0 0 0 auto; border: 2px solid <?= $summary_accent ?>; border-radius: 10px; overflow: hidden; box-shadow: var(--shadow-sm);">
        <table class="table" style="margin-bottom: 0;">
            <tbody>
                <tr class="font-bold" style="background-color: <?= $summary_bg ?>; color: <?= $summary_color ?>; font-size: 1.05rem;">
                    <td class="text-left" style="width: 50%; padding: 14px 18px; border: none;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: <?= $summary_accent ?>;"></span>
                            <span><?= $is_profit ? 'LABA BERSIH (NET PROFIT)' : 'RUGI BERSIH (NET LOSS)' ?></span>
                        </div>
                    </td>
                    <td class="text-right" style="width: 50%; padding: 14px 18px; border: none; font-size: 1.15rem; font-weight: 800;">
                        Rp <?= number_format(abs($net_income), 0, ',', '.') ?>
                    </td>
                </tr>
            </tbody>
        </table>
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
