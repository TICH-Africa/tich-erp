/// Deep links into the employee bottom-nav shell (Clock in tab, etc.).
abstract final class EmployeePortalNav {
  static bool isEmployeeShellActive = false;
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
