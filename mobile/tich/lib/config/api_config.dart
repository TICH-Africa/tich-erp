/// Laravel API base URL.
///
/// Local device on the same Wi‑Fi as the machine running
/// `php artisan serve --host=0.0.0.0 --port=8000`:
///   use that machine's LAN IP (not 127.0.0.1 — that is the phone itself).
///
/// Override at run time:
///   flutter run --dart-define=API_BASE_URL=http://192.168.0.104:8000
///   flutter run --dart-define=API_BASE_URL=https://tich.africa
class ApiConfig {
  ApiConfig._();

  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://192.168.0.104:8000',
  );

  static const String apiPrefix = '/api';

  static Uri uri(String path) {
    final normalized = path.startsWith('/') ? path : '/$path';
    return Uri.parse('$baseUrl$apiPrefix$normalized');
  }
}
