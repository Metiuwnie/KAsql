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

/// Jurnal Umum — General journal display with period filter.
class JurnalScreen extends StatefulWidget {
  const JurnalScreen({super.key});

  @override
  State<JurnalScreen> createState() => _JurnalScreenState();
}

class _JurnalScreenState extends State<JurnalScreen> {
  bool _isLoading = true;
  List<dynamic> _jurnalList = [];
  int _selectedYear = DateTime.now().year;
  int? _selectedMonth;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    
    final cacheKey = 'jurnal_${_selectedYear}_${_selectedMonth ?? 'all'}';
    final cachedData = await DatabaseService().getCachedGenericData(cacheKey);

    if (!mounted) return;

    if (cachedData != null) {
      setState(() { 
        _jurnalList = cachedData; 
        _isLoading = false; 
      });
    }

    final isOnline = context.read<ConnectivityProvider>().isOnline;
    if (isOnline) {
      try {
        final params = <String, dynamic>{'tahun': _selectedYear.toString()};
        if (_selectedMonth != null) params['bulan'] = _selectedMonth.toString();

        final response = await ApiService().get(ApiConfig.jurnalUmum, queryParams: params);
        if (response.statusCode == 200 && response.data['status'] == 'success') {
          setState(() { _jurnalList = response.data['data']; _isLoading = false; });
          await DatabaseService().cacheGenericData(cacheKey, _jurnalList);
        }
      } catch (e) {
        if (cachedData == null) {
          setState(() => _isLoading = false);
        }
      }
    } else if (cachedData == null) {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    // Kelompokkan data jurnal berdasarkan kode_transaksi
    final Map<String, Map<String, dynamic>> groupedMap = {};
    for (final item in _jurnalList) {
      final kode = item['kode_transaksi']?.toString() ?? 'TRX-${item.hashCode}';
      if (!groupedMap.containsKey(kode)) {
        groupedMap[kode] = {
          'kode_transaksi': item['kode_transaksi']?.toString() ?? '-',
          'tanggal': item['tanggal'],
          'deskripsi': item['deskripsi'],
          'details': <dynamic>[],
        };
      }
      groupedMap[kode]!['details'].add(item);
    }
    final groupedList = groupedMap.values.toList();

    return Scaffold(
      drawer: const AppDrawer(currentRoute: '/jurnal'),
      appBar: AppBar(title: const Text('Jurnal Umum')),
      body: Column(
        children: [
          OfflineBanner(isOffline: !context.watch<ConnectivityProvider>().isOnline),
          // Period filter
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Expanded(
                  child: DropdownButtonFormField<int>(
                    value: _selectedYear,
                    decoration: const InputDecoration(labelText: 'Tahun', contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10)),
                    items: List.generate(5, (i) => DateTime.now().year - i).map((y) => DropdownMenuItem(value: y, child: Text('$y'))).toList(),
                    onChanged: (v) { _selectedYear = v ?? _selectedYear; _loadData(); },
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: DropdownButtonFormField<int?>(
                    value: _selectedMonth,
                    decoration: const InputDecoration(labelText: 'Bulan', contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10)),
                    items: [
                      const DropdownMenuItem(value: null, child: Text('Semua')),
                      ...List.generate(12, (i) => DropdownMenuItem(value: i + 1, child: Text(_monthName(i + 1)))),
                    ],
                    onChanged: (v) { _selectedMonth = v; _loadData(); },
                  ),
                ),
              ],
            ),
          ),
          // Journal list
          Expanded(
            child: _isLoading
                ? ListView(children: List.generate(5, (_) => const Padding(padding: EdgeInsets.symmetric(horizontal: 16, vertical: 4), child: ShimmerCard())))
                : groupedList.isEmpty
                    ? const EmptyState(icon: Icons.description_outlined, title: 'Tidak ada jurnal')
                    : RefreshIndicator(
                        onRefresh: _loadData,
                        child: ListView.builder(
                          padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                          itemCount: groupedList.length,
                          itemBuilder: (context, index) {
                            final group = groupedList[index];
                            final details = group['details'] as List<dynamic>;
                            
                            return Container(
                              margin: const EdgeInsets.only(bottom: 12),
                              padding: const EdgeInsets.all(16),
                              decoration: BoxDecoration(
                                color: isDark ? AppTheme.surfaceCardDark : Colors.white,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: isDark ? AppTheme.borderDark : AppTheme.borderLight),
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.black.withValues(alpha: 0.02),
                                    blurRadius: 8,
                                    offset: const Offset(0, 4),
                                  ),
                                ],
                              ),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                        decoration: BoxDecoration(color: AppTheme.teal.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(6)),
                                        child: Text(group['kode_transaksi']?.toString() ?? '', style: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.teal)),
                                      ),
                                      Text(formatTanggal(group['tanggal']?.toString(), format: 'dd MMM yyyy'), style: GoogleFonts.dmSans(fontSize: 12, color: AppTheme.textMutedDark)),
                                    ],
                                  ),
                                  const SizedBox(height: 10),
                                  Text(group['deskripsi']?.toString() ?? '-', style: GoogleFonts.dmSans(fontSize: 13, fontWeight: FontWeight.w600, color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight)),
                                  const Divider(height: 24),
                                  ...details.map((item) {
                                    final debit = double.tryParse(item['Debit']?.toString() ?? '0') ?? 0;
                                    final kredit = double.tryParse(item['Kredit']?.toString() ?? '0') ?? 0;
                                    final isKredit = kredit > 0;
                                    
                                    return Padding(
                                      padding: const EdgeInsets.only(bottom: 8.0),
                                      child: Row(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Expanded(
                                            child: Padding(
                                              padding: EdgeInsets.only(left: isKredit ? 16.0 : 0.0),
                                              child: Column(
                                                crossAxisAlignment: CrossAxisAlignment.start,
                                                children: [
                                                  Text('${item['kode_akun']} - ${item['akun'] ?? ''}', style: GoogleFonts.dmSans(fontSize: 13, fontWeight: FontWeight.w500)),
                                                ],
                                              ),
                                            ),
                                          ),
                                          const SizedBox(width: 12),
                                          Column(
                                            crossAxisAlignment: CrossAxisAlignment.end,
                                            children: [
                                              if (debit > 0)
                                                Row(
                                                  children: [
                                                    Text('D: ', style: GoogleFonts.dmSans(fontSize: 12, color: AppTheme.success, fontWeight: FontWeight.bold)),
                                                    Text(formatRupiah(debit, withSymbol: false), style: GoogleFonts.dmSans(fontSize: 13, fontWeight: FontWeight.bold)),
                                                  ],
                                                ),
                                              if (kredit > 0)
                                                Row(
                                                  children: [
                                                    Text('K: ', style: GoogleFonts.dmSans(fontSize: 12, color: AppTheme.danger, fontWeight: FontWeight.bold)),
                                                    Text(formatRupiah(kredit, withSymbol: false), style: GoogleFonts.dmSans(fontSize: 13, fontWeight: FontWeight.bold)),
                                                  ],
                                                ),
                                            ],
                                          ),
                                        ],
                                      ),
                                    );
                                  }),
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

  String _monthName(int month) {
    const names = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return names[month];
  }
}
