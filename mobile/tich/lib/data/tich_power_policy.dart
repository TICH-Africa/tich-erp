/// Intervals and throttles tuned for battery (ERP data does not need second-level freshness).
abstract final class TichPowerPolicy {
  /// Visible screen auto-refresh while app is in foreground (no WebSocket).
  /// Only the screen the user is looking at polls, and [liveRefreshMinGap] caps it,
  /// so this stays cheap while making a push-less server feel near-real-time.
  static const liveRefreshInterval = Duration(seconds: 45);

  /// Fallback poll when Reverb is connected (push handles most updates).
  static const liveRefreshIntervalWithRealtime = Duration(minutes: 20);

  static const liveRefreshMinGap = Duration(seconds: 40);

  /// Bottom-nav badge poll while portal is open.
  static const navBadgeInterval = Duration(minutes: 1);

  static const navBadgeInitialDelay = Duration(seconds: 4);

  static const navBadgeMinGap = Duration(seconds: 45);

  /// Re-scheduling hundreds of local alarms is expensive; limit how often we rebuild.
  static const clockInScheduleMinGap = Duration(minutes: 12);

  static const notificationSyncMinGap = Duration(minutes: 8);

  static const resumeMaintenanceMinGap = Duration(minutes: 10);

  /// Workmanager (Android minimum periodic interval is 15 minutes).
  static const workmanagerSyncInterval = Duration(minutes: 30);

  static const workmanagerClockInterval = Duration(minutes: 60);
}

/// Simple in-process throttle keys (per app session).
abstract final class TichRefreshThrottle {
  static final Map<String, DateTime> _last = {};

  static bool allow(String key, Duration minGap, {bool force = false}) {
    if (force) {
      _last[key] = DateTime.now();
      return true;
    }
    final now = DateTime.now();
    final prev = _last[key];
    if (prev != null && now.difference(prev) < minGap) {
      return false;
    }
    _last[key] = now;
    return true;
  }
}
