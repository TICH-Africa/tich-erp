import 'package:flutter/widgets.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../bloc/auth/auth_bloc.dart';
import '../../bloc/auth/auth_event.dart';

/// Sign-out callback for portal chrome (header button, More side bar).
///
/// The bloc is read eagerly so the closure stays safe after the widget that
/// built it is gone.
VoidCallback tichLogout(BuildContext context) {
  final auth = context.read<AuthBloc>();
  return () => auth.add(const AuthLogoutRequested());
}
