import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/procurement_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/portal_hero.dart';
import '../shell/procurement_nav_signals.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_widgets.dart';
import 'procurement_module_nav.dart';

class ProcurementHomePage extends StatefulWidget {
  const ProcurementHomePage({super.key, required this.api});

  final ProcurementApi api;

  @override
  State<ProcurementHomePage> createState() => _ProcurementHomePageState();
}

class _ProcurementHomePageState extends State<ProcurementHomePage> {
  late Future<Map<String, dynamic>> _future;
  Object? _cache;

  @override
  void initState() {
    super.initState();
    _future = widget.api.dashboard();
  }

  void _reload() {
    setState(() => _future = widget.api.dashboard());
    ProcurementPortalNavSignals.onCountsChanged?.call();
  }

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.dashboard(),
      onData: (d) => _cache = d,
    );
  }

  int _metric(Map<String, dynamic>? metrics, String key) =>
      (metrics?[key] as num?)?.toInt() ?? 0;

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
        title: 'Procurement',
        body: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snap) {
            final merged = tichMergeSnapshot(snap, _cache as dynamic);
            if (merged == null && snap.connectionState != ConnectionState.done) {
              return const Center(child: AdaptiveProgress());
            }
            if (merged == null && snap.hasError) {
              return Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text('$snap.error'),
                    TextButton(onPressed: _reload, child: const Text('Retry')),
                  ],
                ),
              );
            }
            final data = merged! as Map<String, dynamic>;
            final metrics =
                Map<String, dynamic>.from(data['metrics'] as Map? ?? {});
            final inbox = data['action_inbox'] as List? ?? [];

            final pendingApprovals = _metric(metrics, 'pending_requisitions') +
                _metric(metrics, 'pending_stock_issues') +
                _metric(metrics, 'pending_grns');

            return RefreshIndicator(
              onRefresh: () async => _reload(),
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  PortalHero(
                    title: 'Procurement command center',
                    subtitle:
                        'Approvals, receipts, stock, and assets — optimized for mobile.',
                    accent: TichColors.blueStrong,
                  ),
                  const SizedBox(height: 12),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      _metricChip(
                        'Needs action',
                        pendingApprovals,
                        TichColors.warningText,
                      ),
                      _metricChip(
                        'Stock alerts',
                        _metric(metrics, 'active_stock_alerts'),
                        TichColors.error,
                      ),
                      _metricChip(
                        'GRNs to inspect',
                        _metric(metrics, 'pending_grns'),
                        TichColors.blue,
                      ),
                    ],
                  ),
                  if (inbox.isNotEmpty) ...[
                    const SizedBox(height: 20),
                    Text('Needs action', style: TichTheme.display(fontSize: 14)),
                    const SizedBox(height: 8),
                    ...inbox.map((raw) {
                      final item = Map<String, dynamic>.from(raw as Map);
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: TichCard(
                          onTap: () => openProcurementInboxItem(
                            context,
                            api: widget.api,
                            item: item,
                          ),
                          child: Row(
                            children: [
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      (item['title'] ?? '').toString(),
                                      style: TichTheme.display(fontSize: 14),
                                    ),
                                    Text(
                                      (item['subtitle'] ?? '').toString(),
                                      style: tichUi(
                                        fontSize: 12,
                                        color: TichColors.neutralMuted,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              const Icon(
                                Icons.chevron_right,
                                color: TichColors.neutralMuted,
                              ),
                            ],
                          ),
                        ),
                      );
                    }),
                  ] else ...[
                    const SizedBox(height: 24),
                    Text(
                      'No pending actions right now.',
                      style: tichUi(
                        fontSize: 13,
                        color: TichColors.neutralMuted,
                      ),
                      textAlign: TextAlign.center,
                    ),
                  ],
                ],
              ),
            );
          },
        ),
      ),
    );
  }

  Widget _metricChip(String label, int value, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: tichUi(fontSize: 11, color: TichColors.neutralMuted),
          ),
          Text(
            '$value',
            style: TichTheme.display(fontSize: 18, color: color),
          ),
        ],
      ),
    );
  }
}
