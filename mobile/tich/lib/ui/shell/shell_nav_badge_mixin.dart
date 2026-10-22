import 'dart:async';

import 'package:flutter/material.dart';

import '../../data/tich_realtime_signals.dart';

/// Polls nav badge counts; kept slower than per-screen live refresh to reduce load.
const Duration shellNavBadgeInterval = Duration(seconds: 120);

/// Wait after shell paint before the first badge poll (avoids stacking on tab load).
const Duration shellNavBadgeInitialDelay = Duration(seconds: 8);

/// Polls a fetcher on an interval and when the app resumes.
mixin ShellNavBadgeMixin<T extends StatefulWidget> on State<T>, WidgetsBindingObserver {
  Timer? _badgeTimer;
  bool _badgeFetchScheduled = false;
  VoidCallback? _realtimeBadgeListener;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _realtimeBadgeListener = () => refreshNavBadges();
    final previous = TichRealtimeSignals.onSidebarCountsUpdated;
    TichRealtimeSignals.onSidebarCountsUpdated = () {
      previous?.call();
      _realtimeBadgeListener?.call();
    };
    Future<void>.delayed(shellNavBadgeInitialDelay, () {
      if (!mounted) return;
      refreshNavBadges();
      _badgeTimer?.cancel();
      _badgeTimer = Timer.periodic(shellNavBadgeInterval, (_) => refreshNavBadges());
    });
  }

  @override
  void dispose() {
    _badgeTimer?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      refreshNavBadges();
    }
  }

  Future<void> refreshNavBadges();

  void scheduleNavBadgeFetch(Future<void> Function() fetch) {
    if (_badgeFetchScheduled) return;
    _badgeFetchScheduled = true;
    unawaited(
      fetch().whenComplete(() {
        _badgeFetchScheduled = false;
      }),
    );
  }
}
