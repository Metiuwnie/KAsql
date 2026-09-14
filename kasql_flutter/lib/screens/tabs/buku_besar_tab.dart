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

class BukuBesarTab extends StatefulWidget {
  final int tahun;
  final int? bulan;
  const BukuBesarTab({super.key, required this.tahun, this.bulan});

  @override
  State<BukuBesarTab> createState() => _BukuBesarTabState();
}

class _BukuBesarTabState extends State<BukuBesarTab> with AutomaticKeepAliveClientMixin {
  bool _isLoading = false;
  List<Map<String, dynamic>> _data = [];

  @override
  bool get wantKeepAlive => true;

  @override
  void didUpdateWidget(covariant BukuBesarTab oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.tahun != oldWidget.tahun || widget.bulan != oldWidget.bulan) {
      _loadData();
    }
  }

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);

    final cacheKey = 'buku_besar_${widget.tahun}_${widget.bulan ?? 'all'}';
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

        final response = await ApiService().get(ApiConfig.bukuBesar, queryParams: params);
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

    if (_isLoading) return ListView(children: List.generate(3, (_) => const Padding(padding: EdgeInsets.all(16), child: ShimmerCard())));
    if (_data.isEmpty) return const EmptyState(icon: Icons.menu_book_rounded, title: 'Tidak ada data buku besar');

    return Column(
      children: [
        OfflineBanner(isOffline: !context.watch<ConnectivityProvider>().isOnline),
        Expanded(
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: _data.length,
      itemBuilder: (context, index) {
        final akun = _data[index];
        final transactions = List<Map<String, dynamic>>.from(akun['transactions'] ?? []);

        return Container(
          margin: const EdgeInsets.only(bottom: 14),
          decoration: BoxDecoration(
            color: isDark ? AppTheme.surfaceCardDark : Colors.white,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: isDark ? AppTheme.borderDark : AppTheme.borderLight),
          ),
          child: ExpansionTile(
            tilePadding: const EdgeInsets.symmetric(horizontal: 16),
            title: Text('${akun['kode_akun']} - ${akun['nama_akun']}', style: GoogleFonts.dmSans(fontSize: 14, fontWeight: FontWeight.w600)),
            subtitle: Row(
              children: [
                Text('Saldo Awal: ${formatRupiah(akun['saldo_awal'])}', style: GoogleFonts.dmSans(fontSize: 11, color: AppTheme.textMutedDark)),
                const Spacer(),
                Text('Saldo Akhir: ${formatRupiah(akun['saldo_akhir'])}', style: GoogleFonts.dmSans(fontSize: 11, fontWeight: FontWeight.w600, color: AppTheme.teal)),
              ],
            ),
            children: [
              if (transactions.isEmpty)
                Padding(padding: const EdgeInsets.all(16), child: Text('Tidak ada transaksi', style: GoogleFonts.dmSans(fontSize: 12, color: AppTheme.textMutedDark)))
              else
                ...transactions.map((trx) => Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                  child: Row(
                    children: [
                      SizedBox(width: 65, child: Text(formatTanggal(trx['tanggal'], format: 'dd/MM'), style: GoogleFonts.dmSans(fontSize: 11, color: AppTheme.textMutedDark))),
                      Expanded(child: Text(trx['deskripsi'] ?? trx['kode_transaksi'] ?? '', style: GoogleFonts.dmSans(fontSize: 11), overflow: TextOverflow.ellipsis)),
                      Container(padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1), decoration: BoxDecoration(color: getDkColor(trx['dk'] ?? 'debit').withValues(alpha: 0.1), borderRadius: BorderRadius.circular(3)),
                        child: Text(trx['dk'] == 'Debit' ? 'D' : 'K', style: GoogleFonts.dmSans(fontSize: 9, fontWeight: FontWeight.w700, color: getDkColor(trx['dk'] ?? 'debit')))),
                      const SizedBox(width: 6),
                      SizedBox(width: 70, child: Text(formatRupiah(trx['nilai'], withSymbol: false), style: GoogleFonts.dmSans(fontSize: 11), textAlign: TextAlign.right)),
                      SizedBox(width: 80, child: Text(formatRupiah(trx['saldo'], withSymbol: false), style: GoogleFonts.dmSans(fontSize: 11, fontWeight: FontWeight.w600), textAlign: TextAlign.right)),
                    ],
                  ),
                )),
              const SizedBox(height: 10),
            ],
          ),
        );
      },
    ),
        ),
      ],
    );
  }
}
