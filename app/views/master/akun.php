<!DOCTYPE html>
<html lang="id">
<head>
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data['title']) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css?v=<?= time() ?>">
    <script src="<?= BASE_URL ?>/public/js/script.js?v=2.0" defer></script>
    <style>
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
        /* High-Contrast White Font in Dark Mode */
        [data-theme="dark"] .table td {
            color: #ffffff !important;
        }
        [data-theme="dark"] .table td.font-bold {
            color: #ffffff !important;
        }
        [data-theme="dark"] .table th {
            color: #d4d4d8 !important;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../layout/sidebar.php'; ?>

<div class="main-content">
    <?php include __DIR__ . '/../layout/header.php'; ?>
<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 24px;">
        <div>
            <h2 style="margin: 0; color: var(--text-main); font-weight: 700;">Manajemen Data Akun</h2>
        </div>
    </div>

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

    <div class="card" style="width: 100%; margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; padding-bottom: 0.85rem; border-bottom: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(255, 94, 26, 0.12); color: var(--primary-color); display: flex; align-items: center; justify-content: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="12" y1="8" x2="12" y2="16"></line>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg>
                </div>
                <div>
                    <h4 style="margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--text-main);">
                        <?= $data['edit_data'] ? "Edit Data Akun: " . htmlspecialchars($data['edit_data']['kode_akun']) . " - " . htmlspecialchars($data['edit_data']['akun']) : "Tambah Akun Baru" ?>
                    </h4>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                        <?= $data['edit_data'] ? "Perbarui informasi dan klasifikasi akun ini." : "Lengkapi formulir untuk menambahkan akun baru ke dalam sistem pembukuan." ?>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/master/akun">
            <?= csrf_field() ?>
            <?php if($data['edit_data']): ?>
                <input type="hidden" name="is_edit" value="1">
                <input type="hidden" name="old_kode" value="<?= htmlspecialchars($data['edit_data']['kode_akun']) ?>">
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="kode_akun" class="form-label">Kode Akun</label>
                    <input type="text" id="kode_akun" name="kode_akun" class="form-control" 
                           value="<?= htmlspecialchars($data['edit_data']['kode_akun'] ?? '') ?>" 
                           placeholder="Contoh: 101"
                           <?= $data['edit_data'] ? "readonly style='background-color:var(--bg-surface-hover); cursor:not-allowed;'" : "required" ?>>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="nama_akun" class="form-label">Nama Akun</label>
                    <input type="text" id="nama_akun" name="nama_akun" class="form-control" 
                           value="<?= htmlspecialchars($data['edit_data']['akun'] ?? '') ?>" 
                           placeholder="Contoh: Kas Utama" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="aktiva_pasiva" class="form-label">Kategori (Aktiva/Pasiva)</label>
                    <select id="aktiva_pasiva" name="aktiva_pasiva" class="form-control">
                        <option value="">-- Berada di Luar Neraca --</option>
                        <option value="A" <?= (isset($data['edit_data']['aktiva_pasiva']) && $data['edit_data']['aktiva_pasiva'] == 'A') ? 'selected' : '' ?>>A (Aktiva)</option>
                        <option value="P" <?= (isset($data['edit_data']['aktiva_pasiva']) && $data['edit_data']['aktiva_pasiva'] == 'P') ? 'selected' : '' ?>>P (Pasiva)</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="kategori_neraca" class="form-label">Kategori Neraca Detail</label>
                    <select id="kategori_neraca" name="kategori_neraca" class="form-control">
                        <option value="">-- Pilih Kategori Neraca --</option>
                        <option value="Aktiva Lancar" <?= (isset($data['edit_data']['kategori_neraca']) && $data['edit_data']['kategori_neraca'] == 'Aktiva Lancar') ? 'selected' : '' ?>>Aktiva Lancar</option>
                        <option value="Aktiva Tetap" <?= (isset($data['edit_data']['kategori_neraca']) && $data['edit_data']['kategori_neraca'] == 'Aktiva Tetap') ? 'selected' : '' ?>>Aktiva Tetap</option>
                        <option value="Aktiva Lainnya" <?= (isset($data['edit_data']['kategori_neraca']) && $data['edit_data']['kategori_neraca'] == 'Aktiva Lainnya') ? 'selected' : '' ?>>Aktiva Lainnya</option>
                        <option value="Utang Lancar" <?= (isset($data['edit_data']['kategori_neraca']) && $data['edit_data']['kategori_neraca'] == 'Utang Lancar') ? 'selected' : '' ?>>Utang Lancar</option>
                        <option value="Utang Jangka Panjang" <?= (isset($data['edit_data']['kategori_neraca']) && $data['edit_data']['kategori_neraca'] == 'Utang Jangka Panjang') ? 'selected' : '' ?>>Utang Jangka Panjang</option>
                        <option value="Ekuitas" <?= (isset($data['edit_data']['kategori_neraca']) && $data['edit_data']['kategori_neraca'] == 'Ekuitas') ? 'selected' : '' ?>>Ekuitas</option>
                        <option value="Pendapatan" <?= (isset($data['edit_data']['kategori_neraca']) && $data['edit_data']['kategori_neraca'] == 'Pendapatan') ? 'selected' : '' ?>>Pendapatan</option>
                        <option value="Beban" <?= (isset($data['edit_data']['kategori_neraca']) && $data['edit_data']['kategori_neraca'] == 'Beban') ? 'selected' : '' ?>>Beban</option>
                    </select>
                </div>
            </div>
            
            <div style="margin-top: 1.25rem; border-top: 1px solid var(--border-color); padding-top: 1rem; display: flex; justify-content: flex-end; align-items: center; gap: 10px;">
                <?php if($data['edit_data']): ?>
                    <a href="<?= BASE_URL ?>/master/akun" class="btn btn-secondary" style="padding: 0.55rem 1.1rem;">Batal Edit</a>
                <?php endif; ?>
                <button type="submit" name="submit_akun" value="1" class="btn btn-primary" style="padding: 0.55rem 1.25rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; vertical-align: middle;">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    <?= $data['edit_data'] ? "Simpan Perubahan" : "Simpan Akun Baru" ?>
                </button>
            </div>
        </form>
    </div>

    <h3 class="page-title" style="margin-top: 40px;">Daftar Seluruh Akun</h3>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 15%">Kode Akun</th>
                    <th>Nama Akun</th>
                    <th style="width: 15%">Aktiva/Pasiva</th>
                    <th style="width: 20%">Kategori Neraca</th>
                    <th style="width: 20%; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($data['akun_list']) > 0): ?>
                    <?php foreach($data['akun_list'] as $row): ?>
                        <tr>
                            <td class="font-bold"><?= htmlspecialchars($row['kode_akun']) ?></td>
                            <td><?= htmlspecialchars($row['akun']) ?></td>
                            <td><?= htmlspecialchars($row['aktiva_pasiva'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['kategori_neraca'] ?? '-') ?></td>
                            <td style="text-align: center;">
                                <div class="action-btn-group">
                                    <a href="<?= BASE_URL ?>/master/akun?action=edit&kode=<?= urlencode($row['kode_akun']) ?>" class="btn btn-accent" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">Edit</a>
                                    <form method="POST" action="<?= BASE_URL ?>/master/akun" style="display: inline-block;" onsubmit="return confirm('Yakin hapus?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="kode" value="<?= htmlspecialchars($row['kode_akun']) ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center">Belum ada data akun.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</body>
</html>


