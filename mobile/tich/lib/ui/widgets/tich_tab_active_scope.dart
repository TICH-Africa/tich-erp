import 'package:flutter/widgets.dart';

/// Marks whether a tab child in [LazyIndexedStack] is the selected tab.
class TichTabActiveScope extends InheritedWidget {
  const TichTabActiveScope({
    super.key,
    required this.activeIndex,
    required this.tabIndex,
    required super.child,
  });

  final int activeIndex;
  final int tabIndex;

  bool get isActive => activeIndex == tabIndex;

  static TichTabActiveScope? maybeOf(BuildContext context) {
    return context.dependOnInheritedWidgetOfExactType<TichTabActiveScope>();
  }

  @override
  bool updateShouldNotify(covariant TichTabActiveScope oldWidget) {
    return oldWidget.activeIndex != activeIndex ||
        oldWidget.tabIndex != tabIndex;
  }
}
