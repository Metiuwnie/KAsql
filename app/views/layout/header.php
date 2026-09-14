<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$header_user_role = $_SESSION['user_role'] ?? 'cashier';
$header_is_admin = ($header_user_role === 'admin');
$header_is_accountant = ($header_user_role === 'accountant');
$header_can_view_reports = ($header_is_admin || $header_is_accountant);
?>
<!-- Header Info Card - Searchbar, Tanggal dan Jam -->
<div class="header-info-card">
    <!-- Left Section: Searchbar -->
    <div class="header-search-container">
        <div class="header-search-box" id="headerSearchBox">
            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input 
                type="text" 
                id="headerSearchInput" 
                class="header-search-input" 
                placeholder="Cari kata/kode di tabel atau cari menu..." 
                autocomplete="off"
                aria-label="Cari kata di tabel atau cari menu"
            >
            <div class="search-actions">
                <button type="button" class="search-clear-btn" id="searchClearBtn" title="Bersihkan pencarian" style="display: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
                <kbd class="search-shortcut" title="Tekan Ctrl+K atau / untuk mencari">
                    <span class="shortcut-ctrl">Ctrl + </span><span class="shortcut-key">K</span>
                </kbd>
            </div>
        </div>

        <!-- Search Results Dropdown (Solid Background, Full Width) -->
        <div class="search-results-dropdown" id="searchResultsDropdown" style="display: none;">
            <div class="search-results-header">
                <span class="results-count" id="resultsCount">Pencarian & Filter</span>
                <span class="results-hint">Tekan <kbd>Enter</kbd> untuk filter tabel, atau <kbd>↓</kbd> untuk pilih menu</span>
            </div>
            <div class="search-results-list" id="searchResultsList">
                <!-- Dynamic Items Inserted via JS -->
            </div>
        </div>
    </div>

    <!-- Right Section: DateTime -->
    <div class="datetime-display">
        <div class="date-display" id="current-date">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <span class="date-text">Loading...</span>
        </div>
        <div class="time-display" id="current-time">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            <span class="time-text">--:--:--</span>
        </div>
    </div>
</div>

<?php if ($header_can_view_reports): ?>
<!-- Modal Pengaturan Gemini AI -->
<div class="ai-modal-backdrop" id="aiSettingsModal" style="display: none;">
    <div class="ai-modal-card">
        <div class="ai-modal-header">
            <div class="ai-modal-title">
                <div class="ai-modal-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                </div>
                <div>
                    <h3>Pengaturan Google Gemini AI</h3>
                    <p>Konfigurasi API Key & Model untuk Analisis Finansial Cerdas</p>
                </div>
            </div>
            <button type="button" class="ai-modal-close" id="closeAiModalBtn">&times;</button>
        </div>

        <form id="aiSettingsForm">
            <?= csrf_field() ?>
            <div class="ai-modal-body">
                <div class="ai-form-group">
                    <label for="modalApiKey">Google Gemini API Key</label>
                    <div class="ai-input-wrapper">
                        <input type="password" id="modalApiKey" name="api_key" placeholder="Masukkan Google AI Studio API Key" value="<?= htmlspecialchars($_SESSION['api_key'] ?? '') ?>" autocomplete="off">
                        <button type="button" class="ai-pw-toggle" id="toggleAiKeyVisibility">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    <small style="color: var(--text-muted); font-size: 0.78rem; display: block; margin-top: 4px;">
                        Belum punya API Key? Dapatkan gratis di <a href="https://aistudio.google.com/app/apikey" target="_blank" style="color: var(--primary-color); font-weight: 600;">Google AI Studio</a>.
                    </small>
                </div>

                <div class="ai-form-group">
                    <label for="modalModelChoice">Pilihan Model AI</label>
                    <?php $curr_model = $_SESSION['model_choice'] ?? 'gemini-3.5-flash-lite'; ?>
                    <select id="modalModelChoice" name="model_choice" class="ai-select">
                        <option value="gemini-3.5-flash-lite" <?= $curr_model === 'gemini-3.5-flash-lite' ? 'selected' : '' ?>>Gemini 3.5 Flash Lite (Cepat & Hemat Kuota - Rekomendasi)</option>
                        <option value="gemini-1.5-pro" <?= $curr_model === 'gemini-1.5-pro' ? 'selected' : '' ?>>Gemini 1.5 Pro (Penalaran Kompleks)</option>
                    </select>
                </div>

                <!-- Status Ping Test -->
                <div id="aiTestStatus" style="display: none; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.85rem; margin-top: 0.5rem;"></div>
            </div>

            <div class="ai-modal-footer">
                <button type="button" class="btn btn-secondary" id="btnTestAiConnection" style="display: flex; align-items: center; gap: 0.4rem;">
                    <span>🔌 Uji Koneksi</span>
                </button>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" class="btn btn-outline" id="btnCancelAiModal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveAiSettings">Simpan Pengaturan</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
/* Header Info Card Styles */
.header-info-card {
    background: var(--bg-surface);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-radius: var(--radius-lg);
    padding: 1.1rem 1.75rem;
    margin: 1.5rem 2rem 1.75rem 2rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
    position: relative;
    z-index: 100;
}

.header-info-card:hover {
    box-shadow: var(--shadow-md);
}

/* Search Container & Box */
.header-search-container {
    position: relative;
    flex: 1;
    max-width: 580px;
}

.header-search-box {
    display: flex;
    align-items: center;
    background: #ffffff;
    border: 1.5px solid var(--border-color);
    border-radius: 10px;
    padding: 0.6rem 1rem;
    gap: 0.75rem;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    cursor: text;
}

[data-theme="dark"] .header-search-box {
    background: #111114;
    border-color: rgba(255, 255, 255, 0.08);
}

.header-search-box:focus-within {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.06);
}

[data-theme="dark"] .header-search-box:focus-within {
    box-shadow: 0 0 0 3px rgba(255, 94, 26, 0.2);
}

.search-icon {
    color: var(--text-muted);
    flex-shrink: 0;
    transition: color 0.2s ease;
}

.header-search-box:focus-within .search-icon {
    color: var(--primary-color);
}

.header-search-input {
    width: 100%;
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
    background: transparent !important;
    color: var(--text-main);
    font-size: 0.95rem;
    font-family: inherit;
    font-weight: 500;
    padding: 0 !important;
    margin: 0 !important;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}

.header-search-input:focus,
.header-search-input:focus-visible,
.header-search-input:active {
    outline: none !important;
    border: none !important;
    box-shadow: none !important;
}

.header-search-input::placeholder {
    color: var(--text-muted);
    opacity: 0.8;
}

.search-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.search-clear-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #e2e8f0;
    border: none;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
    padding: 0;
}

[data-theme="dark"] .search-clear-btn {
    background: #27272a;
    color: #e4e4e7;
}

.search-clear-btn:hover {
    background: var(--danger-color);
    color: #ffffff;
}

.search-shortcut {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 5px;
    padding: 3px 7px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-muted);
    box-shadow: 0 1px 1px rgba(0,0,0,0.06);
    user-select: none;
    line-height: 1;
}

[data-theme="dark"] .search-shortcut {
    background: #17171c;
    border-color: rgba(255, 255, 255, 0.1);
    color: #8e8e93;
}

/* Search Dropdown Results — SOLID OPAQUE BACKGROUND & WIDE LAYOUT */
.search-results-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    width: 100%;
    min-width: 540px;
    max-width: 660px;
    background: #ffffff !important;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    box-shadow: 0 20px 35px -5px rgba(0, 0, 0, 0.22), 0 10px 18px -4px rgba(0, 0, 0, 0.12);
    max-height: 440px;
    overflow-y: auto;
    z-index: 99999;
    animation: searchDropdownFade 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

[data-theme="dark"] .search-results-dropdown {
    background: #111114 !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    box-shadow: 0 20px 35px -5px rgba(0, 0, 0, 0.7), 0 10px 18px -4px rgba(0, 0, 0, 0.5);
}

@keyframes searchDropdownFade {
    from { opacity: 0; transform: translateY(-6px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Custom Sleek Scrollbar */
.search-results-dropdown::-webkit-scrollbar {
    width: 6px;
}
.search-results-dropdown::-webkit-scrollbar-track {
    background: transparent;
}
.search-results-dropdown::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
[data-theme="dark"] .search-results-dropdown::-webkit-scrollbar-thumb {
    background: #27272a;
}

.search-results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1.1rem;
    background: #f8fafc;
    border-bottom: 1px solid var(--border-color);
    font-size: 0.8rem;
    color: var(--text-muted);
}

[data-theme="dark"] .search-results-header {
    background: #080809;
}

.results-count {
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--primary-color);
}

.results-hint kbd {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    padding: 2px 5px;
    border-radius: 4px;
    font-size: 0.72rem;
    font-weight: 600;
}

[data-theme="dark"] .results-hint kbd {
    background: #17171c;
    border-color: rgba(255, 255, 255, 0.1);
}

.search-results-list {
    padding: 0.5rem 0.6rem;
}

.search-result-category {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--primary-color);
    padding: 0.75rem 0.8rem 0.35rem 0.8rem;
    letter-spacing: 0.6px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.search-result-category:not(:first-child) {
    margin-top: 0.4rem;
    border-top: 1px solid var(--border-color);
    padding-top: 0.85rem;
}

.search-result-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 0.9rem;
    border-radius: 8px;
    text-decoration: none;
    color: var(--text-main);
    transition: all 0.15s ease;
    cursor: pointer;
    margin-bottom: 2px;
}

.search-result-item:hover,
.search-result-item.selected {
    background: rgba(59, 130, 246, 0.09);
    color: var(--primary-color);
    transform: translateX(4px);
}

[data-theme="dark"] .search-result-item:hover,
[data-theme="dark"] .search-result-item.selected {
    background: rgba(255, 94, 26, 0.12);
    color: var(--primary-color);
}

.search-item-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: #f1f5f9;
    color: var(--primary-color);
    flex-shrink: 0;
    transition: all 0.2s;
}

[data-theme="dark"] .search-item-icon {
    background: #17171c;
}

.search-result-item:hover .search-item-icon,
.search-result-item.selected .search-item-icon {
    background: var(--primary-color);
    color: #ffffff;
}

.search-item-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.search-item-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text-main);
    line-height: 1.3;
}

.search-result-item:hover .search-item-title,
.search-result-item.selected .search-item-title {
    color: var(--primary-color);
}

[data-theme="dark"] .search-result-item:hover .search-item-title,
[data-theme="dark"] .search-result-item.selected .search-item-title {
    color: var(--primary-color);
}

.search-item-desc {
    font-size: 0.8rem;
    color: var(--text-muted);
    line-height: 1.35;
    margin-top: 2px;
}

.search-item-badge {
    font-size: 0.72rem;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 6px;
    background: #f1f5f9;
    color: var(--text-muted);
    border: 1px solid var(--border-color);
    flex-shrink: 0;
}

[data-theme="dark"] .search-item-badge {
    background: #17171c;
    border-color: rgba(255, 255, 255, 0.08);
    color: #8e8e93;
}

.search-result-item.filter-page-action {
    background: rgba(16, 185, 129, 0.08);
    border: 1px dashed rgba(16, 185, 129, 0.4);
}

.search-result-item.filter-page-action:hover,
.search-result-item.filter-page-action.selected {
    background: rgba(16, 185, 129, 0.16);
    color: #059669;
}

[data-theme="dark"] .search-result-item.filter-page-action {
    background: rgba(16, 185, 129, 0.12);
    border-color: rgba(16, 185, 129, 0.3);
}

.search-no-results {
    padding: 2.5rem 1rem;
    text-align: center;
    color: var(--text-muted);
    font-size: 0.9rem;
}

/* Right Section: DateTime */
.datetime-display {
    display: flex;
    gap: 1.75rem;
    align-items: center;
    flex-shrink: 0;
}

.date-display,
.time-display {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    color: var(--text-main);
    font-weight: 500;
    font-size: 0.95rem;
}

.date-display svg,
.time-display svg {
    color: var(--primary-color);
    flex-shrink: 0;
}

.date-text,
.time-text {
    font-family: 'Inter', sans-serif;
}

.time-text {
    font-variant-numeric: tabular-nums;
    min-width: 80px;
    font-weight: 600;
}

/* AI Settings Header Button */
.header-ai-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.45rem 0.9rem;
    border-radius: 8px;
    background: rgba(255, 94, 26, 0.08);
    border: 1px solid rgba(255, 94, 26, 0.25);
    color: var(--primary-color);
    font-size: 0.84rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    font-family: inherit;
}

[data-theme="dark"] .header-ai-btn {
    background: rgba(255, 94, 26, 0.1);
    border-color: rgba(255, 94, 26, 0.35);
    color: #ff5e1a;
}

.header-ai-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255, 94, 26, 0.2);
    background: rgba(255, 94, 26, 0.15);
}

/* AI Modal Backdrop & Card */
.ai-modal-backdrop {
    position: fixed;
    top: 0; left: 0; width: 100vw; height: 100vh;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
}

.ai-modal-card {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 16px;
    width: 100%;
    max-width: 520px;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
    overflow: hidden;
    animation: modalScaleUp 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

[data-theme="dark"] .ai-modal-card {
    background: #111114;
    border-color: rgba(255, 255, 255, 0.1);
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.8);
}

@keyframes modalScaleUp {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

.ai-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    background: #f8fafc;
}

[data-theme="dark"] .ai-modal-header {
    background: #080809;
}

.ai-modal-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.ai-modal-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, #ff5e1a, #f97316);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.ai-modal-title h3 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--text-main);
}

.ai-modal-title p {
    margin: 2px 0 0 0;
    font-size: 0.78rem;
    color: var(--text-muted);
}

.ai-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: var(--text-muted);
    cursor: pointer;
    padding: 4px;
    line-height: 1;
}

.ai-modal-body {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
}

.ai-form-group {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.ai-form-group label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-main);
}

.ai-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.ai-input-wrapper input {
    width: 100%;
    padding: 0.65rem 2.5rem 0.65rem 0.85rem;
    border: 1.5px solid var(--border-color);
    border-radius: 8px;
    background: var(--bg-main);
    color: var(--text-main);
    font-family: inherit;
    font-size: 0.9rem;
}

.ai-input-wrapper input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(255, 94, 26, 0.15);
}

.ai-pw-toggle {
    position: absolute;
    right: 8px;
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 4px;
}

.ai-select {
    width: 100%;
    padding: 0.65rem 0.85rem;
    border: 1.5px solid var(--border-color);
    border-radius: 8px;
    background: var(--bg-main);
    color: var(--text-main);
    font-family: inherit;
    font-size: 0.9rem;
}

.ai-modal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--border-color);
    background: #f8fafc;
}

[data-theme="dark"] .ai-modal-footer {
    background: #080809;
}

/* Responsive */
@media (max-width: 960px) {
    .header-info-card {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
        margin: 1rem 1rem 1.5rem 1rem;
        padding: 1rem;
    }

    .header-search-container {
        max-width: 100%;
    }

    .search-results-dropdown {
        min-width: 100%;
        max-width: 100%;
    }

    .datetime-display {
        justify-content: space-between;
        padding-top: 0.75rem;
        border-top: 1px solid var(--border-color);
        gap: 0.75rem;
    }
    
    .date-display,
    .time-display {
        font-size: 0.88rem;
    }
}

/* Print - Hide header info */
@media print {
    .header-info-card {
        display: none !important;
    }
}
</style>

<script>
// Search, Table Filtering & DateTime functionality
(function() {
    'use strict';

    const baseUrl = '<?= BASE_URL ?>';
    const userRole = '<?= $header_user_role ?>';
    const canViewReports = <?= $header_can_view_reports ? 'true' : 'false' ?>;
    const isAdmin = <?= $header_is_admin ? 'true' : 'false' ?>;

    // Database menu navigasi untuk quick search
    const menuItems = [
        // Beranda & Transaksi
        ...(canViewReports ? [{
            title: 'Dashboard',
            desc: 'Ringkasan keuangan, statistik aset, pendapatan & beban',
            category: 'Beranda',
            url: baseUrl + '/',
            keywords: 'home beranda utama summary grafik chart',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>'
        }] : []),
        {
            title: 'Input Transaksi',
            desc: 'Form pencatatan transaksi jurnal debit & kredit',
            category: 'Transaksi',
            url: baseUrl + '/transaksi/input',
            keywords: 'catat transaksi kasir input jurnal entri baru debit kredit kas penerimaan pengeluaran',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>'
        },
        ...(canViewReports ? [{
            title: 'Transaksi Pending & Verifikasi',
            desc: 'Persetujuan (posting), peninjauan, dan koreksi transaksi',
            category: 'Transaksi',
            url: baseUrl + '/transaksi/pending',
            keywords: 'verifikasi posting pending approve tolak approval koreksi void perbaikan',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>'
        }] : []),

        // Laporan Keuangan
        {
            title: 'Jurnal Umum',
            desc: 'Daftar kronologis seluruh transaksi terverifikasi',
            category: 'Laporan Keuangan',
            url: baseUrl + '/jurnal/umum',
            keywords: 'jurnal umum general journal transaksi harian buku harian',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>'
        },
        ...(canViewReports ? [
            {
                title: 'Jurnal Penyesuaian',
                desc: 'Penyesuaian akrual, beban dibayar di muka, dan penyusutan',
                category: 'Laporan Keuangan',
                url: baseUrl + '/jurnal/penyesuaian',
                keywords: 'penyesuaian adjusting journal akrual depresiasi prepaid amortisasi',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>'
            },
            {
                title: 'Jurnal Pembalik',
                desc: 'Otomasi dan eksekusi pembalik akun akrual awal periode',
                category: 'Laporan Keuangan',
                url: baseUrl + '/jurnal/pembalik',
                keywords: 'pembalik reversing journal balik otomatis awal bulan',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 .49-3.51"></path></svg>'
            },
            {
                title: 'Buku Besar (General Ledger)',
                desc: 'Rincian mutasi dan saldo per akun',
                category: 'Laporan Keuangan',
                url: baseUrl + '/laporan/buku_besar',
                keywords: 'buku besar ledger kartu akun mutasi debit kredit saldo',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>'
            },
            {
                title: 'Neraca Saldo (Trial Balance)',
                desc: 'Daftar saldo awal, mutasi, dan saldo akhir seluruh akun',
                category: 'Laporan Keuangan',
                url: baseUrl + '/laporan/neraca_saldo',
                keywords: 'neraca saldo trial balance keseimbangan balance debet kredit',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>'
            },
            {
                title: 'Laporan Laba Rugi (Income Statement)',
                desc: 'Pendapatan, beban operasional, dan laba bersih',
                category: 'Laporan Keuangan',
                url: baseUrl + '/laporan/laba_rugi',
                keywords: 'laba rugi income statement profit loss pendapatan revenue beban biaya profitabilitas',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>'
            },
            {
                title: 'Laporan Neraca (Balance Sheet)',
                desc: 'Posisi keuangan aktiva lancar, tetap, utang, dan ekuitas modal',
                category: 'Laporan Keuangan',
                url: baseUrl + '/laporan/neraca',
                keywords: 'neraca balance sheet aktiva pasiva harta kewajiban modal ekuitas aset posisi keuangan',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>'
            }
        ] : []),

        // Aset & Periode
        ...(canViewReports ? [
            {
                title: 'Aset Tetap & Depresiasi',
                desc: 'Manajemen aset berwujud dan perhitungan penyusutan otomatis',
                category: 'Aset & Periode',
                url: baseUrl + '/aset/tetap',
                keywords: 'aset tetap fixed asset depresiasi penyusutan umur ekonomis residu peralatan gedung',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>'
            },
            {
                title: 'Prepaid Expense (Beban Dimuka)',
                desc: 'Jadwal pembebanan berkala asuransi/sewa dibayar di muka',
                category: 'Aset & Periode',
                url: baseUrl + '/aset/prepaid',
                keywords: 'prepaid expense beban dibayar dimuka sewa asuransi per bulan amortisasi',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>'
            },
            {
                title: 'Closing Periode Akuntansi',
                desc: 'Tutup buku periode bulanan/tahunan dan penguncian saldo',
                category: 'Aset & Periode',
                url: baseUrl + '/closing',
                keywords: 'closing tutup buku periode bulanan kunci periode lock snapshot saldo',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>'
            }
        ] : []),

        // Master Data
        ...(canViewReports ? [
            {
                title: 'Data Akun (Chart of Accounts)',
                desc: 'Pengaturan daftar kode akun dan kategori neraca',
                category: 'Master Data',
                url: baseUrl + '/master/akun',
                keywords: 'master akun chart of accounts coa kode akun daftar akun aktiva pasiva',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>'
            },
            {
                title: 'Jenis & Mapping Reaksi',
                desc: 'Template otomatisasi transaksi debit kredit siap pakai',
                category: 'Master Data',
                url: baseUrl + '/master/reaksi',
                keywords: 'reaksi aktivitas template transaksi mapping relasi detail reaksi otomatis',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>'
            }
        ] : []),
        ...(isAdmin ? [{
            title: 'Manajemen User',
            desc: 'Kelola pengguna, kata sandi, dan hak akses sistem',
            category: 'Master Data',
            url: baseUrl + '/master/user',
            keywords: 'user akun pengguna kasir akuntan admin hak akses password kelola user',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>'
        }] : []),

        // Analisis AI
        ...(canViewReports ? [
            {
                title: 'Analisis AI: Jurnal Umum',
                desc: 'Audit kepatuhan dan rekomendasi cerdas data jurnal',
                category: 'Analisis AI Gemini',
                url: baseUrl + '/analisis/jurnal',
                keywords: 'ai analisis jurnal audit cerdas gemini rekomendasi anomali',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"></path><path d="M12 6v6l4 2"></path></svg>'
            },
            {
                title: 'Analisis AI: Laba Rugi',
                desc: 'Evaluasi profitabilitas, margin laba, dan efisiensi biaya',
                category: 'Analisis AI Gemini',
                url: baseUrl + '/analisis/laba_rugi',
                keywords: 'ai analisis laba rugi margin efisiensi biaya tren pendapatan evaluasi',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>'
            },
            {
                title: 'Analisis AI: Neraca',
                desc: 'Analisis rasio likuiditas, solvabilitas, dan kesehatan aset',
                category: 'Analisis AI Gemini',
                url: baseUrl + '/analisis/neraca',
                keywords: 'ai analisis neraca rasio likuiditas solvabilitas kesehatan keuangan struktur modal',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>'
            }
        ] : [])
    ];

    let selectedIndex = -1;
    let filteredItems = [];

    // Search Elements
    const searchInput = document.getElementById('headerSearchInput');
    const searchDropdown = document.getElementById('searchResultsDropdown');
    const searchResultsList = document.getElementById('searchResultsList');
    const resultsCount = document.getElementById('resultsCount');
    const searchClearBtn = document.getElementById('searchClearBtn');

    // ========================================================
    // IN-PAGE LIVE TABLE & CONTENT FILTER FUNCTION (WITH TRANSACTION GROUPING)
    // ========================================================
    function filterPageContent(query) {
        query = (query || '').trim().toLowerCase();
        
        let totalMatched = 0;
        let totalRows = 0;

        // 1. Filter all standard tables on the page
        const tables = document.querySelectorAll('table tbody');
        tables.forEach(tbody => {
            const allRows = Array.from(tbody.querySelectorAll('tr'));
            if (allRows.length === 0) return;

            // Group related multi-row transactions (e.g. Debit + Credit in Jurnal Umum, or Master-Detail)
            const groups = [];
            let currentGroup = [];

            allRows.forEach(row => {
                // Check if total or summary row with colspan/font-bold
                const isTotalRow = row.classList.contains('font-bold') || 
                                   row.classList.contains('no-filter') || 
                                   (row.textContent.toLowerCase().includes('total') && (row.querySelector('td[colspan]') || row.style.fontWeight === 'bold'));

                if (isTotalRow) {
                    if (currentGroup.length > 0) {
                        groups.push({ rows: currentGroup, isTotal: false });
                        currentGroup = [];
                    }
                    groups.push({ rows: [row], isTotal: true });
                    return;
                }

                // Check if this row is a continuation of the same transaction:
                // Case A: Expandable detail row (e.g. class "detail-row" in pending.php)
                const isDetailRow = row.classList.contains('detail-row');
                
                // Case B: Grouped journal entry row (in Jurnal Umum, cells 0 and 1 are empty for credit lines)
                const isGroupedContinuation = row.cells.length >= 4 && 
                                              currentGroup.length > 0 && 
                                              (row.cells[0]?.textContent.trim() === '' && row.cells[1]?.textContent.trim() === '');

                if ((isDetailRow || isGroupedContinuation) && currentGroup.length > 0) {
                    currentGroup.push(row);
                } else {
                    if (currentGroup.length > 0) {
                        groups.push({ rows: currentGroup, isTotal: false });
                    }
                    currentGroup = [row];
                }
            });

            if (currentGroup.length > 0) {
                groups.push({ rows: currentGroup, isTotal: false });
            }

            // Apply filter per group
            groups.forEach(group => {
                if (group.isTotal) {
                    group.rows[0].style.display = '';
                    return;
                }

                // Check if any row in the group matches query
                const groupText = group.rows.map(r => r.textContent).join(' ').toLowerCase();
                const isMatch = !query || groupText.includes(query);

                group.rows.forEach(r => {
                    r.style.display = isMatch ? '' : 'none';
                });

                if (isMatch) totalMatched++;
                totalRows++;
            });
        });

        // 2. Filter card containers (e.g. prepaid items, asset cards)
        const cardContainers = document.querySelectorAll('.prepaid-list, .cards-grid');
        cardContainers.forEach(container => {
            const cards = container.querySelectorAll('.card');
            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                if (!query || text.includes(query)) {
                    card.style.display = '';
                    totalMatched++;
                } else {
                    card.style.display = 'none';
                }
                totalRows++;
            });
        });

        return { matched: totalMatched, total: totalRows };
    }

    // ========================================================
    // FILTER RESULTS IN DROPDOWN
    // ========================================================
    function filterResults(query) {
        const trimmed = query.trim().toLowerCase();
        
        // Execute in-page filter live
        const pageStat = filterPageContent(trimmed);

        if (!trimmed) {
            filteredItems = menuItems.slice(0, 8); // Tampilkan item utama secara default
            renderResults(filteredItems, 'Pintasan Menu Utama', trimmed, pageStat);
            return;
        }

        filteredItems = menuItems.filter(item => {
            return item.title.toLowerCase().includes(trimmed) ||
                   item.desc.toLowerCase().includes(trimmed) ||
                   item.category.toLowerCase().includes(trimmed) ||
                   item.keywords.toLowerCase().includes(trimmed);
        });

        renderResults(filteredItems, `${filteredItems.length} Menu Cocok`, trimmed, pageStat);
    }

    // Render Results HTML
    function renderResults(items, countLabel, query, pageStat) {
        selectedIndex = -1;
        resultsCount.textContent = countLabel;

        let html = '';
        let itemCounter = 0;

        // Action Item: Filter Current Page
        if (query) {
            const matchText = pageStat && pageStat.total > 0 ? 
                `Menyaring <strong>${pageStat.matched}</strong> dari ${pageStat.total} baris di halaman ini` : 
                `Tekan Enter untuk menyaring kata "<strong>${escapeHtml(query)}</strong>" di tabel halaman ini`;

            html += `
                <div class="search-result-category">Filter Halaman Aktif</div>
                <div class="search-result-item filter-page-action" data-action="filter-page" data-index="${itemCounter}">
                    <div class="search-item-icon" style="background: rgba(16, 185, 129, 0.15); color: #059669;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                    </div>
                    <div class="search-item-info">
                        <span class="search-item-title">Filter di Halaman Ini: "<strong>${escapeHtml(query)}</strong>"</span>
                        <span class="search-item-desc">${matchText}</span>
                    </div>
                    <span class="search-item-badge" style="background: #d1fae5; color: #065f46; border-color: #a7f3d0;">Filter Aktif</span>
                </div>
            `;
            itemCounter++;
        }

        if (items.length > 0) {
            // Group by category
            const groups = {};
            items.forEach((item, index) => {
                if (!groups[item.category]) groups[item.category] = [];
                groups[item.category].push(item);
            });

            for (const [category, groupItems] of Object.entries(groups)) {
                html += `<div class="search-result-category">${escapeHtml(category)}</div>`;
                groupItems.forEach(item => {
                    html += `
                        <a href="${item.url}" class="search-result-item" data-action="navigate" data-index="${itemCounter}">
                            <div class="search-item-icon">${item.icon}</div>
                            <div class="search-item-info">
                                <span class="search-item-title">${escapeHtml(item.title)}</span>
                                <span class="search-item-desc">${escapeHtml(item.desc)}</span>
                            </div>
                            <span class="search-item-badge">${escapeHtml(item.category)}</span>
                        </a>
                    `;
                    itemCounter++;
                });
            }
        } else if (!query) {
            html += `
                <div class="search-no-results">
                    <div>Ketik nama transaksi, kode akun, atau fitur untuk mencari...</div>
                </div>
            `;
        }

        searchResultsList.innerHTML = html;
        searchDropdown.style.display = 'block';

        // Attach click to filter-page action
        const filterPageBtn = searchResultsList.querySelector('.filter-page-action');
        if (filterPageBtn) {
            filterPageBtn.addEventListener('click', function(e) {
                e.preventDefault();
                filterPageContent(searchInput.value);
                searchDropdown.style.display = 'none';
            });
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Update Selection Visual
    function updateSelection() {
        const items = searchResultsList.querySelectorAll('.search-result-item');
        items.forEach((el, idx) => {
            if (idx === selectedIndex) {
                el.classList.add('selected');
                el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } else {
                el.classList.remove('selected');
            }
        });
    }

    // Event Listeners for Search
    if (searchInput) {
        searchInput.addEventListener('focus', function() {
            filterResults(this.value);
            if (this.value) searchClearBtn.style.display = 'flex';
        });

        searchInput.addEventListener('input', function() {
            filterResults(this.value);
            searchClearBtn.style.display = this.value ? 'flex' : 'none';
        });

        searchInput.addEventListener('keydown', function(e) {
            const items = searchResultsList.querySelectorAll('.search-result-item');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (items.length > 0) {
                    selectedIndex = (selectedIndex + 1) % items.length;
                    updateSelection();
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (items.length > 0) {
                    selectedIndex = (selectedIndex - 1 + items.length) % items.length;
                    updateSelection();
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                
                // JIKA USER MEMILIH MENU TERTENTU VIA ARROW KEY (selectedIndex >= 0):
                if (selectedIndex >= 0 && items[selectedIndex]) {
                    items[selectedIndex].click();
                } else {
                    // JIKA USER HANYA MENGETIK KATA/KODE DAN TEKAN ENTER:
                    // JALANKAN FILTER DI HALAMAN INI DAN TUTUP DROPDOWN (JANGAN PINDAH HALAMAN!)
                    filterPageContent(this.value);
                    searchDropdown.style.display = 'none';
                }
            } else if (e.key === 'Escape') {
                searchDropdown.style.display = 'none';
                searchInput.blur();
                // Jika escape ditekan saat kosong, kembalikan tabel
                if (!this.value) {
                    filterPageContent('');
                }
            }
        });
    }

    if (searchClearBtn) {
        searchClearBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            searchInput.value = '';
            searchInput.focus();
            filterPageContent('');
            filterResults('');
            searchClearBtn.style.display = 'none';
        });
    }

    // Close Dropdown on click outside
    document.addEventListener('click', function(e) {
        const container = document.getElementById('headerSearchBox');
        if (container && !container.contains(e.target) && searchDropdown && !searchDropdown.contains(e.target)) {
            searchDropdown.style.display = 'none';
        }
    });

    // Global Shortcut: Ctrl+K or / to focus search
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
            e.preventDefault();
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        } else if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
            e.preventDefault();
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
    });

    // ========================================================
    // GEMINI AI SETTINGS MODAL & PING TEST
    // ========================================================
    const openAiBtn = document.getElementById('openAiModalBtn');
    const aiModal = document.getElementById('aiSettingsModal');
    const closeAiBtn = document.getElementById('closeAiModalBtn');
    const cancelAiBtn = document.getElementById('btnCancelAiModal');
    const aiForm = document.getElementById('aiSettingsForm');
    const btnTestAi = document.getElementById('btnTestAiConnection');
    const aiTestStatus = document.getElementById('aiTestStatus');
    const toggleKeyBtn = document.getElementById('toggleAiKeyVisibility');
    const apiKeyInput = document.getElementById('modalApiKey');

    if (aiModal) {
        window.openAiModal = () => {
            aiModal.style.display = 'flex';
            if (aiTestStatus) aiTestStatus.style.display = 'none';
        };

        if (openAiBtn) {
            openAiBtn.addEventListener('click', window.openAiModal);
        }

        const closeAiModal = () => { aiModal.style.display = 'none'; };
        if (closeAiBtn) closeAiBtn.addEventListener('click', closeAiModal);
        if (cancelAiBtn) cancelAiBtn.addEventListener('click', closeAiModal);

        aiModal.addEventListener('click', (e) => {
            if (e.target === aiModal) closeAiModal();
        });

        if (toggleKeyBtn && apiKeyInput) {
            toggleKeyBtn.addEventListener('click', () => {
                apiKeyInput.type = (apiKeyInput.type === 'password') ? 'text' : 'password';
            });
        }

        // Test Connection (Ping)
        if (btnTestAi) {
            btnTestAi.addEventListener('click', async () => {
                const key = apiKeyInput.value.trim();
                const model = document.getElementById('modalModelChoice').value;
                if (!key) {
                    aiTestStatus.style.display = 'block';
                    aiTestStatus.style.background = 'rgba(239, 68, 68, 0.15)';
                    aiTestStatus.style.color = 'var(--danger-color)';
                    aiTestStatus.textContent = 'Harap isi API Key terlebih dahulu.';
                    return;
                }

                btnTestAi.disabled = true;
                btnTestAi.innerHTML = '<span>Menguji...</span>';
                aiTestStatus.style.display = 'block';
                aiTestStatus.style.background = 'rgba(59, 130, 246, 0.1)';
                aiTestStatus.style.color = 'var(--primary-color)';
                aiTestStatus.textContent = 'Menghubungkan ke Google Gemini API...';

                try {
                    const formData = new FormData();
                    formData.append('api_key', key);
                    formData.append('model_choice', model);

                    const res = await fetch(baseUrl + '/analisis/test_connection', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();

                    if (data.status === 'success') {
                        aiTestStatus.style.background = 'rgba(16, 185, 129, 0.15)';
                        aiTestStatus.style.color = '#059669';
                        aiTestStatus.innerHTML = '<strong>✓ ' + escapeHtml(data.message) + '</strong>';
                    } else {
                        aiTestStatus.style.background = 'rgba(239, 68, 68, 0.15)';
                        aiTestStatus.style.color = 'var(--danger-color)';
                        aiTestStatus.innerHTML = '<strong>✕ ' + escapeHtml(data.message) + '</strong>';
                    }
                } catch (err) {
                    aiTestStatus.style.background = 'rgba(239, 68, 68, 0.15)';
                    aiTestStatus.style.color = 'var(--danger-color)';
                    aiTestStatus.textContent = 'Gagal menghubungi endpoint: ' + err.message;
                } finally {
                    btnTestAi.disabled = false;
                    btnTestAi.innerHTML = '<span>🔌 Uji Koneksi</span>';
                }
            });
        }

        // Save Settings Form
        if (aiForm) {
            aiForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const saveBtn = document.getElementById('btnSaveAiSettings');
                saveBtn.disabled = true;
                saveBtn.textContent = 'Menyimpan...';

                try {
                    const formData = new FormData(aiForm);
                    formData.append('ajax', '1');

                    const res = await fetch(baseUrl + '/analisis/save_settings', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();

                    if (data.status === 'success') {
                        aiTestStatus.style.display = 'block';
                        aiTestStatus.style.background = 'rgba(16, 185, 129, 0.15)';
                        aiTestStatus.style.color = '#059669';
                        aiTestStatus.textContent = '✓ Pengaturan AI berhasil disimpan!';
                        setTimeout(() => {
                            closeAiModal();
                        }, 900);
                    }
                } catch (err) {
                    alert('Gagal menyimpan: ' + err.message);
                } finally {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Simpan Pengaturan';
                }
            });
        }
    }

    // DateTime functionality
    function updateDateTime() {
        const now = new Date();
        
        // Format date: Sabtu, 9 Mei 2026
        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                       'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        
        const dayName = days[now.getDay()];
        const day = now.getDate();
        const month = months[now.getMonth()];
        const year = now.getFullYear();
        
        const dateText = `${dayName}, ${day} ${month} ${year}`;
        
        // Format time: 10:30:45
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        
        const timeText = `${hours}:${minutes}:${seconds}`;
        
        // Update DOM
        const dateElement = document.querySelector('.date-text');
        const timeElement = document.querySelector('.time-text');
        
        if (dateElement) dateElement.textContent = dateText;
        if (timeElement) timeElement.textContent = timeText;
    }
    
    // Initialize DateTime
    updateDateTime();
    setInterval(updateDateTime, 1000);
})();
</script>
