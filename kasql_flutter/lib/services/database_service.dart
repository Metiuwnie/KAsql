import 'package:sqflite/sqflite.dart';
import 'package:path/path.dart';
import 'dart:convert';

/// SQLite local database for offline-first data storage.
/// Stores cached data and pending offline transactions.
class DatabaseService {
  static final DatabaseService _instance = DatabaseService._internal();
  factory DatabaseService() => _instance;
  DatabaseService._internal();

  Database? _db;

  Future<Database> get database async {
    _db ??= await _initDb();
    return _db!;
  }

  Future<Database> _initDb() async {
    final dbPath = await getDatabasesPath();
    final path = join(dbPath, 'kasql_local.db');

    return await openDatabase(
      path,
      version: 6,
      onCreate: _onCreate,
      onUpgrade: _onUpgrade,
    );
  }

  Future<void> _onCreate(Database db, int version) async {
    // Cached accounts
    await db.execute('''
      CREATE TABLE akun_cache (
        kode_akun TEXT PRIMARY KEY,
        nama_akun TEXT NOT NULL,
        aktiva_pasiva TEXT,
        kategori_neraca TEXT,
        updated_at TEXT
      )
    ''');

    // Cached reaksi (jenis aktivitas)
    await db.execute('''
      CREATE TABLE reaksi_cache (
        id_reaksi TEXT PRIMARY KEY,
        nama_reaksi TEXT NOT NULL,
        details TEXT NOT NULL,
        updated_at TEXT
      )
    ''');

    // Offline transactions (header)
    await db.execute('''
      CREATE TABLE transactions_local (
        local_id INTEGER PRIMARY KEY AUTOINCREMENT,
        global_id TEXT,
        tanggal TEXT NOT NULL,
        deskripsi TEXT NOT NULL,
        sync_status TEXT NOT NULL DEFAULT 'pending_insert',
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL
      )
    ''');

    // Offline transaction details
    await db.execute('''
      CREATE TABLE transaction_details_local (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        local_transaction_id INTEGER NOT NULL,
        kode_akun TEXT NOT NULL,
        dk TEXT NOT NULL,
        nilai REAL NOT NULL,
        FOREIGN KEY (local_transaction_id) REFERENCES transactions_local(local_id)
      )
    ''');

    // App settings cache
    await db.execute('''
      CREATE TABLE settings (
        key TEXT PRIMARY KEY,
        value TEXT
      )
    ''');

    // Generic cache for reports and other dynamic data
    await db.execute('''
      CREATE TABLE generic_cache (
        cache_key TEXT PRIMARY KEY,
        json_data TEXT NOT NULL,
        cached_at TEXT NOT NULL
      )
    ''');

    // Offline prepaid expense
    await db.execute('''
      CREATE TABLE prepaid_offline (
        local_id INTEGER PRIMARY KEY AUTOINCREMENT,
        nama_prepaid TEXT NOT NULL,
        total_nilai REAL NOT NULL,
        lama_bulan INTEGER NOT NULL,
        akun_prepaid TEXT NOT NULL,
        akun_beban TEXT NOT NULL,
        tanggal TEXT NOT NULL,
        sync_status TEXT NOT NULL DEFAULT 'pending_insert',
        created_at TEXT NOT NULL
      )
    ''');

    // Offline aset tetap
    await db.execute('''
      CREATE TABLE aset_tetap_offline (
        local_id INTEGER PRIMARY KEY AUTOINCREMENT,
        nama_aset TEXT NOT NULL,
        harga_perolehan REAL NOT NULL,
        nilai_residu REAL NOT NULL,
        umur_tahun INTEGER NOT NULL,
        umur_bulan INTEGER NOT NULL,
        akun_aset TEXT NOT NULL,
        akun_akumulasi TEXT NOT NULL,
        akun_beban TEXT NOT NULL,
        tanggal TEXT NOT NULL,
        sync_status TEXT NOT NULL DEFAULT 'pending_insert',
        created_at TEXT NOT NULL
      )
    ''');

    // Offline jurnal penyesuaian
    await db.execute('''
      CREATE TABLE penyesuaian_offline (
        local_id INTEGER PRIMARY KEY AUTOINCREMENT,
        tanggal TEXT NOT NULL,
        jenis_penyesuaian TEXT NOT NULL,
        akun_debet TEXT NOT NULL,
        akun_kredit TEXT NOT NULL,
        nilai REAL NOT NULL,
        deskripsi TEXT NOT NULL,
        referensi_id TEXT,
        is_reversing INTEGER NOT NULL DEFAULT 0,
        sync_status TEXT NOT NULL DEFAULT 'pending_insert',
        created_at TEXT NOT NULL
      )
    ''');
  }

  Future<void> _onUpgrade(Database db, int oldVersion, int newVersion) async {
    if (oldVersion < 2) {
      await db.execute('''
        CREATE TABLE reaksi_cache (
          id_reaksi INTEGER PRIMARY KEY,
          nama_reaksi TEXT NOT NULL,
          details TEXT NOT NULL,
          updated_at TEXT
        )
      ''');
    }
    if (oldVersion < 3) {
      await db.execute('''
        CREATE TABLE generic_cache (
          cache_key TEXT PRIMARY KEY,
          json_data TEXT NOT NULL,
          cached_at TEXT NOT NULL
        )
      ''');
    }
    if (oldVersion < 4) {
      await db.execute('DROP TABLE IF EXISTS reaksi_cache');
      await db.execute('''
        CREATE TABLE reaksi_cache (
          id_reaksi TEXT PRIMARY KEY,
          nama_reaksi TEXT NOT NULL,
          details TEXT NOT NULL,
          updated_at TEXT
        )
      ''');
    }
    if (oldVersion < 5) {
      // Offline prepaid expense
      await db.execute('''
        CREATE TABLE prepaid_offline (
          local_id INTEGER PRIMARY KEY AUTOINCREMENT,
          nama_prepaid TEXT NOT NULL,
          total_nilai REAL NOT NULL,
          lama_bulan INTEGER NOT NULL,
          akun_prepaid TEXT NOT NULL,
          akun_beban TEXT NOT NULL,
          tanggal TEXT NOT NULL,
          sync_status TEXT NOT NULL DEFAULT 'pending_insert',
          created_at TEXT NOT NULL
        )
      ''');
  
      // Offline aset tetap
      await db.execute('''
        CREATE TABLE aset_tetap_offline (
          local_id INTEGER PRIMARY KEY AUTOINCREMENT,
          nama_aset TEXT NOT NULL,
          harga_perolehan REAL NOT NULL,
          nilai_residu REAL NOT NULL,
          umur_tahun INTEGER NOT NULL,
          umur_bulan INTEGER NOT NULL,
          akun_aset TEXT NOT NULL,
          akun_akumulasi TEXT NOT NULL,
          akun_beban TEXT NOT NULL,
          tanggal TEXT NOT NULL,
          sync_status TEXT NOT NULL DEFAULT 'pending_insert',
          created_at TEXT NOT NULL
        )
      ''');
    }
    if (oldVersion < 6) {
      // Offline jurnal penyesuaian
      await db.execute('''
        CREATE TABLE penyesuaian_offline (
          local_id INTEGER PRIMARY KEY AUTOINCREMENT,
          tanggal TEXT NOT NULL,
          jenis_penyesuaian TEXT NOT NULL,
          akun_debet TEXT NOT NULL,
          akun_kredit TEXT NOT NULL,
          nilai REAL NOT NULL,
          deskripsi TEXT NOT NULL,
          referensi_id TEXT,
          is_reversing INTEGER NOT NULL DEFAULT 0,
          sync_status TEXT NOT NULL DEFAULT 'pending_insert',
          created_at TEXT NOT NULL
        )
      ''');
    }
  }

  // ───── Akun Cache ─────

  Future<void> cacheAkunList(List<Map<String, dynamic>> akunList) async {
    final db = await database;
    final batch = db.batch();
    batch.delete('akun_cache');
    for (final akun in akunList) {
      batch.insert('akun_cache', {
        'kode_akun': akun['kode_akun'],
        'nama_akun': akun['nama_akun'],
        'aktiva_pasiva': akun['aktiva_pasiva'],
        'kategori_neraca': akun['kategori_neraca'],
        'updated_at': DateTime.now().toIso8601String(),
      }, conflictAlgorithm: ConflictAlgorithm.replace);
    }
    await batch.commit(noResult: true);
  }

  Future<List<Map<String, dynamic>>> getCachedAkun() async {
    final db = await database;
    return await db.query('akun_cache', orderBy: 'kode_akun ASC');
  }

  // ───── Reaksi Cache ─────

  Future<void> cacheReaksiList(List<Map<String, dynamic>> reaksiList) async {
    final db = await database;
    final batch = db.batch();
    batch.delete('reaksi_cache');
    for (final r in reaksiList) {
      batch.insert('reaksi_cache', {
        'id_reaksi': r['id_reaksi'],
        'nama_reaksi': r['nama_reaksi'],
        'details': r['details'] != null ? jsonEncode(r['details']) : '[]',
        'updated_at': DateTime.now().toIso8601String(),
      }, conflictAlgorithm: ConflictAlgorithm.replace);
    }
    await batch.commit(noResult: true);
  }

  Future<List<Map<String, dynamic>>> getCachedReaksi() async {
    final db = await database;
    final rows = await db.query('reaksi_cache', orderBy: 'nama_reaksi ASC');
    // decoding will happen in the screen, or I can do it here. 
    return rows;
  }

  // ───── Offline Transactions ─────

  Future<int> insertOfflineTransaction(String tanggal, String deskripsi, List<Map<String, dynamic>> details) async {
    final db = await database;
    final now = DateTime.now().toIso8601String();

    final localId = await db.insert('transactions_local', {
      'tanggal': tanggal,
      'deskripsi': deskripsi,
      'sync_status': 'pending_insert',
      'created_at': now,
      'updated_at': now,
    });

    for (final detail in details) {
      await db.insert('transaction_details_local', {
        'local_transaction_id': localId,
        'kode_akun': detail['kode_akun'],
        'dk': detail['dk'],
        'nilai': detail['nilai'],
      });
    }

    return localId;
  }

  Future<List<Map<String, dynamic>>> getPendingTransactions() async {
    final db = await database;
    final headers = await db.query(
      'transactions_local',
      where: "sync_status = ?",
      whereArgs: ['pending_insert'],
      orderBy: 'created_at ASC',
    );

    List<Map<String, dynamic>> result = [];
    for (final header in headers) {
      final details = await db.query(
        'transaction_details_local',
        where: 'local_transaction_id = ?',
        whereArgs: [header['local_id']],
      );

      result.add({
        'local_id': header['local_id'],
        'tanggal': header['tanggal'],
        'deskripsi': header['deskripsi'],
        'created_at': header['created_at'],
        'details': details.map((d) => {
          'kode_akun': d['kode_akun'],
          'dk': d['dk'],
          'nilai': d['nilai'],
        }).toList(),
      });
    }

    return result;
  }

  Future<void> markAsSynced(int localId, String globalId) async {
    final db = await database;
    await db.update(
      'transactions_local',
      {
        'global_id': globalId,
        'sync_status': 'synced',
        'updated_at': DateTime.now().toIso8601String(),
      },
      where: 'local_id = ?',
      whereArgs: [localId],
    );
  }

  Future<int> getPendingCount() async {
    final db = await database;
    final resTx = await db.rawQuery("SELECT COUNT(*) as cnt FROM transactions_local WHERE sync_status = 'pending_insert'");
    final countTx = resTx.first['cnt'] as int? ?? 0;

    final resPrepaid = await db.rawQuery("SELECT COUNT(*) as cnt FROM prepaid_offline WHERE sync_status = 'pending_insert'");
    final countPrepaid = resPrepaid.first['cnt'] as int? ?? 0;

    final resAset = await db.rawQuery("SELECT COUNT(*) as cnt FROM aset_tetap_offline WHERE sync_status = 'pending_insert'");
    final countAset = resAset.first['cnt'] as int? ?? 0;

    final resPenyesuaian = await db.rawQuery("SELECT COUNT(*) as cnt FROM penyesuaian_offline WHERE sync_status = 'pending_insert'");
    final countPenyesuaian = resPenyesuaian.first['cnt'] as int? ?? 0;

    return countTx + countPrepaid + countAset + countPenyesuaian;
  }

  // ───── Offline Prepaid ─────

  Future<int> insertOfflinePrepaid(Map<String, dynamic> data) async {
    final db = await database;
    data['sync_status'] = 'pending_insert';
    data['created_at'] = DateTime.now().toIso8601String();
    return await db.insert('prepaid_offline', data);
  }

  Future<List<Map<String, dynamic>>> getPendingPrepaid() async {
    final db = await database;
    return await db.query(
      'prepaid_offline',
      where: "sync_status = ?",
      whereArgs: ['pending_insert'],
      orderBy: 'created_at ASC',
    );
  }

  Future<void> removeOfflinePrepaid(int localId) async {
    final db = await database;
    await db.delete('prepaid_offline', where: 'local_id = ?', whereArgs: [localId]);
  }

  // ───── Offline Aset Tetap ─────

  Future<int> insertOfflineAsetTetap(Map<String, dynamic> data) async {
    final db = await database;
    data['sync_status'] = 'pending_insert';
    data['created_at'] = DateTime.now().toIso8601String();
    return await db.insert('aset_tetap_offline', data);
  }

  Future<List<Map<String, dynamic>>> getPendingAsetTetap() async {
    final db = await database;
    return await db.query(
      'aset_tetap_offline',
      where: "sync_status = ?",
      whereArgs: ['pending_insert'],
      orderBy: 'created_at ASC',
    );
  }

  Future<void> removeOfflineAsetTetap(int localId) async {
    final db = await database;
    await db.delete('aset_tetap_offline', where: 'local_id = ?', whereArgs: [localId]);
  }

  // ───── Offline Jurnal Penyesuaian ─────

  Future<int> insertOfflinePenyesuaian(Map<String, dynamic> data) async {
    final db = await database;
    data['sync_status'] = 'pending_insert';
    data['created_at'] = DateTime.now().toIso8601String();
    return await db.insert('penyesuaian_offline', data);
  }

  Future<List<Map<String, dynamic>>> getPendingPenyesuaian() async {
    final db = await database;
    return await db.query(
      'penyesuaian_offline',
      where: "sync_status = ?",
      whereArgs: ['pending_insert'],
      orderBy: 'created_at ASC',
    );
  }

  Future<void> removeOfflinePenyesuaian(int localId) async {
    final db = await database;
    await db.delete('penyesuaian_offline', where: 'local_id = ?', whereArgs: [localId]);
  }

  // ───── Generic Cache ─────

  Future<void> cacheGenericData(String key, dynamic data) async {
    final db = await database;
    await db.insert(
      'generic_cache',
      {
        'cache_key': key,
        'json_data': jsonEncode(data),
        'cached_at': DateTime.now().toIso8601String(),
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  Future<dynamic> getCachedGenericData(String key) async {
    final db = await database;
    final result = await db.query(
      'generic_cache',
      where: 'cache_key = ?',
      whereArgs: [key],
    );
    
    if (result.isNotEmpty) {
      final String jsonStr = result.first['json_data'] as String;
      return jsonDecode(jsonStr);
    }
    return null;
  }

  // ───── Settings ─────

  Future<void> setSetting(String key, String value) async {
    final db = await database;
    await db.insert('settings', {'key': key, 'value': value},
        conflictAlgorithm: ConflictAlgorithm.replace);
  }

  Future<String?> getSetting(String key) async {
    final db = await database;
    final result = await db.query('settings', where: 'key = ?', whereArgs: [key]);
    if (result.isNotEmpty) return result.first['value'] as String?;
    return null;
  }

  /// Clear all cached data (for logout/reset)
  Future<void> clearAll() async {
    final db = await database;
    await db.delete('akun_cache');
    await db.delete('reaksi_cache');
    await db.delete('generic_cache');
    await db.delete('transaction_details_local');
    await db.delete('transactions_local');
    await db.delete('settings');
  }
}
