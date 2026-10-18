import 'package:flutter/material.dart';

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
    return oldWidget.counts != counts;
  }
}

Map<String, int> parseSidebarCounts(Object? raw) {
  if (raw is! Map) return const {};
  final out = <String, int>{};
  raw.forEach((key, value) {
    if (key is! String) return;
    final n = value is num ? value.toInt() : int.tryParse('$value');
    if (n != null && n > 0) {
      out[key] = n;
    }
  });
  return out;
}
