import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../adaptive/adaptive.dart';
import '../../data/auth_api.dart';
import '../../data/auth_repository.dart';
import '../../settings/app_settings.dart';
import '../../settings/biometric_service.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_widgets.dart';

class SecuritySettingsPage extends StatefulWidget {
  const SecuritySettingsPage({super.key});

  @override
  State<SecuritySettingsPage> createState() => _SecuritySettingsPageState();
}

class _SecuritySettingsPageState extends State<SecuritySettingsPage> {
  final _settings = AppSettings.instance;
  final _biometrics = BiometricService();
  bool _checkingBiometric = true;
  bool _canBiometric = false;
  String _biometricLabel = 'Fingerprint / Face unlock';
  bool _togglingBiometric = false;

  final _current = TextEditingController();
  final _next = TextEditingController();
  final _confirm = TextEditingController();
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _loadBiometrics();
  }

  Future<void> _loadBiometrics() async {
    setState(() => _checkingBiometric = true);
    final can = await _biometrics.isSupported;
    final label = await _biometrics.label;
    if (!mounted) return;
    setState(() {
      _canBiometric = can;
      _biometricLabel = label;
      _checkingBiometric = false;
    });
  }

  Future<void> _onBiometricChanged(bool enable) async {
    if (_togglingBiometric) return;
    setState(() => _togglingBiometric = true);
    try {
      if (enable) {
        final error = await _biometrics.authenticate(
          reason: 'Enable biometric unlock for TICH',
        );
        if (!mounted) return;
        if (error != null) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(error)),
          );
          return;
        }
      }
      await _settings.setBiometricEnabled(enable);
    } finally {
      if (mounted) setState(() => _togglingBiometric = false);
    }
  }

  @override
  void dispose() {
    _current.dispose();
    _next.dispose();
    _confirm.dispose();
    super.dispose();
  }

  Future<void> _changePassword() async {
    if (_next.text != _confirm.text) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('New passwords do not match.')),
      );
      return;
    }
    setState(() => _busy = true);
    try {
      await context.read<AuthRepository>().changePassword(
            currentPassword: _current.text,
            password: _next.text,
            passwordConfirmation: _confirm.text,
          );
      if (!mounted) return;
      _current.clear();
      _next.clear();
      _confirm.clear();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Password updated.')),
      );
    } on AuthApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Log in and security',
      body: ListenableBuilder(
        listenable: _settings,
        builder: (context, _) {
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              const TichSectionHeader('Session'),
              TichCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'By default you stay signed in for ${AppSettings.sessionDays} days. After that, a password sign-in is required.',
                      style: tichUi(fontSize: 13, color: TichColors.neutralMuted),
                    ),
                    if (_settings.signedInAt != null) ...[
                      const SizedBox(height: 8),
                      Text(
                        'Signed in: ${_settings.signedInAt}',
                        style: tichUi(fontSize: 12),
                      ),
                    ],
                  ],
                ),
              ),
              const SizedBox(height: 16),
              const TichSectionHeader('App lock'),
              TichCard(
                child: Column(
                  children: [
                    SwitchListTile(
                      contentPadding: EdgeInsets.zero,
                      title: Text(_biometricLabel),
                      subtitle: const Text(
                        'Use fingerprint or face to unlock the app.',
                      ),
                      value: _settings.biometricEnabled,
                      onChanged: (_checkingBiometric ||
                              !_canBiometric ||
                              _togglingBiometric)
                          ? null
                          : _onBiometricChanged,
                    ),
                    if (_checkingBiometric)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: Text(
                          'Checking biometric support…',
                          style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
                        ),
                      )
                    else if (!_canBiometric)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: Text(
                          'Set a screen lock (PIN, pattern, fingerprint, or face) in your phone settings, then reopen this screen.',
                          style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
                        ),
                      ),
                    SwitchListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Lock immediately after leaving the app'),
                      subtitle: const Text(
                        'Require unlock whenever you leave TICH.',
                      ),
                      value: _settings.lockOnExit,
                      onChanged: (v) => _settings.setLockOnExit(v),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              const TichSectionHeader('Change password'),
              TichCard(
                child: Column(
                  children: [
                    TextField(
                      controller: _current,
                      obscureText: true,
                      decoration: const InputDecoration(labelText: 'Current password'),
                    ),
                    const SizedBox(height: 10),
                    TextField(
                      controller: _next,
                      obscureText: true,
                      decoration: const InputDecoration(labelText: 'New password'),
                    ),
                    const SizedBox(height: 10),
                    TextField(
                      controller: _confirm,
                      obscureText: true,
                      decoration: const InputDecoration(labelText: 'Confirm new password'),
                    ),
                    const SizedBox(height: 14),
                    AdaptiveFilledButton(
                      onPressed: _busy ? null : _changePassword,
                      child: _busy
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: AdaptiveProgress(color: Colors.white),
                            )
                          : const Text('Update password'),
                    ),
                  ],
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}
