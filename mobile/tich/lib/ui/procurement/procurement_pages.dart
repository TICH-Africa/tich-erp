import 'dart:async';

import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/procurement_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_widgets.dart';
import 'procurement_helpers.dart';
import 'procurement_create_requisition_page.dart';
import 'procurement_raise_stock_issue_page.dart';

final _kes = NumberFormat.currency(symbol: 'KES ', decimalDigits: 0);

class ProcurementRequisitionsPage extends StatefulWidget {
  const ProcurementRequisitionsPage({super.key, required this.api});

  final ProcurementApi api;

  @override
  State<ProcurementRequisitionsPage> createState() =>
      _ProcurementRequisitionsPageState();
}

class _ProcurementRequisitionsPageState extends State<ProcurementRequisitionsPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _cache;
  Timer? _searchDebounce;
  String _query = '';

  @override
  void initState() {
    super.initState();
    procurementPrimeScreen(
      persistKey: 'procurement.requisitions',
      setCache: (c) => _cache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.requisitions(),
    );
  }

  @override
  void dispose() {
    _searchDebounce?.cancel();
    super.dispose();
  }

  void _load({String? search}) {
    procurementUpdateFuture(
      setState,
      (f) {
        _future = f;
      },
      () => widget.api.requisitions(
        search: search != null && search.isNotEmpty ? search : null,
      ),
      mounted: () => mounted,
      onData: (d) => _cache = d,
    );
  }

  void _onSearchChanged(String q) {
    _searchDebounce?.cancel();
    _searchDebounce = Timer(const Duration(milliseconds: 320), () {
      _query = q;
      _load(search: q);
    });
  }

  void _reload() => _load(search: _query);

  Future<void> _openCreate() async {
    final id = await Navigator.of(context).push<int>(
      MaterialPageRoute(
        builder: (_) => ProcurementCreateRequisitionPage(api: widget.api),
      ),
    );
    if (!mounted) return;
    _reload();
    if (id != null) {
      await Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) =>
              ProcurementRequisitionDetailPage(api: widget.api, id: id),
        ),
      );
      if (mounted) _reload();
    }
  }

  Future<void> _openDetail(int id) async {
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ProcurementRequisitionDetailPage(api: widget.api, id: id),
      ),
    );
    if (mounted) _reload();
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Requisitions',
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _openCreate,
        icon: const Icon(Icons.add),
        label: const Text('New requisition'),
      ),
      body: TichLiveRefreshBinder(
        onLiveRefresh: () => _load(search: _query),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            ProcurementSearchBar(
              hint: 'Search number, item, department, budget…',
              onQueryChanged: _onSearchChanged,
            ),
            Expanded(
              child: FutureBuilder<Map<String, dynamic>>(
                future: _future,
                builder: (context, snap) {
                  final merged = tichMergeSnapshot(
                    snap,
                    _cache as dynamic,
                    persistKey: 'procurement.requisitions',
                  );
                  if (tichShowLoadingSpinner(snap, merged)) {
                    return procurementLoadingBody(onRefresh: () async => _reload());
                  }
                  if (merged == null && snap.hasError) {
                    return Center(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(tichDescribeError(snap.error)),
                          TextButton(
                            onPressed: _reload,
                            child: const Text('Retry'),
                          ),
                        ],
                      ),
                    );
                  }
                  final items = (merged as Map<String, dynamic>)['items'] as List? ?? [];
                  if (items.isEmpty) {
                    return Center(
                      child: Text(
                        _query.isEmpty
                            ? 'No requisitions yet. Tap New requisition.'
                            : 'No matches for "$_query".',
                        textAlign: TextAlign.center,
                        style: tichUi(color: TichColors.neutralMuted),
                      ),
                    );
                  }
                  return RefreshIndicator(
                    onRefresh: () async => _reload(),
                    child: ListView.separated(
                      padding: const EdgeInsets.all(12),
                      itemCount: items.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 8),
                      itemBuilder: (context, i) {
                        final r = Map<String, dynamic>.from(items[i] as Map);
                        final id = (r['id'] as num).toInt();
                        final dept = (r['department'] ?? '').toString();
                        final reqDate = formatDate(r['request_date']);
                        return TichCard(
                          onTap: () => _openDetail(id),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                (r['requisition_number'] ?? '').toString(),
                                style: TichTheme.display(fontSize: 15),
                              ),
                              Text(
                                (r['requested_item'] ?? '').toString(),
                                style: tichUi(
                                  fontSize: 13,
                                  color: TichColors.neutralMuted,
                                ),
                              ),
                              if (dept.isNotEmpty || reqDate != '-') ...[
                                const SizedBox(height: 4),
                                Text(
                                  [
                                    if (dept.isNotEmpty) dept,
                                    if (reqDate != '-') reqDate,
                                  ].join(' · '),
                                  style: tichUi(
                                    fontSize: 12,
                                    color: TichColors.neutralMuted,
                                  ),
                                ),
                              ],
                              const SizedBox(height: 6),
                              Row(
                                children: [
                                  procurementStatusChip(
                                    (r['status_label'] ?? '').toString(),
                                  ),
                                  const Spacer(),
                                  Text(
                                    _kes.format((r['estimated_cost'] as num?) ?? 0),
                                    style: tichUi(fontSize: 12),
                                  ),
                                ],
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
          ],
        ),
      ),
    );
  }
}

class ProcurementRequisitionDetailPage extends StatefulWidget {
  const ProcurementRequisitionDetailPage({
    super.key,
    required this.api,
    required this.id,
  });

  final ProcurementApi api;
  final int id;

  @override
  State<ProcurementRequisitionDetailPage> createState() =>
      _ProcurementRequisitionDetailPageState();
}

class _ProcurementRequisitionDetailPageState
    extends State<ProcurementRequisitionDetailPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _cache;
  bool _busy = false;

  String get _cacheKey => 'procurement.requisition.${widget.id}';

  @override
  void initState() {
    super.initState();
    procurementPrimeScreen(
      persistKey: _cacheKey,
      setCache: (c) => _cache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.requisition(widget.id),
    );
  }

  Future<void> _act(Future<Map<String, dynamic>> Function() call) async {
    setState(() => _busy = true);
    try {
      await call();
      if (mounted) {
        procurementUpdateFuture(
          setState,
          (f) {
        _future = f;
      },
          () => widget.api.requisition(widget.id),
          mounted: () => mounted,
          onData: (d) => _cache = d,
          persistKey: _cacheKey,
        );
        procurementNotifyCountsChanged();
      }
    } catch (e) {
      if (mounted) procurementShowError(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Requisition',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(
            snap,
            _cache as dynamic,
            persistKey: _cacheKey,
          );
          if (tichShowLoadingSpinner(snap, merged)) {
            return procurementLoadingBody();
          }
          if (merged == null && snap.hasError) {
            return Center(child: Text(tichDescribeError(snap.error)));
          }
          final r = merged! as Map<String, dynamic>;
          final canApprove = r['can_approve'] == true;
          final canSubmit = r['can_submit'] == true;
          final canReject = r['can_reject'] == true;
          final budget = Map<String, dynamic>.from(r['budget_check'] as Map? ?? {});
          final lines = r['line_items'] as List? ?? [];
          final actions = <Widget>[];
          if (canSubmit) {
            actions.add(
              FilledButton(
                onPressed: _busy
                    ? null
                    : () => _act(() => widget.api.submitRequisition(widget.id)),
                child: const Text('Submit for approval'),
              ),
            );
          }
          if (canApprove) {
            actions.add(
              FilledButton(
                onPressed: _busy
                    ? null
                    : () => _act(() => widget.api.approveRequisition(widget.id)),
                child: _busy
                    ? const AdaptiveProgress()
                    : const Text('Approve'),
              ),
            );
          }
          if (canReject) {
            actions.add(
              OutlinedButton(
                onPressed: _busy
                    ? null
                    : () async {
                        final reason = await procurementPromptText(
                          context,
                          title: 'Reject requisition',
                          hint: 'Reason for rejection',
                        );
                        if (reason == null || reason.isEmpty) return;
                        await _act(() => widget.api.rejectRequisition(
                              widget.id,
                              comments: reason,
                            ));
                      },
                child: const Text('Reject'),
              ),
            );
          }

          return ProcurementDetailBody(
            children: [
              procurementDetailHero(
                title: (r['requisition_number'] ?? '').toString(),
                eyebrow: (r['requested_item'] ?? '').toString(),
                statusLabel: (r['status_label'] ?? '').toString(),
                metricLabel: 'Estimated',
                metricValue: _kes.format((r['estimated_cost'] as num?) ?? 0),
              ),
              procurementDetailSection('Details', [
                procurementDetailField('Department', r['department']),
                procurementDetailField('Requested item', r['requested_item']),
                if ((r['justification'] ?? '').toString().isNotEmpty)
                  procurementDetailNote((r['justification'] ?? '').toString()),
              ]),
              if ((budget['message'] ?? '').toString().isNotEmpty)
                procurementDetailSection('Budget check', [
                  procurementDetailNote(budget['message'].toString()),
                ]),
              if (lines.isNotEmpty)
                procurementDetailSection(
                  'Line items',
                  lines.map((raw) {
                    final line = Map<String, dynamic>.from(raw as Map);
                    return procurementLineItemTile(
                      title: (line['item'] ?? '').toString(),
                      subtitle: 'Qty ${line['quantity']}',
                      trailing: _kes.format((line['unit_price'] as num?) ?? 0),
                    );
                  }).toList(),
                ),
              if (actions.isNotEmpty) procurementDetailActions(actions),
            ],
          );
        },
      ),
    );
  }
}

class ProcurementStockIssuesPage extends StatefulWidget {
  const ProcurementStockIssuesPage({super.key, required this.api});

  final ProcurementApi api;

  @override
  State<ProcurementStockIssuesPage> createState() =>
      _ProcurementStockIssuesPageState();
}

class _ProcurementStockIssuesPageState extends State<ProcurementStockIssuesPage> {
  static const _cacheKey = 'procurement.stock_issues';

  late Future<Map<String, dynamic>> _future;
  Object? _cache;

  @override
  void initState() {
    super.initState();
    procurementPrimeScreen(
      persistKey: _cacheKey,
      setCache: (c) => _cache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.stockIssues(),
    );
  }

  Future<void> _reload() => procurementCachedReload(
        mounted: () => mounted,
        setState: setState,
        fetch: () => widget.api.stockIssues(),
        onData: (d) => _cache = d,
        persistKey: _cacheKey,
      );

  Future<void> _openRaise() async {
    final id = await Navigator.of(context).push<int>(
      MaterialPageRoute(
        builder: (_) => ProcurementRaiseStockIssuePage(api: widget.api),
      ),
    );
    if (!mounted) return;
    _reload();
    if (id != null) {
      await Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => ProcurementStockIssueDetailPage(api: widget.api, id: id),
        ),
      );
      if (mounted) _reload();
    }
  }

  Future<void> _openDetail(int id) async {
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ProcurementStockIssueDetailPage(api: widget.api, id: id),
      ),
    );
    if (mounted) _reload();
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Stock issues',
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _openRaise,
        icon: const Icon(Icons.add),
        label: const Text('Raise issue'),
      ),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(
            snap,
            _cache as dynamic,
            persistKey: _cacheKey,
          );
          if (tichShowLoadingSpinner(snap, merged)) {
            return procurementLoadingBody(onRefresh: () async => _reload());
          }
          if (merged == null && snap.hasError) {
            return Center(child: Text(tichDescribeError(snap.error)));
          }
          final items = (merged as Map<String, dynamic>)['items'] as List? ?? [];
          if (items.isEmpty) {
            return Center(
              child: Text(
                'No stock issues yet. Tap Raise issue.',
                textAlign: TextAlign.center,
                style: tichUi(color: TichColors.neutralMuted),
              ),
            );
          }
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView.separated(
              padding: const EdgeInsets.all(12),
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (context, i) {
                final row = Map<String, dynamic>.from(items[i] as Map);
                final id = (row['id'] as num).toInt();
                return TichCard(
                  onTap: () => _openDetail(id),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        (row['item_name'] ?? 'Item').toString(),
                        style: TichTheme.display(fontSize: 15),
                      ),
                      Text(
                        '${row['department']} · Qty ${row['quantity']}',
                        style: tichUi(fontSize: 13, color: TichColors.neutralMuted),
                      ),
                      const SizedBox(height: 4),
                      procurementStatusChip((row['approval_status'] ?? '').toString()),
                    ],
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}

class ProcurementStockIssueDetailPage extends StatefulWidget {
  const ProcurementStockIssueDetailPage({
    super.key,
    required this.api,
    required this.id,
  });

  final ProcurementApi api;
  final int id;

  @override
  State<ProcurementStockIssueDetailPage> createState() =>
      _ProcurementStockIssueDetailPageState();
}

class _ProcurementStockIssueDetailPageState
    extends State<ProcurementStockIssueDetailPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _cache;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    procurementPrimeScreen(
      persistKey: 'procurement.stockIssue.${widget.id}',
      setCache: (c) => _cache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.stockIssue(widget.id),
    );
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Stock issue',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(
                  snap,
                  _cache as dynamic,
                  persistKey: 'procurement.stockIssue.${widget.id}',
                );
                if (tichShowLoadingSpinner(snap, merged)) {
                  return procurementLoadingBody();
                }
                if (merged == null && snap.hasError) {
                  return Center(child: Text(tichDescribeError(snap.error)));
                }
                final data = merged! as Map<String, dynamic>;
          final r = data;
          final canApprove = r['can_approve'] == true;
          final status = (r['approval_status'] ?? '').toString();
          return ProcurementDetailBody(
            children: [
              procurementDetailHero(
                title: (r['item_name'] ?? 'Stock issue').toString(),
                eyebrow: (r['department'] ?? '').toString(),
                statusLabel: status,
                metricLabel: 'Total cost',
                metricValue: _kes.format((r['total_cost'] as num?) ?? 0),
              ),
              procurementDetailSection('Issue details', [
                procurementDetailField('Department', r['department']),
                procurementDetailField('Quantity', r['quantity']),
                procurementDetailField(
                  'Unit cost',
                  _kes.format((r['unit_cost'] as num?) ?? 0),
                ),
                procurementDetailField('Reason', r['reason']),
                procurementDetailField('Requested by', r['requested_by']),
                procurementDetailField('Created', r['created_at']),
              ]),
              if (canApprove)
                procurementDetailActions([
                  FilledButton(
                    onPressed: _busy
                        ? null
                        : () async {
                            setState(() => _busy = true);
                            try {
                              await widget.api.approveStockIssue(widget.id);
                              if (context.mounted) {
                                procurementUpdateFuture(
                                  setState,
                                  (f) {
        _future = f;
      },
                                  () => widget.api.stockIssue(widget.id),
      mounted: () => mounted,
      onData: (d) => _cache = d,
    );
                                procurementNotifyCountsChanged();
                              }
                            } catch (e) {
                              if (context.mounted) {
                                procurementShowError(context, e);
                              }
                            } finally {
                              if (mounted) setState(() => _busy = false);
                            }
                          },
                    child: _busy
                        ? const AdaptiveProgress()
                        : const Text('Approve issue'),
                  ),
                ]),
            ],
          );
        },
      ),
    );
  }
}

class ProcurementGrnsPage extends StatefulWidget {
  const ProcurementGrnsPage({super.key, required this.api});

  final ProcurementApi api;

  @override
  State<ProcurementGrnsPage> createState() => _ProcurementGrnsPageState();
}

class _ProcurementGrnsPageState extends State<ProcurementGrnsPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _cache;

  @override
  void initState() {
    super.initState();
    procurementPrimeScreen(
      persistKey: 'procurement.grns',
      setCache: (c) => _cache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.grns(),
    );
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'GRNs',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(
                  snap,
                  _cache as dynamic,
                  persistKey: 'procurement.grns',
                );
                if (tichShowLoadingSpinner(snap, merged)) {
                  return procurementLoadingBody();
                }
                if (merged == null && snap.hasError) {
                  return Center(child: Text(tichDescribeError(snap.error)));
                }
                final data = merged! as Map<String, dynamic>;
          final items = data['items'] as List? ?? [];
          return ListView.separated(
            padding: const EdgeInsets.all(12),
            itemCount: items.length,
            separatorBuilder: (_, __) => const SizedBox(height: 8),
            itemBuilder: (context, i) {
              final row = Map<String, dynamic>.from(items[i] as Map);
              final id = (row['id'] as num).toInt();
              return TichCard(
                onTap: () => Navigator.of(context).push(
                  MaterialPageRoute(
                    builder: (_) => ProcurementGrnDetailPage(api: widget.api, id: id),
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      (row['grn_number'] ?? '').toString(),
                      style: TichTheme.display(fontSize: 15),
                    ),
                    Text(
                      'PO ${row['po_number']} · ${row['supplier']}',
                      style: tichUi(fontSize: 13, color: TichColors.neutralMuted),
                    ),
                    procurementStatusChip((row['inspection_status'] ?? '').toString()),
                  ],
                ),
              );
            },
          );
        },
      ),
    );
  }
}

class ProcurementGrnDetailPage extends StatefulWidget {
  const ProcurementGrnDetailPage({super.key, required this.api, required this.id});

  final ProcurementApi api;
  final int id;

  @override
  State<ProcurementGrnDetailPage> createState() => _ProcurementGrnDetailPageState();
}

class _ProcurementGrnDetailPageState extends State<ProcurementGrnDetailPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _cache;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    procurementPrimeScreen(
      persistKey: 'procurement.grn.${widget.id}',
      setCache: (c) => _cache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.grn(widget.id),
    );
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'GRN',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(
                  snap,
                  _cache as dynamic,
                  persistKey: 'procurement.grn.${widget.id}',
                );
                if (tichShowLoadingSpinner(snap, merged)) {
                  return procurementLoadingBody();
                }
                if (merged == null && snap.hasError) {
                  return Center(child: Text(tichDescribeError(snap.error)));
                }
                final data = merged! as Map<String, dynamic>;
          final r = data;
          final items = r['items'] as List? ?? [];
          return ProcurementDetailBody(
            children: [
              procurementDetailHero(
                title: (r['grn_number'] ?? '').toString(),
                eyebrow: 'PO ${r['po_number'] ?? '-'}',
                statusLabel: (r['inspection_status'] ?? '').toString(),
              ),
              procurementDetailSection('Receipt', [
                procurementDetailField('Supplier', r['supplier']),
                procurementDetailField('Received date', r['received_date']),
                procurementDetailField('Received by', r['received_by']),
                if ((r['inspection_notes'] ?? '').toString().isNotEmpty)
                  procurementDetailNote((r['inspection_notes'] ?? '').toString()),
              ]),
              if (items.isNotEmpty)
                procurementDetailSection(
                  'Items received',
                  items.map((raw) {
                    final line = Map<String, dynamic>.from(raw as Map);
                    return procurementLineItemTile(
                      title: (line['item_name'] ?? '').toString(),
                      subtitle:
                          'Qty ${line['quantity_received']} · ${line['condition'] ?? ''}',
                      trailing: _kes.format((line['unit_cost'] as num?) ?? 0),
                    );
                  }).toList(),
                ),
              if (r['can_complete_inspection'] == true)
                procurementDetailActions([
                  FilledButton(
                    onPressed: _busy
                        ? null
                        : () async {
                            final notes = await procurementPromptText(
                              context,
                              title: 'Inspection notes',
                              hint: 'Optional notes',
                              required: false,
                            );
                            if (!context.mounted) return;
                            setState(() => _busy = true);
                            try {
                              await widget.api.completeGrnInspection(
                                widget.id,
                                inspectionNotes: notes,
                              );
                              if (context.mounted) {
                                procurementUpdateFuture(
                                  setState,
                                  (f) {
        _future = f;
      },
                                  () => widget.api.grn(widget.id),
      mounted: () => mounted,
      onData: (d) => _cache = d,
    );
                                procurementNotifyCountsChanged();
                              }
                            } catch (e) {
                              if (context.mounted) {
                                procurementShowError(context, e);
                              }
                            } finally {
                              if (mounted) setState(() => _busy = false);
                            }
                          },
                    child: _busy
                        ? const AdaptiveProgress()
                        : const Text('Complete inspection'),
                  ),
                ]),
            ],
          );
        },
      ),
    );
  }
}

class ProcurementStockAlertsPage extends StatefulWidget {
  const ProcurementStockAlertsPage({super.key, required this.api});

  final ProcurementApi api;

  @override
  State<ProcurementStockAlertsPage> createState() =>
      _ProcurementStockAlertsPageState();
}

class _ProcurementStockAlertsPageState extends State<ProcurementStockAlertsPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _cache;

  @override
  void initState() {
    super.initState();
    procurementPrimeScreen(
      persistKey: 'procurement.stock_alerts',
      setCache: (c) => _cache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.stockAlerts(),
    );
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Stock alerts',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(
                  snap,
                  _cache as dynamic,
                  persistKey: 'procurement.stock_alerts',
                );
                if (tichShowLoadingSpinner(snap, merged)) {
                  return procurementLoadingBody();
                }
                if (merged == null && snap.hasError) {
                  return Center(child: Text(tichDescribeError(snap.error)));
                }
                final data = merged! as Map<String, dynamic>;
          final items = data['items'] as List? ?? [];
          return ListView.separated(
            padding: const EdgeInsets.all(12),
            itemCount: items.length,
            separatorBuilder: (_, __) => const SizedBox(height: 8),
            itemBuilder: (context, i) {
              final row = Map<String, dynamic>.from(items[i] as Map);
              final id = (row['id'] as num).toInt();
              return TichCard(
                onTap: () => Navigator.of(context).push(
                  MaterialPageRoute(
                    builder: (_) =>
                        ProcurementStockAlertDetailPage(api: widget.api, id: id),
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      (row['item_name'] ?? 'Alert').toString(),
                      style: TichTheme.display(fontSize: 15),
                    ),
                    Text(
                      'Stock ${row['current_stock']} / reorder ${row['reorder_level']}',
                      style: tichUi(fontSize: 13, color: TichColors.neutralMuted),
                    ),
                  ],
                ),
              );
            },
          );
        },
      ),
    );
  }
}

class ProcurementStockAlertDetailPage extends StatefulWidget {
  const ProcurementStockAlertDetailPage({
    super.key,
    required this.api,
    required this.id,
  });

  final ProcurementApi api;
  final int id;

  @override
  State<ProcurementStockAlertDetailPage> createState() =>
      _ProcurementStockAlertDetailPageState();
}

class _ProcurementStockAlertDetailPageState
    extends State<ProcurementStockAlertDetailPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _cache;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    procurementPrimeScreen(
      persistKey: 'procurement.stockAlert.${widget.id}',
      setCache: (c) => _cache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.stockAlert(widget.id),
    );
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Stock alert',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(
                  snap,
                  _cache as dynamic,
                  persistKey: 'procurement.stockAlert.${widget.id}',
                );
                if (tichShowLoadingSpinner(snap, merged)) {
                  return procurementLoadingBody();
                }
                if (merged == null && snap.hasError) {
                  return Center(child: Text(tichDescribeError(snap.error)));
                }
                final data = merged! as Map<String, dynamic>;
          final r = data;
          final reqId = (r['requisition_id'] as num?)?.toInt();
          final actions = <Widget>[];
          if (reqId != null) {
            actions.add(
              OutlinedButton(
                onPressed: () => Navigator.of(context).push(
                  MaterialPageRoute(
                    builder: (_) => ProcurementRequisitionDetailPage(
                      api: widget.api,
                      id: reqId,
                    ),
                  ),
                ),
                child: Text('Open requisition #$reqId'),
              ),
            );
          }
          if (r['can_auto_reorder'] == true) {
            actions.add(
              FilledButton(
                onPressed: _busy
                    ? null
                    : () async {
                        setState(() => _busy = true);
                        try {
                          await widget.api.autoReorderStockAlert(widget.id);
                          if (context.mounted) {
                            procurementUpdateFuture(
                              setState,
                              (f) {
        _future = f;
      },
                              () => widget.api.stockAlert(widget.id),
      mounted: () => mounted,
      onData: (d) => _cache = d,
    );
                            procurementNotifyCountsChanged();
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text('Draft requisition created.'),
                              ),
                            );
                          }
                        } catch (e) {
                          if (context.mounted) {
                            procurementShowError(context, e);
                          }
                        } finally {
                          if (mounted) setState(() => _busy = false);
                        }
                      },
                child: _busy
                    ? const AdaptiveProgress()
                    : const Text('Auto-draft requisition'),
              ),
            );
          }

          return ProcurementDetailBody(
            children: [
              procurementDetailHero(
                title: (r['item_name'] ?? 'Stock alert').toString(),
                statusLabel: (r['alert_type'] ?? '').toString(),
                metricLabel: 'Stock / reorder',
                metricValue:
                    '${r['current_stock']} / ${r['reorder_level']}',
              ),
              procurementDetailSection('Alert', [
                procurementDetailField('Recommended qty', r['recommended_quantity']),
                if ((r['notes'] ?? '').toString().isNotEmpty)
                  procurementDetailNote((r['notes'] ?? '').toString()),
              ]),
              if (actions.isNotEmpty) procurementDetailActions(actions),
            ],
          );
        },
      ),
    );
  }
}

class ProcurementAssetsPage extends StatefulWidget {
  const ProcurementAssetsPage({super.key, required this.api});

  final ProcurementApi api;

  @override
  State<ProcurementAssetsPage> createState() => _ProcurementAssetsPageState();
}

class _ProcurementAssetsPageState extends State<ProcurementAssetsPage> {
  final _search = TextEditingController();
  late Future<Map<String, dynamic>> _future;
  Object? _cache;

  @override
  void initState() {
    super.initState();
    procurementPrimeScreen(
      persistKey: 'procurement.assets',
      setCache: (c) => _cache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.assets(),
    );
  }

  void _runSearch() {
    procurementUpdateFuture(
      setState,
      (f) {
        _future = f;
      },
      () => widget.api.assets(search: _search.text.trim()),
      mounted: () => mounted,
      onData: (d) => _cache = d,
    );
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Assets',
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 12, 12, 0),
            child: TextField(
              controller: _search,
              decoration: InputDecoration(
                hintText: 'Search asset number or name',
                suffixIcon: IconButton(
                  icon: const Icon(Icons.search),
                  onPressed: _runSearch,
                ),
              ),
              onSubmitted: (_) => _runSearch(),
            ),
          ),
          Expanded(
            child: FutureBuilder<Map<String, dynamic>>(
              future: _future,
              builder: (context, snap) {
                final merged = tichMergeSnapshot(
                  snap,
                  _cache as dynamic,
                  persistKey: 'procurement.assets',
                );
                if (tichShowLoadingSpinner(snap, merged)) {
                  return procurementLoadingBody();
                }
                if (merged == null && snap.hasError) {
                  return Center(child: Text(tichDescribeError(snap.error)));
                }
                final data = merged! as Map<String, dynamic>;
                final items = data['items'] as List? ?? [];
                return ListView.separated(
                  padding: const EdgeInsets.all(12),
                  itemCount: items.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 8),
                  itemBuilder: (context, i) {
                    final row = Map<String, dynamic>.from(items[i] as Map);
                    final id = (row['id'] as num).toInt();
                    return TichCard(
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) =>
                              ProcurementAssetDetailPage(api: widget.api, id: id),
                        ),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            (row['asset_number'] ?? '').toString(),
                            style: TichTheme.display(fontSize: 15),
                          ),
                          Text(
                            (row['asset_name'] ?? '').toString(),
                            style: tichUi(
                              fontSize: 13,
                              color: TichColors.neutralMuted,
                            ),
                          ),
                          if ((row['location'] ?? '').toString().isNotEmpty)
                            Text(
                              'Location: ${row['location']}',
                              style: tichUi(fontSize: 12),
                            ),
                        ],
                      ),
                    );
                  },
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class ProcurementAssetDetailPage extends StatelessWidget {
  const ProcurementAssetDetailPage({
    super.key,
    required this.api,
    required this.id,
  });

  final ProcurementApi api;
  final int id;

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Asset',
      body: FutureBuilder<Map<String, dynamic>>(
        future: api.asset(id),
        builder: (context, snap) {
          if (!snap.hasData) return procurementLoadingBody();
          if (snap.hasError) {
            return Center(child: Text(tichDescribeError(snap.error)));
          }
          final r = snap.data!;
          return ProcurementDetailBody(
            children: [
              procurementDetailHero(
                title: (r['asset_number'] ?? '').toString(),
                eyebrow: (r['asset_name'] ?? '').toString(),
                statusLabel: (r['status'] ?? '').toString(),
              ),
              procurementDetailSection('Asset record', [
                procurementDetailField('Category', r['category']),
                procurementDetailField('Location', r['location']),
                procurementDetailField('Custodian', r['custodian']),
                procurementDetailField('Tag number', r['tag_number']),
                procurementDetailField('Serial number', r['serial_number']),
                procurementDetailField('Condition', r['condition']),
              ]),
            ],
          );
        },
      ),
    );
  }
}
