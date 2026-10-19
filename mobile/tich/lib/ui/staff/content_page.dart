import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/staff_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_live_refresh.dart';
import 'staff_helpers.dart';

class StaffContentPage extends StatefulWidget {
  const StaffContentPage({super.key, required this.api});

  final StaffApi api;

  @override
  State<StaffContentPage> createState() => _StaffContentPageState();
}

class _StaffContentPageState extends State<StaffContentPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;

  @override
  void initState() {
    super.initState();
    _future = widget.api.content();
  }

  void _reload() => setState(() => _future = widget.api.content());

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.content(),
      onData: (data) => _dataCache = data,
    );
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
      title: 'Learning content',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(snap, _dataCache as dynamic);
          if (merged == null && snap.connectionState != ConnectionState.done) {
            return staffLoadingBody();
          }
          if (merged == null && snap.hasError) {
            return staffErrorBody(snap.error, _reload);
          }
          final data = merged! as Map<String, dynamic>;
          final items = data['items'] as List? ?? const [];
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: items.isEmpty
                ? ListView(
                    children: const [
                      TichCard(
                        child: TichEmpty('No learning content published yet.'),
                      ),
                    ],
                  )
                : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: items.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemBuilder: (context, i) {
                      final c =
                          Map<String, dynamic>.from(items[i] as Map);
                      return TichCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              c['title']?.toString() ?? 'Content',
                              style: tichUi(fontWeight: FontWeight.w700),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              [
                                c['unit_code'],
                                c['content_type'],
                              ]
                                  .where((e) => e != null && '$e'.isNotEmpty)
                                  .join(' · '),
                              style: tichUi(
                                fontSize: 12,
                                color: TichColors.neutralMuted,
                              ),
                            ),
                            if (c['created_at'] != null)
                              Text(
                                formatDate(c['created_at']),
                                style: tichUi(fontSize: 11),
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
