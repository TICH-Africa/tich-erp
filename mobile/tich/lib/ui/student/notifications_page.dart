import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/student_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import 'student_helpers.dart';

class StudentNotificationsPage extends StatefulWidget {
  const StudentNotificationsPage({super.key, required this.api});

  final StudentApi api;

  @override
  State<StudentNotificationsPage> createState() =>
      _StudentNotificationsPageState();
}

class _StudentNotificationsPageState extends State<StudentNotificationsPage> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.notifications();
  }

  void _reload() => setState(() => _future = widget.api.notifications());

  Future<void> _markRead(int id) async {
    try {
      await widget.api.markNotificationRead(id);
      _reload();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Notifications',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return studentLoadingBody();
          }
          if (snap.hasError) {
            return studentErrorBody(snap.error, _reload);
          }
          final items = snap.data!['items'] as List? ?? const [];
          if (items.isEmpty) {
            return const Center(child: TichEmpty('No notifications.'));
          }
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (context, i) {
                final n = Map<String, dynamic>.from(items[i] as Map);
                final unread = n['read_at'] == null;
                return TichCard(
                  onTap: unread ? () => _markRead((n['id'] as num).toInt()) : null,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              n['title']?.toString() ?? 'Notification',
                              style: tichUi(fontWeight: FontWeight.w700),
                            ),
                          ),
                          if (unread)
                            const TichStatusChip('New', tone: TichChipTone.info),
                        ],
                      ),
                      if ((n['body']?.toString() ?? '').isNotEmpty) ...[
                        const SizedBox(height: 6),
                        Text(n['body'].toString(), style: tichUi(fontSize: 13)),
                      ],
                      const SizedBox(height: 6),
                      Text(
                        formatDateTime(n['created_at']),
                        style: tichUi(
                          fontSize: 12,
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
