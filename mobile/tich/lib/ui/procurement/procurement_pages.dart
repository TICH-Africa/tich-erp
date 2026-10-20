import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/procurement_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_widgets.dart';
import 'procurement_helpers.dart';

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

  @override
  void initState() {
    super.initState();
    _future = widget.api.requisitions();
  }

  void _reload() => setState(() => _future = widget.api.requisitions());

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Requisitions',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done && !snap.hasData) {
            return const Center(child: AdaptiveProgress());
          }
          if (snap.hasError) {
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
          final items = snap.data?['items'] as List? ?? [];
          if (items.isEmpty) {
            return const Center(child: Text('No requisitions.'));
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
                return TichCard(
                  onTap: () => Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) =>
                          ProcurementRequisitionDetailPage(api: widget.api, id: id),
                    ),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        (r['requisition_number'] ?? '').toString(),
                        style: TichTheme.display(fontSize: 15),
                      ),
                      Text(
                        (r['requested_item'] ?? '').toString(),
                        style: tichUi(fontSize: 13, color: TichColors.neutralMuted),
                      ),
                      const SizedBox(height: 6),
                      Row(
                        children: [
                          procurementStatusChip((r['status_label'] ?? '').toString()),
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
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _future = widget.api.requisition(widget.id);
  }

  Future<void> _act(Future<Map<String, dynamic>> Function() call) async {
    setState(() => _busy = true);
    try {
      await call();
      if (mounted) {
        setState(() => _future = widget.api.requisition(widget.id));
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
          if (!snap.hasData) {
            return const Center(child: AdaptiveProgress());
          }
          final r = snap.data!;
          final canApprove = r['can_approve'] == true;
          final canSubmit = r['can_submit'] == true;
          final canReject = r['can_reject'] == true;
          final budget = Map<String, dynamic>.from(r['budget_check'] as Map? ?? {});
          final budgetPassed = budget['passed'] == true;
          final lines = r['line_items'] as List? ?? [];
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Text(
                (r['requisition_number'] ?? '').toString(),
                style: TichTheme.display(fontSize: 20),
              ),
              const SizedBox(height: 4),
              procurementStatusChip((r['status_label'] ?? '').toString()),
              const SizedBox(height: 12),
              procurementDetailRow('Department', r['department']),
              procurementDetailRow('Estimated', _kes.format((r['estimated_cost'] as num?) ?? 0)),
              procurementDetailRow('Requested', r['requested_item']),
              if ((r['justification'] ?? '').toString().isNotEmpty)
                procurementDetailRow('Justification', r['justification']),
              if ((budget['message'] ?? '').toString().isNotEmpty)
                Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: Text(
                    budget['message'].toString(),
                    style: tichUi(
                      fontSize: 13,
                      color: budgetPassed ? TichColors.greenDark : TichColors.error,
                    ),
                  ),
                ),
              if (lines.isNotEmpty) ...[
                const SizedBox(height: 12),
                Text('Line items', style: TichTheme.display(fontSize: 14)),
                ...lines.map((raw) {
                  final line = Map<String, dynamic>.from(raw as Map);
                  return Padding(
                    padding: const EdgeInsets.only(top: 6),
                    child: Text(
                      '${line['item']} × ${line['quantity']} @ ${_kes.format((line['unit_price'] as num?) ?? 0)}',
                      style: tichUi(fontSize: 13),
                    ),
                  );
                }),
              ],
              if (canSubmit) ...[
                const SizedBox(height: 20),
                FilledButton(
                  onPressed: _busy
                      ? null
                      : () => _act(() => widget.api.submitRequisition(widget.id)),
                  child: const Text('Submit for approval'),
                ),
              ],
              if (canApprove) ...[
                const SizedBox(height: 20),
                FilledButton(
                  onPressed: _busy
                      ? null
                      : () => _act(() => widget.api.approveRequisition(widget.id)),
                  child: _busy
                      ? const AdaptiveProgress()
                      : const Text('Approve'),
                ),
              ],
              if (canReject) ...[
                const SizedBox(height: 8),
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
              ],
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
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.stockIssues();
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Stock issues',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (!snap.hasData) return const Center(child: AdaptiveProgress());
          final items = snap.data?['items'] as List? ?? [];
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
                        ProcurementStockIssueDetailPage(api: widget.api, id: id),
                  ),
                ),
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
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _future = widget.api.stockIssue(widget.id);
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Stock issue',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (!snap.hasData) return const Center(child: AdaptiveProgress());
          final r = snap.data!;
          final canApprove = r['can_approve'] == true;
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Text(
                (r['item_name'] ?? '').toString(),
                style: TichTheme.display(fontSize: 18),
              ),
              procurementDetailRow('Department', r['department']),
              procurementDetailRow('Quantity', '${r['quantity']}'),
              procurementDetailRow('Total', _kes.format((r['total_cost'] as num?) ?? 0)),
              procurementDetailRow('Reason', r['reason']),
              if (canApprove)
                FilledButton(
                  onPressed: _busy
                      ? null
                      : () async {
                          setState(() => _busy = true);
                          try {
                            await widget.api.approveStockIssue(widget.id);
                            if (context.mounted) {
                              setState(
                                () => _future = widget.api.stockIssue(widget.id),
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

  @override
  void initState() {
    super.initState();
    _future = widget.api.grns();
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'GRNs',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (!snap.hasData) return const Center(child: AdaptiveProgress());
          final items = snap.data?['items'] as List? ?? [];
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
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _future = widget.api.grn(widget.id);
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'GRN',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (!snap.hasData) return const Center(child: AdaptiveProgress());
          final r = snap.data!;
          final items = r['items'] as List? ?? [];
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Text(
                (r['grn_number'] ?? '').toString(),
                style: TichTheme.display(fontSize: 18),
              ),
              procurementDetailRow('PO', r['po_number']),
              procurementDetailRow('Supplier', r['supplier']),
              procurementDetailRow('Received', r['received_date']),
              procurementDetailRow('Inspection', r['inspection_status']),
              if (items.isNotEmpty) ...[
                const SizedBox(height: 12),
                Text('Items received', style: TichTheme.display(fontSize: 14)),
                ...items.map((raw) {
                  final line = Map<String, dynamic>.from(raw as Map);
                  return Padding(
                    padding: const EdgeInsets.only(top: 6),
                    child: Text(
                      '${line['item_name']} · ${line['quantity_received']} × ${_kes.format((line['unit_cost'] as num?) ?? 0)} (${line['condition']})',
                      style: tichUi(fontSize: 13),
                    ),
                  );
                }),
              ],
              if (r['can_complete_inspection'] == true)
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
                              setState(() => _future = widget.api.grn(widget.id));
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

  @override
  void initState() {
    super.initState();
    _future = widget.api.stockAlerts();
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Stock alerts',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (!snap.hasData) return const Center(child: AdaptiveProgress());
          final items = snap.data?['items'] as List? ?? [];
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
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _future = widget.api.stockAlert(widget.id);
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Stock alert',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (!snap.hasData) return const Center(child: AdaptiveProgress());
          final r = snap.data!;
          final reqId = (r['requisition_id'] as num?)?.toInt();
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Text(
                (r['item_name'] ?? '').toString(),
                style: TichTheme.display(fontSize: 18),
              ),
              procurementDetailRow('Type', r['alert_type']),
              procurementDetailRow('Current stock', '${r['current_stock']}'),
              procurementDetailRow('Reorder level', '${r['reorder_level']}'),
              procurementDetailRow('Recommended qty', '${r['recommended_quantity']}'),
              if ((r['notes'] ?? '').toString().isNotEmpty)
                procurementDetailRow('Notes', r['notes']),
              if (reqId != null) ...[
                procurementDetailRow('Linked requisition', '#$reqId'),
                OutlinedButton(
                  onPressed: () => Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => ProcurementRequisitionDetailPage(
                        api: widget.api,
                        id: reqId,
                      ),
                    ),
                  ),
                  child: const Text('Open requisition'),
                ),
                const SizedBox(height: 8),
              ],
              if (r['can_auto_reorder'] == true)
                FilledButton(
                  onPressed: _busy
                      ? null
                      : () async {
                          setState(() => _busy = true);
                          try {
                            await widget.api.autoReorderStockAlert(widget.id);
                            if (context.mounted) {
                              setState(
                                () => _future = widget.api.stockAlert(widget.id),
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

  @override
  void initState() {
    super.initState();
    _future = widget.api.assets();
  }

  void _runSearch() {
    setState(() => _future = widget.api.assets(search: _search.text.trim()));
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
                if (!snap.hasData) {
                  return const Center(child: AdaptiveProgress());
                }
                final items = snap.data?['items'] as List? ?? [];
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
          if (!snap.hasData) return const Center(child: AdaptiveProgress());
          if (snap.hasError) {
            return Center(child: Text(tichDescribeError(snap.error)));
          }
          final r = snap.data!;
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Text(
                (r['asset_number'] ?? '').toString(),
                style: TichTheme.display(fontSize: 20),
              ),
              procurementDetailRow('Name', r['asset_name']),
              procurementDetailRow('Category', r['category']),
              procurementDetailRow('Status', r['status']),
              procurementDetailRow('Location', r['location']),
              procurementDetailRow('Custodian', r['custodian']),
              procurementDetailRow('Tag', r['tag_number']),
              procurementDetailRow('Serial', r['serial_number']),
              procurementDetailRow('Condition', r['condition']),
            ],
          );
        },
      ),
    );
  }
}
