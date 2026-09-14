import 'package:flutter/foundation.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'dart:async';
import 'database_service.dart';
import 'api_service.dart';
import '../config/api_config.dart';

/// Manages network connectivity status and background sync.
/// Provides isOnline flag for UI and auto-triggers sync when back online.
class ConnectivityProvider extends ChangeNotifier {
  final Connectivity _connectivity = Connectivity();
  final DatabaseService _db = DatabaseService();
  final ApiService _api = ApiService();

  bool _isOnline = true;
  bool _isSyncing = false;
  int _pendingCount = 0;
  StreamSubscription? _subscription;

  bool get isOnline => _isOnline;
  bool get isSyncing => _isSyncing;
  int get pendingCount => _pendingCount;

  /// Sync status label for UI
  String get syncStatusLabel {
    if (_isSyncing) return 'Menyinkronkan...';
    if (!_isOnline) return 'Offline';
    if (_pendingCount > 0) return '$_pendingCount pending';
    return 'Tersinkron';
  }

  /// Initialize connectivity listener
  Future<void> init() async {
    // Check initial state
    final result = await _connectivity.checkConnectivity();
    _isOnline = !result.contains(ConnectivityResult.none);

    // Update pending count
    _pendingCount = await _db.getPendingCount();

    // Listen for changes
    _subscription = _connectivity.onConnectivityChanged.listen((results) {
      final wasOffline = !_isOnline;
      _isOnline = !results.contains(ConnectivityResult.none);

      if (wasOffline && _isOnline) {
        // Just came back online → trigger sync
        syncPendingTransactions();
        syncPendingPrepaid();
        syncPendingAsetTetap();
        syncPendingPenyesuaian();
      }

      notifyListeners();
    });

    notifyListeners();
  }

  /// Sync all pending offline transactions to server
  Future<void> syncPendingTransactions() async {
    if (_isSyncing || !_isOnline) return;

    _isSyncing = true;
    notifyListeners();

    try {
      final pending = await _db.getPendingTransactions();
      if (pending.isEmpty) {
        _pendingCount = 0;
        _isSyncing = false;
        notifyListeners();
        return;
      }

      final response = await _api.post(
        ApiConfig.syncTransactions,
        data: {'transactions': pending},
      );

      if (response.statusCode == 200 && response.data['status'] == 'success') {
        final results = response.data['data']['results'] as List;
        for (final result in results) {
          if (result['status'] == 'synced' && result['local_id'] != null) {
            await _db.markAsSynced(result['local_id'], result['global_id']);
          }
        }
      }
    } catch (e) {
      debugPrint('Sync error: $e');
    }

    _pendingCount = await _db.getPendingCount();
    _isSyncing = false;
    notifyListeners();
  }

  /// Sync pending prepaid expenses
  Future<void> syncPendingPrepaid() async {
    if (!_isOnline) return;

    try {
      final pending = await _db.getPendingPrepaid();
      if (pending.isEmpty) return;

      for (final item in pending) {
        final response = await _api.post(ApiConfig.asetPrepaid, data: {
          'nama_prepaid': item['nama_prepaid'],
          'total_nilai': item['total_nilai'],
          'lama_bulan': item['lama_bulan'],
          'tanggal_mulai': item['tanggal'],
          'akun_prepaid': item['akun_prepaid'],
          'akun_beban': item['akun_beban'],
        });

        if (response.statusCode == 200 || response.statusCode == 201) {
          await _db.removeOfflinePrepaid(item['local_id']);
        }
      }
    } catch (e) {
      debugPrint('Sync Prepaid error: $e');
    }
    
    _pendingCount = await _db.getPendingCount();
    notifyListeners();
  }

  /// Sync pending aset tetap
  Future<void> syncPendingAsetTetap() async {
    if (!_isOnline) return;

    try {
      final pending = await _db.getPendingAsetTetap();
      if (pending.isEmpty) return;

      for (final item in pending) {
        final response = await _api.post(ApiConfig.asetTetap, data: {
          'nama_aset': item['nama_aset'],
          'harga_perolehan': item['harga_perolehan'],
          'nilai_residu': item['nilai_residu'],
          'umur_tahun': item['umur_tahun'],
          'umur_bulan': item['umur_bulan'],
          'akun_aset': item['akun_aset'],
          'akun_akumulasi': item['akun_akumulasi'],
          'akun_beban': item['akun_beban'],
          // API endpoint uses 'tanggal' logic or defaults to now, assuming it accepts it if needed. 
          // Current API code does not seem to require date, but we can pass it if we want.
        });

        if (response.statusCode == 200 || response.statusCode == 201) {
          await _db.removeOfflineAsetTetap(item['local_id']);
        }
      }
    } catch (e) {
      debugPrint('Sync Aset Tetap error: $e');
    }
    
    _pendingCount = await _db.getPendingCount();
    notifyListeners();
  }

  /// Sync pending jurnal penyesuaian
  Future<void> syncPendingPenyesuaian() async {
    if (!_isOnline) return;

    try {
      final pending = await _db.getPendingPenyesuaian();
      if (pending.isEmpty) return;

      for (final item in pending) {
        final payload = {
          'tanggal': item['tanggal'],
          'deskripsi': item['deskripsi'],
          'jenis_penyesuaian': item['jenis_penyesuaian'],
          'akun_debet': item['akun_debet'],
          'akun_kredit': item['akun_kredit'],
          'nilai': item['nilai'],
          'referensi_id': item['referensi_id'],
          'is_reversing': item['is_reversing'],
        };

        final response = await _api.post(ApiConfig.jurnalPenyesuaian, data: payload);

        if (response.statusCode == 200 || response.statusCode == 201) {
          await _db.removeOfflinePenyesuaian(item['local_id']);
        }
      }
    } catch (e) {
      debugPrint('Sync Penyesuaian error: $e');
    }
    
    _pendingCount = await _db.getPendingCount();
    notifyListeners();
  }

  /// Update pending count (call after offline save)
  Future<void> refreshPendingCount() async {
    _pendingCount = await _db.getPendingCount();
    notifyListeners();
  }

  @override
  void dispose() {
    _subscription?.cancel();
    super.dispose();
  }
}
