import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../adaptive/adaptive.dart';
import '../../data/procurement_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_widgets.dart';
import 'procurement_helpers.dart';

final _kes = NumberFormat.currency(symbol: 'KES ', decimalDigits: 0);

class ProcurementSuppliersPage extends StatefulWidget {
  const ProcurementSuppliersPage({super.key, required this.api});

  final ProcurementApi api;

  @override
  State<ProcurementSuppliersPage> createState() => _ProcurementSuppliersPageState();
}

class _ProcurementSuppliersPageState extends State<ProcurementSuppliersPage> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.suppliers();
  }

  void _reload() => setState(() => _future = widget.api.suppliers());

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Suppliers',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (!snap.hasData) return const Center(child: AdaptiveProgress());
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
          if (items.isEmpty) return const Center(child: Text('No suppliers.'));
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
                          ProcurementSupplierDetailPage(api: widget.api, id: id),
                    ),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        (r['supplier_name'] ?? '').toString(),
                        style: TichTheme.display(fontSize: 15),
                      ),
                      Text(
                        (r['supplier_code'] ?? '').toString(),
                        style: tichUi(fontSize: 13, color: TichColors.neutralMuted),
                      ),
                      const SizedBox(height: 6),
                      procurementStatusChip((r['compliance_status'] ?? '').toString()),
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

class ProcurementSupplierDetailPage extends StatefulWidget {
  const ProcurementSupplierDetailPage({
    super.key,
    required this.api,
    required this.id,
  });

  final ProcurementApi api;
  final int id;

  @override
  State<ProcurementSupplierDetailPage> createState() =>
      _ProcurementSupplierDetailPageState();
}

class _ProcurementSupplierDetailPageState extends State<ProcurementSupplierDetailPage> {
  late Future<Map<String, dynamic>> _future;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _future = widget.api.supplier(widget.id);
  }

  Future<void> _verify(String complianceStatus) async {
    setState(() => _busy = true);
    try {
      await widget.api.verifySupplier(
        widget.id,
        complianceStatus: complianceStatus,
        riskRating: complianceStatus == 'approved' ? 'low' : null,
      );
      if (mounted) {
        setState(() => _future = widget.api.supplier(widget.id));
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
      title: 'Supplier',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (!snap.hasData) return const Center(child: AdaptiveProgress());
          final r = snap.data!;
          final canVerify = r['can_verify'] == true;
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Text(
                (r['supplier_name'] ?? '').toString(),
                style: TichTheme.display(fontSize: 20),
              ),
              procurementStatusChip((r['compliance_status'] ?? '').toString()),
              const SizedBox(height: 12),
              procurementDetailRow('Code', r['supplier_code']),
              procurementDetailRow('Contact', r['contact_person']),
              procurementDetailRow('Email', r['email']),
              procurementDetailRow('Phone', r['phone']),
              procurementDetailRow('Category', r['supplier_category']),
              procurementDetailRow('Risk', r['risk_rating']),
              procurementDetailRow('Active', r['is_active'] == true ? 'Yes' : 'No'),
              procurementDetailRow('Blacklist', r['blacklist_status']),
              if (canVerify) ...[
                const SizedBox(height: 16),
                FilledButton(
                  onPressed: _busy ? null : () => _verify('approved'),
                  child: const Text('Approve compliance'),
                ),
                const SizedBox(height: 8),
                OutlinedButton(
                  onPressed: _busy ? null : () => _verify('under_review'),
                  child: const Text('Mark under review'),
                ),
                const SizedBox(height: 8),
                OutlinedButton(
                  onPressed: _busy ? null : () => _verify('rejected'),
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

class ProcurementRfqsPage extends StatefulWidget {
  const ProcurementRfqsPage({super.key, required this.api});

  final ProcurementApi api;

  @override
  State<ProcurementRfqsPage> createState() => _ProcurementRfqsPageState();
}

class _ProcurementRfqsPageState extends State<ProcurementRfqsPage> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.rfqs();
  }

  void _reload() => setState(() => _future = widget.api.rfqs());

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'RFQs',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (!snap.hasData) return const Center(child: AdaptiveProgress());
          final items = snap.data?['items'] as List? ?? [];
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
                      builder: (_) => ProcurementRfqDetailPage(api: widget.api, id: id),
                    ),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        (r['rfq_number'] ?? '').toString(),
                        style: TichTheme.display(fontSize: 15),
                      ),
                      Text(
                        (r['item_description'] ?? '').toString(),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: tichUi(fontSize: 13, color: TichColors.neutralMuted),
                      ),
                      const SizedBox(height: 6),
                      Row(
                        children: [
                          procurementStatusChip((r['status'] ?? '').toString()),
                          if ((r['approval_status'] ?? '').toString().isNotEmpty) ...[
                            const SizedBox(width: 8),
                            procurementStatusChip((r['approval_status'] ?? '').toString()),
                          ],
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

class ProcurementRfqDetailPage extends StatefulWidget {
  const ProcurementRfqDetailPage({
    super.key,
    required this.api,
    required this.id,
  });

  final ProcurementApi api;
  final int id;

  @override
  State<ProcurementRfqDetailPage> createState() => _ProcurementRfqDetailPageState();
}

class _ProcurementRfqDetailPageState extends State<ProcurementRfqDetailPage> {
  late Future<Map<String, dynamic>> _future;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _future = widget.api.rfq(widget.id);
  }

  Future<void> _act(Future<Map<String, dynamic>> Function() call) async {
    setState(() => _busy = true);
    try {
      await call();
      if (mounted) {
        setState(() => _future = widget.api.rfq(widget.id));
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
      title: 'RFQ',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (!snap.hasData) return const Center(child: AdaptiveProgress());
          final r = snap.data!;
          final canApprove = r['can_approve_award'] == true;
          final canReject = r['can_reject_award'] == true;
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Text(
                (r['rfq_number'] ?? '').toString(),
                style: TichTheme.display(fontSize: 20),
              ),
              procurementStatusChip((r['status'] ?? '').toString()),
              const SizedBox(height: 12),
              procurementDetailRow('Requisition', r['requisition_number']),
              procurementDetailRow('Item', r['item_description']),
              procurementDetailRow('Quantity', r['quantity']),
              procurementDetailRow('Deadline', r['submission_deadline']),
              procurementDetailRow('Awarded supplier', r['awarded_supplier']),
              if (r['awarded_amount'] != null)
                procurementDetailRow('Award amount', _kes.format((r['awarded_amount'] as num?) ?? 0)),
              procurementDetailRow('Award approval', r['approval_status']),
              if (canApprove) ...[
                const SizedBox(height: 16),
                FilledButton(
                  onPressed: _busy
                      ? null
                      : () async {
                          final notes = await procurementPromptText(
                            context,
                            title: 'Approve award',
                            hint: 'Optional notes',
                            required: false,
                          );
                          if (notes == null) return;
                          await _act(() => widget.api.approveRfqAward(
                                widget.id,
                                notes: notes.isEmpty ? null : notes,
                              ));
                        },
                  child: const Text('Approve award'),
                ),
              ],
              if (canReject) ...[
                const SizedBox(height: 8),
                OutlinedButton(
                  onPressed: _busy
                      ? null
                      : () async {
                          final notes = await procurementPromptText(
                            context,
                            title: 'Reject award',
                            hint: 'Reason for rejection',
                          );
                          if (notes == null || notes.isEmpty) return;
                          await _act(() =>
                              widget.api.rejectRfqAward(widget.id, notes: notes));
                        },
                  child: const Text('Reject award'),
                ),
              ],
            ],
          );
        },
      ),
    );
  }
}

class ProcurementInventoryPage extends StatefulWidget {
  const ProcurementInventoryPage({super.key, required this.api});

  final ProcurementApi api;

  @override
  State<ProcurementInventoryPage> createState() => _ProcurementInventoryPageState();
}

class _ProcurementInventoryPageState extends State<ProcurementInventoryPage> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.inventoryItems();
  }

  void _reload() => setState(() => _future = widget.api.inventoryItems());

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Inventory',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (!snap.hasData) return const Center(child: AdaptiveProgress());
          final items = snap.data?['items'] as List? ?? [];
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView.separated(
              padding: const EdgeInsets.all(12),
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (context, i) {
                final r = Map<String, dynamic>.from(items[i] as Map);
                final id = (r['id'] as num).toInt();
                final low = r['low_stock'] == true;
                return TichCard(
                  onTap: () => Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) =>
                          ProcurementInventoryItemDetailPage(api: widget.api, id: id),
                    ),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        (r['item_name'] ?? '').toString(),
                        style: TichTheme.display(fontSize: 15),
                      ),
                      Text(
                        '${r['item_code']} · Stock ${r['current_stock']}',
                        style: tichUi(fontSize: 13, color: TichColors.neutralMuted),
                      ),
                      if (low) ...[
                        const SizedBox(height: 6),
                        procurementStatusChip('Low stock'),
                      ],
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

class ProcurementInventoryItemDetailPage extends StatelessWidget {
  const ProcurementInventoryItemDetailPage({
    super.key,
    required this.api,
    required this.id,
  });

  final ProcurementApi api;
  final int id;

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Inventory item',
      body: FutureBuilder<Map<String, dynamic>>(
        future: api.inventoryItem(id),
        builder: (context, snap) {
          if (!snap.hasData) return const Center(child: AdaptiveProgress());
          final r = snap.data!;
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Text(
                (r['item_name'] ?? '').toString(),
                style: TichTheme.display(fontSize: 20),
              ),
              if (r['low_stock'] == true) procurementStatusChip('Low stock'),
              const SizedBox(height: 12),
              procurementDetailRow('Code', r['item_code']),
              procurementDetailRow('Category', r['category']),
              procurementDetailRow('Unit', r['unit_of_measure']),
              procurementDetailRow('Stock', r['current_stock']),
              procurementDetailRow('Reorder level', r['reorder_level']),
              procurementDetailRow('Unit cost', _kes.format((r['unit_cost'] as num?) ?? 0)),
              procurementDetailRow('Location', r['store_location']),
              procurementDetailRow('Supplier', r['supplier']),
              procurementDetailRow('Active', r['is_active'] == true ? 'Yes' : 'No'),
            ],
          );
        },
      ),
    );
  }
}

class ProcurementDisposalsPage extends StatefulWidget {
  const ProcurementDisposalsPage({super.key, required this.api});

  final ProcurementApi api;

  @override
  State<ProcurementDisposalsPage> createState() => _ProcurementDisposalsPageState();
}

class _ProcurementDisposalsPageState extends State<ProcurementDisposalsPage> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.assetDisposals();
  }

  void _reload() => setState(() => _future = widget.api.assetDisposals());

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Disposals',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (!snap.hasData) return const Center(child: AdaptiveProgress());
          final items = snap.data?['items'] as List? ?? [];
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
                          ProcurementDisposalDetailPage(api: widget.api, id: id),
                    ),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        (r['asset_name'] ?? r['asset_number'] ?? 'Asset').toString(),
                        style: TichTheme.display(fontSize: 15),
                      ),
                      Text(
                        (r['disposal_type'] ?? '').toString(),
                        style: tichUi(fontSize: 13, color: TichColors.neutralMuted),
                      ),
                      const SizedBox(height: 6),
                      procurementStatusChip((r['approval_status'] ?? '').toString()),
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

class ProcurementDisposalDetailPage extends StatefulWidget {
  const ProcurementDisposalDetailPage({
    super.key,
    required this.api,
    required this.id,
  });

  final ProcurementApi api;
  final int id;

  @override
  State<ProcurementDisposalDetailPage> createState() =>
      _ProcurementDisposalDetailPageState();
}

class _ProcurementDisposalDetailPageState extends State<ProcurementDisposalDetailPage> {
  late Future<Map<String, dynamic>> _future;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _future = widget.api.assetDisposal(widget.id);
  }

  Future<void> _approve() async {
    setState(() => _busy = true);
    try {
      await widget.api.approveAssetDisposal(widget.id);
      if (mounted) {
        setState(() => _future = widget.api.assetDisposal(widget.id));
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
      title: 'Disposal',
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
                (r['asset_name'] ?? '').toString(),
                style: TichTheme.display(fontSize: 20),
              ),
              procurementStatusChip((r['approval_status'] ?? '').toString()),
              const SizedBox(height: 12),
              procurementDetailRow('Asset #', r['asset_number']),
              procurementDetailRow('Type', r['disposal_type']),
              procurementDetailRow('Status', r['status']),
              procurementDetailRow('Reason', r['reason']),
              procurementDetailRow('Value', _kes.format((r['disposed_value'] as num?) ?? 0)),
              procurementDetailRow('Date', r['disposal_date']),
              procurementDetailRow('Requested by', r['requested_by']),
              if (canApprove) ...[
                const SizedBox(height: 16),
                FilledButton(
                  onPressed: _busy ? null : _approve,
                  child: const Text('Approve disposal'),
                ),
              ],
            ],
          );
        },
      ),
    );
  }
}

class ProcurementAuditsPage extends StatefulWidget {
  const ProcurementAuditsPage({super.key, required this.api});

  final ProcurementApi api;

  @override
  State<ProcurementAuditsPage> createState() => _ProcurementAuditsPageState();
}

class _ProcurementAuditsPageState extends State<ProcurementAuditsPage> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.assetAudits();
  }

  void _reload() => setState(() => _future = widget.api.assetAudits());

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Audits',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (!snap.hasData) return const Center(child: AdaptiveProgress());
          final items = snap.data?['items'] as List? ?? [];
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
                          ProcurementAuditDetailPage(api: widget.api, id: id),
                    ),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        (r['asset_name'] ?? r['asset_number'] ?? 'Asset').toString(),
                        style: TichTheme.display(fontSize: 15),
                      ),
                      Text(
                        'Auditor: ${r['auditor'] ?? '—'}',
                        style: tichUi(fontSize: 13, color: TichColors.neutralMuted),
                      ),
                      const SizedBox(height: 6),
                      procurementStatusChip((r['status'] ?? '').toString()),
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

class ProcurementAuditDetailPage extends StatefulWidget {
  const ProcurementAuditDetailPage({
    super.key,
    required this.api,
    required this.id,
  });

  final ProcurementApi api;
  final int id;

  @override
  State<ProcurementAuditDetailPage> createState() =>
      _ProcurementAuditDetailPageState();
}

class _ProcurementAuditDetailPageState extends State<ProcurementAuditDetailPage> {
  late Future<Map<String, dynamic>> _future;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _future = widget.api.assetAudit(widget.id);
  }

  Future<void> _submitReview() async {
    final result = await showDialog<Map<String, String>>(
      context: context,
      builder: (ctx) => _AuditReviewDialog(),
    );
    if (result == null) return;

    setState(() => _busy = true);
    try {
      await widget.api.reviewAssetAudit(
        widget.id,
        verificationStatus: result['verification_status']!,
        condition: result['condition']!,
        reviewNotes: result['review_notes'],
      );
      if (mounted) {
        setState(() => _future = widget.api.assetAudit(widget.id));
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
      title: 'Audit',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (!snap.hasData) return const Center(child: AdaptiveProgress());
          final r = snap.data!;
          final canReview = r['can_review'] == true;
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Text(
                (r['asset_name'] ?? '').toString(),
                style: TichTheme.display(fontSize: 20),
              ),
              procurementStatusChip((r['status'] ?? '').toString()),
              const SizedBox(height: 12),
              procurementDetailRow('Asset #', r['asset_number']),
              procurementDetailRow('Verification', r['verification_status']),
              procurementDetailRow('Condition', r['condition']),
              procurementDetailRow('Auditor', r['auditor']),
              procurementDetailRow('Reviewer', r['reviewer']),
              procurementDetailRow('Notes', r['notes']),
              procurementDetailRow('Review notes', r['review_notes']),
              if (canReview) ...[
                const SizedBox(height: 16),
                FilledButton(
                  onPressed: _busy ? null : _submitReview,
                  child: const Text('Submit review'),
                ),
              ],
            ],
          );
        },
      ),
    );
  }
}

class _AuditReviewDialog extends StatefulWidget {
  @override
  State<_AuditReviewDialog> createState() => _AuditReviewDialogState();
}

class _AuditReviewDialogState extends State<_AuditReviewDialog> {
  String _verification = 'verified';
  String _condition = 'good';
  final _notes = TextEditingController();

  @override
  void dispose() {
    _notes.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Review audit'),
      content: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            DropdownButtonFormField<String>(
              initialValue: _verification,
              decoration: const InputDecoration(labelText: 'Verification'),
              items: const [
                DropdownMenuItem(value: 'verified', child: Text('Verified')),
                DropdownMenuItem(value: 'missing', child: Text('Missing')),
                DropdownMenuItem(value: 'damaged', child: Text('Damaged')),
                DropdownMenuItem(value: 'transferred', child: Text('Transferred')),
              ],
              onChanged: (v) => setState(() => _verification = v ?? _verification),
            ),
            DropdownButtonFormField<String>(
              initialValue: _condition,
              decoration: const InputDecoration(labelText: 'Condition'),
              items: const [
                DropdownMenuItem(value: 'excellent', child: Text('Excellent')),
                DropdownMenuItem(value: 'good', child: Text('Good')),
                DropdownMenuItem(value: 'fair', child: Text('Fair')),
                DropdownMenuItem(value: 'poor', child: Text('Poor')),
                DropdownMenuItem(value: 'damaged', child: Text('Damaged')),
              ],
              onChanged: (v) => setState(() => _condition = v ?? _condition),
            ),
            TextField(
              controller: _notes,
              maxLines: 3,
              decoration: const InputDecoration(labelText: 'Review notes'),
            ),
          ],
        ),
      ),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancel')),
        FilledButton(
          onPressed: () => Navigator.pop(context, {
            'verification_status': _verification,
            'condition': _condition,
            'review_notes': _notes.text.trim(),
          }),
          child: const Text('Save'),
        ),
      ],
    );
  }
}

class ProcurementPaymentsPage extends StatefulWidget {
  const ProcurementPaymentsPage({super.key, required this.api});

  final ProcurementApi api;

  @override
  State<ProcurementPaymentsPage> createState() => _ProcurementPaymentsPageState();
}

class _ProcurementPaymentsPageState extends State<ProcurementPaymentsPage> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.paymentVerification();
  }

  void _reload() => setState(() => _future = widget.api.paymentVerification());

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Payment & verification',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (!snap.hasData) return const Center(child: AdaptiveProgress());
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
          final data = snap.data!;
          final stats = Map<String, dynamic>.from(data['stats'] as Map? ?? {});
          final summary =
              Map<String, dynamic>.from(data['payment_summary'] as Map? ?? {});
          final invoices = data['invoices'] as List? ?? [];
          final discrepancies = data['discrepancies'] as List? ?? [];
          final payments = data['payments'] as List? ?? [];

          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView(
              padding: const EdgeInsets.all(12),
              children: [
                TichCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Overview', style: TichTheme.display(fontSize: 15)),
                      const SizedBox(height: 8),
                      _metricRow('Pending invoices', stats['pending_invoices']),
                      _metricRow('Matched', stats['matched']),
                      _metricRow('Discrepancies', stats['discrepancies']),
                      _metricRow('Queued for finance', stats['queued_for_finance']),
                      _metricRow('Pending STK', stats['pending_stk']),
                    ],
                  ),
                ),
                const SizedBox(height: 8),
                TichCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Payments', style: TichTheme.display(fontSize: 15)),
                      const SizedBox(height: 8),
                      _metricRow(
                        'Pending',
                        _kes.format((summary['pending'] as num?) ?? 0),
                      ),
                      _metricRow(
                        'Success',
                        _kes.format((summary['success'] as num?) ?? 0),
                      ),
                      _metricRow(
                        'M-Pesa',
                        _kes.format((summary['mpesa'] as num?) ?? 0),
                      ),
                      _metricRow(
                        'Bank',
                        _kes.format((summary['bank'] as num?) ?? 0),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                Text('Invoices', style: TichTheme.display(fontSize: 14)),
                ..._invoiceTiles(invoices),
                const SizedBox(height: 12),
                Text('Open discrepancies', style: TichTheme.display(fontSize: 14)),
                ..._discrepancyTiles(discrepancies),
                const SizedBox(height: 12),
                Text('Recent payments', style: TichTheme.display(fontSize: 14)),
                ..._paymentTiles(payments),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _metricRow(String label, dynamic value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Row(
        children: [
          Expanded(child: Text(label, style: tichUi(fontSize: 13))),
          Text('$value', style: tichUi(fontSize: 13, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }

  List<Widget> _invoiceTiles(List invoices) {
    if (invoices.isEmpty) {
      return [const Padding(padding: EdgeInsets.all(8), child: Text('None'))];
    }
    return invoices.map((raw) {
      final r = Map<String, dynamic>.from(raw as Map);
      return Padding(
        padding: const EdgeInsets.only(top: 8),
        child: TichCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                (r['invoice_number'] ?? '').toString(),
                style: TichTheme.display(fontSize: 14),
              ),
              Text(
                (r['supplier'] ?? '').toString(),
                style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
              ),
              const SizedBox(height: 4),
              Row(
                children: [
                  procurementStatusChip((r['status'] ?? '').toString()),
                  const Spacer(),
                  Text(
                    _kes.format((r['total_amount'] as num?) ?? 0),
                    style: tichUi(fontSize: 12),
                  ),
                ],
              ),
            ],
          ),
        ),
      );
    }).toList();
  }

  List<Widget> _discrepancyTiles(List items) {
    if (items.isEmpty) {
      return [const Padding(padding: EdgeInsets.all(8), child: Text('None'))];
    }
    return items.map((raw) {
      final r = Map<String, dynamic>.from(raw as Map);
      return Padding(
        padding: const EdgeInsets.only(top: 8),
        child: TichCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                (r['invoice_number'] ?? 'Invoice').toString(),
                style: TichTheme.display(fontSize: 14),
              ),
              Text(
                '${r['field_name']} · ${r['supplier'] ?? ''}',
                style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
              ),
              const SizedBox(height: 4),
              procurementStatusChip((r['status'] ?? '').toString()),
              if (r['deviation_amount'] != null)
                Text(
                  'Deviation ${_kes.format((r['deviation_amount'] as num?) ?? 0)}',
                  style: tichUi(fontSize: 12),
                ),
            ],
          ),
        ),
      );
    }).toList();
  }

  List<Widget> _paymentTiles(List items) {
    if (items.isEmpty) {
      return [const Padding(padding: EdgeInsets.all(8), child: Text('None'))];
    }
    return items.map((raw) {
      final r = Map<String, dynamic>.from(raw as Map);
      return Padding(
        padding: const EdgeInsets.only(top: 8),
        child: TichCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                (r['payment_number'] ?? '').toString(),
                style: TichTheme.display(fontSize: 14),
              ),
              Text(
                (r['supplier'] ?? '').toString(),
                style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
              ),
              const SizedBox(height: 4),
              Row(
                children: [
                  procurementStatusChip((r['status'] ?? '').toString()),
                  const Spacer(),
                  Text(
                    _kes.format((r['amount'] as num?) ?? 0),
                    style: tichUi(fontSize: 12),
                  ),
                ],
              ),
            ],
          ),
        ),
      );
    }).toList();
  }
}
