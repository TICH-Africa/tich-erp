import 'dart:async';

import 'package:flutter_bloc/flutter_bloc.dart';

import '../../config/api_config.dart';
import '../../data/auth_api.dart';
import '../../data/auth_repository.dart';
import '../../data/tich_realtime.dart';
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
    on<AuthMobileOtpSubmitted>(_onMobileOtpSubmitted);
    on<AuthMobileOtpResendRequested>(_onMobileOtpResend);
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
          await _startCacheForUser(result.user!);
          emit(AuthAuthenticated(result.user!));
        case SessionRestoreResultType.mobileOtp:
          emit(AuthMobileOtpPending(
            result.user!,
            expiresMinutes: result.otpExpiresMinutes ?? 15,
          ));
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
      final session = await _repository.login(
        email: event.email,
        password: event.password,
      );
      if (session.mobileOtpRequired) {
        emit(AuthMobileOtpPending(
          session.user,
          emailMasked: session.emailMasked,
          expiresMinutes: session.otpExpiresMinutes,
          otpSent: session.otpSent,
          message: session.message,
        ));
        return;
      }
      await _startCacheForUser(session.user);
      emit(AuthAuthenticated(session.user));
    } on AuthApiException catch (e) {
      emit(AuthUnauthenticated(message: e.message));
    } catch (e) {
      emit(AuthUnauthenticated(
        message:
            'Could not reach the API at ${ApiConfig.baseUrl}. Check your network connection or configure API_BASE_URL for your environment.\n$e',
      ));
    }
  }

  Future<void> _startCacheForUser(AuthUser user) async {
    TichScreenCache.assignUser(user.id);
    await TichScreenCache.hydrateFromDisk();
    // Warm the newest screens behind the first frame so the tab the user lands
    // on paints from cache instead of waiting on the network.
    unawaited(TichScreenCache.preloadRecent());
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
      await _startCacheForUser(current.user);
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
      final session = await _repository.unlockWithPassword(
        email: event.email,
        password: event.password,
      );
      if (session.mobileOtpRequired) {
        emit(AuthMobileOtpPending(
          session.user,
          emailMasked: session.emailMasked,
          expiresMinutes: session.otpExpiresMinutes,
          otpSent: session.otpSent,
          message: session.message,
        ));
        return;
      }
      await _startCacheForUser(session.user);
      emit(AuthAuthenticated(session.user));
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

  Future<void> _onMobileOtpSubmitted(
    AuthMobileOtpSubmitted event,
    Emitter<AuthState> emit,
  ) async {
    final current = state;
    if (current is! AuthMobileOtpPending) return;

    emit(const AuthLoading());
    try {
      final user = await _repository.verifyMobileOtp(event.code);
      await _startCacheForUser(user);
      emit(AuthAuthenticated(user));
    } on AuthApiException catch (e) {
      emit(AuthMobileOtpPending(
        current.user,
        emailMasked: current.emailMasked,
        expiresMinutes: current.expiresMinutes,
        otpSent: current.otpSent,
        message: e.message,
      ));
    } catch (e) {
      emit(AuthMobileOtpPending(
        current.user,
        emailMasked: current.emailMasked,
        expiresMinutes: current.expiresMinutes,
        otpSent: current.otpSent,
        message: e.toString(),
      ));
    }
  }

  Future<void> _onMobileOtpResend(
    AuthMobileOtpResendRequested event,
    Emitter<AuthState> emit,
  ) async {
    final current = state;
    if (current is! AuthMobileOtpPending) return;

    emit(AuthMobileOtpPending(
      current.user,
      emailMasked: current.emailMasked,
      expiresMinutes: current.expiresMinutes,
      otpSent: current.otpSent,
      resending: true,
    ));

    try {
      final minutes = await _repository.resendMobileOtp();
      emit(AuthMobileOtpPending(
        current.user,
        emailMasked: current.emailMasked,
        expiresMinutes: minutes,
        otpSent: true,
        message: 'A new code was sent to your email.',
      ));
    } on AuthApiException catch (e) {
      emit(AuthMobileOtpPending(
        current.user,
        emailMasked: current.emailMasked,
        expiresMinutes: current.expiresMinutes,
        otpSent: current.otpSent,
        message: e.message,
      ));
    }
  }

  Future<void> _onLogout(
    AuthLogoutRequested event,
    Emitter<AuthState> emit,
  ) async {
    // Keep the current screen visible until cleanup finishes (no AuthLoading flash).
    await TichRealtime.disconnect();
    await _repository.logout();
    await TichScreenCache.clearAll();
    emit(const AuthUnauthenticated());
  }
}
