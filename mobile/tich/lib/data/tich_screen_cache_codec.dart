import 'dart:convert';

/// Top-level parser for [Isolate.run] (must not close over non-sendable state).
Map<String, Map<String, dynamic>> tichParseScreenCacheBlob(
  String raw,
  int nowEpochMs,
  int maxEntryAgeMs,
) {
  if (raw.isEmpty) return {};
  try {
    final decoded = jsonDecode(raw);
    if (decoded is! Map) return {};
    final out = <String, Map<String, dynamic>>{};
    for (final entry in decoded.entries) {
      final key = entry.key.toString();
      final row = entry.value;
      if (row is! Map) continue;
      final savedAt = (row['t'] as num?)?.toInt();
      if (savedAt != null && nowEpochMs - savedAt > maxEntryAgeMs) {
        continue;
      }
      final data = row['d'];
      if (data is Map) {
        out[key] = Map<String, dynamic>.from(data);
      }
    }
    return out;
  } catch (_) {
    return {};
  }
}

String tichEncodeScreenCacheBlob(Map<String, dynamic> blob) => jsonEncode(blob);
