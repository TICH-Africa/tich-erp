import 'dart:async';

import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import 'tich_tab_active_scope.dart';
import 'tich_widgets.dart';

/// Observes pushed routes for live refresh when a page becomes visible again.
final RouteObserver<ModalRoute<void>> tichRouteObserver =
    RouteObserver<ModalRoute<void>>();

/// Default background poll interval while a screen is visible (WhatsApp-style).
const Duration tichLiveRefreshInterval = Duration(seconds: 25);

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
      return const Center(child: AdaptiveProgress());
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

/// Merge [AsyncSnapshot] with [_dataCache] so background refresh does not flash loading.
T? tichMergeSnapshot<T>(AsyncSnapshot<T> snap, T? cache) {
  if (snap.hasData) return snap.data;
  return cache;
}

bool tichShowLoadingSpinner<T>(AsyncSnapshot<T> snap, T? merged) {
  return merged == null && snap.connectionState != ConnectionState.done;
}

/// Background refresh: pass [onData] only (no new [Future]) to avoid [FutureBuilder] flicker.
void tichSilentReload<T>({
  required bool Function() mounted,
  required void Function(VoidCallback fn) setState,
  required Future<T> Function() fetch,
  void Function(T data)? onData,
  void Function(Future<T> future)? apply,
}) {
  unawaited(
    fetch().then((data) {
      if (!mounted()) return;
      setState(() {
        onData?.call(data);
        apply?.call(Future.value(data));
      });
    }).catchError((_) {}),
  );
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
    if (_tabVisible && _shouldRun) {
      widget.onLiveRefresh();
    }
    _syncTimer();
  }

  void _syncTimer() {
    _timer?.cancel();
    _timer = null;
    if (!_shouldRun) return;
    _timer = Timer.periodic(widget.interval, (_) {
      if (_shouldRun) widget.onLiveRefresh();
    });
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    final active = state == AppLifecycleState.resumed;
    if (active == _appActive) return;
    _appActive = active;
    if (_appActive && _shouldRun) {
      widget.onLiveRefresh();
    }
    _syncTimer();
  }

  @override
  void didPopNext() {
    _routeVisible = true;
    if (_shouldRun) widget.onLiveRefresh();
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
