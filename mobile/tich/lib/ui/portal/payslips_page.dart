import 'dart:async';

import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../adaptive/adaptive.dart';
import '../../data/employee_api.dart';
import '../../data/tich_screen_cache.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_widgets.dart';
import 'employee_prefetch.dart';
import 'portal_helpers.dart';

class PayslipsPage extends StatefulWidget {
  const PayslipsPage({super.key, required this.api});

  final EmployeeApi api;

  @override
  State<PayslipsPage> createState() => _PayslipsPageState();
}

class _PayslipsPageState extends State<PayslipsPage> {
  static const _cacheKey = 'portal.payslips_bundle';

  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;
  final ValueNotifier<bool> _showCompensation = ValueNotifier(false);

  Future<Map<String, dynamic>> _fetchAll() async {
    final payslips = await widget.api.payslips();
    final compensation = TichScreenCache.read<Map<String, dynamic>>(
      'portal.dashboard',
    )?['compensation'];
    final dashboard = TichScreenCache.read<Map<String, dynamic>>('portal.dashboard');
    final payrollBreakdown = dashboard?['monthly_payroll_breakdown'];
    if (compensation is Map) {
      return {
        'payslips': payslips['payslips'],
        'compensation': compensation,
        if (payrollBreakdown is Map) 'monthly_payroll_breakdown': payrollBreakdown,
      };
    }
    return {
      'payslips': payslips['payslips'],
      'compensation': const <String, dynamic>{},
      if (payrollBreakdown is Map) 'monthly_payroll_breakdown': payrollBreakdown,
    };
  }

  void _loadCompensationIfNeeded() {
    final cached = TichScreenCache.read<Map<String, dynamic>>('portal.dashboard');
    final comp = cached?['compensation'];
    if (comp is Map && comp.isNotEmpty) return;
    unawaited(
      widget.api.dashboard().then((dashboard) {
        if (!mounted) return;
        TichScreenCache.write('portal.dashboard', dashboard);
        setState(() {
          if (_dataCache is Map<String, dynamic>) {
            final cache = _dataCache as Map<String, dynamic>;
            cache['compensation'] = dashboard['compensation'] ?? {};
            final breakdown = dashboard['monthly_payroll_breakdown'];
            if (breakdown is Map) {
              cache['monthly_payroll_breakdown'] = breakdown;
            }
          }
        });
      }).catchError((_) {}),
    );
  }

  @override
  void initState() {
    super.initState();
    portalPrimeScreen(
      persistKey: _cacheKey,
      setCache: (c) => _dataCache = c,
      setFuture: (f) => _future = f,
      fetch: _fetchAll,
    );
    WidgetsBinding.instance.addPostFrameCallback((_) => _loadCompensationIfNeeded());
  }

  @override
  void dispose() {
    _showCompensation.dispose();
    super.dispose();
  }

  Future<void> _reload() => portalCachedReload(
        mounted: () => mounted,
        setState: setState,
        fetch: _fetchAll,
        onData: (data) => _dataCache = data,
        persistKey: _cacheKey,
      );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: _fetchAll,
      onData: (data) => _dataCache = data,
      persistKey: _cacheKey,
    );
  }

  Future<void> _open(Map<String, dynamic> item) async {
    final id = (item['id'] as num).toInt();
    EmployeePrefetch.payslipDetail(widget.api, id);
    final cached = portalReadCache(EmployeePrefetch.payslipKey(id));
    final detail = cached ??
        await widget.api.payslip(id);
    if (!mounted) return;
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => PayslipDetailPage(detail: detail),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
        title: 'Payslips',
        body: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snap) {
            final merged = tichMergeSnapshot(
              snap,
              _dataCache as dynamic,
              persistKey: _cacheKey,
            );
            if (tichShowLoadingSpinner(snap, merged)) { return tichInstantWaitBody(onRefresh: () async => _reload()); }
            if (merged == null && snap.hasError) {
              return TichErrorRefresh(
                message: snap.error.toString(),
                onRetry: _reload,
              );
            }
            final data = merged! as Map<String, dynamic>;
            final items = data['payslips'] as List? ?? const [];
            final compensation = Map<String, dynamic>.from(
              data['compensation'] as Map? ?? {},
            );
            final payrollBreakdown = data['monthly_payroll_breakdown'] is Map
                ? Map<String, dynamic>.from(
                    data['monthly_payroll_breakdown'] as Map,
                  )
                : null;

            final payslipCount = items.isEmpty ? 1 : items.length;
            const fixedLeading = 3;
            return RefreshIndicator(
              onRefresh: () async => _reload(),
              child: ListView.builder(
                padding: const EdgeInsets.all(16),
                itemCount: fixedLeading + payslipCount,
                itemBuilder: (context, index) {
                  if (index == 0) {
                    return const TichSectionHeader('Compensation');
                  }
                  if (index == 1) {
                    return ValueListenableBuilder<bool>(
                      valueListenable: _showCompensation,
                      builder: (context, revealed, _) {
                        void toggle() =>
                            _showCompensation.value = !_showCompensation.value;
                        return TichCard(
                          child: Column(
                            children: [
                              _CompensationRow(
                                label: 'Consolidated gross pay',
                                amount: compensation['gross_monthly_salary'],
                                revealed: revealed,
                                onToggle: toggle,
                              ),
                              _CompensationRow(
                                label: 'Allowances',
                                amount: compensation['allowances_total'],
                                revealed: revealed,
                                onToggle: toggle,
                              ),
                              _CompensationRow(
                                label: 'Total package',
                                amount: compensation['total_monthly'],
                                revealed: revealed,
                                onToggle: toggle,
                                showDivider: payrollBreakdown == null,
                              ),
                              if (payrollBreakdown != null) ...[
                                const SizedBox(height: 8),
                                Text(
                                  'Estimated statutory deductions',
                                  style: tichUi(
                                    fontWeight: FontWeight.w700,
                                    fontSize: 13,
                                  ),
                                ),
                                _CompensationRow(
                                  label: 'PAYE',
                                  amount: payrollBreakdown['paye'],
                                  revealed: revealed,
                                  onToggle: toggle,
                                ),
                                _CompensationRow(
                                  label: 'NSSF',
                                  amount: payrollBreakdown['nssf'],
                                  revealed: revealed,
                                  onToggle: toggle,
                                ),
                                _CompensationRow(
                                  label: 'SHA / SHIF',
                                  amount: payrollBreakdown['sha'],
                                  revealed: revealed,
                                  onToggle: toggle,
                                ),
                                _CompensationRow(
                                  label: 'Affordable Housing Levy',
                                  amount: payrollBreakdown['ahl'],
                                  revealed: revealed,
                                  onToggle: toggle,
                                ),
                                _CompensationRow(
                                  label: 'Estimated net pay',
                                  amount: payrollBreakdown['net_salary'],
                                  revealed: revealed,
                                  onToggle: toggle,
                                  showDivider: false,
                                ),
                              ],
                            ],
                          ),
                        );
                      },
                    );
                  }
                  if (index == 2) {
                    return const Padding(
                      padding: EdgeInsets.only(top: 16),
                      child: TichSectionHeader('Released payslips'),
                    );
                  }
                  if (items.isEmpty) {
                    return const TichCard(
                      child: TichEmpty('No released payslips yet.'),
                    );
                  }
                  final p =
                      Map<String, dynamic>.from(items[index - fixedLeading] as Map);
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: TichCard(
                      onTap: () => _open(p),
                      onTapDown: (_) {
                        final id = (p['id'] as num?)?.toInt();
                        if (id != null) {
                          EmployeePrefetch.payslipDetail(widget.api, id);
                        }
                      },
                      child: Row(
                        children: [
                          Container(
                            width: 44,
                            height: 44,
                            decoration: BoxDecoration(
                              color: TichColors.greenLight,
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: const Icon(
                              Icons.receipt_long,
                              color: TichColors.greenDark,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  p['period_label']?.toString() ?? 'Payslip',
                                  style: tichUi(fontWeight: FontWeight.w700),
                                ),
                                Text(
                                  p['payslip_number']?.toString() ?? '',
                                  style: tichUi(
                                    fontSize: 12,
                                    color: TichColors.neutralMuted,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          Text(
                            _money(p['net_pay']),
                            style: tichUi(
                              fontWeight: FontWeight.w700,
                              color: TichColors.blueDark,
                            ),
                          ),
                        ],
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

  String _money(dynamic value) {
    final n =
        (value is num) ? value.toDouble() : double.tryParse('$value') ?? 0;
    return n.toStringAsFixed(2);
  }
}

String _kes(dynamic value, {required bool obscure}) {
  final n = (value is num) ? value.toDouble() : double.tryParse('$value') ?? 0;
  final formatted = 'KES ${n.toStringAsFixed(2)}';
  if (!obscure) return formatted;
  return 'KES •••••';
}

class _CompensationRow extends StatelessWidget {
  const _CompensationRow({
    required this.label,
    required this.amount,
    required this.revealed,
    required this.onToggle,
    this.showDivider = true,
  });

  final String label;
  final dynamic amount;
  final bool revealed;
  final VoidCallback onToggle;
  final bool showDivider;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 10),
          child: Row(
            children: [
              Expanded(
                child: Text(
                  label.toUpperCase(),
                  style: tichUi(
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                    letterSpacing: 0.5,
                    color: TichColors.neutralMuted,
                  ),
                ),
              ),
              Flexible(
                child: Text(
                  _kes(amount, obscure: !revealed),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  textAlign: TextAlign.end,
                  style: tichUi(fontWeight: FontWeight.w700),
                ),
              ),
              IconButton(
                visualDensity: VisualDensity.compact,
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                onPressed: onToggle,
                icon: Icon(
                  revealed
                      ? Icons.visibility_outlined
                      : Icons.visibility_off_outlined,
                  size: 18,
                  color: TichColors.neutralMuted,
                ),
              ),
            ],
          ),
        ),
        if (showDivider) const Divider(height: 1),
      ],
    );
  }
}

class PayslipDetailPage extends StatelessWidget {
  const PayslipDetailPage({super.key, required this.detail});

  final Map<String, dynamic> detail;

  @override
  Widget build(BuildContext context) {
    final breakdown =
        Map<String, dynamic>.from(detail['breakdown'] as Map? ?? {});
    final webUrl = detail['web_url']?.toString();

    return AdaptiveScaffold(
      title: detail['period_label']?.toString() ?? 'Payslip',
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          TichCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  detail['payslip_number']?.toString() ?? '',
                  style: tichUi(color: TichColors.neutralMuted),
                ),
                const SizedBox(height: 8),
                ...breakdown.entries.take(20).map(
                      (e) => Padding(
                        padding: const EdgeInsets.symmetric(vertical: 4),
                        child: Row(
                          children: [
                            Expanded(
                              child: Text(e.key.replaceAll('_', ' ')),
                            ),
                            Text('${e.value}'),
                          ],
                        ),
                      ),
                    ),
              ],
            ),
          ),
          if (webUrl != null && webUrl.isNotEmpty) ...[
            const SizedBox(height: 16),
            OutlinedButton.icon(
              onPressed: () => launchUrl(
                Uri.parse(webUrl),
                mode: LaunchMode.externalApplication,
              ),
              icon: const Icon(Icons.open_in_browser),
              label: const Text('Open full payslip in browser'),
            ),
          ],
        ],
      ),
    );
  }
}
