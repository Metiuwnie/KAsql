<?php
function rupiah($v) {
    return 'Rp ' . number_format($v, 0, ',', '.');
}

$sub_jenis_label = [
    'beban_akrual'      => 'Beban Akrual',
    'pendapatan_akrual' => 'Pendapatan Akrual',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data['title']) ?></title>
    <meta name="description" content="Monitoring dan eksekusi manual Jurnal Pembalik (Reversing Entries) otomatis untuk akun akrual.">
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css?v=<?= time() ?>">
    <script src="<?= BASE_URL ?>/public/js/script.js?v=2.0" defer></script>
    <style>
        .badge-pending  { background: rgba(245,158,11,.12); color:#b45309; border:1px solid rgba(245,158,11,.25); padding:3px 10px; border-radius:20px; font-size:.75rem; font-weight:700; letter-spacing:0.04em; display:inline-flex; align-items:center; line-height:1.2; }
        .badge-done     { background: rgba(16,185,129,.12); color:#047857; border:1px solid rgba(16,185,129,.25); padding:3px 10px; border-radius:20px; font-size:.75rem; font-weight:700; letter-spacing:0.04em; display:inline-flex; align-items:center; line-height:1.2; }
        .badge-pending::before,
        .badge-done::before { content: none !important; display: none !important; }

        .badge-type     { background: #f4f4f5; color: #18181b; border: 1px solid rgba(0,0,0,.1); padding: 3px 10px; border-radius: 20px; font-size: .78rem; font-weight: 600; letter-spacing: 0.02em; display: inline-flex; align-items: center; white-space: nowrap; line-height: 1.3; }
        .badge-type-beban      { background: #f4f4f5; color: #18181b; border: 1px solid rgba(0,0,0,.1); }
        .badge-type-pendapatan { background: rgba(5,150,105,.08); color: #047857; border: 1px solid rgba(5,150,105,.22); }

        [data-theme="dark"] .badge-pending  { background: rgba(245,158,11,.18); color: #fbbf24; border-color: rgba(245,158,11,.35); }
        [data-theme="dark"] .badge-done     { background: rgba(16,185,129,.18); color: #34d399; border-color: rgba(16,185,129,.35); }
        [data-theme="dark"] .badge-type     { background: rgba(255,255,255,.08); color: #f4f4f6; border-color: rgba(255,255,255,.16); }
        [data-theme="dark"] .badge-type-beban      { background: rgba(255,255,255,.08); color: #f4f4f6; border-color: rgba(255,255,255,.16); }
        [data-theme="dark"] .badge-type-pendapatan { background: rgba(16,185,129,.16); color: #6ee7b7; border-color: rgba(16,185,129,.35); }

        .stat-card      { background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1.25rem 1.5rem; display:flex; align-items:center; gap:1rem; }
        .stat-icon      { width:48px; height:48px; border-radius:var(--radius-md); display:flex; align-items:center; justify-content:center; font-size:1.5rem; flex-shrink:0; }
        .stat-val       { font-size:1.9rem; font-weight:700; line-height:1; }
        .stat-lbl       { font-size:.82rem; color:var(--text-muted); margin-top:3px; }
        .rev-arrow      { display:inline-flex; align-items:center; gap:4px; font-size:.85rem; color:var(--primary-color); font-weight:600; }
        table.table td  { vertical-align:middle; }
        .card .table-container { box-shadow: none; background: transparent; border: 1px solid var(--border-color); overflow-x: auto; }
        @media (min-width: 992px) {
            .card .table-container { overflow-x: hidden; }
        }
        .empty-state    { text-align:center; padding:3rem 1rem; color:var(--text-muted); }
        .empty-state svg{ opacity:.3; margin-bottom:.75rem; }
        .info-box       { background:rgba(59,130,246,.07); border-left:4px solid var(--primary-color); border-radius:var(--radius-sm); padding:.9rem 1.1rem; font-size:.88rem; color:var(--text-main); margin-bottom:1.5rem; }
        .info-box strong{ color:var(--primary-color); }
    </style>
</head>
<body>

<?php include __DIR__ . '/../layout/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layout/header.php'; ?>
    <div class="container">

        <h2 class="page-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 style="vertical-align:middle;margin-right:8px;">
                <polyline points="1 4 1 10 7 10"></polyline>
                <path d="M3.51 15a9 9 0 1 0 .49-3.51"></path>
            </svg>
            Jurnal Pembalik (Reversing Entries)
        </h2>

        <?php if(!empty($data['error'])): ?>
            <div style="background-color: #fee2e2; border: 1px solid #ef4444; color: #b91c1c; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 4px rgba(239, 68, 68, 0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <span><?= htmlspecialchars($data['error']) ?></span>
            </div>
        <?php endif; ?>
        <?php if(!empty($data['success'])): ?>
            <div style="background-color: #d1fae5; border: 1px solid #10b981; color: #065f46; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <span><?= $data['success'] ?></span>
            </div>
        <?php endif; ?>

        <div class="info-box" style="display:flex; align-items:flex-start; gap:.75rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary-color)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; margin-top:2px;">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
            </svg>
            <div>
                <strong>Apa itu Jurnal Pembalik?</strong>
                Jurnal pembalik dibuat secara otomatis di awal periode baru untuk membatalkan jurnal penyesuaian akrual
                dari periode sebelumnya. Ini membantu mencegah pencatatan ganda saat transaksi sesungguhnya terjadi.
                <br><small style="color:var(--text-muted)">
                    Pembalikan diproses otomatis setiap hari pukul 00.05. Jika ingin segera membalik jurnal tertentu, gunakan tombol <strong>Balik Sekarang</strong> pada tabel di bawah.
                </small>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1rem; margin-bottom:1.75rem;">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(234,179,8,.12);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ca8a04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="22" x2="12" y2="11"/>
                        <path d="M5 3h14"/><path d="M5 21h14"/>
                        <path d="M17 11l-5-8-5 8"/>
                    </svg>
                </div>
                <div>
                    <div class="stat-val" style="color:#ca8a04;"><?= count($data['pending']) ?></div>
                    <div class="stat-lbl">Menunggu Pembalikan</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(34,197,94,.1);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div>
                    <div class="stat-val" style="color:#16a34a;"><?= count($data['sudah_balik']) ?></div>
                    <div class="stat-lbl">Sudah Dibalik</div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom:1.5rem;">
            <h3 style="margin-top:0; padding-bottom:.75rem; border-bottom:1px solid var(--border-color); display:flex; align-items:center; gap:.5rem;">
                <span class="badge-pending">PENDING</span> Jurnal Menunggu Pembalikan
            </h3>

            <?php if (empty($data['pending'])): ?>
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <p>Tidak ada jurnal yang menunggu pembalikan saat ini.</p>
                </div>
            <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Tanggal Asli</th>
                            <th>Deskripsi</th>
                            <th>Jenis</th>
                            <th>Nilai</th>
                            <th>Jadwal Pembalik</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($data['pending'] as $row): 
                        $type_cls = ($row['sub_jenis'] === 'pendapatan_akrual') ? 'badge-type-pendapatan' : 'badge-type-beban';
                    ?>
                        <tr>
                            <td><code><?= htmlspecialchars($row['kode_transaksi']) ?></code></td>
                            <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                            <td style="max-width:220px; white-space:normal; font-size:.88rem;"><?= htmlspecialchars($row['deskripsi']) ?></td>
                            <td><span class="badge-type <?= $type_cls ?>"><?= htmlspecialchars($sub_jenis_label[$row['sub_jenis']] ?? $row['sub_jenis']) ?></span></td>
                            <td><?= rupiah($row['total_debet']) ?></td>
                            <td style="color:var(--primary-color); font-weight:600;">
                                <span style="display:inline-flex; align-items:center; gap:5px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    <?= $row['tgl_pembalik'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <form method="POST" action="<?= BASE_URL ?>/jurnal/pembalik" style="display:inline;"
                                      onsubmit="return confirm('Buat jurnal pembalik untuk <?= htmlspecialchars($row['kode_transaksi']) ?> sekarang?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="trigger_reversal" value="1">
                                    <input type="hidden" name="kode_asal" value="<?= htmlspecialchars($row['kode_transaksi']) ?>">
                                    <button type="submit" class="btn btn-primary btn-sm" style="font-size:.8rem; padding:5px 12px;">
                                        ↺ Balik Sekarang
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3 style="margin-top:0; padding-bottom:.75rem; border-bottom:1px solid var(--border-color); display:flex; align-items:center; gap:.5rem;">
                <span class="badge-done">DONE</span> Riwayat Jurnal Pembalik
            </h3>

            <?php if (empty($data['sudah_balik'])): ?>
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <p>Belum ada jurnal yang dibalik.</p>
                </div>
            <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Jurnal Asli</th>
                            <th>Tanggal Asli</th>
                            <th>Deskripsi</th>
                            <th>Jenis</th>
                            <th>Nilai</th>
                            <th><span class="rev-arrow">→ Jurnal Pembalik</span></th>
                            <th>Dibalik Pada</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($data['sudah_balik'] as $row): 
                        $type_cls = ($row['sub_jenis'] === 'pendapatan_akrual') ? 'badge-type-pendapatan' : 'badge-type-beban';
                    ?>
                        <tr>
                            <td><code><?= htmlspecialchars($row['kode_transaksi']) ?></code></td>
                            <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                            <td style="max-width:200px; white-space:normal; font-size:.88rem;"><?= htmlspecialchars($row['deskripsi']) ?></td>
                            <td><span class="badge-type <?= $type_cls ?>"><?= htmlspecialchars($sub_jenis_label[$row['sub_jenis']] ?? $row['sub_jenis']) ?></span></td>
                            <td><?= rupiah($row['total_debet']) ?></td>
                            <td>
                                <code style="color:var(--success-color); background:rgba(34,197,94,.08); padding:2px 7px; border-radius:4px;">
                                    <?= htmlspecialchars($row['reversed_jurnal_kode'] ?? '-') ?>
                                </code>
                            </td>
                            <td style="font-size:.85rem; color:var(--text-muted);">
                                <?= $row['reversed_at'] ? date('d M Y H:i', strtotime($row['reversed_at'])) : '-' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

</body>
</html>


