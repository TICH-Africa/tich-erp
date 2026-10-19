import 'dart:convert';

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

class ApiClient {
  ApiClient({
    AuthTokenStore? tokenStore,
    http.Client? client,
  })  : _tokenStore = tokenStore ?? AuthTokenStore(),
        _client = client ?? http.Client();

  final AuthTokenStore _tokenStore;
  final http.Client _client;
  static const Duration timeout = Duration(seconds: 15);
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

  Future<Map<String, dynamic>> get(String path) async {
    final response = await _client
        .get(ApiConfig.uri(path), headers: await _headers())
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
