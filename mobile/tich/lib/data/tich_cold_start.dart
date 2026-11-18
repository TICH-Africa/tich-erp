import 'dart:async';

import 'tich_screen_cache.dart';

/// First install / empty disk: cap background work so one screen loads at a time.
abstract final class TichColdStart {
  static bool _emptyDiskSession = false;
  static bool _homeSettled = false;
  static DateTime? _sessionStart;
  static Completer<void>? _homeSettledCompleter;

  /// Call after [TichScreenCache.hydrateFromDisk] on login.
  static void beginSession() {
    _emptyDiskSession = !TichScreenCache.hasDiskCache;
    _homeSettled = !_emptyDiskSession;
    _sessionStart = DateTime.now();
    _homeSettledCompleter = _emptyDiskSession ? Completer<void>() : null;
  }

  static void reset() {
    _emptyDiskSession = false;
    _homeSettled = true;
    _sessionStart = null;
    if (!(_homeSettledCompleter?.isCompleted ?? true)) {
      _homeSettledCompleter!.complete();
    }
    _homeSettledCompleter = null;
  }

  static bool get isEmptyDiskSession => _emptyDiskSession;

  /// Home / dashboard payload is on screen (memory or disk).
  static void markHomeSettled() {
    if (_homeSettled) return;
    _homeSettled = true;
    if (!(_homeSettledCompleter?.isCompleted ?? true)) {
      _homeSettledCompleter!.complete();
    }
    _homeSettledCompleter = null;
  }

  static bool get isHomeSettled => _homeSettled;

  /// Non-home screens wait here on first install so they do not compete with home.
  static Future<void> whenHomeSettled() async {
    if (!_emptyDiskSession || _homeSettled) return;
    final pending = _homeSettledCompleter;
    if (pending == null) return;
    await pending.future;
  }

  /// Blocks redundant post-frame [tichSilentReload] while the first [ensure] runs.
  static bool shouldBlockSilentReload(String persistKey) {
    if (!_emptyDiskSession) return false;
    if (!_homeSettled) {
      return TichScreenCache.read(persistKey) == null;
    }
    return false;
  }

  /// Deferred portal warmup (academics, timetable, …).
  static Duration get deferredWarmupDelay {
    if (_emptyDiskSession) {
      return const Duration(seconds: 50);
    }
    return const Duration(seconds: 12);
  }

  static bool get allowDeferredWarmup {
    if (!_emptyDiskSession) return true;
    if (!_homeSettled) return false;
    final start = _sessionStart;
    if (start == null) return true;
    return DateTime.now().difference(start) >= const Duration(seconds: 20);
  }

  static bool get allowSecondaryEndpoints {
    if (!_emptyDiskSession) return true;
    return _homeSettled;
  }

  static Duration get badgeServiceStartDelay {
    if (_emptyDiskSession) return const Duration(seconds: 10);
    return const Duration(seconds: 2);
  }

  static Duration get notificationsPrimeDelay {
    if (_emptyDiskSession) return const Duration(seconds: 18);
    return const Duration(seconds: 4);
  }

  static Duration get realtimeConnectDelay {
    if (_emptyDiskSession) return const Duration(seconds: 25);
    return const Duration(seconds: 3);
  }

  /// While many screens are empty, only run a few cache fetches at once.
  static int get maxConcurrentEnsures {
    if (_emptyDiskSession && !_homeSettled) return 1;
    if (_emptyDiskSession) return 3;
    return 6;
  }

  /// Max simultaneous HTTP GETs during first install (across all endpoints).
  static int get maxConcurrentHttpGets => _emptyDiskSession ? 2 : 8;
}