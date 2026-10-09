import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../bloc/auth/auth_bloc.dart';
import '../bloc/auth/auth_event.dart';
import '../models/auth_user.dart';

class HomePage extends StatelessWidget {
  const HomePage({super.key, required this.user});

  final AuthUser user;

  String get _portalLabel {
    if (user.isStudent) {
      return 'Student portal';
    }
    if (user.isStaffLike) {
      return 'Employee portal';
    }
    return 'TICH portal';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(_portalLabel),
        actions: [
          IconButton(
            tooltip: 'Sign out',
            onPressed: () =>
                context.read<AuthBloc>().add(const AuthLogoutRequested()),
            icon: const Icon(Icons.logout),
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(24),
        children: [
          Text(
            'Welcome, ${user.name}',
            style: Theme.of(context).textTheme.headlineSmall,
          ),
          const SizedBox(height: 8),
          Text(user.email),
          const SizedBox(height: 16),
          Card(
            child: ListTile(
              leading: const Icon(Icons.badge_outlined),
              title: const Text('Account type'),
              subtitle: Text(user.userType.isEmpty ? '—' : user.userType),
            ),
          ),
          Card(
            child: ListTile(
              leading: const Icon(Icons.verified_user_outlined),
              title: const Text('Roles'),
              subtitle: Text(
                user.roles.isEmpty ? 'None loaded' : user.roles.join(', '),
              ),
            ),
          ),
          const SizedBox(height: 12),
          Text(
            user.isStudent
                ? 'You are signed in as a student. Module screens will be added next.'
                : user.isStaffLike
                    ? 'You are signed in as staff. Leave, notifications, and dashboard come next.'
                    : 'Signed in. Role-specific screens will be added next.',
            style: Theme.of(context).textTheme.bodyMedium,
          ),
          const SizedBox(height: 24),
          const Text(
            'Connection check passed: login token stored, /api/auth/me loaded.',
          ),
        ],
      ),
    );
  }
}
