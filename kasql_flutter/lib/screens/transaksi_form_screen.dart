import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../services/api_service.dart';
import '../services/database_service.dart';
import '../services/connectivity_provider.dart';
import '../config/api_config.dart';
import '../config/app_theme.dart';
import '../utils/helpers.dart';
import 'dart:convert';
import '../widgets/app_drawer.dart';

/// Transaction input form with double-entry validation and offline save.
class TransaksiFormScreen extends StatefulWidget {
  final bool isKoreksi;
  final String? koreksiKode;
  
  const TransaksiFormScreen({
    super.key,
    this.isKoreksi = false,
    this.koreksiKode,
  });

  @override
  State<TransaksiFormScreen> createState() => _TransaksiFormScreenState();
}

class _TransaksiFormScreenState extends State<TransaksiFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _tanggalController = TextEditingController(text: DateTime.now().toString().substring(0, 10));
  final _deskripsiController = TextEditingController();

  List<Map<String, dynamic>> _akunList = [];
  List<Map<String, dynamic>> _reaksiList = [];
  String? _selectedReaksiId;
  List<_DetailRow> _details = [];
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _details = [_DetailRow(), _DetailRow()]; // Start with 2 rows
    _loadAkun().then((_) {
      if (widget.isKoreksi && widget.koreksiKode != null) {
        _loadKoreksi(widget.koreksiKode!);
      }
    });
  }

  Future<void> _loadKoreksi(String kode) async {
    try {
      final response = await ApiService().get(ApiConfig.transaksiShow(kode));
      if (response.statusCode == 200 && response.data['status'] == 'success') {
        final data = response.data['data'];
        setState(() {
          _tanggalController.text = data['tanggal'] ?? _tanggalController.text;
          _deskripsiController.text = data['deskripsi'] ?? '';
          
          if (data['details'] != null) {
            final detailsData = List<Map<String, dynamic>>.from(data['details']);
            _details = detailsData.map((d) {
              final row = _DetailRow();
              row.kodeAkun = d['kode_akun'];
              row.dk = (d['dk'] == 'Debit' || d['dk'] == 'debit') ? 'Debit' : 'Kredit';
              row.nilai = (d['nilai'] ?? 0).toDouble();
              return row;
            }).toList();
            
            while (_details.length < 2) {
              _details.add(_DetailRow());
            }
          }
        });
      }
    } catch (e) {
      debugPrint('Error loading koreksi: $e');
    }
  }

  Future<void> _loadAkun() async {
    // 1. Coba load dari cache dulu supaya tampilan langsung muncul
    final cachedAkun = await DatabaseService().getCachedAkun();
    final cachedReaksi = await DatabaseService().getCachedReaksi();
    
    if (cachedAkun.isNotEmpty) {
      setState(() {
        _akunList = cachedAkun.map((a) => {'kode_akun': a['kode_akun'], 'nama_akun': a['nama_akun']}).toList();
      });
    }
    
    if (cachedReaksi.isNotEmpty) {
      setState(() {
        _reaksiList = cachedReaksi.map((r) {
          dynamic details;
          try {
            details = jsonDecode(r['details'] ?? '[]');
          } catch (e) {
            details = [];
          }
          return {
            'id_reaksi': r['id_reaksi'],
            'nama_reaksi': r['nama_reaksi'],
            'details': details,
          };
        }).toList();
      });
    }

    // 2. Jika online, coba update cache dari API di background
    if (!mounted) return;
    final connectivity = context.read<ConnectivityProvider>();
    if (connectivity.isOnline) {
      try {
        final response = await ApiService().get(ApiConfig.masterAkun);
        if (response.statusCode == 200 && response.data['status'] == 'success') {
          setState(() {
            _akunList = List<Map<String, dynamic>>.from(response.data['data']);
          });
          await DatabaseService().cacheAkunList(_akunList);
        }
        
        final resReaksi = await ApiService().get(ApiConfig.masterReaksi);
        if (resReaksi.statusCode == 200 && resReaksi.data['status'] == 'success') {
          setState(() {
            _reaksiList = List<Map<String, dynamic>>.from(resReaksi.data['data']);
          });
          await DatabaseService().cacheReaksiList(_reaksiList);
        }
      } catch (e) {
        debugPrint('Error updating akun dari API: $e');
        // Tetap pakai cache, jangan biarkan kosong
      }
    } else {
      // Jika offline dan cache benar-benar kosong, beri tahu user
      if (cachedAkun.isEmpty || cachedReaksi.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Data master kosong! Harap nyalakan internet sebentar agar aplikasi bisa mengunduh data.'),
            backgroundColor: AppTheme.danger,
            duration: Duration(seconds: 5),
          ),
        );
      }
    }
  }

  void _onReaksiChanged(String? idReaksi) {
    setState(() {
      _selectedReaksiId = idReaksi;
      if (idReaksi == null) {
        _details = [_DetailRow(), _DetailRow()];
        return;
      }
      
      final reaksi = _reaksiList.firstWhere((r) => r['id_reaksi'].toString() == idReaksi, orElse: () => {});
      if (reaksi.isNotEmpty && reaksi['details'] != null) {
        final detailsData = List<Map<String, dynamic>>.from(reaksi['details']);
        _details = detailsData.map((d) {
          final row = _DetailRow();
          row.kodeAkun = d['kode_akun'];
          row.dk = (d['dk'] == 'Debit' || d['dk'] == 'debit') ? 'Debit' : 'Kredit';
          row.nilai = 0; // User input required
          return row;
        }).toList();
        
        // Ensure at least 2 rows
        while (_details.length < 2) {
          _details.add(_DetailRow());
        }
      }
    });
  }

  double get _totalDebit => _details.fold(0, (sum, d) => sum + (d.dk == 'Debit' ? d.nilai : 0));
  double get _totalKredit => _details.fold(0, (sum, d) => sum + (d.dk == 'Kredit' ? d.nilai : 0));
  bool get _isBalanced => (_totalDebit - _totalKredit).abs() < 0.01 && _totalDebit > 0;

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (!_isBalanced) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Transaksi belum seimbang! Debit: ${formatRupiah(_totalDebit)}, Kredit: ${formatRupiah(_totalKredit)}'), backgroundColor: AppTheme.danger),
      );
      return;
    }

    setState(() => _isSubmitting = true);
    final connectivity = context.read<ConnectivityProvider>();
    final detailsData = _details.where((d) => d.kodeAkun != null && d.nilai > 0).map((d) => {
      'kode_akun': d.kodeAkun,
      'dk': d.dk,
      'nilai': d.nilai,
    }).toList();

    if (connectivity.isOnline) {
      try {
        final Map<String, dynamic> payload = {
          'tanggal': _tanggalController.text,
          'deskripsi': _deskripsiController.text,
          'details': detailsData,
        };
        if (widget.isKoreksi && widget.koreksiKode != null) {
          payload['koreksi_dari'] = widget.koreksiKode;
        }

        final response = await ApiService().post(ApiConfig.transaksiStore, data: payload);
        if (response.statusCode == 201 && response.data['status'] == 'success') {
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text('✓ Transaksi ${response.data['data']['kode_transaksi']} berhasil disimpan'), backgroundColor: AppTheme.success),
            );
            Navigator.pop(context);
          }
        }
      } catch (e) {
        // Fallback to offline save
        await _saveOffline(detailsData);
      }
    } else {
      await _saveOffline(detailsData);
    }
    if (mounted) {
      setState(() => _isSubmitting = false);
    }
  }

  Future<void> _saveOffline(List<Map<String, dynamic>> detailsData) async {
    await DatabaseService().insertOfflineTransaction(
      _tanggalController.text,
      _deskripsiController.text,
      detailsData,
    );
    if (!mounted) return;
    await context.read<ConnectivityProvider>().refreshPendingCount();
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Disimpan offline. Akan disinkronkan saat online.'), backgroundColor: AppTheme.warning),
    );
    Navigator.pop(context);
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

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      drawer: widget.isKoreksi ? null : const AppDrawer(currentRoute: '/transaksi-form'),
      appBar: AppBar(
        title: Text(widget.isKoreksi ? 'Koreksi Transaksi' : 'Input Transaksi'),
        actions: [
          if (_isSubmitting)
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: 16.0),
              child: Center(
                child: SizedBox(
                  width: 20,
                  height: 20,
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
              ),
            ),
        ],
      ),
      body: Form(
        key: _formKey,
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Offline Indicator
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
              // Jenis Aktivitas
              DropdownButtonFormField<String>(
                  value: _selectedReaksiId,
                  isExpanded: true,
                  decoration: InputDecoration(
                    labelText: 'Jenis Aktivitas',
                    prefixIcon: const Icon(Icons.category_rounded, size: 18),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    filled: true,
                    fillColor: isDark ? AppTheme.navy700.withValues(alpha: 0.5) : Colors.grey.shade50,
                  ),
                  items: [
                    const DropdownMenuItem(value: null, child: Text('-- Pilih Jenis / Transaksi Kustom --')),
                    ..._reaksiList.map((r) => DropdownMenuItem(
                      value: r['id_reaksi'].toString(),
                      child: Text(r['nama_reaksi'] ?? '', overflow: TextOverflow.ellipsis),
                    )),
                  ],
                  onChanged: _onReaksiChanged,
                ),
                const SizedBox(height: 16),
              // Tanggal & Deskripsi
              Row(
                children: [
                  Expanded(
                    flex: 2,
                    child: TextFormField(
                      controller: _tanggalController,
                      readOnly: true,
                      onTap: _pickDate,
                      decoration: const InputDecoration(
                        labelText: 'Tanggal',
                        prefixIcon: Icon(Icons.calendar_today_rounded, size: 18),
                      ),
                      validator: (v) => v == null || v.isEmpty ? 'Wajib' : null,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _deskripsiController,
                decoration: const InputDecoration(
                  labelText: 'Deskripsi / Keterangan',
                  prefixIcon: Icon(Icons.notes_rounded, size: 18),
                ),
                validator: (v) => v == null || v.isEmpty ? 'Wajib diisi' : null,
              ),
              const SizedBox(height: 20),

              // Detail rows header
              Text('DETAIL JURNAL', style: GoogleFonts.dmSans(fontSize: 11, fontWeight: FontWeight.w700, letterSpacing: 1, color: AppTheme.textMutedDark)),
              const SizedBox(height: 10),

              // Detail entries
              ..._details.asMap().entries.map((entry) => _buildDetailRow(entry.key, isDark)),

              // Add row button
              TextButton.icon(
                onPressed: () => setState(() => _details.add(_DetailRow())),
                icon: const Icon(Icons.add_circle_outline, size: 18),
                label: Text('Tambah Baris', style: GoogleFonts.dmSans(fontSize: 13, fontWeight: FontWeight.w600)),
              ),
              const SizedBox(height: 16),

              // Balance indicator
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: _isBalanced
                      ? AppTheme.success.withValues(alpha: 0.1)
                      : AppTheme.danger.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: _isBalanced
                        ? AppTheme.success.withValues(alpha: 0.3)
                        : AppTheme.danger.withValues(alpha: 0.3),
                  ),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text('Total Debit', style: GoogleFonts.dmSans(fontSize: 11, color: AppTheme.textMutedDark)),
                      Text(formatRupiah(_totalDebit), style: GoogleFonts.dmSans(fontSize: 15, fontWeight: FontWeight.w700, color: AppTheme.info)),
                    ]),
                    Icon(_isBalanced ? Icons.check_circle_rounded : Icons.warning_amber_rounded, color: _isBalanced ? AppTheme.success : AppTheme.danger),
                    Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
                      Text('Total Kredit', style: GoogleFonts.dmSans(fontSize: 11, color: AppTheme.textMutedDark)),
                      Text(formatRupiah(_totalKredit), style: GoogleFonts.dmSans(fontSize: 15, fontWeight: FontWeight.w700, color: AppTheme.danger)),
                    ]),
                  ],
                ),
              ),
              const SizedBox(height: 20),

              // Submit
              SizedBox(
                height: 50,
                child: ElevatedButton(
                  onPressed: _isSubmitting ? null : _submit,
                  child: _isSubmitting
                      ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : Text('Simpan Transaksi', style: GoogleFonts.dmSans(fontWeight: FontWeight.w700)),
                ),
              ),
              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDetailRow(int index, bool isDark) {
    final detail = _details[index];
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDark ? AppTheme.navy700.withValues(alpha: 0.5) : Colors.grey.shade50,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: isDark ? AppTheme.borderDark : AppTheme.borderLight),
      ),
      child: Column(
        children: [
          Row(
            children: [
              // Akun dropdown
              Expanded(
                flex: 3,
                child: DropdownButtonFormField<String>(
                  value: detail.kodeAkun,
                  isExpanded: true,
                  decoration: InputDecoration(
                    labelText: 'Akun',
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                  style: GoogleFonts.dmSans(fontSize: 12, color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight),
                  items: _akunList.map((a) => DropdownMenuItem(
                    value: a['kode_akun'] as String,
                    child: Text('${a['kode_akun']} - ${a['nama_akun']}', overflow: TextOverflow.ellipsis),
                  )).toList(),
                  onChanged: (v) => setState(() => detail.kodeAkun = v),
                ),
              ),
              const SizedBox(width: 8),
              // Remove button
              if (_details.length > 2)
                IconButton(
                  icon: const Icon(Icons.remove_circle_outline, color: AppTheme.danger, size: 20),
                  onPressed: () => setState(() => _details.removeAt(index)),
                ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              // D/K toggle
              // D/K toggle
              Expanded(
                child: Container(
                  height: 44,
                  decoration: BoxDecoration(
                    color: isDark ? Colors.black26 : Colors.grey.shade200,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: isDark ? AppTheme.borderDark : Colors.grey.shade300),
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: GestureDetector(
                          onTap: () => setState(() => detail.dk = 'Debit'),
                          child: Container(
                            alignment: Alignment.center,
                            decoration: BoxDecoration(
                              color: detail.dk == 'Debit' ? (isDark ? AppTheme.teal : AppTheme.tealDark) : Colors.transparent,
                              borderRadius: BorderRadius.circular(7),
                            ),
                            child: Text(
                              'Debit',
                              style: GoogleFonts.dmSans(
                                fontSize: 13,
                                fontWeight: detail.dk == 'Debit' ? FontWeight.w700 : FontWeight.w500,
                                color: detail.dk == 'Debit' ? Colors.white : (isDark ? Colors.grey.shade400 : Colors.grey.shade600),
                              ),
                            ),
                          ),
                        ),
                      ),
                      Expanded(
                        child: GestureDetector(
                          onTap: () => setState(() => detail.dk = 'Kredit'),
                          child: Container(
                            alignment: Alignment.center,
                            decoration: BoxDecoration(
                              color: detail.dk == 'Kredit' ? AppTheme.danger : Colors.transparent,
                              borderRadius: BorderRadius.circular(7),
                            ),
                            child: Text(
                              'Kredit',
                              style: GoogleFonts.dmSans(
                                fontSize: 13,
                                fontWeight: detail.dk == 'Kredit' ? FontWeight.w700 : FontWeight.w500,
                                color: detail.dk == 'Kredit' ? Colors.white : (isDark ? Colors.grey.shade400 : Colors.grey.shade600),
                              ),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 8),
              // Nominal
              Expanded(
                child: TextFormField(
                  keyboardType: TextInputType.number,
                  decoration: InputDecoration(
                    labelText: 'Nominal',
                    prefixText: 'Rp ',
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                  style: GoogleFonts.dmSans(fontSize: 13, fontWeight: FontWeight.w600),
                  onChanged: (v) {
                    setState(() {
                      detail.nilai = double.tryParse(v.replaceAll('.', '').replaceAll(',', '')) ?? 0;
                    });
                  },
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  @override
  void dispose() {
    _tanggalController.dispose();
    _deskripsiController.dispose();
    super.dispose();
  }
}

class _DetailRow {
  String? kodeAkun;
  String dk = 'Debit';
  double nilai = 0;
}
