import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:tich/adaptive/adaptive.dart';
import 'package:tich/ui/widgets/tich_more_drawer.dart';

void _noop() {}

const _links = [
  TichMoreItem(label: 'My profile', icon: Icons.person_outline, onTap: _noop),
  TichMoreItem(label: 'Documents', icon: Icons.folder_outlined, onTap: _noop),
];

const _tabs = [
  AdaptiveTabItem(
    label: 'Home',
    materialIcon: Icons.home_outlined,
    cupertinoIcon: Icons.home,
  ),
  AdaptiveTabItem(
    label: 'Leave',
    materialIcon: Icons.event_available_outlined,
    cupertinoIcon: Icons.event_available,
  ),
  AdaptiveTabItem(
    label: 'Clock in',
    materialIcon: Icons.fingerprint_outlined,
    cupertinoIcon: Icons.fingerprint,
  ),
  AdaptiveTabItem(
    label: 'Payslips',
    materialIcon: Icons.payments_outlined,
    cupertinoIcon: Icons.payments,
  ),
  AdaptiveTabItem(
    label: 'More',
    materialIcon: Icons.menu,
    cupertinoIcon: Icons.menu,
  ),
];

Future<List<int>> pumpShell(WidgetTester tester, {required Size size}) async {
  await tester.binding.setSurfaceSize(size);
  addTearDown(() => tester.binding.setSurfaceSize(null));

  final tapped = <int>[];
  await tester.pumpWidget(
    MaterialApp(
      home: AdaptiveTabScaffold(
        currentIndex: 0,
        onTap: tapped.add,
        pages: List.generate(
          _tabs.length,
          (i) => AdaptiveScaffold(title: 'Page $i', body: const SizedBox()),
        ),
        items: _tabs,
        portalName: 'Employee portal',
        moreIndex: 4,
        moreDrawer: (context) =>
            TichMoreDrawer(portalName: 'Employee portal', items: _links),
      ),
    ),
  );
  await tester.pumpAndSettle();
  return tapped;
}

void main() {
  testWidgets('a phone window keeps the five footer tabs', (tester) async {
    await pumpShell(tester, size: const Size(390, 844));
    expect(find.byType(NavigationBar), findsOneWidget);
    expect(find.text('Payslips'), findsOneWidget);
    expect(find.byType(Drawer), findsNothing);
  });

  testWidgets('a wide window keeps the footer tabs instead of a rail',
      (tester) async {
    await pumpShell(tester, size: const Size(1200, 900));
    expect(find.byType(NavigationBar), findsOneWidget);
    expect(find.text('Payslips'), findsOneWidget);
  });

  testWidgets('More slides the side bar open without switching tab',
      (tester) async {
    final tapped = await pumpShell(tester, size: const Size(1200, 900));

    await tester.tap(find.text('More'));
    await tester.pumpAndSettle();

    expect(tapped, isEmpty);
    expect(find.byType(Drawer), findsOneWidget);
    expect(find.text('My profile'), findsOneWidget);
    expect(find.text('Employee portal'), findsWidgets);
    expect(find.byType(NavigationBar), findsOneWidget);
  });

  testWidgets('a page pushed inside a portal offers the way back',
      (tester) async {
    await pumpShell(tester, size: const Size(390, 844));

    Navigator.of(tester.element(find.text('Page 0'))).push(
      MaterialPageRoute<void>(
        builder: (_) =>
            const AdaptiveScaffold(title: 'My profile', body: SizedBox()),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('My profile'), findsWidgets);
    expect(find.text('Employee portal'), findsOneWidget);
    expect(find.byTooltip('Back to Employee portal'), findsOneWidget);
  });
}
