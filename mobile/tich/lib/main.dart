import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'adaptive/adaptive.dart';
import 'bloc/auth/auth_bloc.dart';
import 'bloc/auth/auth_event.dart';
import 'bloc/auth/auth_state.dart';
import 'data/api_client.dart';
import 'data/auth_repository.dart';
import 'data/employee_api.dart';
import 'data/student_api.dart';
import 'settings/app_settings.dart';
import 'theme/tich_theme.dart';
import 'ui/auth/unlock_page.dart';
import 'ui/login_page.dart';
import 'ui/shell/portal_shell.dart';
import 'ui/shell/student_portal_shell.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await AppSettings.instance.load();
  runApp(const TichApp());
}

class TichApp extends StatefulWidget {
  const TichApp({super.key});

  @override
  State<TichApp> createState() => _TichAppState();
}

class _TichAppState extends State<TichApp> with WidgetsBindingObserver {
  late final AuthBloc _authBloc;
  late final AuthRepository _authRepository;
  late final ApiClient _apiClient;
  final _settings = AppSettings.instance;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _apiClient = ApiClient();
    _authRepository = AuthRepository();
    _authBloc = AuthBloc(repository: _authRepository)..add(const AuthStarted());
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _authBloc.close();
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    // Lock only when the app truly goes to background (not mid-dialog).
    if (state == AppLifecycleState.paused) {
      _authBloc.add(const AuthAppPaused());
    }
  }

  @override
  Widget build(BuildContext context) {
    return MultiRepositoryProvider(
      providers: [
        RepositoryProvider.value(value: _authRepository),
        RepositoryProvider.value(value: _apiClient),
        RepositoryProvider(create: (_) => EmployeeApi(_apiClient)),
        RepositoryProvider(create: (_) => StudentApi(_apiClient)),
      ],
      child: BlocProvider.value(
        value: _authBloc,
        child: ListenableBuilder(
          listenable: _settings,
          builder: (context, _) {
            return AdaptiveApp(
              title: 'TICH',
              materialTheme: TichTheme.light(),
              materialDarkTheme: TichTheme.dark(),
              themeMode: _settings.themeMode,
              textScaleFactor: _settings.textScaleFactor,
              cupertinoTheme: TichTheme.cupertino(),
              home: const _AuthGate(),
            );
          },
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
          if (state.user.isStudent) {
            return const StudentPortalShell();
          }
          return const PortalShell();
        }
        if (state is AuthLocked) {
          return UnlockPage(user: state.user, message: state.message);
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
