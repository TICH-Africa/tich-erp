import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config/api_config.dart';
import '../models/auth_user.dart';

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
  });

  final String token;
  final AuthUser user;
}

class AuthApi {
  AuthApi({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;

  Future<LoginResult> login({
    required String email,
    required String password,
  }) async {
    final response = await _client.post(
      ApiConfig.uri('/auth/login'),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'login': email.trim(),
        'password': password,
      }),
    );

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
      );
    }

    throw AuthApiException(
      body['message']?.toString() ?? 'Login failed (${response.statusCode})',
      statusCode: response.statusCode,
    );
  }

  Future<AuthUser> me(String token) async {
    final response = await _client.get(
      ApiConfig.uri('/auth/me'),
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

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
        // /me may return raw User without name — fall back to email local-part.
        userMap['name'] = userMap['email']?.toString().split('@').first ?? 'User';
      }
      return AuthUser.fromJson(userMap);
    }

    throw AuthApiException(
      body['message']?.toString() ?? 'Session check failed (${response.statusCode})',
      statusCode: response.statusCode,
    );
  }

  Future<void> logout(String token) async {
    final response = await _client.post(
      ApiConfig.uri('/auth/logout'),
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return;
    }

    // Still clear local session even if server logout fails.
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
