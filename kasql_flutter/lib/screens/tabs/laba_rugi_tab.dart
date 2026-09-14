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

class LabaRugiTab extends StatefulWidget {
  final int tahun;
  final int? bulan;
  const LabaRugiTab({super.key, required this.tahun, this.bulan});

  @override
  State<LabaRugiTab> createState() => _LabaRugiTabState();
}

class _LabaRugiTabState extends State<LabaRugiTab> with AutomaticKeepAliveClientMixin {
  bool _isLoading = false;
  Map<String, dynamic> _data = {};

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() { super.initState(); _loadData(); }

  @override
  void didUpdateWidget(covariant LabaRugiTab oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.tahun != oldWidget.tahun || widget.bulan != oldWidget.bulan) _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);

    final cacheKey = 'laba_rugi_${widget.tahun}_${widget.bulan ?? 'all'}';
    final cachedData = await DatabaseService().getCachedGenericData(cacheKey);

    if (cachedData != null && mounted) {
      setState(() { _data = cachedData; _isLoading = false; });
    }

    if (!mounted) return;
    final isOnline = context.read<ConnectivityProvider>().isOnline;
    if (isOnline) {
      try {
        final params = <String, dynamic>{'tahun': widget.tahun.toString()};
        if (widget.bulan != null) params['bulan'] = widget.bulan.toString();
        final response = await ApiService().get(ApiConfig.labaRugi, queryParams: params);
        if (response.statusCode == 200 && response.data['status'] == 'success') {
          setState(() { _data = response.data['data']; _isLoading = false; });
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

    if (_isLoading) return ListView(children: List.generate(3, (_) => const Padding(padding: EdgeInsets.all(16), child: ShimmerCard())));
    if (_data.isEmpty) return const EmptyState(icon: Icons.trending_up_rounded, title: 'Tidak ada data');

    final pendapatan = List<Map<String, dynamic>>.from(_data['pendapatan'] ?? []);
    final beban = List<Map<String, dynamic>>.from(_data['beban'] ?? []);
    final totalPendapatan = (_data['total_pendapatan'] ?? 0).toDouble();
    final totalBeban = (_data['total_beban'] ?? 0).toDouble();
    final labaBersih = (_data['laba_bersih'] ?? 0).toDouble();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        OfflineBanner(isOffline: !context.watch<ConnectivityProvider>().isOnline),
        Expanded(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Pendapatan section
          _buildSection('PENDAPATAN', pendapatan, totalPendapatan, AppTheme.success, isDark),
          const SizedBox(height: 16),
          // Beban section
          _buildSection('BEBAN', beban, totalBeban, AppTheme.danger, isDark),
          const SizedBox(height: 16),
          // Laba/Rugi Bersih
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: LinearGradient(colors: labaBersih >= 0 ? [AppTheme.teal.withValues(alpha: 0.15), AppTheme.teal.withValues(alpha: 0.05)] : [AppTheme.danger.withValues(alpha: 0.15), AppTheme.danger.withValues(alpha: 0.05)]),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: labaBersih >= 0 ? AppTheme.teal.withValues(alpha: 0.3) : AppTheme.danger.withValues(alpha: 0.3)),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(labaBersih >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH', style: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.w700, letterSpacing: 0.5, color: labaBersih >= 0 ? AppTheme.teal : AppTheme.danger)),
                  const SizedBox(height: 4),
                  Text(formatRupiah(labaBersih), style: GoogleFonts.dmSans(fontSize: 22, fontWeight: FontWeight.w800, color: labaBersih >= 0 ? AppTheme.teal : AppTheme.danger)),
                ]),
                Icon(labaBersih >= 0 ? Icons.trending_up_rounded : Icons.trending_down_rounded, size: 40, color: (labaBersih >= 0 ? AppTheme.teal : AppTheme.danger).withValues(alpha: 0.5)),
              ],
            ),
          ),
        ],
      ),
    ),
  ),
],
    );
  }

  Widget _buildSection(String title, List<Map<String, dynamic>> items, double total, Color color, bool isDark) {
    return Container(
      decoration: BoxDecoration(
        color: isDark ? AppTheme.surfaceCardDark : Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: isDark ? AppTheme.borderDark : AppTheme.borderLight),
      ),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(color: color.withValues(alpha: 0.08), borderRadius: const BorderRadius.vertical(top: Radius.circular(13))),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(title, style: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.w700, letterSpacing: 0.5, color: color)),
                Text(formatRupiah(total), style: GoogleFonts.dmSans(fontSize: 14, fontWeight: FontWeight.w700, color: color)),
              ],
            ),
          ),
          ...items.map((item) => Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: Row(children: [
              Text(item['kode_akun'] ?? '', style: GoogleFonts.dmSans(fontSize: 11, color: AppTheme.textMutedDark)),
              const SizedBox(width: 8),
              Expanded(child: Text(item['nama_akun'] ?? '', style: GoogleFonts.dmSans(fontSize: 12))),
              Text(formatRupiah(item['nilai'] ?? 0), style: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.w600)),
            ]),
          )),
          if (items.isEmpty) Padding(padding: const EdgeInsets.all(16), child: Text('Tidak ada data', style: GoogleFonts.dmSans(fontSize: 12, color: AppTheme.textMutedDark))),
        ],
      ),
    );
  }
}
