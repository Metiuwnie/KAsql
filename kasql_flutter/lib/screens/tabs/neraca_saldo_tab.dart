import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../services/api_service.dart';
import '../../services/database_service.dart';
import '../../services/connectivity_provider.dart';
import '../../config/api_config.dart';
import '../../config/app_theme.dart';
import '../../utils/helpers.dart';
import '../../widgets/common_widgets.dart';
import '../../widgets/offline_banner.dart';

class NeracaSaldoTab extends StatefulWidget {
  final int tahun;
  final int? bulan;
  const NeracaSaldoTab({super.key, required this.tahun, this.bulan});

  @override
  State<NeracaSaldoTab> createState() => _NeracaSaldoTabState();
}

class _NeracaSaldoTabState extends State<NeracaSaldoTab> with AutomaticKeepAliveClientMixin {
  bool _isLoading = false;
  List<Map<String, dynamic>> _data = [];

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() { super.initState(); _loadData(); }

  @override
  void didUpdateWidget(covariant NeracaSaldoTab oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.tahun != oldWidget.tahun || widget.bulan != oldWidget.bulan) _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);

    final cacheKey = 'neraca_saldo_${widget.tahun}_${widget.bulan ?? 'all'}';
    final cachedData = await DatabaseService().getCachedGenericData(cacheKey);

    if (cachedData != null && mounted) {
      setState(() { _data = List<Map<String, dynamic>>.from(cachedData); _isLoading = false; });
    }

    if (!mounted) return;
    final isOnline = context.read<ConnectivityProvider>().isOnline;
    if (isOnline) {
      try {
        final params = <String, dynamic>{'tahun': widget.tahun.toString()};
        if (widget.bulan != null) params['bulan'] = widget.bulan.toString();
        final response = await ApiService().get(ApiConfig.neracaSaldo, queryParams: params);
        if (response.statusCode == 200 && response.data['status'] == 'success') {
          setState(() { _data = List<Map<String, dynamic>>.from(response.data['data']); _isLoading = false; });
          await DatabaseService().cacheGenericData(cacheKey, response.data['data']);
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
    super.build(context);
    final isDark = Theme.of(context).brightness == Brightness.dark;

    if (_isLoading) return ListView(children: List.generate(5, (_) => const Padding(padding: EdgeInsets.all(16), child: ShimmerCard())));
    if (_data.isEmpty) return const EmptyState(icon: Icons.attach_money_rounded, title: 'Tidak ada data');

    double totalDebet = 0, totalKredit = 0;
    for (final row in _data) {
      totalDebet += (row['saldo_akhir_debet'] ?? 0).toDouble();
      totalKredit += (row['saldo_akhir_kredit'] ?? 0).toDouble();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        OfflineBanner(isOffline: !context.watch<ConnectivityProvider>().isOnline),
        Expanded(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                // Table header
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            decoration: BoxDecoration(color: isDark ? AppTheme.navy700 : Colors.grey.shade100, borderRadius: const BorderRadius.vertical(top: Radius.circular(12))),
            child: Row(children: [
              SizedBox(width: 55, child: Text('Kode', style: GoogleFonts.dmSans(fontSize: 10, fontWeight: FontWeight.w700))),
              Expanded(child: Text('Nama Akun', style: GoogleFonts.dmSans(fontSize: 10, fontWeight: FontWeight.w700))),
              SizedBox(width: 80, child: Text('Debet', style: GoogleFonts.dmSans(fontSize: 10, fontWeight: FontWeight.w700), textAlign: TextAlign.right)),
              SizedBox(width: 80, child: Text('Kredit', style: GoogleFonts.dmSans(fontSize: 10, fontWeight: FontWeight.w700), textAlign: TextAlign.right)),
            ]),
          ),
          // Table rows
          ..._data.map((row) {
            final debet = double.tryParse(row['saldo_akhir_debet']?.toString() ?? '0') ?? 0;
            final kredit = double.tryParse(row['saldo_akhir_kredit']?.toString() ?? '0') ?? 0;
            return Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(border: Border(bottom: BorderSide(color: isDark ? AppTheme.borderDark : AppTheme.borderLight, width: 0.5))),
            child: Row(children: [
              SizedBox(width: 55, child: Text(row['kode_akun'] ?? '', style: GoogleFonts.dmSans(fontSize: 11, color: AppTheme.teal, fontWeight: FontWeight.w600))),
              Expanded(child: Text(row['nama_akun'] ?? '', style: GoogleFonts.dmSans(fontSize: 11), overflow: TextOverflow.ellipsis)),
              SizedBox(width: 80, child: Text(debet > 0 ? formatRupiah(debet, withSymbol: false) : '', style: GoogleFonts.dmSans(fontSize: 11), textAlign: TextAlign.right)),
              SizedBox(width: 80, child: Text(kredit > 0 ? formatRupiah(kredit, withSymbol: false) : '', style: GoogleFonts.dmSans(fontSize: 11), textAlign: TextAlign.right)),
            ]),
          );
          }),
          // Total
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            decoration: BoxDecoration(color: isDark ? AppTheme.teal.withValues(alpha: 0.1) : AppTheme.tealDark.withValues(alpha: 0.05), borderRadius: const BorderRadius.vertical(bottom: Radius.circular(12))),
            child: Row(children: [
              const SizedBox(width: 55),
              Expanded(child: Text('TOTAL', style: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.w800))),
              SizedBox(width: 80, child: Text(formatRupiah(totalDebet, withSymbol: false), style: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.w800), textAlign: TextAlign.right)),
              SizedBox(width: 80, child: Text(formatRupiah(totalKredit, withSymbol: false), style: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.w800), textAlign: TextAlign.right)),
            ]),
          ),
          const SizedBox(height: 8),
          if ((totalDebet - totalKredit).abs() < 0.01)
            Container(padding: const EdgeInsets.all(8), decoration: BoxDecoration(color: AppTheme.success.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(8)),
              child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [const Icon(Icons.check_circle, color: AppTheme.success, size: 16), const SizedBox(width: 6), Text('SEIMBANG', style: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.w700, color: AppTheme.success))])),
              ],
            ),
          ),
        ),
      ],
    );
  }
}
