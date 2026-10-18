import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
import '../../adaptive/adaptive.dart';
import '../shell/portal_shell.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_live_refresh.dart';

class PayslipsPage extends StatefulWidget {
  const PayslipsPage({super.key, required this.api});

  final EmployeeApi api;

  @override
  State<PayslipsPage> createState() => _PayslipsPageState();
}

class _PayslipsPageState extends State<PayslipsPage> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.payslips();
  }

  void _reload() => setState(() => _future = widget.api.payslips());

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.payslips(),
      apply: (f) => _future = f,
    );
  }

  Future<void> _open(Map<String, dynamic> item) async {
    final detail = await widget.api.payslip((item['id'] as num).toInt());
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
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: AdaptiveProgress());
          }
          if (snap.hasError) {
            return TichErrorRefresh(
              message: snap.error.toString(),
              onRetry: _reload,
            );
          }
          final items = snap.data!['payslips'] as List? ?? const [];
          if (items.isEmpty) {
            return const Center(child: TichEmpty('No released payslips yet.'));
          }
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (context, i) {
                final p = Map<String, dynamic>.from(items[i] as Map);
                return TichCard(
                  onTap: () => _open(p),
                  child: Row(
                    children: [
                      Container(
                        width: 44,
                        height: 44,
                        decoration: BoxDecoration(
                          color: TichColors.greenLight,
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: const Icon(Icons.receipt_long, color: TichColors.greenDark),
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
    final n = (value is num) ? value.toDouble() : double.tryParse('$value') ?? 0;
    return n.toStringAsFixed(2);
  }
}

class PayslipDetailPage extends StatelessWidget {
  const PayslipDetailPage({super.key, required this.detail});

  final Map<String, dynamic> detail;

  @override
  Widget build(BuildContext context) {
    final breakdown = Map<String, dynamic>.from(detail['breakdown'] as Map? ?? {});
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
                        Expanded(child: Text(e.key.replaceAll('_', ' '))),
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
