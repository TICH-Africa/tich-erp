import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/staff_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_widgets.dart';
import 'staff_helpers.dart';

class StaffUnitsPage extends StatefulWidget {
  const StaffUnitsPage({super.key, required this.api});

  final StaffApi api;

  @override
  State<StaffUnitsPage> createState() => _StaffUnitsPageState();
}

class _StaffUnitsPageState extends State<StaffUnitsPage> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.units();
  }

  void _reload() => setState(() => _future = widget.api.units());

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'My units',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return staffLoadingBody();
          }
          if (snap.hasError) {
            return staffErrorBody(snap.error, _reload);
          }
          final items = snap.data!['items'] as List? ?? const [];
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: items.isEmpty
                ? ListView(
                    children: const [
                      TichCard(child: TichEmpty('No active unit allocations.')),
                    ],
                  )
                : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: items.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemBuilder: (context, i) {
                      final u = Map<String, dynamic>.from(items[i] as Map);
                      return TichCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              [
                                u['unit_code'],
                                u['unit_name'],
                              ]
                                  .where((e) => e != null && '$e'.isNotEmpty)
                                  .join(' · '),
                              style: tichUi(fontWeight: FontWeight.w700),
                            ),
                            const SizedBox(height: 6),
                            Text(
                              [
                                u['semester'],
                                u['academic_year'],
                                u['campus'],
                              ]
                                  .where((e) => e != null && '$e'.isNotEmpty)
                                  .join(' · '),
                              style: tichUi(
                                fontSize: 13,
                                color: TichColors.neutralMuted,
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
    );
  }
}
