<?php
// Role badge colors
function role_badge_class($role) {
    switch ($role) {
        case 'admin': return 'background: linear-gradient(135deg, #ff5e1a, #ea580c); color: white;';
        case 'accountant': return 'background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white;';
        case 'cashier': return 'background: linear-gradient(135deg, #10b981, #059669); color: white;';
        default: return 'background: #64748b; color: white;';
    }
}
function role_label($role) {
    switch ($role) {
        case 'admin': return 'Admin';
        case 'accountant': return 'Akuntan';
        case 'cashier': return 'Kasir';
        default: return ucfirst($role);
    }
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
    <title><?= htmlspecialchars($data['title']) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css?v=<?= time() ?>">
    <script src="<?= BASE_URL ?>/public/js/script.js?v=2.0" defer></script>
    <style>
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            color: white;
            flex-shrink: 0;
        }
        .user-info-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .password-hint {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* Modern Role Permission Matrix Card */
        .role-permission-card {
            margin-top: 2rem;
            padding: 1.5rem 1.75rem;
            border-radius: 16px;
            background: var(--bg-surface);
            border: 1px solid var(--border-glass, rgba(125, 125, 125, 0.12));
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .role-permission-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 1.5rem;
            padding-bottom: 1.1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .role-header-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 94, 26, 0.12);
            color: var(--primary-color);
            flex-shrink: 0;
        }

        [data-theme="dark"] .role-header-icon {
            background: rgba(255, 94, 26, 0.18);
            color: #ff5e1a;
        }

        .role-header-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.01em;
        }

        .role-header-subtitle {
            margin: 3px 0 0 0;
            font-size: 0.82rem;
            color: var(--text-muted);
            line-height: 1.4;
        }

        .role-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.25rem;
        }

        .role-item-card {
            border-radius: 14px;
            padding: 1.35rem;
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            position: relative;
        }

        [data-theme="light"] .role-item-card {
            background: #ffffff;
            border-color: rgba(0, 0, 0, 0.07);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        [data-theme="dark"] .role-item-card {
            background: rgba(255, 255, 255, 0.025);
            border-color: rgba(255, 255, 255, 0.07);
        }

        .role-item-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
        }

        [data-theme="dark"] .role-item-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
        }

        /* Subtle Accent Highlight on Hover */
        .role-item-card.role-cashier:hover {
            border-color: #10b981;
        }
        .role-item-card.role-accountant:hover {
            border-color: #3b82f6;
        }
        .role-item-card.role-admin:hover {
            border-color: #ff5e1a;
        }

        .role-item-top {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1.1rem;
            padding-bottom: 0.95rem;
            border-bottom: 1px solid var(--border-color);
        }

        .role-icon-box {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.04);
        }

        .role-icon-box.cashier {
            background: rgba(16, 185, 129, 0.14);
            color: #10b981;
        }
        .role-icon-box.accountant {
            background: rgba(59, 130, 246, 0.14);
            color: #3b82f6;
        }
        .role-icon-box.admin {
            background: rgba(255, 94, 26, 0.14);
            color: #ff5e1a;
        }

        [data-theme="light"] .role-icon-box.cashier {
            background: #ecfdf5;
            color: #059669;
        }
        [data-theme="light"] .role-icon-box.accountant {
            background: #eff6ff;
            color: #2563eb;
        }
        [data-theme="light"] .role-icon-box.admin {
            background: #fff7ed;
            color: #ea580c;
        }

        .role-item-name {
            font-size: 0.98rem;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.25;
        }

        .role-item-tier {
            font-size: 0.74rem;
            color: var(--text-muted);
            margin-top: 2px;
            font-weight: 500;
        }

        .role-permissions-list {
            display: flex;
            flex-direction: column;
            gap: 0.7rem;
            font-size: 0.84rem;
        }

        .perm-row {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            line-height: 1.45;
            color: var(--text-main);
        }

        .perm-check {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            font-size: 0.68rem;
            font-weight: 800;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .perm-check.allowed {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
        }
        [data-theme="light"] .perm-check.allowed {
            background: #ecfdf5;
            color: #059669;
        }

        .perm-check.denied {
            background: rgba(239, 68, 68, 0.12);
            color: #ef4444;
        }
        [data-theme="light"] .perm-check.denied {
            background: #fef2f2;
            color: #dc2626;
        }

        .perm-row.restricted {
            color: var(--text-muted);
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
                <h2 style="margin: 0; color: var(--text-main); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; color: var(--text-muted);">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    Manajemen User
                </h2>
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

        <!-- Add / Edit Form -->
        <div class="card" style="width: 100%; margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; padding-bottom: 0.85rem; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(255, 94, 26, 0.12); color: var(--primary-color); display: flex; align-items: center; justify-content: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <line x1="20" y1="8" x2="20" y2="14"></line>
                            <line x1="23" y1="11" x2="17" y2="11"></line>
                        </svg>
                    </div>
                    <div>
                        <h4 style="margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--text-main);">
                            <?= $data['edit_data'] ? 'Edit Data User: ' . htmlspecialchars($data['edit_data']['username']) : 'Tambah User Baru' ?>
                        </h4>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                            <?= $data['edit_data'] ? 'Perbarui informasi pengguna dan hak akses di bawah ini.' : 'Daftarkan pengguna baru dan tentukan hak akses sistem.' ?>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" action="<?= BASE_URL ?>/master/user">
                <?= csrf_field() ?>
                <?php if($data['edit_data']): ?>
                    <input type="hidden" name="is_edit" value="1">
                    <input type="hidden" name="edit_id" value="<?= htmlspecialchars($data['edit_data']['id']) ?>">
                <?php endif; ?>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-control" 
                               value="<?= htmlspecialchars($data['edit_data']['username'] ?? '') ?>" 
                               placeholder="Masukkan username" required autocomplete="off">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" 
                               value="<?= htmlspecialchars($data['edit_data']['nama_lengkap'] ?? '') ?>" 
                               placeholder="Masukkan nama lengkap" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="<?= $data['edit_data'] ? 'Kosongkan jika tidak diubah' : 'Minimal 6 karakter' ?>"
                               <?= !$data['edit_data'] ? 'required' : '' ?> autocomplete="new-password" minlength="6">
                        <?php if($data['edit_data']): ?>
                            <div class="password-hint">Kosongkan jika tidak ingin mengubah password.</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="role" class="form-label">Role / Level Akses</label>
                        <select id="role" name="role" class="form-control" required>
                            <option value="cashier" <?= (isset($data['edit_data']['role']) && $data['edit_data']['role'] == 'cashier') ? 'selected' : '' ?>>Kasir (Cashier)</option>
                            <option value="accountant" <?= (isset($data['edit_data']['role']) && $data['edit_data']['role'] == 'accountant') ? 'selected' : '' ?>>Akuntan (Accountant)</option>
                            <option value="admin" <?= (isset($data['edit_data']['role']) && $data['edit_data']['role'] == 'admin') ? 'selected' : '' ?>>Administrator (Admin)</option>
                        </select>
                    </div>
                </div>

                <div style="margin-top: 1.25rem; border-top: 1px solid var(--border-color); padding-top: 1rem; display: flex; justify-content: flex-end; align-items: center; gap: 10px;">
                    <?php if($data['edit_data']): ?>
                        <a href="<?= BASE_URL ?>/master/user" class="btn btn-secondary" style="padding: 0.55rem 1.1rem;">Batal</a>
                    <?php endif; ?>
                    <button type="submit" name="submit_user" value="1" class="btn btn-primary" style="padding: 0.55rem 1.25rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; vertical-align: middle;">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        <?= $data['edit_data'] ? 'Simpan Perubahan' : 'Tambah User' ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <h3 class="page-title" style="margin-top: 40px;">Daftar Semua User</h3>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 5%">#</th>
                        <th style="width: 22%">User</th>
                        <th style="width: 22%">Nama Lengkap</th>
                        <th style="width: 13%">Role</th>
                        <th style="width: 18%">Dibuat</th>
                        <th style="width: 20%; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if(count($data['user_list']) > 0): 
                        foreach($data['user_list'] as $row): 
                            $avatar_colors = ['admin' => '#ef4444', 'accountant' => '#f59e0b', 'cashier' => '#10b981'];
                            $initial = strtoupper(substr($row['nama_lengkap'], 0, 1));
                            $bg_color = $avatar_colors[$row['role']] ?? '#64748b';
                    ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td>
                                <div class="user-info-cell">
                                    <div class="user-avatar" style="background: <?= $bg_color ?>;"><?= $initial ?></div>
                                    <div>
                                        <strong><?= htmlspecialchars($row['username']) ?></strong>
                                        <?php if($row['id'] == $_SESSION['user_id']): ?>
                                            <br><small style="color: var(--primary-color); font-weight: 600;">(Anda)</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                            <td>
                                <span class="role-badge" style="<?= role_badge_class($row['role']) ?>">
                                    <?= role_label($row['role']) ?>
                                </span>
                            </td>
                            <td style="color: var(--text-muted); font-size: 0.85rem;">
                                <?= date('d M Y, H:i', strtotime($row['created_at'])) ?>
                            </td>
                            <td style="text-align: center;">
                                <div class="action-btn-group">
                                    <a href="<?= BASE_URL ?>/master/user?action=edit&id=<?= urlencode($row['id']) ?>" 
                                       class="btn btn-accent" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">Edit</a>
                                    <?php if($row['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" action="<?= BASE_URL ?>/master/user" style="display: inline-block;" 
                                          onsubmit="return confirm('Yakin hapus user \'<?= htmlspecialchars($row['username']) ?>\'?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($row['id']) ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">Hapus</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center">Belum ada data user.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Modern Role Permissions Section -->
        <div class="role-permission-card">
            <div class="role-permission-header">
                <div class="role-header-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="role-header-title">Matriks Hak Akses & Otoritas Role</h4>
                    <p class="role-header-subtitle">Ringkasan hak izin operasional, otoritas verifikasi akuntansi, dan batasan akses modul sistem.</p>
                </div>
            </div>

            <div class="role-grid">
                <!-- Kasir -->
                <div class="role-item-card role-cashier">
                    <div class="role-item-top">
                        <div class="role-icon-box cashier">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="4" width="20" height="16" rx="2"/>
                                <line x1="6" y1="8" x2="6.01" y2="8"/>
                                <line x1="10" y1="8" x2="14" y2="8"/>
                                <line x1="6" y1="12" x2="18" y2="12"/>
                            </svg>
                        </div>
                        <div>
                            <div class="role-item-name">Kasir</div>
                            <div class="role-item-tier">Operator Entri Transaksi</div>
                        </div>
                    </div>
                    
                    <div class="role-permissions-list">
                        <div class="perm-row">
                            <span class="perm-check allowed">✓</span>
                            <span>Entri transaksi jurnal kas & operasional baru</span>
                        </div>
                        <div class="perm-row">
                            <span class="perm-check allowed">✓</span>
                            <span>Melihat histori jurnal transaksi milik sendiri</span>
                        </div>
                        <div class="perm-row restricted">
                            <span class="perm-check denied">✕</span>
                            <span>Tidak dapat edit atau hapus transaksi tersimpan</span>
                        </div>
                        <div class="perm-row restricted">
                            <span class="perm-check denied">✕</span>
                            <span>Tidak dapat membuka modul laporan & neraca</span>
                        </div>
                    </div>
                </div>

                <!-- Akuntan -->
                <div class="role-item-card role-accountant">
                    <div class="role-item-top">
                        <div class="role-icon-box accountant">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="20" x2="18" y2="10"/>
                                <line x1="12" y1="20" x2="12" y2="4"/>
                                <line x1="6" y1="20" x2="6" y2="14"/>
                            </svg>
                        </div>
                        <div>
                            <div class="role-item-name">Akuntan</div>
                            <div class="role-item-tier">Pelaporan & Pembukuan Finansial</div>
                        </div>
                    </div>
                    
                    <div class="role-permissions-list">
                        <div class="perm-row">
                            <span class="perm-check allowed">✓</span>
                            <span>Akses penuh laporan keuangan & neraca saldo</span>
                        </div>
                        <div class="perm-row">
                            <span class="perm-check allowed">✓</span>
                            <span>Verifikasi & posting transaksi berstatus pending</span>
                        </div>
                        <div class="perm-row">
                            <span class="perm-check allowed">✓</span>
                            <span>Kelola jurnal penyesuaian, pembalik, & closing</span>
                        </div>
                        <div class="perm-row restricted">
                            <span class="perm-check denied">✕</span>
                            <span>Tidak memiliki otoritas manajemen pengguna</span>
                        </div>
                    </div>
                </div>

                <!-- Admin -->
                <div class="role-item-card role-admin">
                    <div class="role-item-top">
                        <div class="role-icon-box admin">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                <path d="M2 17l10 5 10-5"/>
                                <path d="M2 12l10 5 10-5"/>
                            </svg>
                        </div>
                        <div>
                            <div class="role-item-name">Administrator</div>
                            <div class="role-item-tier">Kontrol Penuh & Otoritas Sistem</div>
                        </div>
                    </div>
                    
                    <div class="role-permissions-list">
                        <div class="perm-row">
                            <span class="perm-check allowed">✓</span>
                            <span>Mencakup seluruh hak akses modul akuntan</span>
                        </div>
                        <div class="perm-row">
                            <span class="perm-check allowed">✓</span>
                            <span>Manajemen akun pengguna & penentuan role</span>
                        </div>
                        <div class="perm-row">
                            <span class="perm-check allowed">✓</span>
                            <span>Konfigurasi aplikasi & integrasi API Gemini</span>
                        </div>
                        <div class="perm-row">
                            <span class="perm-check allowed">✓</span>
                            <span>Otoritas master data & pembukuan menyeluruh</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>


