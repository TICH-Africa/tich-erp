import 'dart:convert';

import 'package:hive_flutter/hive_flutter.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'tich_screen_cache_codec.dart';

/// Per-key on-disk cache (Hive) so updating one screen does not re-encode the whole store.
abstract final class TichScreenCacheStore {
  static const _version = 'tich_api_cache_v3';
  static const _legacyVersion = 'tich_api_cache_v2';
  static const maxDiskEntries = 48;
  static const maxEntryAge = Duration(hours: 72);

  static Box<String>? _box;
  static String _openScope = '';

  static Future<void> init() async {
    await Hive.initFlutter();
  }

  static String _scopeKey(String userScope) => '$_version:$userScope';

  static String _legacyScopeKey(String userScope) => '$_legacyVersion:$userScope';

  static Future<void> _ensureBox(String userScope) async {
    if (userScope.isEmpty) return;
    final boxName = _scopeKey(userScope);
    if (_box != null && _openScope == userScope && _box!.isOpen) {
      return;
    }
    if (_box?.isOpen == true) {
      await _box!.close();
    }
    _box = await Hive.openBox<String>(boxName);
    _openScope = userScope;
    await _migrateLegacySharedPreferences(userScope);
  }

  static Future<void> _migrateLegacySharedPreferences(String userScope) async {
    final prefs = await SharedPreferences.getInstance();
    final legacyKey = _legacyScopeKey(userScope);
    final raw = prefs.getString(legacyKey);
    if (raw == null || raw.isEmpty) return;

    final now = DateTime.now().millisecondsSinceEpoch;
    final parsed = tichParseScreenCacheBlob(
      raw,
      now,
      maxEntryAge.inMilliseconds,
    );
    for (final entry in parsed.entries) {
      await _putEncoded(userScope, entry.key, now, entry.value);
    }
    await prefs.remove(legacyKey);
  }

  static Future<Map<String, Map<String, dynamic>>> load(String userScope) async {
    if (userScope.isEmpty) return {};
    await _ensureBox(userScope);
    final box = _box;
    if (box == null) return {};

    final now = DateTime.now().millisecondsSinceEpoch;
    final out = <String, Map<String, dynamic>>{};
    for (final key in box.keys) {
      final cacheKey = key.toString();
      final raw = box.get(cacheKey);
      if (raw == null || raw.isEmpty) continue;
      try {
        final decoded = jsonDecode(raw);
        if (decoded is! Map) continue;
        final savedAt = (decoded['t'] as num?)?.toInt();
        if (savedAt != null && now - savedAt > maxEntryAge.inMilliseconds) {
          await box.delete(cacheKey);
          continue;
        }
        final data = decoded['d'];
        if (data is Map) {
          out[cacheKey] = Map<String, dynamic>.from(data);
        }
      } catch (_) {
        await box.delete(cacheKey);
      }
    }
    return out;
  }

  static Future<void> saveEntry(
    String userScope,
    String key,
    Map<String, dynamic> data,
    int savedAtMs,
  ) async {
    if (userScope.isEmpty) return;
    await _ensureBox(userScope);
    await _putEncoded(userScope, key, savedAtMs, data);
    await _enforceMaxEntries(userScope);
  }

  static Future<void> saveDirtyEntries(
    String userScope,
    Map<String, Map<String, dynamic>> entries,
    int savedAtMs,
  ) async {
    if (userScope.isEmpty || entries.isEmpty) return;
    await _ensureBox(userScope);
    for (final entry in entries.entries) {
      await _putEncoded(userScope, entry.key, savedAtMs, entry.value);
    }
    await _enforceMaxEntries(userScope);
  }

  static Future<void> deleteEntry(String userScope, String key) async {
    if (userScope.isEmpty) return;
    await _ensureBox(userScope);
    await _box?.delete(key);
  }

  static Future<void> clearScope(String userScope) async {
    if (userScope.isEmpty) return;
    if (_box?.isOpen == true && _openScope == userScope) {
      await _box!.clear();
      await _box!.close();
      _box = null;
      _openScope = '';
    }
    await Hive.deleteBoxFromDisk(_scopeKey(userScope));
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_legacyScopeKey(userScope));
  }

  static Future<void> _putEncoded(
    String userScope,
    String key,
    int savedAtMs,
    Map<String, dynamic> data,
  ) async {
    await _ensureBox(userScope);
    final payload = jsonEncode({'t': savedAtMs, 'd': data});
    await _box!.put(key, payload);
  }

  static Future<void> _enforceMaxEntries(String userScope) async {
    await _ensureBox(userScope);
    final box = _box;
    if (box == null || box.length <= maxDiskEntries) return;

    final rows = <({String key, int savedAt})>[];
    final now = DateTime.now().millisecondsSinceEpoch;
    for (final key in box.keys) {
      final cacheKey = key.toString();
      final raw = box.get(cacheKey);
      var savedAt = 0;
      if (raw != null && raw.isNotEmpty) {
        try {
          final decoded = jsonDecode(raw);
          if (decoded is Map) {
            savedAt = (decoded['t'] as num?)?.toInt() ?? 0;
          }
        } catch (_) {}
      }
      if (savedAt > 0 && now - savedAt > maxEntryAge.inMilliseconds) {
        await box.delete(cacheKey);
        continue;
      }
      rows.add((key: cacheKey, savedAt: savedAt));
    }

    rows.sort((a, b) => a.savedAt.compareTo(b.savedAt));
    while (box.length > maxDiskEntries && rows.isNotEmpty) {
      final evict = rows.removeAt(0);
      await box.delete(evict.key);
    }
  }
}
