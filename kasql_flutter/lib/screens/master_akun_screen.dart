import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../services/api_service.dart';
import '../services/database_service.dart';
import '../services/connectivity_provider.dart';
import '../config/api_config.dart';
import '../config/app_theme.dart';
import '../widgets/app_drawer.dart';
import '../widgets/common_widgets.dart';
import '../widgets/offline_banner.dart';

/// Daftar Akun (Chart of Accounts) with search, grouped by category.
class MasterAkunScreen extends StatefulWidget {
  const MasterAkunScreen({super.key});

  @override
  State<MasterAkunScreen> createState() => _MasterAkunScreenState();
}

class _MasterAkunScreenState extends State<MasterAkunScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _akunList = [];
  String _searchQuery = '';

  @override
  void initState() { super.initState(); _loadData(); }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    
    final cachedData = await DatabaseService().getCachedAkun();
    if (!mounted) return;
    if (cachedData.isNotEmpty) {
      setState(() {
         _akunList = cachedData.map((a) => {'kode_akun': a['kode_akun'], 'nama_akun': a['nama_akun'], 'aktiva_pasiva': a['aktiva_pasiva'], 'kategori_neraca': a['kategori_neraca']}).toList();
         _isLoading = false;
      });
    }

    final isOnline = context.read<ConnectivityProvider>().isOnline;
    if (isOnline) {
      try {
        final response = await ApiService().get(ApiConfig.masterAkun);
        if (response.statusCode == 200 && response.data['status'] == 'success') {
          setState(() { _akunList = List<Map<String, dynamic>>.from(response.data['data']); _isLoading = false; });
          await DatabaseService().cacheAkunList(_akunList);
        }
      } catch (e) {
        if (cachedData.isEmpty) {
          setState(() => _isLoading = false);
        }
      }
    } else if (cachedData.isEmpty) {
       setState(() => _isLoading = false);
    }
  }

  List<Map<String, dynamic>> get _filtered {
    if (_searchQuery.isEmpty) return _akunList;
    return _akunList.where((a) {
      final kode = (a['kode_akun'] ?? '').toString().toLowerCase();
      final nama = (a['nama_akun'] ?? '').toString().toLowerCase();
      return kode.contains(_searchQuery.toLowerCase()) || nama.contains(_searchQuery.toLowerCase());
    }).toList();
  }

  void _showAddDialog() {
    final kodeCtrl = TextEditingController();
    final namaCtrl = TextEditingController();
    String ap = 'A';
    String kn = 'Aktiva Lancar'; // Default value for Kategori Neraca Detail

    final kategoriNeracaOptions = [
      'Aktiva Lancar', 'Aktiva Tetap', 'Aktiva Lainnya',
      'Utang Lancar', 'Utang Jangka Panjang', 'Ekuitas',
      'Pendapatan', 'Beban'
    ];

    showDialog(context: context, builder: (ctx) {
      return StatefulBuilder(
        builder: (context, setStateDialog) {
          return AlertDialog(
            title: Text('Tambah Akun', style: GoogleFonts.dmSans(fontWeight: FontWeight.w700)),
            content: SingleChildScrollView(
              child: Column(mainAxisSize: MainAxisSize.min, children: [
                TextField(controller: kodeCtrl, decoration: const InputDecoration(labelText: 'Kode Akun')),
                const SizedBox(height: 8),
                TextField(controller: namaCtrl, decoration: const InputDecoration(labelText: 'Nama Akun')),
                const SizedBox(height: 8),
                DropdownButtonFormField<String>(
                  value: ap, 
                  decoration: const InputDecoration(labelText: 'Kategori (Aktiva/Pasiva)'),
                  items: const [
                    DropdownMenuItem(value: 'A', child: Text('A (Aktiva)')), 
                    DropdownMenuItem(value: 'P', child: Text('P (Pasiva)'))
                  ],
                  onChanged: (v) => setStateDialog(() => ap = v ?? 'A')
                ),
                const SizedBox(height: 8),
                DropdownButtonFormField<String>(
                  value: kn, 
                  decoration: const InputDecoration(labelText: 'Kategori Neraca Detail'),
                  items: kategoriNeracaOptions.map((String value) {
                    return DropdownMenuItem<String>(
                      value: value,
                      child: Text(value),
                    );
                  }).toList(),
                  onChanged: (v) => setStateDialog(() => kn = v ?? 'Aktiva Lancar')
                ),
              ]),
            ),
            actions: [
              TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
              ElevatedButton(onPressed: () async {
                try {
                  await ApiService().post(ApiConfig.masterAkun, data: {
                    'kode_akun': kodeCtrl.text, 
                    'nama_akun': namaCtrl.text, 
                    'aktiva_pasiva': ap,
                    'kategori_neraca': kn
                  });
                  if (!mounted) return;
                  Navigator.pop(ctx);
                  _loadData();
                  ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('✓ Akun berhasil ditambahkan'), backgroundColor: AppTheme.success));
                } catch (e) {
                  if (!mounted) return;
                  ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e'), backgroundColor: AppTheme.danger));
                }
              }, child: const Text('Simpan')),
            ],
          );
        },
      );
    });
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final filtered = _filtered;

    // Group by first digit
    Map<String, List<Map<String, dynamic>>> grouped = {};
    for (final akun in filtered) {
      final category = _getCategoryName(akun['kode_akun']?.toString() ?? '');
      grouped.putIfAbsent(category, () => []);
      grouped[category]!.add(akun);
    }

    return Scaffold(
      drawer: const AppDrawer(currentRoute: '/master-akun'),
      appBar: AppBar(title: const Text('Daftar Akun')),
      floatingActionButton: FloatingActionButton(onPressed: _showAddDialog, child: const Icon(Icons.add)),
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
                      'Mode Offline. Aksi Menambah, Mengedit, atau Menghapus Akun dikunci karena membutuhkan sinkronisasi langsung ke server.',
                      style: GoogleFonts.dmSans(fontSize: 13, color: AppTheme.danger, fontWeight: FontWeight.w600),
                    ),
                  ),
                ],
              ),
            ),
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              onChanged: (v) => setState(() => _searchQuery = v),
              decoration: InputDecoration(hintText: 'Cari akun...', prefixIcon: const Icon(Icons.search_rounded, size: 20), border: OutlineInputBorder(borderRadius: BorderRadius.circular(12))),
              style: GoogleFonts.dmSans(fontSize: 14),
            ),
          ),
          Expanded(
            child: _isLoading
                ? ListView(children: List.generate(5, (_) => const Padding(padding: EdgeInsets.symmetric(horizontal: 16, vertical: 4), child: ShimmerCard())))
                : filtered.isEmpty
                    ? const EmptyState(icon: Icons.list_alt_rounded, title: 'Tidak ada akun')
                    : RefreshIndicator(
                        onRefresh: _loadData,
                        child: ListView(
                          padding: const EdgeInsets.fromLTRB(16, 0, 16, 80),
                          children: grouped.entries.map((entry) => Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Padding(
                                padding: const EdgeInsets.symmetric(vertical: 8),
                                child: Text(entry.key, style: GoogleFonts.dmSans(fontSize: 11, fontWeight: FontWeight.w700, letterSpacing: 0.5, color: AppTheme.teal)),
                              ),
                              ...entry.value.map((akun) => Container(
                                margin: const EdgeInsets.only(bottom: 4),
                                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                                decoration: BoxDecoration(
                                  color: isDark ? AppTheme.surfaceCardDark : Colors.white,
                                  borderRadius: BorderRadius.circular(10),
                                  border: Border.all(color: isDark ? AppTheme.borderDark : AppTheme.borderLight),
                                ),
                                child: Row(children: [
                                  Container(
                                    width: 42, height: 42,
                                    decoration: BoxDecoration(color: AppTheme.teal.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(10)),
                                    child: Center(child: Text(akun['kode_akun'] ?? '', style: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.w800, color: AppTheme.teal))),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                                    Text(akun['nama_akun'] ?? '', style: GoogleFonts.dmSans(fontSize: 13, fontWeight: FontWeight.w500)),
                                    Text(akun['kategori_neraca'] ?? '', style: GoogleFonts.dmSans(fontSize: 11, color: AppTheme.textMutedDark)),
                                  ])),
                                  StatusBadge(label: akun['aktiva_pasiva'] == 'A' ? 'Aktiva' : 'Pasiva', color: akun['aktiva_pasiva'] == 'A' ? AppTheme.info : AppTheme.gold),
                                ]),
                              )),
                            ],
                          )).toList(),
                        ),
                      ),
          ),
        ],
      ),
    );
  }

  String _getCategoryName(String kode) {
    if (kode.isEmpty) return 'Lainnya';
    switch (kode[0]) {
      case '1': return 'ASET';
      case '2': return 'KEWAJIBAN';
      case '3': return 'EKUITAS';
      case '4': return 'PENDAPATAN';
      case '5': return 'BEBAN';
      default: return 'LAINNYA';
    }
  }
}
