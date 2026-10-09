import '../models/auth_user.dart';
import 'auth_api.dart';
import 'auth_token_store.dart';

class AuthRepository {
  AuthRepository({
    AuthApi? api,
    AuthTokenStore? tokenStore,
  })  : _api = api ?? AuthApi(),
        _tokenStore = tokenStore ?? AuthTokenStore();

  final AuthApi _api;
  final AuthTokenStore _tokenStore;

  Future<AuthUser?> restoreSession() async {
    final token = await _tokenStore.read();
    if (token == null || token.isEmpty) {
      return null;
    }

    try {
      return await _api.me(token);
    } on AuthApiException catch (e) {
      if (e.statusCode == 401 || e.statusCode == 403) {
        await _tokenStore.clear();
        return null;
      }
      rethrow;
    }
  }

  Future<AuthUser> login({
    required String email,
    required String password,
  }) async {
    final result = await _api.login(email: email, password: password);
    await _tokenStore.save(result.token);
    // Prefer fresh /me so we get roles/permissions shape consistently.
    try {
      return await _api.me(result.token);
    } catch (_) {
      return result.user;
    }
  }

  Future<void> logout() async {
    final token = await _tokenStore.read();
    if (token != null && token.isNotEmpty) {
      try {
        await _api.logout(token);
      } catch (_) {
        // Local logout still proceeds.
      }
    }
    await _tokenStore.clear();
  }
}
