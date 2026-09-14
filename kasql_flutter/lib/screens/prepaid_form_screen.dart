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

class PrepaidFormScreen extends StatefulWidget {
  const PrepaidFormScreen({super.key});

  @override
  State<PrepaidFormScreen> createState() => _PrepaidFormScreenState();
}

class _PrepaidFormScreenState extends State<PrepaidFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _namaController = TextEditingController();
  final _tanggalController = TextEditingController(text: DateTime.now().toString().substring(0, 10));
  final _nilaiController = TextEditingController();
  final _lamaBulanController = TextEditingController();

  List<Map<String, dynamic>> _akunList = [];
  List<Map<String, dynamic>> _prepaidList = [];
  String? _akunPrepaid;
  String? _akunBeban;
  bool _isSubmitting = false;
  bool _isLoadingList = true;

  @override
  void initState() {
    super.initState();
    _loadAkun();
    _loadPrepaidList();
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

  Future<void> _loadPrepaidList() async {
    setState(() => _isLoadingList = true);
    
    // 1. Load from cache and offline DB
    try {
      final cachedStr = await DatabaseService().getCachedGenericData('prepaid_list');
      if (cachedStr != null) {
        _prepaidList = List<Map<String, dynamic>>.from(cachedStr);
      }
    } catch (_) {}

    final pending = await DatabaseService().getPendingPrepaid();
    _prepaidList = [...pending, ..._prepaidList];
    if (mounted) setState(() {});

    if (!mounted) return;
    final connectivity = context.read<ConnectivityProvider>();
    if (connectivity.isOnline) {
      try {
        final response = await ApiService().get(ApiConfig.asetPrepaid);
        if (response.statusCode == 200 && response.data['status'] == 'success') {
          final onlineData = List<Map<String, dynamic>>.from(response.data['data']);
          await DatabaseService().cacheGenericData('prepaid_list', onlineData);
          
          final currentPending = await DatabaseService().getPendingPrepaid();
          if (mounted) {
            setState(() {
              _prepaidList = [...currentPending, ...onlineData];
            });
          }
        }
      } catch (e) {
        debugPrint('Error loading prepaid list: $e');
      }
    }
    if (mounted) setState(() => _isLoadingList = false);
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime.tryParse(_tanggalController.text) ?? DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime(2030),
    );
    if (picked != null) {
      _tanggalController.text = picked.toString().substring(0, 10);
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_akunPrepaid == null || _akunBeban == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Harap pilih Akun Prepaid (Debit) dan Akun Beban (Kredit)'), backgroundColor: AppTheme.danger),
      );
      return;
    }

    setState(() => _isSubmitting = true);
    
    final totalNilai = double.tryParse(_nilaiController.text.replaceAll('.', '').replaceAll(',', '')) ?? 0;
    final lamaBulan = int.tryParse(_lamaBulanController.text) ?? 0;
    
    final connectivity = context.read<ConnectivityProvider>();
    if (!connectivity.isOnline) {
      // Offline mode
      await DatabaseService().insertOfflinePrepaid({
        'nama_prepaid': _namaController.text,
        'total_nilai': totalNilai,
        'lama_bulan': lamaBulan,
        'tanggal': _tanggalController.text,
        'akun_prepaid': _akunPrepaid,
        'akun_beban': _akunBeban,
      });
      if (mounted) {
        await context.read<ConnectivityProvider>().refreshPendingCount();
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Disimpan offline. Akan disinkronkan saat online.'), backgroundColor: AppTheme.warning),
        );
        _namaController.clear();
        _nilaiController.clear();
        _lamaBulanController.clear();
        _loadPrepaidList();
        setState(() => _isSubmitting = false);
      }
      return;
    }

    try {
      final response = await ApiService().post(ApiConfig.asetPrepaid, data: {
        'nama_prepaid': _namaController.text,
        'total_nilai': totalNilai,
        'lama_bulan': lamaBulan,
        'tanggal_mulai': _tanggalController.text,
        'akun_prepaid': _akunPrepaid,
        'akun_beban': _akunBeban,
      });
      
      if (response.statusCode == 200 && response.data['status'] == 'success') {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('✓ Prepaid Expense berhasil disimpan'), backgroundColor: AppTheme.success),
          );
          
          // Clear form and reload list
          _namaController.clear();
          _nilaiController.clear();
          _lamaBulanController.clear();
          _loadPrepaidList();
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
          title: const Text('Prepaid Expense'),
          bottom: TabBar(
            labelStyle: GoogleFonts.dmSans(fontWeight: FontWeight.w700),
            unselectedLabelStyle: GoogleFonts.dmSans(fontWeight: FontWeight.w500),
            indicatorColor: AppTheme.teal,
            tabs: const [
              Tab(text: 'Input Baru'),
              Tab(text: 'Daftar Prepaid'),
            ],
          ),
        ),
        drawer: const AppDrawer(currentRoute: '/prepaid-form'),
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
                labelText: 'Nama Prepaid',
                prefixIcon: Icon(Icons.label_outline_rounded, size: 18),
              ),
              validator: (v) => v == null || v.isEmpty ? 'Wajib diisi' : null,
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  flex: 3,
                  child: TextFormField(
                    controller: _nilaiController,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(
                      labelText: 'Total Nilai',
                      prefixText: 'Rp ',
                      prefixIcon: Icon(Icons.monetization_on_outlined, size: 18),
                    ),
                    validator: (v) => v == null || v.isEmpty ? 'Wajib' : null,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  flex: 2,
                  child: TextFormField(
                    controller: _lamaBulanController,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(
                      labelText: 'Lama (Bulan)',
                      prefixIcon: Icon(Icons.timelapse_rounded, size: 18),
                    ),
                    validator: (v) => v == null || v.isEmpty ? 'Wajib' : null,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _tanggalController,
              readOnly: true,
              onTap: _pickDate,
              decoration: const InputDecoration(
                labelText: 'Tanggal Mulai',
                prefixIcon: Icon(Icons.calendar_today_rounded, size: 18),
              ),
              validator: (v) => v == null || v.isEmpty ? 'Wajib' : null,
            ),
            const SizedBox(height: 24),
            
            Text('PENGATURAN AKUN', style: GoogleFonts.dmSans(fontSize: 11, fontWeight: FontWeight.w700, letterSpacing: 1, color: AppTheme.textMutedDark)),
            const SizedBox(height: 10),
            
            DropdownButtonFormField<String>(
              value: _akunPrepaid,
              isExpanded: true,
              decoration: InputDecoration(
                labelText: 'Akun Prepaid (Debet)',
                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                filled: true,
                fillColor: isDark ? AppTheme.navy700.withValues(alpha: 0.5) : Colors.grey.shade50,
              ),
              items: _akunList.map((a) => DropdownMenuItem(
                value: a['kode_akun'] as String,
                child: Text('${a['kode_akun']} - ${a['nama_akun']}', overflow: TextOverflow.ellipsis),
              )).toList(),
              onChanged: (v) => setState(() => _akunPrepaid = v),
            ),
            const SizedBox(height: 12),
            
            DropdownButtonFormField<String>(
              value: _akunBeban,
              isExpanded: true,
              decoration: InputDecoration(
                labelText: 'Akun Beban (Kredit)',
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
                    : Text('Simpan Prepaid', style: GoogleFonts.dmSans(fontWeight: FontWeight.w700)),
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

    if (_prepaidList.isEmpty) {
      return Center(
        child: Text(
          'Belum ada data Prepaid Expense',
          style: GoogleFonts.dmSans(color: AppTheme.textMutedDark),
        ),
      );
    }

    final isDark = Theme.of(context).brightness == Brightness.dark;

    return RefreshIndicator(
      onRefresh: _loadPrepaidList,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _prepaidList.length,
        itemBuilder: (context, index) {
          final item = _prepaidList[index];
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
                              item['nama_prepaid'] ?? 'Unknown',
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
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(
                          '${item['lama_bulan']} Bulan',
                          style: GoogleFonts.dmSans(
                            fontWeight: FontWeight.w600,
                            color: AppTheme.teal,
                            fontSize: 13,
                          ),
                        ),
                        if (item['bulan_terpakai'] != null) ...[
                          const SizedBox(height: 4),
                          Builder(
                            builder: (ctx) {
                              final terpakai = int.tryParse(item['bulan_terpakai'].toString()) ?? 0;
                              final lama = int.tryParse(item['lama_bulan'].toString()) ?? 1;
                              final sisa = lama - terpakai;
                              final persen = (terpakai / lama * 100).toInt();
                              
                              if (terpakai >= lama) {
                                return Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                  decoration: BoxDecoration(
                                    color: AppTheme.success.withValues(alpha: 0.1),
                                    borderRadius: BorderRadius.circular(4),
                                  ),
                                  child: Text('Selesai', style: GoogleFonts.dmSans(fontSize: 10, color: AppTheme.success, fontWeight: FontWeight.w700)),
                                );
                              }
                              
                              return Text(
                                '$persen% (Sisa $sisa bln)',
                                style: GoogleFonts.dmSans(
                                  fontSize: 11,
                                  color: AppTheme.warning,
                                  fontWeight: FontWeight.w600,
                                ),
                              );
                            }
                          )
                        ]
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('Total Nilai', style: GoogleFonts.dmSans(fontSize: 12, color: AppTheme.textMutedDark)),
                    Text(
                      formatRupiah(double.tryParse(item['total_nilai'].toString()) ?? 0),
                      style: GoogleFonts.dmSans(fontWeight: FontWeight.w600),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('Nilai per Bulan', style: GoogleFonts.dmSans(fontSize: 12, color: AppTheme.textMutedDark)),
                    Text(
                      formatRupiah(double.tryParse(item['nilai_per_bulan'].toString()) ?? 0),
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
                    'Akun: ${item['akun_prepaid']} → ${item['akun_beban']}',
                    style: GoogleFonts.dmSans(fontSize: 11, color: isDark ? Colors.grey.shade400 : Colors.grey.shade700),
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
    _tanggalController.dispose();
    _nilaiController.dispose();
    _lamaBulanController.dispose();
    super.dispose();
  }
}
