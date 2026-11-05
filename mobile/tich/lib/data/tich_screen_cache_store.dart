import 'dart:convert';
import 'dart:isolate';

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

  /// Key -> saved timestamp, without decoding any payload. Lets the app start
  /// from an index and pull only the screen it is about to show.
  static Future<Map<String, int>> index(String userScope) async {
    if (userScope.isEmpty) return {};
    await _ensureBox(userScope);
    final box = _box;
    if (box == null) return {};

    final now = DateTime.now().millisecondsSinceEpoch;
    final out = <String, int>{};
    for (final key in box.keys) {
      final cacheKey = key.toString();
      final raw = box.get(cacheKey);
      if (raw == null || raw.isEmpty) continue;
      final savedAt = _savedAtOf(raw);
      if (savedAt == null) {
        await box.delete(cacheKey);
        continue;
      }
      if (now - savedAt > maxEntryAge.inMilliseconds) {
        await box.delete(cacheKey);
        continue;
      }
      out[cacheKey] = savedAt;
    }
    return out;
  }

  /// Decodes one payload only - the screen the user actually opened.
  static Future<Map<String, dynamic>?> loadKey(
    String userScope,
    String key,
  ) async {
    if (userScope.isEmpty) return null;
    await _ensureBox(userScope);
    final box = _box;
    if (box == null) return null;

    final raw = box.get(key);
    if (raw == null || raw.isEmpty) return null;
    final savedAt = _savedAtOf(raw);
    if (savedAt == null ||
        DateTime.now().millisecondsSinceEpoch - savedAt >
            maxEntryAge.inMilliseconds) {
      await box.delete(key);
      return null;
    }
    try {
      final decoded = jsonDecode(raw);
      if (decoded is! Map) return null;
      final data = decoded['d'];
      return data is Map ? Map<String, dynamic>.from(data) : null;
    } catch (_) {
      await box.delete(key);
      return null;
    }
  }

  /// Reads the save time from the payload prefix. Entries are written as
  /// `{"t":<ms>,"d":{...}}`, so a full jsonDecode is never needed for expiry
  /// or least-recently-used ordering - the two places that ran on every write.
  static int? _savedAtOf(String raw) {
    const prefix = '{"t":';
    if (!raw.startsWith(prefix)) return _savedAtByDecode(raw);
    final end = raw.indexOf(',', prefix.length);
    if (end <= prefix.length) return _savedAtByDecode(raw);
    return int.tryParse(raw.substring(prefix.length, end));
  }

  static int? _savedAtByDecode(String raw) {
    try {
      final decoded = jsonDecode(raw);
      if (decoded is! Map) return null;
      return (decoded['t'] as num?)?.toInt();
    } catch (_) {
      return null;
    }
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
    final box = _box;
    if (box == null) return;

    // Encode a real batch away from the UI isolate, then write it in one putAll
    // so a background save cannot drop frames while the user is still scrolling.
    // Spawning that isolate costs more than the encode itself for one or two
    // keys, so a small batch stays inline.
    final encoded = entries.length <= 2
        ? tichEncodeScreenCacheEntries(entries, savedAtMs)
        : await Isolate.run(
            () => tichEncodeScreenCacheEntries(entries, savedAtMs),
          );
    await box.putAll(encoded);
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
        savedAt = _savedAtOf(raw) ?? 0;
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
