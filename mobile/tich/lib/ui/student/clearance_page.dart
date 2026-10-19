import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/student_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_live_refresh.dart';
import 'student_helpers.dart';

class StudentClearancePage extends StatefulWidget {
  const StudentClearancePage({super.key, required this.api});

  final StudentApi api;

  @override
  State<StudentClearancePage> createState() => _StudentClearancePageState();
}

class _StudentClearancePageState extends State<StudentClearancePage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;

  @override
  void initState() {
    super.initState();
    _future = widget.api.clearance();
  }

  void _reload() => setState(() => _future = widget.api.clearance());

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.clearance(),
      onData: (data) => _dataCache = data,
    );
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
      title: 'Clearance',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(snap, _dataCache as dynamic);
          if (merged == null && snap.connectionState != ConnectionState.done) {
            return studentLoadingBody();
          }
          if (merged == null && snap.hasError) {
            return studentErrorBody(snap.error, _reload);
          }
          final data = merged! as Map<String, dynamic>;
          final items = data['items'] as List? ?? const [];
          return RefreshIndicator(
            onRefresh: () async {
              await widget.api.ensureClearance();
              _reload();
            },
            child: items.isEmpty
                ? ListView(
                    children: const [
                      SizedBox(height: 120),
                      Center(child: TichEmpty('No clearance items.')),
                    ],
                  )
                : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: items.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemBuilder: (context, i) {
                      final item =
                          Map<String, dynamic>.from(items[i] as Map);
                      return TichCard(
                        child: ListTile(
                          contentPadding: EdgeInsets.zero,
                          title: Text(
                            item['department_label']?.toString() ??
                                item['department_key']?.toString() ??
                                'Item',
                            style: tichUi(fontWeight: FontWeight.w600),
                          ),
                          subtitle: Text(
                            item['cleared_at'] != null
                                ? 'Cleared ${formatDateTime(item['cleared_at'])}'
                                : (item['notes']?.toString() ?? 'Awaiting clearance'),
                          ),
                          trailing: TichStatusChip(
                            item['status']?.toString() ?? 'pending',
                            tone: toneForStatus(item['status']?.toString()),
                          ),
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
