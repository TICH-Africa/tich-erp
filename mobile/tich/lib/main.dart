import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'adaptive/adaptive.dart';
import 'bloc/auth/auth_bloc.dart';
import 'bloc/auth/auth_event.dart';
import 'bloc/auth/auth_state.dart';
import 'data/api_client.dart';
import 'data/auth_repository.dart';
import 'data/employee_api.dart';
import 'theme/tich_theme.dart';
import 'ui/login_page.dart';
import 'ui/shell/portal_shell.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const TichApp());
}

class TichApp extends StatelessWidget {
  const TichApp({super.key});

  @override
  Widget build(BuildContext context) {
    final apiClient = ApiClient();

    return MultiRepositoryProvider(
      providers: [
        RepositoryProvider(create: (_) => AuthRepository()),
        RepositoryProvider.value(value: apiClient),
        RepositoryProvider(create: (_) => EmployeeApi(apiClient)),
      ],
      child: BlocProvider(
        create: (context) => AuthBloc(
          repository: context.read<AuthRepository>(),
        )..add(const AuthStarted()),
        child: AdaptiveApp(
          title: 'TICH',
          materialTheme: TichTheme.light(),
          cupertinoTheme: TichTheme.cupertino(),
          home: const _AuthGate(),
        ),
      ),
    );
  }
}

class _AuthGate extends StatelessWidget {
  const _AuthGate();

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<AuthBloc, AuthState>(
      builder: (context, state) {
        if (state is AuthAuthenticated) {
          return const PortalShell();
        }
        if (state is AuthInitial || state is AuthChecking) {
          return const Scaffold(
            body: Center(child: AdaptiveProgress()),
          );
        }
        return const LoginPage();
      },
    );
  }
}
