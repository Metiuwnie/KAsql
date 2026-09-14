import 'package:flutter/material.dart';
import '../screens/login_screen.dart';
import '../screens/dashboard_screen.dart';
import '../screens/transaksi_screen.dart';
import '../screens/transaksi_form_screen.dart';
import '../screens/prepaid_form_screen.dart';
import '../screens/aset_tetap_form_screen.dart';
import '../screens/transaksi_pending_screen.dart';
import '../screens/jurnal_screen.dart';
import '../screens/jurnal_penyesuaian_screen.dart';
import '../screens/jurnal_pembalik_screen.dart';
import '../screens/laporan_screen.dart';
import '../screens/master_akun_screen.dart';
import '../screens/detail_reaksi_screen.dart';
import '../screens/closing_screen.dart';
import '../screens/user_management_screen.dart';
import '../screens/settings_screen.dart';
import '../screens/analisis_screen.dart';

/// Named route definitions with RBAC guard metadata.
class AppRoutes {
  static const String login = '/login';
  static const String dashboard = '/dashboard';
  static const String transaksi = '/transaksi';
  static const String transaksiForm = '/transaksi-form';
  static const String transaksiPending = '/transaksi-pending';
  static const String jurnal = '/jurnal';
  static const String jurnalPenyesuaian = '/jurnal-penyesuaian';
  static const String jurnalPembalik = '/jurnal-pembalik';
  static const String laporan = '/laporan';
  static const String masterAkun = '/master-akun';
  static const String detailReaksi = '/detail-reaksi';
  static const String closing = '/closing';
  static const String userManagement = '/user-management';
  static const String settings = '/settings';
  static const String analisis = '/analisis';

  static const String prepaidForm = '/prepaid-form';
  static const String asetTetapForm = '/aset-tetap-form';

  static Route<dynamic> generateRoute(RouteSettings routeSettings) {
    switch (routeSettings.name) {
      case login:
        return _fade(const LoginScreen());
      case dashboard:
        return _fade(const DashboardScreen());
      case transaksi:
        return _slide(const TransaksiScreen());
      case transaksiForm:
        final args = routeSettings.arguments as Map<String, dynamic>?;
        return _slide(TransaksiFormScreen(
          isKoreksi: args?['is_koreksi'] ?? false,
          koreksiKode: args?['koreksi_kode'],
        ));
      case prepaidForm:
        return _slide(const PrepaidFormScreen());
      case asetTetapForm:
        return _slide(const AsetTetapFormScreen());
      case transaksiPending:
        return _slide(const TransaksiPendingScreen());
      case jurnal:
        return _slide(const JurnalScreen());
      case jurnalPenyesuaian:
        return _slide(const JurnalPenyesuaianScreen());
      case jurnalPembalik:
        return _slide(const JurnalPembalikScreen());
      case laporan:
        final initialTab = routeSettings.arguments as int? ?? 0;
        return _slide(LaporanScreen(initialTab: initialTab));
      case masterAkun:
        return _slide(const MasterAkunScreen());
      case detailReaksi:
        return _slide(const DetailReaksiScreen());
      case closing:
        return _slide(const ClosingScreen());
      case userManagement:
        return _slide(const UserManagementScreen());
      case settings:
        return _slide(const SettingsScreen());
      case analisis:
        return _slide(const AnalisisScreen());
      default:
        return _fade(const DashboardScreen());
    }
  }

  /// Fade transition
  static PageRouteBuilder _fade(Widget page) {
    return PageRouteBuilder(
      pageBuilder: (_, __, ___) => page,
      transitionsBuilder: (_, animation, __, child) {
        return FadeTransition(opacity: animation, child: child);
      },
      transitionDuration: const Duration(milliseconds: 250),
    );
  }

  /// Slide from right transition
  static PageRouteBuilder _slide(Widget page) {
    return PageRouteBuilder(
      pageBuilder: (_, __, ___) => page,
      transitionsBuilder: (_, animation, __, child) {
        const begin = Offset(0.05, 0.0);
        const end = Offset.zero;
        final tween = Tween(begin: begin, end: end).chain(CurveTween(curve: Curves.easeOut));
        return SlideTransition(position: animation.drive(tween), child: FadeTransition(opacity: animation, child: child));
      },
      transitionDuration: const Duration(milliseconds: 250),
    );
  }
}
