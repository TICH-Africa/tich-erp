import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../data/api_client.dart';
import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
import '../shell/portal_shell.dart';
import '../widgets/tich_widgets.dart';

class DashboardPage extends StatefulWidget {
  const DashboardPage({super.key, required this.api});

  final EmployeeApi api;

  @override
  State<DashboardPage> createState() => _DashboardPageState();
}

class _DashboardPageState extends State<DashboardPage> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.dashboard();
  }

  void _reload() => setState(() => _future = widget.api.dashboard());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: tichAppBar(context, title: 'My Employee Portal', showLogout: true),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
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
                              ].where((e) => e != null && e.toString().isNotEmpty).join(' · '),
                              style: tichUi(
                                color: TichColors.neutralMuted,
                                fontSize: 13,
                              ),
                            ),
                            if (data['employment_duration'] != null) ...[
                              const SizedBox(height: 4),
                              Text(
                                'With TICH for ${data['employment_duration']}',
                                style: tichUi(
                                  color: TichColors.greenDark,
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
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
                        'Type: ${contract['type'] ?? '—'}',
                        style: tichUi(fontSize: 13),
                      ),
                      Text(
                        'Period: ${_fmt(contract['start_date'])} → ${_fmt(contract['end_date'])}',
                        style: tichUi(fontSize: 13),
                      ),
                      if (contract['days_remaining'] != null)
                        Text(
                          'Days remaining: ${contract['days_remaining']}',
                          style: tichUi(
                            fontSize: 13,
                            color: TichColors.blueDark,
                          ),
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
                                style: tichUi(
                                  fontWeight: FontWeight.w600,
                                ),
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
                                    style: tichUi(
                                      fontWeight: FontWeight.w600,
                                    ),
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
                              '${_fmt(r['start_date'])} → ${_fmt(r['end_date'])} · ${r['days_requested']} day(s)',
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
                if ((data['pending_profile_changes'] as num?)?.toInt() != null &&
                    (data['pending_profile_changes'] as num).toInt() > 0) ...[
                  const SizedBox(height: 16),
                  TichCard(
                    child: Row(
                      children: [
                        const Icon(Icons.info_outline, color: TichColors.blue),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            '${data['pending_profile_changes']} profile change(s) awaiting HR approval.',
                            style: tichUi(fontSize: 13),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
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

  String _fmt(dynamic value) {
    if (value == null) return '—';
    return value.toString();
  }
}
