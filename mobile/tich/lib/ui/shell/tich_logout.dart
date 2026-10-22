import 'package:flutter/widgets.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../bloc/auth/auth_bloc.dart';
import '../../bloc/auth/auth_event.dart';
import '../../navigation/tich_root_nav.dart';

/// Resets navigation and requests sign-out (portal chrome, profile, unlock).
void tichPerformLogout(BuildContext context) {
  TichRootNav.popToRoot();
  context.read<AuthBloc>().add(const AuthLogoutRequested());
}

/// Sign-out callback for portal chrome (header button, More side bar).
///
/// The bloc is read eagerly so the closure stays safe after the widget that
/// built it is gone.
VoidCallback tichLogout(BuildContext context) {
  final auth = context.read<AuthBloc>();
  return () {
    TichRootNav.popToRoot();
    auth.add(const AuthLogoutRequested());
  };
}
