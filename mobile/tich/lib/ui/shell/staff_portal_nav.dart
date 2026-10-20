/// Deep links into the staff portal bottom-nav shell.
abstract final class StaffPortalNav {
  static bool isStaffShellActive = false;
  static int? pendingTabIndex;
  static void Function()? onTabRequest;

  static void requestTab(int index) {
    pendingTabIndex = index;
    onTabRequest?.call();
  }

  static int? consumePendingTab() {
    final tab = pendingTabIndex;
    pendingTabIndex = null;
    return tab;
  }
}
