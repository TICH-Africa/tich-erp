import 'package:flutter_bloc/flutter_bloc.dart';

import '../../config/api_config.dart';
import '../../data/auth_api.dart';
import '../../data/auth_repository.dart';
import '../../data/tich_screen_cache.dart';
import '../../models/auth_user.dart';
import '../../settings/app_settings.dart';
import '../../settings/biometric_service.dart';
import 'auth_event.dart';
import 'auth_state.dart';

class AuthBloc extends Bloc<AuthEvent, AuthState> {
  AuthBloc({
    required AuthRepository repository,
    AppSettings? settings,
    BiometricService? biometrics,
  })  : _repository = repository,
        _settings = settings ?? AppSettings.instance,
        _biometrics = biometrics ?? BiometricService(),
        super(const AuthInitial()) {
    on<AuthStarted>(_onStarted);
    on<AuthLoginSubmitted>(_onLogin);
    on<AuthBiometricUnlockRequested>(_onBiometricUnlock);
    on<AuthPasswordUnlockSubmitted>(_onPasswordUnlock);
    on<AuthAppPaused>(_onAppPaused);
    on<AuthLogoutRequested>(_onLogout);
  }

  final AuthRepository _repository;
  final AppSettings _settings;
  final BiometricService _biometrics;

  Future<void> _onStarted(
    AuthStarted event,
    Emitter<AuthState> emit,
  ) async {
    emit(const AuthChecking());
    try {
      final result = await _repository.restoreSession();
      switch (result.type) {
        case SessionRestoreResultType.none:
          emit(const AuthUnauthenticated());
        case SessionRestoreResultType.expired:
          emit(const AuthUnauthenticated(
            message: 'Your 7-day session expired. Please sign in again.',
          ));
        case SessionRestoreResultType.locked:
          emit(AuthLocked(result.user!));
        case SessionRestoreResultType.authenticated:
          await _bindCacheForUser(result.user!);
          emit(AuthAuthenticated(result.user!));
      }
    } catch (e) {
      emit(AuthUnauthenticated(
        message:
            'Could not restore session. Check API_BASE_URL and that Laravel is serving.\n$e',
      ));
    }
  }

  Future<void> _onLogin(
    AuthLoginSubmitted event,
    Emitter<AuthState> emit,
  ) async {
    emit(const AuthLoading());
    try {
      final user = await _repository.login(
        email: event.email,
        password: event.password,
      );
      await _bindCacheForUser(user);
      emit(AuthAuthenticated(user));
    } on AuthApiException catch (e) {
      emit(AuthUnauthenticated(message: e.message));
    } catch (e) {
      emit(AuthUnauthenticated(
        message:
            'Could not reach the API at ${ApiConfig.baseUrl}. Check your network connection or configure API_BASE_URL for your environment.\n$e',
      ));
    }
  }

  Future<void> _bindCacheForUser(AuthUser user) async {
    await TichScreenCache.bindUser(user.id);
  }

  Future<void> _onBiometricUnlock(
    AuthBiometricUnlockRequested event,
    Emitter<AuthState> emit,
  ) async {
    final current = state;
    if (current is! AuthLocked) return;

    final error = await _biometrics.authenticate(reason: 'Unlock TICH');
    if (error == null) {
      await _settings.clearNeedsUnlock();
      await _bindCacheForUser(current.user);
      emit(AuthAuthenticated(current.user));
    } else {
      emit(AuthLocked(current.user, message: error));
    }
  }

  Future<void> _onPasswordUnlock(
    AuthPasswordUnlockSubmitted event,
    Emitter<AuthState> emit,
  ) async {
    final current = state;
    if (current is! AuthLocked) return;

    emit(const AuthLoading());
    try {
      final user = await _repository.unlockWithPassword(
        email: event.email,
        password: event.password,
      );
      await _bindCacheForUser(user);
      emit(AuthAuthenticated(user));
    } on AuthApiException catch (e) {
      emit(AuthLocked(current.user, message: e.message));
    } catch (e) {
      emit(AuthLocked(current.user, message: e.toString()));
    }
  }

  Future<void> _onAppPaused(
    AuthAppPaused event,
    Emitter<AuthState> emit,
  ) async {
    if (state is! AuthAuthenticated) return;
    // WhatsApp-style: lock when leaving if "lock immediately" or biometric unlock is on.
    if (_settings.lockOnExit || _settings.biometricEnabled) {
      await _settings.markNeedsUnlock();
      emit(AuthLocked((state as AuthAuthenticated).user));
    }
  }

  Future<void> _onLogout(
    AuthLogoutRequested event,
    Emitter<AuthState> emit,
  ) async {
    emit(const AuthLoading());
    await _repository.logout();
    await TichScreenCache.clearAll();
    emit(const AuthUnauthenticated());
  }
}
