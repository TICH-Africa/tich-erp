import 'dart:async';

import 'package:flutter/material.dart';

import '../widgets/tich_live_refresh.dart';

/// Polls a fetcher on an interval and when the app resumes.
mixin ShellNavBadgeMixin<T extends StatefulWidget> on State<T>, WidgetsBindingObserver {
  Timer? _badgeTimer;
  bool _badgeFetchScheduled = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    WidgetsBinding.instance.addPostFrameCallback((_) => refreshNavBadges());
    _badgeTimer = Timer.periodic(tichLiveRefreshInterval, (_) => refreshNavBadges());
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
