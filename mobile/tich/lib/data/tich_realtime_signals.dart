import 'package:flutter/foundation.dart';

/// Server push over WebSockets (Laravel Reverb) — nav badges and cache hints.
abstract final class TichRealtimeSignals {
  static VoidCallback? onSidebarCountsUpdated;
  static VoidCallback? onDataStale;

  static void notifySidebarCountsUpdated() {
    onSidebarCountsUpdated?.call();
  }

  static void notifyDataStale() {
    onDataStale?.call();
  }
}
