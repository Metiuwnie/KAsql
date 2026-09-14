<?php
/**
 * Financial Helper - SAK EMKM Comprehensive Financial Ratio & Health Analysis
 * Digunakan untuk perhitungan deterministik instan dan integrasi Hybrid Gemini AI.
 */

if (!function_exists('calculate_financial_metrics')) {
    /**
     * Menghitung seluruh rasio keuangan SAK EMKM dari data Neraca dan Laba Rugi
     */
    function calculate_financial_metrics($neraca_rows = [], $labarugi_rows = [], $net_income_neraca = 0) {
        $kas_bank = 0;
        $piutang = 0;
        $perlengkapan = 0;
        $aktiva_lancar = 0;
        $aktiva_tetap = 0;
        $kewajiban_lancar = 0;
        $kewajiban_panjang = 0;
        $modal_pokok = 0;

        foreach ($neraca_rows as $row) {
            $kode = (string)($row['kode_akun'] ?? '');
            $kat = strtolower($row['kategori_neraca'] ?? '');
            $bal = floatval($row['final_balance'] ?? 0);
            $prefix1 = substr($kode, 0, 1);
            $prefix2 = substr($kode, 0, 2);

            // Klasifikasi Aktiva
            if ($prefix1 === '1') {
                if (strpos($kat, 'lancar') !== false || in_array($prefix2, ['10', '11'])) {
                    $aktiva_lancar += $bal;
                    if (in_array($kode, ['101', '102', '100']) || strpos(strtolower($row['akun']), 'kas') !== false || strpos(strtolower($row['akun']), 'bank') !== false) {
                        $kas_bank += $bal;
                    } elseif ($kode === '103' || strpos(strtolower($row['akun']), 'piutang') !== false) {
                        $piutang += $bal;
                    } elseif (in_array($kode, ['104', '105']) || strpos(strtolower($row['akun']), 'perlengkapan') !== false || strpos(strtolower($row['akun']), 'persediaan') !== false) {
                        $perlengkapan += $bal;
                    }
                } else {
                    $aktiva_tetap += $bal;
                }
            }
            // Klasifikasi Kewajiban
            elseif ($prefix1 === '2') {
                if (strpos($kat, 'lancar') !== false || in_array($prefix2, ['20', '21'])) {
                    $kewajiban_lancar += $bal;
                } else {
                    $kewajiban_panjang += $bal;
                }
            }
            // Klasifikasi Ekuitas
            elseif ($prefix1 === '3') {
                $modal_pokok += $bal;
            }
        }

        $total_aset = $aktiva_lancar + $aktiva_tetap;
        $total_kewajiban = $kewajiban_lancar + $kewajiban_panjang;
        $total_ekuitas = $modal_pokok + $net_income_neraca;

        // Laba Rugi Metrics
        $total_pendapatan = 0;
        $total_beban = 0;
        foreach ($labarugi_rows as $row) {
            $kode = (string)($row['kode_akun'] ?? '');
            $val = floatval($row['total_nilai'] ?? 0);
            if (substr($kode, 0, 1) === '4') {
                $total_pendapatan += $val;
            } elseif (substr($kode, 0, 1) === '5') {
                $total_beban += $val;
            }
        }
        $laba_bersih = $total_pendapatan - $total_beban;

        // 1. Rasio Likuiditas
        $current_ratio = ($kewajiban_lancar > 0) ? ($aktiva_lancar / $kewajiban_lancar) : ($aktiva_lancar > 0 ? 999 : 0);
        $quick_ratio = ($kewajiban_lancar > 0) ? (($aktiva_lancar - $perlengkapan) / $kewajiban_lancar) : ($aktiva_lancar > 0 ? 999 : 0);
        $cash_ratio = ($kewajiban_lancar > 0) ? ($kas_bank / $kewajiban_lancar) : ($kas_bank > 0 ? 999 : 0);
        $nwc = $aktiva_lancar - $kewajiban_lancar; // Net Working Capital

        // 2. Rasio Profitabilitas
        $npm = ($total_pendapatan > 0) ? (($laba_bersih / $total_pendapatan) * 100) : 0;
        $roa = ($total_aset > 0) ? (($laba_bersih / $total_aset) * 100) : 0;
        $roe = ($total_ekuitas > 0) ? (($laba_bersih / $total_ekuitas) * 100) : 0;

        // 3. Rasio Solvabilitas & Efisiensi
        $dar = ($total_aset > 0) ? (($total_kewajiban / $total_aset) * 100) : 0;
        $der = ($total_ekuitas > 0) ? (($total_kewajiban / $total_ekuitas) * 100) : 0;
        $oer = ($total_pendapatan > 0) ? (($total_beban / $total_pendapatan) * 100) : 0;

        // 4. Skor Kesehatan Komposit (0 - 100 Poin)
        $score = 0;
        // Likuiditas (Max 35 poin)
        if ($current_ratio >= 1.5) $score += 20;
        elseif ($current_ratio >= 1.0) $score += 12;
        elseif ($current_ratio > 0) $score += 5;

        if ($cash_ratio >= 0.5) $score += 15;
        elseif ($cash_ratio >= 0.2) $score += 10;
        elseif ($cash_ratio > 0) $score += 5;

        // Profitabilitas (Max 35 poin)
        if ($npm >= 20) $score += 20;
        elseif ($npm >= 10) $score += 15;
        elseif ($npm > 0) $score += 8;

        if ($roa >= 10) $score += 15;
        elseif ($roa >= 5) $score += 10;
        elseif ($roa > 0) $score += 5;

        // Solvabilitas & Efisiensi (Max 30 poin)
        if ($dar <= 40) $score += 15;
        elseif ($dar <= 70) $score += 10;
        else $score += 3;

        if ($der <= 80) $score += 15;
        elseif ($der <= 150) $score += 8;
        else $score += 2;

        if ($score >= 80) {
            $health_status = 'Sangat Sehat';
            $health_badge = 'badge-success';
            $health_color = '#10b981';
            $health_desc = 'Kinerja finansial sangat prima, likuiditas aman dan struktur modal kokoh.';
        } elseif ($score >= 60) {
            $health_status = 'Sehat & Stabil';
            $health_badge = 'badge-primary';
            $health_color = '#3b82f6';
            $health_desc = 'Operasional stabil dengan rasio likuiditas memadai untuk pertumbuhan.';
        } elseif ($score >= 40) {
            $health_status = 'Perlu Perhatian';
            $health_badge = 'badge-warning';
            $health_color = '#f59e0b';
            $health_desc = 'Terdapat potensi tekanan arus kas atau beban operasional yang perlu diefisienkan.';
        } else {
            $health_status = 'Kritis';
            $health_badge = 'badge-danger';
            $health_color = '#ef4444';
            $health_desc = 'Risiko likuiditas atau solvabilitas tinggi, evaluasi restrukturisasi segera disarankan.';
        }

        return [
            'summary' => [
                'kas_bank' => $kas_bank,
                'piutang' => $piutang,
                'perlengkapan' => $perlengkapan,
                'aktiva_lancar' => $aktiva_lancar,
                'aktiva_tetap' => $aktiva_tetap,
                'total_aset' => $total_aset,
                'kewajiban_lancar' => $kewajiban_lancar,
                'total_kewajiban' => $total_kewajiban,
                'total_ekuitas' => $total_ekuitas,
                'total_pendapatan' => $total_pendapatan,
                'total_beban' => $total_beban,
                'laba_bersih' => $laba_bersih,
                'modal_kerja_bersih' => $nwc
            ],
            'ratios' => [
                'current_ratio' => $current_ratio,
                'quick_ratio' => $quick_ratio,
                'cash_ratio' => $cash_ratio,
                'npm' => $npm,
                'roa' => $roa,
                'roe' => $roe,
                'dar' => $dar,
                'der' => $der,
                'oer' => $oer
            ],
            'health' => [
                'score' => $score,
                'status' => $health_status,
                'badge' => $health_badge,
                'color' => $health_color,
                'desc' => $health_desc
            ]
        ];
    }
}

if (!function_exists('generate_deterministic_analysis_html')) {
    /**
     * Fallback Engine: Menghasilkan laporan analisis finansial profesional berbasis Rule Engine jika Gemini offline
     */
    function generate_deterministic_analysis_html($metrics, $report_type = 'neraca') {
        $r = $metrics['ratios'];
        $s = $metrics['summary'];
        $h = $metrics['health'];

        $cr_fmt = ($r['current_ratio'] > 50) ? '> 50x' : number_format($r['current_ratio'], 2, ',', '.') . 'x';
        $qr_fmt = ($r['quick_ratio'] > 50) ? '> 50x' : number_format($r['quick_ratio'], 2, ',', '.') . 'x';
        $cash_fmt = ($r['cash_ratio'] > 50) ? '> 50x' : number_format($r['cash_ratio'], 2, ',', '.') . 'x';
        $npm_fmt = number_format($r['npm'], 2, ',', '.') . '%';
        $roa_fmt = number_format($r['roa'], 2, ',', '.') . '%';
        $dar_fmt = number_format($r['dar'], 2, ',', '.') . '%';
        $der_fmt = number_format($r['der'], 2, ',', '.') . '%';
        $oer_fmt = number_format($r['oer'], 2, ',', '.') . '%';

        $html = '<div class="deterministic-analysis-content">';
        $html .= '<div style="background: rgba(59, 130, 246, 0.08); border-left: 4px solid var(--primary-color); padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem;">';
        $html .= '<h4 style="margin: 0 0 0.4rem 0; color: var(--primary-color); font-size: 1.05rem;">Ringkasan Eksekutif Keuangan (' . htmlspecialchars($h['status']) . ' - Skor ' . $h['score'] . '/100)</h4>';
        $html .= '<p style="margin: 0; font-size: 0.92rem; line-height: 1.5;">' . htmlspecialchars($h['desc']) . ' Evaluasi dilakukan berdasarkan standar akuntansi SAK EMKM dan rasio likuiditas, profitabilitas, serta solvabilitas.</p>';
        $html .= '</div>';

        // Tabel Rasio Keuangan
        $html .= '<h4 style="margin: 1.5rem 0 0.75rem 0; color: var(--text-main);">1. Analisis Rasio Finansial Utama</h4>';
        $html .= '<div class="table-responsive"><table class="table" style="width: 100%; margin-bottom: 1.5rem;">';
        $html .= '<thead><tr><th style="text-align: left;">Indikator Rasio</th><th style="text-align: center;">Nilai Aktual</th><th style="text-align: center;">Standar Acuan</th><th style="text-align: left;">Interpretasi Kinerja</th></tr></thead>';
        $html .= '<tbody>';

        // Current Ratio
        $cr_status = ($r['current_ratio'] >= 1.5) ? '<span style="color: #10b981; font-weight: 600;">✓ Sangat Kuat</span>' : '<span style="color: #f59e0b; font-weight: 600;">⚠ Cukup</span>';
        $html .= '<tr><td><strong>Current Ratio (Rasio Lancar)</strong></td><td style="text-align: center; font-weight: 600;">' . $cr_fmt . '</td><td style="text-align: center; color: var(--text-muted);">≥ 1.50x</td><td>' . $cr_status . ' — Kemampuan memenuhi utang jangka pendek.</td></tr>';

        // Quick Ratio
        $qr_status = ($r['quick_ratio'] >= 1.0) ? '<span style="color: #10b981; font-weight: 600;">✓ Likuid</span>' : '<span style="color: #f59e0b; font-weight: 600;">⚠ Perhatian</span>';
        $html .= '<tr><td><strong>Quick Ratio (Acid-Test)</strong></td><td style="text-align: center; font-weight: 600;">' . $qr_fmt . '</td><td style="text-align: center; color: var(--text-muted);">≥ 1.00x</td><td>' . $qr_status . ' — Likuiditas tanpa memperhitungkan persediaan/perlengkapan.</td></tr>';

        // Net Profit Margin
        $npm_status = ($r['npm'] >= 15) ? '<span style="color: #10b981; font-weight: 600;">✓ Sangat Baik</span>' : (($r['npm'] > 0) ? '<span style="color: #3b82f6; font-weight: 600;">✓ Positif</span>' : '<span style="color: #ef4444; font-weight: 600;">⚠ Rugi</span>');
        $html .= '<tr><td><strong>Net Profit Margin (NPM)</strong></td><td style="text-align: center; font-weight: 600;">' . $npm_fmt . '</td><td style="text-align: center; color: var(--text-muted);">≥ 15.00%</td><td>' . $npm_status . ' — Efisiensi laba bersih yang dihasilkan dari tiap rupiah pendapatan.</td></tr>';

        // Debt to Asset
        $dar_status = ($r['dar'] <= 50) ? '<span style="color: #10b981; font-weight: 600;">✓ Aman</span>' : '<span style="color: #ef4444; font-weight: 600;">⚠ Berisiko</span>';
        $html .= '<tr><td><strong>Debt to Asset Ratio (DAR)</strong></td><td style="text-align: center; font-weight: 600;">' . $dar_fmt . '</td><td style="text-align: center; color: var(--text-muted);">≤ 50.00%</td><td>' . $dar_status . ' — Proporsi aset yang dibiayai oleh pinjaman/kewajiban.</td></tr>';

        $html .= '</tbody></table></div>';

        // Rekomendasi Aksi
        $html .= '<h4 style="margin: 1.5rem 0 0.75rem 0; color: var(--text-main);">2. Rekomendasi Manajerial & Solusi Strategis</h4>';
        $html .= '<ul style="line-height: 1.7; padding-left: 1.5rem; color: var(--text-main); font-size: 0.93rem;">';
        if ($r['cash_ratio'] >= 0.5) {
            $html .= '<li><strong>Manajemen Kas Optimal:</strong> Saldo kas & setara kas berada di posisi yang sangat aman untuk menutup operasional harian. Pertimbangkan alokasi dana mengendap ke instrumen berimbal hasil rendah risiko.</li>';
        } else {
            $html .= '<li><strong>Peningkatan Cadangan Kas:</strong> Cadangan kas perlu diperkuat dengan mempercepat penagihan piutang usaha dan penataan jadwal pembayaran kewajiban.</li>';
        }

        if ($r['npm'] >= 15) {
            $html .= '<li><strong>Pertahankan Margin Laba:</strong> Efisiensi biaya berjalan efektif. Jaga stabilitas harga jual dan kontrol beban umum agar profitabilitas tetap terjaga.</li>';
        } else {
            $html .= '<li><strong>Audit Efisiensi Beban:</strong> Lakukan peninjauan menyeluruh terhadap akun beban operasional (OER: ' . $oer_fmt . ') untuk memangkas pengeluaran yang non-esensial.</li>';
        }

        if ($r['dar'] <= 50) {
            $html .= '<li><strong>Struktur Modal Seimbang:</strong> Rasio utang terhadap aset terkendali dengan baik sehingga risiko solvabilitas sangat minimal.</li>';
        } else {
            $html .= '<li><strong>Mitigasi Kewajiban:</strong> Prioritaskan pelunasan utang jangka pendek guna menekan beban bunga dan risiko gagal bayar.</li>';
        }
        $html .= '</ul>';

        $html .= '</div>';
        return $html;
    }
}
