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
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_widgets.dart';
import 'attendance_page.dart';
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
  bool _showCompensation = false;

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
      // ignore: unawaited_futures
      TichNotifications.instance.syncFromServerInBackground(isStudent: false);
    } catch (_) {}
  }

  Future<void> _syncAttendanceReminders() async {
    try {
      final data = await widget.api.attendance();
      final today = data['today'] as Map?;
      final clockedIn = today != null && today['clock_in_at'] != null;
      if (!mounted) return;
      setState(() => _clockedInToday = clockedIn);
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
            final staff =
                Map<String, dynamic>.from(data['staff'] as Map? ?? {});
            final contract =
                Map<String, dynamic>.from(data['contract'] as Map? ?? {});
            final compensation = Map<String, dynamic>.from(
              data['compensation'] as Map? ?? {},
            );
            final contractHistory =
                (data['contract_history'] as List? ?? const []);
            final employmentDuration =
                data['employment_duration']?.toString();

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
                    subtitle:
                        roleLine.isEmpty ? 'My Employee Portal' : roleLine,
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
                        PortalHeroPill(
                          icon: Icons.person_outline,
                          label: 'Update profile',
                          onTap: () => Navigator.of(context).push(
                            MaterialPageRoute(
                              builder: (_) => ProfilePage(api: widget.api),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  _EmploymentStatGrid(
                    employmentStatus: _labelEmploymentStatus(
                      staff['employment_status']?.toString(),
                    ),
                    contractLabel: _contractSummaryLabel(contract),
                    monthlyCompensation: _kes(
                      compensation['total_monthly'],
                      obscure: !_showCompensation,
                    ),
                    timeWithTich: employmentDuration?.isNotEmpty == true
                        ? employmentDuration!
                        : '—',
                    showCompensation: _showCompensation,
                    onToggleCompensation: () => setState(
                      () => _showCompensation = !_showCompensation,
                    ),
                  ),
                  const SizedBox(height: 16),
                  const TichSectionHeader('Employment & contract'),
                  TichCard(
                    child: Column(
                      children: [
                        _EmploymentKvRow(
                          label: 'Department',
                          value: staff['department']?.toString(),
                        ),
                        _EmploymentKvRow(
                          label: 'Campus',
                          value: staff['campus']?.toString(),
                        ),
                        _EmploymentKvRow(
                          label: 'Job title',
                          value: staff['job_title']?.toString(),
                        ),
                        _EmploymentKvRow(
                          label: 'Line manager',
                          value: staff['line_manager']?.toString(),
                        ),
                        _EmploymentKvRow(
                          label: 'Start date',
                          value: _formatEmploymentDate(
                            staff['employment_start_date'] ??
                                contract['start_date'],
                          ),
                        ),
                        _EmploymentKvRow(
                          label: 'Contract end',
                          value: contract['end_date'] != null
                              ? _formatEmploymentDate(contract['end_date'])
                              : 'Open-ended / permanent',
                        ),
                        _EmploymentKvRow(
                          label: 'Contract no.',
                          value: contract['number']?.toString(),
                        ),
                        _EmploymentKvRow(
                          label: 'Contract type',
                          value: _labelContractType(
                            contract['type']?.toString(),
                          ),
                          showDivider: false,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  const TichSectionHeader('Compensation'),
                  TichCard(
                    child: Column(
                      children: [
                        _CompensationRow(
                          label: 'Consolidated gross pay',
                          amount: compensation['gross_monthly_salary'],
                          revealed: _showCompensation,
                          onToggle: () => setState(
                            () => _showCompensation = !_showCompensation,
                          ),
                        ),
                        _CompensationRow(
                          label: 'Allowances',
                          amount: compensation['allowances_total'],
                          revealed: _showCompensation,
                          onToggle: () => setState(
                            () => _showCompensation = !_showCompensation,
                          ),
                        ),
                        _CompensationRow(
                          label: 'Total package',
                          amount: compensation['total_monthly'],
                          revealed: _showCompensation,
                          onToggle: () => setState(
                            () => _showCompensation = !_showCompensation,
                          ),
                          showDivider: false,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  const TichSectionHeader('Contract history'),
                  if (contractHistory.isEmpty)
                    const TichCard(
                      child: TichEmpty('No contracts on file.'),
                    )
                  else
                    TichCard(
                      child: Column(
                        children: [
                          for (var i = 0; i < contractHistory.length; i++) ...[
                            if (i > 0) const Divider(height: 20),
                            _ContractHistoryTile(
                              item: Map<String, dynamic>.from(
                                contractHistory[i] as Map,
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  const SizedBox(height: 8),
                ],
              ),
            );
          },
        ),
      ),
    );
  }
}

String _labelEmploymentStatus(String? raw) {
  if (raw == null || raw.isEmpty) return '—';
  return raw
      .split('_')
      .map((w) =>
          w.isEmpty ? w : '${w[0].toUpperCase()}${w.substring(1).toLowerCase()}')
      .join(' ');
}

String _labelContractType(String? raw) {
  if (raw == null || raw.isEmpty) return '—';
  return _labelEmploymentStatus(raw);
}

String _contractSummaryLabel(Map<String, dynamic> contract) {
  final status = contract['status']?.toString();
  if (status == 'open_ended') return 'Open-ended';
  final days = (contract['days_remaining'] as num?)?.toInt();
  if (days != null && days >= 0) return '$days days left';
  if (contract['end_date'] != null &&
      contract['end_date'].toString().isNotEmpty) {
    return 'Expired';
  }
  return 'Not set';
}

String _formatEmploymentDate(dynamic value) {
  if (value == null || value.toString().isEmpty) return '—';
  return formatDate(value);
}

String _kes(dynamic value, {required bool obscure}) {
  final n = (value is num) ? value.toDouble() : double.tryParse('$value') ?? 0;
  final formatted = 'KES ${n.toStringAsFixed(2)}';
  if (!obscure) return formatted;
  return 'KES •••••';
}

class _EmploymentStatGrid extends StatelessWidget {
  const _EmploymentStatGrid({
    required this.employmentStatus,
    required this.contractLabel,
    required this.monthlyCompensation,
    required this.timeWithTich,
    required this.showCompensation,
    required this.onToggleCompensation,
  });

  final String employmentStatus;
  final String contractLabel;
  final String monthlyCompensation;
  final String timeWithTich;
  final bool showCompensation;
  final VoidCallback onToggleCompensation;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final w = (constraints.maxWidth - 8) / 2;
        return Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            _StatCard(
              width: w,
              caption: 'Employment status',
              value: employmentStatus,
            ),
            _StatCard(
              width: w,
              caption: 'Contract',
              value: contractLabel,
            ),
            _StatCard(
              width: w,
              caption: 'Monthly compensation',
              value: monthlyCompensation,
              trailing: IconButton(
                visualDensity: VisualDensity.compact,
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                tooltip: showCompensation ? 'Hide amounts' : 'Show amounts',
                onPressed: onToggleCompensation,
                icon: Icon(
                  showCompensation
                      ? Icons.visibility_outlined
                      : Icons.visibility_off_outlined,
                  size: 18,
                  color: TichColors.neutralMuted,
                ),
              ),
            ),
            _StatCard(
              width: w,
              caption: 'Time with TICH',
              value: timeWithTich,
            ),
          ],
        );
      },
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({
    required this.width,
    required this.caption,
    required this.value,
    this.trailing,
  });

  final double width;
  final String caption;
  final String value;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: width,
      child: TichCard(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              caption.toUpperCase(),
              style: tichUi(
                fontSize: 10,
                fontWeight: FontWeight.w700,
                letterSpacing: 0.6,
                color: TichColors.neutralMuted,
              ),
            ),
            const SizedBox(height: 6),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Text(
                    value,
                    style: tichUi(
                      fontWeight: FontWeight.w700,
                      fontSize: 15,
                    ),
                  ),
                ),
                if (trailing != null) trailing!,
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _EmploymentKvRow extends StatelessWidget {
  const _EmploymentKvRow({
    required this.label,
    required this.value,
    this.showDivider = true,
  });

  final String label;
  final String? value;
  final bool showDivider;

  @override
  Widget build(BuildContext context) {
    final display =
        (value != null && value!.trim().isNotEmpty) ? value!.trim() : '—';
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 10),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                flex: 2,
                child: Text(
                  label.toUpperCase(),
                  style: tichUi(
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                    letterSpacing: 0.5,
                    color: TichColors.neutralMuted,
                  ),
                ),
              ),
              Expanded(
                flex: 3,
                child: Text(
                  display,
                  style: tichUi(fontWeight: FontWeight.w600, fontSize: 14),
                ),
              ),
            ],
          ),
        ),
        if (showDivider) const Divider(height: 1),
      ],
    );
  }
}

class _CompensationRow extends StatelessWidget {
  const _CompensationRow({
    required this.label,
    required this.amount,
    required this.revealed,
    required this.onToggle,
    this.showDivider = true,
  });

  final String label;
  final dynamic amount;
  final bool revealed;
  final VoidCallback onToggle;
  final bool showDivider;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 10),
          child: Row(
            children: [
              Expanded(
                child: Text(
                  label.toUpperCase(),
                  style: tichUi(
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                    letterSpacing: 0.5,
                    color: TichColors.neutralMuted,
                  ),
                ),
              ),
              Text(
                _kes(amount, obscure: !revealed),
                style: tichUi(fontWeight: FontWeight.w700),
              ),
              IconButton(
                visualDensity: VisualDensity.compact,
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                onPressed: onToggle,
                icon: Icon(
                  revealed
                      ? Icons.visibility_outlined
                      : Icons.visibility_off_outlined,
                  size: 18,
                  color: TichColors.neutralMuted,
                ),
              ),
            ],
          ),
        ),
        if (showDivider) const Divider(height: 1),
      ],
    );
  }
}

class _ContractHistoryTile extends StatelessWidget {
  const _ContractHistoryTile({required this.item});

  final Map<String, dynamic> item;

  @override
  Widget build(BuildContext context) {
    final number = item['contract_number']?.toString() ?? 'Contract';
    final type = _labelContractType(item['contract_type']?.toString());
    final start = _formatEmploymentDate(item['start_date']);
    final end = item['end_date'] != null
        ? _formatEmploymentDate(item['end_date'])
        : 'Ongoing';
    final expired = item['is_expired'] == true;
    final expiringSoon = item['is_expiring_soon'] == true;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: RichText(
                text: TextSpan(
                  style: tichUi(fontWeight: FontWeight.w700, fontSize: 14),
                  children: [
                    TextSpan(text: number),
                    TextSpan(
                      text: ' · $type',
                      style: tichUi(
                        fontWeight: FontWeight.w500,
                        color: TichColors.neutralMuted,
                      ),
                    ),
                  ],
                ),
              ),
            ),
            if (expired)
              const TichStatusChip('Expired', tone: TichChipTone.neutral)
            else if (expiringSoon)
              const TichStatusChip('Expiring soon', tone: TichChipTone.warning),
          ],
        ),
        const SizedBox(height: 4),
        Text(
          '$start → $end',
          style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
        ),
      ],
    );
  }
}
