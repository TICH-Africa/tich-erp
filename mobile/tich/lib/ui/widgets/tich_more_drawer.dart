import 'package:flutter/material.dart';

import '../../theme/tich_theme.dart';
import 'tich_more_row_trailing.dart';
import 'tich_nav_badge.dart';
import 'tich_brand_logo.dart';
import 'tich_widgets.dart';

/// One link in the More menu, used by both the side bar and the iOS list.
class TichMoreItem {
  const TichMoreItem({
    required this.label,
    required this.icon,
    required this.onTap,
    this.badgeCount = 0,
  });

  final String label;
  final IconData icon;
  final VoidCallback onTap;
  final int badgeCount;
}

/// More slides this in instead of replacing the footer tabs: the web portal
/// sidebar (--sb-* tokens) as a panel, so a phone keeps five tabs and still
/// reaches every secondary section.
class TichMoreDrawer extends StatelessWidget {
  const TichMoreDrawer({
    super.key,
    required this.portalName,
    required this.items,
  });

  final String portalName;
  final List<TichMoreItem> items;

  @override
  Widget build(BuildContext context) {
    return Drawer(
      backgroundColor: TichColors.sidebar,
      surfaceTintColor: TichColors.sidebar,
      width: TichLayout.railWidth,
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
                            color: TichColors.sidebarLink,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          portalName,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TichTheme.display(
                            fontSize: 19,
                            color: TichColors.white,
                          ),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    tooltip: 'Close',
                    onPressed: () => Navigator.of(context).pop(),
                    icon: const Icon(
                      Icons.close,
                      color: TichColors.sidebarLink,
                    ),
                  ),
                ],
              ),
            ),
            const Divider(height: 1, color: Color(0x1FFFFFFF)),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.symmetric(vertical: 8),
                children: [
                  for (final item in items)
                    TichSideNavRow(
                      item: item,
                      onTap: () {
                        Navigator.of(context).pop();
                        item.onTap();
                      },
                    ),
                ],
              ),
            ),
          ],
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
    return InkWell(
      onTap: onTap,
      child: Container(
        color: active ? TichColors.sidebarActiveBg : Colors.transparent,
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
            Icon(
              item.icon,
              size: 20,
              color: active ? TichColors.white : TichColors.sidebarMuted,
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
                  color: active
                      ? TichColors.sidebarActive
                      : TichColors.sidebarLink,
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
          onTap: item.onTap,
          child: ListTile(
            contentPadding: EdgeInsets.zero,
            leading: Icon(item.icon, color: TichColors.greenDark),
            title: Text(
              item.label,
              style: tichUi(fontWeight: FontWeight.w600),
            ),
            trailing: TichMoreRowTrailing(badgeCount: item.badgeCount),
          ),
        );
      },
    );
  }
}
