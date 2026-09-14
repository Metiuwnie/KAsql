import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../services/api_service.dart';
import '../services/connectivity_provider.dart';
import 'package:provider/provider.dart';
import '../config/api_config.dart';
import '../config/app_theme.dart';
import '../utils/helpers.dart';
import '../widgets/app_drawer.dart';
import '../widgets/common_widgets.dart';

/// Jurnal Pembalik — Pending and completed reversing entries.
class JurnalPembalikScreen extends StatefulWidget {
  const JurnalPembalikScreen({super.key});

  @override
  State<JurnalPembalikScreen> createState() => _JurnalPembalikScreenState();
}

class _JurnalPembalikScreenState extends State<JurnalPembalikScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  bool _isLoading = true;
  List<Map<String, dynamic>> _pendingList = [];
  List<Map<String, dynamic>> _doneList = [];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    try {
      final resPending = await ApiService().get(ApiConfig.jurnalPembalikPending);
      final resDone = await ApiService().get(ApiConfig.jurnalPembalikDone);

      setState(() {
        if (resPending.data['status'] == 'success') _pendingList = List<Map<String, dynamic>>.from(resPending.data['data']);
        if (resDone.data['status'] == 'success') _doneList = List<Map<String, dynamic>>.from(resDone.data['data']);
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _processReversal(String kodeAsal) async {
    try {
      final response = await ApiService().post(ApiConfig.jurnalPembalikProses, data: {'kode_asal': kodeAsal});
      if (response.statusCode == 200 && response.data['status'] == 'success') {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('✓ ${response.data['data']['message']}'), backgroundColor: AppTheme.success));
        _loadData();
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e'), backgroundColor: AppTheme.danger));
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      drawer: const AppDrawer(currentRoute: '/jurnal-pembalik'),
      appBar: AppBar(
        title: const Text('Jurnal Pembalik'),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: AppTheme.teal,
          labelColor: AppTheme.teal,
          unselectedLabelColor: AppTheme.textMutedDark,
          tabs: [
            Tab(text: 'Pending (${_pendingList.length})'),
            Tab(text: 'Selesai (${_doneList.length})'),
          ],
        ),
      ),
      body: Column(
        children: [
          if (!context.watch<ConnectivityProvider>().isOnline)
            Container(
              width: double.infinity,
              margin: const EdgeInsets.all(16),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: AppTheme.danger.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: AppTheme.danger.withValues(alpha: 0.3)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.cloud_off_rounded, color: AppTheme.danger, size: 24),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      'Fitur Jurnal Pembalik dinonaktifkan saat Offline untuk mencegah penggunaan data penyesuaian yang usang. Aplikasi wajib terhubung ke server utama untuk menarik angka jurnal penyesuaian yang paling mutakhir dan valid.',
                      style: GoogleFonts.dmSans(fontSize: 13, color: AppTheme.danger, fontWeight: FontWeight.w600),
                    ),
                  ),
                ],
              ),
            ),
          Expanded(
            child: _isLoading
                ? ListView(children: List.generate(3, (_) => const Padding(padding: EdgeInsets.all(16), child: ShimmerCard())))
                : TabBarView(
                    controller: _tabController,
                    children: [
                // Pending tab
                _pendingList.isEmpty
                    ? const EmptyState(icon: Icons.replay_rounded, title: 'Tidak ada jurnal pembalik pending')
                    : ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _pendingList.length,
                        itemBuilder: (_, i) {
                          final item = _pendingList[i];
                          return Container(
                            margin: const EdgeInsets.only(bottom: 10),
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              color: isDark ? AppTheme.surfaceCardDark : Colors.white,
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(color: AppTheme.warning.withValues(alpha: 0.3)),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(item['kode_transaksi'] ?? '', style: GoogleFonts.dmSans(fontWeight: FontWeight.w700, color: AppTheme.teal)),
                                    const StatusBadge(label: 'Menunggu', color: AppTheme.warning),
                                  ],
                                ),
                                const SizedBox(height: 6),
                                Text(item['deskripsi'] ?? '', style: GoogleFonts.dmSans(fontSize: 13)),
                                Text('Tanggal asal: ${formatTanggal(item['tanggal'])}', style: GoogleFonts.dmSans(fontSize: 11, color: AppTheme.textMutedDark)),
                                Text(formatRupiah(item['total_debet'] ?? 0), style: GoogleFonts.dmSans(fontWeight: FontWeight.w600)),
                                const SizedBox(height: 10),
                                SizedBox(
                                  width: double.infinity,
                                  child: ElevatedButton.icon(
                                    onPressed: () => _processReversal(item['kode_transaksi']),
                                    icon: const Icon(Icons.replay_rounded, size: 18),
                                    label: Text('Proses Pembalik', style: GoogleFonts.dmSans(fontWeight: FontWeight.w600, fontSize: 13)),
                                  ),
                                ),
                              ],
                            ),
                          );
                        },
                      ),
                // Done tab
                _doneList.isEmpty
                    ? const EmptyState(icon: Icons.check_circle_outline, title: 'Belum ada jurnal pembalik selesai')
                    : ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _doneList.length,
                        itemBuilder: (_, i) {
                          final item = _doneList[i];
                          return Container(
                            margin: const EdgeInsets.only(bottom: 10),
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              color: isDark ? AppTheme.surfaceCardDark : Colors.white,
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(color: isDark ? AppTheme.borderDark : AppTheme.borderLight),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(item['kode_transaksi'] ?? '', style: GoogleFonts.dmSans(fontWeight: FontWeight.w700)),
                                    const StatusBadge(label: 'Selesai', color: AppTheme.success),
                                  ],
                                ),
                                Text(item['deskripsi'] ?? '', style: GoogleFonts.dmSans(fontSize: 13)),
                                Text('Dibalik pada: ${formatTanggal(item['reversed_at'])}', style: GoogleFonts.dmSans(fontSize: 11, color: AppTheme.textMutedDark)),
                                Text('Kode pembalik: ${item['reversed_jurnal_kode'] ?? '-'}', style: GoogleFonts.dmSans(fontSize: 11, color: AppTheme.teal)),
                              ],
                            ),
                          );
                        },
                      ),
              ],
                    ),
            ),
        ],
      ),
    );
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }
}
