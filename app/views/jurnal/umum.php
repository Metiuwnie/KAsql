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
                font-size: 10pt !important;
            }
            .no-print, nav, .navbar, .page-title, form, .form-row, .header-info-card, .sidebar, .badge { display: none !important; }
            
            .container { 
                width: 100% !important; 
                max-width: none !important; 
                margin: 0 !important; 
                padding: 0 !important; 
                box-shadow: none !important;
                border: none !important;
            }
            
            div[style*="border-bottom: 2px solid var(--border-color)"] { display: none !important; }

            .print-only { display: block !important; margin-bottom: 15px !important; }
            
            .table-container {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin-bottom: 15px !important;
            }

            .table {
                width: 100% !important;
                border: 2px solid #000 !important;
                border-collapse: collapse !important;
                margin-bottom: 15px !important;
            }
            
            .table tr {
                page-break-inside: avoid !important;
            }

            .table thead {
                display: table-header-group !important;
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

            .font-bold, .table tr.font-bold td {
                background: #f1f5f9 !important;
                color: #000 !important;
                font-weight: bold !important;
            }

            .web-table-header { display: none !important; }
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
            <h2 style="border: none; margin: 0; padding: 0;" class="page-title">Jurnal Umum <?= htmlspecialchars($_SESSION['nama_perusahaan'] ?? 'USAHA') ?></h2>
        </div>
        <button type="button" onclick="window.print()" class="btn-print no-print" title="Cetak Laporan atau Simpan PDF">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            <span>Cetak Laporan</span>
        </button>
    </div>
    
    <div class="card no-print">
        <form method="GET" action="<?= BASE_URL ?>/jurnal/umum" class="form-row">
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
            
            <button type="submit" class="btn btn-primary">Tampilkan Jurnal</button>
        </form>
    </div>

    <?php 
    $total_debit = 0;
    $total_kredit = 0;
    
    if (count($data['jurnal_list']) > 0): 
        if ($data['bulan'] !== '') {
            $periode_str = $nama_bulan[$data['bulan']] . " " . $data['tahun'];
        } else {
            $periode_str = "Tahun " . $data['tahun'];
        }
    ?>

    <!-- Formal Print Header (A4 Accounting Standard) -->
    <div class="print-only formal-print-header" style="text-align: center; margin-bottom: 20px;">
        <h1 style="font-family: 'Times New Roman', Times, serif; font-size: 16pt; font-weight: bold; margin: 0; text-transform: uppercase;"><?= htmlspecialchars($_SESSION['nama_perusahaan'] ?? 'USAHA') ?></h1>
        <p style="font-family: 'Times New Roman', Times, serif; font-size: 9pt; margin: 3px 0 0 0; color: #333;">Laporan Keuangan Resmi SAK EMKM | <?= htmlspecialchars($_SESSION['nama_perusahaan'] ?? 'USAHA') ?></p>
        <div style="border-bottom: 2px solid #000; border-top: 1px solid #000; height: 3px; margin: 8px 0 14px 0;"></div>
        <h2 style="font-family: 'Times New Roman', Times, serif; font-size: 13pt; font-weight: bold; margin: 0; text-transform: uppercase;">LAPORAN JURNAL UMUM (GENERAL JOURNAL)</h2>
        <div style="font-family: 'Times New Roman', Times, serif; font-size: 10pt; font-style: italic; margin-top: 4px;">Periode: <?= $periode_str ?> (Mata Uang: IDR / Rupiah)</div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr class="web-table-header no-print">
                    <td colspan="6" class="text-center" style="font-size: 1.1rem; font-weight: 600; padding: 1rem; border-bottom: none;">
                         Jurnal Umum <?= htmlspecialchars($_SESSION['nama_perusahaan'] ?? 'USAHA') ?><br>
                        <span style="font-size: 0.9rem; font-weight: normal; color: var(--text-muted);">Periode: <?= $periode_str ?></span>
                    </td>
                </tr>
                <tr>
                    <th style="width: 12%">Tanggal</th>
                    <th style="width: 15%">Nomor Transaksi</th>
                    <th style="width: 11%">Nomor Akun</th>
                    <th style="width: 28%">Akun</th>
                    <th style="width: 15%" class="text-right">Debit</th>
                    <th style="width: 15%" class="text-right">Kredit</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $last_nomor = '';
                foreach ($data['jurnal_list'] as $row): 
                    $debit_val = $row['Debit'];
                    $kredit_val = $row['Kredit'];
                    
                    $total_debit += $debit_val;
                    $total_kredit += $kredit_val;

                    $debit_str = $debit_val > 0 ? number_format($debit_val, 0, ',', '.') : '-';
                    $kredit_str = $kredit_val > 0 ? number_format($kredit_val, 0, ',', '.') : '-';

                    $tgl = date('d-M-Y', strtotime($row['tanggal']));
                    $nomor = htmlspecialchars($row['kode_transaksi'] ?? '');
                    $status = htmlspecialchars($row['status_verifikasi'] ?? 'pending');
                    
                    $show_nomor = ($nomor !== $last_nomor);
                    $last_nomor = $nomor;

                    $akun_nama = htmlspecialchars($row['akun'] ?? $row['kode_akun'] ?? '');
                    
                    if ($kredit_val > 0) {
                        $keterangan = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $akun_nama;
                    } else {
                        $keterangan = $akun_nama;
                    }
                    
                    if ($show_nomor) {
                        $keterangan .= ' <br><small style="color:var(--text-muted);">(' . htmlspecialchars($row['deskripsi'] ?? '') . ')</small>';
                    }
                ?>
                <tr>
                    <td class="text-center"><?= $show_nomor ? $tgl : '' ?></td>
                    <td class="text-center">
                        <?php if ($show_nomor):
                            $jenis_j   = $row['jenis_jurnal'] ?? 'umum';
                            $sub_j     = $row['sub_jenis'] ?? '';
                            if ($sub_j === 'jurnal_pembalik') {
                                $type_badge = '<span style="font-size:.6rem;padding:1px 6px;border-radius:10px;background:rgba(79,70,229,.12);color:#4338ca;border:1px solid rgba(79,70,229,.3);white-space:nowrap;">&#x21BA; Pembalik</span>';
                            } elseif ($jenis_j === 'penyesuaian') {
                                $type_badge = '<span style="font-size:.6rem;padding:1px 6px;border-radius:10px;background:rgba(234,179,8,.12);color:#b45309;border:1px solid rgba(234,179,8,.3);white-space:nowrap;">&#9998; Penyesuaian</span>';
                            } else {
                                $type_badge = '';
                            }
                        ?>
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                                <span style="font-weight: 600;"><?= $nomor ?></span>
                                <span class="badge badge-<?= $status ?>" style="font-size: 0.65rem; padding: 0.1rem 0.4rem;"><?= ucfirst($status) ?></span>
                                <?= $type_badge ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?= htmlspecialchars($row['kode_akun'] ?? '') ?></td>
                    <td class="text-left" style="line-height: 1.4;"><?= $keterangan ?></td>
                    <td class="text-right"><?= $debit_str ?></td>
                    <td class="text-right"><?= $kredit_str ?></td>
                </tr>
                <?php endforeach; ?>
            <tr class="font-bold">
                <td colspan="4" class="text-center" style="background-color: var(--table-header-bg);">TOTAL</td>
                <td class="text-right" style="background-color: var(--table-header-bg);"><?= number_format($total_debit, 0, ',', '.') ?></td>
                <td class="text-right" style="background-color: var(--table-header-bg);"><?= number_format($total_kredit, 0, ',', '.') ?></td>
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

    <?php else: ?>
    <div class="card" style="text-align: center; color: var(--text-muted);">
        Tidak ada transaksi untuk periode ini.
    </div>
    <?php endif; ?>

</div>
</div>
</body>
</html>


