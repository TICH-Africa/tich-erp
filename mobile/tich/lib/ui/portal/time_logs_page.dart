import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_widgets.dart';
import 'time_log_detail_page.dart';

class TimeLogsPage extends StatefulWidget {
  const TimeLogsPage({super.key, required this.api});

  final EmployeeApi api;

  @override
  State<TimeLogsPage> createState() => _TimeLogsPageState();
}

class _TimeLogsPageState extends State<TimeLogsPage> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.timeLogs();
  }

  void _reload() => setState(() => _future = widget.api.timeLogs());

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.timeLogs(),
      apply: (f) => _future = f,
    );
  }

  Future<void> _openWeek() async {
    final data = await _future;
    final meta = Map<String, dynamic>.from(data['meta'] as Map? ?? {});
    var year = (meta['current_year'] as num?)?.toInt() ?? DateTime.now().year;
    var month = (meta['current_month'] as num?)?.toInt() ?? DateTime.now().month;
    var weekOptions = (meta['week_options'] as List? ?? const [])
        .map((e) => Map<String, dynamic>.from(e as Map))
        .toList();
    var weekNumber = (meta['current_week_number'] as num?)?.toInt() ??
        (weekOptions.isNotEmpty
            ? (weekOptions.first['week_number'] as num?)?.toInt() ?? 1
            : 1);

    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (ctx, setModal) {
            Future<void> refreshWeeks() async {
              try {
                final res = await widget.api.timeLogWeeks(
                  year: year,
                  month: month,
                );
                setModal(() {
                  weekOptions = (res['weeks'] as List? ?? const [])
                      .map((e) => Map<String, dynamic>.from(e as Map))
                      .toList();
                  if (weekOptions.isNotEmpty) {
                    weekNumber =
                        (weekOptions.first['week_number'] as num?)?.toInt() ??
                            weekNumber;
                  }
                });
              } on ApiException catch (e) {
                if (ctx.mounted) {
                  ScaffoldMessenger.of(ctx)
                      .showSnackBar(SnackBar(content: Text(e.message)));
                }
              }
            }

            return AlertDialog(
              title: const Text('Open weekly time log'),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Row(
                      children: [
                        const Text('Year'),
                        IconButton(
                          onPressed: () => setModal(() => year -= 1),
                          icon: const Icon(Icons.remove),
                        ),
                        Text('$year', style: tichUi(fontWeight: FontWeight.w700)),
                        IconButton(
                          onPressed: () => setModal(() => year += 1),
                          icon: const Icon(Icons.add),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    TichDropdownFormField<int>(
                      value: month,
                      decoration: const InputDecoration(labelText: 'Month'),
                      items: List.generate(
                        12,
                        (i) => DropdownMenuItem(
                          value: i + 1,
                          child: Text(_monthName(i + 1)),
                        ),
                      ),
                      onChanged: (v) async {
                        if (v == null) return;
                        setModal(() => month = v);
                        await refreshWeeks();
                      },
                    ),
                    const SizedBox(height: 8),
                    if (weekOptions.isEmpty)
                      const Text('No weeks for this month.')
                    else
                      TichDropdownFormField<int>(
                        value: weekNumber,
                        decoration: const InputDecoration(labelText: 'Week'),
                        items: [
                          for (final w in weekOptions)
                            DropdownMenuItem(
                              value: (w['week_number'] as num).toInt(),
                              child: Text(
                                '${w['label'] ?? 'Week'} · ${w['week_ref'] ?? ''}',
                              ),
                            ),
                        ],
                        onChanged: (v) {
                          if (v != null) setModal(() => weekNumber = v);
                        },
                      ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(ctx, false),
                  child: const Text('Cancel'),
                ),
                FilledButton(
                  onPressed: weekOptions.isEmpty
                      ? null
                      : () => Navigator.pop(ctx, true),
                  child: const Text('Open week'),
                ),
              ],
            );
          },
        );
      },
    );
    if (ok != true || !mounted) return;

    try {
      final res = await widget.api.createTimeLog(
        year: year,
        month: month,
        weekNumber: weekNumber,
      );
      final log = Map<String, dynamic>.from(res['log'] as Map? ?? {});
      final id = (log['id'] as num?)?.toInt();
      if (id == null) return;
      if (!mounted) return;
      await Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => TimeLogDetailPage(api: widget.api, timeLogId: id),
        ),
      );
      _reload();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  void _openExisting(int id) {
    Navigator.of(context)
        .push(
      MaterialPageRoute(
        builder: (_) => TimeLogDetailPage(api: widget.api, timeLogId: id),
      ),
    )
        .then((_) => _reload());
  }

  static String _monthName(int month) {
    const names = [
      'January',
      'February',
      'March',
      'April',
      'May',
      'June',
      'July',
      'August',
      'September',
      'October',
      'November',
      'December',
    ];
    return names[month - 1];
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
        title: 'Time logs',
        floatingActionButton: FloatingActionButton.extended(
          onPressed: _openWeek,
          icon: const Icon(Icons.add),
          label: const Text('Open week'),
        ),
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
            final weeks = snap.data!['weeks'] as List? ?? const [];
            final message = snap.data!['message']?.toString();
            if (weeks.isEmpty) {
              return Center(
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      TichEmpty(message ?? 'No weekly time logs yet.'),
                      const SizedBox(height: 16),
                      AdaptiveFilledButton(
                        onPressed: _openWeek,
                        child: const Text('Open a week'),
                      ),
                    ],
                  ),
                ),
              );
            }
            return RefreshIndicator(
              onRefresh: () async => _reload(),
              child: ListView.separated(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 88),
                itemCount: weeks.length,
                separatorBuilder: (_, __) => const SizedBox(height: 8),
                itemBuilder: (context, i) {
                  final w = Map<String, dynamic>.from(weeks[i] as Map);
                  final id = (w['id'] as num?)?.toInt();
                  return TichCard(
                    onTap: id == null ? null : () => _openExisting(id),
                    child: Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                w['log_code']?.toString() ??
                                    w['week_ref']?.toString() ??
                                    'Time log',
                                style: tichUi(fontWeight: FontWeight.w700),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                '${formatDate(w['week_start'])} → ${formatDate(w['week_end'])}',
                                style: tichUi(
                                  fontSize: 12,
                                  color: TichColors.neutralMuted,
                                ),
                              ),
                            ],
                          ),
                        ),
                        if (w['total_hours'] != null)
                          Padding(
                            padding: const EdgeInsets.only(right: 8),
                            child: Text('${w['total_hours']}h'),
                          ),
                        TichStatusChip(
                          w['status']?.toString() ?? '',
                          tone: toneForStatus(w['status']?.toString()),
                        ),
                        const Icon(Icons.chevron_right, size: 20),
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
}
