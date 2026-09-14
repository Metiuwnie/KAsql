import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../services/api_service.dart';
import '../services/database_service.dart';
import '../services/connectivity_provider.dart';
import '../config/api_config.dart';
import '../config/app_theme.dart';
import '../utils/helpers.dart';
import '../widgets/app_drawer.dart';
import '../widgets/offline_banner.dart';
import 'dart:convert';

class AsetTetapFormScreen extends StatefulWidget {
  const AsetTetapFormScreen({super.key});

  @override
  State<AsetTetapFormScreen> createState() => _AsetTetapFormScreenState();
}

class _AsetTetapFormScreenState extends State<AsetTetapFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _namaController = TextEditingController();
  final _hargaController = TextEditingController();
  final _residuController = TextEditingController();
  final _umurTahunController = TextEditingController();
  final _umurBulanController = TextEditingController();

  List<Map<String, dynamic>> _akunList = [];
  List<Map<String, dynamic>> _asetList = [];
  String? _akunAset;
  String? _akunAkumulasi;
  String? _akunBeban;
  bool _isSubmitting = false;
  bool _isLoadingList = true;

  @override
  void initState() {
    super.initState();
    _loadAkun();
    _loadAsetList();
  }

  Future<void> _loadAkun() async {
    try {
      final response = await ApiService().get(ApiConfig.masterAkun);
      if (response.statusCode == 200 && response.data['status'] == 'success') {
        setState(() {
          _akunList = List<Map<String, dynamic>>.from(response.data['data']);
        });
      }
    } catch (e) {
      final cached = await DatabaseService().getCachedAkun();
      setState(() {
        _akunList = cached.map((a) => {'kode_akun': a['kode_akun'], 'nama_akun': a['nama_akun']}).toList();
      });
    }
  }

  Future<void> _loadAsetList() async {
    setState(() => _isLoadingList = true);
    
    // 1. Load from cache and offline DB
    try {
      final cachedStr = await DatabaseService().getCachedGenericData('aset_tetap_list');
      if (cachedStr != null) {
        _asetList = List<Map<String, dynamic>>.from(cachedStr);
      }
    } catch (_) {}

    final pending = await DatabaseService().getPendingAsetTetap();
    // Normalize properties for display
    final normalizedPending = pending.map((p) {
      return {
        ...p,
        'umur_ekonomis_bulan': (p['umur_tahun'] * 12) + p['umur_bulan'],
        'penyusutan_per_bulan': ((p['harga_perolehan'] - p['nilai_residu']) / ((p['umur_tahun'] * 12) + p['umur_bulan'])),
      };
    }).toList();

    _asetList = [...normalizedPending, ..._asetList];
    if (mounted) setState(() {});

    if (!mounted) return;
    final connectivity = context.read<ConnectivityProvider>();
    if (connectivity.isOnline) {
      try {
        final response = await ApiService().get(ApiConfig.asetTetap);
        if (response.statusCode == 200 && response.data['status'] == 'success') {
          final onlineData = List<Map<String, dynamic>>.from(response.data['data']);
          await DatabaseService().cacheGenericData('aset_tetap_list', onlineData);
          
          final currentPending = await DatabaseService().getPendingAsetTetap();
          final normPending = currentPending.map((p) {
            return {
              ...p,
              'umur_ekonomis_bulan': (p['umur_tahun'] * 12) + p['umur_bulan'],
              'penyusutan_per_bulan': ((p['harga_perolehan'] - p['nilai_residu']) / ((p['umur_tahun'] * 12) + p['umur_bulan'])),
            };
          }).toList();

          if (mounted) {
            setState(() {
              _asetList = [...normPending, ...onlineData];
            });
          }
        }
      } catch (e) {
        debugPrint('Error loading aset list: $e');
      }
    }
    if (mounted) setState(() => _isLoadingList = false);
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_akunAset == null || _akunAkumulasi == null || _akunBeban == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Harap pilih semua akun terkait (Aset, Akumulasi, Beban)'), backgroundColor: AppTheme.danger),
      );
      return;
    }

    setState(() => _isSubmitting = true);
    
    final harga = double.tryParse(_hargaController.text.replaceAll('.', '').replaceAll(',', '')) ?? 0;
    final residu = double.tryParse(_residuController.text.replaceAll('.', '').replaceAll(',', '')) ?? 0;
    final umurTahun = int.tryParse(_umurTahunController.text) ?? 0;
    final umurBulan = int.tryParse(_umurBulanController.text) ?? 0;
    
    final connectivity = context.read<ConnectivityProvider>();
    if (!connectivity.isOnline) {
      // Offline mode
      await DatabaseService().insertOfflineAsetTetap({
        'nama_aset': _namaController.text,
        'harga_perolehan': harga,
        'nilai_residu': residu,
        'umur_tahun': umurTahun,
        'umur_bulan': umurBulan,
        'akun_aset': _akunAset,
        'akun_akumulasi': _akunAkumulasi,
        'akun_beban': _akunBeban,
        'tanggal': DateTime.now().toString().substring(0, 10),
      });
      if (mounted) {
        await context.read<ConnectivityProvider>().refreshPendingCount();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Disimpan offline. Akan disinkronkan saat online.'), backgroundColor: AppTheme.warning),
        );
        _namaController.clear();
        _hargaController.clear();
        _residuController.clear();
        _umurTahunController.clear();
        _umurBulanController.clear();
        _loadAsetList();
        setState(() => _isSubmitting = false);
      }
      return;
    }

    try {
      final response = await ApiService().post(ApiConfig.asetTetap, data: {
        'nama_aset': _namaController.text,
        'harga_perolehan': harga,
        'nilai_residu': residu,
        'umur_tahun': umurTahun,
        'umur_bulan': umurBulan,
        'akun_aset': _akunAset,
        'akun_akumulasi': _akunAkumulasi,
        'akun_beban': _akunBeban,
      });
      
      if (response.statusCode == 200 && response.data['status'] == 'success') {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('✓ Aset Tetap berhasil disimpan'), backgroundColor: AppTheme.success),
          );
          
          _namaController.clear();
          _hargaController.clear();
          _residuController.clear();
          _umurTahunController.clear();
          _umurBulanController.clear();
          _loadAsetList();
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Gagal menyimpan: $e'), backgroundColor: AppTheme.danger),
        );
      }
    }
    
    if (mounted) setState(() => _isSubmitting = false);
  }

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Depresiasi Aset'),
          bottom: TabBar(
            labelStyle: GoogleFonts.dmSans(fontWeight: FontWeight.w700),
            unselectedLabelStyle: GoogleFonts.dmSans(fontWeight: FontWeight.w500),
            indicatorColor: AppTheme.teal,
            tabs: const [
              Tab(text: 'Input Baru'),
              Tab(text: 'Daftar Aset'),
            ],
          ),
        ),
        drawer: const AppDrawer(currentRoute: '/aset-tetap-form'),
        body: TabBarView(
          children: [
            _buildForm(context),
            _buildList(context),
          ],
        ),
      ),
    );
  }

  Widget _buildForm(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Form(
      key: _formKey,
      child: SingleChildScrollView(
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
            TextFormField(
              controller: _namaController,
              decoration: const InputDecoration(
                labelText: 'Nama Aset',
                prefixIcon: Icon(Icons.category_rounded, size: 18),
              ),
              validator: (v) => v == null || v.isEmpty ? 'Wajib diisi' : null,
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: TextFormField(
                    controller: _hargaController,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(
                      labelText: 'Harga Perolehan',
                      prefixText: 'Rp ',
                    ),
                    validator: (v) => v == null || v.isEmpty ? 'Wajib' : null,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: TextFormField(
                    controller: _residuController,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(
                      labelText: 'Nilai Residu (Opsional)',
                      hintText: '0',
                      prefixText: 'Rp ',
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: TextFormField(
                    controller: _umurTahunController,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(
                      labelText: 'Umur (Tahun)',
                    ),
                    validator: (v) => v == null || v.isEmpty ? 'Wajib' : null,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: TextFormField(
                    controller: _umurBulanController,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(
                      labelText: 'Umur Bulan (Opsional)',
                      hintText: '0',
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),
            
            Text('PENGATURAN AKUN', style: GoogleFonts.dmSans(fontSize: 11, fontWeight: FontWeight.w700, letterSpacing: 1, color: AppTheme.textMutedDark)),
            const SizedBox(height: 10),
            
            DropdownButtonFormField<String>(
              value: _akunAset,
              isExpanded: true,
              decoration: InputDecoration(
                labelText: 'Akun Aset',
                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                filled: true,
                fillColor: isDark ? AppTheme.navy700.withValues(alpha: 0.5) : Colors.grey.shade50,
              ),
              items: _akunList.map((a) => DropdownMenuItem(
                value: a['kode_akun'] as String,
                child: Text('${a['kode_akun']} - ${a['nama_akun']}', overflow: TextOverflow.ellipsis),
              )).toList(),
              onChanged: (v) => setState(() => _akunAset = v),
            ),
            const SizedBox(height: 12),
            
            DropdownButtonFormField<String>(
              value: _akunAkumulasi,
              isExpanded: true,
              decoration: InputDecoration(
                labelText: 'Akun Akumulasi Penyusutan',
                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                filled: true,
                fillColor: isDark ? AppTheme.navy700.withValues(alpha: 0.5) : Colors.grey.shade50,
              ),
              items: _akunList.map((a) => DropdownMenuItem(
                value: a['kode_akun'] as String,
                child: Text('${a['kode_akun']} - ${a['nama_akun']}', overflow: TextOverflow.ellipsis),
              )).toList(),
              onChanged: (v) => setState(() => _akunAkumulasi = v),
            ),
            const SizedBox(height: 12),
            
            DropdownButtonFormField<String>(
              value: _akunBeban,
              isExpanded: true,
              decoration: InputDecoration(
                labelText: 'Akun Beban Penyusutan',
                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                filled: true,
                fillColor: isDark ? AppTheme.navy700.withValues(alpha: 0.5) : Colors.grey.shade50,
              ),
              items: _akunList.map((a) => DropdownMenuItem(
                value: a['kode_akun'] as String,
                child: Text('${a['kode_akun']} - ${a['nama_akun']}', overflow: TextOverflow.ellipsis),
              )).toList(),
              onChanged: (v) => setState(() => _akunBeban = v),
            ),
            const SizedBox(height: 32),

            SizedBox(
              height: 50,
              child: ElevatedButton(
                onPressed: _isSubmitting ? null : _submit,
                child: _isSubmitting
                    ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : Text('Simpan Aset', style: GoogleFonts.dmSans(fontWeight: FontWeight.w700)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildList(BuildContext context) {
    if (_isLoadingList) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_asetList.isEmpty) {
      return Center(
        child: Text(
          'Belum ada data Aset Tetap',
          style: GoogleFonts.dmSans(color: AppTheme.textMutedDark),
        ),
      );
    }

    final isDark = Theme.of(context).brightness == Brightness.dark;

    return RefreshIndicator(
      onRefresh: _loadAsetList,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _asetList.length,
        itemBuilder: (context, index) {
          final item = _asetList[index];
          return Container(
            margin: const EdgeInsets.only(bottom: 12),
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: isDark ? AppTheme.navy700.withValues(alpha: 0.5) : Colors.white,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: isDark ? AppTheme.borderDark : AppTheme.borderLight),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Row(
                        children: [
                          Flexible(
                            child: Text(
                              item['nama_aset'] ?? 'Unknown',
                              style: GoogleFonts.dmSans(
                                fontWeight: FontWeight.w700,
                                fontSize: 15,
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          if (item['sync_status'] == 'pending_insert') ...[
                            const SizedBox(width: 8),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: AppTheme.warning.withValues(alpha: 0.2),
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Icon(Icons.pending_actions, size: 12, color: AppTheme.warning),
                                  const SizedBox(width: 4),
                                  Text('Pending', style: GoogleFonts.dmSans(fontSize: 10, color: AppTheme.warning, fontWeight: FontWeight.w600)),
                                ],
                              ),
                            ),
                          ]
                        ],
                      ),
                    ),
                    Text(
                      '${item['umur_ekonomis_bulan']} Bulan',
                      style: GoogleFonts.dmSans(
                        fontWeight: FontWeight.w600,
                        color: AppTheme.teal,
                        fontSize: 13,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('Harga', style: GoogleFonts.dmSans(fontSize: 12, color: AppTheme.textMutedDark)),
                    Text(
                      formatRupiah(double.tryParse(item['harga_perolehan'].toString()) ?? 0),
                      style: GoogleFonts.dmSans(fontWeight: FontWeight.w600),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('Penyusutan per Bulan', style: GoogleFonts.dmSans(fontSize: 12, color: AppTheme.textMutedDark)),
                    Text(
                      formatRupiah(double.tryParse(item['nilai_penyusutan_per_bulan'].toString()) ?? 0),
                      style: GoogleFonts.dmSans(fontWeight: FontWeight.w600, color: AppTheme.danger),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: isDark ? Colors.black26 : Colors.grey.shade100,
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    '${item['akun_aset']} / ${item['akun_akumulasi']} / ${item['akun_beban']}',
                    style: GoogleFonts.dmSans(fontSize: 10, color: isDark ? Colors.grey.shade400 : Colors.grey.shade700),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  @override
  void dispose() {
    _namaController.dispose();
    _hargaController.dispose();
    _residuController.dispose();
    _umurTahunController.dispose();
    _umurBulanController.dispose();
    super.dispose();
  }
}
