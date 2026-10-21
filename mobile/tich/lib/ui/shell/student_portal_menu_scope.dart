import 'package:flutter/material.dart';

/// Student photo for the More drawer / list (from dashboard).
class StudentPortalMenuScope extends InheritedWidget {
  const StudentPortalMenuScope({
    super.key,
    required this.profilePhotoUrl,
    required super.child,
  });

  final String? profilePhotoUrl;

  static StudentPortalMenuScope? maybeOf(BuildContext context) {
    return context.dependOnInheritedWidgetOfExactType<StudentPortalMenuScope>();
  }

  static String? photoUrlOf(BuildContext context) {
    return maybeOf(context)?.profilePhotoUrl;
  }

  @override
  bool updateShouldNotify(StudentPortalMenuScope oldWidget) {
    return oldWidget.profilePhotoUrl != profilePhotoUrl;
  }
}
