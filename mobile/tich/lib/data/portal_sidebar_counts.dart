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
