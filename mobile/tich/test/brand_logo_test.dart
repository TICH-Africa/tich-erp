import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:tich/ui/widgets/tich_brand_logo.dart';

void main() {
  testWidgets('brand logo uses bundled asset and wordmark', (tester) async {
    await tester.pumpWidget(
      const MaterialApp(home: Scaffold(body: TichBrandLogo())),
    );

    final image = tester.widget<Image>(find.byType(Image));
    expect(image.image, isA<AssetImage>());
    expect(
      (image.image as AssetImage).assetName,
      TichBrandAssets.logo,
    );
    expect(find.text('TICH in Africa'), findsOneWidget);
  });
}
