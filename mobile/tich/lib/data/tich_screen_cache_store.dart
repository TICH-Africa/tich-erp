import 'dart:isolate';

import 'package:shared_preferences/shared_preferences.dart';

import 'tich_screen_cache_codec.dart';

/// Persists API screen payloads so revisiting tabs survives app restarts.
abstract final class TichScreenCacheStore {
  static const _version = 'tich_api_cache_v2';
  static const maxDiskEntries = 48;
  static const maxEntryAge = Duration(hours: 72);

  static String _scopeKey(String userScope) => '$_version:$userScope';

  static Future<Map<String, Map<String, dynamic>>> load(String userScope) async {
    if (userScope.isEmpty) return {};
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_scopeKey(userScope));
    if (raw == null || raw.isEmpty) return {};

    final now = DateTime.now().millisecondsSinceEpoch;
    try {
      return await Isolate.run(
        () => tichParseScreenCacheBlob(
          raw,
          now,
          maxEntryAge.inMilliseconds,
        ),
      );
    } catch (_) {
      return {};
    }
  }

  static Future<void> save(
    String userScope,
    Map<String, Object> memory,
    List<String> lruOrder,
  ) async {
    if (userScope.isEmpty) return;
    final prefs = await SharedPreferences.getInstance();
    final now = DateTime.now().millisecondsSinceEpoch;
    final blob = <String, dynamic>{};
    var count = 0;
    for (var i = lruOrder.length - 1; i >= 0 && count < maxDiskEntries; i--) {
      final key = lruOrder[i];
      final value = memory[key];
      if (value is! Map<String, dynamic>) continue;
      blob[key] = {'t': now, 'd': value};
      count++;
    }
    final json = await Isolate.run(() => tichEncodeScreenCacheBlob(blob));
    await prefs.setString(_scopeKey(userScope), json);
  }

  static Future<void> clearScope(String userScope) async {
    if (userScope.isEmpty) return;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_scopeKey(userScope));
  }
}
