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
            @page { size: A4 landscape; margin: 1cm; }
            body { 
                font-family: "Times New Roman", Times, serif !important; 
                background: #fff !important; 
                color: #000 !important; 
                font-size: 10pt !important;
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

            .table {
                width: 100% !important;
                border: 2px solid #000 !important;
                border-collapse: collapse !important;
                margin-bottom: 20px !important;
            }
            .table tr {
                page-break-inside: avoid !important;
            }
            .table thead {
                display: table-header-group !important;
            }
            .table-container {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin-bottom: 15px !important;
            }
            
            .table th, .table td {
                border: 1px solid #000 !important;
                padding: 5px 6px !important;
                color: #000 !important;
                font-size: 9.5pt !important;
            }
            
            .table thead td, .table thead th {
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
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var dropdowns = document.querySelectorAll(".dropdown-check-list");
        dropdowns.forEach(function(dd) {
            var anchor = dd.querySelector(".anchor");
            if (anchor) {
                anchor.addEventListener("click", function(e) {
                    e.stopPropagation();
                    dd.classList.toggle("visible");
                });
            }
            var items = dd.querySelector(".items");
            if (items) {
                items.addEventListener("click", function(e) {
                    e.stopPropagation(); // Biarkan dropdown tetap terbuka saat memilih akun
                });
            }
        });

        document.addEventListener("click", function() {
            dropdowns.forEach(function(dd) {
                dd.classList.remove("visible");
            });
        });

        // Select All Logic
        var selectAll = document.getElementById('select-all-akun');
        var checkboxes = document.querySelectorAll('input[name="akun[]"]');
        
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(function(cb) {
                    cb.checked = selectAll.checked;
                });
                updateAnchor();
            });
        }

        var anchor = document.querySelector('#list1 .anchor');
        
        function updateAnchor() {
            if (!anchor) return;
            var checkedCount = 0;
            for (var i = 0; i < checkboxes.length; i++) {
                if (checkboxes[i].checked) {
                    checkedCount++;
                }
            }
            if (checkedCount > 0) {
                anchor.innerHTML = "<span>" + checkedCount + " Akun Terpilih</span>";
            } else {
                anchor.innerHTML = "<span>Pilih satu atau lebih akun...</span>";
            }
            
            if (selectAll) {
                selectAll.checked = (checkedCount === checkboxes.length && checkboxes.length > 0);
                selectAll.indeterminate = (checkedCount > 0 && checkedCount < checkboxes.length);
            }
        }
        
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].addEventListener('change', updateAnchor);
        }
        updateAnchor();
    });
    </script>
</head>
<body>
<?php include __DIR__ . '/../layout/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layout/header.php'; ?>
    
    <div class="container">
    <div class="report-header-row">
        <div>
            <h2 style="border: none; margin: 0; padding: 0;" class="page-title">Buku Besar (General Ledger)</h2>
        </div>
        <button type="button" onclick="window.print()" class="btn-print no-print" title="Cetak Laporan atau Simpan PDF">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            <span>Cetak Laporan</span>
        </button>
    </div>
    
    <!-- Filter Card -->
    <div class="card no-print" style="position: relative; z-index: 50; margin-bottom: 2rem;">
        <form method="GET" action="<?= BASE_URL ?>/laporan/buku_besar" class="form-row">
            <div class="form-group" style="margin-bottom: 0; flex: 1.5; min-width: 280px;">
                <label class="form-label">Pilih Akun:</label>
                <div id="list1" class="dropdown-check-list" tabindex="100">
                    <span class="anchor">Pilih satu atau lebih akun...</span>
                    <div class="items">
                        <div class="select-all-container">
                            <label>
                                <input type="checkbox" id="select-all-akun" /> 
                                <span><strong>Pilih Semua Akun</strong></span>
                            </label>
                        </div>
                        <?php foreach($data['semua_akun'] as $kode => $nama): ?>
                            <label>
                                <input type="checkbox" name="akun[]" value="<?= $kode ?>" <?= in_array($kode, $data['akun_pilihan']) ? 'checked' : '' ?> /> 
                                <span><strong><?= $kode ?></strong> — <?= htmlspecialchars($nama) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
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
            
            <button type="submit" class="btn btn-primary">Tampilkan Buku Besar</button>
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
        <h2 style="font-family: 'Times New Roman', Times, serif; font-size: 13pt; font-weight: bold; margin: 0; text-transform: uppercase;">LAPORAN BUKU BESAR (GENERAL LEDGER)</h2>
        <div style="font-family: 'Times New Roman', Times, serif; font-size: 10pt; font-style: italic; margin-top: 4px;">Periode: <?= $periode_str ?> (Mata Uang: IDR / Rupiah)</div>
    </div>

    <!-- HASIL BUKU BESAR -->
    <?php if ($data['is_submitted'] && !empty($data['akun_pilihan'])): ?>
        <?php 
        if (empty($data['transactions']) && empty($data['saldo_awals'])) {
            echo '<div class="alert alert-info">Tidak ada data transaksi atau saldo awal untuk akun terpilih pada periode ini.</div>';
        } else {
            foreach ($data['akun_pilihan'] as $kode_akun) {
                $nama_akun = $data['semua_akun'][$kode_akun] ?? 'Akun Tidak Dikenal';
                $trx_akun = $data['transactions'][$kode_akun] ?? [];
                $saldo_awal = $data['saldo_awals'][$kode_akun] ?? 0;
                
                $first_digit = substr($kode_akun, 0, 1);
                $is_normal_debit = in_array($first_digit, ['1', '5']) && !in_array($kode_akun, ['106']);

                if (empty($trx_akun) && $saldo_awal == 0) {
                    continue;
                }

                $saldo_berjalan = $saldo_awal;
                $saldo_awal_d_str = ($saldo_awal > 0 && $is_normal_debit) || ($saldo_awal < 0 && !$is_normal_debit) ? number_format(abs($saldo_awal), 0, ',', '.') : '-';
                $saldo_awal_k_str = ($saldo_awal > 0 && !$is_normal_debit) || ($saldo_awal < 0 && $is_normal_debit) ? number_format(abs($saldo_awal), 0, ',', '.') : '-';
        ?>
        <div class="table-container" style="margin-bottom: 2rem;">
        <table class="table">
            <thead>
                <tr>
                    <td colspan="7" style="font-size: 1.05rem; font-weight: 700; background-color: var(--table-header-bg); padding: 10px 14px;">
                        <span style="color: var(--primary-color);"><?= htmlspecialchars($kode_akun) ?></span> — <?= htmlspecialchars($nama_akun) ?>
                    </td>
                </tr>
                <tr>
                    <th rowspan="2" style="width: 12%; text-align: center; vertical-align: middle;">Tanggal</th>
                    <th rowspan="2" style="width: 15%; text-align: center; vertical-align: middle;">No. Transaksi</th>
                    <th rowspan="2" style="vertical-align: middle;">Keterangan</th>
                    <th rowspan="2" style="width: 13%; text-align: right; vertical-align: middle;">Debit (Rp)</th>
                    <th rowspan="2" style="width: 13%; text-align: right; vertical-align: middle;">Kredit (Rp)</th>
                    <th colspan="2" style="text-align: center; border-bottom: none;">Saldo (Rp)</th>
                </tr>
                <tr>
                    <th style="width: 13%; text-align: right;">Debit</th>
                    <th style="width: 13%; text-align: right;">Kredit</th>
                </tr>
            </thead>
            <tbody>
                <!-- SALDO AWAL -->
                <tr style="font-style: italic; background-color: rgba(0,0,0,0.01);">
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td><strong>Saldo Awal Periode</strong></td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right font-bold"><?= $saldo_awal_d_str ?></td>
                    <td class="text-right font-bold"><?= $saldo_awal_k_str ?></td>
                </tr>
                <?php 
                $total_debit = 0;
                $total_kredit = 0;
                $s_d = $saldo_awal_d_str;
                $s_k = $saldo_awal_k_str;
                foreach ($trx_akun as $row):
                    $debit_val = 0;
                    $kredit_val = 0;
                    if (strtolower($row['dk']) == 'debit') {
                        $debit_val = $row['nilai'];
                        $saldo_berjalan += ($is_normal_debit ? $debit_val : -$debit_val);
                    } else {
                        $kredit_val = $row['nilai'];
                        $saldo_berjalan += ($is_normal_debit ? -$kredit_val : $kredit_val);
                    }
                    
                    $total_debit += $debit_val;
                    $total_kredit += $kredit_val;

                    $s_d = ($saldo_berjalan > 0 && $is_normal_debit) || ($saldo_berjalan < 0 && !$is_normal_debit) ? number_format(abs($saldo_berjalan), 0, ',', '.') : '-';
                    $s_k = ($saldo_berjalan > 0 && !$is_normal_debit) || ($saldo_berjalan < 0 && $is_normal_debit) ? number_format(abs($saldo_berjalan), 0, ',', '.') : '-';
                ?>
                <tr>
                    <td class="text-center"><?= date('d-M-Y', strtotime($row['tanggal'])) ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['kode_transaksi']) ?></td>
                    <td><?= htmlspecialchars($row['deskripsi']) ?></td>
                    <td class="text-right"><?= $debit_val > 0 ? number_format($debit_val, 0, ',', '.') : '-' ?></td>
                    <td class="text-right"><?= $kredit_val > 0 ? number_format($kredit_val, 0, ',', '.') : '-' ?></td>
                    <td class="text-right"><?= $s_d ?></td>
                    <td class="text-right"><?= $s_k ?></td>
                </tr>
                <?php endforeach; ?>
                <tr style="font-weight: 600; background-color: var(--table-row-even);">
                    <td colspan="3" class="text-center">TOTAL MUTASI PERIODE</td>
                    <td class="text-right"><?= number_format($total_debit, 0, ',', '.') ?></td>
                    <td class="text-right"><?= number_format($total_kredit, 0, ',', '.') ?></td>
                    <td class="text-right" colspan="2" style="background-color: var(--bg-surface);"></td>
                </tr>
                <tr style="font-weight: bold; background-color: var(--table-header-bg);">
                    <td colspan="5" class="text-center">SALDO AKHIR BUKU BESAR</td>
                    <td class="text-right"><?= $s_d ?></td>
                    <td class="text-right"><?= $s_k ?></td>
                </tr>
            </tbody>
        </table>
        </div>
        <?php
            } // end foreach
        } // end if empty
        ?>

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
    <?php endif; ?>

</div>
</div>
</body>
</html>
