import 'package:flutter/material.dart';
import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/employee_api.dart';
import '../../data/tich_screen_cache.dart';
import '../../theme/tich_theme.dart';
import '../shell/tich_logout.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_profile_identity_card.dart';
import '../widgets/tich_widgets.dart';

class ProfilePage extends StatefulWidget {
  const ProfilePage({super.key, required this.api});

  final EmployeeApi api;

  @override
  State<ProfilePage> createState() => _ProfilePageState();
}

class _ProfilePageState extends State<ProfilePage> {
  static const _cacheKey = 'portal.profile';

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
  bool _editing = false;

  @override
  void initState() {
    super.initState();
    _dataCache = TichScreenCache.read<Map<String, dynamic>>(_cacheKey);
    _future = TichScreenCache.ensure(_cacheKey, () => widget.api.profile());
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

  void _fillControllers(Map<String, dynamic> p) {
    _phone.text = p['phone_number']?.toString() ?? '';
    _address.text = p['physical_address']?.toString() ?? '';
    _emergencyName.text = p['emergency_contact_name']?.toString() ?? '';
    _emergencyPhone.text = p['emergency_contact_phone']?.toString() ?? '';
    _emergencyRel.text = p['emergency_contact_relationship']?.toString() ?? '';
    final nok = Map<String, dynamic>.from(p['next_of_kin'] as Map? ?? {});
    _nokName.text = nok['full_name']?.toString() ?? '';
    _nokRel.text = nok['relationship']?.toString() ?? '';
    _nokPhone.text = nok['phone_number']?.toString() ?? '';
  }

  void _startEdit(Map<String, dynamic> p) {
    _fillControllers(p);
    setState(() => _editing = true);
  }

  void _cancelEdit() => setState(() => _editing = false);

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
      await _reload();
      if (mounted) setState(() => _editing = false);
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _reload() => tichRefreshCached<Map<String, dynamic>>(
        mounted: () => mounted,
        setState: setState,
        fetch: () => widget.api.profile(),
        onData: (data) => _dataCache = data,
        persistKey: _cacheKey,
      );

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
    Map<String, dynamic> data,
    Map<String, dynamic> p,
    Map? prompt,
    List<dynamic> missing,
  ) {
    final nok = Map<String, dynamic>.from(p['next_of_kin'] as Map? ?? {});

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        if (data['must_complete'] == true)
          TichCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Please complete: ${missing.join(', ')}',
                  style: tichUi(color: TichColors.blueDark),
                ),
                const SizedBox(height: 10),
                FilledButton(
                  onPressed: _busy ? null : () => _startEdit(p),
                  child: const Text('Complete profile'),
                ),
              ],
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
        TichProfileIdentityCard(
          name: p['full_name']?.toString() ?? '',
          photoUrl: p['photo_url']?.toString(),
          lines: [
            '${p['employee_number'] ?? ''} · ${p['job_title'] ?? ''}'.trim(),
            p['department']?.toString() ?? '',
            p['primary_email']?.toString() ?? '',
          ],
        ),
        const SizedBox(height: 16),
        if (data['must_complete'] != true)
          OutlinedButton.icon(
            onPressed: _busy ? null : () => _startEdit(p),
            icon: const Icon(Icons.edit_outlined),
            label: const Text('Edit profile'),
          ),
        if (data['must_complete'] != true) const SizedBox(height: 16),
        _readSection('Contact', [
          TichReadOnlyRow(label: 'Phone', value: p['phone_number']),
          TichReadOnlyRow(
            label: 'Physical address',
            value: p['physical_address'],
          ),
        ]),
        _readSection('Emergency contact', [
          TichReadOnlyRow(
            label: 'Name',
            value: p['emergency_contact_name'],
          ),
          TichReadOnlyRow(
            label: 'Phone',
            value: p['emergency_contact_phone'],
          ),
          TichReadOnlyRow(
            label: 'Relationship',
            value: p['emergency_contact_relationship'],
          ),
        ]),
        _readSection('Next of kin', [
          TichReadOnlyRow(label: 'Full name', value: nok['full_name']),
          TichReadOnlyRow(label: 'Relationship', value: nok['relationship']),
          TichReadOnlyRow(label: 'Phone', value: nok['phone_number']),
        ]),
        OutlinedButton.icon(
          onPressed: () => tichPerformLogout(context),
          icon: const Icon(Icons.logout),
          label: const Text('Sign out'),
        ),
        const SizedBox(height: 24),
      ],
    );
  }

  Widget _buildEditForm(Map<String, dynamic> p) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        TichProfileIdentityCard(
          name: p['full_name']?.toString() ?? '',
          photoUrl: p['photo_url']?.toString(),
          lines: [
            '${p['employee_number'] ?? ''} · ${p['job_title'] ?? ''}'.trim(),
          ],
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
          decoration: const InputDecoration(labelText: 'Physical address'),
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
              return tichInstantWaitBody(onRefresh: () async => _reload());
            }
            if (merged == null && snap.hasError) {
              return TichErrorRefresh(
                message: snap.error.toString(),
                onRetry: _reload,
              );
            }
            final data = merged! as Map<String, dynamic>;
            final p = Map<String, dynamic>.from(data['profile'] as Map? ?? {});
            final prompt = data['profile_update_prompt'] as Map?;
            final missing = data['missing_fields'] as List? ?? const [];

            if (_editing) return _buildEditForm(p);
            return _buildReadView(data, p, prompt, missing);
          },
        ),
      ),
    );
  }
}
