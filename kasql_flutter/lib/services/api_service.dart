import 'package:flutter/foundation.dart';
import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../config/api_config.dart';

/// Core HTTP client using Dio with JWT interceptor.
/// Handles token attachment, 401 redirects, and base error handling.
class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;

  late Dio dio;
  final FlutterSecureStorage _storage = const FlutterSecureStorage();
  String? _cachedToken;
  VoidCallback? onTokenExpired;

  ApiService._internal() {
    dio = Dio(BaseOptions(
      baseUrl: ApiConfig.baseUrl,
      connectTimeout: const Duration(seconds: 60),
      receiveTimeout: const Duration(seconds: 60),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    ));

    // Add JWT interceptor
    dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        _cachedToken ??= await _storage.read(key: 'auth_token');
        if (_cachedToken != null) {
          options.headers['Authorization'] = 'Bearer $_cachedToken';
        }
        return handler.next(options);
      },
      onError: (error, handler) {
        if (error.response?.statusCode == 401) {
          // Token expired — trigger logout
          _cachedToken = null;
          _storage.delete(key: 'auth_token');
          if (onTokenExpired != null) {
            onTokenExpired!();
          }
        }
        return handler.next(error);
      },
    ));
  }

  /// Save token after login
  Future<void> saveToken(String token) async {
    _cachedToken = token;
    await _storage.write(key: 'auth_token', value: token);
  }

  /// Clear token on logout
  Future<void> clearToken() async {
    _cachedToken = null;
    await _storage.delete(key: 'auth_token');
  }

  /// Check if token exists
  Future<bool> hasToken() async {
    final token = await _storage.read(key: 'auth_token');
    return token != null && token.isNotEmpty;
  }

  /// GET request
  Future<Response> get(String url, {Map<String, dynamic>? queryParams}) async {
    return await dio.get(url, queryParameters: queryParams);
  }

  /// POST request
  Future<Response> post(String url, {dynamic data}) async {
    return await dio.post(url, data: data);
  }

  /// PUT request
  Future<Response> put(String url, {dynamic data}) async {
    return await dio.put(url, data: data);
  }

  /// DELETE request
  Future<Response> delete(String url) async {
    return await dio.delete(url);
  }
}
