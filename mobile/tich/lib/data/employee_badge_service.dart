import 'dart:async';

import 'package:flutter/foundation.dart';

import '../notifications/tich_notifications.dart';
import 'employee_api.dart';
import 'employee_clock_nav.dart';
import 'portal_sidebar_counts.dart';
import 'tich_realtime_signals.dart';

/// Shared employee nav + dashboard signals from attendance + notifications APIs.
class EmployeeBadgeSnapshot {
  const EmployeeBadgeSnapshot({
    required this.sidebarCounts,
    required this.notificationTotal,
    required this.unreadCount,
    required this.clockPulse,
    required this.clockedInToday,
    required this.fetchedAt,
  });

  final Map<String, int> sidebarCounts;
  final int notificationTotal;
  final int unreadCount;
  final bool clockPulse;
  final bool? clockedInToday;
  final DateTime fetchedAt;
}

/// Single owner for parallel `/employee/attendance` + `/employee/notifications`.
class EmployeeBadgeService extends ChangeNotifier {
  EmployeeBadgeService(this._api);

  final EmployeeApi _api;

  static const _minGap = Duration(seconds: 25);

  EmployeeBadgeSnapshot? _snapshot;
  Future<void>? _inFlight;
  DateTime? _lastSuccess;
  VoidCallback? _realtimeHook;

  EmployeeBadgeSnapshot? get snapshot => _snapshot;

  void start() {
    final previous = TichRealtimeSignals.onSidebarCountsUpdated;
    _realtimeHook = () {
      previous?.call();
      unawaited(refresh(force: true));
    };
    TichRealtimeSignals.onSidebarCountsUpdated = _realtimeHook;
    unawaited(refresh());
  }

  @override
  void dispose() {
    if (TichRealtimeSignals.onSidebarCountsUpdated == _realtimeHook) {
      TichRealtimeSignals.onSidebarCountsUpdated = null;
    }
    super.dispose();
  }

  /// [force] skips the inter-fetch gap (Reverb push, pull-to-refresh, clock-in).
  Future<void> refresh({bool force = false}) async {
    if (!force &&
        _lastSuccess != null &&
        DateTime.now().difference(_lastSuccess!) < _minGap) {
      return;
    }
    if (_inFlight != null) {
      return _inFlight;
    }
    _inFlight = _fetch().whenComplete(() => _inFlight = null);
    return _inFlight;
  }

  Future<void> _fetch() async {
    try {
      final results = await Future.wait([
        _api.attendance(),
        _api.notifications(),
      ]);
      final attendance = results[0];
      final notifications = results[1];

      final counts = parseSidebarCounts(notifications['counts']);
      final total = (notifications['total'] as num?)?.toInt() ?? 0;
      final unread = (notifications['unread'] as num?)?.toInt() ?? total;
      final today = attendance['today'];
      final clockedIn = today is Map && today['clock_in_at'] != null;
      final pulse = employeeNeedsClockInPulse(attendance);

      _snapshot = EmployeeBadgeSnapshot(
        sidebarCounts: counts,
        notificationTotal: total,
        unreadCount: unread,
        clockPulse: pulse,
        clockedInToday: clockedIn,
        fetchedAt: DateTime.now(),
      );
      _lastSuccess = _snapshot!.fetchedAt;
      notifyListeners();

      unawaited(
        TichNotifications.instance.syncFromServerInBackground(isStudent: false),
      );
      unawaited(
        TichNotifications.instance.scheduleClockInReminders(
          alreadyClockedIn: clockedIn,
        ),
      );
    } catch (_) {}
  }
}
