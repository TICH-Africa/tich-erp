import 'package:flutter/foundation.dart';

/// Cross-page hooks for the employee portal shell (badges, compliance gate, etc.).
abstract final class EmployeePortalNavSignals {
  static VoidCallback? onAttendanceChanged;
  static VoidCallback? onStaffPhotoChanged;
  static VoidCallback? onTimeLogComplianceChanged;
}
