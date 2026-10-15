import 'dart:async';

import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'adaptive/adaptive.dart';
import 'bloc/auth/auth_bloc.dart';
import 'bloc/auth/auth_event.dart';
import 'bloc/auth/auth_state.dart';
import 'data/api_client.dart';
import 'data/auth_repository.dart';
import 'data/auth_token_store.dart';
import 'data/employee_api.dart';
import 'data/student_api.dart';
import 'notifications/tich_notifications.dart';
import 'settings/app_settings.dart';
import 'theme/tich_theme.dart';
import 'ui/auth/unlock_page.dart';
import 'ui/login_page.dart';
import 'ui/shell/portal_shell.dart';
import 'ui/shell/student_portal_shell.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await AppSettings.instance.load();
  TichTheme.warmCache();
  runApp(const TichApp());
  // Defer timezone / Workmanager / permission work off the first frame.
  unawaited(TichNotifications.instance.init());
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

  late final ThemeData _lightTheme = TichTheme.light();
  late final ThemeData _darkTheme = TichTheme.dark();
  late final CupertinoThemeData _cupertinoTheme = TichTheme.cupertino();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    final tokenStore = AuthTokenStore();
    _apiClient = ApiClient(tokenStore: tokenStore);
    TichNotifications.instance.attachApiClient(_apiClient);
    _authRepository = AuthRepository(
      tokenStore: tokenStore,
      onTokenChanged: (token) {
        if (token == null) {
          _apiClient.invalidateTokenCache();
        } else {
          _apiClient.cacheToken(token);
        }
      },
    );
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
              title: 'TICH in Africa',
              materialTheme: _lightTheme,
              materialDarkTheme: _darkTheme,
              themeMode: _settings.themeMode,
              textScaleFactor: _settings.textScaleFactor,
              cupertinoTheme: _cupertinoTheme,
              home: const _AuthGate(),
            );
          },
        ),
      ),
    );
  }
}

class _AuthGate extends StatefulWidget {
  const _AuthGate();

  @override
  State<_AuthGate> createState() => _AuthGateState();
}

class _AuthGateState extends State<_AuthGate> {
  String? _primedUserKey;

  void _primeIfNeeded(AuthAuthenticated state) {
    final key = '${state.user.id}:${state.user.isStudent}';
    if (_primedUserKey == key) return;
    _primedUserKey = key;
    unawaited(
      TichNotifications.instance.onAuthenticated(
        isStudent: state.user.isStudent,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return BlocConsumer<AuthBloc, AuthState>(
      listenWhen: (prev, next) => next is AuthAuthenticated,
      listener: (context, state) {
        if (state is AuthAuthenticated) {
          _primeIfNeeded(state);
        }
      },
      buildWhen: (prev, next) => prev.runtimeType != next.runtimeType ||
          (next is AuthAuthenticated && prev is AuthAuthenticated &&
              next.user.id != prev.user.id) ||
          next is AuthLocked,
      builder: (context, state) {
        if (state is AuthAuthenticated) {
          if (state.user.isStudent) {
            return const StudentPortalShell();
          }
          return const PortalShell();
        }
        if (state is AuthLocked) {
          _primedUserKey = null;
          return UnlockPage(user: state.user, message: state.message);
        }
        if (state is AuthInitial || state is AuthChecking) {
          return const Scaffold(
            body: Center(child: AdaptiveProgress()),
          );
        }
        _primedUserKey = null;
        return const LoginPage();
      },
    );
  }
}
