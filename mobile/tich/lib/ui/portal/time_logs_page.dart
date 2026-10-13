import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
import '../../adaptive/adaptive.dart';
import '../shell/portal_shell.dart';
import '../widgets/tich_widgets.dart';

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

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Time logs',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: AdaptiveProgress());
          }
          if (snap.hasError) {
            return TichErrorRefresh(message: snap.error.toString(), onRetry: _reload);
          }
          final weeks = snap.data!['weeks'] as List? ?? const [];
          final message = snap.data!['message']?.toString();
          if (weeks.isEmpty) {
            return Center(child: TichEmpty(message ?? 'No weekly time logs yet.'));
          }
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: weeks.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (context, i) {
                final w = Map<String, dynamic>.from(weeks[i] as Map);
                return TichCard(
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(
                          '${w['week_start'] ?? ''} → ${w['week_end'] ?? ''}',
                          style: tichUi(fontWeight: FontWeight.w600),
                        ),
                      ),
                      if (w['total_hours'] != null)
                        Text('${w['total_hours']}h'),
                      const SizedBox(width: 8),
                      TichStatusChip(
                        w['status']?.toString() ?? '',
                        tone: toneForStatus(w['status']?.toString()),
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
