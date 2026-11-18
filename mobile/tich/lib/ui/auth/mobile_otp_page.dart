import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../adaptive/adaptive.dart';
import '../../bloc/auth/auth_bloc.dart';
import '../../bloc/auth/auth_event.dart';
import '../../bloc/auth/auth_state.dart';
import '../../models/auth_user.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_brand_logo.dart';

class MobileOtpPage extends StatefulWidget {
  const MobileOtpPage({
    super.key,
    required this.user,
    this.emailMasked,
    this.expiresMinutes = 15,
    this.otpSent = true,
    this.message,
    this.resending = false,
  });

  final AuthUser user;
  final String? emailMasked;
  final int expiresMinutes;
  final bool otpSent;
  final String? message;
  final bool resending;

  @override
  State<MobileOtpPage> createState() => _MobileOtpPageState();
}

class _MobileOtpPageState extends State<MobileOtpPage> {
  final _codeController = TextEditingController();

  @override
  void dispose() {
    _codeController.dispose();
    super.dispose();
  }

  void _submit() {
    final code = _codeController.text.trim();
    if (code.length != 6) return;
    context.read<AuthBloc>().add(AuthMobileOtpSubmitted(code));
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final background = Theme.of(context).scaffoldBackgroundColor;
    final recipient = widget.emailMasked ?? widget.user.email;
    final loading = context.watch<AuthBloc>().state is AuthLoading;

    final body = SafeArea(
      child: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 420),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const TichBrandLogo(),
                const SizedBox(height: 8),
                Text(
                  'Verify your email',
                  textAlign: TextAlign.center,
                  style: tichUi(
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 12),
                Text(
                  widget.otpSent
                      ? 'We sent a 6-digit code to $recipient. Enter it below to finish your first sign-in on the mobile app.'
                      : 'Enter the 6-digit code from your email to finish your first sign-in on the mobile app.',
                  textAlign: TextAlign.center,
                  style: tichUi(
                    fontSize: 14,
                    color: scheme.onSurface.withValues(alpha: 0.85),
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'The code expires in ${widget.expiresMinutes} minutes.',
                  textAlign: TextAlign.center,
                  style: tichUi(
                    fontSize: 13,
                    color: TichColors.neutralMuted,
                  ),
                ),
                const SizedBox(height: 28),
                if (usesCupertino)
                  AdaptiveTextField(
                    controller: _codeController,
                    placeholder: '6-digit code',
                    enabled: !loading,
                    keyboardType: TextInputType.number,
                    onSubmitted: (_) => _submit(),
                  )
                else
                  TextFormField(
                    controller: _codeController,
                    enabled: !loading,
                    keyboardType: TextInputType.number,
                    textInputAction: TextInputAction.done,
                    autofillHints: const [AutofillHints.oneTimeCode],
                    maxLength: 6,
                    inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                    decoration: const InputDecoration(
                      labelText: '6-digit code',
                      counterText: '',
                    ),
                    onFieldSubmitted: (_) => _submit(),
                    onChanged: (value) {
                      if (value.length == 6) _submit();
                    },
                  ),
                if (widget.message != null && widget.message!.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  Text(
                    widget.message!,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: usesCupertino
                          ? CupertinoColors.destructiveRed.resolveFrom(context)
                          : scheme.error,
                    ),
                  ),
                ],
                const SizedBox(height: 24),
                AdaptiveFilledButton(
                  onPressed: loading ? null : _submit,
                  child: loading
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: AdaptiveProgress(color: Colors.white),
                        )
                      : Text(
                          'Verify',
                          style: tichUi(fontWeight: FontWeight.w700),
                        ),
                ),
                const SizedBox(height: 12),
                _LinkAction(
                  onPressed: (loading || widget.resending)
                      ? null
                      : () => context
                          .read<AuthBloc>()
                          .add(const AuthMobileOtpResendRequested()),
                  label: widget.resending ? 'Sending…' : 'Resend code',
                  busy: widget.resending,
                ),
                const SizedBox(height: 8),
                _LinkAction(
                  onPressed: loading
                      ? null
                      : () => context
                          .read<AuthBloc>()
                          .add(const AuthLogoutRequested()),
                  label: 'Use a different account',
                ),
              ],
            ),
          ),
        ),
      ),
    );

    if (usesCupertino) {
      return CupertinoPageScaffold(
        backgroundColor: background,
        child: ColoredBox(color: background, child: body),
      );
    }
    return Scaffold(
      backgroundColor: background,
      body: body,
    );
  }
}

class _LinkAction extends StatelessWidget {
  const _LinkAction({
    required this.onPressed,
    required this.label,
    this.busy = false,
  });

  final VoidCallback? onPressed;
  final String label;
  final bool busy;

  @override
  Widget build(BuildContext context) {
  final color = usesCupertino
      ? CupertinoColors.activeBlue.resolveFrom(context)
      : Theme.of(context).colorScheme.primary;

    if (usesCupertino) {
      return CupertinoButton(
        padding: EdgeInsets.zero,
        onPressed: onPressed,
        child: busy
            ? const SizedBox(
                height: 18,
                width: 18,
                child: AdaptiveProgress(),
              )
            : Text(label, style: TextStyle(color: color)),
      );
    }

    return TextButton(
      onPressed: onPressed,
      child: busy
          ? const SizedBox(
              height: 18,
              width: 18,
              child: AdaptiveProgress(),
            )
          : Text(label),
    );
  }
}