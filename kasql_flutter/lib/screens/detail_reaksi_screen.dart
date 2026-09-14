import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:provider/provider.dart';
import '../services/api_service.dart';
import '../services/database_service.dart';
import '../services/connectivity_provider.dart';
import '../config/api_config.dart';
import '../config/app_theme.dart';
import '../widgets/app_drawer.dart';
import '../widgets/common_widgets.dart';
import '../widgets/offline_banner.dart';

/// Detail Reaksi — Transaction reaction templates.
class DetailReaksiScreen extends StatefulWidget {
  const DetailReaksiScreen({super.key});

  @override
  State<DetailReaksiScreen> createState() => _DetailReaksiScreenState();
}

class _DetailReaksiScreenState extends State<DetailReaksiScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _reaksiList = [];
  List<Map<String, dynamic>> _akunList = [];

  @override
  void initState() {
    super.initState();
    _loadData();
    _loadAkun();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    
    final cachedReaksi = await DatabaseService().getCachedReaksi();
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
        _isLoading = false;
      });
    }

    if (!mounted) return;
    final isOnline = context.read<ConnectivityProvider>().isOnline;
    if (isOnline) {
      try {
        final response = await ApiService().get(ApiConfig.masterReaksi);
        if (response.statusCode == 200 && response.data['status'] == 'success') {
          setState(() {
            _reaksiList = List<Map<String, dynamic>>.from(response.data['data']);
          });
          await DatabaseService().cacheReaksiList(_reaksiList);
        }
      } catch (e) {
        debugPrint('Error load data: $e');
      } finally {
        if (mounted) setState(() => _isLoading = false);
      }
    } else {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _loadAkun() async {
    final cachedAkun = await DatabaseService().getCachedAkun();
    if (cachedAkun.isNotEmpty) {
      setState(() {
         _akunList = cachedAkun.map((a) => {'kode_akun': a['kode_akun'], 'nama_akun': a['nama_akun']}).toList();
      });
    }
    
    final isOnline = context.read<ConnectivityProvider>().isOnline;
    if (isOnline) {
      try {
        final response = await ApiService().get(ApiConfig.masterAkun);
        if (response.statusCode == 200 && response.data['status'] == 'success') {
          setState(() {
            _akunList = List<Map<String, dynamic>>.from(response.data['data']);
          });
          await DatabaseService().cacheAkunList(_akunList);
        }
      } catch (e) {
        debugPrint('Error load akun: $e');
      }
    }
  }

  void _showAddReaksiDialog({Map<String, dynamic>? editData}) {
    final isEdit = editData != null;
    final idCtrl = TextEditingController(text: isEdit ? editData['id_reaksi'].toString() : '');
    final namaCtrl = TextEditingController(text: isEdit ? editData['nama_reaksi'] ?? '' : '');

    showDialog(
      context: context,
      builder: (ctx) {
        return AlertDialog(
          title: Text(isEdit ? 'Edit Jenis Reaksi' : 'Tambah Jenis Reaksi', style: GoogleFonts.dmSans(fontWeight: FontWeight.w700)),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: idCtrl,
                decoration: InputDecoration(
                  labelText: 'ID Reaksi (contoh: J01)',
                  hintText: 'Masukkan ID (Huruf & Angka)',
                ),
                enabled: !isEdit,
              ),
              const SizedBox(height: 12),
              TextField(
                controller: namaCtrl,
                decoration: const InputDecoration(labelText: 'Nama Reaksi'),
              ),
            ],
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
            ElevatedButton(
              onPressed: () async {
                if (idCtrl.text.isEmpty || namaCtrl.text.isEmpty) return;
                try {
                  final data = {
                    'id_reaksi': idCtrl.text,
                    'nama_reaksi': namaCtrl.text,
                    'is_edit': isEdit,
                  };
                  final response = await ApiService().post(ApiConfig.masterReaksi, data: data);
                  if (response.statusCode == 200 || response.statusCode == 201) {
                    Navigator.pop(ctx);
                    _loadData();
                    if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(isEdit ? 'Reaksi diperbarui' : 'Reaksi ditambahkan'), backgroundColor: AppTheme.success));
                  }
                } catch (e) {
                  if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e'), backgroundColor: AppTheme.danger));
                }
              },
              child: const Text('Simpan'),
            ),
          ],
        );
      },
    );
  }

  void _deleteReaksi(String id) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Hapus Reaksi?'),
        content: Text('Apakah Anda yakin ingin menghapus reaksi ID: $id?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppTheme.danger, foregroundColor: Colors.white),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Hapus'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      try {
        final response = await ApiService().delete(ApiConfig.masterReaksiDelete(id));
        if (response.statusCode == 200) {
          _loadData();
          if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Reaksi dihapus'), backgroundColor: AppTheme.success));
        }
      } catch (e) {
        String errorMsg = 'Gagal menghapus reaksi.';
        if (e is DioException && e.response?.data != null) {
          errorMsg = e.response?.data['message'] ?? e.toString();
        } else {
          errorMsg = e.toString();
        }
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(errorMsg), backgroundColor: AppTheme.danger));
      }
    }
  }

  void _showAddMappingDialog(String idReaksi, {Map<String, dynamic>? editData}) {
    if (_akunList.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Data Akun belum dimuat, tunggu sebentar.'), backgroundColor: AppTheme.warning));
      return;
    }
    
    final isEdit = editData != null;
    String selectedAkun = isEdit ? editData['kode_akun'] : _akunList.first['kode_akun'];
    String dk = isEdit ? editData['dk'] : 'Debit';

    showDialog(
      context: context,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setStateDialog) {
            return AlertDialog(
              title: Text(isEdit ? 'Edit Mapping Akun' : 'Tambah Mapping Akun', style: GoogleFonts.dmSans(fontWeight: FontWeight.w700)),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  DropdownButtonFormField<String>(
                    value: selectedAkun,
                    decoration: const InputDecoration(labelText: 'Pilih Akun'),
                    isExpanded: true,
                    items: _akunList.map((a) {
                      return DropdownMenuItem<String>(
                        value: a['kode_akun'].toString(),
                        child: Text('${a['kode_akun']} - ${a['nama_akun']}', overflow: TextOverflow.ellipsis),
                      );
                    }).toList(),
                    onChanged: (v) => setStateDialog(() => selectedAkun = v!),
                  ),
                  const SizedBox(height: 16),
                  DropdownButtonFormField<String>(
                    value: dk,
                    decoration: const InputDecoration(labelText: 'Posisi (Debit/Kredit)'),
                    items: const [
                      DropdownMenuItem(value: 'Debit', child: Text('Debit')),
                      DropdownMenuItem(value: 'Kredit', child: Text('Kredit')),
                    ],
                    onChanged: (v) => setStateDialog(() => dk = v!),
                  ),
                ],
              ),
              actions: [
                TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
                ElevatedButton(
                  onPressed: () async {
                    try {
                      final data = {
                        'id_reaksi': idReaksi,
                        'kode_akun': selectedAkun,
                        'dk': dk,
                        'is_edit': isEdit,
                        if (isEdit) 'id_detail_reaksi': editData['id_detail_reaksi'],
                      };
                      final response = await ApiService().post(ApiConfig.masterDetailReaksi, data: data);
                      if (response.statusCode == 200 || response.statusCode == 201) {
                        Navigator.pop(ctx);
                        _loadData();
                        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(isEdit ? 'Mapping diperbarui' : 'Mapping ditambahkan'), backgroundColor: AppTheme.success));
                      }
                    } catch (e) {
                      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e'), backgroundColor: AppTheme.danger));
                    }
                  },
                  child: const Text('Simpan'),
                ),
              ],
            );
          },
        );
      },
    );
  }

  void _deleteMapping(String idDetail) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Hapus Mapping?'),
        content: const Text('Apakah Anda yakin ingin menghapus akun ini dari reaksi?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppTheme.danger, foregroundColor: Colors.white),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Hapus'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      try {
        final response = await ApiService().delete(ApiConfig.masterDetailReaksiDelete(idDetail));
        if (response.statusCode == 200) {
          _loadData();
          if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Mapping dihapus'), backgroundColor: AppTheme.success));
        }
      } catch (e) {
        String errorMsg = 'Gagal menghapus mapping.';
        if (e is DioException && e.response?.data != null) {
          errorMsg = e.response?.data['message'] ?? e.toString();
        } else {
          errorMsg = e.toString();
        }
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(errorMsg), backgroundColor: AppTheme.danger));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      drawer: const AppDrawer(currentRoute: '/detail-reaksi'),
      appBar: AppBar(title: const Text('Detail Reaksi')),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _showAddReaksiDialog(),
        child: const Icon(Icons.add),
      ),
      body: Column(
        children: [
          if (!context.watch<ConnectivityProvider>().isOnline)
            Container(
              margin: const EdgeInsets.all(16),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: AppTheme.danger.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: AppTheme.danger.withValues(alpha: 0.3)),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Icon(Icons.wifi_off_rounded, color: AppTheme.danger, size: 24),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      'Mode Offline. Aksi Mengelola Jenis Reaksi & Mapping dikunci karena membutuhkan validasi langsung ke server.',
                      style: GoogleFonts.dmSans(fontSize: 13, color: AppTheme.danger, fontWeight: FontWeight.w600),
                    ),
                  ),
                ],
              ),
            ),
          Expanded(
            child: _isLoading
                ? ListView(children: List.generate(4, (_) => const Padding(padding: EdgeInsets.all(16), child: ShimmerCard())))
                : _reaksiList.isEmpty
                    ? const EmptyState(icon: Icons.event_note_rounded, title: 'Belum ada template reaksi')
                    : RefreshIndicator(
                        onRefresh: _loadData,
                        child: ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: _reaksiList.length,
                    itemBuilder: (_, index) {
                      final reaksi = _reaksiList[index];
                      final details = List<Map<String, dynamic>>.from(reaksi['details'] ?? []);

                      return Container(
                        margin: const EdgeInsets.only(bottom: 10),
                        decoration: BoxDecoration(
                          color: isDark ? AppTheme.surfaceCardDark : Colors.white,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: isDark ? AppTheme.borderDark : AppTheme.borderLight),
                        ),
                        child: ExpansionTile(
                          tilePadding: const EdgeInsets.symmetric(horizontal: 16),
                          leading: Container(
                            width: 36, height: 36,
                            decoration: BoxDecoration(color: AppTheme.gold.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(10)),
                            child: Center(child: Text('${reaksi['id_reaksi']}', style: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.w800, color: AppTheme.gold))),
                          ),
                          title: Text(reaksi['nama_reaksi'] ?? '', style: GoogleFonts.dmSans(fontSize: 14, fontWeight: FontWeight.w600)),
                          subtitle: Text('${details.length} akun terkait', style: GoogleFonts.dmSans(fontSize: 11, color: AppTheme.textMutedDark)),
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                              color: isDark ? Colors.black12 : Colors.grey.shade50,
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.end,
                                children: [
                                  TextButton.icon(
                                    onPressed: () => _showAddMappingDialog(reaksi['id_reaksi'].toString()),
                                    icon: const Icon(Icons.add_circle_outline, size: 16),
                                    label: const Text('Akun'),
                                  ),
                                  TextButton.icon(
                                    onPressed: () => _showAddReaksiDialog(editData: reaksi),
                                    icon: const Icon(Icons.edit, size: 16),
                                    label: const Text('Edit'),
                                    style: TextButton.styleFrom(foregroundColor: AppTheme.info),
                                  ),
                                  TextButton.icon(
                                    onPressed: () => _deleteReaksi(reaksi['id_reaksi'].toString()),
                                    icon: const Icon(Icons.delete, size: 16),
                                    label: const Text('Hapus'),
                                    style: TextButton.styleFrom(foregroundColor: AppTheme.danger),
                                  ),
                                ],
                              ),
                            ),
                            const Divider(height: 1),
                            ...details.map((d) => Padding(
                              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 4),
                              child: Row(
                                children: [
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                    decoration: BoxDecoration(color: (d['dk'] == 'Debit' ? AppTheme.info : AppTheme.danger).withValues(alpha: 0.1), borderRadius: BorderRadius.circular(4)),
                                    child: Text(d['dk'] ?? '', style: GoogleFonts.dmSans(fontSize: 10, fontWeight: FontWeight.w700, color: d['dk'] == 'Debit' ? AppTheme.info : AppTheme.danger)),
                                  ),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Text('${d['kode_akun']} - ${d['nama_akun'] ?? ''}', style: GoogleFonts.dmSans(fontSize: 12)),
                                  ),
                                  IconButton(
                                    icon: Icon(Icons.edit, size: 16, color: AppTheme.info),
                                    onPressed: () => _showAddMappingDialog(reaksi['id_reaksi'].toString(), editData: d),
                                  ),
                                  IconButton(
                                    icon: Icon(Icons.delete, size: 16, color: AppTheme.danger),
                                    onPressed: () => _deleteMapping(d['id_detail_reaksi'].toString()),
                                  ),
                                ],
                              ),
                            )),
                            const SizedBox(height: 8),
                          ],
                        ),
                      );
                    },
                  ),
                ),
          ),
        ],
      ),
    );
  }
}
