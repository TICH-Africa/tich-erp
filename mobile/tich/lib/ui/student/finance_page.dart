import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/student_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_form_dialog.dart';
import '../widgets/tich_live_refresh.dart';
import 'student_helpers.dart';

class StudentFinancePage extends StatefulWidget {
  const StudentFinancePage({super.key, required this.api});

  final StudentApi api;

  @override
  State<StudentFinancePage> createState() => _StudentFinancePageState();
}

class _StudentFinancePageState extends State<StudentFinancePage> {
  late Future<Map<String, dynamic>> _future;
  bool _paying = false;

  @override
  void initState() {
    super.initState();
    _future = widget.api.finance();
  }

  void _reload() => setState(() => _future = widget.api.finance());

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.finance(),
      apply: (f) => _future = f,
    );
  }

  Future<void> _pay(Map<String, dynamic> invoice, Map finance) async {
    final balance = (invoice['balance'] as num?)?.toDouble() ?? 0;
    if (balance <= 0) return;
    final phoneCtrl = TextEditingController(
      text: finance['default_phone']?.toString() ?? '',
    );
    final amountCtrl = TextEditingController(text: balance.toStringAsFixed(2));
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => TichFormDialog(
        title: Text('Pay ${invoice['invoice_number'] ?? 'invoice'}'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            TextField(
              controller: amountCtrl,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(labelText: 'Amount'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: phoneCtrl,
              keyboardType: TextInputType.phone,
              decoration: const InputDecoration(labelText: 'M-Pesa phone'),
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Pay')),
        ],
      ),
    );
    if (ok != true) return;
    final amount = double.tryParse(amountCtrl.text.trim());
    if (amount == null || amount <= 0) return;

    setState(() => _paying = true);
    try {
      final res = await widget.api.payInvoice(
        (invoice['id'] as num).toInt(),
        amount: amount,
        paymentMethod: 'mpesa',
        phoneNumber: phoneCtrl.text.trim(),
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message']?.toString() ?? 'Payment started')),
      );
      final stkId = (res['stk_request_id'] as num?)?.toInt();
      if (stkId != null) {
        await _pollStk(stkId);
      }
      _reload();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _paying = false);
    }
  }

  Future<void> _pollStk(int id) async {
    for (var i = 0; i < 12; i++) {
      await Future<void>.delayed(const Duration(seconds: 3));
      try {
        final status = await widget.api.stkStatus(id);
        if (status['is_complete'] == true) {
          if (!mounted) return;
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(
                status['is_success'] == true
                    ? 'Payment successful${status['mpesa_receipt_number'] != null ? ' · ${status['mpesa_receipt_number']}' : ''}.'
                    : (status['result_desc']?.toString() ?? 'Payment not completed.'),
              ),
            ),
          );
          return;
        }
      } catch (_) {
        return;
      }
    }
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
          if (snap.connectionState != ConnectionState.done) {
            return studentLoadingBody();
          }
          if (snap.hasError) {
            return studentErrorBody(snap.error, _reload);
          }
          final finance =
              Map<String, dynamic>.from(snap.data!['finance'] as Map? ?? {});
          final summary =
              Map<String, dynamic>.from(finance['summary'] as Map? ?? {});
          final invoices = finance['invoices'] as List? ?? const [];
          final payments = finance['payments'] as List? ?? const [];
          final payable = finance['payable_invoices'] as List? ?? const [];
          final statement = finance['statement'] as List? ?? const [];

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
                    ],
                  ),
                ),
                if (payable.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  const TichSectionHeader('Pay now'),
                  ...payable.map((raw) {
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
                            '${money(inv['balance'])} · ${inv['status'] ?? ''}',
                          ),
                          trailing: FilledButton(
                            onPressed: _paying ? null : () => _pay(inv, finance),
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
      ),
      ),
    );
  }
}
