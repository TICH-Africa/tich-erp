import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';

import '../../data/api_client.dart';
import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
import '../shell/portal_shell.dart';
import '../widgets/tich_widgets.dart';

class WfhPage extends StatefulWidget {
  const WfhPage({super.key, required this.api});

  final EmployeeApi api;

  @override
  State<WfhPage> createState() => _WfhPageState();
}

class _WfhPageState extends State<WfhPage> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.wfh();
  }

  void _reload() => setState(() => _future = widget.api.wfh());

  Future<void> _apply() async {
    final reason = TextEditingController();
    DateTime date = DateTime.now().add(const Duration(days: 1));
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Apply for WFH'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            OutlinedButton(
              onPressed: () async {
                final picked = await showDatePicker(
                  context: ctx,
                  initialDate: date,
                  firstDate: DateTime.now(),
                  lastDate: DateTime.now().add(const Duration(days: 120)),
                );
                if (picked != null) date = picked;
              },
              child: Text(DateFormat('dd MMM yyyy').format(date)),
            ),
            TextField(controller: reason, maxLines: 3, decoration: const InputDecoration(labelText: 'Reason')),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Submit')),
        ],
      ),
    );
    if (ok != true) return;
    try {
      await widget.api.submitWfh(
        workDate: DateFormat('yyyy-MM-dd').format(date),
        reason: reason.text.trim(),
      );
      _reload();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: tichAppBar(context, title: 'Work from home'),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _apply,
        label: const Text('Apply'),
        icon: const Icon(Icons.add),
      ),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snap.hasError) {
            return TichErrorRefresh(message: snap.error.toString(), onRetry: _reload);
          }
          final requests = snap.data!['requests'] as List? ?? const [];
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 88),
              children: [
                if (requests.isEmpty)
                  const TichCard(child: TichEmpty('No WFH requests yet.'))
                else
                  ...requests.map((raw) {
                    final r = Map<String, dynamic>.from(raw as Map);
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        child: Row(
                          children: [
                            Expanded(
                              child: Text(
                                r['work_date']?.toString() ?? '',
                                style: tichUi(fontWeight: FontWeight.w600),
                              ),
                            ),
                            TichStatusChip(
                              r['status']?.toString() ?? '',
                              tone: toneForStatus(r['status']?.toString()),
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
    );
  }
}
