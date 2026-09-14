import 'package:flutter/foundation.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'api_service.dart';
import '../config/api_config.dart';

/// Authentication service with Firebase Auth + PHP backend role sync.
/// Flow: Firebase login → Exchange UID with PHP → Get role + API token.
class AuthService extends ChangeNotifier {
  final FirebaseAuth _firebaseAuth = FirebaseAuth.instance;
  final FlutterSecureStorage _storage = const FlutterSecureStorage();
  final ApiService _api = ApiService();

  AuthService() {
    _api.onTokenExpired = () {
      _clearSession();
      notifyListeners();
    };
  }

  Map<String, dynamic>? _currentUser;
  String? _companyName;
  bool _isLoading = false;

  // Getters
  Map<String, dynamic>? get currentUser => _currentUser;
  String? get companyName => _companyName;
  bool get isLoading => _isLoading;
  bool get isLoggedIn => _currentUser != null;
  String get userRole => _currentUser?['role'] ?? '';
  String get userName => _currentUser?['nama_lengkap'] ?? 'User';
  String get userUsername => _currentUser?['username'] ?? '';
  int get userId => _currentUser?['id'] ?? 0;

  bool get isAdmin => userRole == 'admin';
  bool get isAccountant => userRole == 'accountant';
  bool get isCashier => userRole == 'cashier';
  bool get canViewReports => isAdmin || isAccountant;

  /// Initialize — Check if user is already logged in
  Future<void> init() async {
    _isLoading = true;
    notifyListeners();

    try {
      final hasToken = await _api.hasToken();
      if (hasToken) {
        // Verify token is still valid
        final response = await _api.get(ApiConfig.me);
        if (response.statusCode == 200 && response.data['status'] == 'success') {
          _currentUser = response.data['data']['user'];
          _companyName = await _storage.read(key: 'company_name');
        } else {
          await _clearSession();
        }
      }
    } catch (e) {
      // Token invalid or network error — try loading from local storage
      _currentUser = await _loadLocalUser();
      _companyName = await _storage.read(key: 'company_name');
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<void> updateCompanyName(String newName) async {
    _companyName = newName;
    await _storage.write(key: 'company_name', value: newName);
    notifyListeners();
  }

  /// Login with Firebase (Email/Password)
  Future<Map<String, dynamic>> loginWithFirebase(String email, String password) async {
    _isLoading = true;
    notifyListeners();

    try {
      // 1. Firebase Auth (Appends a dummy domain if username is provided instead of email)
      String loginEmail = email;
      if (!loginEmail.contains('@')) {
        loginEmail = '$loginEmail@kasql.local';
      }

      final credential = await _firebaseAuth.signInWithEmailAndPassword(
        email: loginEmail,
        password: password,
      );

      final firebaseUser = credential.user;
      if (firebaseUser == null) {
        throw Exception('Firebase login gagal.');
      }

      // 2. Exchange Firebase UID with PHP backend
      final response = await _api.post(
        ApiConfig.firebaseLogin,
        data: {
          'firebase_uid': firebaseUser.uid,
          'email': firebaseUser.email,
          'display_name': firebaseUser.displayName ?? '',
        },
      );

      if (response.statusCode == 200 && response.data['status'] == 'success') {
        final data = response.data['data'];
        _currentUser = data['user'];
        _companyName = data['company_name'];

        // Save token & user locally
        await _api.saveToken(data['token']);
        await _saveLocalUser(data['user']);
        await _storage.write(key: 'company_name', value: _companyName ?? 'USAHA');

        _isLoading = false;
        notifyListeners();
        return {'success': true};
      } else {
        throw Exception(response.data['message'] ?? 'Login gagal.');
      }
    } on FirebaseAuthException catch (e) {
      _isLoading = false;
      notifyListeners();
      return {'success': false, 'message': _getFirebaseErrorMessage(e.code)};
    } catch (e) {
      _isLoading = false;
      notifyListeners();
      return {'success': false, 'message': e.toString()};
    }
  }

  /// Login with username/password (dev fallback without Firebase)
  Future<Map<String, dynamic>> loginWithPassword(String username, String password) async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await _api.post(
        ApiConfig.login,
        data: {'username': username, 'password': password},
      );

      if (response.statusCode == 200 && response.data['status'] == 'success') {
        final data = response.data['data'];
        _currentUser = data['user'];
        _companyName = data['company_name'];

        await _api.saveToken(data['token']);
        await _saveLocalUser(data['user']);
        await _storage.write(key: 'company_name', value: _companyName ?? 'USAHA');

        _isLoading = false;
        notifyListeners();
        return {'success': true};
      } else {
        throw Exception(response.data['message'] ?? 'Login gagal.');
      }
    } catch (e) {
      _isLoading = false;
      notifyListeners();

      String message = 'Terjadi kesalahan. Periksa koneksi internet.';
      if (e is Exception) {
        message = e.toString().replaceAll('Exception: ', '');
      }
      return {'success': false, 'message': message};
    }
  }

  /// Logout
  Future<void> logout() async {
    try {
      await _firebaseAuth.signOut();
    } catch (_) {}
    await _clearSession();
    notifyListeners();
  }

  // ───── Private Helpers ─────

  Future<void> _saveLocalUser(Map<String, dynamic> user) async {
    await _storage.write(key: 'user_id', value: user['id'].toString());
    await _storage.write(key: 'user_role', value: user['role'] ?? '');
    await _storage.write(key: 'user_name', value: user['nama_lengkap'] ?? '');
    await _storage.write(key: 'user_username', value: user['username'] ?? '');
  }

  Future<Map<String, dynamic>?> _loadLocalUser() async {
    final id = await _storage.read(key: 'user_id');
    if (id == null) return null;
    return {
      'id': int.tryParse(id) ?? 0,
      'role': await _storage.read(key: 'user_role') ?? '',
      'nama_lengkap': await _storage.read(key: 'user_name') ?? '',
      'username': await _storage.read(key: 'user_username') ?? '',
    };
  }

  Future<void> _clearSession() async {
    _currentUser = null;
    _companyName = null;
    await _api.clearToken();
    await _storage.deleteAll();
  }

  String _getFirebaseErrorMessage(String code) {
    switch (code) {
      case 'user-not-found':
        return 'Akun tidak ditemukan.';
      case 'wrong-password':
        return 'Password salah.';
      case 'invalid-email':
        return 'Format email tidak valid.';
      case 'user-disabled':
        return 'Akun dinonaktifkan.';
      case 'too-many-requests':
        return 'Terlalu banyak percobaan. Coba lagi nanti.';
      case 'invalid-credential':
        return 'Email atau password salah.';
      default:
        return 'Error: $code';
    }
  }
}
