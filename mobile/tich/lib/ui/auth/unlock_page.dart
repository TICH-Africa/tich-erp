import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../adaptive/adaptive.dart';
import '../../bloc/auth/auth_bloc.dart';
import '../../bloc/auth/auth_event.dart';
import '../../bloc/auth/auth_state.dart';
import '../../models/auth_user.dart';
import '../../settings/app_settings.dart';
import '../../theme/tich_theme.dart';

class UnlockPage extends StatefulWidget {
  const UnlockPage({super.key, required this.user, this.message});

  final AuthUser user;
  final String? message;

  @override
  State<UnlockPage> createState() => _UnlockPageState();
}

class _UnlockPageState extends State<UnlockPage> {
  final _password = TextEditingController();

  @override
  void dispose() {
    _password.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final settings = AppSettings.instance;
    final loading = context.watch<AuthBloc>().state is AuthLoading;

    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Spacer(),
              Text(
                'TICH',
                textAlign: TextAlign.center,
                style: GoogleFonts.merriweather(
                  fontSize: 36,
                  fontWeight: FontWeight.w700,
                  color: TichColors.green,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Welcome back, ${widget.user.name}',
                textAlign: TextAlign.center,
                style: tichUi(fontSize: 15),
              ),
              const SizedBox(height: 8),
              Text(
                'Unlock to continue',
                textAlign: TextAlign.center,
                style: tichUi(color: TichColors.neutralMuted),
              ),
              if (widget.message != null && widget.message!.isNotEmpty) ...[
                const SizedBox(height: 12),
                Text(
                  widget.message!,
                  textAlign: TextAlign.center,
                  style: TextStyle(color: Theme.of(context).colorScheme.error),
                ),
              ],
              const SizedBox(height: 28),
              if (settings.biometricEnabled) ...[
                AdaptiveFilledButton(
                  onPressed: loading
                      ? null
                      : () => context
                          .read<AuthBloc>()
                          .add(const AuthBiometricUnlockRequested()),
                  child: const Text('Unlock with fingerprint / face'),
                ),
                const SizedBox(height: 16),
                const Text('or use password', textAlign: TextAlign.center),
                const SizedBox(height: 16),
              ],
              TextField(
                controller: _password,
                obscureText: true,
                enabled: !loading,
                decoration: const InputDecoration(labelText: 'Password'),
                onSubmitted: (_) => _unlockWithPassword(),
              ),
              const SizedBox(height: 16),
              AdaptiveFilledButton(
                onPressed: loading ? null : _unlockWithPassword,
                child: loading
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: AdaptiveProgress(color: Colors.white),
                      )
                    : const Text('Unlock'),
              ),
              const Spacer(),
              TextButton(
                onPressed: () =>
                    context.read<AuthBloc>().add(const AuthLogoutRequested()),
                child: const Text('Sign out'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _unlockWithPassword() {
    context.read<AuthBloc>().add(
          AuthPasswordUnlockSubmitted(
            email: widget.user.email,
            password: _password.text,
          ),
        );
  }
}
