/// Laravel API base URL.
///
/// Production is the default. For local development, override with the
/// machine's LAN IP (not 127.0.0.1 - that is the phone itself):
///
/// Override at run time:
///   flutter run --dart-define=API_BASE_URL=http://192.168.0.104:8000
///   flutter run --dart-define=API_BASE_URL=https://tich.africa
class ApiConfig {
  ApiConfig._();

  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://tich.africa',
  );

  static const String apiPrefix = '/api';

  static Uri uri(String path) {
    final normalized = path.startsWith('/') ? path : '/$path';
    return Uri.parse('$baseUrl$apiPrefix$normalized');
  }

  /// Web app origin (no `/api`) - opens ERP pages in the device browser.
  static String get webOrigin {
    final parsed = Uri.parse(baseUrl);
    return parsed.hasPort
        ? '${parsed.scheme}://${parsed.host}:${parsed.port}'
        : '${parsed.scheme}://${parsed.host}';
  }

  static Uri webUri(String path) {
    final normalized = path.startsWith('/') ? path : '/$path';
    return Uri.parse('$webOrigin$normalized');
  }
}
