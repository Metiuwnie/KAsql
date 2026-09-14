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
    <style>
        .status-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .status-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0.6rem 1.2rem;
            border-radius: var(--radius-md);
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.25s ease;
            border: 1px solid var(--border-color);
            background: var(--bg-surface);
            color: var(--text-muted);
        }
        .status-tab:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .status-tab.active-tab-pending {
            background: #fefce8;
            color: #a16207;
            border-color: #fde047;
            box-shadow: 0 1px 3px rgba(202, 138, 4, 0.12);
        }
        .status-tab.active-tab-sesuai {
            background: #f0fdf4;
            color: #15803d;
            border-color: #86efac;
            box-shadow: 0 1px 3px rgba(16, 185, 129, 0.12);
        }
        .status-tab.active-tab-koreksi {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fca5a5;
            box-shadow: 0 1px 3px rgba(239, 68, 68, 0.12);
        }
        .status-tab.active-tab-semua {
            background: #18181b;
            color: #ffffff;
            border-color: #18181b;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        [data-theme="dark"] .status-tab.active-tab-pending {
            background: rgba(245, 158, 11, 0.15);
            color: #fde047;
            border-color: rgba(250, 204, 21, 0.35);
        }
        [data-theme="dark"] .status-tab.active-tab-sesuai {
            background: rgba(16, 185, 129, 0.15);
            color: #4ade80;
            border-color: rgba(74, 222, 128, 0.35);
        }
        [data-theme="dark"] .status-tab.active-tab-koreksi {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border-color: rgba(248, 113, 113, 0.35);
        }
        [data-theme="dark"] .status-tab.active-tab-semua {
            background: #27272a;
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.2);
        }
        .tab-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 22px;
            height: 22px;
            padding: 0 6px;
            border-radius: 11px;
            font-size: 0.7rem;
            font-weight: 700;
            background: var(--border-color);
            color: var(--text-muted);
        }
        .active-tab-pending .tab-count {
            background: #fef08a;
            color: #854d0e;
        }
        .active-tab-sesuai .tab-count {
            background: #bbf7d0;
            color: #14532d;
        }
        .active-tab-koreksi .tab-count {
            background: #fecaca;
            color: #7f1d1d;
        }
        .active-tab-semua .tab-count {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }
        [data-theme="dark"] .active-tab-pending .tab-count {
            background: rgba(245, 158, 11, 0.25);
            color: #fde047;
        }
        [data-theme="dark"] .active-tab-sesuai .tab-count {
            background: rgba(16, 185, 129, 0.25);
            color: #6ee7b7;
        }
        [data-theme="dark"] .active-tab-koreksi .tab-count {
            background: rgba(239, 68, 68, 0.25);
            color: #fca5a5;
        }
        .detail-row {
            background: var(--bg-surface-hover);
            font-size: 0.85rem;
        }
        .detail-row td {
            padding: 0.4rem 0.75rem !important;
            color: var(--text-muted);
        }
        .koreksi-info {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 3px;
        }
        .expand-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            transition: all 0.2s ease;
            padding: 4px;
            border-radius: 4px;
        }
        .expand-btn:hover {
            background: var(--bg-surface-hover);
            color: var(--primary-color);
        }
        .expand-btn svg {
            transition: transform 0.2s ease;
        }
        .expand-btn.expanded svg {
            transform: rotate(90deg);
        }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/../layout/sidebar.php'; ?>

<div class="main-content">
    <?php include __DIR__ . '/../layout/header.php'; ?>
    
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-color); margin-bottom: 2rem; padding-bottom: 1rem;">
            <h2 style="border: none; margin: 0; padding: 0;" class="page-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px; color: var(--primary-color);">
                    <path d="M9 11l3 3L22 4"></path>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
                Verifikasi Transaksi
            </h2>
        </div>

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

        <!-- Status Filter Tabs -->
        <div class="status-tabs no-print">
            <a href="?status=pending" class="status-tab <?= $data['filter_status'] === 'pending' ? 'active-tab-pending' : '' ?>">
                Pending <span class="tab-count"><?= (int)$data['counts']['cnt_pending'] ?></span>
            </a>
            <a href="?status=sesuai" class="status-tab <?= $data['filter_status'] === 'sesuai' ? 'active-tab-sesuai' : '' ?>">
                Sesuai <span class="tab-count"><?= (int)$data['counts']['cnt_sesuai'] ?></span>
            </a>
            <a href="?status=koreksi" class="status-tab <?= $data['filter_status'] === 'koreksi' ? 'active-tab-koreksi' : '' ?>">
                Koreksi <span class="tab-count"><?= (int)$data['counts']['cnt_koreksi'] ?></span>
            </a>
            <a href="?status=semua" class="status-tab <?= $data['filter_status'] === 'semua' ? 'active-tab-semua' : '' ?>">
                Semua <span class="tab-count"><?= (int)$data['counts']['cnt_semua'] ?></span>
            </a>
        </div>

        <?php if (count($data['transactions']) > 0): ?>
        <div class="table-container">
            <table class="table" id="tabel-pending">
                <thead>
                    <tr>
                        <th style="width: 5%"></th>
                        <th style="width: 10%">Tanggal</th>
                        <th style="width: 10%">Kode</th>
                        <th style="width: 25%">Deskripsi</th>
                        <th style="width: 13%" class="text-right">Total Debit</th>
                        <th style="width: 13%" class="text-right">Total Kredit</th>
                        <th style="width: 10%" class="text-center">Status</th>
                        <th style="width: 14%" class="text-center no-print">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['transactions'] as $idx => $trx): 
                        $tgl = date('d-M-Y', strtotime($trx['tanggal']));
                        $kode = htmlspecialchars($trx['kode_transaksi']);
                        $desc = htmlspecialchars($trx['deskripsi']);
                        $status = $trx['status_verifikasi'];
                        $badge_class = 'badge-' . $status;
                        $status_label = ['pending' => 'Pending', 'sesuai' => 'Sesuai', 'koreksi' => 'Koreksi'];
                        $total_d = number_format($trx['total_debit'], 0, ',', '.');
                        $total_k = number_format($trx['total_kredit'], 0, ',', '.');

                        $hasil_koreksi = $trx['hasil_koreksi'];
                        $asal_koreksi = $trx['asal_koreksi'];
                    ?>
                    <tr>
                        <td class="text-center">
                            <button class="expand-btn" onclick="toggleDetail(this, 'detail-<?= $idx ?>')" title="Lihat detail">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </button>
                        </td>
                        <td class="text-center"><?= $tgl ?></td>
                        <td class="text-center" style="font-weight: 600;"><?= $kode ?></td>
                        <td>
                            <?= $desc ?>
                            <?php if (!empty($trx['creator_name'])): ?>
                                <div class="koreksi-info">oleh: <?= htmlspecialchars($trx['creator_name']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($trx['pengoreksi_id'])): ?>
                                <div class="koreksi-info">📌 <?= htmlspecialchars($trx['pengoreksi_id']) ?></div>
                            <?php endif; ?>
                            <?php if ($status === 'koreksi'): ?>
                                <?php if ($hasil_koreksi): ?>
                                    <div class="koreksi-info" style="color: var(--danger-color); font-weight: 600;">
                                        👉 Koreksi Baru: <strong><?= htmlspecialchars($hasil_koreksi['kode_transaksi']) ?></strong> (Rp <?= number_format($hasil_koreksi['total_debit'], 0, ',', '.') ?>)
                                    </div>
                                <?php endif; ?>
                            <?php elseif (!empty($trx['koreksi_dari_id'])): ?>
                                <?php if ($asal_koreksi): ?>
                                    <div class="koreksi-info" style="color: var(--warning-color); font-weight: 500;">
                                        🔄 Koreksi dari: <strong><?= htmlspecialchars($trx['koreksi_dari_id']) ?></strong> (Asal: Rp <?= number_format($asal_koreksi['total_debit'], 0, ',', '.') ?>)
                                    </div>
                                <?php else: ?>
                                    <div class="koreksi-info">🔄 Koreksi dari: <?= htmlspecialchars($trx['koreksi_dari_id']) ?></div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">Rp <?= $total_d ?></td>
                        <td class="text-right">Rp <?= $total_k ?></td>
                        <td class="text-center">
                            <span class="badge <?= $badge_class ?>"><?= $status_label[$status] ?? $status ?></span>
                        </td>
                        <td class="text-center no-print">
                            <!-- PENDING BUTTONS -->
                            <?php if ($status === 'pending'): ?>
                            <div class="action-btns" style="justify-content: center;">
                                <!-- Posting Button -->
                                <form method="POST" action="<?= BASE_URL ?>/transaksi/pending" style="display:inline;" onsubmit="return confirm('Yakin ingin memposting (menyetujui) transaksi <?= $kode ?>?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="kode_transaksi" value="<?= $kode ?>">
                                    <button type="submit" name="action_posting" value="1" class="btn-posting" title="Setujui transaksi">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        Posting
                                    </button>
                                </form>
                                <!-- Koreksi Button -->
                                <a href="<?= BASE_URL ?>/transaksi/input?koreksi_dari=<?= urlencode($trx['kode_transaksi']) ?>" class="btn-koreksi" title="Koreksi transaksi" onclick="return confirm('Membuka form koreksi untuk transaksi <?= $kode ?>?')">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                    Koreksi
                                </a>
                            </div>
                            <?php else: ?>
                                <span style="color: var(--text-muted); font-size: 0.8rem;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <!-- Detail rows (hidden by default) -->
                    <?php foreach ($trx['details'] as $det): ?>
                    <tr class="detail-row" id="detail-<?= $idx ?>" style="display: none;">
                        <td></td>
                        <td></td>
                        <td class="text-center" style="font-family: monospace;"><?= htmlspecialchars($det['kode_akun']) ?></td>
                        <td style="<?= strtolower($det['dk']) === 'kredit' ? 'padding-left: 2rem;' : '' ?>">
                            <?= strtolower($det['dk']) === 'kredit' ? '&nbsp;&nbsp;&nbsp;' : '' ?><?= htmlspecialchars($det['akun'] ?? $det['kode_akun']) ?>
                        </td>
                        <td class="text-right"><?= strtolower($det['dk']) === 'debit' ? 'Rp ' . number_format($det['nilai'], 0, ',', '.') : '-' ?></td>
                        <td class="text-right"><?= strtolower($det['dk']) === 'kredit' ? 'Rp ' . number_format($det['nilai'], 0, ',', '.') : '-' ?></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="card" style="text-align: center; color: var(--text-muted); padding: 3rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 1rem; opacity: 0.4;">
                <path d="M9 11l3 3L22 4"></path>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
            </svg>
            <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">Tidak ada transaksi dengan status "<?= htmlspecialchars($data['filter_status']) ?>".</p>
            <p style="font-size: 0.9rem;">Semua transaksi telah diproses.</p>
        </div>
        <?php endif; ?>

        <!-- RIWAYAT VERIFIKASI & KOREKSI TRANSAKSI -->
        <div style="margin-top: 3.5rem; margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid var(--border-color); margin-bottom: 1.5rem; padding-bottom: 0.75rem;">
                <h3 style="border: none; margin: 0; padding: 0; font-size: 1.3rem; display: flex; align-items: center; gap: 8px;" class="page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary-color);">
                        <path d="M12 8v4l3 3"></path>
                        <circle cx="12" cy="12" r="10"></circle>
                    </svg>
                    Riwayat Verifikasi & Koreksi Transaksi (20 Terbaru)
                </h3>
            </div>
            
            <?php if (count($data['history_trx']) > 0): ?>
            <div class="table-container">
                <table class="table" style="font-size: 0.9rem;">
                    <thead>
                        <tr>
                            <th style="width: 12%">Tanggal</th>
                            <th style="width: 13%">Kode Transaksi</th>
                            <th style="width: 30%">Deskripsi / Keterangan</th>
                            <th style="width: 15%" class="text-right">Total Debit</th>
                            <th style="width: 15%" class="text-right">Total Kredit</th>
                            <th style="width: 15%" class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['history_trx'] as $h_idx => $htrx): 
                            $h_tgl = date('d-M-Y', strtotime($htrx['tanggal']));
                            $h_kode = htmlspecialchars($htrx['kode_transaksi']);
                            $h_desc = htmlspecialchars($htrx['deskripsi']);
                            $h_status = $htrx['status_verifikasi'];
                            $h_badge_class = 'badge-' . $h_status;
                            $h_status_label = ['sesuai' => 'Sesuai (Posted)', 'koreksi' => 'Koreksi (Void)'];
                            $h_total_d = number_format($htrx['total_debit'], 0, ',', '.');
                            $h_total_k = number_format($htrx['total_kredit'], 0, ',', '.');

                            $h_hasil_koreksi = $htrx['h_hasil_koreksi'];
                            $h_asal_koreksi = $htrx['h_asal_koreksi'];
                        ?>
                        <tr>
                            <td class="text-center"><?= $h_tgl ?></td>
                            <td class="text-center" style="font-weight: 600;"><?= $h_kode ?></td>
                            <td>
                                <?= $h_desc ?>
                                <?php if (!empty($htrx['creator_name'])): ?>
                                    <div class="koreksi-info">oleh: <?= htmlspecialchars($htrx['creator_name']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($htrx['pengoreksi_id'])): ?>
                                    <div class="koreksi-info">📌 <?= htmlspecialchars($htrx['pengoreksi_id']) ?></div>
                                <?php endif; ?>
                                <?php if ($h_status === 'koreksi'): ?>
                                    <?php if ($h_hasil_koreksi): ?>
                                        <div class="koreksi-info" style="color: var(--danger-color); font-weight: 600;">
                                            👉 Koreksi Baru: <strong><?= htmlspecialchars($h_hasil_koreksi['kode_transaksi']) ?></strong> (Rp <?= number_format($h_hasil_koreksi['total_debit'], 0, ',', '.') ?>)
                                        </div>
                                    <?php endif; ?>
                                <?php elseif (!empty($htrx['koreksi_dari_id'])): ?>
                                    <?php if ($h_asal_koreksi): ?>
                                        <div class="koreksi-info" style="color: var(--warning-color); font-weight: 500;">
                                            🔄 Koreksi dari: <strong><?= htmlspecialchars($htrx['koreksi_dari_id']) ?></strong> (Asal: Rp <?= number_format($h_asal_koreksi['total_debit'], 0, ',', '.') ?>)
                                        </div>
                                    <?php else: ?>
                                        <div class="koreksi-info">🔄 Koreksi dari: <?= htmlspecialchars($htrx['koreksi_dari_id']) ?></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">Rp <?= $h_total_d ?></td>
                            <td class="text-right">Rp <?= $h_total_k ?></td>
                            <td class="text-center">
                                <span class="badge <?= $h_badge_class ?>"><?= $h_status_label[$h_status] ?? $h_status ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="card" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                <p>Belum ada riwayat verifikasi transaksi yang diposting atau dikoreksi.</p>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
function toggleDetail(btn, id) {
    const rows = document.querySelectorAll('#' + id);
    const isVisible = rows[0]?.style.display !== 'none';
    
    rows.forEach(row => {
        row.style.display = isVisible ? 'none' : 'table-row';
    });
    
    btn.classList.toggle('expanded', !isVisible);
}
</script>

</body>
</html>


