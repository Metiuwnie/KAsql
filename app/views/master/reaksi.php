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
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0;
        }
        .tab-btn {
            padding: 12px 24px;
            border: none;
            background: none;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-muted);
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        .tab-btn.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        .tab-content {
            display: none;
            animation: fadeIn 0.4s ease;
        }
        .tab-content.active {
            display: block;
        }
        .reaksi-badge {
            background: var(--primary-light);
            color: var(--primary-color);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        /* Seamless Table Row Alignment (No Broken Borders) */
        .table td {
            vertical-align: middle;
        }
        .action-btn-group {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .action-btn-group a,
        .action-btn-group button {
            color: #ffffff !important;
            font-weight: 500 !important;
            border-radius: 6px !important;
        }
        [data-theme="dark"] .action-btn-group .btn-accent {
            background: rgba(255, 94, 26, 0.16) !important;
            color: #ffffff !important;
            border: 1px solid rgba(255, 94, 26, 0.35) !important;
            box-shadow: none !important;
        }
        [data-theme="dark"] .action-btn-group .btn-accent:hover {
            background: #ea580c !important;
            box-shadow: 0 0 10px rgba(255, 94, 26, 0.35) !important;
        }
        [data-theme="dark"] .action-btn-group .btn-danger {
            background: rgba(239, 68, 68, 0.16) !important;
            color: #ffffff !important;
            border: 1px solid rgba(239, 68, 68, 0.35) !important;
            box-shadow: none !important;
        }
        [data-theme="dark"] .action-btn-group .btn-danger:hover {
            background: #dc2626 !important;
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.35) !important;
        }

        /* D/K Badge & Indicator */
        .badge-dk {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-weight: 700;
            font-size: 0.9rem;
        }
        .dk-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }
        .badge-dk-debit {
            color: #0284c7;
        }
        .dk-dot.debit {
            background: #38bdf8;
            box-shadow: 0 0 6px rgba(56, 189, 248, 0.6);
        }
        .badge-dk-kredit {
            color: #dc2626;
        }
        .dk-dot.kredit {
            background: #f87171;
            box-shadow: 0 0 6px rgba(248, 113, 113, 0.6);
        }

        /* High-Contrast White Font in Dark Mode */
        [data-theme="dark"] .table td {
            color: #ffffff !important;
        }
        [data-theme="dark"] .table td small {
            color: #e4e4e7 !important;
        }
        [data-theme="dark"] .table td strong {
            color: #ffffff !important;
        }
        [data-theme="dark"] .table th {
            color: #d4d4d8 !important;
        }
        [data-theme="dark"] .badge-dk-debit,
        [data-theme="dark"] .badge-dk-kredit {
            color: #ffffff !important;
        }
        [data-theme="dark"] .reaksi-badge {
            background: rgba(255, 94, 26, 0.15);
            color: #ff5e1a;
            border: 1px solid rgba(255, 94, 26, 0.3);
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/../layout/sidebar.php'; ?>

<div class="main-content">
    <?php include __DIR__ . '/../layout/header.php'; ?>
<div class="container">
    <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 24px;">
        <h2 style="margin: 0; color: var(--text-main); font-weight: 700;">Manajemen Sistem Reaksi Otomatis</h2>
    </div>

    <!-- Alert Messages -->
    <?php if($data['success']): ?>
        <div style="background-color: var(--success-bg); color: var(--success-color); padding: 15px; margin-bottom: 20px; border-radius: var(--radius-md); border: 1px solid var(--success-color);">
            <?= htmlspecialchars($data['success']) ?>
        </div>
    <?php endif; ?>
    <?php if($data['error']): ?>
        <div style="background-color: var(--danger-bg); color: var(--danger-color); padding: 15px; margin-bottom: 20px; border-radius: var(--radius-md); border: 1px solid var(--danger-color);">
            <?= htmlspecialchars($data['error']) ?>
        </div>
    <?php endif; ?>

    <!-- Tabs Navigation -->
    <div class="tabs">
        <button class="tab-btn <?= $data['active_tab'] == 'reaksi' ? 'active' : '' ?>" onclick="openTab('reaksi')">1. Daftar Jenis Reaksi</button>
        <button class="tab-btn <?= $data['active_tab'] == 'detail' ? 'active' : '' ?>" onclick="openTab('detail')">2. Mapping Akun (Debit/Kredit)</button>
    </div>

    <!-- TAB 1: JENIS REAKSI -->
    <div id="tab-reaksi" class="tab-content <?= $data['active_tab'] == 'reaksi' ? 'active' : '' ?>">
        <div class="card" style="max-width: 600px; margin-bottom: 30px;">
            <h4 style="margin-top: 0;"><?= $data['edit_reaksi'] ? 'Edit Jenis Reaksi' : 'Tambah Jenis Reaksi Baru' ?></h4>
            <form method="POST" action="<?= BASE_URL ?>/master/reaksi?tab=reaksi">
                <?= csrf_field() ?>
                <input type="hidden" name="submit_reaksi" value="1">
                <?php if($data['edit_reaksi']): ?>
                    <input type="hidden" name="is_edit_reaksi" value="1">
                <?php endif; ?>
                
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">ID Reaksi</label>
                        <input type="text" name="id_reaksi" class="form-control" placeholder="Contoh: R01" 
                               value="<?= htmlspecialchars($data['edit_reaksi']['id_reaksi'] ?? '') ?>" 
                               <?= $data['edit_reaksi'] ? 'readonly style="background:#f1f5f9;"' : 'required' ?>>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Reaksi (Aktivitas)</label>
                        <input type="text" name="nama_reaksi" class="form-control" placeholder="Contoh: Penjualan Tunai" 
                               value="<?= htmlspecialchars($data['edit_reaksi']['nama_reaksi'] ?? '') ?>" required>
                    </div>
                </div>
                <div style="margin-top: 15px; text-align: right;">
                    <?php if($data['edit_reaksi']): ?>
                        <a href="<?= BASE_URL ?>/master/reaksi?tab=reaksi" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary"><?= $data['edit_reaksi'] ? 'Simpan Perubahan' : 'Tambah Reaksi' ?></button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 15%;">ID</th>
                        <th>Nama Aktivitas Reaksi</th>
                        <th style="width: 20%; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($data['reaksi_list'] as $r): ?>
                        <tr>
                            <td><span class="reaksi-badge"><?= htmlspecialchars($r['id_reaksi']) ?></span></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($r['nama_reaksi']) ?></td>
                            <td style="text-align: center;">
                                <div class="action-btn-group">
                                    <a href="<?= BASE_URL ?>/master/reaksi?action=edit_reaksi&id=<?= urlencode($r['id_reaksi']) ?>&tab=reaksi" class="btn btn-accent" style="padding: 5px 10px; font-size: 0.8rem;">Edit</a>
                                    <form method="POST" action="<?= BASE_URL ?>/master/reaksi?tab=reaksi" style="display: inline;" onsubmit="return confirm('Hapus reaksi ini?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_reaksi">
                                        <input type="hidden" name="id_reaksi" value="<?= htmlspecialchars($r['id_reaksi']) ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem; cursor: pointer;">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: DETAIL MAPPING -->
    <div id="tab-detail" class="tab-content <?= $data['active_tab'] == 'detail' ? 'active' : '' ?>">
        <div class="card" style="margin-bottom: 30px;">
            <h4 style="margin-top: 0;"><?= $data['edit_detail'] ? 'Edit Mapping Akun' : 'Tambah Mapping Akun Baru' ?></h4>
            <form method="POST" action="<?= BASE_URL ?>/master/reaksi?tab=detail">
                <?= csrf_field() ?>
                <input type="hidden" name="submit_detail" value="1">
                <?php if($data['edit_detail']): ?>
                    <input type="hidden" name="is_edit" value="1">
                    <input type="hidden" name="id_detail_reaksi" value="<?= htmlspecialchars($data['edit_detail']['id_detail_reaksi']) ?>">
                <?php endif; ?>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Jenis Reaksi</label>
                        <select name="id_reaksi" class="form-control" required>
                            <option value="">-- Pilih Reaksi --</option>
                            <?php foreach($data['reaksi_list'] as $opt): ?>
                                <option value="<?= htmlspecialchars($opt['id_reaksi']) ?>" <?= ($data['edit_detail'] && $data['edit_detail']['id_reaksi'] == $opt['id_reaksi']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars('[' . $opt['id_reaksi'] . '] ' . $opt['nama_reaksi']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Akun Terkait</label>
                        <select name="kode_akun" class="form-control" required>
                            <option value="">-- Pilih Akun --</option>
                            <?php foreach($data['akun_list'] as $opt): ?>
                                <option value="<?= htmlspecialchars($opt['kode_akun']) ?>" <?= ($data['edit_detail'] && $data['edit_detail']['kode_akun'] == $opt['kode_akun']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars('[' . $opt['kode_akun'] . '] ' . $opt['akun']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Posisi (D/K)</label>
                        <select name="dk" class="form-control" required>
                            <option value="">-- Pilih Debit/Kredit --</option>
                            <option value="Debit" <?= ($data['edit_detail'] && $data['edit_detail']['dk'] == 'Debit') ? 'selected' : '' ?>>Debit</option>
                            <option value="Kredit" <?= ($data['edit_detail'] && $data['edit_detail']['dk'] == 'Kredit') ? 'selected' : '' ?>>Kredit</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top: 15px; text-align: right;">
                    <?php if($data['edit_detail']): ?>
                        <a href="<?= BASE_URL ?>/master/reaksi?tab=detail" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary"><?= $data['edit_detail'] ? 'Simpan Perubahan' : 'Tambah Mapping' ?></button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Reaksi</th>
                        <th>Akun Terkait</th>
                        <th style="width: 15%;">D/K</th>
                        <th style="width: 15%; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($data['mapping_list'] as $row): ?>
                        <tr>
                            <td>
                                <span class="reaksi-badge"><?= htmlspecialchars($row['id_reaksi']) ?></span><br>
                                <small><?= htmlspecialchars($row['nama_reaksi'] ?? '???') ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($row['nama_akun'] ?? '???') ?></strong><br>
                                <small style="color: var(--text-muted);">Kode: <?= htmlspecialchars($row['kode_akun']) ?></small>
                            </td>
                            <td>
                                <?php if(strtolower($row['dk']) == 'debit'): ?>
                                    <span class="badge-dk badge-dk-debit"><span class="dk-dot debit"></span>Debit</span>
                                <?php else: ?>
                                    <span class="badge-dk badge-dk-kredit"><span class="dk-dot kredit"></span>Kredit</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <div class="action-btn-group">
                                    <a href="<?= BASE_URL ?>/master/reaksi?action=edit&id=<?= urlencode($row['id_detail_reaksi']) ?>&tab=detail" class="btn btn-accent" style="padding: 5px 10px; font-size: 0.8rem;">Edit</a>
                                    <form method="POST" action="<?= BASE_URL ?>/master/reaksi?tab=detail" style="display: inline;" onsubmit="return confirm('Hapus mapping ini?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_detail">
                                        <input type="hidden" name="id_detail_reaksi" value="<?= htmlspecialchars($row['id_detail_reaksi']) ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem; cursor: pointer;">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<script>
function openTab(tabName) {
    // Hide all contents
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    // Remove active class from buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show current tab
    document.getElementById('tab-' + tabName).classList.add('active');
    // Add active class to button
    event.currentTarget.classList.add('active');
    
    // Update URL without reload to keep state
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.pushState({}, '', url);
}

// Ensure correct tab button is active if page was loaded with a tab parameter via link
document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab');
    if (tab) {
        // Trigger click on correct tab button if needed
        const btn = Array.from(document.querySelectorAll('.tab-btn')).find(b => b.textContent.toLowerCase().includes(tab));
        if (btn) btn.classList.add('active');
    }
});
</script>

</body>
</html>


