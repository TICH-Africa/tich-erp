import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

class AuthTokenStore {
  AuthTokenStore({FlutterSecureStorage? storage})
      : _storage = storage ??
            const FlutterSecureStorage(
              aOptions: AndroidOptions(encryptedSharedPreferences: true),
            );

  static const _key = 'tich_api_token';
  static const _prefsKey = 'tich_api_token_mirror';

  final FlutterSecureStorage _storage;

  Future<void> save(String token) async {
    await _storage.write(key: _key, value: token);
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_prefsKey, token);
  }

  Future<String?> read() async {
    try {
      final secure = await _storage.read(key: _key);
      if (secure != null && secure.isNotEmpty) return secure;
    } catch (_) {}
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_prefsKey);
  }

  Future<void> clear() async {
    try {
      await _storage.delete(key: _key);
    } catch (_) {}
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_prefsKey);
  }
}
