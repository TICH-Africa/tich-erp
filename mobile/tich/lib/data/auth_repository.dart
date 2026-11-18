import '../models/auth_user.dart';
import '../settings/app_settings.dart';
import 'auth_api.dart';
import 'auth_token_store.dart';

enum SessionRestoreResultType { none, authenticated, locked, expired, mobileOtp }

class SessionRestoreResult {
  const SessionRestoreResult._(this.type, {this.user, this.otpExpiresMinutes});

  final SessionRestoreResultType type;
  final AuthUser? user;
  final int? otpExpiresMinutes;

  factory SessionRestoreResult.none() =>
      const SessionRestoreResult._(SessionRestoreResultType.none);

  factory SessionRestoreResult.authenticated(AuthUser user) =>
      SessionRestoreResult._(SessionRestoreResultType.authenticated, user: user);

  factory SessionRestoreResult.locked(AuthUser user) =>
      SessionRestoreResult._(SessionRestoreResultType.locked, user: user);

  factory SessionRestoreResult.expired() =>
      const SessionRestoreResult._(SessionRestoreResultType.expired);

  factory SessionRestoreResult.mobileOtp(AuthUser user, {int expiresMinutes = 15}) =>
      SessionRestoreResult._(
        SessionRestoreResultType.mobileOtp,
        user: user,
        otpExpiresMinutes: expiresMinutes,
      );
}

class LoginSession {
  const LoginSession({
    required this.user,
    required this.mobileOtpRequired,
    this.emailMasked,
    this.otpExpiresMinutes = 15,
    this.otpSent = true,
    this.message,
  });

  final AuthUser user;
  final bool mobileOtpRequired;
  final String? emailMasked;
  final int otpExpiresMinutes;
  final bool otpSent;
  final String? message;
}

class AuthRepository {
  AuthRepository({
    AuthApi? api,
    AuthTokenStore? tokenStore,
    AppSettings? settings,
    this.onTokenChanged,
  })  : _api = api ?? AuthApi(),
        _tokenStore = tokenStore ?? AuthTokenStore(),
        _settings = settings ?? AppSettings.instance;

  final AuthApi _api;
  final AuthTokenStore _tokenStore;
  final AppSettings _settings;
  final void Function(String? token)? onTokenChanged;

  Future<void> _persistToken(String token) async {
    await _tokenStore.save(token);
    onTokenChanged?.call(token);
  }

  Future<void> _clearToken() async {
    await _tokenStore.clear();
    onTokenChanged?.call(null);
  }

  Future<SessionRestoreResult> restoreSession() async {
    final token = await _tokenStore.read();
    if (token == null || token.isEmpty) {
      return SessionRestoreResult.none();
    }

    if (_settings.sessionExpired) {
      await _clearToken();
      await _settings.clearSessionMeta();
      return SessionRestoreResult.expired();
    }

    onTokenChanged?.call(token);

    try {
      final me = await _api.me(token);
      if (me.mobileOtpRequired) {
        return SessionRestoreResult.mobileOtp(me.user);
      }
      if (_settings.needsUnlock) {
        return SessionRestoreResult.locked(me.user);
      }
      return SessionRestoreResult.authenticated(me.user);
    } on AuthApiException catch (e) {
      if (e.statusCode == 401 || e.statusCode == 403) {
        await _clearToken();
        await _settings.clearSessionMeta();
        return SessionRestoreResult.none();
      }
      rethrow;
    }
  }

  Future<LoginSession> login({
    required String email,
    required String password,
  }) async {
    final result = await _api.login(email: email, password: password);
    await _persistToken(result.token);
    if (!result.mobileOtpRequired) {
      await _settings.markSignedIn();
    }
    try {
      final me = await _api.me(result.token);
      final user = me.user;
      final otpRequired = result.mobileOtpRequired || me.mobileOtpRequired;
      return LoginSession(
        user: user,
        mobileOtpRequired: otpRequired,
        emailMasked: result.emailMasked ?? _maskEmail(user.email),
        otpExpiresMinutes: result.otpExpiresMinutes,
        otpSent: result.otpSent,
        message: result.message,
      );
    } catch (_) {
      return LoginSession(
        user: result.user,
        mobileOtpRequired: result.mobileOtpRequired,
        emailMasked: result.emailMasked ?? _maskEmail(result.user.email),
        otpExpiresMinutes: result.otpExpiresMinutes,
        otpSent: result.otpSent,
        message: result.message,
      );
    }
  }

  Future<AuthUser> verifyMobileOtp(String code) async {
    final token = await _tokenStore.read();
    if (token == null || token.isEmpty) {
      throw AuthApiException('Not signed in.');
    }
    await _api.verifyMobileOtp(token: token, code: code);
    await _settings.markSignedIn();
    final me = await _api.me(token);
    return me.user;
  }

  Future<int> resendMobileOtp() async {
    final token = await _tokenStore.read();
    if (token == null || token.isEmpty) {
      throw AuthApiException('Not signed in.');
    }
    return _api.resendMobileOtp(token);
  }

  Future<LoginSession> unlockWithPassword({
    required String email,
    required String password,
  }) async {
    return login(email: email, password: password);
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
    await _clearToken();
    await _settings.clearSessionMeta();
  }

  static String _maskEmail(String email) {
    final parts = email.split('@');
    if (parts.length != 2) return email;
    final local = parts[0];
    final visible = local.length <= 2 ? local : local.substring(0, 2);
    final masked = '$visible${'•' * (local.length - visible.length).clamp(1, 8)}';
    return '$masked@${parts[1]}';
  }
}