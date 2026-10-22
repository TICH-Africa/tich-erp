import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;
import 'package:http_parser/http_parser.dart';

import '../config/api_config.dart';
import 'auth_token_store.dart';

class ApiException implements Exception {
  ApiException(this.message, {this.statusCode});

  final String message;
  final int? statusCode;

  @override
  String toString() => message;
}

/// Plain words for a failure a screen shows.
///
/// A tab used to print the Dart object text straight to the user - "TimeoutException
/// after 0:00:20.000000: Future not completed" - which names nothing they can act on.
/// Network, timeout and server failures are mapped to what happened and what to do,
/// and a message the backend wrote on purpose is passed through as written.
String tichDescribeError(Object? error) {
  if (error is ApiException) {
    return tichDescribeHttpStatus(error.statusCode) ?? error.message;
  }

  final text = (error ?? '').toString().trim();
  if (text.isEmpty) {
    return 'Something went wrong. Pull down to try again.';
  }

  if (error is TimeoutException ||
      text.contains('TimeoutException') ||
      text.contains('Future not completed')) {
    return 'The server is slow to answer. Check the connection and try again.';
  }

  if (error is SocketException ||
      text.contains('SocketException') ||
      text.contains('Failed host lookup') ||
      text.contains('Connection refused')) {
    return 'Cannot reach the server. Check the network or the app address.';
  }

  if (text.contains('ClientException')) {
    return 'The connection to the server dropped. Try again.';
  }

  final stripped = text.replaceFirst(RegExp(r'^(Exception|ApiException):\s*'), '');
  final status = RegExp(r'\((\d{3})\)$').firstMatch(stripped)?.group(1);
  final described = tichDescribeHttpStatus(status == null ? null : int.parse(status));

  return described ?? stripped;
}

/// What a rejected request means for the person holding the phone.
String? tichDescribeHttpStatus(int? status) {
  final code = status;
  if (code == null) return null;

  return switch (code) {
    401 => 'Your session expired. Sign in again.',
    403 => 'This account is not allowed to open that page.',
    404 => 'That record is no longer there.',
    422 => 'Some details are missing or wrong. Check the form.',
    429 => 'Too many requests. Wait a moment and try again.',
    >=500 => 'The server could not complete the request. Try again shortly.',
    _ => null,
  };
}

/// HTTPS JSON API transport for mobile (lists, mutations, file uploads).
///
/// Live server push uses [TichRealtime] (WebSocket / Laravel Reverb), not this client.
class ApiClient {
  ApiClient({
    AuthTokenStore? tokenStore,
    http.Client? client,
  })  : _tokenStore = tokenStore ?? AuthTokenStore(),
        _client = client ?? http.Client();

  final AuthTokenStore _tokenStore;
  final http.Client _client;

  /// Opening a tab fires the page plus the nav badge counts, and a server that
  /// handles one request at a time queues them behind each other. Anything tighter
  /// than this gave up on requests the server was still going to answer.
  static const Duration timeout = Duration(seconds: 30);
  String? _cachedToken;

  Future<String?> _token() async {
    if (_cachedToken != null && _cachedToken!.isNotEmpty) {
      return _cachedToken;
    }
    _cachedToken = await _tokenStore.read();
    return _cachedToken;
  }

  /// Call after login / logout so secure-storage isn't hit every request.
  void invalidateTokenCache() => _cachedToken = null;

  void cacheToken(String? token) {
    _cachedToken = token;
  }

  Future<Map<String, String>> _headers({bool json = true}) async {
    final headers = <String, String>{'Accept': 'application/json'};
    if (json) {
      headers['Content-Type'] = 'application/json';
    }
    final token = await _token();
    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }
    return headers;
  }

  Future<List<int>> getBytes(
    String path, {
    Map<String, String>? query,
    String accept = 'application/pdf',
  }) async {
    var uri = ApiConfig.uri(path);
    if (query != null && query.isNotEmpty) {
      uri = uri.replace(queryParameters: query);
    }
    final headers = await _headers(json: false);
    headers['Accept'] = accept;
    final response = await _client.get(uri, headers: headers).timeout(timeout);
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return response.bodyBytes;
    }
    throw ApiException(
      'Request failed (${response.statusCode})',
      statusCode: response.statusCode,
    );
  }

  Future<Map<String, dynamic>> get(
    String path, {
    Map<String, String>? query,
  }) async {
    var uri = ApiConfig.uri(path);
    if (query != null && query.isNotEmpty) {
      uri = uri.replace(queryParameters: query);
    }
    final response = await _client
        .get(uri, headers: await _headers())
        .timeout(timeout);
    return _decode(response);
  }

  Future<Map<String, dynamic>> post(
    String path, {
    Map<String, dynamic>? body,
  }) async {
    final response = await _client
        .post(
          ApiConfig.uri(path),
          headers: await _headers(),
          body: body == null ? null : jsonEncode(body),
        )
        .timeout(timeout);
    return _decode(response);
  }

  Future<Map<String, dynamic>> put(
    String path, {
    Map<String, dynamic>? body,
  }) async {
    final response = await _client
        .put(
          ApiConfig.uri(path),
          headers: await _headers(),
          body: body == null ? null : jsonEncode(body),
        )
        .timeout(timeout);
    return _decode(response);
  }

  Future<Map<String, dynamic>> multipart(
    String path, {
    required Map<String, String> fields,
    required String fileField,
    required String filePath,
    required String fileName,
    String? mimeType,
  }) =>
      multipartFiles(
        path,
        fields: fields,
        files: [
          (
            field: fileField,
            path: filePath,
            name: fileName,
            mimeType: mimeType,
          ),
        ],
      );

  Future<Map<String, dynamic>> multipartFiles(
    String path, {
    required Map<String, String> fields,
    required List<({String field, String path, String name, String? mimeType})>
        files,
  }) async {
    final request = http.MultipartRequest('POST', ApiConfig.uri(path));
    request.headers.addAll(await _headers(json: false));
    request.fields.addAll(fields);
    for (final file in files) {
      request.files.add(
        await http.MultipartFile.fromPath(
          file.field,
          file.path,
          filename: file.name,
          contentType:
              file.mimeType != null ? MediaType.parse(file.mimeType!) : null,
        ),
      );
    }
    final streamed = await request.send().timeout(timeout);
    final response = await http.Response.fromStream(streamed);
    return _decode(response);
  }

  Map<String, dynamic> _decode(http.Response response) {
    Map<String, dynamic> body = {};
    if (response.body.isNotEmpty) {
      try {
        final decoded = jsonDecode(response.body);
        if (decoded is Map<String, dynamic>) {
          body = decoded;
        } else if (decoded is Map) {
          body = Map<String, dynamic>.from(decoded);
        } else {
          body = {'data': decoded};
        }
      } catch (_) {
        body = {'message': response.body};
      }
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return body;
    }

    throw ApiException(
      body['message']?.toString() ?? 'Request failed (${response.statusCode})',
      statusCode: response.statusCode,
    );
  }
}
