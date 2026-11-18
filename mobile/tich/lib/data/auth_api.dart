import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config/api_config.dart';
import '../config/mobile_client.dart';
import '../models/auth_user.dart';
import 'api_client.dart';

class AuthApiException implements Exception {
  AuthApiException(this.message, {this.statusCode});

  final String message;
  final int? statusCode;

  @override
  String toString() => message;
}

class LoginResult {
  const LoginResult({
    required this.token,
    required this.user,
    this.mobileOtpRequired = false,
    this.otpSent = false,
    this.otpExpiresMinutes = 15,
    this.emailMasked,
    this.message,
  });

  final String token;
  final AuthUser user;
  final bool mobileOtpRequired;
  final bool otpSent;
  final int otpExpiresMinutes;
  final String? emailMasked;
  final String? message;
}

class MeResult {
  const MeResult({
    required this.user,
    this.mobileOtpRequired = false,
  });

  final AuthUser user;
  final bool mobileOtpRequired;
}

class AuthApi {
  AuthApi({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;

  static const Duration _timeout = ApiClient.timeout;

  Map<String, String> _jsonHeaders({String? token}) => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        ...tichMobileClientHeaders,
        if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
      };

  Future<LoginResult> login({
    required String email,
    required String password,
  }) async {
    final response = await _client
        .post(
          ApiConfig.uri('/auth/login'),
          headers: _jsonHeaders(),
          body: jsonEncode({
            'login': email.trim(),
            'password': password,
          }),
        )
        .timeout(_timeout);

    final body = _decode(response);

    if (response.statusCode >= 200 && response.statusCode < 300) {
      final token = body['token']?.toString();
      final userJson = body['user'];
      if (token == null || token.isEmpty || userJson is! Map) {
        throw AuthApiException('Login succeeded but token/user missing.');
      }

      final userMap = Map<String, dynamic>.from(userJson);
      if (body['roles'] != null) {
        userMap['roles'] = body['roles'];
      }

      return LoginResult(
        token: token,
        user: AuthUser.fromJson(userMap),
        mobileOtpRequired: body['mobile_otp_required'] == true,
        otpSent: body['otp_sent'] == true,
        otpExpiresMinutes:
            int.tryParse(body['otp_expires_minutes']?.toString() ?? '') ?? 15,
        emailMasked: body['email_masked']?.toString(),
        message: body['message']?.toString(),
      );
    }

    throw AuthApiException(
      body['message']?.toString() ?? 'Login failed (${response.statusCode})',
      statusCode: response.statusCode,
    );
  }

  Future<MeResult> me(String token) async {
    final response = await _client
        .get(
          ApiConfig.uri('/auth/me'),
          headers: _jsonHeaders(token: token),
        )
        .timeout(_timeout);

    final body = _decode(response);

    if (response.statusCode >= 200 && response.statusCode < 300) {
      final userJson = body['user'];
      if (userJson is! Map) {
        throw AuthApiException('Invalid /auth/me response.');
      }
      final userMap = Map<String, dynamic>.from(userJson);
      if (body['roles'] != null) {
        userMap['roles'] = body['roles'];
      }
      if (userMap['name'] == null || userMap['name'].toString().isEmpty) {
        userMap['name'] =
            userMap['email']?.toString().split('@').first ?? 'User';
      }
      return MeResult(
        user: AuthUser.fromJson(userMap),
        mobileOtpRequired: body['mobile_otp_required'] == true,
      );
    }

    throw AuthApiException(
      body['message']?.toString() ??
          'Session check failed (${response.statusCode})',
      statusCode: response.statusCode,
    );
  }

  Future<void> verifyMobileOtp({
    required String token,
    required String code,
  }) async {
    final response = await _client
        .post(
          ApiConfig.uri('/auth/mobile/otp/verify'),
          headers: _jsonHeaders(token: token),
          body: jsonEncode({'code': code.trim()}),
        )
        .timeout(_timeout);

    final body = _decode(response);
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return;
    }
    throw AuthApiException(
      body['message']?.toString() ??
          'Could not verify code (${response.statusCode})',
      statusCode: response.statusCode,
    );
  }

  Future<int> resendMobileOtp(String token) async {
    final response = await _client
        .post(
          ApiConfig.uri('/auth/mobile/otp/resend'),
          headers: _jsonHeaders(token: token),
        )
        .timeout(_timeout);

    final body = _decode(response);
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return int.tryParse(body['otp_expires_minutes']?.toString() ?? '') ?? 15;
    }
    throw AuthApiException(
      body['message']?.toString() ??
          'Could not resend code (${response.statusCode})',
      statusCode: response.statusCode,
    );
  }

  Future<void> changePassword({
    required String token,
    required String currentPassword,
    required String password,
    required String passwordConfirmation,
  }) async {
    final response = await _client
        .post(
          ApiConfig.uri('/auth/change-password'),
          headers: _jsonHeaders(token: token),
          body: jsonEncode({
            'current_password': currentPassword,
            'password': password,
            'password_confirmation': passwordConfirmation,
          }),
        )
        .timeout(_timeout);

    final body = _decode(response);
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return;
    }
    throw AuthApiException(
      body['message']?.toString() ??
          'Could not change password (${response.statusCode})',
      statusCode: response.statusCode,
    );
  }

  Future<void> logout(String token) async {
    final response = await _client
        .post(
          ApiConfig.uri('/auth/logout'),
          headers: _jsonHeaders(token: token),
        )
        .timeout(_timeout);

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return;
    }

    final body = _decode(response);
    throw AuthApiException(
      body['message']?.toString() ?? 'Logout failed (${response.statusCode})',
      statusCode: response.statusCode,
    );
  }

  Map<String, dynamic> _decode(http.Response response) {
    if (response.body.isEmpty) {
      return {};
    }
    try {
      final decoded = jsonDecode(response.body);
      if (decoded is Map<String, dynamic>) {
        return decoded;
      }
      if (decoded is Map) {
        return Map<String, dynamic>.from(decoded);
      }
      return {'message': response.body};
    } catch (_) {
      return {'message': response.body};
    }
  }
}