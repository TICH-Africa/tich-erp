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
  Object? _dataCache;
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
  bool _editing = false;

  static const _cacheKey = 'student.profile';

  @override
  void initState() {
    super.initState();
    _dataCache = studentReadCache(_cacheKey);
    _future = studentEnsure(_cacheKey, () => widget.api.profile());
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

  void _fillControllers(Map<String, dynamic> data) {
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
  }

  void _startEdit(Map<String, dynamic> payload) {
    _fillControllers(payload);
    setState(() => _editing = true);
  }

  void _cancelEdit() => setState(() => _editing = false);

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
      await studentCachedReload(
        mounted: () => mounted,
        setState: setState,
        fetch: () => widget.api.profile(),
        onData: (data) => _dataCache = data,
        persistKey: _cacheKey,
      );
      if (mounted) setState(() => _editing = false);
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _reloadProfile() async {
    await studentCachedReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.profile(),
      onData: (data) => _dataCache = data,
      persistKey: _cacheKey,
    );
  }

  void _liveRefresh() {
    if (_editing) return;
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.profile(),
      onData: (data) => _dataCache = data,
      persistKey: _cacheKey,
    );
  }

  Widget _readSection(String title, List<Widget> rows) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        TichSectionHeader(title),
        TichCard(child: Column(children: rows)),
        const SizedBox(height: 16),
      ],
    );
  }

  Widget _buildReadView(
    Map<String, dynamic> payload,
    Map<String, dynamic> student,
    List<dynamic> changes,
  ) {
    final biodata = Map<String, dynamic>.from(payload['biodata'] as Map? ?? {});
    final contact = Map<String, dynamic>.from(biodata['contact'] as Map? ?? {});
    final nok = Map<String, dynamic>.from(biodata['next_of_kin'] as Map? ?? {});
    final emergency =
        Map<String, dynamic>.from(biodata['emergency'] as Map? ?? {});

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
        OutlinedButton.icon(
          onPressed: _busy ? null : () => _startEdit(payload),
          icon: const Icon(Icons.edit_outlined),
          label: const Text('Edit profile'),
        ),
        const SizedBox(height: 16),
        _readSection('Contact', [
          TichReadOnlyRow(label: 'Phone', value: contact['phone_number']),
          TichReadOnlyRow(label: 'Email', value: contact['email']),
          TichReadOnlyRow(label: 'Home county', value: contact['home_county']),
          TichReadOnlyRow(label: 'Postal address', value: contact['postal_address']),
        ]),
        _readSection('Next of kin', [
          TichReadOnlyRow(label: 'Full name', value: nok['name']),
          TichReadOnlyRow(label: 'Relationship', value: nok['relationship']),
          TichReadOnlyRow(label: 'Phone', value: nok['phone']),
        ]),
        _readSection('Emergency contact', [
          TichReadOnlyRow(label: 'Full name', value: emergency['name']),
          TichReadOnlyRow(label: 'Relationship', value: emergency['relationship']),
          TichReadOnlyRow(label: 'Phone', value: emergency['phone']),
        ]),
        if (changes.isNotEmpty) ...[
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
          const SizedBox(height: 8),
        ],
        OutlinedButton.icon(
          onPressed: () =>
              context.read<AuthBloc>().add(const AuthLogoutRequested()),
          icon: const Icon(Icons.logout),
          label: const Text('Sign out'),
        ),
        const SizedBox(height: 24),
      ],
    );
  }

  Widget _buildEditForm() {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        const TichSectionHeader('Edit contact'),
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
              : const Text('Save changes'),
        ),
        const SizedBox(height: 10),
        TextButton(
          onPressed: _busy ? null : _cancelEdit,
          child: const Text('Cancel'),
        ),
        const SizedBox(height: 24),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      enabled: !_busy && !_editing,
      child: AdaptiveScaffold(
        title: _editing ? 'Edit profile' : 'My profile',
        leading: _editing
            ? IconButton(
                icon: const Icon(Icons.arrow_back),
                onPressed: _busy ? null : _cancelEdit,
              )
            : null,
        body: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snap) {
            final merged = tichMergeSnapshot(
              snap,
              _dataCache as dynamic,
              persistKey: _cacheKey,
            );
            if (tichShowLoadingSpinner(snap, merged)) {
              return studentLoadingBody(onRefresh: () async => _reloadProfile());
            }
            if (merged == null && snap.hasError) {
              return studentErrorBody(snap.error, _reloadProfile);
            }
            final payload = merged! as Map<String, dynamic>;
            final student =
                Map<String, dynamic>.from(payload['student'] as Map? ?? {});
            final changes = payload['change_requests'] as List? ?? const [];

            if (_editing) return _buildEditForm();
            return _buildReadView(payload, student, changes);
          },
        ),
      ),
    );
  }
}
