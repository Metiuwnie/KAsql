import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'dart:convert';
import '../services/api_service.dart';
import '../services/database_service.dart';
import '../services/connectivity_provider.dart';
import '../config/api_config.dart';
import '../config/app_theme.dart';
import '../widgets/app_drawer.dart';
import '../widgets/offline_banner.dart';
import '../utils/helpers.dart';

class JurnalPenyesuaianScreen extends StatefulWidget {
  const JurnalPenyesuaianScreen({super.key});

  @override
  State<JurnalPenyesuaianScreen> createState() => _JurnalPenyesuaianScreenState();
}

class _JurnalPenyesuaianScreenState extends State<JurnalPenyesuaianScreen> {
  final _formKey = GlobalKey<FormState>();
  
  String? _bulan;
  String? _tahun;
  String? _tanggalOtomatis;
  String? _jenisPenyesuaian;

  List<Map<String, dynamic>> _akunList = [];
  List<Map<String, dynamic>> _asetList = [];
  List<Map<String, dynamic>> _prepaidList = [];
  bool _isLoading = false;
  bool _isSubmitting = false;

  // Aset
  String? _astId;
  String _astDesc = '';

  // Prepaid
  String? _prpId;
  String _prpDesc = '';

  // Perlengkapan
  String? _plkAkun;
  final _plkAwalCtrl = TextEditingController();
  final _plkBeliCtrl = TextEditingController();
  final _plkAkhirCtrl = TextEditingController();
  String _plkDesc = '';

  // Standard Akrual
  String? _stdAkunDebet;
  String? _stdAkunKredit;
  final _stdNilaiCtrl = TextEditingController();
  String _stdDesc = '';
  bool _isReversing = false;

  // Final Values
  String? _finalDebet;
  String? _finalKredit;
  double _finalNilai = 0;
  String _finalDesc = '';
  String? _referensiId;

  final List<Map<String, String>> _namaBulan = [
    {'val': '01', 'name': 'Januari'}, {'val': '02', 'name': 'Februari'},
    {'val': '03', 'name': 'Maret'}, {'val': '04', 'name': 'April'},
    {'val': '05', 'name': 'Mei'}, {'val': '06', 'name': 'Juni'},
    {'val': '07', 'name': 'Juli'}, {'val': '08', 'name': 'Agustus'},
    {'val': '09', 'name': 'September'}, {'val': '10', 'name': 'Oktober'},
    {'val': '11', 'name': 'November'}, {'val': '12', 'name': 'Desember'},
  ];

  @override
  void initState() {
    super.initState();
    _loadFormData();
  }

  Future<void> _loadFormData() async {
    setState(() => _isLoading = true);
    try {
      final cachedStr = await DatabaseService().getCachedGenericData('jurnal_penyesuaian_list');
      if (cachedStr != null) {
        _populateData(cachedStr);
      }
    } catch (_) {}

    if (!mounted) return;
    final isOnline = context.read<ConnectivityProvider>().isOnline;
    
    if (isOnline) {
      try {
        final response = await ApiService().get(ApiConfig.jurnalPenyesuaian);
        if (response.statusCode == 200 && response.data['status'] == 'success') {
          final data = response.data['data'];
          await DatabaseService().cacheGenericData('jurnal_penyesuaian_list', data);
          if (mounted) _populateData(data);
        }
      } catch (_) {}
    }
    
    if (mounted) setState(() => _isLoading = false);
  }

  void _populateData(dynamic data) {
    setState(() {
      _akunList = List<Map<String, dynamic>>.from(data['akun_list'] ?? []);
      _asetList = List<Map<String, dynamic>>.from(data['aset_list'] ?? []);
      _prepaidList = List<Map<String, dynamic>>.from(data['prepaid_list'] ?? []);
    });
  }

  void _onPeriodeChange() {
    if (_bulan != null && _tahun != null) {
      final year = int.parse(_tahun!);
      final month = int.parse(_bulan!);
      final lastDay = DateTime(year, month + 1, 0).day;
      setState(() {
        _tanggalOtomatis = "$year-${_bulan!.padLeft(2, '0')}-${lastDay.toString().padLeft(2, '0')}";
      });
    } else {
      setState(() => _tanggalOtomatis = null);
    }
    
    if (_jenisPenyesuaian == 'penyusutan' && _bulan != '12') {
      setState(() => _jenisPenyesuaian = null);
    }
    _updateLogic();
  }

  void _onJenisChange(String? val) {
    setState(() {
      _jenisPenyesuaian = val;
      _isReversing = false;
      
      if (val == 'beban_akrual') {
        _stdDesc = "Penyesuaian Beban Akrual";
      } else if (val == 'pendapatan_akrual') {
        _stdDesc = "Penyesuaian Pendapatan Akrual";
      }
    });
    _updateLogic();
  }

  double _getPerlengkapanTerpakai() {
    final awal = double.tryParse(_plkAwalCtrl.text.replaceAll('.', '').replaceAll(',', '')) ?? 0;
    final beli = double.tryParse(_plkBeliCtrl.text.replaceAll('.', '').replaceAll(',', '')) ?? 0;
    final akhir = double.tryParse(_plkAkhirCtrl.text.replaceAll('.', '').replaceAll(',', '')) ?? 0;
    final result = awal + beli - akhir;
    return result > 0 ? result : 0;
  }

  void _updateLogic() {
    String? debet;
    String? kredit;
    double nilai = 0;
    String desc = '';
    String? refId;

    if (_jenisPenyesuaian == 'penyusutan') {
      final aset = _asetList.where((a) => a['id'].toString() == _astId).firstOrNull;
      if (aset != null) {
        debet = aset['akun_beban'];
        kredit = aset['akun_akumulasi'];
        final harga = double.tryParse(aset['harga_perolehan'].toString()) ?? 0;
        final residu = double.tryParse(aset['nilai_residu'].toString()) ?? 0;
        final umurBulan = double.tryParse(aset['umur_ekonomis_bulan'].toString()) ?? 0;
        if (umurBulan > 0) {
          nilai = ((harga - residu) / (umurBulan / 12)).roundToDouble();
        } else {
          final perBulan = double.tryParse(aset['nilai_penyusutan_per_bulan'].toString()) ?? 0;
          nilai = (perBulan * 12).roundToDouble();
        }
        refId = aset['id'].toString();
      }
      desc = _astDesc;
    } 
    else if (_jenisPenyesuaian == 'prepaid_expense') {
      final prep = _prepaidList.where((p) => p['id'].toString() == _prpId).firstOrNull;
      if (prep != null) {
        debet = prep['akun_beban'];
        kredit = prep['akun_prepaid'];
        nilai = double.tryParse(prep['nilai_per_bulan'].toString()) ?? 0;
        refId = prep['id'].toString();
      }
      desc = _prpDesc;
    }
    else if (_jenisPenyesuaian == 'perlengkapan') {
      debet = '503'; // Asumsi Beban Perlengkapan
      kredit = _plkAkun;
      nilai = _getPerlengkapanTerpakai();
      desc = _plkDesc;
    }
    else if (_jenisPenyesuaian == 'beban_akrual' || _jenisPenyesuaian == 'pendapatan_akrual') {
      debet = _stdAkunDebet;
      kredit = _stdAkunKredit;
      nilai = double.tryParse(_stdNilaiCtrl.text.replaceAll('.', '').replaceAll(',', '')) ?? 0;
      desc = _stdDesc;
    }

    setState(() {
      _finalDebet = debet;
      _finalKredit = kredit;
      _finalNilai = nilai;
      _finalDesc = desc;
      _referensiId = refId;
    });
  }

  String _getAkunName(String? kode) {
    if (kode == null) return '-';
    final a = _akunList.where((x) => x['kode_akun'] == kode).firstOrNull;
    return a != null ? (a['akun'] ?? a['nama_akun'] ?? '-') : '-';
  }

  bool get _isBalance {
    return _finalNilai > 0 && 
           _finalDebet != null && _finalDebet!.isNotEmpty && 
           _finalKredit != null && _finalKredit!.isNotEmpty && 
           _finalDebet != _finalKredit && 
           _tanggalOtomatis != null;
  }

  Future<void> _submit() async {
    if (!_isBalance) return;
    setState(() => _isSubmitting = true);

    final payload = {
      'tanggal': _tanggalOtomatis,
      'deskripsi': _finalDesc,
      'jenis_penyesuaian': _jenisPenyesuaian,
      'akun_debet': _finalDebet,
      'akun_kredit': _finalKredit,
      'nilai': _finalNilai,
      'referensi_id': _referensiId,
      'is_reversing': _isReversing ? 1 : 0,
    };

    final isOnline = context.read<ConnectivityProvider>().isOnline;
    if (!isOnline) {
      await DatabaseService().insertOfflinePenyesuaian(payload);
      if (mounted) {
        await context.read<ConnectivityProvider>().refreshPendingCount();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Disimpan offline. Akan disinkronkan saat online.'), backgroundColor: AppTheme.warning),
        );
        _resetForm();
        setState(() => _isSubmitting = false);
      }
      return;
    }

    try {
      final response = await ApiService().post(ApiConfig.jurnalPenyesuaian, data: payload);
      if (response.statusCode == 201 && response.data['status'] == 'success') {
        if (mounted) {
          final kode = response.data['data']['kode'];
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('✓ Jurnal penyesuaian $kode berhasil disimpan'), backgroundColor: AppTheme.success));
          _resetForm();
        }
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e'), backgroundColor: AppTheme.danger));
    }

    if (mounted) setState(() => _isSubmitting = false);
  }

  void _resetForm() {
    setState(() {
      _jenisPenyesuaian = null;
      _astId = null;
      _astDesc = '';
      _prpId = null;
      _prpDesc = '';
      _plkAkun = null;
      _plkAwalCtrl.clear();
      _plkBeliCtrl.clear();
      _plkAkhirCtrl.clear();
      _plkDesc = '';
      _stdAkunDebet = null;
      _stdAkunKredit = null;
      _stdNilaiCtrl.clear();
      _stdDesc = '';
      _isReversing = false;
      _updateLogic();
    });
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    
    final currentYear = DateTime.now().year;
    final years = List.generate(4, (i) => (currentYear - 2 + i).toString());
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      drawer: const AppDrawer(currentRoute: '/jurnal-penyesuaian'),
      appBar: AppBar(title: const Text('Jurnal Penyesuaian Dinamis')),
      body: RefreshIndicator(
        onRefresh: _loadFormData,
        color: AppTheme.teal,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (!context.watch<ConnectivityProvider>().isOnline)
              Container(
                margin: const EdgeInsets.only(bottom: 16),
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                decoration: BoxDecoration(
                  color: AppTheme.warning.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppTheme.warning.withValues(alpha: 0.3)),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.cloud_off_rounded, color: AppTheme.warning, size: 20),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'Offline Mode: Data akan disimpan lokal dan disinkronkan otomatis',
                        style: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.w600, color: AppTheme.warning),
                      ),
                    ),
                  ],
                ),
              ),
            
            // Periode
            Row(
              children: [
                Expanded(
                  child: DropdownButtonFormField<String>(
                    isExpanded: true,
                    value: _bulan,
                    decoration: const InputDecoration(labelText: 'Bulan Periode'),
                    items: _namaBulan.map((b) => DropdownMenuItem(value: b['val'], child: Text(b['name']!))).toList(),
                    onChanged: (v) { _bulan = v; _onPeriodeChange(); },
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: DropdownButtonFormField<String>(
                    isExpanded: true,
                    value: _tahun,
                    decoration: const InputDecoration(labelText: 'Tahun'),
                    items: years.map((y) => DropdownMenuItem(value: y, child: Text(y))).toList(),
                    onChanged: (v) { _tahun = v; _onPeriodeChange(); },
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: TextEditingController(text: _tanggalOtomatis ?? ''),
              readOnly: true,
              decoration: InputDecoration(
                labelText: 'Tanggal Transaksi (Otomatis)',
                filled: true,
                fillColor: isDark ? AppTheme.navy700 : Colors.grey.shade100,
              ),
            ),
            const SizedBox(height: 16),
            
            // Jenis Penyesuaian
            DropdownButtonFormField<String>(
              isExpanded: true,
              value: _jenisPenyesuaian,
              decoration: const InputDecoration(labelText: 'Jenis Penyesuaian', border: OutlineInputBorder(borderSide: BorderSide(color: AppTheme.teal))),
              items: [
                const DropdownMenuItem(value: 'beban_akrual', child: Text('1. Beban Akrual (Accrued Expense)')),
                const DropdownMenuItem(value: 'pendapatan_akrual', child: Text('2. Pendapatan Akrual (Accrued Revenue)')),
                const DropdownMenuItem(value: 'prepaid_expense', child: Text('3. Prepaid Expense (beban dibayar di muka)')),
                const DropdownMenuItem(value: 'perlengkapan', child: Text('4. Perlengkapan (perlengkapan terpakai)')),
                if (_bulan == '12') const DropdownMenuItem(value: 'penyusutan', child: Text('5. Penyusutan (penurunan nilai aset tahunan)')),
              ],
              onChanged: _tanggalOtomatis == null ? null : _onJenisChange, // Only selectable if period is set
            ),
            
            const SizedBox(height: 20),
            
            // Dynamic Sections
            if (_jenisPenyesuaian == 'penyusutan') _buildPenyusutanSection(),
            if (_jenisPenyesuaian == 'prepaid_expense') _buildPrepaidSection(),
            if (_jenisPenyesuaian == 'perlengkapan') _buildPerlengkapanSection(),
            if (_jenisPenyesuaian == 'beban_akrual' || _jenisPenyesuaian == 'pendapatan_akrual') _buildStandardSection(),

            const SizedBox(height: 24),
            
            SizedBox(
              height: 50,
              child: ElevatedButton(
                onPressed: _isBalance && !_isSubmitting ? _submit : null,
                style: ElevatedButton.styleFrom(backgroundColor: AppTheme.teal),
                child: _isSubmitting 
                    ? const CircularProgressIndicator(color: Colors.white)
                    : Text('Simpan Jurnal Penyesuaian', style: GoogleFonts.dmSans(fontWeight: FontWeight.w700)),
              ),
            ),
            
            const SizedBox(height: 24),
            
            // Preview Card
            _buildPreviewCard(isDark),
          ],
        ),
      ),
      ),
    );
  }

  Widget _buildPenyusutanSection() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(border: Border.all(color: AppTheme.borderLight), borderRadius: BorderRadius.circular(8)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          DropdownButtonFormField<String>(
            value: _astId,
            isExpanded: true,
            decoration: const InputDecoration(labelText: 'Pilih Aset'),
            items: _asetList.map((a) {
              final harga = double.tryParse(a['harga_perolehan'].toString()) ?? 0;
              final residu = double.tryParse(a['nilai_residu'].toString()) ?? 0;
              final umurBulan = double.tryParse(a['umur_ekonomis_bulan'].toString()) ?? 0;
              final penytahun = umurBulan > 0 
                  ? ((harga - residu) / (umurBulan / 12)).roundToDouble() 
                  : ((double.tryParse(a['nilai_penyusutan_per_bulan'].toString()) ?? 0) * 12).roundToDouble();
              final label = "${a['nama_aset']} (Rp${formatRupiah(a['harga_perolehan'])}) - Rp${formatRupiah(penytahun)}/th";
              return DropdownMenuItem(value: a['id'].toString(), child: Text(label, style: GoogleFonts.dmSans(fontSize: 12), overflow: TextOverflow.ellipsis));
            }).toList(),
            onChanged: (v) { _astId = v; _updateLogic(); },
          ),
          const SizedBox(height: 12),
          TextFormField(
            onChanged: (v) { _astDesc = v; _updateLogic(); },
            decoration: const InputDecoration(labelText: 'Keterangan', hintText: 'Contoh: Penyusutan Mesin Produksi Tahun Ini'),
          ),
        ],
      ),
    );
  }

  Widget _buildPrepaidSection() {
    final prep = _prepaidList.where((p) => p['id'].toString() == _prpId).firstOrNull;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(border: Border.all(color: AppTheme.borderLight), borderRadius: BorderRadius.circular(8)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          DropdownButtonFormField<String>(
            value: _prpId,
            isExpanded: true,
            decoration: const InputDecoration(labelText: 'Pilih Prepaid'),
            items: _prepaidList.map((p) {
              final label = "${p['nama_prepaid']} (Rp${formatRupiah(p['total_nilai'])} / ${p['lama_bulan']} bulan) - Rp${formatRupiah(p['nilai_per_bulan'])}/bln";
              return DropdownMenuItem(value: p['id'].toString(), child: Text(label, style: GoogleFonts.dmSans(fontSize: 12), overflow: TextOverflow.ellipsis));
            }).toList(),
            onChanged: (v) { _prpId = v; _updateLogic(); },
          ),
          if (prep != null) ...[
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(color: AppTheme.teal.withValues(alpha: 0.05), borderRadius: BorderRadius.circular(8)),
              child: Column(
                children: [
                  _infoRow('Beban Per Bulan:', 'Rp ${formatRupiah(prep['nilai_per_bulan'])}'),
                  _infoRow('Sudah Dibebankan:', '${prep['bulan_terpakai']} bulan'),
                  Builder(builder: (context) {
                    final lama = double.tryParse(prep['lama_bulan'].toString()) ?? 0;
                    final terpakai = double.tryParse(prep['bulan_terpakai'].toString()) ?? 0;
                    final progress = lama > 0 ? (terpakai / lama) : 0.0;
                    final sisa = (lama - terpakai).round();
                    
                    return Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _infoRow('Sisa Umur:', '$sisa bulan'),
                        const SizedBox(height: 8),
                        Text('Progress Masa Manfaat: ${(progress * 100).round()}%', style: const TextStyle(fontSize: 11)),
                        const SizedBox(height: 4),
                        LinearProgressIndicator(value: progress, color: AppTheme.teal, minHeight: 6),
                      ],
                    );
                  })
                ],
              ),
            )
          ],
          const SizedBox(height: 12),
          TextFormField(
            onChanged: (v) { _prpDesc = v; _updateLogic(); },
            decoration: const InputDecoration(labelText: 'Keterangan', hintText: 'Contoh: Beban sewa Januari'),
          ),
        ],
      ),
    );
  }
  
  Widget _infoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
        Text(label, style: const TextStyle(fontSize: 12)),
        Text(value, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
      ]),
    );
  }

  Widget _buildPerlengkapanSection() {
    final plkList = _akunList.where((a) => a['aktiva_pasiva'] == 'A' && ((a['akun'] ?? '').toLowerCase().contains('supply') || (a['akun'] ?? '').toLowerCase().contains('perlengkapan'))).toList();
    
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(border: Border.all(color: AppTheme.borderLight), borderRadius: BorderRadius.circular(8)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          DropdownButtonFormField<String>(
            value: _plkAkun,
            isExpanded: true,
            decoration: const InputDecoration(labelText: 'Perlengkapan'),
            items: plkList.map((a) => DropdownMenuItem(value: a['kode_akun'].toString(), child: Text('${a['kode_akun']} - ${a['akun'] ?? a['nama_akun']}', style: GoogleFonts.dmSans(fontSize: 12)))).toList(),
            onChanged: (v) { _plkAkun = v; _updateLogic(); },
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(child: TextFormField(
                controller: _plkAwalCtrl, keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Saldo Awal (Rp)'),
                onChanged: (_) { setState((){}); _updateLogic(); },
              )),
              const SizedBox(width: 8),
              Expanded(child: TextFormField(
                controller: _plkBeliCtrl, keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Pembelian (Rp)'),
                onChanged: (_) { setState((){}); _updateLogic(); },
              )),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(child: TextFormField(
                controller: _plkAkhirCtrl, keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Stok Akhir (Rp)'),
                onChanged: (_) { setState((){}); _updateLogic(); },
              )),
              const SizedBox(width: 8),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Terpakai (Rp)', style: TextStyle(fontSize: 12, color: AppTheme.textMutedDark)),
                    const SizedBox(height: 4),
                    Text(formatRupiah(_getPerlengkapanTerpakai()), style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: AppTheme.danger)),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          TextFormField(
            onChanged: (v) { _plkDesc = v; _updateLogic(); },
            decoration: const InputDecoration(labelText: 'Keterangan', hintText: 'Contoh: Perlengkapan kantor terpakai'),
          ),
        ],
      ),
    );
  }

  Widget _buildStandardSection() {
    List<Map<String, dynamic>> optDebet = [];
    List<Map<String, dynamic>> optKredit = [];

    if (_jenisPenyesuaian == 'beban_akrual') {
      optDebet = _akunList.where((a) {
        final kat = (a['kategori_neraca'] ?? '').toString().toLowerCase();
        return kat == 'beban' || kat.contains('beban');
      }).toList();
      optKredit = _akunList.where((a) {
        final kat = (a['kategori_neraca'] ?? '').toString().toLowerCase();
        return kat.contains('utang');
      }).toList();
    } else if (_jenisPenyesuaian == 'pendapatan_akrual') {
      optDebet = _akunList.where((a) {
        final akun = (a['akun'] ?? a['nama_akun'] ?? '').toString().toLowerCase();
        return akun.contains('piutang');
      }).toList();
      optKredit = _akunList.where((a) {
        final kat = (a['kategori_neraca'] ?? '').toString().toLowerCase();
        return kat == 'pendapatan';
      }).toList();
    } else {
      optDebet = _akunList;
      optKredit = _akunList;
    }

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(border: Border.all(color: AppTheme.borderLight), borderRadius: BorderRadius.circular(8)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          DropdownButtonFormField<String>(
            value: _stdAkunDebet,
            isExpanded: true,
            decoration: const InputDecoration(labelText: 'Akun Debet'),
            items: optDebet.map((a) => DropdownMenuItem(value: a['kode_akun'].toString(), child: Text('${a['kode_akun']} - ${a['akun'] ?? a['nama_akun']}', style: GoogleFonts.dmSans(fontSize: 12)))).toList(),
            onChanged: (v) { _stdAkunDebet = v; _updateLogic(); },
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<String>(
            value: _stdAkunKredit,
            isExpanded: true,
            decoration: const InputDecoration(labelText: 'Akun Kredit'),
            items: optKredit.map((a) => DropdownMenuItem(value: a['kode_akun'].toString(), child: Text('${a['kode_akun']} - ${a['akun'] ?? a['nama_akun']}', style: GoogleFonts.dmSans(fontSize: 12)))).toList(),
            onChanged: (v) { _stdAkunKredit = v; _updateLogic(); },
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _stdNilaiCtrl, keyboardType: TextInputType.number,
            decoration: const InputDecoration(labelText: 'Nilai (Rp)'),
            onChanged: (_) => _updateLogic(),
          ),
          const SizedBox(height: 12),
          TextFormField(
            onChanged: (v) { _stdDesc = v; _updateLogic(); },
            decoration: InputDecoration(labelText: 'Keterangan', hintText: _stdDesc),
          ),
          const SizedBox(height: 16),
          CheckboxListTile(
            title: const Text('Buat Jurnal Pembalik Otomatis'),
            subtitle: const Text('Sistem otomatis membalik jurnal ini di periode berikutnya.'),
            value: _isReversing,
            onChanged: (v) => setState(() { _isReversing = v ?? false; }),
            controlAffinity: ListTileControlAffinity.leading,
            contentPadding: EdgeInsets.zero,
          )
        ],
      ),
    );
  }

  Widget _buildPreviewCard(bool isDark) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Text('Preview Jurnal', style: GoogleFonts.dmSans(fontSize: 16, fontWeight: FontWeight.bold)),
            ),
            const Divider(),
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: DataTable(
                headingRowHeight: 40,
                dataRowMinHeight: 40,
                dataRowMaxHeight: 40,
                columnSpacing: 16,
                columns: [
                  DataColumn(label: Text('Tanggal', style: GoogleFonts.dmSans(fontWeight: FontWeight.bold, fontSize: 12))),
                  DataColumn(label: Text('Kode', style: GoogleFonts.dmSans(fontWeight: FontWeight.bold, fontSize: 12))),
                  DataColumn(label: Text('Nama Akun', style: GoogleFonts.dmSans(fontWeight: FontWeight.bold, fontSize: 12))),
                  DataColumn(label: Text('Debet', style: GoogleFonts.dmSans(fontWeight: FontWeight.bold, fontSize: 12)), numeric: true),
                  DataColumn(label: Text('Kredit', style: GoogleFonts.dmSans(fontWeight: FontWeight.bold, fontSize: 12)), numeric: true),
                ],
                rows: [
                  DataRow(
                    cells: [
                      DataCell(Text(_tanggalOtomatis ?? '-', style: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.bold))),
                      DataCell(Text(_finalDebet ?? '-', style: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.bold))),
                      DataCell(Text(_getAkunName(_finalDebet), style: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.bold))),
                      DataCell(Text(_finalNilai > 0 ? formatRupiah(_finalNilai) : '-', style: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.bold))),
                      DataCell(Text('-', style: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.bold))),
                    ],
                  ),
                  DataRow(
                    cells: [
                      DataCell(Text('', style: GoogleFonts.dmSans(fontSize: 12))),
                      DataCell(Text(_finalKredit ?? '-', style: GoogleFonts.dmSans(fontSize: 12, fontStyle: FontStyle.italic))),
                      DataCell(Text('  ${_getAkunName(_finalKredit)}', style: GoogleFonts.dmSans(fontSize: 12, fontStyle: FontStyle.italic))),
                      DataCell(Text('-', style: GoogleFonts.dmSans(fontSize: 12, fontStyle: FontStyle.italic))),
                      DataCell(Text(_finalNilai > 0 ? formatRupiah(_finalNilai) : '-', style: GoogleFonts.dmSans(fontSize: 12, fontStyle: FontStyle.italic))),
                    ],
                  ),
                  DataRow(
                    cells: [
                      DataCell(Text('', style: GoogleFonts.dmSans(fontSize: 12))),
                      DataCell(Text('', style: GoogleFonts.dmSans(fontSize: 12))),
                      DataCell(Text('Total Balance', style: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.bold, color: _isBalance ? AppTheme.success : null))),
                      DataCell(Text(_finalNilai > 0 ? formatRupiah(_finalNilai) : '-', style: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.bold, color: _isBalance ? AppTheme.success : null))),
                      DataCell(Text(_finalNilai > 0 ? formatRupiah(_finalNilai) : '-', style: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.bold, color: _isBalance ? AppTheme.success : null))),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
