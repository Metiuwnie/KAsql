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
import '../widgets/common_widgets.dart';
import '../widgets/offline_banner.dart';

/// Transaction list screen — grouped by date, with search & filter.
class TransaksiScreen extends StatefulWidget {
  const TransaksiScreen({super.key});

  @override
  State<TransaksiScreen> createState() => _TransaksiScreenState();
}

class _TransaksiScreenState extends State<TransaksiScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _transactions = [];
  String _searchQuery = '';
  int _selectedYear = DateTime.now().year;
  int? _selectedMonth;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    
    final cacheKey = 'transaksi_${_selectedYear}_${_selectedMonth ?? 'all'}';
    final cachedData = await DatabaseService().getCachedGenericData(cacheKey);
    final pendingOffline = await DatabaseService().getPendingTransactions();
    
    if (!mounted) return;
    
    if (cachedData != null) {
      setState(() {
        _transactions = List<Map<String, dynamic>>.from(cachedData);
        // Tambahkan pending offline ke tampilan
        if (pendingOffline.isNotEmpty) {
           final pendingFormatted = pendingOffline.map((p) => {
             ...p,
             'kode_transaksi': 'PENDING (Belum tersinkron)',
             'status_verifikasi': 'pending',
           }).toList();
           _transactions.insertAll(0, pendingFormatted);
        }
        _isLoading = false;
      });
    }

    final isOnline = context.read<ConnectivityProvider>().isOnline;
    if (isOnline) {
      try {
        final params = <String, dynamic>{'tahun': _selectedYear.toString()};
        if (_selectedMonth != null) params['bulan'] = _selectedMonth.toString();

        final response = await ApiService().get(ApiConfig.transaksi, queryParams: params);
        if (response.statusCode == 200 && response.data['status'] == 'success') {
          setState(() {
            _transactions = List<Map<String, dynamic>>.from(response.data['data']);
            _isLoading = false;
          });
          await DatabaseService().cacheGenericData(cacheKey, response.data['data']);
        }
      } catch (e) {
        if (cachedData == null) {
          setState(() => _isLoading = false);
        }
      }
    } else if (cachedData == null) {
       setState(() {
         if (pendingOffline.isNotEmpty) {
             _transactions = pendingOffline.map((p) => {
             ...p,
             'kode_transaksi': 'PENDING (Belum tersinkron)',
             'status_verifikasi': 'pending',
           }).toList();
         }
         _isLoading = false;
       });
    }
  }

  List<Map<String, dynamic>> get _filteredTransactions {
    if (_searchQuery.isEmpty) return _transactions;
    return _transactions.where((t) {
      final desc = (t['deskripsi'] ?? '').toString().toLowerCase();
      final kode = (t['kode_transaksi'] ?? '').toString().toLowerCase();
      return desc.contains(_searchQuery.toLowerCase()) || kode.contains(_searchQuery.toLowerCase());
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final filtered = _filteredTransactions;

    return Scaffold(
      drawer: const AppDrawer(currentRoute: '/transaksi'),
      appBar: AppBar(title: const Text('Transaksi')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => Navigator.pushNamed(context, '/transaksi-form').then((_) => _loadData()),
        icon: const Icon(Icons.add_rounded),
        label: Text('Tambah', style: GoogleFonts.dmSans(fontWeight: FontWeight.w600)),
      ),
      body: Column(
        children: [
          OfflineBanner(isOffline: !context.watch<ConnectivityProvider>().isOnline),
          // Search bar
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            child: TextField(
              onChanged: (v) => setState(() => _searchQuery = v),
              decoration: InputDecoration(
                hintText: 'Cari transaksi...',
                prefixIcon: const Icon(Icons.search_rounded, size: 20),
                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              ),
              style: GoogleFonts.dmSans(fontSize: 14),
            ),
          ),
          // List
          Expanded(
            child: _isLoading
                ? ListView(children: List.generate(5, (_) => const Padding(padding: EdgeInsets.symmetric(horizontal: 16, vertical: 4), child: ShimmerCard())))
                : filtered.isEmpty
                    ? const EmptyState(icon: Icons.receipt_long_outlined, title: 'Tidak ada transaksi')
                    : RefreshIndicator(
                        onRefresh: _loadData,
                        child: ListView.builder(
                          padding: const EdgeInsets.fromLTRB(16, 0, 16, 80),
                          itemCount: filtered.length,
                          itemBuilder: (context, index) {
                            final trx = filtered[index];
                            final details = List<Map<String, dynamic>>.from(trx['details'] ?? []);
                            final totalDebit = details.fold<double>(0, (sum, d) => sum + (d['debit'] ?? 0));

                            return Container(
                              margin: const EdgeInsets.only(bottom: 10),
                              decoration: BoxDecoration(
                                color: isDark ? AppTheme.surfaceCardDark : Colors.white,
                                borderRadius: BorderRadius.circular(14),
                                border: Border.all(color: isDark ? AppTheme.borderDark : AppTheme.borderLight),
                              ),
                              child: InkWell(
                                borderRadius: BorderRadius.circular(14),
                                onTap: () {
                                  _showTransactionDetail(context, trx);
                                },
                                child: Padding(
                                  padding: const EdgeInsets.all(14),
                                  child: Row(
                                    children: [
                                      // Icon
                                      Container(
                                        width: 42,
                                        height: 42,
                                        decoration: BoxDecoration(
                                          color: AppTheme.teal.withValues(alpha: 0.1),
                                          borderRadius: BorderRadius.circular(10),
                                        ),
                                        child: const Icon(Icons.receipt_long_rounded, color: AppTheme.teal, size: 20),
                                      ),
                                      const SizedBox(width: 12),
                                      // Content
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Row(
                                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                              children: [
                                                Text(
                                                  trx['kode_transaksi'] ?? '',
                                                  style: GoogleFonts.dmSans(fontSize: 13, fontWeight: FontWeight.w700, color: AppTheme.teal),
                                                ),
                                                StatusBadge(
                                                  label: getVerificationLabel(trx['status_verifikasi'] ?? 'sesuai'),
                                                  color: getVerificationColor(trx['status_verifikasi'] ?? 'sesuai'),
                                                ),
                                              ],
                                            ),
                                            const SizedBox(height: 4),
                                            Text(
                                              trx['deskripsi'] ?? '-',
                                              style: GoogleFonts.dmSans(fontSize: 13, fontWeight: FontWeight.w500),
                                              maxLines: 1,
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                            const SizedBox(height: 4),
                                            Row(
                                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                              children: [
                                                Text(
                                                  formatTanggal(trx['tanggal']),
                                                  style: GoogleFonts.dmSans(fontSize: 11, color: AppTheme.textMutedDark),
                                                ),
                                                Text(
                                                  formatRupiah(totalDebit),
                                                  style: GoogleFonts.dmSans(fontSize: 13, fontWeight: FontWeight.w700),
                                                ),
                                              ],
                                            ),
                                          ],
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
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

  void _showTransactionDetail(BuildContext context, Map<String, dynamic> trx) {
    final details = List<Map<String, dynamic>>.from(trx['details'] ?? []);
    final isDark = Theme.of(context).brightness == Brightness.dark;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: isDark ? AppTheme.surfaceCardDark : Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (ctx) => DraggableScrollableSheet(
        initialChildSize: 0.5,
        maxChildSize: 0.85,
        minChildSize: 0.3,
        expand: false,
        builder: (_, scrollController) => Padding(
          padding: const EdgeInsets.all(20),
          child: ListView(
            controller: scrollController,
            children: [
              Center(
                child: Container(width: 40, height: 4, decoration: BoxDecoration(color: Colors.grey.shade400, borderRadius: BorderRadius.circular(2))),
              ),
              const SizedBox(height: 16),
              Text(trx['kode_transaksi'] ?? '', style: GoogleFonts.dmSans(fontSize: 18, fontWeight: FontWeight.w700)),
              const SizedBox(height: 4),
              Text(trx['deskripsi'] ?? '', style: GoogleFonts.dmSans(fontSize: 14, color: AppTheme.textMutedDark)),
              Text(formatTanggal(trx['tanggal']), style: GoogleFonts.dmSans(fontSize: 12, color: AppTheme.textMutedDark)),
              const SizedBox(height: 16),
              const Divider(),
              ...details.map((d) {
                final debit = double.tryParse(d['debit']?.toString() ?? '0') ?? 0;
                final kredit = double.tryParse(d['kredit']?.toString() ?? '0') ?? 0;
                return Padding(
                padding: const EdgeInsets.symmetric(vertical: 6),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: getDkColor(debit > 0 ? 'debit' : 'kredit').withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Text(
                        debit > 0 ? 'D' : 'K',
                        style: GoogleFonts.dmSans(fontSize: 10, fontWeight: FontWeight.w700, color: getDkColor(debit > 0 ? 'debit' : 'kredit')),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('${d['kode_akun']} - ${d['nama_akun'] ?? ''}', style: GoogleFonts.dmSans(fontSize: 13, fontWeight: FontWeight.w500)),
                        ],
                      ),
                    ),
                    Text(
                      formatRupiah(debit > 0 ? debit : kredit),
                      style: GoogleFonts.dmSans(fontSize: 13, fontWeight: FontWeight.w600),
                    ),
                  ],
                ),
              );
              }),
            ],
          ),
        ),
      ),
    );
  }
}
