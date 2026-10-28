import 'dart:async';

import 'tich_screen_cache_store.dart';

/// In-memory + on-disk cache so screens paint from last data (WhatsApp-style).
abstract final class TichScreenCache {
  static const _maxMemoryEntries = 36;
  static final Map<String, Object> _store = {};
  static final List<String> _lru = [];
  static final Map<String, Future<Object>> _inFlight = {};

  static String _userScope = '';
  static Timer? _persistDebounce;
  static bool _hydrated = false;

  static bool get isHydrated => _hydrated;

  /// Sets active user scope immediately (safe before [hydrateFromDisk] finishes).
  static void assignUser(int? userId) {
    final nextScope = userId == null ? '' : 'u$userId';
    if (nextScope == _userScope && _hydrated) return;
    _userScope = nextScope;
    _store.clear();
    _lru.clear();
    _inFlight.clear();
    _hydrated = false;
  }

  /// Loads persisted tab payloads for the current [assignUser] scope.
  static Future<void> hydrateFromDisk() async {
    if (_userScope.isEmpty || _hydrated) return;
    final disk = await TichScreenCacheStore.load(_userScope);
    for (final entry in disk.entries) {
      write(entry.key, entry.value);
    }
    _hydrated = true;
  }

  /// Call after login; loads persisted payloads for this user.
  static Future<void> bindUser(int? userId) async {
    assignUser(userId);
    await hydrateFromDisk();
  }

  /// Logout / account switch - drop memory and optional disk slice.
  static Future<void> clearAll({bool wipeDisk = false}) async {
    final scope = _userScope;
    _store.clear();
    _lru.clear();
    _inFlight.clear();
    _hydrated = false;
    _userScope = '';
    _persistDebounce?.cancel();
    if (wipeDisk && scope.isNotEmpty) {
      await TichScreenCacheStore.clearScope(scope);
    }
  }

  static String scopedKey(String key) => key;

  static T? read<T>(String key) {
    final value = _store[key];
    if (value is T) return value;
    return null;
  }

  static void write(String key, Object value) {
    if (value is! Map<String, dynamic>) {
      _store[key] = value;
    } else {
      _store[key] = _cloneMap(value);
    }
    _touch(key);
    _trimMemory();
    _schedulePersist();
  }

  static Map<String, dynamic> _cloneMap(Map<String, dynamic> source) {
    // Shallow copy is enough for immutable API payloads; avoids jsonEncode jank on large academics JSON.
    return source.map((key, value) {
      if (value is Map) {
        return MapEntry(key, Map<String, dynamic>.from(value));
      }
      if (value is List) {
        return MapEntry(key, List<dynamic>.from(value));
      }
      return MapEntry(key, value);
    });
  }

  static void _touch(String key) {
    _lru.remove(key);
    _lru.add(key);
  }

  static void _trimMemory() {
    while (_lru.length > _maxMemoryEntries) {
      final evict = _lru.removeAt(0);
      _store.remove(evict);
    }
  }

  static void _schedulePersist() {
    if (_userScope.isEmpty) return;
    _persistDebounce?.cancel();
    _persistDebounce = Timer(const Duration(milliseconds: 800), () {
      unawaited(
        TichScreenCacheStore.save(_userScope, _store, _lru),
      );
    });
  }

  static void seed<T>(String key, void Function(T? cached) apply) {
    apply(read<T>(key));
  }

  /// Prime a typical map API screen ([FutureBuilder] + optional [_dataCache]).
  static void primeMapScreen({
    required String persistKey,
    required void Function(Object? cache) applyCache,
    required void Function(Future<Map<String, dynamic>> future) applyFuture,
    required Future<Map<String, dynamic>> Function() fetch,
  }) {
    applyCache(read<Map<String, dynamic>>(persistKey));
    applyFuture(ensure(persistKey, fetch));
  }

  /// One shared in-flight request per [key] - prefetch and page open dedupe.
  static Future<T> ensure<T>(String key, Future<T> Function() fetch) {
    final cached = read<T>(key);
    if (cached != null) return Future<T>.value(cached);

    final existing = _inFlight[key];
    if (existing != null) {
      return existing.then((value) => value as T);
    }

    final future = fetch().then((value) {
      write(key, value as Object);
      return value as Object;
    }).whenComplete(() => _inFlight.remove(key));

    _inFlight[key] = future;
    return future.then((value) => value as T);
  }

  /// Starts [fetch] when idle; safe to call from drawer open / tab preload.
  static void warm(String key, Future<Object> Function() fetch) {
    if (_store.containsKey(key) || _inFlight.containsKey(key)) return;
    unawaited(ensure<Object>(key, fetch));
  }
}
