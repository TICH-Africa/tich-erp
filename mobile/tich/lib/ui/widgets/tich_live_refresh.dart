import 'dart:async';

import 'package:flutter/material.dart';

import '../../data/api_client.dart';
import '../../data/tich_power_policy.dart';
import '../../data/tich_screen_cache.dart';
import 'tich_page_skeleton.dart';
import 'tich_tab_active_scope.dart';
import 'tich_widgets.dart';

/// Observes pushed routes for live refresh when a page becomes visible again.
final RouteObserver<ModalRoute<void>> tichRouteObserver =
    RouteObserver<ModalRoute<void>>();

/// Default background poll interval while a screen is visible.
const Duration tichLiveRefreshInterval = TichPowerPolicy.liveRefreshInterval;

/// Minimum gap between automatic background refreshes (avoids flicker on tab/back).
const Duration tichLiveRefreshMinGap = TichPowerPolicy.liveRefreshMinGap;

/// Scrollable skeleton while the first payload arrives - paints on frame 1.
Widget tichInstantWaitBody({Future<void> Function()? onRefresh}) {
  return RefreshIndicator(
    onRefresh: onRefresh ?? () async {},
    child: const TichPageSkeleton(),
  );
}

/// Hook for [TichLiveRefreshBinder] → [TichLiveData] silent updates (no [FutureBuilder] swap).
class TichAsyncRefreshHandle {
  VoidCallback? _silent;

  void attach(VoidCallback silent) => _silent = silent;

  void detach() => _silent = null;

  void refreshSilently() => _silent?.call();
}

/// Loads once, keeps data on screen while refreshing in the background (no flicker).
class TichLiveData<T> extends StatefulWidget {
  const TichLiveData({
    super.key,
    required this.fetch,
    required this.builder,
    required this.refreshHandle,
    this.errorMessage,
  });

  final Future<T> Function() fetch;
  final Widget Function(
    BuildContext context,
    T data,
    Future<void> Function() reload,
  ) builder;
  final TichAsyncRefreshHandle refreshHandle;
  final String? errorMessage;

  @override
  State<TichLiveData<T>> createState() => _TichLiveDataState<T>();
}

class _TichLiveDataState<T> extends State<TichLiveData<T>> {
  T? _data;
  Object? _error;

  @override
  void initState() {
    super.initState();
    widget.refreshHandle.attach(_silentFetch);
    _fetch(showError: true);
  }

  @override
  void dispose() {
    widget.refreshHandle.detach();
    super.dispose();
  }

  @override
  void didUpdateWidget(covariant TichLiveData<T> oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.refreshHandle != widget.refreshHandle) {
      oldWidget.refreshHandle.detach();
      widget.refreshHandle.attach(_silentFetch);
    }
  }

  Future<void> _fetch({required bool showError}) async {
    try {
      final result = await widget.fetch();
      if (!mounted) return;
      setState(() {
        _data = result;
        _error = null;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        if (showError || _data == null) {
          _error = e;
        }
      });
    }
  }

  void _silentFetch() {
    unawaited(_fetch(showError: false));
  }

  Future<void> _reload() => _fetch(showError: true);

  @override
  Widget build(BuildContext context) {
    if (_data == null && _error == null) {
      return tichInstantWaitBody(onRefresh: _reload);
    }
    if (_data == null && _error != null) {
      return TichErrorRefresh(
        message: widget.errorMessage ?? tichDescribeError(_error),
        onRetry: _reload,
      );
    }
    return widget.builder(context, _data as T, _reload);
  }
}

/// Merge [AsyncSnapshot] with in-memory cache so background refresh never flashes loading.
T? tichMergeSnapshot<T>(
  AsyncSnapshot<T> snap,
  T? cache, {
  String? persistKey,
}) {
  if (cache != null) return cache;
  if (snap.hasData) {
    final data = snap.data;
    if (persistKey != null && data != null) {
      TichScreenCache.write(persistKey, data as Object);
    }
    return data;
  }
  if (persistKey != null) return TichScreenCache.read<T>(persistKey);
  return null;
}

/// Prefer [tichInstantWaitBody] over a spinner when [merged] is still null.
bool tichShowLoadingSpinner<T>(AsyncSnapshot<T> snap, T? merged) {
  return merged == null && !snap.hasError;
}

/// Network refresh without replacing the [FutureBuilder]'s future (prevents flicker).
void tichSilentReload<T>({
  required bool Function() mounted,
  required void Function(VoidCallback fn) setState,
  required Future<T> Function() fetch,
  void Function(T data)? onData,
  String? persistKey,
}) {
  unawaited(
    fetch().then((data) {
      if (!mounted()) return;
      if (persistKey != null) {
        TichScreenCache.write(persistKey, data as Object);
      }
      setState(() => onData?.call(data));
    }).catchError((_) {}),
  );
}

/// Pull-to-refresh / retry: same as [tichSilentReload] but awaits completion.
Future<void> tichRefreshCached<T>({
  required bool Function() mounted,
  required void Function(VoidCallback fn) setState,
  required Future<T> Function() fetch,
  required void Function(T data) onData,
  String? persistKey,
}) async {
  try {
    final data = await fetch();
    if (!mounted()) return;
    if (persistKey != null) {
      TichScreenCache.write(persistKey, data as Object);
    }
    setState(() => onData(data));
  } catch (_) {}
}

/// Wraps portal screens so lists and dashboards stay up to date without pull-to-refresh.
class TichLiveRefreshBinder extends StatefulWidget {
  const TichLiveRefreshBinder({
    super.key,
    required this.onLiveRefresh,
    required this.child,
    this.enabled = true,
    this.interval = tichLiveRefreshInterval,
  });

  final VoidCallback onLiveRefresh;
  final Widget child;
  final bool enabled;
  final Duration interval;

  @override
  State<TichLiveRefreshBinder> createState() => _TichLiveRefreshBinderState();
}

class _TichLiveRefreshBinderState extends State<TichLiveRefreshBinder>
    with WidgetsBindingObserver, RouteAware {
  Timer? _timer;
  bool _routeVisible = true;
  bool _tabVisible = true;
  bool _appActive = true;
  DateTime? _lastAutoRefresh;

  bool get _shouldRun =>
      widget.enabled && _routeVisible && _tabVisible && _appActive;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final route = ModalRoute.of(context);
    if (route != null) {
      tichRouteObserver.unsubscribe(this);
      tichRouteObserver.subscribe(this, route);
    }
    _syncTabVisibility();
  }

  @override
  void didUpdateWidget(covariant TichLiveRefreshBinder oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.enabled != widget.enabled) {
      _syncTimer();
    }
  }

  void _syncTabVisibility() {
    final scope = TichTabActiveScope.maybeOf(context);
    final tabVisible = scope?.isActive ?? true;
    if (tabVisible == _tabVisible) return;
    _tabVisible = tabVisible;
    _syncTimer();
  }

  void _maybeAutoRefresh() {
    if (!_shouldRun) return;
    final now = DateTime.now();
    if (_lastAutoRefresh != null &&
        now.difference(_lastAutoRefresh!) < tichLiveRefreshMinGap) {
      return;
    }
    _lastAutoRefresh = now;
    widget.onLiveRefresh();
  }

  void _syncTimer() {
    _timer?.cancel();
    _timer = null;
    if (!_shouldRun) return;
    _timer = Timer.periodic(widget.interval, (_) => _maybeAutoRefresh());
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    final active = state == AppLifecycleState.resumed;
    if (active == _appActive) return;
    _appActive = active;
    if (_appActive) _maybeAutoRefresh();
    _syncTimer();
  }

  @override
  void didPopNext() {
    _routeVisible = true;
    _syncTimer();
  }

  @override
  void didPush() {
    _routeVisible = true;
    _syncTimer();
  }

  @override
  void didPushNext() {
    _routeVisible = false;
    _syncTimer();
  }

  @override
  void didPop() {
    _routeVisible = false;
    _syncTimer();
  }

  @override
  void dispose() {
    _timer?.cancel();
    tichRouteObserver.unsubscribe(this);
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final scope = TichTabActiveScope.maybeOf(context);
    if (scope != null && scope.isActive != _tabVisible) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) _syncTabVisibility();
      });
    }
    if (_shouldRun && _timer == null) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) _syncTimer();
      });
    }
    return widget.child;
  }
}
