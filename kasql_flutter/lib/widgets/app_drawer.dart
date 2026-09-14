import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import '../services/auth_service.dart';
import '../services/connectivity_provider.dart';
import '../config/app_theme.dart';
import '../utils/helpers.dart';

/// Premium navigation drawer mirroring the web sidebar structure.
class AppDrawer extends StatelessWidget {
  final String currentRoute;

  const AppDrawer({super.key, required this.currentRoute});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthService>();
    final connectivity = context.watch<ConnectivityProvider>();
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Drawer(
      backgroundColor: isDark ? AppTheme.navy800 : Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.horizontal(right: Radius.circular(24)),
      ),
      elevation: 16,
      child: ClipRRect(
        borderRadius: const BorderRadius.horizontal(right: Radius.circular(24)),
        child: Container(
          color: isDark ? AppTheme.navy800 : Colors.white,
          child: Column(
            children: [
            // ─── Header ───
            Container(
              width: double.infinity,
              padding: EdgeInsets.only(
                top: MediaQuery.of(context).padding.top + 16,
                left: 20,
                right: 20,
                bottom: 20,
              ),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [Color(0xFF0F2847), Color(0xFF1A3A6E)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.15),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Icon(Icons.account_balance_wallet_outlined, color: Colors.white, size: 24),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'KAsql.',
                              style: GoogleFonts.outfit(
                                fontSize: 24,
                                fontWeight: FontWeight.w800,
                                color: Colors.white,
                                letterSpacing: -0.5,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              auth.companyName ?? 'USAHA',
                              style: GoogleFonts.dmSans(
                                fontSize: 11,
                                fontWeight: FontWeight.w600,
                                color: const Color(0xFFBFDBFE),
                                letterSpacing: 0.5,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  // Sync Status
                  _SyncStatusPill(connectivity: connectivity),
                ],
              ),
            ),

            // ─── Menu Items ───
            Expanded(
              child: TweenAnimationBuilder<double>(
                tween: Tween<double>(begin: 0.0, end: 1.0),
                duration: const Duration(milliseconds: 800),
                curve: Curves.easeOutCubic,
                builder: (context, value, child) {
                  return Opacity(
                    opacity: value,
                    child: Transform.translate(
                      offset: Offset(-50 * (1 - value), 0),
                      child: child,
                    ),
                  );
                },
                child: ListView(
                padding: const EdgeInsets.symmetric(vertical: 8),
                children: [
                  // Beranda
                  if (auth.canViewReports) ...[
                    _SectionTitle('Beranda'),
                    _DrawerItem(
                      icon: Icons.dashboard_rounded,
                      label: 'Dashboard',
                      route: '/dashboard',
                      currentRoute: currentRoute,
                    ),
                  ],

                  // Laporan Akuntansi
                  _SectionTitle('Laporan Akuntansi'),
                  _DrawerItem(
                    icon: Icons.description_outlined,
                    label: 'Jurnal Umum',
                    route: '/jurnal',
                    currentRoute: currentRoute,
                  ),
                  if (auth.canViewReports) ...[
                    _DrawerItem(
                      icon: Icons.edit_note_rounded,
                      label: 'Jurnal Penyesuaian',
                      route: '/jurnal-penyesuaian',
                      currentRoute: currentRoute,
                    ),
                    _DrawerItem(
                      icon: Icons.replay_rounded,
                      label: 'Jurnal Pembalik',
                      route: '/jurnal-pembalik',
                      currentRoute: currentRoute,
                    ),
                    _DrawerItem(
                      icon: Icons.menu_book_rounded,
                      label: 'Buku Besar',
                      route: '/laporan',
                      routeArgs: 0,
                      currentRoute: currentRoute,
                    ),
                    _DrawerItem(
                      icon: Icons.attach_money_rounded,
                      label: 'Neraca Saldo',
                      route: '/laporan',
                      routeArgs: 1,
                      currentRoute: currentRoute,
                    ),
                    _DrawerItem(
                      icon: Icons.trending_up_rounded,
                      label: 'Laba Rugi',
                      route: '/laporan',
                      routeArgs: 2,
                      currentRoute: currentRoute,
                    ),
                    _DrawerItem(
                      icon: Icons.grid_view_rounded,
                      label: 'Neraca Laporan',
                      route: '/laporan',
                      routeArgs: 3,
                      currentRoute: currentRoute,
                    ),
                    _DrawerItem(
                      icon: Icons.auto_awesome_rounded,
                      label: 'Analisis AI',
                      route: '/analisis',
                      currentRoute: currentRoute,
                    ),
                  ],

                  // Input & Verifikasi
                  _SectionTitle('Transaksi'),
                  _ExpandableDrawerItem(
                    icon: Icons.add_circle_outline_rounded,
                    label: 'Input Transaksi',
                    isExpanded: currentRoute == '/transaksi-form' || currentRoute == '/prepaid-form' || currentRoute == '/aset-tetap-form',
                    children: [
                      _DrawerSubItem(label: 'Jurnal Umum', route: '/transaksi-form', currentRoute: currentRoute),
                      if (auth.canViewReports) _DrawerSubItem(label: 'Prepaid Expense', route: '/prepaid-form', currentRoute: currentRoute),
                      if (auth.canViewReports) _DrawerSubItem(label: 'Depresiasi Aset', route: '/aset-tetap-form', currentRoute: currentRoute),
                    ],
                  ),
                  if (auth.canViewReports)
                    _DrawerItem(
                      icon: Icons.checklist_rounded,
                      label: 'Transaksi Pending',
                      route: '/transaksi-pending',
                      currentRoute: currentRoute,
                    ),
                  if (auth.canViewReports)
                    _DrawerItem(
                      icon: Icons.event_note_rounded,
                      label: 'Closing Periode',
                      route: '/closing',
                      currentRoute: currentRoute,
                    ),

                  // Data Master
                  if (auth.canViewReports) ...[
                    _SectionTitle('Data Master'),
                    _DrawerItem(
                      icon: Icons.list_alt_rounded,
                      label: 'Daftar Akun',
                      route: '/master-akun',
                      currentRoute: currentRoute,
                    ),
                    _DrawerItem(
                      icon: Icons.info_outline_rounded,
                      label: 'Detail Reaksi',
                      route: '/detail-reaksi',
                      currentRoute: currentRoute,
                    ),
                  ],

                  // Admin
                  if (auth.isAdmin) ...[
                    _SectionTitle('Administrasi'),
                    _DrawerItem(
                      icon: Icons.people_outline_rounded,
                      label: 'Manajemen User',
                      route: '/user-management',
                      currentRoute: currentRoute,
                    ),
                  ],
                ],
              ),
            ),
            ),

            // ─── Footer ───
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                border: Border(
                  top: BorderSide(
                    color: isDark ? AppTheme.borderDark : AppTheme.borderLight,
                  ),
                ),
              ),
              child: Column(
                children: [
                  // User Info
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: isDark
                          ? Colors.white.withValues(alpha: 0.05)
                          : Colors.grey.withValues(alpha: 0.08),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Row(
                      children: [
                        CircleAvatar(
                          radius: 18,
                          backgroundColor: getRoleColor(auth.userRole),
                          child: Text(
                            auth.userName.isNotEmpty
                                ? auth.userName[0].toUpperCase()
                                : 'U',
                            style: GoogleFonts.dmSans(
                              color: Colors.white,
                              fontWeight: FontWeight.w700,
                              fontSize: 14,
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                auth.userName,
                                style: GoogleFonts.dmSans(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w600,
                                ),
                                overflow: TextOverflow.ellipsis,
                              ),
                              Text(
                                getRoleLabel(auth.userRole),
                                style: GoogleFonts.dmSans(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w500,
                                  color: getRoleColor(auth.userRole),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 8),
                  // Settings
                  _DrawerItem(
                    icon: Icons.settings_rounded,
                    label: 'Settings',
                    route: '/settings',
                    currentRoute: currentRoute,
                    dense: true,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  final String title;
  const _SectionTitle(this.title);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 4),
      child: Text(
        title.toUpperCase(),
        style: GoogleFonts.dmSans(
          fontSize: 10,
          fontWeight: FontWeight.w700,
          letterSpacing: 1.2,
          color: AppTheme.textMutedDark,
        ),
      ),
    );
  }
}

class _DrawerItem extends StatelessWidget {
  final IconData icon;
  final String label;
  final String route;
  final String currentRoute;
  final dynamic routeArgs;
  final bool dense;

  const _DrawerItem({
    required this.icon,
    required this.label,
    required this.route,
    required this.currentRoute,
    this.routeArgs,
    this.dense = false,
  });

  @override
  Widget build(BuildContext context) {
    final isActive = currentRoute == route;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 8, vertical: 1),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(10),
        child: InkWell(
          borderRadius: BorderRadius.circular(10),
          onTap: () {
            Navigator.pop(context); // Close drawer
            if (currentRoute != route) {
              Future.delayed(const Duration(milliseconds: 250), () {
                if (context.mounted) {
                  Navigator.pushReplacementNamed(context, route, arguments: routeArgs);
                }
              });
            }
          },
          child: Container(
            padding: EdgeInsets.symmetric(
              horizontal: 14,
              vertical: dense ? 10 : 12,
            ),
            decoration: BoxDecoration(
              color: isActive
                  ? (isDark
                      ? AppTheme.teal.withValues(alpha: 0.12)
                      : AppTheme.tealDark.withValues(alpha: 0.08))
                  : null,
              borderRadius: BorderRadius.circular(10),
              border: isActive
                  ? Border.all(
                      color: isDark
                          ? AppTheme.teal.withValues(alpha: 0.25)
                          : AppTheme.tealDark.withValues(alpha: 0.15),
                    )
                  : null,
            ),
            child: Row(
              children: [
                Icon(
                  icon,
                  size: dense ? 18 : 20,
                  color: isActive
                      ? (isDark ? AppTheme.teal : AppTheme.tealDark)
                      : (isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight),
                ),
                const SizedBox(width: 12),
                Text(
                  label,
                  style: GoogleFonts.dmSans(
                    fontSize: dense ? 13 : 14,
                    fontWeight: isActive ? FontWeight.w600 : FontWeight.w400,
                    color: isActive
                        ? (isDark ? AppTheme.teal : AppTheme.tealDark)
                        : (isDark ? AppTheme.textSecondaryDark : AppTheme.textPrimaryLight),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _SyncStatusPill extends StatelessWidget {
  final ConnectivityProvider connectivity;
  const _SyncStatusPill({required this.connectivity});

  @override
  Widget build(BuildContext context) {
    Color bgColor;
    Color textColor;
    IconData icon;

    if (connectivity.isSyncing) {
      bgColor = AppTheme.info.withValues(alpha: 0.2);
      textColor = AppTheme.info;
      icon = Icons.sync_rounded;
    } else if (!connectivity.isOnline) {
      bgColor = AppTheme.warning.withValues(alpha: 0.2);
      textColor = AppTheme.warning;
      icon = Icons.cloud_off_rounded;
    } else if (connectivity.pendingCount > 0) {
      bgColor = AppTheme.warning.withValues(alpha: 0.2);
      textColor = AppTheme.warning;
      icon = Icons.warning_amber_rounded;
    } else {
      bgColor = AppTheme.success.withValues(alpha: 0.2);
      textColor = AppTheme.success;
      icon = Icons.cloud_done_rounded;
    }

    return GestureDetector(
      onTap: () {
        if (connectivity.isOnline) {
          connectivity.syncPendingTransactions();
        }
      },
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: bgColor,
          borderRadius: BorderRadius.circular(20),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            connectivity.isSyncing
                ? SizedBox(
                    width: 14,
                    height: 14,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: textColor,
                    ),
                  )
                : Icon(icon, size: 14, color: textColor),
            const SizedBox(width: 6),
            Text(
              connectivity.syncStatusLabel,
              style: GoogleFonts.dmSans(
                fontSize: 11,
                fontWeight: FontWeight.w600,
                color: textColor,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ExpandableDrawerItem extends StatefulWidget {
  final IconData icon;
  final String label;
  final bool isExpanded;
  final List<Widget> children;

  const _ExpandableDrawerItem({
    required this.icon,
    required this.label,
    required this.isExpanded,
    required this.children,
  });

  @override
  State<_ExpandableDrawerItem> createState() => _ExpandableDrawerItemState();
}

class _ExpandableDrawerItemState extends State<_ExpandableDrawerItem> {
  late bool _isExpanded;

  @override
  void initState() {
    super.initState();
    _isExpanded = widget.isExpanded;
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Theme(
      data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 8, vertical: 1),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(10),
          color: _isExpanded
              ? (isDark ? AppTheme.teal.withValues(alpha: 0.05) : AppTheme.tealDark.withValues(alpha: 0.03))
              : Colors.transparent,
        ),
        child: ExpansionTile(
          initiallyExpanded: _isExpanded,
          onExpansionChanged: (expanded) {
            setState(() {
              _isExpanded = expanded;
            });
          },
          tilePadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 0),
          childrenPadding: const EdgeInsets.only(bottom: 8),
          leading: Icon(
            widget.icon,
            size: 20,
            color: _isExpanded
                ? (isDark ? AppTheme.teal : AppTheme.tealDark)
                : (isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight),
          ),
          title: Text(
            widget.label,
            style: GoogleFonts.dmSans(
              fontSize: 14,
              fontWeight: _isExpanded ? FontWeight.w600 : FontWeight.w400,
              color: _isExpanded
                  ? (isDark ? AppTheme.teal : AppTheme.tealDark)
                  : (isDark ? AppTheme.textSecondaryDark : AppTheme.textPrimaryLight),
            ),
          ),
          children: widget.children,
        ),
      ),
    );
  }
}

class _DrawerSubItem extends StatelessWidget {
  final String label;
  final String route;
  final String currentRoute;

  const _DrawerSubItem({
    required this.label,
    required this.route,
    required this.currentRoute,
  });

  @override
  Widget build(BuildContext context) {
    final isActive = currentRoute == route;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return InkWell(
      onTap: () {
        Navigator.pop(context);
        if (currentRoute != route) {
          Future.delayed(const Duration(milliseconds: 250), () {
            if (context.mounted) {
              Navigator.pushReplacementNamed(context, route);
            }
          });
        }
      },
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 46, vertical: 10),
        width: double.infinity,
        child: Text(
          label,
          style: GoogleFonts.dmSans(
            fontSize: 13,
            fontWeight: isActive ? FontWeight.w600 : FontWeight.w400,
            color: isActive
                ? (isDark ? AppTheme.teal : AppTheme.tealDark)
                : (isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight),
          ),
        ),
      ),
    );
  }
}
