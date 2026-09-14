import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:fl_chart/fl_chart.dart';
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

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> with SingleTickerProviderStateMixin {
  bool _isLoading = true;
  Map<String, dynamic> _data = {};
  String? _error;
  int? _selectedYear;

  late AnimationController _animController;

  @override
  void initState() {
    super.initState();
    _animController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 800),
    );
    _loadData();
  }

  @override
  void dispose() {
    _animController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    setState(() { _isLoading = true; _error = null; });

    final targetYear = _selectedYear ?? _data['current_year'] ?? DateTime.now().year;
    final cacheKey = 'dashboard_$targetYear';
    final cachedData = await DatabaseService().getCachedGenericData(cacheKey);

    if (cachedData != null) {
      setState(() {
        _data = cachedData;
        _isLoading = false;
      });
      _animController.forward(from: 0);
    }

    final isOnline = context.read<ConnectivityProvider>().isOnline;
    if (isOnline) {
      try {
        final queryParams = _selectedYear != null ? {'tahun': _selectedYear.toString()} : null;
        final response = await ApiService().get(ApiConfig.dashboard, queryParams: queryParams);
        if (response.statusCode == 200 && response.data['status'] == 'success') {
          setState(() { _data = response.data['data']; _isLoading = false; });
          await DatabaseService().cacheGenericData(cacheKey, _data);
          if (cachedData == null) {
            _animController.forward(from: 0);
          }
        }
      } catch (e) {
        if (cachedData == null) {
          setState(() { _error = e.toString(); _isLoading = false; });
        }
      }
    } else if (cachedData == null) {
      setState(() { _error = 'Tidak ada koneksi dan tidak ada data tersimpan.'; _isLoading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      drawer: const AppDrawer(currentRoute: '/dashboard'),
      appBar: AppBar(
        title: Text(_data['company_name'] ?? 'Dashboard'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: _loadData,
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _loadData,
        color: AppTheme.teal,
        child: _isLoading
            ? _buildShimmerLoading()
            : _error != null
                ? ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    children: [
                      SizedBox(height: MediaQuery.of(context).size.height * 0.3),
                      Center(child: Text('Gagal memuat data: $_error', style: GoogleFonts.dmSans(fontSize: 16))),
                      const SizedBox(height: 8),
                      Center(child: ElevatedButton(onPressed: _loadData, child: const Text('Coba Lagi'))),
                    ],
                  )
                : SingleChildScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        OfflineBanner(isOffline: !context.watch<ConnectivityProvider>().isOnline),
                        const SizedBox(height: 16),
                        // ─── Header ───
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              'Dashboard',
                              style: GoogleFonts.dmSans(
                                fontSize: 26,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                            _YearDropdown(
                              years: List<int>.from(_data['available_years'] ?? [DateTime.now().year]),
                              currentYear: _selectedYear ?? _data['current_year'] ?? DateTime.now().year,
                              onChanged: (year) {
                                setState(() {
                                  _selectedYear = year;
                                });
                                _loadData();
                              },
                            ),
                          ],
                        ),
                        const SizedBox(height: 20),

                        // ─── Summary Cards ───
                        _buildSummaryCards(isDark),
                        const SizedBox(height: 24),

                        // ─── Revenue vs Expense Chart ───
                        _buildLineChart(isDark),
                        const SizedBox(height: 20),

                        // ─── Asset Composition ───
                        _buildDoughnutChart(isDark),
                        const SizedBox(height: 20),
                      ],
                    ),
                  ),
                ),
    );
  }

  Widget _buildSummaryCards(bool isDark) {
    final summary = _data['summary'] ?? {};

    return GridView.count(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisCount: 2,
      mainAxisSpacing: 12,
      crossAxisSpacing: 12,
      childAspectRatio: 1.45,
      children: [
        SummaryCard(
          title: 'TOTAL ASET',
          value: formatRupiah(summary['total_aset'] ?? 0),
          icon: Icons.account_balance_wallet_rounded,
          accentColor: AppTheme.info,
        ),
        SummaryCard(
          title: 'LIABILITAS',
          value: formatRupiah(summary['total_kewajiban'] ?? 0),
          icon: Icons.credit_card_rounded,
          accentColor: AppTheme.danger,
        ),
        SummaryCard(
          title: 'PENDAPATAN',
          value: formatRupiah(summary['total_pendapatan'] ?? 0),
          icon: Icons.trending_up_rounded,
          accentColor: AppTheme.success,
        ),
        SummaryCard(
          title: 'LABA BERSIH',
          value: formatRupiah(summary['laba_rugi_bersih'] ?? 0),
          icon: Icons.show_chart_rounded,
          accentColor: (summary['laba_rugi_bersih'] ?? 0) >= 0 ? AppTheme.teal : AppTheme.danger,
        ),
        SummaryCard(
          title: 'EKUITAS',
          value: formatRupiah(summary['total_ekuitas'] ?? 0),
          icon: Icons.pie_chart_rounded,
          accentColor: AppTheme.gold,
        ),
      ],
    );
  }

  Widget _buildLineChart(bool isDark) {
    final chart = _data['chart'] ?? {};
    final labels = List<String>.from(chart['labels'] ?? []);
    final pendapatan = List<double>.from(
      (chart['pendapatan'] as List?)?.map((e) => (e as num).toDouble()) ?? [],
    );
    final beban = List<double>.from(
      (chart['beban'] as List?)?.map((e) => (e as num).toDouble()) ?? [],
    );

    if (labels.isEmpty) return const SizedBox.shrink();

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: isDark ? AppTheme.surfaceCardDark : Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: isDark ? AppTheme.borderDark : AppTheme.borderLight),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.show_chart_rounded, color: AppTheme.teal, size: 20),
              const SizedBox(width: 8),
              Text(
                'Arus Kas & Performa Laba',
                style: GoogleFonts.dmSans(fontSize: 15, fontWeight: FontWeight.w700),
              ),
            ],
          ),
          const SizedBox(height: 8),
          // Legend
          Row(
            children: [
              _LegendDot(color: AppTheme.success, label: 'Pendapatan'),
              const SizedBox(width: 16),
              _LegendDot(color: AppTheme.danger, label: 'Beban'),
            ],
          ),
          const SizedBox(height: 20),
          SizedBox(
            height: 220,
            child: LineChart(
              LineChartData(
                gridData: FlGridData(
                  show: true,
                  drawVerticalLine: false,
                  horizontalInterval: _calculateInterval(pendapatan, beban),
                  getDrawingHorizontalLine: (value) => FlLine(
                    color: isDark
                        ? Colors.white.withValues(alpha: 0.05)
                        : Colors.black.withValues(alpha: 0.05),
                    strokeWidth: 1,
                  ),
                ),
                titlesData: FlTitlesData(
                  leftTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      reservedSize: 50,
                      getTitlesWidget: (value, meta) {
                        String text;
                        if (value >= 1000000) {
                          text = '${(value / 1000000).toStringAsFixed(0)}Jt';
                        } else if (value >= 1000) {
                          text = '${(value / 1000).toStringAsFixed(0)}Rb';
                        } else {
                          text = value.toStringAsFixed(0);
                        }
                        return SideTitleWidget(
                          meta: meta,
                          child: Text(text, style: GoogleFonts.dmSans(fontSize: 9, color: AppTheme.textMutedDark)),
                        );
                      },
                    ),
                  ),
                  bottomTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      getTitlesWidget: (value, meta) {
                        final idx = value.toInt();
                        if (idx >= 0 && idx < labels.length) {
                          return SideTitleWidget(
                            meta: meta,
                            child: Text(labels[idx], style: GoogleFonts.dmSans(fontSize: 9, color: AppTheme.textMutedDark)),
                          );
                        }
                        return const SizedBox.shrink();
                      },
                    ),
                  ),
                  topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                  rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                ),
                borderData: FlBorderData(show: false),
                lineBarsData: [
                  _buildLine(pendapatan, AppTheme.success),
                  _buildLine(beban, AppTheme.danger),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  LineChartBarData _buildLine(List<double> data, Color color) {
    return LineChartBarData(
      spots: data.asMap().entries.map((e) => FlSpot(e.key.toDouble(), e.value)).toList(),
      isCurved: true,
      color: color,
      barWidth: 3,
      isStrokeCapRound: true,
      dotData: FlDotData(
        show: true,
        getDotPainter: (spot, percent, bar, index) => FlDotCirclePainter(
          radius: 3,
          color: color,
          strokeWidth: 2,
          strokeColor: Colors.white,
        ),
      ),
      belowBarData: BarAreaData(
        show: true,
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [color.withValues(alpha: 0.3), color.withValues(alpha: 0.0)],
        ),
      ),
    );
  }

  double _calculateInterval(List<double> a, List<double> b) {
    final allValues = [...a, ...b];
    if (allValues.isEmpty) return 1;
    final maxVal = allValues.reduce((a, b) => a > b ? a : b);
    if (maxVal <= 0) return 1;
    return (maxVal / 4).ceilToDouble();
  }

  Widget _buildDoughnutChart(bool isDark) {
    final assetComp = _data['asset_composition'] ?? {};
    final labels = List<String>.from(assetComp['labels'] ?? []);
    final values = List<double>.from(
      (assetComp['data'] as List?)?.map((e) => (e as num).toDouble()) ?? [],
    );

    if (labels.isEmpty || values.isEmpty) return const SizedBox.shrink();

    final colors = [AppTheme.primary, AppTheme.info, AppTheme.success, const Color(0xFF8B5CF6), AppTheme.gold];
    final total = values.fold(0.0, (sum, v) => sum + v);

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: isDark ? AppTheme.surfaceCardDark : Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: isDark ? AppTheme.borderDark : AppTheme.borderLight),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.donut_large_rounded, color: AppTheme.gold, size: 20),
              const SizedBox(width: 8),
              Text(
                'Komposisi Aset Top 5',
                style: GoogleFonts.dmSans(fontSize: 15, fontWeight: FontWeight.w700),
              ),
            ],
          ),
          const SizedBox(height: 20),
          SizedBox(
            height: 200,
            child: PieChart(
              PieChartData(
                sectionsSpace: 3,
                centerSpaceRadius: 55,
                sections: values.asMap().entries.map((e) {
                  final pct = total > 0 ? (e.value / total * 100) : 0;
                  return PieChartSectionData(
                    value: e.value,
                    color: colors[e.key % colors.length],
                    title: '${pct.toStringAsFixed(0)}%',
                    titleStyle: GoogleFonts.dmSans(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                    ),
                    radius: 35,
                  );
                }).toList(),
              ),
            ),
          ),
          const SizedBox(height: 16),
          // Legend
          ...labels.asMap().entries.map((e) => Padding(
            padding: const EdgeInsets.symmetric(vertical: 3),
            child: Row(
              children: [
                Container(
                  width: 10, height: 10,
                  decoration: BoxDecoration(
                    color: colors[e.key % colors.length],
                    shape: BoxShape.circle,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    e.value,
                    style: GoogleFonts.dmSans(fontSize: 12, color: AppTheme.textSecondaryDark),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                Text(
                  formatRupiah(values[e.key]),
                  style: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.w600),
                ),
              ],
            ),
          )),
        ],
      ),
    );
  }

  Widget _buildShimmerLoading() {
    return SingleChildScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      child: Column(
        children: [
          GridView.count(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            crossAxisCount: 2,
            mainAxisSpacing: 12,
            crossAxisSpacing: 12,
            childAspectRatio: 1.1,
            children: List.generate(5, (_) => const ShimmerCard()),
          ),
          const SizedBox(height: 16),
          const ShimmerBlock(height: 280),
        ],
      ),
    );
  }
}

class _YearDropdown extends StatelessWidget {
  final List<int> years;
  final int currentYear;
  final ValueChanged<int> onChanged;

  const _YearDropdown({required this.years, required this.currentYear, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.3)),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<int>(
          value: currentYear,
          isDense: true,
          icon: const Icon(Icons.keyboard_arrow_down, size: 18),
          style: GoogleFonts.dmSans(
            fontSize: 13, 
            fontWeight: FontWeight.w600,
            color: Theme.of(context).textTheme.bodyMedium?.color,
          ),
          items: years.map((y) => DropdownMenuItem(value: y, child: Text('Tahun $y'))).toList(),
          onChanged: (v) { if (v != null) onChanged(v); },
        ),
      ),
    );
  }
}

class _LegendDot extends StatelessWidget {
  final Color color;
  final String label;
  const _LegendDot({required this.color, required this.label});

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(width: 8, height: 8, decoration: BoxDecoration(color: color, shape: BoxShape.circle)),
        const SizedBox(width: 4),
        Text(label, style: GoogleFonts.dmSans(fontSize: 11, color: AppTheme.textMutedDark)),
      ],
    );
  }
}
