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
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data['title']) ?> — <?= htmlspecialchars($_SESSION['nama_perusahaan'] ?? 'USAHA') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css?v=<?= time() ?>">
    <script src="<?= BASE_URL ?>/public/js/script.js?v=2.0" defer></script>
    <style>
        /* Print Styles for Formal Accounting Report */
        @media screen {
            .print-only { display: none !important; }
        }
        @media print {
            @page { size: A4 landscape; margin: 1.2cm; }
            body { 
                font-family: "Times New Roman", Times, serif !important; 
                background: #fff !important; 
                color: #000 !important; 
                font-size: 10pt !important;
            }
            .no-print, nav, .navbar, .page-title, form, .form-row, .header-info-card, .sidebar, .alert { display: none !important; }
            
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
                padding: 5px 6px !important;
                color: #000 !important;
                font-size: 9.5pt !important;
            }
            
            .table thead th {
                background: #e2e8f0 !important;
                color: #000 !important;
                font-weight: bold !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .font-bold {
                background: #f1f5f9 !important;
                color: #000 !important;
                font-weight: bold !important;
            }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../layout/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layout/header.php'; ?>
    
    <div class="container">
    <div class="report-header-row">
        <div>
            <h2 style="border: none; margin: 0; padding: 0;" class="page-title">Neraca Saldo (Trial Balance)</h2>
        </div>
        <button type="button" onclick="window.print()" class="btn-print no-print" title="Cetak Laporan atau Simpan PDF">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            <span>Cetak Laporan</span>
        </button>
    </div>
    
    <!-- Filter Form -->
    <div class="card no-print" style="margin-bottom: 1.75rem;">
        <form method="GET" action="<?= BASE_URL ?>/laporan/neraca_saldo" class="form-row">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="bulan" class="form-label">Bulan:</label>
                <select name="bulan" id="bulan" class="form-control" style="width: auto;">
                    <option value="">-- Semua Bulan --</option>
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
            
            <button type="submit" class="btn btn-primary">Tampilkan Neraca Saldo</button>
        </form>
    </div>

    <?php 
        if (!empty($data['bulan'])) {
            $last_day = date("t", strtotime($data['tahun'] . "-" . $data['bulan'] . "-01"));
            $periode_str = "01 " . $nama_bulan[$data['bulan']] . " " . $data['tahun'] . " s/d " . $last_day . " " . $nama_bulan[$data['bulan']] . " " . $data['tahun'];
        } else {
            $periode_str = "Tahun " . $data['tahun'];
        }
    ?>

    <!-- Formal Print Header (A4 Accounting Standard) -->
    <div class="print-only formal-print-header" style="text-align: center; margin-bottom: 20px;">
        <h1 style="font-family: 'Times New Roman', Times, serif; font-size: 16pt; font-weight: bold; margin: 0; text-transform: uppercase;"><?= htmlspecialchars($_SESSION['nama_perusahaan'] ?? 'USAHA') ?></h1>
        <p style="font-family: 'Times New Roman', Times, serif; font-size: 9pt; margin: 3px 0 0 0; color: #333;">Laporan Keuangan Resmi SAK EMKM | <?= htmlspecialchars($_SESSION['nama_perusahaan'] ?? 'USAHA') ?></p>
        <div style="border-bottom: 2px solid #000; border-top: 1px solid #000; height: 3px; margin: 8px 0 14px 0;"></div>
        <h2 style="font-family: 'Times New Roman', Times, serif; font-size: 13pt; font-weight: bold; margin: 0; text-transform: uppercase;">LAPORAN NERACA SALDO (TRIAL BALANCE)</h2>
        <div style="font-family: 'Times New Roman', Times, serif; font-size: 10pt; font-style: italic; margin-top: 4px;">Periode: <?= $periode_str ?> (Mata Uang: IDR / Rupiah)</div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th rowspan="2" style="vertical-align: middle; width: 10%; text-align: center;">No Akun</th>
                    <th rowspan="2" style="vertical-align: middle; width: 28%;">Nama Akun</th>
                    <th colspan="2" class="text-center" style="border-bottom: none;">Saldo Awal (Rp)</th>
                    <th colspan="2" class="text-center" style="border-bottom: none;">Mutasi Periode Ini (Rp)</th>
                    <th colspan="2" class="text-center" style="border-bottom: none;">Saldo Akhir (Rp)</th>
                </tr>
                <tr>
                    <th class="text-right" style="width: 10%;">Debit</th>
                    <th class="text-right" style="width: 10%;">Kredit</th>
                    <th class="text-right" style="width: 11%;">Debit</th>
                    <th class="text-right" style="width: 11%;">Kredit</th>
                    <th class="text-right" style="width: 10%;">Debit</th>
                    <th class="text-right" style="width: 10%;">Kredit</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_sa_debit = 0; $total_sa_kredit = 0;
                $total_mutasi_debit = 0; $total_mutasi_kredit = 0;
                $total_akhir_debit = 0; $total_akhir_kredit = 0;

                foreach ($data['mutasi'] as $row): 
                    $kode_akun = $row['kode_akun'];
                    $akun = $row['akun'];
                    $kategori = $row['kategori_neraca'];

                    $first_digit = substr($kode_akun, 0, 1);
                    $is_normal_debit = in_array($first_digit, ['1', '5']) && !in_array($kode_akun, ['106']);

                    // Saldo Awal
                    $sa = $data['saldo_awals'][$kode_akun] ?? 0;
                    $sa_debit = 0; $sa_kredit = 0;
                    if ($is_normal_debit) {
                        if ($sa >= 0) $sa_debit = $sa; else $sa_kredit = abs($sa);
                    } else {
                        if ($sa >= 0) $sa_kredit = $sa; else $sa_debit = abs($sa);
                    }

                    // Mutasi
                    $mutasi_debit = floatval($row['mutasi_debet']);
                    $mutasi_kredit = floatval($row['mutasi_kredit']);

                    // Saldo Akhir
                    $saldo_akhir = $sa;
                    if ($is_normal_debit) {
                        $saldo_akhir += ($mutasi_debit - $mutasi_kredit);
                    } else {
                        $saldo_akhir += ($mutasi_kredit - $mutasi_debit);
                    }

                    $akhir_debit = 0; $akhir_kredit = 0;
                    if ($is_normal_debit) {
                        if ($saldo_akhir >= 0) $akhir_debit = $saldo_akhir; else $akhir_kredit = abs($saldo_akhir);
                    } else {
                        if ($saldo_akhir >= 0) $akhir_kredit = $saldo_akhir; else $akhir_debit = abs($saldo_akhir);
                    }

                    if ($sa == 0 && $mutasi_debit == 0 && $mutasi_kredit == 0 && $saldo_akhir == 0) {
                        continue; 
                    }

                    $total_sa_debit += $sa_debit;
                    $total_sa_kredit += $sa_kredit;
                    $total_mutasi_debit += $mutasi_debit;
                    $total_mutasi_kredit += $mutasi_kredit;
                    $total_akhir_debit += $akhir_debit;
                    $total_akhir_kredit += $akhir_kredit;
                ?>
                <tr>
                    <td class="text-center"><?= htmlspecialchars($kode_akun) ?></td>
                    <td><?= htmlspecialchars($akun) ?></td>
                    
                    <td class="text-right"><?= $sa_debit > 0 ? number_format($sa_debit, 0, ',', '.') : '-' ?></td>
                    <td class="text-right"><?= $sa_kredit > 0 ? number_format($sa_kredit, 0, ',', '.') : '-' ?></td>
                    
                    <td class="text-right"><?= $mutasi_debit > 0 ? number_format($mutasi_debit, 0, ',', '.') : '-' ?></td>
                    <td class="text-right"><?= $mutasi_kredit > 0 ? number_format($mutasi_kredit, 0, ',', '.') : '-' ?></td>
                    
                    <td class="text-right"><?= $akhir_debit > 0 ? number_format($akhir_debit, 0, ',', '.') : '-' ?></td>
                    <td class="text-right"><?= $akhir_kredit > 0 ? number_format($akhir_kredit, 0, ',', '.') : '-' ?></td>
                </tr>
                <?php endforeach; ?>

                <!-- Baris TOTAL -->
                <tr style="font-weight: bold; background-color: var(--table-header-bg);">
                    <td colspan="2" class="text-center">TOTAL KESEIMBANGAN</td>
                    <td class="text-right"><?= number_format($total_sa_debit, 0, ',', '.') ?></td>
                    <td class="text-right"><?= number_format($total_sa_kredit, 0, ',', '.') ?></td>
                    <td class="text-right"><?= number_format($total_mutasi_debit, 0, ',', '.') ?></td>
                    <td class="text-right"><?= number_format($total_mutasi_kredit, 0, ',', '.') ?></td>
                    <td class="text-right" style="color: <?= $total_akhir_debit == $total_akhir_kredit ? 'var(--success-color)' : 'var(--danger-color)' ?>;">
                        <?= number_format($total_akhir_debit, 0, ',', '.') ?>
                    </td>
                    <td class="text-right" style="color: <?= $total_akhir_debit == $total_akhir_kredit ? 'var(--success-color)' : 'var(--danger-color)' ?>;">
                        <?= number_format($total_akhir_kredit, 0, ',', '.') ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <?php if ($total_akhir_debit != $total_akhir_kredit): ?>
        <div class="alert alert-error" style="margin-top: 15px;">
            <strong>Peringatan!</strong> Saldo akhir Debit dan Kredit tidak seimbang (Unbalanced). Periksa kembali entri jurnal pada periode ini.
        </div>
    <?php endif; ?>

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
