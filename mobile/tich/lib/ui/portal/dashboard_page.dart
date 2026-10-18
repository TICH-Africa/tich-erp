import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/employee_api.dart';
import '../../notifications/tich_notifications.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/employee_avatar.dart';
import '../widgets/portal_hero.dart';
import '../widgets/quick_actions.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_widgets.dart';
import 'attendance_page.dart';
import 'documents_page.dart';
import 'leave_page.dart';
import 'notifications_page.dart';
import 'profile_page.dart';

class DashboardPage extends StatefulWidget {
  const DashboardPage({super.key, required this.api});

  final EmployeeApi api;

  @override
  State<DashboardPage> createState() => _DashboardPageState();
}

class _DashboardPageState extends State<DashboardPage> {
  late Future<Map<String, dynamic>> _future;
  int _notifCount = 0;
  bool? _clockedInToday;

  @override
  void initState() {
    super.initState();
    _future = widget.api.dashboard();
    _loadNotifications();
    _syncAttendanceReminders();
  }

  Future<void> _loadNotifications() async {
    try {
      final data = await widget.api.notifications();
      if (!mounted) return;
      setState(() =>
          _notifCount = (data['unread'] as num?)?.toInt() ??
              (data['total'] as num?)?.toInt() ??
              0);
      // Surface server clock-in reminders as system popups.
      // ignore: unawaited_futures
      TichNotifications.instance.syncFromServerInBackground(isStudent: false);
    } catch (_) {
      // Keep badge at 0 if notifications fail.
    }
  }

  Future<void> _syncAttendanceReminders() async {
    try {
      final data = await widget.api.attendance();
      final today = data['today'] as Map?;
      final clockedIn = today != null && today['clock_in_at'] != null;
      if (!mounted) return;
      setState(() => _clockedInToday = clockedIn);
      // Don't block the UI isolate on dozens of plugin schedules.
      // ignore: unawaited_futures
      TichNotifications.instance
          .scheduleClockInReminders(alreadyClockedIn: clockedIn);
    } catch (_) {}
  }

  void _reload() {
    setState(() => _future = widget.api.dashboard());
    _loadNotifications();
    _syncAttendanceReminders();
  }

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.dashboard(),
      apply: (f) => _future = f,
    );
    _loadNotifications();
    _syncAttendanceReminders();
  }

  Widget _notificationAction() {
    final icon = usesCupertino
        ? CupertinoIcons.bell
        : Icons.notifications_outlined;

    return Stack(
      clipBehavior: Clip.none,
      children: [
        IconButton(
          tooltip: 'Notifications',
          onPressed: () async {
            await Navigator.of(context).push(
              MaterialPageRoute(
                builder: (_) => NotificationsPage(api: widget.api),
              ),
            );
            _loadNotifications();
          },
          icon: Icon(icon, color: usesCupertino ? TichColors.white : null),
        ),
        if (_notifCount > 0)
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
                _notifCount > 99 ? '99+' : '$_notifCount',
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
      title: 'Employee Portal',
      actions: [_notificationAction()],
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: AdaptiveProgress());
          }
          if (snap.hasError) {
            return TichErrorRefresh(
              message: snap.error is ApiException
                  ? snap.error.toString()
                  : 'Could not load dashboard.',
              onRetry: _reload,
            );
          }
          final data = snap.data!;
          final staff = Map<String, dynamic>.from(data['staff'] as Map? ?? {});
          final contract =
              Map<String, dynamic>.from(data['contract'] as Map? ?? {});
          final leaveBalances = (data['leave_balances'] as List? ?? const []);
          final recentLeave =
              (data['recent_leave_requests'] as List? ?? const []);

          final roleLine = [
            staff['job_title'],
            staff['department'],
          ]
              .where((e) => e != null && e.toString().isNotEmpty)
              .join(' · ');
          final clockLabel = _clockedInToday == null
              ? 'Checking attendance…'
              : (_clockedInToday!
                  ? 'Clocked in today'
                  : 'Not clocked in yet');

          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                PortalHero(
                  title: staff['full_name']?.toString() ?? 'Employee',
                  subtitle: roleLine.isEmpty ? 'My Employee Portal' : roleLine,
                  accent: TichColors.blue,
                  trailing: EmployeeAvatar(
                    name: staff['full_name']?.toString() ?? 'Employee',
                    radius: 28,
                    photoUrl: staff['photo_url']?.toString(),
                  ),
                  footer: Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      PortalHeroPill(
                        icon: _clockedInToday == true
                            ? Icons.check_circle_outline
                            : Icons.fingerprint,
                        label: clockLabel,
                        onTap: () async {
                          await Navigator.of(context).push(
                            MaterialPageRoute(
                              builder: (_) =>
                                  AttendancePage(api: widget.api),
                            ),
                          );
                          _syncAttendanceReminders();
                          _loadNotifications();
                        },
                      ),
                      if (_notifCount > 0)
                        PortalHeroPill(
                          icon: Icons.notifications_active_outlined,
                          label: '$_notifCount unread',
                          onTap: () async {
                            await Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (_) =>
                                    NotificationsPage(api: widget.api),
                              ),
                            );
                            _loadNotifications();
                          },
                        ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                QuickActionsGrid(
                  actions: [
                    QuickAction(
                      label: 'Apply leave',
                      icon: Icons.event_available_outlined,
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => LeavePage(api: widget.api),
                        ),
                      ),
                    ),
                    QuickAction(
                      label: 'Clock in / out',
                      icon: Icons.fingerprint,
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => AttendancePage(api: widget.api),
                        ),
                      ),
                    ),
                    QuickAction(
                      label: 'Upload document',
                      icon: Icons.upload_file_outlined,
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => DocumentsPage(api: widget.api),
                        ),
                      ),
                    ),
                    QuickAction(
                      label: 'My profile',
                      icon: Icons.person_outline,
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => ProfilePage(api: widget.api),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                const TichSectionHeader('Contract'),
                TichCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              contract['job_title']?.toString() ?? '—',
                              style: tichUi(
                                fontWeight: FontWeight.w700,
                                fontSize: 15,
                              ),
                            ),
                          ),
                          TichStatusChip(
                            contract['status']?.toString() ?? 'active',
                            tone: toneForStatus(contract['status']?.toString()),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'Period: ${formatDateTime(contract['start_date'])} → ${formatDateTime(contract['end_date'])}',
                        style: tichUi(fontSize: 13),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                const TichSectionHeader('Leave balances'),
                if (leaveBalances.isEmpty)
                  const TichCard(child: TichEmpty('No leave balances yet.'))
                else
                  ...leaveBalances.map((raw) {
                    final b = Map<String, dynamic>.from(raw as Map);
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        child: Row(
                          children: [
                            Expanded(
                              child: Text(
                                b['leave_type_name']?.toString() ?? 'Leave',
                                style: tichUi(fontWeight: FontWeight.w600),
                              ),
                            ),
                            Text(
                              '${b['balance_days']} days',
                              style: tichUi(
                                color: TichColors.greenDark,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  }),
                const SizedBox(height: 8),
                const TichSectionHeader('Recent leave'),
                if (recentLeave.isEmpty)
                  const TichCard(child: TichEmpty('No recent leave requests.'))
                else
                  ...recentLeave.map((raw) {
                    final r = Map<String, dynamic>.from(raw as Map);
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    r['leave_type_name']?.toString() ?? 'Leave',
                                    style: tichUi(fontWeight: FontWeight.w600),
                                  ),
                                ),
                                TichStatusChip(
                                  r['overall_status']?.toString() ?? '',
                                  tone: toneForStatus(
                                    r['overall_status']?.toString(),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 4),
                            Text(
                              '${formatDateTime(r['start_date'])} → ${formatDateTime(r['end_date'])}',
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
