import 'package:flutter_bloc/flutter_bloc.dart';

import '../../data/auth_api.dart';
import '../../data/auth_repository.dart';
import 'auth_event.dart';
import 'auth_state.dart';

class AuthBloc extends Bloc<AuthEvent, AuthState> {
  AuthBloc({required this._repository}) : super(const AuthInitial()) {
    on<AuthStarted>(_onStarted);
    on<AuthLoginSubmitted>(_onLogin);
    on<AuthLogoutRequested>(_onLogout);
  }

  final AuthRepository _repository;

  Future<void> _onStarted(
    AuthStarted event,
    Emitter<AuthState> emit,
  ) async {
    emit(const AuthLoading());
    try {
      final user = await _repository.restoreSession();
      if (user == null) {
        emit(const AuthUnauthenticated());
      } else {
        emit(AuthAuthenticated(user));
      }
    } catch (e) {
      emit(AuthUnauthenticated(message: e.toString()));
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
      emit(AuthAuthenticated(user));
    } on AuthApiException catch (e) {
      emit(AuthUnauthenticated(message: e.message));
    } catch (e) {
      emit(AuthUnauthenticated(
        message:
            'Could not reach the server. Check API_BASE_URL and that Laravel is serving on 0.0.0.0:8000.\n$e',
      ));
    }
  }

  Future<void> _onLogout(
    AuthLogoutRequested event,
    Emitter<AuthState> emit,
  ) async {
    emit(const AuthLoading());
    await _repository.logout();
    emit(const AuthUnauthenticated());
  }
}
