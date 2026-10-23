import 'package:flutter/material.dart';

import '../../theme/tich_theme.dart';
import 'employee_avatar.dart';
import 'tich_more_row_trailing.dart';
import 'tich_nav_badge.dart';
import 'tich_brand_logo.dart';
import 'tich_global_more_footer.dart';
import 'tich_widgets.dart';

/// Close the More drawer, then run [action] (usually [TichRootNav.push]).
void runTichMoreDrawerAction(BuildContext context, VoidCallback action) {
  final navigator = Navigator.of(context);
  if (navigator.canPop()) {
    navigator.pop();
  }
  WidgetsBinding.instance.addPostFrameCallback((_) {
    action();
  });
}

/// One link in the More menu, used by both the side bar and the iOS list.
class TichMoreItem {
  const TichMoreItem({
    required this.label,
    required this.icon,
    required this.onTap,
    this.badgeCount = 0,
    this.photoUrl,
    this.showProfilePhoto = false,
  });

  final String label;
  final IconData icon;
  final VoidCallback onTap;
  final int badgeCount;

  /// When [showProfilePhoto] is true, shows staff photo or [icon] as fallback.
  final String? photoUrl;
  final bool showProfilePhoto;
}

/// Sidebar / list leading: employee photo when available, otherwise the menu icon.
class TichMoreProfileLeading extends StatelessWidget {
  const TichMoreProfileLeading({
    super.key,
    required this.photoUrl,
    required this.fallbackIcon,
    required this.iconColor,
    this.size = 28,
  });

  final String? photoUrl;
  final IconData fallbackIcon;
  final Color iconColor;
  final double size;

  @override
  Widget build(BuildContext context) {
    final uri = employeePhotoUri(photoUrl);
    final icon = Icon(fallbackIcon, size: 20, color: iconColor);

    if (uri == null) {
      return SizedBox(width: size, height: size, child: Center(child: icon));
    }

    final dpr = MediaQuery.devicePixelRatioOf(context);
    final px = (size * dpr).round().clamp(48, 128);

    return SizedBox(
      width: size,
      height: size,
      child: ClipOval(
        child: Image.network(
          uri.toString(),
          width: size,
          height: size,
          fit: BoxFit.cover,
          cacheWidth: px,
          cacheHeight: px,
          filterQuality: FilterQuality.low,
          errorBuilder: (_, __, ___) => Center(child: icon),
          frameBuilder: (context, child, frame, wasSync) {
            if (wasSync || frame != null) return child;
            return Center(child: icon);
          },
        ),
      ),
    );
  }
}

/// More slides this in instead of replacing the footer tabs: the web portal
/// sidebar (--sb-* tokens) as a panel, so a phone keeps five tabs and still
/// reaches every secondary section.
class TichMoreDrawer extends StatelessWidget {
  const TichMoreDrawer({
    super.key,
    required this.portalName,
    required this.items,
    required this.menuContext,
    this.footerItems,
  });

  final String portalName;
  final List<TichMoreItem> items;
  final TichPortalMenuContext menuContext;

  /// When null, uses [tichMoreMenuFooter] for [menuContext].
  final List<TichMoreItem>? footerItems;

  static const _darkNavMuted = Color(0xFF9AA0A6);

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final scheme = Theme.of(context).colorScheme;
    final panelColor = isDark ? TichColors.darkSurface : TichColors.sidebar;

    final drawerWidth = MediaQuery.sizeOf(context).width * 0.8;
    final footer =
        footerItems ?? tichMoreMenuFooter(context, menuContext);

    return Drawer(
      backgroundColor: panelColor,
      surfaceTintColor: Colors.transparent,
      width: drawerWidth,
      child: Material(
        color: panelColor,
        child: SafeArea(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 12, 6, 10),
                child: Row(
                  children: [
                    const TichBrandMark(size: 36),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'TICH in Africa',
                            style: tichUi(
                              fontSize: 10,
                              fontWeight: FontWeight.w700,
                              letterSpacing: 1.2,
                              color: isDark
                                  ? _darkNavMuted
                                  : TichColors.sidebarLink,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            portalName,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TichTheme.display(
                              fontSize: 19,
                              color: isDark ? scheme.onSurface : TichColors.white,
                            ),
                          ),
                        ],
                      ),
                    ),
                    IconButton(
                      tooltip: 'Close',
                      onPressed: () => Navigator.of(context).pop(),
                      icon: Icon(
                        Icons.close,
                        color: isDark ? _darkNavMuted : TichColors.sidebarLink,
                      ),
                    ),
                  ],
                ),
              ),
              Divider(
                height: 1,
                color: isDark ? TichColors.darkBorder : const Color(0x1FFFFFFF),
              ),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.symmetric(vertical: 8),
                children: [
                  for (final item in items)
                    TichSideNavRow(
                      item: item,
                      onTap: () =>
                          runTichMoreDrawerAction(context, item.onTap),
                    ),
                ],
              ),
            ),
            if (footer.isNotEmpty) ...[
              Divider(
                height: 1,
                color: isDark ? TichColors.darkBorder : const Color(0x1FFFFFFF),
              ),
              for (final item in footer)
                TichSideNavRow(
                  item: item,
                  onTap: () =>
                      runTichMoreDrawerAction(context, item.onTap),
                ),
            ],
          ],
        ),
      ),
      ),
    );
  }
}

/// Sidebar link: cream text on deep blue, green edge bar when it is the open section.
class TichSideNavRow extends StatelessWidget {
  const TichSideNavRow({
    super.key,
    required this.item,
    required this.onTap,
    this.active = false,
  });

  final TichMoreItem item;
  final VoidCallback onTap;
  final bool active;

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final iconColor = isDark
        ? (active ? TichColors.blueLight : TichMoreDrawer._darkNavMuted)
        : (active ? TichColors.white : TichColors.sidebarMuted);
    final labelColor = isDark
        ? (active ? TichColors.blueLight : Theme.of(context).colorScheme.onSurface)
        : (active ? TichColors.sidebarActive : TichColors.sidebarLink);
    final activeBg = isDark
        ? const Color(0xFF24384A)
        : TichColors.sidebarActiveBg;

    return InkWell(
      onTap: onTap,
      child: Container(
        color: active ? activeBg : Colors.transparent,
        decoration: active
            ? const BoxDecoration(
                border: Border(
                  left: BorderSide(color: TichColors.green, width: 3),
                ),
              )
            : null,
        padding: const EdgeInsets.symmetric(horizontal: 17, vertical: 13),
        child: Row(
          children: [
            if (item.showProfilePhoto)
              TichMoreProfileLeading(
                photoUrl: item.photoUrl,
                fallbackIcon: item.icon,
                iconColor: iconColor,
              )
            else
              Icon(
                item.icon,
                size: 20,
                color: iconColor,
              ),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                item.label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: tichUi(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: labelColor,
                ),
              ),
            ),
            if (item.badgeCount > 0)
              Padding(
                padding: const EdgeInsets.only(left: 6),
                child: TichNavBadge.circleCount(item.badgeCount, fontSize: 10),
              ),
          ],
        ),
      ),
    );
  }
}

/// The same links as stacked cards, for the Cupertino More page.
class TichMoreList extends StatelessWidget {
  const TichMoreList({super.key, required this.items});

  final List<TichMoreItem> items;

  @override
  Widget build(BuildContext context) {
    return ListView.separated(
      padding: TichLayout.pagePadding,
      itemCount: items.length,
      separatorBuilder: (_, __) => const SizedBox(height: 8),
      itemBuilder: (context, index) {
        final item = items[index];
        return TichCard(
          child: ListTile(
            onTap: item.onTap,
            contentPadding: EdgeInsets.zero,
            leading: item.showProfilePhoto
                ? TichMoreProfileLeading(
                    photoUrl: item.photoUrl,
                    fallbackIcon: item.icon,
                    iconColor: TichColors.greenDark,
                  )
                : Icon(item.icon, color: TichColors.greenDark),
            title: Text(
              item.label,
              style: tichUi(
                fontWeight: FontWeight.w600,
                color: Theme.of(context).colorScheme.onSurface,
              ),
            ),
            trailing: TichMoreRowTrailing(badgeCount: item.badgeCount),
          ),
        );
      },
    );
  }
}
