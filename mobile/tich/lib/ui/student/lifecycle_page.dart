import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/student_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import 'student_helpers.dart';

class StudentLifecyclePage extends StatefulWidget {
  const StudentLifecyclePage({super.key, required this.api});

  final StudentApi api;

  @override
  State<StudentLifecyclePage> createState() => _StudentLifecyclePageState();
}

class _StudentLifecyclePageState extends State<StudentLifecyclePage> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.lifecycleRequests();
  }

  void _reload() => setState(() => _future = widget.api.lifecycleRequests());

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Deferment requests',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return studentLoadingBody();
          }
          if (snap.hasError) {
            return studentErrorBody(snap.error, _reload);
          }
          final requests = snap.data!['requests'] as List? ?? const [];
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                const TichCard(
                  child: Text(
                    'To submit a new deferment with supporting documents, use the web student portal. Your existing requests appear below.',
                  ),
                ),
                const SizedBox(height: 16),
                if (requests.isEmpty)
                  const TichCard(child: TichEmpty('No lifecycle requests yet.'))
                else
                  ...requests.map((raw) {
                    final r = Map<String, dynamic>.from(raw as Map);
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        child: ListTile(
                          contentPadding: EdgeInsets.zero,
                          title: Text(
                            '${r['request_type'] ?? 'Request'}'
                            '${r['deferment_months'] != null ? ' · ${r['deferment_months']} months' : ''}',
                            style: tichUi(fontWeight: FontWeight.w600),
                          ),
                          subtitle: Text(
                            [
                              r['reason'],
                              formatDateTime(r['created_at']),
                            ].where((e) => e != null && '$e'.isNotEmpty).join('\n'),
                          ),
                          isThreeLine: true,
                          trailing: TichStatusChip(
                            r['status']?.toString() ?? 'pending',
                            tone: toneForStatus(r['status']?.toString()),
                          ),
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
