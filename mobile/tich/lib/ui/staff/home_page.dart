import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../adaptive/adaptive.dart';
import '../../bloc/auth/auth_bloc.dart';
import '../../bloc/auth/auth_state.dart';
import '../../data/staff_api.dart';
import '../../models/auth_user.dart';
import '../../data/tich_api_scope.dart';
import '../shell/portal_shell.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/portal_hero.dart';
import '../widgets/quick_actions.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_live_refresh.dart';
import 'attendance_page.dart';
import 'grading_page.dart';
import 'lesson_plans_page.dart';
import 'staff_helpers.dart';
import 'timetable_page.dart';
import 'units_page.dart';

class StaffHomePage extends StatefulWidget {
  const StaffHomePage({super.key, required this.api});

  final StaffApi api;

  @override
  State<StaffHomePage> createState() => _StaffHomePageState();
}

class _StaffHomePageState extends State<StaffHomePage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;

  @override
  void initState() {
    super.initState();
    staffPrimeScreen(
      persistKey: 'staff.home',
      setCache: (c) => _dataCache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.dashboard(),
    );
  }

  Future<void> _reload() => staffCachedReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.dashboard(),
      onData: (data) => _dataCache = data,
      persistKey: 'staff.home',
    );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.dashboard(),
      onData: (data) => _dataCache = data,
      persistKey: 'staff.home',
    );
  }

  bool _canSwitchToEmployee(AuthUser? user) {
    if (user == null || user.isStudent) return false;
    return user.canAccessEmployeePortal ||
        user.hasBothStaffPortals ||
        (user.staffId != null && user.isTeachingStaff);
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthBloc>().state;
    final user = auth is AuthAuthenticated ? auth.user : null;
    final employeePortalAction = _canSwitchToEmployee(user)
        ? [
            IconButton(
              tooltip: 'My Employee Portal',
              onPressed: () {
                if (user == null) return;
                tichOpenPortalRoot(user, const PortalShell());
              },
              icon: const Icon(Icons.badge_outlined),
            ),
          ]
        : null;

    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
      title: 'Staff Portal',
      actions: employeePortalAction,
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(snap, _dataCache as dynamic, persistKey: 'staff.home');
          if (tichShowLoadingSpinner(snap, merged)) { return staffLoadingBody(onRefresh: () async => _reload()); }
          if (merged == null && snap.hasError) {
            return staffErrorBody(snap.error, _reload);
          }
          final data = merged! as Map<String, dynamic>;
          final staff =
              Map<String, dynamic>.from(data['staff'] as Map? ?? {});
          final stats =
              Map<String, dynamic>.from(data['stats'] as Map? ?? {});
          final units = data['units'] as List? ?? const [];
          final upcoming = data['upcoming_sessions'] as List? ?? const [];
          final today = data['timetable_today'] as List? ?? const [];
          final alerts = data['alerts'] as List? ?? const [];

          final roleLine = [
            staff['job_title'],
            staff['department'],
          ].where((e) => e != null && '$e'.isNotEmpty).join(' · ');

          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                PortalHero(
                  title: staff['full_name']?.toString() ?? 'Teaching staff',
                  subtitle: roleLine.isEmpty
                      ? 'Teaching workspace'
                      : roleLine,
                  accent: TichColors.greenDark,
                  footer: const Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      PortalHeroPill(
                        icon: Icons.school_outlined,
                        label: 'Staff Portal',
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 14),
                Row(
                  children: [
                    StaffStatTile(
                      label: 'Units',
                      value: '${stats['allocation_count'] ?? 0}',
                    ),
                    const SizedBox(width: 8),
                    StaffStatTile(
                      label: 'Sessions',
                      value: '${stats['attendance_session_count'] ?? 0}',
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    StaffStatTile(
                      label: 'Lesson plans',
                      value: '${stats['lesson_plan_count'] ?? 0}',
                    ),
                    const SizedBox(width: 8),
                    StaffStatTile(
                      label: 'Alerts',
                      value: '${stats['alert_count'] ?? 0}',
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                QuickActionsGrid(
                  actions: [
                    QuickAction(
                      label: 'Class attendance',
                      icon: Icons.fact_check_outlined,
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => StaffAttendancePage(api: widget.api),
                        ),
                      ),
                    ),
                    QuickAction(
                      label: 'Marks',
                      icon: Icons.grade_outlined,
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => StaffGradingPage(api: widget.api),
                        ),
                      ),
                    ),
                    QuickAction(
                      label: 'Lesson plans',
                      icon: Icons.menu_book_outlined,
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) =>
                              StaffLessonPlansPage(api: widget.api),
                        ),
                      ),
                    ),
                    QuickAction(
                      label: 'Timetable',
                      icon: Icons.calendar_month_outlined,
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => StaffTimetablePage(api: widget.api),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                TichSectionHeader(
                  'Today\'s classes',
                  trailing: TextButton(
                    onPressed: () => Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => StaffTimetablePage(api: widget.api),
                      ),
                    ),
                    child: const Text('All'),
                  ),
                ),
                if (today.isEmpty)
                  const TichCard(child: TichEmpty('No classes scheduled today.'))
                else
                  ...today.take(4).map((raw) {
                    final s = Map<String, dynamic>.from(raw as Map);
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        child: Text(
                          '${s['start_time'] ?? '—'}–${s['end_time'] ?? '—'} · '
                          '${[
                            s['unit_code'],
                            s['unit_name'],
                          ].where((e) => e != null && '$e'.isNotEmpty).join(' · ')}'
                          '${(s['room']?.toString().isNotEmpty ?? false) ? ' · ${s['room']}' : ''}',
                          style: tichUi(fontWeight: FontWeight.w600),
                        ),
                      ),
                    );
                  }),
                const SizedBox(height: 8),
                TichSectionHeader(
                  'My units',
                  trailing: TextButton(
                    onPressed: () => Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => StaffUnitsPage(api: widget.api),
                      ),
                    ),
                    child: const Text('All'),
                  ),
                ),
                if (units.isEmpty)
                  const TichCard(child: TichEmpty('No active unit allocations.'))
                else
                  ...units.take(4).map((raw) {
                    final u = Map<String, dynamic>.from(raw as Map);
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
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
                            if (staffUnitCohortYearLine(u).isNotEmpty) ...[
                              const SizedBox(height: 4),
                              Text(
                                staffUnitCohortYearLine(u),
                                style: tichUi(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                  color: TichColors.blueDark,
                                ),
                              ),
                            ],
                            if (staffTrimmed(u['semester']) != null) ...[
                              const SizedBox(height: 2),
                              Text(
                                staffTrimmed(u['semester'])!,
                                style: tichUi(
                                  fontSize: 12,
                                  color: TichColors.neutralMuted,
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                    );
                  }),
                if (upcoming.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  const TichSectionHeader('Upcoming attendance'),
                  ...upcoming.take(3).map((raw) {
                    final s = Map<String, dynamic>.from(raw as Map);
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        child: Text(
                          '${formatDate(s['session_date'])} · '
                          '${[
                            s['unit_code'],
                            s['unit_name'],
                          ].where((e) => e != null && '$e'.isNotEmpty).join(' · ')}',
                          style: tichUi(fontWeight: FontWeight.w600),
                        ),
                      ),
                    );
                  }),
                ],
                if (alerts.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  const TichSectionHeader('Attendance alerts'),
                  ...alerts.take(3).map((raw) {
                    final a = Map<String, dynamic>.from(raw as Map);
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        child: Row(
                          children: [
                            Expanded(
                              child: Text(
                                '${a['student_name'] ?? a['registration_number'] ?? 'Student'}'
                                ' · ${a['unit_code'] ?? ''}',
                                style: tichUi(fontWeight: FontWeight.w600),
                              ),
                            ),
                            TichStatusChip(
                              '${a['attendance_percentage'] ?? 0}%',
                              tone: TichChipTone.warning,
                            ),
                          ],
                        ),
                      ),
                    );
                  }),
                ],
              ],
            ),
          );
        },
      ),
      ),
    );
  }
}
