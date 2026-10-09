import 'package:flutter_test/flutter_test.dart';
import 'package:tich/main.dart';

void main() {
  testWidgets('TICH app boots to loading/login gate', (tester) async {
    await tester.pumpWidget(const TichApp());
    await tester.pump();
    expect(find.byType(TichApp), findsOneWidget);
  });
}
