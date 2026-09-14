/// KAsql API Configuration
/// Adjust baseUrl based on your environment.

class ApiConfig {
  // For Android emulator use 10.0.2.2
  // For physical device use your computer's local IP (e.g., 192.168.1.x)
  // For iOS simulator use localhost
  static const String baseUrl = 'http://10.0.2.2/KAsql/api/v1';

  // Auth
  static String get login => '$baseUrl/auth/login';
  static String get firebaseLogin => '$baseUrl/auth/firebase-login';
  static String get me => '$baseUrl/auth/me';

  // Dashboard
  static String get dashboard => '$baseUrl/dashboard';

  // Transaksi
  static String get transaksi => '$baseUrl/transaksi';
  static String transaksiShow(String kode) => '$baseUrl/transaksi/show/$kode';
  static String get transaksiStore => '$baseUrl/transaksi/store';
  static String get transaksiPending => '$baseUrl/transaksi/pending';
  static String get transaksiVerify => '$baseUrl/transaksi/verify';
  static String get transaksiReaksi => '$baseUrl/transaksi/reaksi';

  // Laporan
  static String get jurnalUmum => '$baseUrl/laporan/jurnal_umum';
  static String get bukuBesar => '$baseUrl/laporan/buku_besar';
  static String get neracaSaldo => '$baseUrl/laporan/neraca_saldo';
  static String get labaRugi => '$baseUrl/laporan/laba_rugi';
  static String get neraca => '$baseUrl/laporan/neraca';
  static String get akunList => '$baseUrl/laporan/akun_list';

  // Master
  static String get masterAkun => '$baseUrl/master/akun';
  static String masterAkunDetail(String kode) => '$baseUrl/master/akun_detail/$kode';
  static String get masterReaksi => '$baseUrl/master/reaksi';
  static String masterReaksiDelete(String id) => '$baseUrl/master/reaksi/$id';
  static String get masterDetailReaksi => '$baseUrl/master/detail_reaksi';
  static String masterDetailReaksiDelete(String id) => '$baseUrl/master/detail_reaksi/$id';

  // Jurnal
  static String get jurnalPenyesuaian => '$baseUrl/jurnal/penyesuaian';
  static String get jurnalPembalikPending => '$baseUrl/jurnal/pembalik_pending';
  static String get jurnalPembalikDone => '$baseUrl/jurnal/pembalik_done';
  static String get jurnalPembalikProses => '$baseUrl/jurnal/pembalik_proses';

  // Closing
  static String get closing => '$baseUrl/closing';
  static String get closingProcess => '$baseUrl/closing/close';

  // Aset
  static String get asetPrepaid => '$baseUrl/aset/prepaid';
  static String get asetTetap => '$baseUrl/aset/tetap';

  // Users (Admin)
  static String get users => '$baseUrl/users';
  static String get usersStore => '$baseUrl/users/store';
  static String userUpdate(int id) => '$baseUrl/users/update/$id';
  static String userDelete(int id) => '$baseUrl/users/delete/$id';

  // Analisis AI
  static String get analisisJurnal => '$baseUrl/analisis/jurnal';
  static String get analisisLabaRugi => '$baseUrl/analisis/laba_rugi';
  static String get analisisNeraca => '$baseUrl/analisis/neraca';

  // Settings
  static String get settingsUpdate => '$baseUrl/settings/update';

  // Sync
  static String get syncTransactions => '$baseUrl/sync/transactions';
}
