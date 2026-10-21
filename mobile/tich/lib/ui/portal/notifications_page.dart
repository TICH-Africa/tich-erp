import 'dart:async';

import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import '../../data/tich_screen_cache.dart';
import '../widgets/tich_live_refresh.dart';
import '../../notifications/tich_notification_router.dart';

class NotificationsPage extends StatefulWidget {
  const NotificationsPage({super.key, required this.api});

  final EmployeeApi api;

  @override
  State<NotificationsPage> createState() => _NotificationsPageState();
}

class _NotificationsPageState extends State<NotificationsPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;

  @override
  void initState() {
    super.initState();
    _dataCache = TichScreenCache.read<Map<String, dynamic>>('portal.notifications');
    _future = TichScreenCache.ensure('portal.notifications', () => widget.api.notifications());
  }

  Future<void> _reload() => tichRefreshCached<Map<String, dynamic>>(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.notifications(),
      onData: (data) => _dataCache = data,
      persistKey: 'portal.notifications',
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

  void _openNotification(Map<String, dynamic> n) {
    final id = (n['id'] as num?)?.toInt();
    if (n['is_read'] != true && id != null) {
      unawaited(_markRead(id));
    }
    TichNotificationRouter.instance.navigateFromInApp(
      context,
      actionUrl: n['action_url']?.toString(),
      relatedEntityType: n['related_entity_type']?.toString(),
    );
  }

  void _openActionItem(Map<String, dynamic> a) {
    TichNotificationRouter.instance.navigateFromInApp(
      context,
      sidebarKey: a['key']?.toString(),
    );
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
          final merged = tichMergeSnapshot(snap, _dataCache as dynamic, persistKey: 'portal.notifications');
          if (tichShowLoadingSpinner(snap, merged)) { return tichInstantWaitBody(onRefresh: () async => _reload()); }
          if (merged == null && snap.hasError) {
            return TichErrorRefresh(
              message: snap.error.toString(),
              onRetry: _reload,
            );
          }
          final data = merged! as Map<String, dynamic>;
          final items = data['items'] as List? ?? const [];
          final actions = data['action_items'] as List? ?? const [];
          final unread = (data['unread'] as num?)?.toInt() ?? 0;

          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Container(
                  padding: const EdgeInsets.all(18),
                  decoration: BoxDecoration(
                    color: TichColors.blue,
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Inbox',
                              style: GoogleFonts.merriweather(
                                color: Colors.white,
                                fontWeight: FontWeight.w700,
                                fontSize: 18,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              unread == 0
                                  ? 'You are all caught up'
                                  : '$unread unread notification${unread == 1 ? '' : 's'}',
                              style: tichUi(color: Colors.white70, fontSize: 13),
                            ),
                          ],
                        ),
                      ),
                      CircleAvatar(
                        backgroundColor: Colors.white.withValues(alpha: 0.2),
                        child: Text(
                          unread > 99 ? '99+' : '$unread',
                          style: tichUi(
                            color: Colors.white,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                if (actions.isNotEmpty) ...[
                  const SizedBox(height: 18),
                  const TichSectionHeader('Action needed'),
                  ...actions.map((raw) {
                    final a = Map<String, dynamic>.from(raw as Map);
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        onTap: () => _openActionItem(a),
                        child: ListTile(
                          contentPadding: EdgeInsets.zero,
                          leading: CircleAvatar(
                            backgroundColor: TichColors.blueLight,
                            child: Icon(Icons.bolt, color: TichColors.blueDark),
                          ),
                          title: Text(
                            a['title']?.toString() ?? 'Action',
                            style: tichUi(fontWeight: FontWeight.w700),
                          ),
                          trailing: TichStatusChip(
                            '${a['count'] ?? 0}',
                            tone: TichChipTone.warning,
                          ),
                        ),
                      ),
                    );
                  }),
                ],
                const SizedBox(height: 12),
                const TichSectionHeader('Recent'),
                if (items.isEmpty)
                  const TichCard(child: TichEmpty('No notifications yet.'))
                else
                  ...items.map((raw) {
                    final n = Map<String, dynamic>.from(raw as Map);
                    final unreadItem = n['is_read'] != true;
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        onTap: () => _openNotification(n),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Container(
                              width: 10,
                              height: 10,
                              margin: const EdgeInsets.only(top: 6, right: 12),
                              decoration: BoxDecoration(
                                color: unreadItem
                                    ? TichColors.green
                                    : TichColors.neutralBorder,
                                shape: BoxShape.circle,
                              ),
                            ),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    n['title']?.toString() ?? 'Notification',
                                    style: tichUi(
                                      fontWeight: FontWeight.w700,
                                      fontSize: 15,
                                    ),
                                  ),
                                  if ((n['body']?.toString() ?? '').isNotEmpty) ...[
                                    const SizedBox(height: 4),
                                    Text(
                                      n['body'].toString(),
                                      style: tichUi(
                                        fontSize: 13,
                                        color: TichColors.neutralMuted,
                                        height: 1.35,
                                      ),
                                    ),
                                  ],
                                  const SizedBox(height: 6),
                                  Text(
                                    formatDateTime(n['created_at']),
                                    style: tichUi(fontSize: 11),
                                  ),
                                ],
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
