import 'dart:async';

import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/student_api.dart';
import '../../notifications/tich_notifications.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/portal_hero.dart';
import '../widgets/quick_actions.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_widgets.dart';
import 'documents_page.dart';
import 'finance_page.dart';
import 'notifications_page.dart';
import 'profile_page.dart';
import 'student_helpers.dart';
import 'suggestions_page.dart';
import 'timetable_page.dart';

class StudentHomePage extends StatefulWidget {
  const StudentHomePage({super.key, required this.api});

  final StudentApi api;

  @override
  State<StudentHomePage> createState() => _StudentHomePageState();
}

class _StudentHomePageState extends State<StudentHomePage> {
  late Future<Map<String, dynamic>> _future;
  int _unread = 0;
  Map<String, dynamic>? _nextLesson;

  @override
  void initState() {
    super.initState();
    _future = widget.api.dashboard().then((data) {
      final unread = (data['unread_notifications'] as num?)?.toInt() ?? 0;
      // Paint home first; timetable + reminders run in the background.
      scheduleMicrotask(() {
        if (!mounted) return;
        setState(() => _unread = unread);
        unawaited(_primeLessonReminders());
      });
      return data;
    });
  }

  void _reload() {
    setState(() {
      _future = widget.api.dashboard().then((data) {
        final unread =
            (data['unread_notifications'] as num?)?.toInt() ?? 0;
        scheduleMicrotask(() {
          if (!mounted) return;
          setState(() => _unread = unread);
          unawaited(_primeLessonReminders());
        });
        return data;
      });
    });
  }

  void _liveRefresh() {
    unawaited(
      widget.api.dashboard().then((data) {
        if (!mounted) return;
        final unread = (data['unread_notifications'] as num?)?.toInt() ?? 0;
        setState(() {
          _future = Future.value(data);
          _unread = unread;
        });
        unawaited(_primeLessonReminders());
      }).catchError((_) {}),
    );
  }

  Future<void> _primeLessonReminders() async {
    try {
      final tt = await widget.api.timetable();
      final sessions = lessonSessionsFromTimetablePayload(tt);
      if (!mounted) return;
      setState(() => _nextLesson = nextLessonToday(sessions));
      unawaited(
        TichNotifications.instance.scheduleLessonReminders(sessions),
      );
    } catch (_) {}
  }

  Widget _notificationAction() {
    final icon =
        usesCupertino ? CupertinoIcons.bell : Icons.notifications_outlined;
    return Stack(
      clipBehavior: Clip.none,
      children: [
        IconButton(
          tooltip: 'Notifications',
          onPressed: () async {
            await Navigator.of(context).push(
              MaterialPageRoute(
                builder: (_) => StudentNotificationsPage(api: widget.api),
              ),
            );
            _reload();
          },
          icon: Icon(icon, color: usesCupertino ? TichColors.white : null),
        ),
        if (_unread > 0)
          Positioned(
            right: 6,
            top: 6,
            child: Container(
              constraints: const BoxConstraints(minWidth: 16),
              height: 16,
              padding: const EdgeInsets.symmetric(horizontal: 4),
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: Colors.red.shade600,
                borderRadius: BorderRadius.circular(999),
              ),
              child: Text(
                _unread > 99 ? '99+' : '$_unread',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 9,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
      title: 'Student Portal',
      actions: [_notificationAction()],
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return studentLoadingBody();
          }
          if (snap.hasError) {
            return studentErrorBody(snap.error, _reload);
          }
          final data = snap.data!;
          final student =
              Map<String, dynamic>.from(data['student'] as Map? ?? {});
          final stats =
              Map<String, dynamic>.from(data['overview_stats'] as Map? ?? {});
          final finance =
              Map<String, dynamic>.from(data['finance_summary'] as Map? ?? {});
          final preview = data['notifications_preview'] as List? ?? const [];

          final identity = [
            student['registration_number'],
            student['program'],
          ].where((e) => e != null && '$e'.isNotEmpty).join(' · ');
          final nextUnit = _nextLesson == null
              ? null
              : [
                  _nextLesson!['unit_code'],
                  _nextLesson!['unit_name'],
                ].where((e) => e != null && '$e'.isNotEmpty).join(' · ');
          final nextStart = _nextLesson?['start_time']?.toString();
          final nextRoom = _nextLesson?['room']?.toString();

          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                PortalHero(
                  title: student['full_name']?.toString() ?? 'Student',
                  subtitle:
                      identity.isEmpty ? 'Student Portal' : identity,
                  accent: TichColors.greenDark,
                  footer: Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      PortalHeroPill(
                        icon: Icons.school_outlined,
                        label: stats['enrollment_status']?.toString() ??
                            student['enrollment_status']?.toString() ??
                            'enrolled',
                      ),
                      PortalHeroPill(
                        icon: Icons.payments_outlined,
                        label:
                            'Fees: ${stats['fee_clearance'] ?? finance['fee_clearance_status'] ?? 'pending'}',
                      ),
                      if (_unread > 0)
                        PortalHeroPill(
                          icon: Icons.notifications_active_outlined,
                          label: '$_unread unread',
                        ),
                    ],
                  ),
                ),
                if (_nextLesson != null) ...[
                  const SizedBox(height: 14),
                  TichCard(
                    onTap: () => Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) =>
                            StudentTimetablePage(api: widget.api),
                      ),
                    ),
                    child: Row(
                      children: [
                        Container(
                          width: 44,
                          height: 44,
                          decoration: BoxDecoration(
                            color: TichColors.greenLight,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: const Icon(
                            Icons.schedule,
                            color: TichColors.greenDark,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Up next',
                                style: tichUi(
                                  fontSize: 11,
                                  color: TichColors.neutralMuted,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                nextUnit?.isNotEmpty == true
                                    ? nextUnit!
                                    : 'Upcoming class',
                                style: tichUi(fontWeight: FontWeight.w700),
                              ),
                              Text(
                                [
                                  if (nextStart != null && nextStart.isNotEmpty)
                                    nextStart.substring(
                                      0,
                                      nextStart.length >= 5 ? 5 : nextStart.length,
                                    ),
                                  if (nextRoom != null && nextRoom.isNotEmpty)
                                    'Room $nextRoom',
                                ].join(' · '),
                                style: tichUi(
                                  fontSize: 12,
                                  color: TichColors.neutralMuted,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const Icon(
                          Icons.chevron_right,
                          color: TichColors.neutralMuted,
                        ),
                      ],
                    ),
                  ),
                ],
                const SizedBox(height: 16),
                const TichSectionHeader('Overview'),
                Row(
                  children: [
                    StudentStatTile(
                      label: 'Outstanding',
                      value: money(
                        stats['outstanding_balance'] ??
                            finance['outstanding_balance'],
                      ),
                    ),
                    const SizedBox(width: 8),
                    StudentStatTile(
                      label: 'Units',
                      value: '${stats['registered_unit_count'] ?? 0}',
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    StudentStatTile(
                      label: 'Grades',
                      value: '${stats['grade_count'] ?? 0}',
                    ),
                    const SizedBox(width: 8),
                    StudentStatTile(
                      label: 'Documents',
                      value: '${stats['document_count'] ?? 0}',
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                QuickActionsGrid(
                  actions: [
                    QuickAction(
                      label: 'Pay fees',
                      icon: Icons.payments_outlined,
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => StudentFinancePage(api: widget.api),
                        ),
                      ),
                    ),
                    QuickAction(
                      label: 'My profile',
                      icon: Icons.person_outline,
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => StudentProfilePage(api: widget.api),
                        ),
                      ),
                    ),
                    QuickAction(
                      label: 'Documents',
                      icon: Icons.folder_outlined,
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => StudentDocumentsPage(api: widget.api),
                        ),
                      ),
                    ),
                    QuickAction(
                      label: 'Suggestion box',
                      icon: Icons.lightbulb_outline,
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) =>
                              StudentSuggestionsPage(api: widget.api),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                const TichSectionHeader('Recent notifications'),
                if (preview.isEmpty)
                  const TichCard(child: TichEmpty('No notifications yet.'))
                else
                  ...preview.map((raw) {
                    final n = Map<String, dynamic>.from(raw as Map);
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              n['title']?.toString() ?? 'Notification',
                              style: tichUi(fontWeight: FontWeight.w600),
                            ),
                            if ((n['body']?.toString() ?? '').isNotEmpty) ...[
                              const SizedBox(height: 4),
                              Text(
                                n['body'].toString(),
                                style: tichUi(
                                  fontSize: 13,
                                  color: TichColors.neutralMuted,
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
