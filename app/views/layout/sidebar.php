<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include auth helpers if not already loaded
if (!function_exists('is_logged_in')) {
    require_once dirname(dirname(dirname(__DIR__))) . '/auth.php';
}

$user_role_check = $_SESSION['user_role'] ?? 'cashier';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_ai_settings']) && $user_role_check === 'admin') {
    csrf_verify();
    $_SESSION['api_key'] = trim($_POST['api_key'] ?? '');
    $_SESSION['model_choice'] = $_POST['model_choice'] ?? 'gemini-3.5-flash-lite';
    
    $nama_baru = trim($_POST['nama_perusahaan'] ?? 'USAHA');
    if (empty($nama_baru)) $nama_baru = 'USAHA';
    
    // Update ke Database
    try {
        $db = new Database();
        $conn_upd = $db->getConnection();
        $stmt_upd = $conn_upd->prepare("UPDATE pengaturan SET nama_perusahaan = ? WHERE id = 1");
        $stmt_upd->bind_param("s", $nama_baru);
        $stmt_upd->execute();
    } catch (Exception $e) {
        error_log("[KAsql] Gagal update nama perusahaan: " . $e->getMessage());
    }
    
    $_SESSION['nama_perusahaan'] = $nama_baru;
    
    if (!headers_sent()) {
        $clean_redirect = filter_var($_SERVER['REQUEST_URI'] ?? (defined('BASE_URL') ? BASE_URL : '/'), FILTER_SANITIZE_URL);
        header("Location: " . $clean_redirect);
    } else {
        echo "<script>window.location.replace(window.location.href);</script>";
    }
    exit;
}

$current_page = isset($_GET['url']) ? rtrim($_GET['url'], '/') : 'dashboard';
$current_api_key = $_SESSION['api_key'] ?? '';
$current_model_choice = $_SESSION['model_choice'] ?? 'gemini-3.5-flash-lite';
$current_nama_perusahaan = $_SESSION['nama_perusahaan'] ?? 'USAHA';

// Auth data for sidebar
$user_role = $_SESSION['user_role'] ?? 'cashier';
$user_nama = $_SESSION['user_nama'] ?? 'User';
$user_username = $_SESSION['user_username'] ?? '';

$is_admin = ($user_role === 'admin');
$is_accountant = ($user_role === 'accountant');
$is_cashier = ($user_role === 'cashier');
$can_view_reports = ($is_admin || $is_accountant);
?>

<!-- Sidebar Navigation -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="sidebar-logo-badge" title="KAsql">
                KA
            </div>
            <div style="display: flex; flex-direction: column; flex: 1; min-width: 0;">
                <span style="font-size: 1.05rem; font-weight: 800; color: var(--text-heading); letter-spacing: -0.02em; line-height: 1.2;">KAsql<span style="color: #ff5e1a;">.</span></span>
                <span style="font-size: 0.76rem; font-weight: 600; color: var(--text-muted); line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 1px;" title="<?= htmlspecialchars($current_nama_perusahaan) ?>"><?= htmlspecialchars($current_nama_perusahaan) ?></span>
            </div>
        </div>
    </div>

    <div class="sidebar-content">
        <?php if ($can_view_reports): ?>
        <!-- Dashboard & Laporan Utama (Admin & Accountant only) -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Beranda</div>
            <a href="<?= BASE_URL ?>/" class="sidebar-item <?= ($current_page == 'index.php' || $current_page == 'dashboard') ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                <span>Dashboard</span>
            </a>
        </div>
        <?php endif; ?>

        <!-- Transaksi (Input Transaksi & Pending) -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Transaksi</div>
            <div class="sidebar-dropdown <?= in_array($current_page, ['transaksi/input', 'aset/prepaid', 'aset/tetap', 'input_transaksi.php', 'manajemen_prepaid.php', 'aset_tetap.php']) ? 'open' : '' ?>">
                <button class="sidebar-dropdown-toggle <?= in_array($current_page, ['transaksi/input', 'aset/prepaid', 'aset/tetap', 'input_transaksi.php', 'manajemen_prepaid.php', 'aset_tetap.php']) ? 'active' : '' ?>" onclick="toggleSidebarDropdown(this)">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        <span>Input Transaksi</span>
                    </div>
                    <svg class="dropdown-arrow" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div class="sidebar-dropdown-content">
                    <a href="<?= BASE_URL ?>/transaksi/input" class="sidebar-subitem <?= ($current_page == 'transaksi/input' || $current_page == 'input_transaksi.php') ? 'active' : '' ?>">Entri Transaksi</a>
                    <?php if ($can_view_reports): ?>
                    <a href="<?= BASE_URL ?>/aset/prepaid" class="sidebar-subitem <?= ($current_page == 'aset/prepaid' || $current_page == 'manajemen_prepaid.php') ? 'active' : '' ?>">Prepaid Expense</a>
                    <a href="<?= BASE_URL ?>/aset/tetap" class="sidebar-subitem <?= ($current_page == 'aset/tetap' || $current_page == 'aset_tetap.php') ? 'active' : '' ?>">Depresiasi Aset</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($can_view_reports): ?>
            <?php
                // Count pending transactions for badge
                $pending_count = 0;
                try {
                    $db_badge2 = new Database();
                    $conn_badge2 = $db_badge2->getConnection();
                    $pending_q = $conn_badge2->query("SELECT COUNT(*) as cnt FROM detil_transaksi WHERE status_verifikasi = 'pending'");
                    if ($pending_q && $row_p = $pending_q->fetch_assoc()) {
                        $pending_count = (int)$row_p['cnt'];
                    }
                } catch (Exception $e) { $pending_count = 0; }
            ?>
            <a href="<?= BASE_URL ?>/transaksi/pending" class="sidebar-item <?= ($current_page == 'transaksi/pending' || $current_page == 'transaksi_pending.php') ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 11l3 3L22 4"></path>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
                <span>Transaksi Pending</span>
                <?php if ($pending_count > 0): ?>
                    <span class="sidebar-badge"><?= $pending_count ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>
        </div>

        <?php if ($can_view_reports): ?>
        <!-- Laporan Keuangan -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Laporan Keuangan</div>
            <a href="<?= BASE_URL ?>/jurnal/umum" class="sidebar-item <?= ($current_page == 'jurnal/umum' || $current_page == 'jurnal_umum.php') ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <span>Jurnal Umum</span>
            </a>

            <?php if ($is_accountant || $is_admin): ?>
            <a href="<?= BASE_URL ?>/jurnal/penyesuaian" class="sidebar-item <?= ($current_page == 'jurnal/penyesuaian' || $current_page == 'jurnal_penyesuaian.php') ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 20h9"></path>
                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                </svg>
                <span>Jurnal Penyesuaian</span>
            </a>
            <?php
                // Badge: hitung jurnal pembalik yang menunggu pembalikan
                $rev_pending = 0;
                try {
                    $db_badge = new Database();
                    $conn_badge = $db_badge->getConnection();
                    $rev_q = $conn_badge->query("SELECT COUNT(*) AS cnt FROM detil_transaksi WHERE is_reversing = 1 AND reversed_at IS NULL");
                    if ($rev_q && $rev_r = $rev_q->fetch_assoc()) $rev_pending = (int)$rev_r['cnt'];
                } catch (Exception $e) { $rev_pending = 0; }
            ?>
            <a href="<?= BASE_URL ?>/jurnal/pembalik" class="sidebar-item <?= ($current_page == 'jurnal/pembalik' || $current_page == 'jurnal_pembalik.php') ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <path d="M3.51 15a9 9 0 1 0 .49-3.51"></path>
                </svg>
                <span>Jurnal Pembalik</span>
                <?php if ($rev_pending > 0): ?>
                    <span class="sidebar-badge"><?= $rev_pending ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>

            <a href="<?= BASE_URL ?>/laporan/buku_besar" class="sidebar-item <?= ($current_page == 'laporan/buku_besar' || $current_page == 'buku_besar.php') ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
                <span>Buku Besar</span>
            </a>
            <a href="<?= BASE_URL ?>/laporan/neraca_saldo" class="sidebar-item <?= ($current_page == 'laporan/neraca_saldo' || $current_page == 'neraca_saldo.php') ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                <span>Neraca Saldo</span>
            </a>
            <a href="<?= BASE_URL ?>/laporan/laba_rugi" class="sidebar-item <?= ($current_page == 'laporan/laba_rugi' || $current_page == 'laba_rugi.php') ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                    <polyline points="17 6 23 6 23 12"></polyline>
                </svg>
                <span>Laba Rugi</span>
            </a>
            <a href="<?= BASE_URL ?>/laporan/neraca" class="sidebar-item <?= ($current_page == 'laporan/neraca' || $current_page == 'neraca.php' || $current_page == 'neraca_2.php') ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="3" y1="9" x2="21" y2="9"></line>
                    <line x1="9" y1="21" x2="9" y2="9"></line>
                </svg>
                <span>Neraca Laporan</span>
            </a>
            <a href="<?= BASE_URL ?>/closing" class="sidebar-item <?= ($current_page == 'closing' || $current_page == 'closing_periode.php') ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span>Closing Periode</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if ($can_view_reports): ?>
        <!-- Fitur Analisis AI -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Fitur Analisis AI</div>
            <div class="sidebar-dropdown <?= in_array($current_page, ['analisis/jurnal', 'analisis/laba_rugi', 'analisis/neraca', 'analisa_jurnal.php', 'analisa_labarugi.php', 'analisa_neraca.php']) ? 'open' : '' ?>">
                <button class="sidebar-dropdown-toggle <?= in_array($current_page, ['analisis/jurnal', 'analisis/laba_rugi', 'analisis/neraca', 'analisa_jurnal.php', 'analisa_labarugi.php', 'analisa_neraca.php']) ? 'active' : '' ?>" onclick="toggleSidebarDropdown(this)">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                        <span>Analisis Laporan</span>
                    </div>
                    <svg class="dropdown-arrow" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div class="sidebar-dropdown-content">
                    <a href="<?= BASE_URL ?>/analisis/jurnal" class="sidebar-subitem <?= ($current_page == 'analisis/jurnal' || $current_page == 'analisa_jurnal.php') ? 'active' : '' ?>">Analisis Jurnal Umum</a>
                    <a href="<?= BASE_URL ?>/analisis/laba_rugi" class="sidebar-subitem <?= ($current_page == 'analisis/laba_rugi' || $current_page == 'analisa_labarugi.php') ? 'active' : '' ?>">Analisis Laba Rugi</a>
                    <a href="<?= BASE_URL ?>/analisis/neraca" class="sidebar-subitem <?= ($current_page == 'analisis/neraca' || $current_page == 'analisa_neraca.php') ? 'active' : '' ?>">Analisis Neraca</a>
                </div>
            </div>
        </div>

        <!-- Data Master -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Data Master</div>
            <a href="<?= BASE_URL ?>/master/akun" class="sidebar-item <?= ($current_page == 'master/akun' || $current_page == 'akun.php') ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                    <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                </svg>
                <span>Daftar Akun</span>
            </a>
            <a href="<?= BASE_URL ?>/master/reaksi" class="sidebar-item <?= ($current_page == 'master/reaksi' || $current_page == 'detail_reaksi.php') ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span>Detail Reaksi</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if ($is_admin): ?>
        <!-- Manajemen User (Admin Only) -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Administrasi</div>
            <a href="<?= BASE_URL ?>/master/user" class="sidebar-item <?= ($current_page == 'master/user' || $current_page == 'manajemen_user.php') ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Manajemen User</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if ($is_admin): ?>
        <!-- Pengaturan -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Pengaturan</div>
            <a href="javascript:void(0)" class="sidebar-item" onclick="document.getElementById('aiSettingsModal').style.display='flex'">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
                <span>Settings</span>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <div class="sidebar-footer">
        <!-- User Info Card -->
        <?php 
            $avatar_colors = [
                'admin'      => 'linear-gradient(135deg, #ff5e1a, #ea580c)',
                'accountant' => 'linear-gradient(135deg, #3b82f6, #1d4ed8)',
                'cashier'    => 'linear-gradient(135deg, #10b981, #059669)'
            ];
            $user_initial = strtoupper(substr($user_nama, 0, 1));
            $user_avatar_bg = $avatar_colors[$user_role] ?? 'linear-gradient(135deg, #64748b, #475569)';
            $role_labels = ['admin' => 'Admin', 'accountant' => 'Akuntan', 'cashier' => 'Kasir'];
            $role_display = $role_labels[$user_role] ?? ucfirst($user_role);
        ?>
        <div class="sidebar-user-card">
            <div class="sidebar-user-avatar" style="background: <?= $user_avatar_bg ?>;">
                <?= $user_initial ?>
            </div>
            <div class="sidebar-user-details">
                <div class="sidebar-user-name" title="<?= htmlspecialchars($user_nama) ?>">
                    <?= htmlspecialchars($user_nama) ?>
                </div>
                <div class="sidebar-user-role">
                    <?= $role_display ?>
                </div>
            </div>
        </div>

        <!-- Logout Button (Direct & Reliable) -->
        <a href="<?= BASE_URL ?>/auth/logout" class="sidebar-footer-btn sidebar-logout-btn" id="sidebarLogoutBtn" title="Keluar dari akun">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Logout</span>
        </a>

        <!-- Dark Mode Toggle -->
        <button id="theme-switch-sidebar" class="sidebar-footer-btn theme-switch-sidebar" type="button" title="Ganti mode tampilan">
            <div style="display: flex; align-items: center; gap: 9px;">
                <svg class="theme-icon-dark" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #f59e0b;">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
                <svg class="theme-icon-light" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none; color: #ff5e1a;">
                    <circle cx="12" cy="12" r="5"></circle>
                    <line x1="12" y1="1" x2="12" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="23"></line>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                    <line x1="1" y1="12" x2="3" y2="12"></line>
                    <line x1="21" y1="12" x2="23" y2="12"></line>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                </svg>
                <span class="theme-label-text">Mode Tampilan</span>
            </div>
        </button>

        <!-- KAsql Copyright -->
        <div style="text-align: center; padding-top: 0.65rem; border-top: 1px solid var(--border-color); margin-top: 0.25rem; font-size: 0.72rem; color: var(--text-muted); letter-spacing: 0.02em;">
            &copy; <?= date('Y') ?> <strong style="color: var(--primary-color);">KAsql</strong>. All rights reserved.
        </div>
    </div>
</div>

<!-- Modal Pengaturan AI -->
<div id="aiSettingsModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000; justify-content:center; align-items:center; backdrop-filter: blur(2px);">
    <div class="card" style="width: 100%; max-width: 500px; margin: 20px; position: relative; padding-top: 1.5rem;">
        <button type="button" onclick="document.getElementById('aiSettingsModal').style.display='none'" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); line-height: 1;">&times;</button>
        <h3 style="margin-top: 0; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; color: var(--text-main) !important;">Pengaturan Aplikasi</h3>
        
        <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="save_ai_settings" value="1">
            
            <div class="form-group">
                <label for="nama_perusahaan_modal" class="form-label">Nama Perusahaan:</label>
                <input type="text" name="nama_perusahaan" id="nama_perusahaan_modal" class="form-control" placeholder="Masukkan Nama Perusahaan" value="<?= htmlspecialchars($current_nama_perusahaan) ?>" required autocomplete="off">
            </div>

            <div class="form-group">
                <label for="api_key_modal" class="form-label">Gemini API Key:</label>
                <input type="password" name="api_key" id="api_key_modal" class="form-control" placeholder="Masukkan API Key Gemini Anda" value="<?= htmlspecialchars($current_api_key) ?>" autocomplete="off">
            </div>
            
            <div class="form-group">
                <label for="model_choice_modal" class="form-label">Pilih Model Gemini:</label>
                <select name="model_choice" id="model_choice_modal" class="form-control" required>
                    <option value="gemini-3.5-flash-lite" <?= $current_model_choice === 'gemini-3.5-flash-lite' ? 'selected' : '' ?>>Gemini 3.5 Flash Lite (Cepat & Hemat Kuota)</option>
                    <option value="gemini-1.5-pro" <?= $current_model_choice === 'gemini-1.5-pro' ? 'selected' : '' ?>>Gemini 1.5 Pro (Penalaran Kompleks)</option>
                </select>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('aiSettingsModal').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSidebarDropdown(button) {
    const dropdown = button.parentElement;
    dropdown.classList.toggle('open');
    saveSidebarScroll();
}

function toggleMobileSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    sidebar.classList.toggle('mobile-open');
    overlay.classList.toggle('active');
}

// Sidebar Scroll Persistence across Page Navigation
function getSidebarScrollContainer() {
    return document.querySelector('.sidebar-content') || document.querySelector('.sidebar');
}

function saveSidebarScroll() {
    const sc = getSidebarScrollContainer();
    if (sc) {
        sessionStorage.setItem('kasql_sidebar_scroll', sc.scrollTop.toString());
    }
}

function restoreSidebarScroll() {
    const sc = getSidebarScrollContainer();
    const saved = sessionStorage.getItem('kasql_sidebar_scroll');
    if (sc && saved !== null) {
        const top = parseInt(saved, 10);
        if (!isNaN(top)) {
            sc.scrollTop = top;
        }
    }
}

(function() {
    // Auto-open dropdown if any child is active
    document.querySelectorAll('.sidebar-dropdown').forEach(function(dd) {
        if (dd.querySelector('.active')) {
            dd.classList.add('open');
        }
    });

    // Restore immediately when DOM evaluates
    restoreSidebarScroll();

    // Attach scroll tracking to sidebar content
    const sc = getSidebarScrollContainer();
    if (sc) {
        sc.addEventListener('scroll', function() {
            sessionStorage.setItem('kasql_sidebar_scroll', sc.scrollTop.toString());
        }, { passive: true });
    }

    // Capture on all sidebar navigation clicks (links & buttons)
    document.querySelectorAll('#sidebar a, #sidebar button').forEach(function(el) {
        el.addEventListener('click', function() {
            saveSidebarScroll();
        });
    });

    // Save before unload & pagehide
    window.addEventListener('beforeunload', saveSidebarScroll);
    window.addEventListener('pagehide', saveSidebarScroll);

    // Multi-stage restore to prevent reflow jumping
    document.addEventListener('DOMContentLoaded', restoreSidebarScroll);
    window.addEventListener('load', function() {
        restoreSidebarScroll();
        requestAnimationFrame(restoreSidebarScroll);
        setTimeout(restoreSidebarScroll, 50);
        setTimeout(restoreSidebarScroll, 150);
    });
})();
</script>

<!-- Mobile Menu Toggle -->
<button class="mobile-menu-toggle no-print" id="mobileMenuToggle" onclick="toggleMobileSidebar()">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
</button>

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleMobileSidebar()"></div>


