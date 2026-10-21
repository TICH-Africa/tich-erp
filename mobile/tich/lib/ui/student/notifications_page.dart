import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/student_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_live_refresh.dart';
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
  Object? _dataCache;

  @override
  void initState() {
    super.initState();
    studentPrimeScreen(
      persistKey: 'student.notifications',
      setCache: (c) => _dataCache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.notifications(),
    );
  }

  Future<void> _reload() => studentCachedReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.notifications(),
      onData: (data) => _dataCache = data,
      persistKey: 'student.notifications',
    );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.notifications(),
      onData: (data) => _dataCache = data,
    );
  }

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
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
      title: 'Notifications',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(snap, _dataCache as dynamic, persistKey: 'student.notifications');
          if (tichShowLoadingSpinner(snap, merged)) { return studentLoadingBody(onRefresh: () async => _reload()); }
          if (merged == null && snap.hasError) {
            return studentErrorBody(snap.error, _reload);
          }
          final data = merged! as Map<String, dynamic>;
          final items = data['items'] as List? ?? const [];
          final unread = (data['unread'] as num?)?.toInt() ?? 0;

          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Container(
                  padding: const EdgeInsets.all(18),
                  decoration: BoxDecoration(
                    color: TichColors.greenDark,
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Student alerts',
                              style: GoogleFonts.merriweather(
                                color: Colors.white,
                                fontWeight: FontWeight.w700,
                                fontSize: 18,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              unread == 0
                                  ? 'No new alerts'
                                  : '$unread unread',
                              style: tichUi(color: Colors.white70, fontSize: 13),
                            ),
                          ],
                        ),
                      ),
                      Icon(Icons.notifications_active,
                          color: Colors.white.withValues(alpha: 0.9), size: 28),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                if (items.isEmpty)
                  const TichCard(child: TichEmpty('No notifications.'))
                else
                  ...items.map((raw) {
                    final n = Map<String, dynamic>.from(raw as Map);
                    final unreadItem = n['read_at'] == null;
                    final id = (n['id'] as num?)?.toInt();
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        onTap: unreadItem && id != null
                            ? () => _markRead(id)
                            : null,
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
                                if (unreadItem)
                                  const TichStatusChip('New',
                                      tone: TichChipTone.info),
                              ],
                            ),
                            if ((n['body']?.toString() ?? '').isNotEmpty) ...[
                              const SizedBox(height: 6),
                              Text(n['body'].toString(),
                                  style: tichUi(fontSize: 13, height: 1.35)),
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
