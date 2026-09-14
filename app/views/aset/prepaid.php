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
                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                Prepaid Expense (Dibayar di Muka)
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
                    <h3 style="margin-top: 0; margin-bottom: 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Tambah Prepaid</h3>
                    <form method="POST" action="<?= BASE_URL ?>/aset/prepaid">
                        <?= csrf_field() ?>
                        <input type="hidden" name="submit_prepaid" value="1">
                        
                        <div class="form-group">
                            <label class="form-label">Nama Prepaid (Contoh: Sewa Ruko 2026)</label>
                            <input type="text" name="nama_prepaid" class="form-control" required autocomplete="off">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Total Harga/Nilai (Rp)</label>
                            <input type="text" name="total_nilai" id="total_nilai" class="form-control" required autocomplete="off" onkeyup="this.value=this.value.replace(/[^\d]/g,''); formatRupiahInput(this); hitungPerBulan()">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Durasi (Bulan)</label>
                            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <button type="button" class="btn btn-secondary" style="flex:1; padding: 5px;" onclick="setDurasi(12)">1 Tahun (12)</button>
                                <button type="button" class="btn btn-secondary" style="flex:1; padding: 5px;" onclick="setDurasi(6)">6 Bulan</button>
                            </div>
                            <input type="number" name="lama_bulan" id="lama_bulan" class="form-control" placeholder="Isi manual (bulan)" required min="1" oninput="hitungPerBulan()">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Nilai per Bulan (Otomatis)</label>
                            <input type="text" id="nilai_per_bulan_display" class="form-control" readonly style="background-color: var(--bg-surface-hover); font-weight: bold; color: var(--primary-color);">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Akun Prepaid (Debet awal - Aset)</label>
                            <select name="akun_prepaid" class="form-control" required>
                                <option value="">-- Pilih Akun Aset --</option>
                                <?php foreach($data['akun_list'] as $a): if($a['kategori_neraca'] == 'Aktiva Lancar' || $a['kategori_neraca'] == 'Aktiva Tetap'): ?>
                                    <option value="<?= $a['kode_akun'] ?>"><?= $a['kode_akun'] ?> - <?= htmlspecialchars($a['akun']) ?></option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Akun Beban (Kredit saat penyesuaian)</label>
                            <select name="akun_beban" class="form-control" required>
                                <option value="">-- Pilih Akun Beban --</option>
                                <?php foreach($data['akun_list'] as $a): if($a['kategori_neraca'] == 'Beban'): ?>
                                    <option value="<?= $a['kode_akun'] ?>"><?= $a['kode_akun'] ?> - <?= htmlspecialchars($a['akun']) ?></option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%;">Simpan Prepaid Baru</button>
                    </form>
                </div>
            </div>
            
            <!-- TABEL -->
            <div class="col" style="flex: 2; min-width: 500px;">
                <div class="card">
                    <h3 style="margin-top: 0; margin-bottom: 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 1px; color: var(--text-muted);">Daftar Prepaid (<?= count($data['prepaid_data']) ?> Item)</h3>
                    <div class="prepaid-list" style="display: flex; flex-direction: column; gap: 15px;">
                        <?php if(empty($data['prepaid_data'])): ?>
                            <div class="text-center" style="color: var(--text-muted); padding: 2rem;">Belum ada data prepaid expense.</div>
                        <?php else: ?>
                            <?php foreach($data['prepaid_data'] as $row): 
                                $is_lunas = ($row['bulan_terpakai'] >= $row['lama_bulan']);
                                
                                $d1 = new DateTime(date('Y-m-01', strtotime($row['tanggal_mulai'] ?? date('Y-m-d'))));
                                $d2 = new DateTime(date('Y-m-01'));
                                $diff = $d1->diff($d2);
                                $month_index_now = ($diff->format('%y') * 12) + $diff->format('%m');
                                if ($diff->invert) {
                                    $month_index_now = -1;
                                }
                                
                                $is_adjusted = ($row['bulan_terpakai'] > $month_index_now);
                                
                                if ($is_lunas) {
                                    $status_bg = 'rgba(79, 70, 229, 0.1)';
                                    $status_color = '#4f46e5';
                                    $status_text = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px; margin-bottom: 2px;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> LUNAS SEPENUHNYA';
                                } else if ($is_adjusted) {
                                    $status_bg = 'rgba(16, 185, 129, 0.1)';
                                    $status_color = 'var(--success-color)';
                                    $status_text = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px; margin-bottom: 2px;"><polyline points="20 6 9 17 4 12"></polyline></svg> Sudah Dibayar Bulan Ini';
                                } else {
                                    $status_bg = 'rgba(245, 158, 11, 0.1)';
                                    $status_color = '#f59e0b';
                                    $status_text = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px; margin-bottom: 2px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg> Belum Dibayar Bulan Ini';
                                }
                                $tgl_indo = date('d M Y', strtotime($row['tanggal_mulai'] ?? date('Y-m-d')));
                            ?>
                            <div class="card" style="margin-bottom: 0; position: relative; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.05); border-radius: 8px;">
                                <h4 style="margin: 0 0 10px 0; font-size: 1.2rem;"><?= htmlspecialchars($row['nama_prepaid']) ?></h4>
                                <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 15px;">
                                    Total: <strong>Rp <?= number_format($row['total_nilai'], 0, ',', '.') ?></strong> • Durasi: <strong><?= $row['lama_bulan'] ?> bulan</strong> • Mulai: <?= $tgl_indo ?>
                                </div>
                                <div style="font-size: 1.1rem; margin-bottom: 15px;">
                                    <strong>Rp <?= number_format($row['nilai_per_bulan'], 0, ',', '.') ?></strong> <span style="font-size: 0.85rem; color: var(--text-muted);">/ bulan</span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="background-color: <?= $status_bg ?>; color: <?= $status_color ?>; padding: 6px 12px; border-radius: 4px; font-weight: 700; font-size: 0.85rem; display: inline-block;">
                                        <?= $status_text ?>
                                    </div>
                                    <div style="display: flex; gap: 10px;">
                                        <form method="POST" action="<?= BASE_URL ?>/aset/prepaid" style="margin: 0;" onsubmit="return confirm('Hapus data prepaid ini?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete_prepaid">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                                            <button type="submit" class="btn" style="padding: 6px 12px; display: flex; align-items: center; justify-content: center; background-color: rgba(0,0,0,0.05); border: 1px solid var(--border-color); border-radius: 4px; color: var(--text-main); cursor: pointer;" title="Hapus Data">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                
                                <!-- JADWAL PEMBEBANAN -->
                                <div style="margin-top: 20px; border-top: 1px dashed var(--border-color); padding-top: 15px;">
                                    <h5 style="margin: 0 0 10px 0; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Jadwal Pembebanan:</h5>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 8px;">
                                        <?php 
                                        $start_date = strtotime($row['tanggal_mulai'] ?? date('Y-m-01'));
                                        $lama_bulan = (int)$row['lama_bulan'];
                                        $bulan_terpakai = (int)$row['bulan_terpakai'];
                                        
                                        for ($i = 0; $i < $lama_bulan; $i++) {
                                            $current_month_time = strtotime("+$i months", $start_date);
                                            // Terjemahan nama bulan singkat
                                            $months_indo = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                                            $month_index = (int)date('n', $current_month_time) - 1;
                                            $year = date('Y', $current_month_time);
                                            $month_label = $months_indo[$month_index] . ' ' . $year;
                                            
                                            $is_month_paid = ($i < $bulan_terpakai);
                                            $badge_bg = $is_month_paid ? 'rgba(16, 185, 129, 0.1)' : 'rgba(245, 158, 11, 0.1)';
                                            $badge_color = $is_month_paid ? 'var(--success-color)' : '#f59e0b';
                                            $icon = $is_month_paid ? '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px; margin-bottom: 2px;"><polyline points="20 6 9 17 4 12"></polyline></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px; margin-bottom: 2px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>';
                                            $title = $is_month_paid ? 'Sudah Lunas' : 'Belum Lunas';
                                            
                                            echo "<div title='{$title}' style='background: {$badge_bg}; color: {$badge_color}; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; text-align: center; font-weight: 600; border: 1px solid {$badge_color}33; display: flex; align-items: center; justify-content: center;'>";
                                            echo "{$icon} <span>{$month_label}</span>";
                                            echo "</div>";
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
    
    function setDurasi(bulan) {
        document.getElementById('lama_bulan').value = bulan;
        hitungPerBulan();
    }
    
    function hitungPerBulan() {
        let totalStr = document.getElementById('total_nilai').value.replace(/[^\d]/g, '');
        let bulanStr = document.getElementById('lama_bulan').value;
        
        let total = parseFloat(totalStr) || 0;
        let bulan = parseInt(bulanStr) || 0;
        
        if (total > 0 && bulan > 0) {
            let perBulan = total / bulan;
            document.getElementById('nilai_per_bulan_display').value = 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(perBulan));
        } else {
            document.getElementById('nilai_per_bulan_display').value = '';
        }
    }
</script>
</body>
</html>


