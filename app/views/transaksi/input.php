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
    <script>
        function loadAkun() {
            var sel = document.getElementById('id_reaksi');
            var val = sel.value;
            window.location.href = '<?= BASE_URL ?>/transaksi/input?id_reaksi=' + encodeURIComponent(val);
        }

        function formatRupiahInput(input) {
            let cursorPosition = input.selectionStart;
            let originalLength = input.value.length;
            
            // Bersihkan selain angka
            let cleanVal = input.value.replace(/\D/g, '');
            
            if (!cleanVal) {
                input.value = '';
                updateLiveBalance();
                return;
            }
            
            // Format titik ribuan Indonesia (contoh: 12.500.000)
            let formatted = new Intl.NumberFormat('id-ID').format(cleanVal);
            input.value = formatted;
            
            // Pertahankan posisi kursor saat mengetik
            let newLength = formatted.length;
            cursorPosition = cursorPosition + (newLength - originalLength);
            input.setSelectionRange(cursorPosition, cursorPosition);
            
            updateLiveBalance();
        }

        function updateLiveBalance() {
            let totalDebit = 0;
            let totalKredit = 0;
            const rows = document.querySelectorAll('.akun-row');
            rows.forEach(r => {
                const dkEl = r.querySelector('select[name="dk[]"]');
                const rpEl = r.querySelector('input[name="rupiah[]"]');
                if (!dkEl || !rpEl) return;
                
                const dk = dkEl.value;
                const rawVal = rpEl.value.replace(/\D/g, '');
                const num = parseFloat(rawVal) || 0;
                
                if (dk.toLowerCase() === 'debit') {
                    totalDebit += num;
                } else {
                    totalKredit += num;
                }
            });
            
            const selisih = Math.abs(totalDebit - totalKredit);
            const isBalanced = totalDebit > 0 && selisih === 0;
            
            const debitEl = document.getElementById('live-total-debit');
            const kreditEl = document.getElementById('live-total-kredit');
            const statusEl = document.getElementById('live-balance-status');
            
            if (debitEl && kreditEl && statusEl) {
                debitEl.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(totalDebit);
                kreditEl.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(totalKredit);
                
                if (totalDebit === 0 && totalKredit === 0) {
                    statusEl.innerHTML = '<span style="color: var(--text-muted); font-size: 0.9rem;">Masukkan nominal transaksi</span>';
                } else if (isBalanced) {
                    statusEl.innerHTML = '<span style="color: var(--success-color); font-weight: 700; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 5px;">' +
                        '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>' +
                        'Seimbang (Balance)</span>';
                } else {
                    statusEl.innerHTML = '<span style="color: var(--warning-color); font-weight: 700; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 5px;">' +
                        '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>' +
                        'Selisih: Rp ' + new Intl.NumberFormat('id-ID').format(selisih) + '</span>';
                }
            }
        }

        function addRow() {
            var container = document.getElementById('akun-container');
            var firstRow = document.querySelector('.akun-row').cloneNode(true);
            
            // clear values
            firstRow.querySelector('select[name="akun[]"]').value = '';
            firstRow.querySelector('select[name="dk[]"]').value = 'Debit';
            firstRow.querySelector('input[name="rupiah[]"]').value = '';
            
            // Hubungkan event change ke update balance
            firstRow.querySelector('select[name="dk[]"]').onchange = updateLiveBalance;
            
            container.appendChild(firstRow);
            updateLiveBalance();
        }

        function removeRow(btn) {
            var rows = document.querySelectorAll('.akun-row');
            if (rows.length <= 2) {
                alert('Transaksi minimal memerlukan 2 baris akun (Debit dan Kredit).');
                return;
            }
            btn.closest('.akun-row').remove();
            updateLiveBalance();
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('input[name="rupiah[]"]').forEach(input => {
                if (input.value) {
                    formatRupiahInput(input);
                }
            });
            document.querySelectorAll('select[name="dk[]"]').forEach(sel => {
                sel.addEventListener('change', updateLiveBalance);
            });
            updateLiveBalance();
        });
    </script>
</head>
<body>

<?php include __DIR__ . '/../layout/sidebar.php'; ?>


<div class="main-content">
    <?php include __DIR__ . '/../layout/header.php'; ?>
<div class="container">

    <?php if ($data['is_koreksi'] && $data['koreksi_header']): ?>
    <h2 class="page-title">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px; color: var(--warning-color);">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
        </svg>
        Koreksi Transaksi — <?= htmlspecialchars($data['koreksi_dari']) ?>
    </h2>
    <div class="alert" style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); color: var(--text-main); border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1.5rem;">
        <strong style="color: #d97706;">⚠️ Mode Koreksi</strong> — Anda sedang mengoreksi transaksi <strong><?= htmlspecialchars($data['koreksi_dari']) ?></strong> (<?= htmlspecialchars($data['koreksi_header']['deskripsi']) ?>). 
        Data di bawah sudah diisi dari transaksi asli. Setelah disimpan, transaksi asli akan ditandai sebagai "koreksi" dan transaksi baru akan dibuat dengan status "pending".
    </div>
    <?php else: ?>
    <h2 class="page-title">Input Transaksi Jurnal Umum</h2>
    <?php endif; ?>

    <?php if($data['error']): ?>
        <div style="background-color: #fee2e2; border: 1px solid #ef4444; color: #b91c1c; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; box-shadow: 0 4px 6px -1px rgba(239,68,68,0.1);">
            <?= htmlspecialchars($data['error']) ?>
        </div>
    <?php endif; ?>
    <?php if($data['success']): ?>
        <div style="background-color: #d1fae5; border: 1px solid #10b981; color: #047857; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; box-shadow: 0 4px 6px -1px rgba(16,185,129,0.1);">
            <?= htmlspecialchars($data['success']) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <!-- Hidden Field -->
    <form method="POST" action="<?= BASE_URL ?>/transaksi/input" onsubmit="const btn = this.querySelector('button[type=submit]'); btn.innerText='Sedang Menyimpan...'; btn.style.opacity='0.7'; btn.style.pointerEvents='none';">
        <?= csrf_field() ?>
        <input type="hidden" name="submit_transaksi" value="1">
        <?php if ($data['is_koreksi']): ?>
        <input type="hidden" name="koreksi_dari" value="<?= htmlspecialchars($data['koreksi_dari']) ?>">
        <?php endif; ?>
        
        <?php if (!$data['is_koreksi']): ?>
        <div class="form-group">
            <label>Jenis Aktivitas</label>
            <select name="aktivitas_id" id="id_reaksi" class="form-control" onchange="loadAkun()">
                <option value="">-- Pilih Jenis / Transaksi Kustom --</option>
                <?php foreach($data['reaksi_opts'] as $opt): ?>
                    <option value="<?= htmlspecialchars($opt['id_reaksi']) ?>" <?= ($data['selected_reaksi'] == $opt['id_reaksi']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($opt['nama_reaksi']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label>Tanggal</label>
            <input type="date" name="tanggal" required class="form-control" value="<?= $data['is_koreksi'] && $data['koreksi_header'] ? htmlspecialchars($data['koreksi_header']['tanggal']) : date('Y-m-d') ?>">
        </div>

        <div class="form-group">
            <label>Deskripsi Transaksi</label>
            <input type="text" name="deskripsi" required class="form-control" value="<?= $data['is_koreksi'] && $data['koreksi_header'] ? htmlspecialchars($data['koreksi_header']['deskripsi']) : '' ?>">
        </div>

        <div id="akun-container">
        <?php for($i = 0; $i < $data['baris_tampil']; $i++): 
            $def_kode_akun = '';
            $def_dk = '';
            $def_nilai = '';
            
            // Koreksi mode: pre-fill from original transaction
            if ($data['is_koreksi'] && isset($data['koreksi_data'][$i])) {
                $def_kode_akun = $data['koreksi_data'][$i]['kode_akun'];
                $def_dk = $data['koreksi_data'][$i]['dk'];
                $def_nilai = $data['koreksi_data'][$i]['nilai'] > 0 ? number_format($data['koreksi_data'][$i]['nilai'], 0, ',', '.') : '';
            }
            // Reaksi template mode
            else if (isset($data['loaded_details'][$i])) {
                $def_kode_akun = $data['loaded_details'][$i]['kode_akun'];
                $def_dk = $data['loaded_details'][$i]['dk'];
            }
        ?>
        <div class="akun-row" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center; background-color: var(--bg-surface-hover); padding: 10px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
            <select name="akun[]" class="form-control" style="flex: 2; margin-bottom: 0;">
                <option value="">-- Pilih Akun --</option>
                <?php foreach($data['akun_opts'] as $opt): ?>
                    <option value="<?= htmlspecialchars($opt['kode_akun']) ?>" <?= ($def_kode_akun == $opt['kode_akun']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($opt['kode_akun'] . ' - ' . $opt['akun']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="dk[]" class="form-control" style="flex: 1; margin-bottom: 0;">
                <option value="Debit" <?= (strtolower($def_dk) == 'debit') ? 'selected' : '' ?>>Debit</option>
                <option value="Kredit" <?= (strtolower($def_dk) == 'kredit') ? 'selected' : '' ?>>Kredit</option>
            </select>
            
            <div class="input-group" style="flex: 1.5; display: flex; align-items: center;">
                <span style="padding: 0 10px; background-color: var(--border-color); border-radius: var(--radius-sm) 0 0 var(--radius-sm); border: 1px solid var(--border-color); height: 100%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.9rem;">Rp</span>
                <input type="text" name="rupiah[]" class="form-control rupiah-input" placeholder="0" value="<?= htmlspecialchars($def_nilai) ?>" oninput="formatRupiahInput(this)" autocomplete="off" style="border-radius: 0 var(--radius-sm) var(--radius-sm) 0; margin-bottom: 0;">
            </div>

            <button type="button" class="btn-del-row" onclick="removeRow(this)" title="Hapus Baris" style="background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 8px; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.color='var(--danger-color)'; this.style.backgroundColor='rgba(239, 68, 68, 0.1)';" onmouseout="this.style.color='var(--text-muted)'; this.style.backgroundColor='transparent';">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <?php endfor; ?>
        </div>
        
        <div style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">
            <button type="button" class="btn btn-secondary" onclick="addRow()" style="padding: 0.5rem 1rem; display: inline-flex; align-items: center; gap: 6px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Tambah Baris Akun
            </button>
        </div>

        <!-- LIVE BALANCE INDICATOR -->
        <div id="live-balance-box" style="margin-top: 18px; padding: 14px 20px; border-radius: var(--radius-sm); background: rgba(125, 125, 125, 0.05); border: 1px solid var(--border-color); display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 15px;">
            <div style="display: flex; gap: 20px; align-items: center; font-size: 0.95rem;">
                <div>Total Debit: <strong id="live-total-debit" style="color: var(--primary-color);">Rp 0</strong></div>
                <div style="color: var(--border-color);">|</div>
                <div>Total Kredit: <strong id="live-total-kredit" style="color: var(--primary-color);">Rp 0</strong></div>
            </div>
            <div id="live-balance-status" style="font-size: 0.9rem;">
                <span style="color: var(--text-muted);">Masukkan nominal transaksi</span>
            </div>
        </div>

        <div style="margin-top: 30px; border-top: 1px solid var(--border-color); padding-top: 25px; display: flex; gap: 15px; align-items: center; justify-content: space-between;">
            <?php if ($data['is_koreksi']): ?>
            <a href="<?= BASE_URL ?>/transaksi/pending" style="display: inline-flex; align-items: center; gap: 6px; color: var(--text-muted); text-decoration: none; padding: 12px 20px; border: 1px solid var(--border-color); border-radius: 8px; font-weight: 600; background: var(--bg-surface-hover); transition: all 0.2s;" onmouseover="this.style.color='var(--primary-color)'; this.style.borderColor='var(--primary-color)';" onmouseout="this.style.color='var(--text-muted)'; this.style.borderColor='var(--border-color)';">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Batal & Kembali
            </a>
            <button type="submit" name="submit_transaksi" class="btn btn-primary" style="flex: 1; font-size: 1.1rem; padding: 12px; background: linear-gradient(135deg, #d97706, #f59e0b); border: none; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3); display: flex; justify-content: center; align-items: center; gap: 8px; font-weight: 700; letter-spacing: 0.5px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                Simpan Koreksi Transaksi
            </button>
            <?php else: ?>
            <button type="submit" name="submit_transaksi" class="btn btn-primary" style="width: 100%; font-size: 1.1rem; padding: 12px; display: flex; justify-content: center; align-items: center; gap: 8px; font-weight: 700;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                Simpan Transaksi Jurnal
            </button>
            <?php endif; ?>
        </div>
        
    </form>
    </div>
</div>

</div>
</body>
</html>


