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
  static bool _indexLoaded = false;
  static Future<void>? _hydrateFuture;
  static Map<String, int> _diskIndex = {};
  static final Set<String> _dirtyKeys = {};

  static bool get isHydrated => _indexLoaded;

  /// Completes when the disk index for the current [assignUser] scope is known.
  static Future<void> whenHydrated() async {
    if (_indexLoaded || _userScope.isEmpty) return;
    await hydrateFromDisk();
  }

  /// Sets active user scope immediately (safe before [hydrateFromDisk] finishes).
  static void assignUser(int? userId) {
    final nextScope = userId == null ? '' : 'u$userId';
    if (nextScope == _userScope && _indexLoaded) return;
    _userScope = nextScope;
    _store.clear();
    _lru.clear();
    _inFlight.clear();
    _dirtyKeys.clear();
    _diskIndex = {};
    _indexLoaded = false;
    _hydrateFuture = null;
  }

  /// Reads which payloads are on disk. Decodes nothing - a screen pulls its own
  /// entry on demand, so no page ever waits for the whole cache to parse.
  static Future<void> hydrateFromDisk() async {
    if (_userScope.isEmpty || _indexLoaded) return;
    _hydrateFuture ??= _hydrateFromDiskImpl();
    await _hydrateFuture;
  }

  static Future<void> _hydrateFromDiskImpl() async {
    try {
      _diskIndex = await TichScreenCacheStore.index(_userScope);
    } catch (_) {
      _diskIndex = {};
    } finally {
      _indexLoaded = true;
      _hydrateFuture = null;
    }
  }

  /// Decodes the newest entries in the background so the screens the user lands
  /// on after login paint from cache instead of a skeleton.
  static Future<void> preloadRecent({int limit = 6}) async {
    if (_userScope.isEmpty) return;
    await hydrateFromDisk();
    final keys = _diskIndex.keys.toList()
      ..sort((a, b) => _diskIndex[b]!.compareTo(_diskIndex[a]!));
    for (final key in keys.take(limit)) {
      if (_store.containsKey(key)) continue;
      await _readDisk<Object>(key);
    }
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
    _dirtyKeys.clear();
    _diskIndex = {};
    _indexLoaded = false;
    _hydrateFuture = null;
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
    _writeWithoutPersist(key, value);
    _dirtyKeys.add(key);
    _schedulePersist();
  }

  static void _writeWithoutPersist(String key, Object value) {
    if (value is! Map<String, dynamic>) {
      _store[key] = value;
    } else {
      _store[key] = _cloneMap(value);
    }
    _touch(key);
    _trimMemory();
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
    final scope = _userScope;
    while (_lru.length > _maxMemoryEntries) {
      final evict = _lru.removeAt(0);
      _store.remove(evict);
      _dirtyKeys.remove(evict);
      _diskIndex.remove(evict);
      if (scope.isNotEmpty) {
        unawaited(TichScreenCacheStore.deleteEntry(scope, evict));
      }
    }
  }

  static void _schedulePersist() {
    if (_userScope.isEmpty || _dirtyKeys.isEmpty) return;
    _persistDebounce?.cancel();
    _persistDebounce = Timer(const Duration(milliseconds: 800), () {
      final scope = _userScope;
      if (scope.isEmpty || _dirtyKeys.isEmpty) return;
      final now = DateTime.now().millisecondsSinceEpoch;
      final batch = <String, Map<String, dynamic>>{};
      for (final key in _dirtyKeys.toList()) {
        final value = _store[key];
        if (value is Map<String, dynamic>) {
          batch[key] = value;
        }
      }
      _dirtyKeys.clear();
      if (batch.isNotEmpty) {
        for (final key in batch.keys) {
          _diskIndex[key] = now;
        }
        unawaited(
          TichScreenCacheStore.saveDirtyEntries(scope, batch, now),
        );
      }
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
  /// A warm memory hit returns synchronously; only a real miss touches disk.
  static Future<T> ensure<T>(String key, Future<T> Function() fetch) async {
    final cached = read<T>(key);
    if (cached != null) return cached;

    final existing = _inFlight[key];
    if (existing != null) {
      return existing.then((value) => value as T);
    }

    final diskHit = await _readDisk<T>(key);
    if (diskHit != null) return diskHit;

    final future = fetch().then((value) {
      write(key, value as Object);
      return value as Object;
    }).whenComplete(() => _inFlight.remove(key));

    _inFlight[key] = future;
    return future.then((value) => value as T);
  }

  static Future<T?> _readDisk<T>(String key) async {
    if (_userScope.isEmpty) return null;
    if (!_indexLoaded) await hydrateFromDisk();
    if (!_diskIndex.containsKey(key)) return null;
    final data = await TichScreenCacheStore.loadKey(_userScope, key);
    if (data == null) {
      _diskIndex.remove(key);
      return null;
    }
    _writeWithoutPersist(key, data);
    return read<T>(key);
  }

  /// Starts [fetch] when idle; safe to call from drawer open / tab preload.
  static void warm(String key, Future<Object> Function() fetch) {
    if (_store.containsKey(key) || _inFlight.containsKey(key)) return;
    unawaited(ensure<Object>(key, fetch));
  }
}
