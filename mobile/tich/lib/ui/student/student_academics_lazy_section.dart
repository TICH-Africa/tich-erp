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

  @override
  void initState() {
    super.initState();
    studentPrimeScreen(
      persistKey: widget.cacheKey,
      setCache: (c) => _cache = c,
      setFuture: (f) => _future = f,
      fetch: widget.fetch,
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
    return AdaptiveScaffold(
      title: widget.title,
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(
            snap,
            _cache as dynamic,
            persistKey: widget.cacheKey,
          );
          if (tichShowLoadingSpinner(snap, merged)) {
            return studentLoadingBody(onRefresh: () async => _reload());
          }
          if (merged == null && snap.hasError) {
            return studentErrorBody(snap.error, _reload);
          }
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: widget.builder(merged! as Map<String, dynamic>),
          );
        },
      ),
    );
  }
}
