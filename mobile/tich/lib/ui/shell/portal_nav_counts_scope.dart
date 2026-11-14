import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';

export '../../data/portal_sidebar_counts.dart';

/// Updates [PortalNavCountsScope] for the More drawer without rebuilding tabs.
class PortalNavCountsModel extends ChangeNotifier {
  Map<String, int> counts = const {};

  void update(Map<String, int> next) {
    if (mapEquals(counts, next)) return;
    counts = Map.unmodifiable(next);
    notifyListeners();
  }
}

/// Sidebar-style notification counts keyed like the web portal (`profile-changes`, …).
class PortalNavCountsScope extends InheritedWidget {
  const PortalNavCountsScope({
    super.key,
    required this.counts,
    required super.child,
  });

  final Map<String, int> counts;

  static PortalNavCountsScope? maybeOf(BuildContext context) {
    return context.dependOnInheritedWidgetOfExactType<PortalNavCountsScope>();
  }

  int countFor(String key) => counts[key] ?? 0;

  int sumKeys(Iterable<String> keys) {
    var total = 0;
    for (final key in keys) {
      total += countFor(key);
    }
    return total;
  }

  @override
  bool updateShouldNotify(PortalNavCountsScope oldWidget) {
    return !mapEquals(oldWidget.counts, counts);
  }
}

