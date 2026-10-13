import '../models/auth_user.dart';
import '../settings/app_settings.dart';
import 'auth_api.dart';
import 'auth_token_store.dart';

enum SessionRestoreResultType { none, authenticated, locked, expired }

class SessionRestoreResult {
  const SessionRestoreResult._(this.type, {this.user});

  final SessionRestoreResultType type;
  final AuthUser? user;

  factory SessionRestoreResult.none() =>
      const SessionRestoreResult._(SessionRestoreResultType.none);

  factory SessionRestoreResult.authenticated(AuthUser user) =>
      SessionRestoreResult._(SessionRestoreResultType.authenticated, user: user);

  factory SessionRestoreResult.locked(AuthUser user) =>
      SessionRestoreResult._(SessionRestoreResultType.locked, user: user);

  factory SessionRestoreResult.expired() =>
      const SessionRestoreResult._(SessionRestoreResultType.expired);
}

class AuthRepository {
  AuthRepository({
    AuthApi? api,
    AuthTokenStore? tokenStore,
    AppSettings? settings,
  })  : _api = api ?? AuthApi(),
        _tokenStore = tokenStore ?? AuthTokenStore(),
        _settings = settings ?? AppSettings.instance;

  final AuthApi _api;
  final AuthTokenStore _tokenStore;
  final AppSettings _settings;

  Future<SessionRestoreResult> restoreSession() async {
    final token = await _tokenStore.read();
    if (token == null || token.isEmpty) {
      return SessionRestoreResult.none();
    }

    if (_settings.sessionExpired) {
      await _tokenStore.clear();
      await _settings.clearSessionMeta();
      return SessionRestoreResult.expired();
    }

    try {
      final user = await _api.me(token);
      if (_settings.needsUnlock) {
        return SessionRestoreResult.locked(user);
      }
      return SessionRestoreResult.authenticated(user);
    } on AuthApiException catch (e) {
      if (e.statusCode == 401 || e.statusCode == 403) {
        await _tokenStore.clear();
        await _settings.clearSessionMeta();
        return SessionRestoreResult.none();
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
    await _settings.markSignedIn();
    try {
      return await _api.me(result.token);
    } catch (_) {
      return result.user;
    }
  }

  Future<AuthUser> unlockWithPassword({
    required String email,
    required String password,
  }) async {
    // Re-auth with password without discarding token until success.
    final result = await _api.login(email: email, password: password);
    await _tokenStore.save(result.token);
    await _settings.markSignedIn();
    try {
      return await _api.me(result.token);
    } catch (_) {
      return result.user;
    }
  }

  Future<void> changePassword({
    required String currentPassword,
    required String password,
    required String passwordConfirmation,
  }) async {
    final token = await _tokenStore.read();
    if (token == null || token.isEmpty) {
      throw AuthApiException('Not signed in.');
    }
    await _api.changePassword(
      token: token,
      currentPassword: currentPassword,
      password: password,
      passwordConfirmation: passwordConfirmation,
    );
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
    await _settings.clearSessionMeta();
  }
}
