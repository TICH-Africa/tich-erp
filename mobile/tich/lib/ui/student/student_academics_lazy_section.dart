import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../widgets/tich_live_refresh.dart';
import 'student_helpers.dart';

/// Loads a student academics subsection on first open (units, content, assessments).
class StudentAcademicsLazySection extends StatefulWidget {
  const StudentAcademicsLazySection({
    super.key,
    required this.title,
    required this.cacheKey,
    required this.fetch,
    required this.builder,
  });

  final String title;
  final String cacheKey;
  final Future<Map<String, dynamic>> Function() fetch;
  final Widget Function(Map<String, dynamic> data) builder;

  @override
  State<StudentAcademicsLazySection> createState() =>
      _StudentAcademicsLazySectionState();
}

class _StudentAcademicsLazySectionState extends State<StudentAcademicsLazySection> {
  late Future<Map<String, dynamic>> _future;
  Object? _cache;
  bool _recoverScheduled = false;

  @override
  void initState() {
    super.initState();
    studentPrimeScreen(
      persistKey: widget.cacheKey,
      setCache: (c) => _cache = c,
      setFuture: (f) => _future = f,
      fetch: widget.fetch,
    );
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      final cached = studentReadCache(widget.cacheKey);
      if (cached != null) {
        Future<void>.delayed(const Duration(seconds: 10), () {
          if (!mounted) return;
          _liveRefresh();
        });
        return;
      }
      _liveRefresh();
    });
  }

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: widget.fetch,
      onData: (data) => _cache = data,
      persistKey: widget.cacheKey,
    );
  }

  Future<void> _reload() => studentCachedReload(
        mounted: () => mounted,
        setState: setState,
        fetch: widget.fetch,
        onData: (data) => _cache = data,
        persistKey: widget.cacheKey,
      );

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
        title: widget.title,
        body: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snap) {
            return studentCachedMapBody(
              snap: snap,
              memoryCache: _cache,
              persistKey: widget.cacheKey,
              onRetry: _reload,
              autoRecoverOnError: true,
              recoverScheduled: _recoverScheduled,
              setRecoverScheduled: (v) => _recoverScheduled = v,
              mounted: () => mounted,
              builder: (data) => RefreshIndicator(
                onRefresh: () async => _reload(),
                child: widget.builder(data),
              ),
            );
          },
        ),
      ),
    );
  }
}
