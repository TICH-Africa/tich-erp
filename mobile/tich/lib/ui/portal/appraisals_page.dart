import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
import '../../adaptive/adaptive.dart';
import '../shell/portal_shell.dart';
import '../widgets/tich_widgets.dart';

class AppraisalsPage extends StatefulWidget {
  const AppraisalsPage({super.key, required this.api});

  final EmployeeApi api;

  @override
  State<AppraisalsPage> createState() => _AppraisalsPageState();
}

class _AppraisalsPageState extends State<AppraisalsPage> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.appraisals();
  }

  void _reload() => setState(() => _future = widget.api.appraisals());

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Appraisals',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: AdaptiveProgress());
          }
          if (snap.hasError) {
            return TichErrorRefresh(message: snap.error.toString(), onRetry: _reload);
          }
          final items = snap.data!['appraisals'] as List? ?? const [];
          if (items.isEmpty) {
            return const Center(child: TichEmpty('No appraisals yet.'));
          }
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (context, i) {
                final a = Map<String, dynamic>.from(items[i] as Map);
                return TichCard(
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(
                          a['cycle']?.toString() ?? 'Appraisal',
                          style: tichUi(fontWeight: FontWeight.w700),
                        ),
                      ),
                      TichStatusChip(
                        a['status']?.toString() ?? '',
                        tone: toneForStatus(a['status']?.toString()),
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
