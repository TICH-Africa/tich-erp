import 'package:flutter/material.dart';

import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
import '../../adaptive/adaptive.dart';
import '../widgets/tich_widgets.dart';
import '../../data/tich_screen_cache.dart';
import '../widgets/tich_live_refresh.dart';

class AppraisalsPage extends StatefulWidget {
  const AppraisalsPage({super.key, required this.api});

  final EmployeeApi api;

  @override
  State<AppraisalsPage> createState() => _AppraisalsPageState();
}

class _AppraisalsPageState extends State<AppraisalsPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;

  @override
  void initState() {
    super.initState();
    _dataCache = TichScreenCache.read<Map<String, dynamic>>('portal.appraisals');
    _future = TichScreenCache.ensure('portal.appraisals', () => widget.api.appraisals());
  }

  Future<void> _reload() => tichRefreshCached<Map<String, dynamic>>(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.appraisals(),
      onData: (data) => _dataCache = data,
      persistKey: 'portal.appraisals',
    );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.appraisals(),
      onData: (data) => _dataCache = data,
      persistKey: 'portal.appraisals',
    );
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
      title: 'Appraisals',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(snap, _dataCache as dynamic, persistKey: 'portal.appraisals');
          if (tichShowLoadingSpinner(snap, merged)) { return tichInstantWaitBody(onRefresh: () async => _reload()); }
          if (merged == null && snap.hasError) {
            return TichErrorRefresh(message: snap.error.toString(), onRetry: _reload);
          }
          final data = merged! as Map<String, dynamic>;
          final items = data['appraisals'] as List? ?? const [];
          if (items.isEmpty) {
            return const Center(child: TichEmpty('No appraisals yet.'));
          }
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (context, i) {
                final a = Map<String, dynamic>.from(items[i] as Map);
                return TichCard(
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(
                          a['cycle']?.toString() ?? 'Appraisal',
                          style: tichUi(fontWeight: FontWeight.w700),
                        ),
                      ),
                      TichStatusChip(
                        a['status']?.toString() ?? '',
                        tone: toneForStatus(a['status']?.toString()),
                      ),
                    ],
                  ),
                );
              },
            ),
          );
        },
      ),
      ),
    );
  }
}
