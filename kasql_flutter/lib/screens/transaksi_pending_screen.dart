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

/// Pending transaction verification screen with status tabs.
class TransaksiPendingScreen extends StatefulWidget {
  const TransaksiPendingScreen({super.key});

  @override
  State<TransaksiPendingScreen> createState() => _TransaksiPendingScreenState();
}

class _TransaksiPendingScreenState extends State<TransaksiPendingScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final TextEditingController _searchController = TextEditingController();
  bool _isLoading = true;
  List<Map<String, dynamic>> _transactions = [];
  Map<String, int> _counts = {};
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _loadData('pending');
    _tabController.addListener(() {
      if (!_tabController.indexIsChanging) {
        final statuses = ['pending', 'sesuai', 'koreksi'];
        _loadData(statuses[_tabController.index]);
      }
    });
  }

  Future<void> _loadData(String status) async {
    setState(() => _isLoading = true);
    try {
      final response = await ApiService().get(ApiConfig.transaksiPending, queryParams: {'status': status});
      if (response.statusCode == 200 && response.data['status'] == 'success') {
        setState(() {
          _transactions = List<Map<String, dynamic>>.from(response.data['data']['transactions']);
          _counts = Map<String, int>.from(response.data['data']['counts'] ?? {});
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _verifyTransaction(String kode) async {
    try {
      final response = await ApiService().post(ApiConfig.transaksiVerify, data: {'kode_transaksi': kode});
      if (response.statusCode == 200) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('✓ $kode berhasil diposting'), backgroundColor: AppTheme.success));
        _loadData('pending');
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e'), backgroundColor: AppTheme.danger));
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      drawer: const AppDrawer(currentRoute: '/transaksi-pending'),
      appBar: AppBar(
        title: const Text('Transaksi Pending'),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: AppTheme.teal,
          labelColor: AppTheme.teal,
          unselectedLabelColor: AppTheme.textMutedDark,
          labelStyle: GoogleFonts.dmSans(fontWeight: FontWeight.w600, fontSize: 13),
          tabs: [
            Tab(text: 'Pending (${_counts['pending'] ?? 0})'),
            Tab(text: 'Disetujui (${_counts['sesuai'] ?? 0})'),
            Tab(text: 'Koreksi (${_counts['koreksi'] ?? 0})'),
          ],
        ),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16.0),
            child: TextField(
              controller: _searchController,
              decoration: InputDecoration(
                hintText: 'Cari kode transaksi atau deskripsi...',
                prefixIcon: const Icon(Icons.search_rounded),
                filled: true,
                fillColor: isDark ? AppTheme.surfaceCardDark : Colors.grey.shade100,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide.none,
                ),
                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              ),
              onChanged: (value) {
                setState(() {
                  _searchQuery = value.toLowerCase();
                });
              },
            ),
          ),
          if (!context.watch<ConnectivityProvider>().isOnline)
            Container(
              width: double.infinity,
              margin: const EdgeInsets.only(left: 16, right: 16, bottom: 16),
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
                      'Mode Offline. Aksi Posting dan Koreksi dikunci karena membutuhkan validasi langsung dari database utama di server untuk memastikan konsistensi dan ketersediaan saldo akuntansi Anda.',
                      style: GoogleFonts.dmSans(fontSize: 13, color: AppTheme.danger, fontWeight: FontWeight.w600),
                    ),
                  ),
                ],
              ),
            ),
          Expanded(
            child: Builder(
              builder: (context) {
                final filteredTransactions = _transactions.where((trx) {
                  final kode = (trx['kode_transaksi'] ?? '').toString().toLowerCase();
                  final deskripsi = (trx['deskripsi'] ?? '').toString().toLowerCase();
                  return kode.contains(_searchQuery) || deskripsi.contains(_searchQuery);
                }).toList();

                if (_isLoading) {
                  return ListView(children: List.generate(4, (_) => const Padding(padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8), child: ShimmerCard())));
                }

                if (filteredTransactions.isEmpty) {
                  return const EmptyState(icon: Icons.fact_check_outlined, title: 'Tidak ada data');
                }

                return RefreshIndicator(
                  onRefresh: () => _loadData(['pending', 'sesuai', 'koreksi'][_tabController.index]),
                  child: ListView.builder(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                    itemCount: filteredTransactions.length,
                    itemBuilder: (context, index) {
                      final trx = filteredTransactions[index];
                      final status = trx['status_verifikasi'] ?? 'pending';
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
                                Text(trx['kode_transaksi'] ?? '', style: GoogleFonts.dmSans(fontWeight: FontWeight.w700, color: AppTheme.teal)),
                                StatusBadge(label: getVerificationLabel(status), color: getVerificationColor(status)),
                              ],
                            ),
                            const SizedBox(height: 6),
                            Text(trx['deskripsi'] ?? '', style: GoogleFonts.dmSans(fontSize: 13)),
                            const SizedBox(height: 4),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(formatTanggal(trx['tanggal']), style: GoogleFonts.dmSans(fontSize: 11, color: AppTheme.textMutedDark)),
                                Text(formatRupiah(trx['total_debit'] ?? 0), style: GoogleFonts.dmSans(fontWeight: FontWeight.w600)),
                              ],
                            ),
                            if (status == 'pending') ...[
                              const SizedBox(height: 10),
                              Row(
                                children: [
                                  Expanded(
                                    child: ElevatedButton.icon(
                                      onPressed: () => _verifyTransaction(trx['kode_transaksi']),
                                      icon: const Icon(Icons.check_rounded, size: 18),
                                      label: Text('Posting', style: GoogleFonts.dmSans(fontWeight: FontWeight.w600, fontSize: 13)),
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: AppTheme.success,
                                        foregroundColor: Colors.white,
                                        padding: const EdgeInsets.symmetric(vertical: 10),
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: ElevatedButton.icon(
                                      onPressed: () {
                                        Navigator.pushNamed(context, '/transaksi-form', arguments: {
                                          'is_koreksi': true,
                                          'koreksi_kode': trx['kode_transaksi'],
                                        }).then((_) {
                                          _loadData(['pending', 'sesuai', 'koreksi'][_tabController.index]);
                                        });
                                      },
                                      icon: const Icon(Icons.edit_note_rounded, size: 18),
                                      label: Text('Koreksi', style: GoogleFonts.dmSans(fontWeight: FontWeight.w600, fontSize: 13)),
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: AppTheme.danger,
                                        foregroundColor: Colors.white,
                                        padding: const EdgeInsets.symmetric(vertical: 10),
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ],
                        ),
                      );
                    },
                  ),
                );
              }
            ),
          ),
        ],
      ),
    );
  }

  @override
  void dispose() {
    _searchController.dispose();
    _tabController.dispose();
    super.dispose();
  }
}
