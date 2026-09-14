import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../services/auth_service.dart';
import '../services/connectivity_provider.dart';
import '../services/database_service.dart';
import '../config/app_theme.dart';
import '../utils/helpers.dart';
import '../services/api_service.dart';
import '../services/theme_provider.dart';
import '../config/api_config.dart';
import 'package:dio/dio.dart';
import '../widgets/app_drawer.dart';

/// Settings screen — Profile, sync controls, theme, logout.
class SettingsScreen extends StatelessWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthService>();
    final connectivity = context.watch<ConnectivityProvider>();
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      drawer: const AppDrawer(currentRoute: '/settings'),
      appBar: AppBar(title: const Text('Settings')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Profile card
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: const LinearGradient(colors: [Color(0xFF0F2847), Color(0xFF1A3A6E)]),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Row(children: [
                CircleAvatar(
                  radius: 28,
                  backgroundColor: getRoleColor(auth.userRole),
                  child: Text(
                    auth.userName.isNotEmpty ? auth.userName[0].toUpperCase() : 'U',
                    style: GoogleFonts.dmSans(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 22),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(auth.userName, style: GoogleFonts.dmSans(fontSize: 18, fontWeight: FontWeight.w700, color: Colors.white)),
                  Text(auth.userUsername, style: GoogleFonts.dmSans(fontSize: 13, color: const Color(0xFFBFDBFE))),
                  const SizedBox(height: 4),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(color: getRoleColor(auth.userRole).withValues(alpha: 0.2), borderRadius: BorderRadius.circular(6)),
                    child: Text(getRoleLabel(auth.userRole), style: GoogleFonts.dmSans(fontSize: 11, fontWeight: FontWeight.w600, color: getRoleColor(auth.userRole))),
                  ),
                ])),
              ]),
            ),
            const SizedBox(height: 24),

            if (auth.userRole != 'cashier') ...[
              // Sync section
              Text('SINKRONISASI', style: GoogleFonts.dmSans(fontSize: 11, fontWeight: FontWeight.w700, letterSpacing: 1, color: AppTheme.textMutedDark)),
              const SizedBox(height: 8),
              _SettingsCard(isDark: isDark, children: [
                _SettingsTile(
                  icon: Icons.cloud_sync_rounded,
                  iconColor: connectivity.isOnline ? AppTheme.success : AppTheme.warning,
                  title: 'Status Koneksi',
                  subtitle: connectivity.isOnline ? 'Online — tersambung ke server' : 'Offline — data disimpan lokal',
                ),
                const Divider(height: 1),
                _SettingsTile(
                  icon: Icons.pending_actions_rounded,
                  iconColor: AppTheme.info,
                  title: 'Transaksi Pending',
                  subtitle: '${connectivity.pendingCount} transaksi menunggu sinkronisasi',
                  trailing: connectivity.pendingCount > 0
                      ? TextButton(
                          onPressed: connectivity.isOnline ? () => connectivity.syncPendingTransactions() : null,
                          child: Text('Sync', style: GoogleFonts.dmSans(fontWeight: FontWeight.w600)),
                        )
                      : null,
                ),
              ]),
              const SizedBox(height: 20),

              // Data section
              Text('DATA', style: GoogleFonts.dmSans(fontSize: 11, fontWeight: FontWeight.w700, letterSpacing: 1, color: AppTheme.textMutedDark)),
              const SizedBox(height: 8),
              _SettingsCard(isDark: isDark, children: [
                _SettingsTile(
                  icon: Icons.delete_sweep_rounded,
                  iconColor: AppTheme.danger,
                  title: 'Hapus Cache Lokal',
                  subtitle: 'Menghapus data cache, bukan transaksi pending',
                  onTap: () async {
                    final confirmed = await showDialog<bool>(context: context, builder: (ctx) => AlertDialog(
                      title: Text('Hapus Cache?', style: GoogleFonts.dmSans(fontWeight: FontWeight.w700)),
                      content: const Text('Cache lokal akan dihapus. Transaksi pending tidak akan terpengaruh.'),
                      actions: [
                        TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
                        ElevatedButton(onPressed: () => Navigator.pop(ctx, true), style: ElevatedButton.styleFrom(backgroundColor: AppTheme.danger), child: const Text('Hapus')),
                      ],
                    ));
                    if (confirmed == true) {
                      await DatabaseService().clearAll();
                      if (context.mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('✓ Cache berhasil dihapus'), backgroundColor: AppTheme.success));
                    }
                  },
                ),
              ]),
              const SizedBox(height: 20),

              // Tampilan
              Text('TAMPILAN', style: GoogleFonts.dmSans(fontSize: 11, fontWeight: FontWeight.w700, letterSpacing: 1, color: AppTheme.textMutedDark)),
              const SizedBox(height: 8),
              _SettingsCard(isDark: isDark, children: [
                _SettingsTile(
                  icon: Icons.dark_mode_rounded,
                  iconColor: AppTheme.teal,
                  title: 'Tema Aplikasi',
                  subtitle: 'Pilih mode tampilan',
                  trailing: DropdownButtonHideUnderline(
                    child: DropdownButton<ThemeMode>(
                      value: context.watch<ThemeProvider>().themeMode,
                      items: const [
                        DropdownMenuItem(value: ThemeMode.system, child: Text('Sistem', style: TextStyle(fontSize: 13))),
                        DropdownMenuItem(value: ThemeMode.light, child: Text('Terang', style: TextStyle(fontSize: 13))),
                        DropdownMenuItem(value: ThemeMode.dark, child: Text('Gelap', style: TextStyle(fontSize: 13))),
                      ],
                      onChanged: (mode) {
                        if (mode != null) {
                          context.read<ThemeProvider>().setThemeMode(mode);
                        }
                      },
                    ),
                  ),
                ),
              ]),
              const SizedBox(height: 20),

              // Tentang
              Text('TENTANG', style: GoogleFonts.dmSans(fontSize: 11, fontWeight: FontWeight.w700, letterSpacing: 1, color: AppTheme.textMutedDark)),
              const SizedBox(height: 8),
              _SettingsCard(isDark: isDark, children: [
                _SettingsTile(icon: Icons.info_outline_rounded, iconColor: AppTheme.textMutedDark, title: 'KAsql Mobile', subtitle: 'v1.0.0 — Sistem Akuntansi Keuangan'),
                const Divider(height: 1),
                _SettingsTile(
                  icon: Icons.business_rounded,
                  iconColor: AppTheme.textMutedDark,
                  title: 'Perusahaan',
                  subtitle: auth.companyName ?? 'USAHA',
                  trailing: auth.isAdmin ? const Icon(Icons.edit_rounded, size: 16, color: AppTheme.info) : null,
                  onTap: auth.isAdmin ? () async {
                    final controller = TextEditingController(text: auth.companyName);
                    bool isLoading = false;
                    final newName = await showDialog<String>(context: context, builder: (ctx) {
                      return StatefulBuilder(builder: (context, setStateDialog) {
                        return AlertDialog(
                          title: Text('Ubah Nama Perusahaan', style: GoogleFonts.dmSans(fontWeight: FontWeight.w700)),
                          content: TextField(
                            controller: controller,
                            decoration: const InputDecoration(labelText: 'Nama Perusahaan', border: OutlineInputBorder()),
                            autofocus: true,
                          ),
                          actions: [
                            TextButton(onPressed: isLoading ? null : () => Navigator.pop(ctx), child: const Text('Batal')),
                            ElevatedButton(
                              onPressed: isLoading ? null : () async {
                                if (controller.text.trim().isEmpty) return;
                                setStateDialog(() => isLoading = true);
                                try {
                                  final res = await ApiService().dio.post(ApiConfig.settingsUpdate, data: {'nama_perusahaan': controller.text.trim()});
                                  if (res.data['status'] == 'success') {
                                    if (ctx.mounted) Navigator.pop(ctx, controller.text.trim());
                                  } else {
                                    setStateDialog(() => isLoading = false);
                                    if (ctx.mounted) ScaffoldMessenger.of(ctx).showSnackBar(SnackBar(content: Text(res.data['message'] ?? 'Gagal')));
                                  }
                                } on DioException catch (e) {
                                  setStateDialog(() => isLoading = false);
                                  if (ctx.mounted) ScaffoldMessenger.of(ctx).showSnackBar(SnackBar(content: Text(e.response?.data?['message'] ?? 'Gagal mengubah nama')));
                                }
                              },
                              style: ElevatedButton.styleFrom(backgroundColor: AppTheme.teal),
                              child: isLoading ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)) : const Text('Simpan'),
                            ),
                          ],
                        );
                      });
                    });
                    if (newName != null) {
                      await auth.updateCompanyName(newName);
                      if (!context.mounted) return;
                      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Nama perusahaan berhasil diubah'), backgroundColor: AppTheme.success));
                    }
                  } : null,
                ),
              ]),
              const SizedBox(height: 24),
            ],

            // Logout
            SizedBox(
              height: 50,
              child: OutlinedButton.icon(
                onPressed: () async {
                  final confirmed = await showDialog<bool>(context: context, builder: (ctx) => AlertDialog(
                    title: Text('Logout?', style: GoogleFonts.dmSans(fontWeight: FontWeight.w700)),
                    content: const Text('Anda akan keluar dari aplikasi.'),
                    actions: [
                      TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
                      ElevatedButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Logout')),
                    ],
                  ));
                  if (confirmed == true) {
                    await auth.logout();
                    if (!context.mounted) return;
                    Navigator.pushNamedAndRemoveUntil(context, '/login', (_) => false);
                  }
                },
                icon: const Icon(Icons.logout_rounded, color: AppTheme.danger),
                label: Text('Logout', style: GoogleFonts.dmSans(fontWeight: FontWeight.w700, color: AppTheme.danger)),
                style: OutlinedButton.styleFrom(
                  side: const BorderSide(color: AppTheme.danger),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
              ),
            ),
            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }
}

class _SettingsCard extends StatelessWidget {
  final bool isDark;
  final List<Widget> children;
  const _SettingsCard({required this.isDark, required this.children});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: isDark ? AppTheme.surfaceCardDark : Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: isDark ? AppTheme.borderDark : AppTheme.borderLight),
      ),
      child: Column(children: children),
    );
  }
}

class _SettingsTile extends StatelessWidget {
  final IconData icon;
  final Color iconColor;
  final String title;
  final String subtitle;
  final VoidCallback? onTap;
  final Widget? trailing;

  const _SettingsTile({required this.icon, required this.iconColor, required this.title, required this.subtitle, this.onTap, this.trailing});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        child: Row(children: [
          Container(
            width: 38, height: 38,
            decoration: BoxDecoration(color: iconColor.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(10)),
            child: Icon(icon, color: iconColor, size: 20),
          ),
          const SizedBox(width: 14),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(title, style: GoogleFonts.dmSans(fontSize: 14, fontWeight: FontWeight.w600)),
            Text(subtitle, style: GoogleFonts.dmSans(fontSize: 12, color: AppTheme.textMutedDark)),
          ])),
          if (trailing != null) trailing!,
        ]),
      ),
    );
  }
}
