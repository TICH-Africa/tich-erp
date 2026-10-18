import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_form_dialog.dart';
import '../widgets/tich_live_refresh.dart';

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

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.wfh(),
      apply: (f) => _future = f,
    );
  }

  Future<void> _apply() async {
    final reason = TextEditingController();
    DateTime start = DateTime.now().add(const Duration(days: 1)).copyWith(hour: 8, minute: 0);
    DateTime end = start.copyWith(hour: 17, minute: 0);
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (ctx, setModal) => TichFormDialog(
            title: const Text('Apply for WFH'),
            scrollable: true,
            content: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                OutlinedButton(
                  onPressed: () async {
                    final picked = await pickDateTime(
                      ctx,
                      initial: start,
                      firstDate: DateTime.now(),
                      lastDate: DateTime.now().add(const Duration(days: 120)),
                    );
                    if (picked != null) setModal(() => start = picked);
                  },
                  child: Text('Start: ${dateTimeDisplay.format(start)}'),
                ),
                const SizedBox(height: 8),
                OutlinedButton(
                  onPressed: () async {
                    final picked = await pickDateTime(
                      ctx,
                      initial: end,
                      firstDate: DateTime.now(),
                      lastDate: DateTime.now().add(const Duration(days: 120)),
                    );
                    if (picked != null) setModal(() => end = picked);
                  },
                  child: Text('End: ${dateTimeDisplay.format(end)}'),
                ),
                const SizedBox(height: 8),
                TextField(
                  controller: reason,
                  maxLines: 3,
                  decoration: const InputDecoration(labelText: 'Tasks / reason'),
                ),
              ],
            ),
            actions: [
              TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
              FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Submit')),
            ],
          ),
        );
      },
    );
    if (ok != true) return;
    try {
      await widget.api.submitWfh(
        workDate: dateApi.format(start),
        reason: reason.text.trim(),
        startTime: timeApi.format(start),
        endTime: timeApi.format(end),
      );
      _reload();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
      title: 'Work from home',
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _apply,
        label: const Text('Apply'),
        icon: const Icon(Icons.add),
      ),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: AdaptiveProgress());
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
      ),
    );
  }
}
