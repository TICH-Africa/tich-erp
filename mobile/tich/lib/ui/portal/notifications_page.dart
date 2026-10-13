import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_widgets.dart';

class NotificationsPage extends StatefulWidget {
  const NotificationsPage({super.key, required this.api});

  final EmployeeApi api;

  @override
  State<NotificationsPage> createState() => _NotificationsPageState();
}

class _NotificationsPageState extends State<NotificationsPage> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.notifications();
  }

  void _reload() => setState(() => _future = widget.api.notifications());

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Notifications',
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
          final items = snap.data!['items'] as List? ?? const [];
          if (items.isEmpty) {
            return const Center(child: TichEmpty('You are all caught up.'));
          }
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (context, i) {
                final n = Map<String, dynamic>.from(items[i] as Map);
                return TichCard(
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(
                          n['title']?.toString() ?? 'Notification',
                          style: tichUi(fontWeight: FontWeight.w600),
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 10,
                          vertical: 4,
                        ),
                        decoration: BoxDecoration(
                          color: Colors.red.shade600,
                          borderRadius: BorderRadius.circular(999),
                        ),
                        child: Text(
                          '${n['count'] ?? 0}',
                          style: tichUi(
                            color: Colors.white,
                            fontWeight: FontWeight.w700,
                            fontSize: 12,
                          ),
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
