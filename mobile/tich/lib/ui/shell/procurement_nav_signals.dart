import 'package:flutter/foundation.dart';

/// Fired after procurement actions so bottom-nav badges refresh immediately.
abstract final class ProcurementPortalNavSignals {
  static VoidCallback? onCountsChanged;
}
