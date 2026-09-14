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

class NeracaTab extends StatefulWidget {
  final int tahun;
  final int? bulan;
  const NeracaTab({super.key, required this.tahun, this.bulan});

  @override
  State<NeracaTab> createState() => _NeracaTabState();
}

class _NeracaTabState extends State<NeracaTab> with AutomaticKeepAliveClientMixin {
  bool _isLoading = false;
  Map<String, dynamic> _data = {};

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() { super.initState(); _loadData(); }

  @override
  void didUpdateWidget(covariant NeracaTab oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.tahun != oldWidget.tahun || widget.bulan != oldWidget.bulan) _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);

    final cacheKey = 'neraca_${widget.tahun}_${widget.bulan ?? 'all'}';
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
        final response = await ApiService().get(ApiConfig.neraca, queryParams: params);
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
    if (_data.isEmpty) return const EmptyState(icon: Icons.grid_view_rounded, title: 'Tidak ada data');

    final aktiva = List<Map<String, dynamic>>.from(_data['aktiva'] ?? []);
    final pasiva = List<Map<String, dynamic>>.from(_data['pasiva'] ?? []);
    final labaBerjalan = (_data['laba_berjalan'] ?? 0).toDouble();
    final totalAktiva = (_data['total_aktiva'] ?? 0).toDouble();
    final totalPasiva = (_data['total_pasiva'] ?? 0).toDouble();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        OfflineBanner(isOffline: !context.watch<ConnectivityProvider>().isOnline),
        Expanded(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                // Aktiva
          _buildSection('AKTIVA (ASET)', aktiva, totalAktiva, AppTheme.info, isDark),
          const SizedBox(height: 16),
          // Pasiva
          _buildSection('PASIVA (KEWAJIBAN + EKUITAS)', pasiva, totalPasiva, AppTheme.gold, isDark, labaBerjalan: labaBerjalan),
          const SizedBox(height: 16),
          // Balance check
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: (totalAktiva - totalPasiva).abs() < 1 ? AppTheme.success.withValues(alpha: 0.1) : AppTheme.danger.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon((totalAktiva - totalPasiva).abs() < 1 ? Icons.check_circle : Icons.warning_amber_rounded, color: (totalAktiva - totalPasiva).abs() < 1 ? AppTheme.success : AppTheme.danger, size: 20),
                const SizedBox(width: 8),
                Flexible(
                  child: Text(
                    (totalAktiva - totalPasiva).abs() < 1 ? 'NERACA SEIMBANG' : 'NERACA TIDAK SEIMBANG (Selisih: ${formatRupiah((totalAktiva - totalPasiva).abs())})',
                    style: GoogleFonts.dmSans(fontSize: 13, fontWeight: FontWeight.w700, color: (totalAktiva - totalPasiva).abs() < 1 ? AppTheme.success : AppTheme.danger),
                    textAlign: TextAlign.center,
                  ),
                ),
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

  Widget _buildSection(String title, List<Map<String, dynamic>> items, double total, Color color, bool isDark, {double labaBerjalan = 0}) {
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
            child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
              Expanded(child: Text(title, style: GoogleFonts.dmSans(fontSize: 11, fontWeight: FontWeight.w700, letterSpacing: 0.5, color: color))),
              const SizedBox(width: 8),
              Text(formatRupiah(total), style: GoogleFonts.dmSans(fontSize: 14, fontWeight: FontWeight.w700, color: color)),
            ]),
          ),
          ...items.map((item) => Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
            child: Row(children: [
              Text(item['kode_akun'] ?? '', style: GoogleFonts.dmSans(fontSize: 11, color: AppTheme.textMutedDark)),
              const SizedBox(width: 8),
              Expanded(child: Text(item['nama_akun'] ?? '', style: GoogleFonts.dmSans(fontSize: 12))),
              Text(formatRupiah((item['saldo'] ?? 0).toDouble()), style: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.w600)),
            ]),
          )),
          if (labaBerjalan != 0) Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
            child: Row(children: [
              const SizedBox(width: 40),
              Expanded(child: Text('Laba Berjalan', style: GoogleFonts.dmSans(fontSize: 12, fontStyle: FontStyle.italic, color: AppTheme.teal))),
              Text(formatRupiah(labaBerjalan), style: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.w600, color: AppTheme.teal)),
            ]),
          ),
          const SizedBox(height: 8),
        ],
      ),
    );
  }
}
