import 'dart:async';

import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter/painting.dart';

import 'adaptive/adaptive.dart';
import 'bloc/auth/auth_bloc.dart';
import 'bloc/auth/auth_event.dart';
import 'bloc/auth/auth_state.dart';
import 'data/api_client.dart';
import 'data/auth_repository.dart';
import 'data/auth_token_store.dart';
import 'data/tich_api_scope.dart';
import 'data/tich_portal_warmup.dart';
import 'data/tich_realtime.dart';
import 'models/auth_user.dart';
import 'notifications/tich_notification_router.dart';
import 'notifications/tich_notifications.dart';
import 'settings/app_settings.dart';
import 'theme/tich_theme.dart';
import 'ui/auth/unlock_page.dart';
import 'ui/login_page.dart';
import 'ui/portal/portal_chooser_page.dart';
import 'ui/shell/portal_shell.dart';
import 'ui/shell/procurement_portal_shell.dart';
import 'ui/shell/staff_portal_shell.dart';
import 'navigation/tich_root_nav.dart';
import 'ui/shell/student_portal_shell.dart';
import 'ui/shell/tich_logout.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  PaintingBinding.instance.imageCache.maximumSize = 80;
  PaintingBinding.instance.imageCache.maximumSizeBytes = 48 << 20;
  await AppSettings.instance.load();
  TichTheme.warmCache();
  runApp(const TichApp());
  // Defer heavy notification setup until after first frame (faster cold start).
  WidgetsBinding.instance.addPostFrameCallback((_) {
    Future<void>.delayed(const Duration(seconds: 5), () {
      unawaited(TichNotifications.instance.init());
    });
  });
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
    TichNotificationRouter.instance.onPendingEnqueued = _tryApplyNotificationRoute;
  }

  void _tryApplyNotificationRoute() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final ctx = TichRootNav.navigatorKey.currentContext;
      if (ctx == null) return;
      final auth = _authBloc.state;
      if (auth is AuthAuthenticated) {
        TichNotificationRouter.instance.applyPending(ctx);
      }
    });
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
    if (state == AppLifecycleState.resumed) {
      final auth = _authBloc.state;
      if (auth is AuthAuthenticated &&
          !auth.user.isStudent &&
          TichNotifications.instance.isReady) {
        unawaited(TichNotifications.instance.refreshClockInRemindersFromServer());
        unawaited(
          TichNotifications.instance.syncFromServerInBackground(isStudent: false),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return MultiRepositoryProvider(
      providers: [
        RepositoryProvider.value(value: _authRepository),
        RepositoryProvider.value(value: _apiClient),
      ],
      child: BlocProvider.value(
        value: _authBloc,
        child: ListenableBuilder(
          listenable: _settings,
          builder: (context, _) {
            return BlocBuilder<AuthBloc, AuthState>(
              buildWhen: (prev, next) {
                if (prev.runtimeType != next.runtimeType) return true;
                if (next is AuthAuthenticated && prev is AuthAuthenticated) {
                  return next.user != prev.user;
                }
                if (next is AuthLocked && prev is AuthLocked) {
                  return next.user != prev.user;
                }
                return false;
              },
              builder: (context, authState) {
                final app = AdaptiveApp(
                  title: 'TICH in Africa',
                  navigatorKey: TichRootNav.navigatorKey,
                  materialTheme: _lightTheme,
                  materialDarkTheme: _darkTheme,
                  themeMode: _settings.themeMode,
                  textScaleFactor: _settings.textScaleFactor,
                  cupertinoTheme: _cupertinoTheme,
                  home: const _AuthGate(),
                );
                final AuthUser? scopedUser = switch (authState) {
                  AuthAuthenticated(:final user) => user,
                  AuthLocked(:final user) => user,
                  _ => null,
                };
                if (scopedUser != null) {
                  return TichApiScope(
                    key: tichApiScopeKey(scopedUser),
                    user: scopedUser,
                    child: app,
                  );
                }
                return app;
              },
            );
          },
        ),
      ),
    );
  }
}

class _PortalForUser extends StatelessWidget {
  const _PortalForUser({required this.user});

  final AuthUser user;

  @override
  Widget build(BuildContext context) {
    if (user.isStudent) {
      return const StudentPortalShell();
    }
    if (user.hasMultiplePortals) {
      return PortalChooserPage(user: user);
    }
    if (user.canAccessProcurementPortal &&
        !user.canAccessEmployeePortal &&
        !user.canAccessStaffPortal) {
      return const ProcurementPortalShell();
    }
    if (user.canAccessStaffPortal && !user.canAccessEmployeePortal) {
      return const StaffPortalShell();
    }
    if (user.canAccessEmployeePortal) {
      return const PortalShell();
    }
    return const _NoPortalAccessPage();
  }
}

/// Signed-in user with no mobile portal mapping (misconfigured account).
class _NoPortalAccessPage extends StatelessWidget {
  const _NoPortalAccessPage();

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'TICH',
      onLogout: tichLogout(context),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Text(
            'This account is not set up for a mobile portal yet. '
            'Try the web app or contact ICT if you should have access.',
            textAlign: TextAlign.center,
            style: tichUi(color: TichColors.neutralMuted),
          ),
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
    Future<void>.delayed(const Duration(seconds: 4), () {
      unawaited(
        TichNotifications.instance.onAuthenticated(
          isStudent: state.user.isStudent,
        ),
      );
    });
  }

  @override
  Widget build(BuildContext context) {
    return BlocConsumer<AuthBloc, AuthState>(
      listenWhen: (prev, next) =>
          next is AuthAuthenticated ||
          next is AuthUnauthenticated ||
          next is AuthLocked,
      listener: (context, state) {
        if (state is AuthAuthenticated) {
          _primeIfNeeded(state);
          final client = context.read<ApiClient>();
          TichPortalWarmup.prefetchHome(state.user, client);
          Future<void>.delayed(const Duration(seconds: 3), () {
            if (!context.mounted) return;
            unawaited(TichRealtime.connect(client));
          });
          WidgetsBinding.instance.addPostFrameCallback((_) {
            if (!context.mounted) return;
            TichNotificationRouter.instance.applyPending(context);
          });
        } else {
          unawaited(TichRealtime.disconnect());
        }
      },
      buildWhen: (prev, next) => prev.runtimeType != next.runtimeType ||
          (next is AuthAuthenticated && prev is AuthAuthenticated &&
              next.user.id != prev.user.id) ||
          next is AuthLocked,
      builder: (context, state) {
        late final Widget child;
        late final Key childKey;

        if (state is AuthAuthenticated) {
          childKey = ValueKey('portal-${state.user.id}');
          child = _PortalForUser(user: state.user);
        } else if (state is AuthLocked) {
          _primedUserKey = null;
          childKey = ValueKey('unlock-${state.user.id}');
          child = UnlockPage(user: state.user, message: state.message);
        } else if (state is AuthInitial || state is AuthChecking) {
          childKey = const ValueKey('auth-checking');
          child = const Scaffold(
            body: Center(child: AdaptiveProgress()),
          );
        } else {
          _primedUserKey = null;
          childKey = const ValueKey('login');
          child = const LoginPage();
        }

        return AnimatedSwitcher(
          duration: const Duration(milliseconds: 220),
          switchInCurve: Curves.easeOutCubic,
          switchOutCurve: Curves.easeInCubic,
          transitionBuilder: (widget, animation) {
            return FadeTransition(opacity: animation, child: widget);
          },
          child: KeyedSubtree(key: childKey, child: child),
        );
      },
    );
  }
}
