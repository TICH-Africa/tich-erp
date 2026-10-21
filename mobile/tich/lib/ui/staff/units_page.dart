import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/staff_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_live_refresh.dart';
import 'staff_helpers.dart';

class StaffUnitsPage extends StatefulWidget {
  const StaffUnitsPage({super.key, required this.api});

  final StaffApi api;

  @override
  State<StaffUnitsPage> createState() => _StaffUnitsPageState();
}

class _StaffUnitsPageState extends State<StaffUnitsPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;

  @override
  void initState() {
    super.initState();
    staffPrimeScreen(
      persistKey: 'staff.units',
      setCache: (c) => _dataCache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.units(),
    );
  }

  Future<void> _reload() => staffCachedReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.units(),
      onData: (data) => _dataCache = data,
      persistKey: 'staff.units',
    );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.units(),
      onData: (data) => _dataCache = data,
    );
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
      title: 'My units',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(snap, _dataCache as dynamic, persistKey: 'staff.units');
          if (tichShowLoadingSpinner(snap, merged)) { return staffLoadingBody(onRefresh: () async => _reload()); }
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
                      TichCard(child: TichEmpty('No active unit allocations.')),
                    ],
                  )
                : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: items.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 8),
                    itemBuilder: (context, i) {
                      final u = Map<String, dynamic>.from(items[i] as Map);
                      return TichCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              [
                                u['unit_code'],
                                u['unit_name'],
                              ]
                                  .where((e) => e != null && '$e'.isNotEmpty)
                                  .join(' · '),
                              style: tichUi(fontWeight: FontWeight.w700),
                            ),
                            if (staffUnitCohortYearLine(u).isNotEmpty) ...[
                              const SizedBox(height: 6),
                              Text(
                                staffUnitCohortYearLine(u),
                                style: tichUi(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w600,
                                  color: TichColors.blueDark,
                                ),
                              ),
                            ],
                            const SizedBox(height: 4),
                            Text(
                              [
                                u['semester'],
                                u['campus'],
                              ]
                                  .where((e) => e != null && '$e'.isNotEmpty)
                                  .join(' · '),
                              style: tichUi(
                                fontSize: 12,
                                color: TichColors.neutralMuted,
                              ),
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
