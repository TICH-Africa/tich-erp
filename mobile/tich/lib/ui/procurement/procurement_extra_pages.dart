import 'dart:async';

import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/procurement_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_widgets.dart';
import 'procurement_add_supplier_page.dart';
import 'procurement_helpers.dart';
import 'procurement_request_asset_disposal_page.dart';

final _kes = NumberFormat.currency(symbol: 'KES ', decimalDigits: 0);

class ProcurementSuppliersPage extends StatefulWidget {
  const ProcurementSuppliersPage({super.key, required this.api});

  final ProcurementApi api;

  @override
  State<ProcurementSuppliersPage> createState() => _ProcurementSuppliersPageState();
}

class _ProcurementSuppliersPageState extends State<ProcurementSuppliersPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _cache;
  Timer? _searchDebounce;
  String _query = '';

  @override
  void initState() {
    super.initState();
    procurementPrimeScreen(
      persistKey: 'procurement.suppliers',
      setCache: (c) => _cache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.suppliers(),
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
      () => widget.api.suppliers(
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

  Future<void> _openAdd() async {
    final id = await Navigator.of(context).push<int>(
      MaterialPageRoute(
        builder: (_) => ProcurementAddSupplierPage(api: widget.api),
      ),
    );
    if (!mounted) return;
    _load(search: _query);
    if (id != null) {
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => ProcurementSupplierDetailPage(api: widget.api, id: id),
        ),
      );
    }
  }

  Future<void> _openDetail(int id) async {
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ProcurementSupplierDetailPage(api: widget.api, id: id),
      ),
    );
    if (mounted) _load(search: _query);
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Suppliers',
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _openAdd,
        icon: const Icon(Icons.add),
        label: const Text('Add supplier'),
      ),
      body: TichLiveRefreshBinder(
        onLiveRefresh: () => _load(search: _query),
        child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          ProcurementSearchBar(
            hint: 'Search name, code, email, phone…',
            onQueryChanged: _onSearchChanged,
          ),
          Expanded(
            child: FutureBuilder<Map<String, dynamic>>(
              future: _future,
              builder: (context, snap) {
                final merged = tichMergeSnapshot(
                  snap,
                  _cache as dynamic,
                  persistKey: 'procurement.suppliers',
                );
                if (tichShowLoadingSpinner(snap, merged)) {
                  return procurementLoadingBody();
                }
                if (merged == null && snap.hasError) {
                  return Center(child: Text(tichDescribeError(snap.error)));
                }
                final data = merged! as Map<String, dynamic>;
                if (snap.hasError) {
                  return Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text('$snap.error'),
                        TextButton(
                          onPressed: () => _load(search: _query),
                          child: const Text('Retry'),
                        ),
                      ],
                    ),
                  );
                }
                final items = data['items'] as List? ?? [];
                if (items.isEmpty) {
                  return Center(
                    child: Text(
                      _query.isEmpty
                          ? 'No suppliers yet. Tap Add supplier.'
                          : 'No matches for "$_query".',
                      textAlign: TextAlign.center,
                    ),
                  );
                }
                return RefreshIndicator(
                  onRefresh: () async => _load(search: _query),
                  child: ListView.separated(
                    padding: const EdgeInsets.all(12),
                    itemCount: items.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemBuilder: (context, i) {
                      final r = Map<String, dynamic>.from(items[i] as Map);
                      final id = (r['id'] as num).toInt();
                      return TichCard(
                        onTap: () => _openDetail(id),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              (r['supplier_name'] ?? '').toString(),
                              style: TichTheme.display(fontSize: 15),
                            ),
                            Text(
                              (r['supplier_code'] ?? '').toString(),
                              style: tichUi(
                                fontSize: 13,
                                color: TichColors.neutralMuted,
                              ),
                            ),
                            if ((r['email'] ?? '').toString().isNotEmpty) ...[
                              const SizedBox(height: 2),
                              Text(
                                (r['email'] ?? '').toString(),
                                style: tichUi(
                                  fontSize: 13,
                                  color: TichColors.neutralMuted,
                                ),
                              ),
                            ],
                            const SizedBox(height: 6),
                            procurementStatusChip(
                              (r['compliance_status'] ?? '').toString(),
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
  Object? _cache;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    procurementPrimeScreen(
      persistKey: 'procurement.supplier.${widget.id}',
      setCache: (c) => _cache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.supplier(widget.id),
    );
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
        procurementUpdateFuture(
          setState,
          (f) {
        _future = f;
      },
          () => widget.api.supplier(widget.id),
      mounted: () => mounted,
      onData: (d) => _cache = d,
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
      title: 'Supplier',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(
                  snap,
                  _cache as dynamic,
                  persistKey: 'procurement.supplier.${widget.id}',
                );
                if (tichShowLoadingSpinner(snap, merged)) {
                  return procurementLoadingBody();
                }
                if (merged == null && snap.hasError) {
                  return Center(child: Text(tichDescribeError(snap.error)));
                }
                final data = merged! as Map<String, dynamic>;
          final r = data;
          final canVerify = r['can_verify'] == true;
          return ProcurementDetailBody(
            children: [
              procurementDetailHero(
                title: (r['supplier_name'] ?? '').toString(),
                eyebrow: (r['supplier_code'] ?? '').toString(),
                statusLabel: (r['compliance_status'] ?? '').toString(),
              ),
              procurementDetailSection('Contact', [
                procurementDetailField('Contact person', r['contact_person']),
                procurementDetailField('Email', r['email']),
                procurementDetailField('Phone', r['phone']),
              ]),
              procurementDetailSection('Compliance', [
                procurementDetailField('Category', r['supplier_category']),
                procurementDetailField('Risk rating', r['risk_rating']),
                procurementDetailField('Active', r['is_active'] == true ? 'Yes' : 'No'),
                procurementDetailField('Blacklist', r['blacklist_status']),
              ]),
              if (canVerify)
                procurementDetailActions([
                  FilledButton(
                    onPressed: _busy ? null : () => _verify('approved'),
                    child: const Text('Approve compliance'),
                  ),
                  OutlinedButton(
                    onPressed: _busy ? null : () => _verify('under_review'),
                    child: const Text('Mark under review'),
                  ),
                  OutlinedButton(
                    onPressed: _busy ? null : () => _verify('rejected'),
                    child: const Text('Reject'),
                  ),
                ]),
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
  Object? _cache;

  @override
  void initState() {
    super.initState();
    procurementPrimeScreen(
      persistKey: 'procurement.rfqs',
      setCache: (c) => _cache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.rfqs(),
    );
  }

  void _reload() => procurementUpdateFuture(
        setState,
        (f) {
        _future = f;
      },
        () => widget.api.rfqs(),
      mounted: () => mounted,
      onData: (d) => _cache = d,
    );

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'RFQs',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(
                  snap,
                  _cache as dynamic,
                  persistKey: 'procurement.rfqs',
                );
                if (tichShowLoadingSpinner(snap, merged)) {
                  return procurementLoadingBody();
                }
                if (merged == null && snap.hasError) {
                  return Center(child: Text(tichDescribeError(snap.error)));
                }
                final data = merged! as Map<String, dynamic>;
          final items = data['items'] as List? ?? [];
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
  Object? _cache;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    procurementPrimeScreen(
      persistKey: 'procurement.rfq.${widget.id}',
      setCache: (c) => _cache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.rfq(widget.id),
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
          () => widget.api.rfq(widget.id),
      mounted: () => mounted,
      onData: (d) => _cache = d,
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
      title: 'RFQ',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(
                  snap,
                  _cache as dynamic,
                  persistKey: 'procurement.rfq.${widget.id}',
                );
                if (tichShowLoadingSpinner(snap, merged)) {
                  return procurementLoadingBody();
                }
                if (merged == null && snap.hasError) {
                  return Center(child: Text(tichDescribeError(snap.error)));
                }
                final data = merged! as Map<String, dynamic>;
          final r = data;
          final canApprove = r['can_approve_award'] == true;
          final canReject = r['can_reject_award'] == true;
          final actions = <Widget>[];
          if (canApprove) {
            actions.add(
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
            );
          }
          if (canReject) {
            actions.add(
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
            );
          }

          return ProcurementDetailBody(
            children: [
              procurementDetailHero(
                title: (r['rfq_number'] ?? '').toString(),
                eyebrow: (r['item_description'] ?? '').toString(),
                statusLabel: (r['status'] ?? '').toString(),
                metricLabel: 'Award approval',
                metricValue: (r['approval_status'] ?? '—').toString(),
              ),
              procurementDetailSection('RFQ', [
                procurementDetailField('Requisition', r['requisition_number']),
                procurementDetailField('Quantity', r['quantity']),
                procurementDetailField('Deadline', r['submission_deadline']),
                procurementDetailField('Awarded supplier', r['awarded_supplier']),
                if (r['awarded_amount'] != null)
                  procurementDetailField(
                    'Award amount',
                    _kes.format((r['awarded_amount'] as num?) ?? 0),
                  ),
              ]),
              if (actions.isNotEmpty) procurementDetailActions(actions),
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
  Object? _cache;

  @override
  void initState() {
    super.initState();
    procurementPrimeScreen(
      persistKey: 'procurement.inventory',
      setCache: (c) => _cache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.inventoryItems(),
    );
  }

  void _reload() => procurementUpdateFuture(
        setState,
        (f) {
        _future = f;
      },
        () => widget.api.inventoryItems(),
      mounted: () => mounted,
      onData: (d) => _cache = d,
    );

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Inventory',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(
                  snap,
                  _cache as dynamic,
                  persistKey: 'procurement.inventory',
                );
                if (tichShowLoadingSpinner(snap, merged)) {
                  return procurementLoadingBody();
                }
                if (merged == null && snap.hasError) {
                  return Center(child: Text(tichDescribeError(snap.error)));
                }
                final data = merged! as Map<String, dynamic>;
          final items = data['items'] as List? ?? [];
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
          if (!snap.hasData) return procurementLoadingBody();
          if (snap.hasError) {
            return Center(child: Text(tichDescribeError(snap.error)));
          }
          final r = snap.data!;
          return ProcurementDetailBody(
            children: [
              procurementDetailHero(
                title: (r['item_name'] ?? '').toString(),
                eyebrow: (r['item_code'] ?? '').toString(),
                statusLabel: r['low_stock'] == true ? 'Low stock' : 'In stock',
                metricLabel: 'On hand',
                metricValue: '${r['current_stock']}',
              ),
              procurementDetailSection('Inventory', [
                procurementDetailField('Category', r['category']),
                procurementDetailField('Unit', r['unit_of_measure']),
                procurementDetailField('Reorder level', r['reorder_level']),
                procurementDetailField(
                  'Unit cost',
                  _kes.format((r['unit_cost'] as num?) ?? 0),
                ),
                procurementDetailField('Location', r['store_location']),
                procurementDetailField('Supplier', r['supplier']),
                procurementDetailField('Active', r['is_active'] == true ? 'Yes' : 'No'),
              ]),
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
  Object? _cache;

  @override
  void initState() {
    super.initState();
    _reload();
  }

  void _reload() => procurementUpdateFuture(
        setState,
        (f) {
        _future = f;
      },
        () => widget.api.assetDisposals(),
      mounted: () => mounted,
      onData: (d) => _cache = d,
    );

  Future<void> _openRequest() async {
    final id = await Navigator.of(context).push<int>(
      MaterialPageRoute(
        builder: (_) =>
            ProcurementRequestAssetDisposalPage(api: widget.api),
      ),
    );
    if (!mounted) return;
    _reload();
    if (id != null) {
      await Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => ProcurementDisposalDetailPage(api: widget.api, id: id),
        ),
      );
      if (mounted) _reload();
    }
  }

  Future<void> _openDetail(int id) async {
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ProcurementDisposalDetailPage(api: widget.api, id: id),
      ),
    );
    if (mounted) _reload();
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Disposals',
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _openRequest,
        icon: const Icon(Icons.add),
        label: const Text('Request disposal'),
      ),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (!snap.hasData) return procurementLoadingBody();
          final items = snap.data?['items'] as List? ?? [];
          if (items.isEmpty) {
            return Center(
              child: Text(
                'No disposal requests yet. Tap Request disposal.',
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
                return TichCard(
                  onTap: () => _openDetail(id),
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
  Object? _cache;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    procurementPrimeScreen(
      persistKey: 'procurement.assetDisposal.${widget.id}',
      setCache: (c) => _cache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.assetDisposal(widget.id),
    );
  }

  Future<void> _approve() async {
    setState(() => _busy = true);
    try {
      await widget.api.approveAssetDisposal(widget.id);
      if (mounted) {
        procurementUpdateFuture(
          setState,
          (f) {
        _future = f;
      },
          () => widget.api.assetDisposal(widget.id),
      mounted: () => mounted,
      onData: (d) => _cache = d,
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
      title: 'Disposal',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(
                  snap,
                  _cache as dynamic,
                  persistKey: 'procurement.assetDisposal.${widget.id}',
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
          return ProcurementDetailBody(
            children: [
              procurementDetailHero(
                title: (r['asset_name'] ?? 'Asset').toString(),
                eyebrow: (r['asset_number'] ?? '').toString(),
                statusLabel: (r['approval_status'] ?? '').toString(),
                metricLabel: 'Disposed value',
                metricValue: _kes.format((r['disposed_value'] as num?) ?? 0),
              ),
              procurementDetailSection('Disposal', [
                procurementDetailField('Type', r['disposal_type']),
                procurementDetailField('Status', r['status']),
                procurementDetailField('Date', r['disposal_date']),
                procurementDetailField('Requested by', r['requested_by']),
                procurementDetailField('Reason', r['reason']),
              ]),
              if (canApprove)
                procurementDetailActions([
                  FilledButton(
                    onPressed: _busy ? null : _approve,
                    child: const Text('Approve disposal'),
                  ),
                ]),
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
  Object? _cache;

  @override
  void initState() {
    super.initState();
    procurementPrimeScreen(
      persistKey: 'procurement.asset_audits',
      setCache: (c) => _cache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.assetAudits(),
    );
  }

  void _reload() => procurementUpdateFuture(
        setState,
        (f) {
        _future = f;
      },
        () => widget.api.assetAudits(),
      mounted: () => mounted,
      onData: (d) => _cache = d,
    );

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Audits',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(
                  snap,
                  _cache as dynamic,
                  persistKey: 'procurement.asset_audits',
                );
                if (tichShowLoadingSpinner(snap, merged)) {
                  return procurementLoadingBody();
                }
                if (merged == null && snap.hasError) {
                  return Center(child: Text(tichDescribeError(snap.error)));
                }
                final data = merged! as Map<String, dynamic>;
          final items = data['items'] as List? ?? [];
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
  Object? _cache;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    procurementPrimeScreen(
      persistKey: 'procurement.assetAudit.${widget.id}',
      setCache: (c) => _cache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.assetAudit(widget.id),
    );
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
        procurementUpdateFuture(
          setState,
          (f) {
        _future = f;
      },
          () => widget.api.assetAudit(widget.id),
      mounted: () => mounted,
      onData: (d) => _cache = d,
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
      title: 'Audit',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(
                  snap,
                  _cache as dynamic,
                  persistKey: 'procurement.assetAudit.${widget.id}',
                );
                if (tichShowLoadingSpinner(snap, merged)) {
                  return procurementLoadingBody();
                }
                if (merged == null && snap.hasError) {
                  return Center(child: Text(tichDescribeError(snap.error)));
                }
                final data = merged! as Map<String, dynamic>;
          final r = data;
          final canReview = r['can_review'] == true;
          return ProcurementDetailBody(
            children: [
              procurementDetailHero(
                title: (r['asset_name'] ?? 'Asset').toString(),
                eyebrow: (r['asset_number'] ?? '').toString(),
                statusLabel: (r['status'] ?? '').toString(),
              ),
              procurementDetailSection('Verification', [
                procurementDetailField('Verification', r['verification_status']),
                procurementDetailField('Condition', r['condition']),
                procurementDetailField('Auditor', r['auditor']),
                procurementDetailField('Reviewer', r['reviewer']),
                if ((r['notes'] ?? '').toString().isNotEmpty)
                  procurementDetailNote((r['notes'] ?? '').toString()),
                if ((r['review_notes'] ?? '').toString().isNotEmpty)
                  procurementDetailNote((r['review_notes'] ?? '').toString()),
              ]),
              if (canReview)
                procurementDetailActions([
                  FilledButton(
                    onPressed: _busy ? null : _submitReview,
                    child: const Text('Submit review'),
                  ),
                ]),
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
  Object? _cache;

  @override
  void initState() {
    super.initState();
    procurementPrimeScreen(
      persistKey: 'procurement.payment_verification',
      setCache: (c) => _cache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.paymentVerification(),
    );
  }

  void _reload() => procurementUpdateFuture(
        setState,
        (f) {
        _future = f;
      },
        () => widget.api.paymentVerification(),
      mounted: () => mounted,
      onData: (d) => _cache = d,
    );

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Payment & verification',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(
                  snap,
                  _cache as dynamic,
                  persistKey: 'procurement.payment_verification',
                );
                if (tichShowLoadingSpinner(snap, merged)) {
                  return procurementLoadingBody();
                }
                if (merged == null && snap.hasError) {
                  return Center(child: Text(tichDescribeError(snap.error)));
                }
                final data = merged! as Map<String, dynamic>;
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
