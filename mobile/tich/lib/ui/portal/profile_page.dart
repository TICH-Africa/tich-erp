import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../adaptive/adaptive.dart';
import '../../bloc/auth/auth_bloc.dart';
import '../../bloc/auth/auth_event.dart';
import '../../data/api_client.dart';
import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/employee_avatar.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_widgets.dart';

class ProfilePage extends StatefulWidget {
  const ProfilePage({super.key, required this.api});

  final EmployeeApi api;

  @override
  State<ProfilePage> createState() => _ProfilePageState();
}

class _ProfilePageState extends State<ProfilePage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;
  final _phone = TextEditingController();
  final _address = TextEditingController();
  final _emergencyName = TextEditingController();
  final _emergencyPhone = TextEditingController();
  final _emergencyRel = TextEditingController();
  final _nokName = TextEditingController();
  final _nokRel = TextEditingController();
  final _nokPhone = TextEditingController();
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _future = widget.api.profile().then((data) {
      final p = Map<String, dynamic>.from(data['profile'] as Map? ?? {});
      _phone.text = p['phone_number']?.toString() ?? '';
      _address.text = p['physical_address']?.toString() ?? '';
      _emergencyName.text = p['emergency_contact_name']?.toString() ?? '';
      _emergencyPhone.text = p['emergency_contact_phone']?.toString() ?? '';
      _emergencyRel.text =
          p['emergency_contact_relationship']?.toString() ?? '';
      final nok = Map<String, dynamic>.from(p['next_of_kin'] as Map? ?? {});
      _nokName.text = nok['full_name']?.toString() ?? '';
      _nokRel.text = nok['relationship']?.toString() ?? '';
      _nokPhone.text = nok['phone_number']?.toString() ?? '';
      return data;
    });
  }

  @override
  void dispose() {
    _phone.dispose();
    _address.dispose();
    _emergencyName.dispose();
    _emergencyPhone.dispose();
    _emergencyRel.dispose();
    _nokName.dispose();
    _nokRel.dispose();
    _nokPhone.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    setState(() => _busy = true);
    try {
      final res = await widget.api.updateProfile({
        'phone_number': _phone.text.trim(),
        'physical_address': _address.text.trim(),
        'emergency_contact_name': _emergencyName.text.trim(),
        'emergency_contact_phone': _emergencyPhone.text.trim(),
        'emergency_contact_relationship': _emergencyRel.text.trim(),
        'nok_full_name': _nokName.text.trim(),
        'nok_relationship': _nokRel.text.trim(),
        'nok_phone_number': _nokPhone.text.trim(),
      });
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message']?.toString() ?? 'Saved')),
      );
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.profile(),
      onData: (data) => _dataCache = data,
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
          final merged = tichMergeSnapshot(snap, _dataCache as dynamic);
          if (tichShowLoadingSpinner(snap, merged)) {
            return const Center(child: AdaptiveProgress());
          }
          if (merged == null && snap.hasError) {
            return TichErrorRefresh(
              message: snap.error.toString(),
              onRetry: () => setState(() {
                _future = widget.api.profile();
              }),
            );
          }
          final data = merged! as Map<String, dynamic>;;
          final p = Map<String, dynamic>.from(data['profile'] as Map? ?? {});
          final prompt = data['profile_update_prompt'] as Map?;
          final missing = data['missing_fields'] as List? ?? const [];

          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              if (data['must_complete'] == true)
                TichCard(
                  child: Text(
                    'Please complete: ${missing.join(', ')}',
                    style: tichUi(color: TichColors.blueDark),
                  ),
                ),
              if (prompt != null) ...[
                const SizedBox(height: 8),
                TichCard(
                  child: Text(
                    'Update requested: ${((prompt['labels'] as List?) ?? const []).join(', ')}',
                    style: tichUi(),
                  ),
                ),
              ],
              const SizedBox(height: 12),
              TichCard(
                child: Row(
                  children: [
                    EmployeeAvatar(
                      name: p['full_name']?.toString() ?? '',
                      radius: 30,
                      photoUrl: p['photo_url']?.toString(),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            p['full_name']?.toString() ?? '',
                            style: TichTheme.display(fontSize: 18),
                          ),
                          Text(
                            '${p['employee_number']} · ${p['job_title'] ?? ''}',
                          ),
                          Text(p['department']?.toString() ?? ''),
                          Text(p['primary_email']?.toString() ?? ''),
                        ],
                      ),
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
                controller: _address,
                maxLines: 2,
                decoration: const InputDecoration(
                  labelText: 'Physical address',
                ),
              ),
              const SizedBox(height: 16),
              const TichSectionHeader('Emergency contact'),
              TextField(
                controller: _emergencyName,
                decoration: const InputDecoration(labelText: 'Name'),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _emergencyPhone,
                decoration: const InputDecoration(labelText: 'Phone'),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _emergencyRel,
                decoration: const InputDecoration(labelText: 'Relationship'),
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
