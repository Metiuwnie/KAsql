import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../config/app_theme.dart';
import '../widgets/app_drawer.dart';
import 'tabs/buku_besar_tab.dart';
import 'tabs/neraca_saldo_tab.dart';
import 'tabs/laba_rugi_tab.dart';
import 'tabs/neraca_tab.dart';

/// Laporan container — Tabbed view for all financial reports.
class LaporanScreen extends StatefulWidget {
  final int initialTab;
  const LaporanScreen({super.key, this.initialTab = 0});

  @override
  State<LaporanScreen> createState() => _LaporanScreenState();
}

class _LaporanScreenState extends State<LaporanScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  int _selectedYear = DateTime.now().year;
  int? _selectedMonth;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this, initialIndex: widget.initialTab);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      drawer: const AppDrawer(currentRoute: '/laporan'),
      appBar: AppBar(
        title: const Text('Laporan Keuangan'),
        bottom: TabBar(
          controller: _tabController,
          isScrollable: true,
          indicatorColor: AppTheme.teal,
          labelColor: AppTheme.teal,
          unselectedLabelColor: AppTheme.textMutedDark,
          labelStyle: GoogleFonts.dmSans(fontWeight: FontWeight.w600, fontSize: 13),
          tabAlignment: TabAlignment.start,
          tabs: const [
            Tab(text: 'Buku Besar'),
            Tab(text: 'Neraca Saldo'),
            Tab(text: 'Laba Rugi'),
            Tab(text: 'Neraca'),
          ],
        ),
      ),
      body: Column(
        children: [
          // Shared period filter
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            child: Row(
              children: [
                Expanded(
                  child: DropdownButtonFormField<int>(
                    value: _selectedYear,
                    decoration: const InputDecoration(labelText: 'Tahun', contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10)),
                    items: List.generate(5, (i) => DateTime.now().year - i).map((y) => DropdownMenuItem(value: y, child: Text('$y'))).toList(),
                    onChanged: (v) => setState(() => _selectedYear = v ?? _selectedYear),
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
                    onChanged: (v) => setState(() => _selectedMonth = v),
                  ),
                ),
              ],
            ),
          ),
          // Tab content
          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: [
                BukuBesarTab(tahun: _selectedYear, bulan: _selectedMonth),
                NeracaSaldoTab(tahun: _selectedYear, bulan: _selectedMonth),
                LabaRugiTab(tahun: _selectedYear, bulan: _selectedMonth),
                NeracaTab(tahun: _selectedYear, bulan: _selectedMonth),
              ],
            ),
          ),
        ],
      ),
    );
  }

  String _monthName(int month) {
    const names = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
    return names[month];
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }
}
