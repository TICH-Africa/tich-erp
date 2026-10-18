import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../adaptive/adaptive.dart';
import '../../bloc/auth/auth_bloc.dart';
import '../../bloc/auth/auth_event.dart';
import '../../data/api_client.dart';
import '../../data/student_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_widgets.dart';
import 'student_helpers.dart';

class StudentProfilePage extends StatefulWidget {
  const StudentProfilePage({super.key, required this.api});

  final StudentApi api;

  @override
  State<StudentProfilePage> createState() => _StudentProfilePageState();
}

class _StudentProfilePageState extends State<StudentProfilePage> {
  late Future<Map<String, dynamic>> _future;
  final _phone = TextEditingController();
  final _email = TextEditingController();
  final _county = TextEditingController();
  final _address = TextEditingController();
  final _nokName = TextEditingController();
  final _nokRel = TextEditingController();
  final _nokPhone = TextEditingController();
  final _emergencyName = TextEditingController();
  final _emergencyPhone = TextEditingController();
  final _emergencyRel = TextEditingController();
  bool _busy = false;
  bool _hydrated = false;

  @override
  void initState() {
    super.initState();
    _future = widget.api.profile();
  }

  @override
  void dispose() {
    _phone.dispose();
    _email.dispose();
    _county.dispose();
    _address.dispose();
    _nokName.dispose();
    _nokRel.dispose();
    _nokPhone.dispose();
    _emergencyName.dispose();
    _emergencyPhone.dispose();
    _emergencyRel.dispose();
    super.dispose();
  }

  void _hydrate(Map<String, dynamic> data) {
    if (_hydrated) return;
    final biodata = Map<String, dynamic>.from(data['biodata'] as Map? ?? {});
    final contact = Map<String, dynamic>.from(biodata['contact'] as Map? ?? {});
    final nok = Map<String, dynamic>.from(biodata['next_of_kin'] as Map? ?? {});
    final emergency =
        Map<String, dynamic>.from(biodata['emergency'] as Map? ?? {});
    _phone.text = contact['phone_number']?.toString() ?? '';
    _email.text = contact['email']?.toString() ?? '';
    _county.text = contact['home_county']?.toString() ?? '';
    _address.text = contact['postal_address']?.toString() ?? '';
    _nokName.text = nok['name']?.toString() ?? '';
    _nokRel.text = nok['relationship']?.toString() ?? '';
    _nokPhone.text = nok['phone']?.toString() ?? '';
    _emergencyName.text = emergency['name']?.toString() ?? '';
    _emergencyPhone.text = emergency['phone']?.toString() ?? '';
    _emergencyRel.text = emergency['relationship']?.toString() ?? '';
    _hydrated = true;
  }

  Future<void> _save() async {
    setState(() => _busy = true);
    try {
      final res = await widget.api.updateProfile({
        'phone_number': _phone.text.trim(),
        'email': _email.text.trim(),
        'home_county': _county.text.trim(),
        'postal_address': _address.text.trim(),
        'next_of_kin_name': _nokName.text.trim(),
        'next_of_kin_relationship': _nokRel.text.trim(),
        'next_of_kin_phone': _nokPhone.text.trim(),
        'emergency_contact_name': _emergencyName.text.trim(),
        'emergency_contact_phone': _emergencyPhone.text.trim(),
        'emergency_contact_relationship': _emergencyRel.text.trim(),
      });
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message']?.toString() ?? 'Saved')),
      );
      setState(() {
        _hydrated = false;
        _future = widget.api.profile();
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.profile(),
      apply: (f) => _future = f,
    );
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      enabled: !_busy,
      child: AdaptiveScaffold(
      title: 'My profile',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return studentLoadingBody();
          }
          if (snap.hasError) {
            return studentErrorBody(snap.error, () {
              setState(() {
                _hydrated = false;
                _future = widget.api.profile();
              });
            });
          }
          _hydrate(snap.data!);
          final student =
              Map<String, dynamic>.from(snap.data!['student'] as Map? ?? {});
          final changes = snap.data!['change_requests'] as List? ?? const [];

          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              TichCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      student['full_name']?.toString() ?? 'Student',
                      style: tichUi(fontSize: 18, fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      [
                        student['registration_number'],
                        student['program'],
                        student['campus'],
                      ].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
                      style: tichUi(color: TichColors.neutralMuted, fontSize: 13),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              const TichSectionHeader('Contact'),
              TextField(
                controller: _phone,
                decoration: const InputDecoration(labelText: 'Phone'),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _email,
                decoration: const InputDecoration(labelText: 'Email'),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _county,
                decoration: const InputDecoration(labelText: 'Home county'),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _address,
                decoration: const InputDecoration(labelText: 'Postal address'),
              ),
              const SizedBox(height: 16),
              const TichSectionHeader('Next of kin'),
              TextField(
                controller: _nokName,
                decoration: const InputDecoration(labelText: 'Full name'),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _nokRel,
                decoration: const InputDecoration(labelText: 'Relationship'),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _nokPhone,
                decoration: const InputDecoration(labelText: 'Phone'),
              ),
              const SizedBox(height: 16),
              const TichSectionHeader('Emergency contact'),
              TextField(
                controller: _emergencyName,
                decoration: const InputDecoration(labelText: 'Full name'),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _emergencyRel,
                decoration: const InputDecoration(labelText: 'Relationship'),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _emergencyPhone,
                decoration: const InputDecoration(labelText: 'Phone'),
              ),
              const SizedBox(height: 20),
              AdaptiveFilledButton(
                onPressed: _busy ? null : _save,
                child: _busy
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: AdaptiveProgress(color: Colors.white),
                      )
                    : const Text('Save profile updates'),
              ),
              if (changes.isNotEmpty) ...[
                const SizedBox(height: 20),
                const TichSectionHeader('Change requests'),
                ...changes.map((raw) {
                  final r = Map<String, dynamic>.from(raw as Map);
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: TichCard(
                      child: ListTile(
                        contentPadding: EdgeInsets.zero,
                        title: Text(
                          'Request #${r['id']}',
                          style: tichUi(fontWeight: FontWeight.w600),
                        ),
                        subtitle: Text(formatDateTime(r['created_at'])),
                        trailing: TichStatusChip(
                          r['status']?.toString() ?? 'pending',
                          tone: toneForStatus(r['status']?.toString()),
                        ),
                      ),
                    ),
                  );
                }),
              ],
              const SizedBox(height: 24),
              OutlinedButton.icon(
                onPressed: () =>
                    context.read<AuthBloc>().add(const AuthLogoutRequested()),
                icon: const Icon(Icons.logout),
                label: const Text('Sign out'),
              ),
            ],
          );
        },
      ),
      ),
    );
  }
}
