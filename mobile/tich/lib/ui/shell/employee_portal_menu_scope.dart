import 'package:flutter/material.dart';

/// Staff photo for the employee portal More menu (from dashboard / profile).
class EmployeePortalMenuScope extends InheritedWidget {
  const EmployeePortalMenuScope({
    super.key,
    required this.profilePhotoUrl,
    required super.child,
  });

  final String? profilePhotoUrl;

  static EmployeePortalMenuScope? maybeOf(BuildContext context) {
    return context.dependOnInheritedWidgetOfExactType<EmployeePortalMenuScope>();
  }

  static String? photoUrlOf(BuildContext context) {
    return maybeOf(context)?.profilePhotoUrl;
  }

  @override
  bool updateShouldNotify(EmployeePortalMenuScope oldWidget) {
    return oldWidget.profilePhotoUrl != profilePhotoUrl;
  }
}
