import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/employee_api.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_form_dialog.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_widgets.dart';
import 'portal_helpers.dart';

class WfhPage extends StatefulWidget {
  const WfhPage({super.key, required this.api});

  final EmployeeApi api;

  @override
  State<WfhPage> createState() => _WfhPageState();
}

class _WfhPageState extends State<WfhPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;

  @override
  void initState() {
    super.initState();
    portalPrimeScreen(
      persistKey: 'portal.wfh',
      setCache: (c) => _dataCache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.wfh(),
      mounted: () => mounted,
      scheduleLiveRefresh: _liveRefresh,
    );
  }

  Future<void> _reload() => tichRefreshCached<Map<String, dynamic>>(
        mounted: () => mounted,
        setState: setState,
        fetch: () => widget.api.wfh(),
        onData: (data) => _dataCache = data,
        persistKey: 'portal.wfh',
      );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.wfh(),
      onData: (data) => _dataCache = data,
      persistKey: 'portal.wfh',
    );
  }

  bool _enabled(Map<String, dynamic> data) => data['enabled'] == true;

  Future<void> _apply(Map<String, dynamic> data) async {
    if (!_enabled(data)) return;
    final rules = Map<String, dynamic>.from(data['booking_rules'] as Map? ?? {});
    final entitlement = Map<String, dynamic>.from(data['entitlement'] as Map? ?? {});
    final available = (entitlement['available'] as num?)?.toInt() ?? 0;
    if (available < 1) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No work from home days available in your monthly bank.')),
      );
      return;
    }

    final allowedWeekdays = (rules['allowed_iso_weekdays'] as List?)
            ?.map((e) => (e as num).toInt())
            .toList() ??
        const [2, 3, 4];
    final minAdvance = (rules['min_advance_days'] as num?)?.toInt() ?? 2;
    final earliestStr = rules['earliest_bookable_date'] as String?;
    final earliest = earliestStr != null
        ? DateTime.tryParse(earliestStr)
        : DateTime.now().add(Duration(days: minAdvance));

    final taskControllers = List.generate(5, (_) => TextEditingController());
    DateTime? workDate = earliest;
    TimeOfDay start = const TimeOfDay(hour: 8, minute: 0);
    TimeOfDay end = const TimeOfDay(hour: 17, minute: 0);
    final totalHoursCtrl = TextEditingController(text: '8');

    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDialog) {
          Future<void> pickDate() async {
            final picked = await pickDateOnly(
              ctx,
              initial: workDate ?? earliest,
              firstDate: earliest,
            );
            if (picked == null) return;
            final iso = picked.weekday;
            if (!allowedWeekdays.contains(iso)) {
              if (ctx.mounted) {
                ScaffoldMessenger.of(ctx).showSnackBar(
                  const SnackBar(
                    content: Text('Choose Tuesday, Wednesday, or Thursday only.'),
                  ),
                );
              }
              return;
            }
            setDialog(() => workDate = picked);
          }

          Future<void> pickTime(bool isStart) async {
            final initial = isStart ? start : end;
            final picked = await showTimePicker(
              context: ctx,
              initialTime: initial,
            );
            if (picked == null) return;
            setDialog(() {
              if (isStart) {
                start = picked;
              } else {
                end = picked;
              }
            });
          }

          return TichFormDialog(
            title: const Text('Apply for WFH'),
            scrollable: true,
            content: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  'Tue–Thu only · $minAdvance+ day(s) ahead · 1 day per week (monthly bank)',
                  style: Theme.of(ctx).textTheme.bodySmall,
                ),
                const SizedBox(height: 12),
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: const Text('Work from home date'),
                  subtitle: Text(
                    workDate != null ? dateDisplay.format(workDate!) : 'Select date',
                  ),
                  trailing: const Icon(Icons.calendar_today_outlined),
                  onTap: pickDate,
                ),
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: const Text('Start time'),
                  subtitle: Text(timeDisplay.format(
                    DateTime(2000, 1, 1, start.hour, start.minute),
                  )),
                  onTap: () => pickTime(true),
                ),
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: const Text('End time'),
                  subtitle: Text(timeDisplay.format(
                    DateTime(2000, 1, 1, end.hour, end.minute),
                  )),
                  onTap: () => pickTime(false),
                ),
                TextField(
                  controller: totalHoursCtrl,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  decoration: const InputDecoration(labelText: 'Total hours'),
                ),
                const SizedBox(height: 8),
                Text('Remote tasks', style: Theme.of(ctx).textTheme.titleSmall),
                const SizedBox(height: 4),
                Text(
                  'List up to 5 tasks. At least one is required.',
                  style: Theme.of(ctx).textTheme.bodySmall,
                ),
                for (var i = 0; i < 5; i++) ...[
                  const SizedBox(height: 8),
                  TextField(
                    controller: taskControllers[i],
                    decoration: InputDecoration(
                      labelText: '${i + 1}.',
                      hintText: 'Task or deliverable',
                    ),
                    maxLength: 500,
                  ),
                ],
              ],
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(ctx, false),
                child: const Text('Cancel'),
              ),
              FilledButton(
                onPressed: () => Navigator.pop(ctx, true),
                child: const Text('Submit to HR'),
              ),
            ],
          );
        },
      ),
    );

    if (ok != true || workDate == null) {
      for (final c in taskControllers) {
        c.dispose();
      }
      totalHoursCtrl.dispose();
      return;
    }

    final tasks = taskControllers
        .map((c) => c.text.trim())
        .where((t) => t.isNotEmpty)
        .toList();
    for (final c in taskControllers) {
      c.dispose();
    }

    if (tasks.isEmpty) {
      totalHoursCtrl.dispose();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('List at least one remote task.')),
      );
      return;
    }

    final startMinutes = start.hour * 60 + start.minute;
    final endMinutes = end.hour * 60 + end.minute;
    if (endMinutes <= startMinutes) {
      totalHoursCtrl.dispose();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('End time must be after start time.')),
      );
      return;
    }

    final totalHours = double.tryParse(totalHoursCtrl.text.trim());
    totalHoursCtrl.dispose();

    try {
      final result = await widget.api.submitWfh(
        workDate: dateApi.format(workDate!),
        startTime: timeApi.format(DateTime(2000, 1, 1, start.hour, start.minute)),
        endTime: timeApi.format(DateTime(2000, 1, 1, end.hour, end.minute)),
        totalHours: totalHours,
        remoteTasks: tasks,
      );
      await _reload();
      if (!mounted) return;
      final msg = result['message'] as String? ?? 'Request submitted.';
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  Future<void> _cancel(int id) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Cancel request?'),
        content: const Text('This work from home request will be cancelled.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('No')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Cancel request')),
        ],
      ),
    );
    if (ok != true) return;

    try {
      await widget.api.cancelWfh(id);
      await _reload();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Request cancelled.')),
      );
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  void _showDetail(Map<String, dynamic> item) {
    final tasks = (item['remote_tasks'] as List?)?.map((t) => '$t').toList() ?? [];
    showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      builder: (ctx) => Padding(
        padding: const EdgeInsets.fromLTRB(20, 0, 20, 32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              item['request_code'] as String? ?? 'WFH',
              style: Theme.of(ctx).textTheme.titleLarge,
            ),
            const SizedBox(height: 8),
            Text(item['status_label'] as String? ?? item['status'] as String? ?? ''),
            const SizedBox(height: 16),
            if (item['work_date'] != null)
              Text('Date: ${item['work_date']}'),
            if (item['start_time'] != null && item['end_time'] != null)
              Text('Hours: ${item['start_time']} – ${item['end_time']}'
                  '${item['total_hours'] != null ? ' (${item['total_hours']}h)' : ''}'),
            const SizedBox(height: 12),
            Text('Remote tasks', style: Theme.of(ctx).textTheme.titleSmall),
            const SizedBox(height: 4),
            if (tasks.isEmpty)
              const Text('No tasks listed.')
            else
              ...tasks.map((t) => Padding(
                    padding: const EdgeInsets.only(bottom: 4),
                    child: Text('• $t'),
                  )),
            if (item['can_cancel'] == true) ...[
              const SizedBox(height: 16),
              FilledButton.tonal(
                onPressed: () {
                  Navigator.pop(ctx);
                  _cancel(item['id'] as int);
                },
                child: const Text('Cancel request'),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _entitlementRow(Map<String, dynamic>? entitlement) {
    if (entitlement == null) return const SizedBox.shrink();
    final earned = entitlement['earned'];
    final weeks = entitlement['weeks_in_month'];
    final used = entitlement['used'];
    final available = entitlement['available'];

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
      child: Row(
        children: [
          Expanded(
            child: TichCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Earned', style: Theme.of(context).textTheme.bodySmall),
                  Text('$earned / $weeks', style: Theme.of(context).textTheme.titleMedium),
                ],
              ),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: TichCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Used', style: Theme.of(context).textTheme.bodySmall),
                  Text('$used', style: Theme.of(context).textTheme.titleMedium),
                ],
              ),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: TichCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Available', style: Theme.of(context).textTheme.bodySmall),
                  Text('$available', style: Theme.of(context).textTheme.titleMedium),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
        title: 'Work from home',
        floatingActionButton: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snap) {
            final merged =
                tichMergeSnapshot(snap, _dataCache as dynamic, persistKey: 'portal.wfh');
            if (merged is! Map<String, dynamic> || !_enabled(merged)) {
              return const SizedBox.shrink();
            }
            return FloatingActionButton.extended(
              heroTag: 'portal-wfh',
              onPressed: () => _apply(merged),
              icon: const Icon(Icons.add),
              label: const Text('Apply'),
            );
          },
        ),
        body: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snap) {
            final merged =
                tichMergeSnapshot(snap, _dataCache as dynamic, persistKey: 'portal.wfh');
            if (tichShowLoadingSpinner(snap, merged)) {
              return tichInstantWaitBody(onRefresh: () async => _reload());
            }
            if (merged == null && snap.hasError) {
              return TichErrorRefresh(
                message: snap.error.toString(),
                onRetry: _reload,
              );
            }
            final data = merged! as Map<String, dynamic>;

            if (!_enabled(data)) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: [
                  TichCard(
                    child: TichEmpty(
                      data['message'] as String? ?? 'This page is unavailable.',
                    ),
                  ),
                ],
              );
            }

            final entitlement = data['entitlement'] as Map<String, dynamic>?;
            final requests = data['requests'] as List? ?? const [];

            return RefreshIndicator(
              onRefresh: () async => _reload(),
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: [
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                    child: Text(
                      'Tue–Thu only · Apply ahead · 1 day per week (monthly bank)',
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                  ),
                  _entitlementRow(entitlement),
                  if (requests.isEmpty)
                    const Padding(
                      padding: EdgeInsets.all(16),
                      child: TichCard(child: TichEmpty('No work from home requests yet.')),
                    )
                  else
                    ...requests.map((raw) {
                      final item = Map<String, dynamic>.from(raw as Map);
                      final code = item['request_code'] as String? ?? 'WFH';
                      final date = item['work_date'] as String? ?? '';
                      final status = item['status_label'] as String? ?? item['status'] as String? ?? '';
                      final hours = (item['start_time'] != null && item['end_time'] != null)
                          ? '${item['start_time']} – ${item['end_time']}'
                          : null;

                      return Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                        child: TichCard(
                          onTap: () => _showDetail(item),
                          child: ListTile(
                            title: Text(code),
                            subtitle: Text([
                              if (date.isNotEmpty) date,
                              if (hours != null) hours,
                            ].join(' · ')),
                            trailing: Text(
                              status,
                              style: Theme.of(context).textTheme.bodySmall,
                            ),
                          ),
                        ),
                      );
                    }),
                  const SizedBox(height: 80),
                ],
              ),
            );
          },
        ),
      ),
    );
  }
}