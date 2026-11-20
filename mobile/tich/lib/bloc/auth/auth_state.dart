import 'package:equatable/equatable.dart';

import '../../models/auth_user.dart';

sealed class AuthState extends Equatable {
  const AuthState();

  @override
  List<Object?> get props => [];
}

final class AuthInitial extends AuthState {
  const AuthInitial();
}

/// Cold start / session restore - full-screen spinner only.
final class AuthChecking extends AuthState {
  const AuthChecking();
}

/// Login/logout in progress - keep login UI visible.
final class AuthLoading extends AuthState {
  const AuthLoading();
}

final class AuthAuthenticated extends AuthState {
  const AuthAuthenticated(this.user);

  final AuthUser user;

  @override
  List<Object?> get props => [user];
}

/// Token still valid, but app lock / biometric unlock required.
final class AuthLocked extends AuthState {
  const AuthLocked(this.user, {this.message});

  final AuthUser user;
  final String? message;

  @override
  List<Object?> get props => [user, message];
}

final class AuthUnauthenticated extends AuthState {
  const AuthUnauthenticated({this.message});

  final String? message;

  @override
  List<Object?> get props => [message];
}

/// Password accepted; waiting for the one-time 6-digit email code (first mobile sign-in).
final class AuthMobileOtpPending extends AuthState {
  const AuthMobileOtpPending(
    this.user, {
    this.emailMasked,
    this.expiresMinutes = 15,
    this.otpSent = true,
    this.message,
    this.resending = false,
  });

  final AuthUser user;
  final String? emailMasked;
  final int expiresMinutes;
  final bool otpSent;
  final String? message;
  final bool resending;

  @override
  List<Object?> get props =>
      [user, emailMasked, expiresMinutes, otpSent, message, resending];
}
