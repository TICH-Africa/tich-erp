import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:tich/config/api_config.dart';
import 'package:tich/ui/widgets/tich_brand_logo.dart';

void main() {
  testWidgets('the brand mark is fetched from the API host', (tester) async {
    await tester.pumpWidget(
      const MaterialApp(home: Scaffold(body: TichBrandLogo())),
    );

    final image = tester.widget<Image>(find.byType(Image));
    final network = image.image as NetworkImage;
    expect(network.url, '${ApiConfig.baseUrl}/images/logo-mark.png');

    // The wordmark is there even before (or without) the file arriving.
    expect(find.text('TICH in Africa'), findsOneWidget);
  });
}
