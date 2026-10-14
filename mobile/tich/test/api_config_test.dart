import 'package:flutter_test/flutter_test.dart';
import 'package:tich/config/api_config.dart';
import 'package:tich/ui/widgets/employee_avatar.dart';

void main() {
  group('employeePhotoUri', () {
    test(
      'resolves host-relative uploaded media against the configured API',
      () {
        final uri = employeePhotoUri('/storage/staff/photo.jpg');

        expect(uri, isNotNull);
        expect(uri!.path, '/storage/staff/photo.jpg');
        expect(uri.host, Uri.parse(ApiConfig.baseUrl).host);
      },
    );

    test('preserves an absolute media URL', () {
      final uri = employeePhotoUri('https://cdn.example.test/photo.jpg');

      expect(uri, Uri.parse('https://cdn.example.test/photo.jpg'));
    });

    test('returns null when no media URL is provided', () {
      expect(employeePhotoUri(null), isNull);
      expect(employeePhotoUri(' '), isNull);
    });
  });
}
