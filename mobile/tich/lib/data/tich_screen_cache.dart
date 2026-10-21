import 'dart:async';

/// In-memory cache so revisiting a portal screen can paint instantly (WhatsApp-style).
abstract final class TichScreenCache {
  static final Map<String, Object> _store = {};
  static final Map<String, Future<Object>> _inFlight = {};

  static T? read<T>(String key) {
    final value = _store[key];
    if (value is T) return value;
    return null;
  }

  static void write(String key, Object value) {
    _store[key] = value;
  }

  static void seed<T>(String key, void Function(T? cached) apply) {
    apply(read<T>(key));
  }

  /// One shared in-flight request per [key] — shell prefetch and page open dedupe.
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
