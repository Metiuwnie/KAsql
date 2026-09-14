<?php
$nama_bulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data['title']) ?></title>
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css?v=<?= time() ?>">
    <script src="<?= BASE_URL ?>/public/js/script.js?v=2.0" defer></script>
    
    <style>
        .dynamic-section {
            display: none;
            background: rgba(128, 128, 128, 0.03);
            border: 1px dashed var(--border-color);
            padding: 1.5rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
        }
        .dynamic-section.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .info-alert {
            background-color: rgba(59, 130, 246, 0.1);
            border-left: 4px solid var(--primary-color);
            padding: 1rem;
            border-radius: var(--radius-sm);
            margin-top: 1rem;
            font-size: 0.9rem;
            color: var(--text-main);
        }
    </style>
    
    <script>
    const akunList = <?= json_encode($data['akun_list']) ?>;
    const asetList = <?= json_encode($data['aset_list']) ?>;
    const prepaidList = <?= json_encode($data['prepaid_list']) ?>;
    
    function formatRp(angka) {
        return new Intl.NumberFormat('id-ID').format(angka);
    }
    
    function getLastDayOfMonth(year, month) {
        return new Date(year, month, 0).getDate();
    }

    function onPeriodeChange() {
        const bulan = document.getElementById('bulan').value;
        const tahun = document.getElementById('tahun').value;
        const tglInput = document.getElementById('tanggal');
        
        const jenisSelect = document.getElementById('jenis_penyesuaian');
        const currentVal = jenisSelect.value;
        
        let optionsHtml = '<option value="">-- Pilih Jenis Penyesuaian --</option>' +
                          '<option value="beban_akrual">1. Beban Akrual (Accrued Expense)</option>' +
                          '<option value="pendapatan_akrual">2. Pendapatan Akrual (Accrued Revenue)</option>' +
                          '<option value="prepaid_expense">3. Prepaid Expense (beban dibayar di muka)</option>' +
                          '<option value="perlengkapan">4. Perlengkapan (perlengkapan terpakai)</option>';
                          
        if (bulan === '12') {
            optionsHtml += '<option value="penyusutan">5. Penyusutan (penurunan nilai aset tahunan)</option>';
        }
        
        jenisSelect.innerHTML = optionsHtml;
        
        if (currentVal === 'penyusutan' && bulan !== '12') {
            jenisSelect.value = '';
            onJenisChange();
        } else {
            jenisSelect.value = currentVal;
        }

        if (bulan && tahun) {
            const lastDay = getLastDayOfMonth(tahun, bulan);
            const dateStr = `${tahun}-${bulan.padStart(2, '0')}-${lastDay.toString().padStart(2, '0')}`;
            tglInput.value = dateStr;
            updateLogic();
        } else {
            tglInput.value = '';
        }
    }
    
    function onJenisChange() {
        const jenis = document.getElementById('jenis_penyesuaian').value;
        
        document.querySelectorAll('.dynamic-section').forEach(el => el.classList.remove('active'));
        
        if (jenis === 'penyusutan') {
            document.getElementById('sec_penyusutan').classList.add('active');
        } else if (jenis === 'prepaid_expense') {
            document.getElementById('sec_prepaid').classList.add('active');
        } else if (jenis === 'perlengkapan') {
            document.getElementById('sec_perlengkapan').classList.add('active');
        } else if (jenis !== '') {
            document.getElementById('sec_standar').classList.add('active');
            
            const selectDebet = document.getElementById('std_akun_debet');
            const selectKredit = document.getElementById('std_akun_kredit');
            const stdDesc = document.getElementById('std_deskripsi');
            
            let optDebet = '';
            let optKredit = '';
            
            if (jenis === 'beban_akrual') {
                stdDesc.value = "Penyesuaian Beban Akrual";
                optDebet = buildOptions(a => a.kategori_neraca === 'Beban' || a.kategori_neraca.toLowerCase().includes('beban'));
                optKredit = buildOptions(a => a.kategori_neraca.toLowerCase().includes('utang'));
            } else if (jenis === 'pendapatan_akrual') {
                stdDesc.value = "Penyesuaian Pendapatan Akrual";
                optDebet = buildOptions(a => a.akun.toLowerCase().includes('piutang'));
                optKredit = buildOptions(a => a.kategori_neraca === 'Pendapatan');
            }
            
            selectDebet.innerHTML = '<option value="">-- Pilih Akun --</option>' + optDebet;
            selectKredit.innerHTML = '<option value="">-- Pilih Akun --</option>' + optKredit;
            
            if (selectDebet.options.length === 2) selectDebet.selectedIndex = 1;
            if (selectKredit.options.length === 2) selectKredit.selectedIndex = 1;
        }
        
        updateLogic();
        updateReversingCheckbox();
    }
    
    function buildOptions(filterFunc) {
        let html = '';
        akunList.filter(filterFunc).forEach(akun => {
            html += `<option value="${akun.kode_akun}">${akun.kode_akun} - ${akun.akun}</option>`;
        });
        return html;
    }
    
    function calculatePerlengkapan() {
        const awalStr = document.getElementById('plk_awal').value.replace(/[^\d.-]/g,'') || '0';
        const beliStr = document.getElementById('plk_beli').value.replace(/[^\d.-]/g,'') || '0';
        const akhirStr = document.getElementById('plk_akhir').value.replace(/[^\d.-]/g,'') || '0';
        
        const awal = parseFloat(awalStr);
        const beli = parseFloat(beliStr);
        const akhir = parseFloat(akhirStr);
        
        const terpakai = awal + beli - akhir;
        document.getElementById('plk_terpakai').value = terpakai > 0 ? terpakai : 0;
        
        updateLogic();
    }
    
    function getAkunName(kode) {
        if(!kode) return '-';
        const akun = akunList.find(a => a.kode_akun === kode);
        return akun ? akun.akun : '-';
    }

    function updateLogic() {
        const jenis = document.getElementById('jenis_penyesuaian').value;
        let finalDebet = '';
        let finalKredit = '';
        let finalNilai = 0;
        let finalDesc = '';
        let referensiId = '';
        
        if (jenis === 'penyusutan') {
            const asetId = document.getElementById('ast_id').value;
            const aset = asetList.find(a => a.id == asetId);
            if (aset) {
                finalDebet = aset.akun_beban;
                finalKredit = aset.akun_akumulasi;
                const harga = parseFloat(aset.harga_perolehan) || 0;
                const residu = parseFloat(aset.nilai_residu) || 0;
                const umurBulan = parseFloat(aset.umur_ekonomis_bulan) || 0;
                if (umurBulan > 0) {
                    finalNilai = Math.round((harga - residu) / (umurBulan / 12));
                } else {
                    finalNilai = Math.round(parseFloat(aset.nilai_penyusutan_per_bulan) * 12);
                }
                document.getElementById('ast_nilai').value = finalNilai;
                referensiId = asetId;
            } else {
                document.getElementById('ast_nilai').value = '';
            }
            finalDesc = document.getElementById('ast_desc').value;
            
        } else if (jenis === 'prepaid_expense') {
            const prepId = document.getElementById('prp_id').value;
            const prep = prepaidList.find(p => p.id == prepId);
            if (prep) {
                finalDebet = prep.akun_beban;
                finalKredit = prep.akun_prepaid;
                finalNilai = Math.round(parseFloat(prep.nilai_per_bulan));
                referensiId = prepId;
                
                const opt = document.querySelector(`#prp_id option[value='${prepId}']`);
                const total = parseFloat(opt.getAttribute('data-total')) || 0;
                const lama = parseInt(opt.getAttribute('data-lama')) || 0;
                const terpakai = parseInt(opt.getAttribute('data-terpakai')) || 0;
                
                const sisaUmur = lama - terpakai;
                const sisaNilai = total - (finalNilai * terpakai);
                const progress = lama > 0 ? Math.round((terpakai / lama) * 100) : 0;
                
                document.getElementById('prp_lbl_beban').innerText = formatRp(finalNilai);
                document.getElementById('prp_lbl_sudah').innerText = terpakai + " bulan";
                document.getElementById('prp_lbl_sisa_umur').innerText = sisaUmur + " bulan";
                document.getElementById('prp_lbl_sisa_nilai').innerText = formatRp(sisaNilai);
                document.getElementById('prp_lbl_progress_text').innerText = "Progress Masa Manfaat: " + progress + "%";
                document.getElementById('prp_progress_bar').style.width = progress + "%";
                
                document.getElementById('prp_details_card').style.display = 'block';
            } else {
                document.getElementById('prp_details_card').style.display = 'none';
            }
            finalDesc = document.getElementById('prp_desc').value;
            
        } else if (jenis === 'perlengkapan') {
            finalDebet = '503'; // Asumsi 503 Beban Perlengkapan
            finalKredit = document.getElementById('plk_akun').value; 
            finalNilai = parseFloat(document.getElementById('plk_terpakai').value) || 0;
            finalDesc = document.getElementById('plk_desc').value;
            
        } else if (jenis === 'beban_akrual' || jenis === 'pendapatan_akrual') {
            finalDebet = document.getElementById('std_akun_debet').value;
            finalKredit = document.getElementById('std_akun_kredit').value;
            finalNilai = parseFloat(document.getElementById('std_nilai').value.replace(/[^\d.-]/g,'')) || 0;
            finalDesc = document.getElementById('std_deskripsi').value;
        }

        document.getElementById('final_akun_debet').value = finalDebet;
        document.getElementById('final_akun_kredit').value = finalKredit;
        document.getElementById('final_nilai').value = finalNilai;
        document.getElementById('final_deskripsi').value = finalDesc;
        document.getElementById('referensi_id').value = referensiId;
        
        const valStr = finalNilai > 0 ? formatRp(finalNilai) : '-';
        const tgl = document.getElementById('tanggal').value || '-';
        
        document.getElementById('prev_tgl_1').innerText = tgl;
        document.getElementById('prev_tgl_2').innerText = ''; 
        
        document.getElementById('prev_kode_debet').innerText = finalDebet || '-';
        document.getElementById('prev_kode_kredit').innerText = finalKredit || '-';
        
        document.getElementById('prev_akun_debet').innerText = getAkunName(finalDebet);
        document.getElementById('prev_akun_kredit').innerText = '\u00A0\u00A0\u00A0\u00A0' + getAkunName(finalKredit);
        
        document.getElementById('prev_val_debet').innerText = valStr;
        document.getElementById('prev_val_kredit').innerText = '-';
        
        document.getElementById('prev_val_debet_null').innerText = '-';
        document.getElementById('prev_val_kredit_real').innerText = valStr;
        
        document.getElementById('prev_total_debet').innerText = valStr;
        document.getElementById('prev_total_kredit').innerText = valStr;
        
        const btnSubmit = document.getElementById('btn_submit');
        const isBalance = (finalNilai > 0 && finalDebet && finalKredit && finalDebet !== finalKredit && tgl !== '-');
        
        if (isBalance) {
            btnSubmit.disabled = false;
        } else {
            btnSubmit.disabled = true;
        }
    }
    
    function updateReversingCheckbox() {
        const jenis = document.getElementById('jenis_penyesuaian').value;
        const reversingBox  = document.getElementById('is_reversing');
        const reversingWrap = document.getElementById('wrap_is_reversing');
        const akrualTypes = ['beban_akrual', 'pendapatan_akrual'];

        if (akrualTypes.includes(jenis)) {
            reversingWrap.style.display = 'block';
            reversingBox.disabled = false;
        } else {
            reversingWrap.style.display = 'none';
            reversingBox.checked  = false;
            reversingBox.disabled = true;
        }
    }

    window.onload = function() {
        const plkDrop = document.getElementById('plk_akun');
        plkDrop.innerHTML = '<option value="">-- Pilih Akun Perlengkapan --</option>' + buildOptions(a => a.aktiva_pasiva === 'A' && (a.akun.toLowerCase().includes('supply') || a.akun.toLowerCase().includes('perlengkapan')));
        if (plkDrop.options.length === 2) plkDrop.selectedIndex = 1;
        updateReversingCheckbox();
    };
    </script>
</head>
<body>

<?php include __DIR__ . '/../layout/sidebar.php'; ?>

<div class="main-content">
    <?php require_once __DIR__ . '/../layout/header.php'; ?>
    <div class="container">
        <h2 class="page-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
                <path d="M12 20h9"></path>
                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
            </svg>
            Jurnal Penyesuaian Dinamis
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
                <span><?= htmlspecialchars($data['success']) ?></span>
            </div>
        <?php endif; ?>

        <div class="row" style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div class="col" style="flex: 1; min-width: 450px;">
                <div class="card">
                    <form method="POST" action="<?= BASE_URL ?>/jurnal/penyesuaian">
                        <?= csrf_field() ?>
                        <input type="hidden" name="submit_penyesuaian" value="1">
                        
                        <input type="hidden" id="final_akun_debet" name="final_akun_debet" value="">
                        <input type="hidden" id="final_akun_kredit" name="final_akun_kredit" value="">
                        <input type="hidden" id="final_nilai" name="final_nilai" value="">
                        <input type="hidden" id="final_deskripsi" name="final_deskripsi" value="">
                        <input type="hidden" id="referensi_id" name="referensi_id" value="">
                        
                        <div style="display: flex; gap: 10px; margin-bottom: 1rem;">
                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                <label class="form-label">Bulan Periode</label>
                                <select name="bulan" id="bulan" class="form-control" required onchange="onPeriodeChange()">
                                    <option value="">-- Bulan --</option>
                                    <?php foreach($nama_bulan as $num => $name): ?>
                                        <option value="<?= $num ?>"><?= $name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                <label class="form-label">Tahun</label>
                                <select name="tahun" id="tahun" class="form-control" required onchange="onPeriodeChange()">
                                    <option value="">-- Tahun --</option>
                                    <?php 
                                    $curr_year = date('Y');
                                    for($y = $curr_year - 2; $y <= $curr_year + 1; $y++): ?>
                                        <option value="<?= $y ?>"><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tanggal Transaksi (Otomatis)</label>
                            <input type="date" name="tanggal" id="tanggal" class="form-control" required readonly style="background-color: var(--bg-surface-hover);">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Jenis Penyesuaian</label>
                            <select name="jenis_penyesuaian" id="jenis_penyesuaian" class="form-control" required onchange="onJenisChange()" style="font-weight: 600; border: 1px solid var(--primary-color);">
                                <option value="">-- Pilih Jenis Penyesuaian --</option>
                                <option value="beban_akrual">1. Beban Akrual (Accrued Expense)</option>
                                <option value="pendapatan_akrual">2. Pendapatan Akrual (Accrued Revenue)</option>
                                <option value="prepaid_expense">3. Prepaid Expense (beban dibayar di muka)</option>
                                <option value="perlengkapan">4. Perlengkapan (perlengkapan terpakai)</option>
                            </select>
                        </div>
                        
                        <!-- SECTION PENYUSUTAN -->
                        <div id="sec_penyusutan" class="dynamic-section">
                            <div class="form-group">
                                <label class="form-label">Pilih Aset</label>
                                <select id="ast_id" class="form-control" onchange="updateLogic()">
                                    <option value="">-- Pilih Aset Tetap --</option>
                                    <?php foreach($data['aset_list'] as $aset): 
                                        $peny_tahun = $aset['nilai_penyusutan_per_bulan'] * 12;
                                        $u_tahun = floor($aset['umur_ekonomis_bulan'] / 12);
                                        $u_bulan = $aset['umur_ekonomis_bulan'] % 12;
                                        $umur_str = $u_tahun > 0 ? $u_tahun . ' th' : '';
                                        $umur_str .= ($u_tahun > 0 && $u_bulan > 0) ? ' ' : '';
                                        $umur_str .= $u_bulan > 0 ? $u_bulan . ' bln' : '';
                                        $label = $aset['nama_aset'] . " (Rp".number_format($aset['harga_perolehan'],0,',','.').", ".$umur_str.") - Rp".number_format($peny_tahun,0,',','.')."/th";
                                    ?>
                                        <option value="<?= $aset['id'] ?>"><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nilai Penyusutan per Tahun (Rp)</label>
                                <input type="text" id="ast_nilai" class="form-control" readonly style="background-color: var(--bg-surface-hover);">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Keterangan</label>
                                <input type="text" id="ast_desc" class="form-control" placeholder="Contoh: Penyusutan Mesin Produksi Tahun Ini" onkeyup="updateLogic()">
                            </div>
                            <div class="info-alert">
                                <strong>Rumus:</strong> (Harga Perolehan - Nilai Residu) / Umur Ekonomis (bulan)
                            </div>
                        </div>

                        <!-- SECTION PREPAID -->
                        <div id="sec_prepaid" class="dynamic-section">
                            <div class="form-group">
                                <label class="form-label">Pilih Prepaid</label>
                                <select id="prp_id" class="form-control" onchange="updateLogic()">
                                    <option value="">-- Pilih Prepaid --</option>
                                    <?php foreach($data['prepaid_list'] as $prep): 
                                        $label = $prep['nama_prepaid'] . " (Rp".number_format($prep['total_nilai'],0,',','.')." / ".$prep['lama_bulan']." bulan) - Rp".number_format($prep['nilai_per_bulan'],0,',','.')."/bulan";
                                    ?>
                                        <option value="<?= $prep['id'] ?>" data-total="<?= $prep['total_nilai'] ?>" data-lama="<?= $prep['lama_bulan'] ?>" data-terpakai="<?= $prep['bulan_terpakai'] ?>"><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div id="prp_details_card" style="display: none; background-color: rgba(79, 70, 229, 0.05); border: 1px solid rgba(79, 70, 229, 0.2); border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span>Beban Per Bulan:</span>
                                    <strong id="prp_lbl_beban"></strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span>Sudah Dibebankan:</span>
                                    <strong id="prp_lbl_sudah"></strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                    <span>Sisa Umur:</span>
                                    <strong id="prp_lbl_sisa_umur"></strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                    <span>Sisa Nilai:</span>
                                    <strong id="prp_lbl_sisa_nilai"></strong>
                                </div>
                                
                                <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 5px;" id="prp_lbl_progress_text">
                                    Progress Masa Manfaat: 0%
                                </div>
                                <div style="width: 100%; background-color: var(--border-color); border-radius: 4px; height: 6px; overflow: hidden;">
                                    <div id="prp_progress_bar" style="height: 100%; background-color: var(--primary-color); width: 0%; transition: width 0.3s ease;"></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Keterangan</label>
                                <input type="text" id="prp_desc" class="form-control" placeholder="Contoh: Beban sewa Januari" onkeyup="updateLogic()">
                            </div>
                            <div class="info-alert">
                                <strong>Penjelasan:</strong> Prepaid adalah pembayaran di muka. Setiap akhir bulan, sebagian diakui sebagai beban.
                            </div>
                        </div>

                        <!-- SECTION PERLENGKAPAN -->
                        <div id="sec_perlengkapan" class="dynamic-section">
                            <div class="form-group">
                                <label class="form-label">Perlengkapan</label>
                                <select id="plk_akun" class="form-control" onchange="updateLogic()"></select>
                            </div>
                            
                            <div style="display: flex; gap: 10px; margin-bottom: 1rem;">
                                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                    <label class="form-label">Saldo Awal (Rp)</label>
                                    <input type="text" id="plk_awal" class="form-control" placeholder="Saldo awal bulan" onkeyup="this.value=this.value.replace(/[^\d.-]/g,''); calculatePerlengkapan()">
                                </div>
                                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                    <label class="form-label">Pembelian Bulan Ini (Rp)</label>
                                    <input type="text" id="plk_beli" class="form-control" placeholder="Pembelian bulan ini" onkeyup="this.value=this.value.replace(/[^\d.-]/g,''); calculatePerlengkapan()">
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 10px; margin-bottom: 1rem;">
                                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                    <label class="form-label">Stok Akhir (Rp) - <small>hasil opname</small></label>
                                    <input type="text" id="plk_akhir" class="form-control" placeholder="Stok akhir" onkeyup="this.value=this.value.replace(/[^\d.-]/g,''); calculatePerlengkapan()">
                                </div>
                                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                    <label class="form-label">Yang Terpakai (Rp)</label>
                                    <input type="text" id="plk_terpakai" class="form-control" readonly style="background-color: var(--bg-surface-hover); color: var(--danger-color); font-weight: bold;">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Keterangan</label>
                                <input type="text" id="plk_desc" class="form-control" placeholder="Contoh: Perlengkapan kantor yang terpakai Januari" onkeyup="updateLogic()">
                            </div>
                            <div class="info-alert">
                                <strong>Rumus:</strong> Yang Terpakai = Saldo Awal + Pembelian - Stok Akhir
                            </div>
                        </div>
                        
                        <!-- SECTION STANDAR -->
                        <div id="sec_standar" class="dynamic-section">
                            <div class="form-group">
                                <label class="form-label">Akun Debet</label>
                                <select id="std_akun_debet" class="form-control" onchange="updateLogic()"></select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Akun Kredit</label>
                                <select id="std_akun_kredit" class="form-control" onchange="updateLogic()"></select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nilai Nominal (Rp)</label>
                                <input type="text" id="std_nilai" class="form-control" onkeyup="this.value=this.value.replace(/[^\d.-]/g,''); updateLogic()">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Deskripsi</label>
                                <input type="text" id="std_deskripsi" class="form-control" onkeyup="updateLogic()">
                            </div>
                        </div>

                        <!-- JURNAL PEMBALIK CHECKBOX -->
                        <div id="wrap_is_reversing" style="display: none; margin-bottom: 1.25rem;">
                            <div style="background: linear-gradient(135deg, rgba(79,70,229,0.08), rgba(79,70,229,0.04)); border: 1px solid rgba(79,70,229,0.25); border-radius: var(--radius-md); padding: 1rem 1.25rem;">
                                <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer; user-select: none;">
                                    <div style="position: relative; flex-shrink: 0; margin-top: 2px;">
                                        <input
                                            type="checkbox"
                                            id="is_reversing"
                                            name="is_reversing"
                                            value="1"
                                            style="width: 18px; height: 18px; accent-color: var(--primary-color); cursor: pointer;"
                                        >
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: var(--primary-color); font-size: 0.95rem; margin-bottom: 3px;">
                                            &#x21BA; Buat Jurnal Pembalik Otomatis di awal periode berikutnya
                                        </div>
                                        <div style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.45;">
                                            Hanya tersedia untuk <strong>Beban Akrual</strong> dan <strong>Pendapatan Akrual</strong>.
                                            Sistem akan otomatis membalik jurnal ini pada tanggal 1 periode berikutnya (pukul 00:05).
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <button type="submit" id="btn_submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1.05rem;" disabled>Simpan Jurnal Penyesuaian</button>

                    </form>
                </div>
            </div>
            
            <div class="col" style="flex: 1; min-width: 350px;">
                <div class="card" style="position: sticky; top: 20px;">
                    <h3 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">Preview Jurnal</h3>
                    <div class="table-container" style="margin-top: 15px;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 15%">Tanggal</th>
                                    <th style="width: 15%">Kode</th>
                                    <th style="width: 40%">Nama Akun</th>
                                    <th style="width: 15%" class="text-right">Debet</th>
                                    <th style="width: 15%" class="text-right">Kredit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td id="prev_tgl_1">-</td>
                                    <td id="prev_kode_debet">-</td>
                                    <td id="prev_akun_debet" style="font-weight: 500;">-</td>
                                    <td id="prev_val_debet" class="text-right">-</td>
                                    <td id="prev_val_kredit" class="text-right">-</td>
                                </tr>
                                <tr>
                                    <td id="prev_tgl_2"></td>
                                    <td id="prev_kode_kredit">-</td>
                                    <td id="prev_akun_kredit" style="color: var(--text-muted);">-</td>
                                    <td id="prev_val_debet_null" class="text-right">-</td>
                                    <td id="prev_val_kredit_real" class="text-right">-</td>
                                </tr>
                                <tr class="font-bold" style="background-color: var(--bg-surface-hover);">
                                    <td colspan="3" class="text-center">Total Balance</td>
                                    <td id="prev_total_debet" class="text-right" style="color: var(--success-color);">-</td>
                                    <td id="prev_total_kredit" class="text-right" style="color: var(--success-color);">-</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 15px; font-size: 0.85rem; color: var(--text-muted); text-align: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        Pastikan debet dan kredit sudah sesuai. Tombol simpan otomatis aktif jika valid.
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

</body>
</html>


