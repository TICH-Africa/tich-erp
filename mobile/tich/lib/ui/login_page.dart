import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../adaptive/adaptive.dart';
import '../bloc/auth/auth_bloc.dart';
import '../bloc/auth/auth_event.dart';
import '../bloc/auth/auth_state.dart';
import '../config/api_config.dart';
import '../theme/tich_theme.dart';
import 'widgets/tich_brand_logo.dart';

class LoginPage extends StatefulWidget {
  const LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscure = true;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  void _submit() {
    if (usesCupertino) {
      if (_emailController.text.trim().isEmpty ||
          !_emailController.text.contains('@') ||
          _passwordController.text.isEmpty) {
        return;
      }
    } else if (!_formKey.currentState!.validate()) {
      return;
    }
    context.read<AuthBloc>().add(
          AuthLoginSubmitted(
            email: _emailController.text,
            password: _passwordController.text,
          ),
        );
  }

  @override
  Widget build(BuildContext context) {
    final content = BlocBuilder<AuthBloc, AuthState>(
      builder: (context, state) {
        final loading = state is AuthLoading;
        final error = state is AuthUnauthenticated ? state.message : null;

        final formChildren = <Widget>[
          const TichBrandLogo(),
          const SizedBox(height: 8),
          Text(
            'Sign in with your ERP account',
            textAlign: TextAlign.center,
            style: tichUi(
              fontSize: 14,
              color: Theme.of(context).colorScheme.onSurface,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'API: ${ApiConfig.baseUrl}',
            textAlign: TextAlign.center,
            style: tichUi(fontSize: 11, color: TichColors.neutralMuted),
          ),
          const SizedBox(height: 28),
          if (usesCupertino) ...[
            AdaptiveTextField(
              controller: _emailController,
              placeholder: 'Email',
              enabled: !loading,
              keyboardType: TextInputType.emailAddress,
            ),
            const SizedBox(height: 16),
            AdaptiveTextField(
              controller: _passwordController,
              placeholder: 'Password',
              obscureText: _obscure,
              enabled: !loading,
              onSubmitted: (_) => _submit(),
              suffix: CupertinoButton(
                padding: const EdgeInsets.only(right: 8),
                onPressed: () => setState(() => _obscure = !_obscure),
                child: Icon(
                  _obscure ? CupertinoIcons.eye : CupertinoIcons.eye_slash,
                  size: 20,
                ),
              ),
            ),
          ] else ...[
            TextFormField(
              controller: _emailController,
              keyboardType: TextInputType.emailAddress,
              autofillHints: const [AutofillHints.email],
              enabled: !loading,
              decoration: const InputDecoration(labelText: 'Email'),
              validator: (value) {
                final v = value?.trim() ?? '';
                if (v.isEmpty || !v.contains('@')) {
                  return 'Enter a valid email';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _passwordController,
              obscureText: _obscure,
              enabled: !loading,
              autofillHints: const [AutofillHints.password],
              decoration: InputDecoration(
                labelText: 'Password',
                suffixIcon: IconButton(
                  onPressed: () => setState(() => _obscure = !_obscure),
                  icon: Icon(
                    _obscure ? Icons.visibility : Icons.visibility_off,
                  ),
                ),
              ),
              validator: (value) {
                if ((value ?? '').isEmpty) {
                  return 'Enter your password';
                }
                return null;
              },
              onFieldSubmitted: (_) => _submit(),
            ),
          ],
          if (error != null && error.isNotEmpty) ...[
            const SizedBox(height: 16),
            Text(
              error,
              style: TextStyle(
                color: usesCupertino
                    ? CupertinoColors.destructiveRed
                    : Theme.of(context).colorScheme.error,
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
                    'Sign in',
                    style: tichUi(fontWeight: FontWeight.w700),
                  ),
          ),
        ];

        final form = usesCupertino
            ? Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: formChildren,
              )
            : Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: formChildren,
                ),
              );

        return Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 420),
              child: form,
            ),
          ),
        );
      },
    );

    final body = ColoredBox(
      color: TichColors.neutral,
      child: SafeArea(child: content),
    );

    if (usesCupertino) {
      return CupertinoPageScaffold(child: body);
    }
    return Scaffold(body: body);
  }
}
