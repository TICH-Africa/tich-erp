import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../adaptive/adaptive.dart';
import '../../bloc/auth/auth_bloc.dart';
import '../../bloc/auth/auth_state.dart';
import '../../data/student_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_live_refresh.dart';
import 'student_helpers.dart';
import 'student_web_handoff.dart';

class StudentFinancePage extends StatefulWidget {
  const StudentFinancePage({super.key, required this.api});

  final StudentApi api;

  @override
  State<StudentFinancePage> createState() => _StudentFinancePageState();
}

class _StudentFinancePageState extends State<StudentFinancePage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;
  bool _recoverScheduled = false;

  @override
  void initState() {
    super.initState();
    studentPrimeScreen(
      persistKey: 'student.finance',
      setCache: (c) => _dataCache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.finance(),
    );
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      _liveRefresh();
    });
  }

  Future<void> _reload() => studentCachedReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.finance(),
      onData: (data) => _dataCache = data,
      persistKey: 'student.finance',
    );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.finance(),
      onData: (data) => _dataCache = data,
      persistKey: 'student.finance',
    );
  }

  void _openPaymentOnWeb({int? invoiceId}) {
    final auth = context.read<AuthBloc>().state;
    final user = switch (auth) {
      AuthAuthenticated(:final user) => user,
      AuthLocked(:final user) => user,
      _ => null,
    };
    if (user == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Sign in again to pay on the web portal.')),
      );
      return;
    }
    studentOpenFinancePaymentOnWeb(
      context,
      user: user,
      api: widget.api,
      invoiceId: invoiceId,
    );
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
      title: 'Finance',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          return studentCachedMapBody(
            snap: snap,
            memoryCache: _dataCache,
            persistKey: 'student.finance',
            onRetry: _reload,
            autoRecoverOnError: true,
            recoverScheduled: _recoverScheduled,
            setRecoverScheduled: (v) => _recoverScheduled = v,
            mounted: () => mounted,
            builder: (data) {
          final finance =
              Map<String, dynamic>.from(data['finance'] as Map? ?? {});
          final summary =
              Map<String, dynamic>.from(finance['summary'] as Map? ?? {});
          final invoices = finance['invoices'] as List? ?? const [];
          final payments = finance['payments'] as List? ?? const [];
          final payable = finance['payable_invoices'] as List? ?? const [];
          final statement = finance['statement'] as List? ?? const [];
          final hasBalance =
              ((summary['outstanding_balance'] as num?)?.toDouble() ?? 0) > 0;

          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
              children: [
                TichCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        money(summary['outstanding_balance']),
                        style: tichUi(fontSize: 22, fontWeight: FontWeight.w800),
                      ),
                      const SizedBox(height: 4),
                      Text('Outstanding balance',
                          style: tichUi(color: TichColors.neutralMuted)),
                      const SizedBox(height: 10),
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              'Paid: ${money(summary['total_paid'])}',
                              style: tichUi(fontSize: 13),
                            ),
                          ),
                          TichStatusChip(
                            summary['fee_clearance_status']?.toString() ??
                                'pending',
                            tone: toneForStatus(
                              summary['fee_clearance_status']?.toString(),
                            ),
                          ),
                        ],
                      ),
                      if (hasBalance) ...[
                        const SizedBox(height: 14),
                        SizedBox(
                          width: double.infinity,
                          child: FilledButton.icon(
                            onPressed: () => _openPaymentOnWeb(),
                            icon: const Icon(Icons.open_in_browser),
                            label: const Text('Pay on student portal'),
                          ),
                        ),
                        const SizedBox(height: 6),
                        Text(
                          'M-Pesa and other payments are completed on the web student portal.',
                          style: tichUi(
                            fontSize: 12,
                            color: TichColors.neutralMuted,
                            height: 1.35,
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
                if (payable.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  const TichSectionHeader('Pay now'),
                  ...payable.map((raw) {
                    final inv = Map<String, dynamic>.from(raw as Map);
                    final invoiceId = (inv['id'] as num?)?.toInt();
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        child: ListTile(
                          contentPadding: EdgeInsets.zero,
                          title: Text(
                            inv['invoice_number']?.toString() ?? 'Invoice',
                            style: tichUi(fontWeight: FontWeight.w600),
                          ),
                          subtitle: Text(
                            '${money(inv['balance'])} · ${inv['status'] ?? ''}',
                          ),
                          trailing: FilledButton(
                            onPressed: () => _openPaymentOnWeb(
                              invoiceId: invoiceId,
                            ),
                            child: const Text('Pay'),
                          ),
                        ),
                      ),
                    );
                  }),
                ],
                const SizedBox(height: 16),
                const TichSectionHeader('Invoices'),
                if (invoices.isEmpty)
                  const TichCard(child: TichEmpty('No invoices.'))
                else
                  ...invoices.take(20).map((raw) {
                    final inv = Map<String, dynamic>.from(raw as Map);
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        child: ListTile(
                          contentPadding: EdgeInsets.zero,
                          title: Text(
                            inv['invoice_number']?.toString() ?? 'Invoice',
                            style: tichUi(fontWeight: FontWeight.w600),
                          ),
                          subtitle: Text(
                            '${formatDateTime(inv['issue_date'])} · Balance ${money(inv['balance'])}',
                            style: tichUi(fontSize: 12),
                          ),
                          trailing: TichStatusChip(
                            inv['status']?.toString() ?? '',
                            tone: toneForStatus(inv['status']?.toString()),
                          ),
                        ),
                      ),
                    );
                  }),
                const SizedBox(height: 16),
                const TichSectionHeader('Payments'),
                if (payments.isEmpty)
                  const TichCard(child: TichEmpty('No payments yet.'))
                else
                  ...payments.take(20).map((raw) {
                    final p = Map<String, dynamic>.from(raw as Map);
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        child: ListTile(
                          contentPadding: EdgeInsets.zero,
                          title: Text(
                            p['payment_number']?.toString() ?? 'Payment',
                            style: tichUi(fontWeight: FontWeight.w600),
                          ),
                          subtitle: Text(
                            '${formatDateTime(p['payment_date'])} · ${p['payment_method'] ?? ''}',
                            style: tichUi(fontSize: 12),
                          ),
                          trailing: Text(money(p['amount']),
                              style: tichUi(fontWeight: FontWeight.w700)),
                        ),
                      ),
                    );
                  }),
                const SizedBox(height: 16),
                const TichSectionHeader('Statement'),
                if (statement.isEmpty)
                  const TichCard(child: TichEmpty('No statement entries.'))
                else
                  ...statement.take(30).map((raw) {
                    final e = Map<String, dynamic>.from(raw as Map);
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              e['description']?.toString() ?? e['type']?.toString() ?? 'Entry',
                              style: tichUi(fontWeight: FontWeight.w600),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              '${formatDateTime(e['date'])} · ${e['reference'] ?? ''}',
                              style: tichUi(
                                fontSize: 12,
                                color: TichColors.neutralMuted,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'Dr ${money(e['debit'])} · Cr ${money(e['credit'])}'
                              ' · Bal ${money(e['running_balance'])}',
                              style: tichUi(fontSize: 12),
                            ),
                          ],
                        ),
                      ),
                    );
                  }),
              ],
            ),
          );
            },
          );
        },
      ),
      ),
    );
  }
}
