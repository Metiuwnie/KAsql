<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data['title'] ?></title>
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css?v=<?= time() ?>">
    <script src="<?= BASE_URL ?>/public/js/script.js?v=2.0" defer></script>
    <style>
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            color: #fff;
        }
        .status-done { background-color: var(--success-color); }
        .status-pending { background-color: var(--danger-color); }
    </style>
</head>
<body>
<?php include __DIR__ . '/../layout/sidebar.php'; ?>

<div class="main-content">
    <?php include __DIR__ . '/../layout/header.php'; ?>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-color); margin-bottom: 2rem; padding-bottom: 1rem;">
            <h2 style="border: none; margin: 0; padding: 0;" class="page-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                </svg>
                Manajemen Aset Tetap (Depresiasi)
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

        <div class="row" style="display: flex; gap: 20px; flex-wrap: wrap;">
            
            <!-- FORM -->
            <div class="col" style="flex: 1; min-width: 350px;">
                <div class="card">
                    <h3 style="margin-top: 0; margin-bottom: 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Tambah Aset Baru</h3>
                    <form method="POST" action="<?= BASE_URL ?>/aset/tetap">
                        <?= csrf_field() ?>
                        <input type="hidden" name="submit_aset" value="1">
                        
                        <div class="form-group">
                            <label class="form-label">Nama Aset (Contoh: Mesin Produksi)</label>
                            <input type="text" name="nama_aset" class="form-control" required autocomplete="off">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Harga Perolehan (Rp)</label>
                            <input type="text" name="harga_perolehan" id="harga_perolehan" class="form-control" required autocomplete="off" onkeyup="this.value=this.value.replace(/[^\d]/g,''); formatRupiahInput(this); hitungPenyusutan()">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nilai Residu / Sisa (Rp) - Opsional</label>
                            <input type="text" name="nilai_residu" id="nilai_residu" class="form-control" autocomplete="off" onkeyup="this.value=this.value.replace(/[^\d]/g,''); formatRupiahInput(this); hitungPenyusutan()">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Umur Ekonomis</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="number" name="umur_tahun" id="umur_tahun" class="form-control" placeholder="Tahun" min="0" oninput="hitungPenyusutan()">
                                <input type="number" name="umur_bulan" id="umur_bulan" class="form-control" placeholder="Bulan (opsional)" min="0" max="11" oninput="hitungPenyusutan()">
                            </div>
                            <small style="color: var(--text-muted); display: block; margin-top: 5px;">*Tahun wajib diisi (minimal 0 jika aset sangat singkat), bulan opsional.</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Penyusutan per Bulan (Otomatis)</label>
                            <input type="text" id="penyusutan_per_bulan_display" class="form-control" readonly style="background-color: var(--bg-surface-hover); font-weight: bold; color: var(--primary-color);">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Akun Aset (Debet awal)</label>
                            <select name="akun_aset" class="form-control" required>
                                <option value="">-- Pilih Akun Aset Tetap --</option>
                                <?php foreach($data['akun_list'] as $a): if($a['kategori_neraca'] == 'Aktiva Tetap' || $a['kategori_neraca'] == 'Aktiva Lancar'): ?>
                                    <option value="<?= $a['kode_akun'] ?>"><?= $a['kode_akun'] ?> - <?= htmlspecialchars($a['akun']) ?></option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Akun Akumulasi Penyusutan (Kredit saat disusutkan)</label>
                            <select name="akun_akumulasi" class="form-control" required>
                                <option value="">-- Pilih Akun Akumulasi --</option>
                                <?php foreach($data['akun_list'] as $a): if($a['kategori_neraca'] == 'Aktiva Tetap'): ?>
                                    <option value="<?= $a['kode_akun'] ?>"><?= $a['kode_akun'] ?> - <?= htmlspecialchars($a['akun']) ?></option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Akun Beban (Debet saat disusutkan)</label>
                            <select name="akun_beban" class="form-control" required>
                                <option value="">-- Pilih Akun Beban --</option>
                                <?php foreach($data['akun_list'] as $a): if($a['kategori_neraca'] == 'Beban'): ?>
                                    <option value="<?= $a['kode_akun'] ?>"><?= $a['kode_akun'] ?> - <?= htmlspecialchars($a['akun']) ?></option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%;">Simpan Aset Baru</button>
                    </form>
                </div>
            </div>
            
            <!-- TABEL -->
            <div class="col" style="flex: 2; min-width: 500px;">
                <div class="card">
                    <h3 style="margin-top: 0; margin-bottom: 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Daftar Aset Tetap Aktif</h3>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nama Aset</th>
                                    <th>Harga Perolehan</th>
                                    <th>Umur</th>
                                    <th>Rp / Bulan</th>
                                    <th class="text-center">Status (<?= date('M Y') ?>)</th>
                                    <th class="text-center" style="width: 80px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($data['aset_data'])): ?>
                                    <tr><td colspan="6" class="text-center" style="color: var(--text-muted);">Belum ada data aset tetap.</td></tr>
                                <?php else: ?>
                                    <?php foreach($data['aset_data'] as $row): 
                                        $is_adjusted = ($row['last_adjusted_month'] === $data['current_month']);
                                        $status_text = $is_adjusted ? 'Sudah Disesuaikan' : 'Belum Disesuaikan';
                                        $status_class = $is_adjusted ? 'status-done' : 'status-pending';
                                        
                                        $u_tahun = floor($row['umur_ekonomis_bulan'] / 12);
                                        $u_bulan = $row['umur_ekonomis_bulan'] % 12;
                                        $umur_str = $u_tahun > 0 ? $u_tahun . ' Thn ' : '';
                                        $umur_str .= $u_bulan > 0 ? $u_bulan . ' Bln' : '';
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($row['nama_aset']) ?></strong></td>
                                        <td>Rp<?= number_format($row['harga_perolehan'], 0, ',', '.') ?></td>
                                        <td><?= trim($umur_str) ?></td>
                                        <td style="color: var(--primary-color);">Rp<?= number_format($row['nilai_penyusutan_per_bulan'], 0, ',', '.') ?></td>
                                        <td class="text-center">
                                            <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                                        </td>
                                        <td class="text-center">
                                            <form method="POST" action="<?= BASE_URL ?>/aset/tetap" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data aset \'<?= htmlspecialchars($row['nama_aset']) ?>\'?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete_aset">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="btn btn-danger" style="padding: 4px 8px; font-size: 0.8rem;" title="Hapus Aset">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script>
    function formatRupiahInput(input) {
        let value = input.value.replace(/[^\d]/g, '');
        if (value) {
            input.value = new Intl.NumberFormat('id-ID').format(value);
        }
    }
    
    function hitungPenyusutan() {
        let hargaStr = document.getElementById('harga_perolehan').value.replace(/[^\d]/g, '');
        let residuStr = document.getElementById('nilai_residu').value.replace(/[^\d]/g, '');
        let tahunStr = document.getElementById('umur_tahun').value;
        let bulanStr = document.getElementById('umur_bulan').value;
        
        let harga = parseFloat(hargaStr) || 0;
        let residu = parseFloat(residuStr) || 0;
        let tahun = parseInt(tahunStr) || 0;
        let bulan = parseInt(bulanStr) || 0;
        
        let totalBulan = (tahun * 12) + bulan;
        
        if (harga > 0 && totalBulan > 0) {
            let nilaiPenyusutan = (harga - residu) / totalBulan;
            document.getElementById('penyusutan_per_bulan_display').value = 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(nilaiPenyusutan));
        } else {
            document.getElementById('penyusutan_per_bulan_display').value = '';
        }
    }
</script>
</body>
</html>


