import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../services/api_service.dart';
import '../config/api_config.dart';
import '../config/app_theme.dart';
import '../utils/helpers.dart';
import '../widgets/app_drawer.dart';
import '../widgets/common_widgets.dart';

/// User management — Admin only CRUD.
class UserManagementScreen extends StatefulWidget {
  const UserManagementScreen({super.key});

  @override
  State<UserManagementScreen> createState() => _UserManagementScreenState();
}

class _UserManagementScreenState extends State<UserManagementScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _users = [];

  @override
  void initState() { super.initState(); _loadData(); }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    try {
      final response = await ApiService().get(ApiConfig.users);
      if (response.statusCode == 200 && response.data['status'] == 'success') {
        setState(() { _users = List<Map<String, dynamic>>.from(response.data['data']); _isLoading = false; });
      }
    } catch (e) { setState(() => _isLoading = false); }
  }

  void _showUserDialog({Map<String, dynamic>? user}) {
    final isEdit = user != null;
    final usernameCtrl = TextEditingController(text: user?['username'] ?? '');
    final namaCtrl = TextEditingController(text: user?['nama_lengkap'] ?? '');
    final passwordCtrl = TextEditingController();
    String role = user?['role'] ?? 'cashier';

    showDialog(context: context, builder: (ctx) => AlertDialog(
      title: Text(isEdit ? 'Edit User' : 'Tambah User', style: GoogleFonts.dmSans(fontWeight: FontWeight.w700)),
      content: SingleChildScrollView(child: Column(mainAxisSize: MainAxisSize.min, children: [
        TextField(controller: usernameCtrl, decoration: const InputDecoration(labelText: 'Username / Email')),
        const SizedBox(height: 8),
        TextField(controller: namaCtrl, decoration: const InputDecoration(labelText: 'Nama Lengkap')),
        const SizedBox(height: 8),
        TextField(controller: passwordCtrl, obscureText: true, decoration: InputDecoration(labelText: isEdit ? 'Password (kosongkan jika tidak berubah)' : 'Password')),
        const SizedBox(height: 8),
        DropdownButtonFormField<String>(value: role, decoration: const InputDecoration(labelText: 'Role'),
          items: const [
            DropdownMenuItem(value: 'admin', child: Text('Admin')),
            DropdownMenuItem(value: 'accountant', child: Text('Akuntan')),
            DropdownMenuItem(value: 'cashier', child: Text('Kasir')),
          ],
          onChanged: (v) => role = v ?? role),
      ])),
      actions: [
        TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
        ElevatedButton(onPressed: () async {
          try {
            final data = {
              'username': usernameCtrl.text,
              'nama_lengkap': namaCtrl.text,
              'role': role,
            };
            if (passwordCtrl.text.isNotEmpty) data['password'] = passwordCtrl.text;

            if (isEdit) {
              await ApiService().put(ApiConfig.userUpdate(user!['id']), data: data);
            } else {
              data['password'] = passwordCtrl.text;
              await ApiService().post(ApiConfig.usersStore, data: data);
            }
            if (!mounted) return;
            Navigator.pop(ctx);
            _loadData();
            ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('✓ User berhasil ${isEdit ? 'diupdate' : 'ditambahkan'}'), backgroundColor: AppTheme.success));
          } catch (e) {
            if (!mounted) return;
            ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e'), backgroundColor: AppTheme.danger));
          }
        }, child: Text(isEdit ? 'Update' : 'Simpan')),
      ],
    ));
  }

  Future<void> _deleteUser(int id) async {
    final confirmed = await showDialog<bool>(context: context, builder: (ctx) => AlertDialog(
      title: Text('Hapus User?', style: GoogleFonts.dmSans(fontWeight: FontWeight.w700)),
      content: const Text('User yang dihapus tidak dapat dikembalikan.'),
      actions: [
        TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
        ElevatedButton(onPressed: () => Navigator.pop(ctx, true), style: ElevatedButton.styleFrom(backgroundColor: AppTheme.danger), child: const Text('Hapus')),
      ],
    ));

    if (confirmed == true) {
      try {
        await ApiService().delete(ApiConfig.userDelete(id).toString());
        if (!mounted) return;
        _loadData();
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('✓ User berhasil dihapus'), backgroundColor: AppTheme.success));
      } catch (e) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e'), backgroundColor: AppTheme.danger));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      drawer: const AppDrawer(currentRoute: '/user-management'),
      appBar: AppBar(title: const Text('Manajemen User')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _showUserDialog(),
        icon: const Icon(Icons.person_add_rounded),
        label: Text('Tambah', style: GoogleFonts.dmSans(fontWeight: FontWeight.w600)),
      ),
      body: Column(
        children: [
          Container(
            margin: const EdgeInsets.all(16),
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: AppTheme.info.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: AppTheme.info.withValues(alpha: 0.3)),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(Icons.security_rounded, color: AppTheme.info, size: 24),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'Informasi: Otentikasi aplikasi ini didukung oleh Firebase Authentication demi keamanan standar industri. Password dienkripsi secara aman dan dikelola otomatis lewat layanan Firebase.',
                    style: GoogleFonts.dmSans(fontSize: 13, color: AppTheme.info, fontWeight: FontWeight.w600),
                  ),
                ),
              ],
            ),
          ),
          Expanded(
            child: _isLoading
                ? ListView(children: List.generate(4, (_) => const Padding(padding: EdgeInsets.all(16), child: ShimmerCard())))
                : _users.isEmpty
                    ? const EmptyState(icon: Icons.people_outline, title: 'Belum ada user')
                    : RefreshIndicator(
                        onRefresh: _loadData,
                        child: ListView.builder(
                          padding: const EdgeInsets.fromLTRB(16, 0, 16, 80),
                          itemCount: _users.length,
                          itemBuilder: (_, index) {
                            final user = _users[index];
                            return Container(
                              margin: const EdgeInsets.only(bottom: 10),
                              padding: const EdgeInsets.all(14),
                              decoration: BoxDecoration(
                                color: isDark ? AppTheme.surfaceCardDark : Colors.white,
                                borderRadius: BorderRadius.circular(14),
                                border: Border.all(color: isDark ? AppTheme.borderDark : AppTheme.borderLight),
                              ),
                              child: Row(children: [
                                CircleAvatar(
                                  radius: 22,
                                  backgroundColor: getRoleColor(user['role'] ?? ''),
                                  child: Text(
                                    (user['nama_lengkap'] ?? 'U').toString().substring(0, 1).toUpperCase(),
                                    style: GoogleFonts.dmSans(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 16),
                                  ),
                                ),
                                const SizedBox(width: 14),
                                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                                  Text(user['nama_lengkap'] ?? '', style: GoogleFonts.dmSans(fontSize: 14, fontWeight: FontWeight.w600)),
                                  Text(user['username'] ?? '', style: GoogleFonts.dmSans(fontSize: 12, color: AppTheme.textMutedDark)),
                                ])),
                                StatusBadge(label: getRoleLabel(user['role'] ?? ''), color: getRoleColor(user['role'] ?? '')),
                                PopupMenuButton<String>(
                                  onSelected: (v) {
                                    if (v == 'edit') _showUserDialog(user: user);
                                    if (v == 'delete') _deleteUser(user['id']);
                                  },
                                  itemBuilder: (_) => [
                                    const PopupMenuItem(value: 'edit', child: Text('Edit')),
                                    const PopupMenuItem(value: 'delete', child: Text('Hapus')),
                                  ],
                                ),
                              ]),
                            );
                          },
                        ),
                      ),
          ),
        ],
      ),
    );
  }
}
