import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import 'notifications_page.dart';

class DashboardPage extends StatefulWidget {
  const DashboardPage({super.key, required this.api});

  final EmployeeApi api;

  @override
  State<DashboardPage> createState() => _DashboardPageState();
}

class _DashboardPageState extends State<DashboardPage> {
  late Future<Map<String, dynamic>> _future;
  int _notifCount = 0;

  @override
  void initState() {
    super.initState();
    _future = widget.api.dashboard();
    _loadNotifications();
  }

  Future<void> _loadNotifications() async {
    try {
      final data = await widget.api.notifications();
      if (!mounted) return;
      setState(() => _notifCount = (data['total'] as num?)?.toInt() ?? 0);
    } catch (_) {
      // Keep badge at 0 if notifications fail.
    }
  }

  void _reload() {
    setState(() => _future = widget.api.dashboard());
    _loadNotifications();
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
    return AdaptiveScaffold(
      title: 'My Employee Portal',
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

          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                TichCard(
                  child: Row(
                    children: [
                      CircleAvatar(
                        radius: 28,
                        backgroundColor: TichColors.blueLight,
                        backgroundImage: (staff['photo_url'] != null &&
                                staff['photo_url'].toString().isNotEmpty)
                            ? NetworkImage(staff['photo_url'].toString())
                            : null,
                        child: (staff['photo_url'] == null ||
                                staff['photo_url'].toString().isEmpty)
                            ? Text(
                                _initials(staff['full_name']?.toString()),
                                style: tichUi(
                                  color: TichColors.blueDark,
                                  fontWeight: FontWeight.w700,
                                ),
                              )
                            : null,
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              staff['full_name']?.toString() ?? 'Employee',
                              style: GoogleFonts.merriweather(
                                fontSize: 18,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              [
                                staff['job_title'],
                                staff['department'],
                              ]
                                  .where((e) =>
                                      e != null && e.toString().isNotEmpty)
                                  .join(' · '),
                              style: tichUi(
                                color: TichColors.neutralMuted,
                                fontSize: 13,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
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
    );
  }

  String _initials(String? name) {
    final parts = (name ?? '').trim().split(RegExp(r'\s+'));
    if (parts.isEmpty || parts.first.isEmpty) return 'T';
    if (parts.length == 1) return parts.first[0].toUpperCase();
    return (parts.first[0] + parts.last[0]).toUpperCase();
  }
}
