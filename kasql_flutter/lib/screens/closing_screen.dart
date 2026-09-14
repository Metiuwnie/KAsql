import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../config/app_theme.dart';
import '../widgets/app_drawer.dart';
import '../services/api_service.dart';
import '../services/connectivity_provider.dart';
import 'package:provider/provider.dart';
import '../config/api_config.dart';
import 'package:intl/intl.dart';

/// Closing Periode — Shows a list of accounting periods and allows closing them.
class ClosingScreen extends StatefulWidget {
  const ClosingScreen({super.key});

  @override
  State<ClosingScreen> createState() => _ClosingScreenState();
}

class _ClosingScreenState extends State<ClosingScreen> {
  List<Map<String, dynamic>> _periodeList = [];
  List<String> _availableYears = [];
  String? _selectedYear;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadPeriode();
  }

  Future<void> _loadPeriode() async {
    setState(() => _isLoading = true);
    try {
      final response = await ApiService().get(ApiConfig.closing);
      if (response.statusCode == 200 && response.data['status'] == 'success') {
        final List<Map<String, dynamic>> data = List<Map<String, dynamic>>.from(response.data['data']);
        
        final Set<String> years = {};
        for (var p in data) {
          final tglMulai = p['tanggal_mulai'] as String?;
          if (tglMulai != null && tglMulai.length >= 4) {
            years.add(tglMulai.substring(0, 4));
          }
        }
        
        final sortedYears = years.toList()..sort((a, b) => b.compareTo(a));

        setState(() {
          _periodeList = data;
          _availableYears = sortedYears;
          if (_selectedYear == null && sortedYears.isNotEmpty) {
            _selectedYear = sortedYears.first;
          } else if (!sortedYears.contains(_selectedYear)) {
            _selectedYear = sortedYears.isNotEmpty ? sortedYears.first : null;
          }
        });
      }
    } catch (e) {
      debugPrint('Error loading closing periods: $e');
    }
    setState(() => _isLoading = false);
  }

  Future<void> _processClosing(int id, String namaPeriode) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('Tutup Periode?', style: GoogleFonts.dmSans(fontWeight: FontWeight.w700)),
        content: Text('Anda yakin ingin menutup $namaPeriode?\n\nPastikan semua transaksi telah diposting dan jurnal penyesuaian telah selesai. Proses ini tidak dapat dibatalkan dengan mudah.', style: GoogleFonts.dmSans()),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppTheme.warning, foregroundColor: Colors.white),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Tutup Periode'),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    setState(() => _isLoading = true);
    try {
      final response = await ApiService().post(
        ApiConfig.closingProcess,
        data: {'periode_id': id},
      );
      
      if (response.statusCode == 200 && response.data['status'] == 'success') {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(response.data['data']['message']), backgroundColor: AppTheme.success),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Gagal menutup periode: $e'), backgroundColor: AppTheme.danger),
        );
      }
    }
    _loadPeriode();
  }

  String formatRupiah(double amount) {
    final formatCurrency = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);
    return formatCurrency.format(amount);
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    // Filter the periods by selected year
    final displayedPeriods = _selectedYear != null
        ? _periodeList.where((p) {
            final tglMulai = p['tanggal_mulai'] as String?;
            return tglMulai != null && tglMulai.startsWith(_selectedYear!);
          }).toList()
        : _periodeList;

    return Scaffold(
      drawer: const AppDrawer(currentRoute: '/closing'),
      appBar: AppBar(title: const Text('Closing Periode')),
      body: RefreshIndicator(
        onRefresh: _loadPeriode,
        child: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  if (!context.watch<ConnectivityProvider>().isOnline)
                    Container(
                      margin: const EdgeInsets.only(bottom: 16),
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
                              'Fitur Closing Periode dinonaktifkan saat Offline guna mencegah kerusakan data (Race Condition). Tindakan tutup buku wajib terpusat di server untuk mengunci sistem secara real-time dan memastikan tidak ada selisih neraca dari transaksi sinkronisasi yang tertunda.',
                              style: GoogleFonts.dmSans(fontSize: 13, color: AppTheme.danger, fontWeight: FontWeight.w600),
                            ),
                          ),
                        ],
                      ),
                    ),
                  _buildHeaderCard(isDark),
                  const SizedBox(height: 24),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'RIWAYAT PERIODE',
                        style: GoogleFonts.dmSans(
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                          letterSpacing: 1,
                          color: AppTheme.textMutedDark,
                        ),
                      ),
                      if (_availableYears.isNotEmpty)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                          decoration: BoxDecoration(
                            color: isDark ? AppTheme.navy600 : Colors.grey[200],
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: DropdownButtonHideUnderline(
                            child: DropdownButton<String>(
                              value: _selectedYear,
                              isDense: true,
                              icon: const Icon(Icons.arrow_drop_down, size: 20),
                              style: GoogleFonts.dmSans(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: isDark ? Colors.white : Colors.black87,
                              ),
                              onChanged: (String? newValue) {
                                if (newValue != null) {
                                  setState(() {
                                    _selectedYear = newValue;
                                  });
                                }
                              },
                              items: _availableYears.map<DropdownMenuItem<String>>((String value) {
                                return DropdownMenuItem<String>(
                                  value: value,
                                  child: Text(value),
                                );
                              }).toList(),
                            ),
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  if (displayedPeriods.isEmpty)
                    const Center(
                      child: Padding(
                        padding: EdgeInsets.all(32.0),
                        child: Text('Belum ada riwayat periode.'),
                      ),
                    )
                  else
                    ...displayedPeriods.map((periode) => _buildPeriodeCard(periode, isDark)),
                ],
              ),
      ),
    );
  }

  Widget _buildHeaderCard(bool isDark) {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: isDark ? AppTheme.surfaceCardDark : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: isDark ? AppTheme.borderDark : AppTheme.borderLight),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          )
        ],
      ),
      child: Column(
        children: [
          Container(
            width: 60, height: 60,
            decoration: BoxDecoration(
              color: AppTheme.warning.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(16),
            ),
            child: const Icon(Icons.lock_clock_rounded, size: 30, color: AppTheme.warning),
          ),
          const SizedBox(height: 16),
          Text('Tutup Buku Periode', style: GoogleFonts.dmSans(fontSize: 18, fontWeight: FontWeight.w700)),
          const SizedBox(height: 8),
          Text(
            'Proses ini akan mengunci transaksi pada periode berjalan dan memindahkan saldo laba/rugi ke ekuitas.',
            textAlign: TextAlign.center,
            style: GoogleFonts.dmSans(fontSize: 13, color: AppTheme.textMutedDark, height: 1.5),
          ),
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () {
                // Find oldest open period that is NOT locked (after 2026-05-31)
                final lockedDate = DateTime.parse('2026-05-31');
                final openPeriods = _periodeList.where((p) {
                  if (p['status'] != 'open') return false;
                  final tglSelesai = DateTime.tryParse(p['tanggal_selesai'].toString());
                  if (tglSelesai != null && (tglSelesai.isBefore(lockedDate) || tglSelesai.isAtSameMomentAs(lockedDate))) {
                    return false; // Locked
                  }
                  return true;
                }).toList();

                if (openPeriods.isNotEmpty) {
                  // The list is DESC (newest first). The oldest open is at the end of the openPeriods list.
                  final oldestOpen = openPeriods.last;
                  final periodId = int.tryParse(oldestOpen['id'].toString()) ?? 0;
                  _processClosing(periodId, oldestOpen['nama_periode'] as String);
                } else {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Tidak ada periode terbuka yang dapat ditutup.'), backgroundColor: AppTheme.info),
                  );
                }
              },
              icon: const Icon(Icons.lock_rounded, size: 18),
              label: Text('Proses Closing (Otomatis)', style: GoogleFonts.dmSans(fontWeight: FontWeight.w700)),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.warning,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 12),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPeriodeCard(Map<String, dynamic> periode, bool isDark) {
    final status = periode['status'] ?? 'open';
    final namaPeriode = periode['nama_periode'] ?? 'Periode';
    final tglMulai = periode['tanggal_mulai'] ?? '-';
    final tglSelesai = periode['tanggal_selesai'] ?? '-';
    final labaRugi = double.tryParse(periode['laba_periode']?.toString() ?? '0') ?? 0;
    final id = int.tryParse(periode['id'].toString()) ?? 0;

    // Check if period is locked by system
    final tglSelesaiDate = DateTime.tryParse(tglSelesai);
    final lockedDate = DateTime.parse('2026-05-31');
    final isLocked = tglSelesaiDate != null && (tglSelesaiDate.isBefore(lockedDate) || tglSelesaiDate.isAtSameMomentAs(lockedDate));

    Color badgeColor;
    Color badgeBg;
    String badgeText;
    IconData badgeIcon;

    if (isLocked) {
      badgeColor = AppTheme.textMutedDark;
      badgeBg = AppTheme.textMutedDark.withValues(alpha: 0.1);
      badgeText = 'TERKUNCI';
      badgeIcon = Icons.lock_outline;
    } else if (status == 'closed') {
      badgeColor = AppTheme.info;
      badgeBg = AppTheme.info.withValues(alpha: 0.1);
      badgeText = 'CLOSED';
      badgeIcon = Icons.lock_rounded;
    } else if (status == 'reopened') {
      badgeColor = AppTheme.warning;
      badgeBg = AppTheme.warning.withValues(alpha: 0.1);
      badgeText = 'REOPENED';
      badgeIcon = Icons.lock_open_rounded;
    } else {
      badgeColor = AppTheme.success;
      badgeBg = AppTheme.success.withValues(alpha: 0.1);
      badgeText = 'OPEN';
      badgeIcon = Icons.lock_open_rounded;
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark ? AppTheme.navy700.withValues(alpha: 0.5) : Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: isDark ? AppTheme.borderDark : AppTheme.borderLight),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                namaPeriode,
                style: GoogleFonts.dmSans(
                  fontWeight: FontWeight.w700,
                  fontSize: 16,
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: badgeBg,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Row(
                  children: [
                    Icon(badgeIcon, size: 12, color: badgeColor),
                    const SizedBox(width: 4),
                    Text(
                      badgeText,
                      style: GoogleFonts.dmSans(
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                        color: badgeColor,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Tgl Mulai', style: GoogleFonts.dmSans(fontSize: 11, color: AppTheme.textMutedDark)),
                  const SizedBox(height: 2),
                  Text(tglMulai, style: GoogleFonts.dmSans(fontSize: 13, fontWeight: FontWeight.w500)),
                ],
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text('Tgl Selesai', style: GoogleFonts.dmSans(fontSize: 11, color: AppTheme.textMutedDark)),
                  const SizedBox(height: 2),
                  Text(tglSelesai, style: GoogleFonts.dmSans(fontSize: 13, fontWeight: FontWeight.w500)),
                ],
              ),
            ],
          ),
          if (status == 'closed') ...[
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 12),
              child: Divider(height: 1),
            ),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('Laba / Rugi', style: GoogleFonts.dmSans(fontSize: 12, color: AppTheme.textMutedDark)),
                Text(
                  formatRupiah(labaRugi),
                  style: GoogleFonts.dmSans(
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                    color: labaRugi < 0 ? AppTheme.danger : AppTheme.success,
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}
