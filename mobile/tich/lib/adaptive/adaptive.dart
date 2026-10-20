import 'package:flutter/cupertino.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';

import '../theme/tich_theme.dart';
import '../ui/widgets/tich_form_dialog.dart';
import '../ui/widgets/tich_live_refresh.dart';
import '../ui/widgets/tich_nav_badge.dart';
import '../ui/widgets/tich_tab_active_scope.dart';
import 'lazy_indexed_stack.dart';

/// True when the current OS should use Cupertino chrome (iOS).
bool get usesCupertino =>
    !kIsWeb && defaultTargetPlatform == TargetPlatform.iOS;

/// Root adaptive app: CupertinoApp on iOS, MaterialApp elsewhere.
class AdaptiveApp extends StatelessWidget {
  const AdaptiveApp({
    super.key,
    required this.title,
    required this.home,
    required this.materialTheme,
    this.materialDarkTheme,
    this.themeMode = ThemeMode.system,
    this.textScaleFactor = 1.0,
    this.cupertinoTheme,
    this.debugShowCheckedModeBanner = false,
  });

  final String title;
  final Widget home;
  final ThemeData materialTheme;
  final ThemeData? materialDarkTheme;
  final ThemeMode themeMode;
  final double textScaleFactor;
  final CupertinoThemeData? cupertinoTheme;
  final bool debugShowCheckedModeBanner;

  ThemeData _materialThemeFor(BuildContext context) {
    final platform = MediaQuery.platformBrightnessOf(context);
    final brightness = switch (themeMode) {
      ThemeMode.dark => Brightness.dark,
      ThemeMode.light => Brightness.light,
      ThemeMode.system => platform,
    };
    return brightness == Brightness.dark
        ? (materialDarkTheme ?? materialTheme)
        : materialTheme;
  }

  @override
  Widget build(BuildContext context) {
    final builder = (BuildContext ctx, Widget? child) {
      final media = MediaQuery.of(ctx);
      final material = _materialThemeFor(ctx);
      return MediaQuery(
        data: media.copyWith(textScaler: TextScaler.linear(textScaleFactor)),
        child: Theme(
          data: material,
          child: child ?? const SizedBox.shrink(),
        ),
      );
    };

    if (usesCupertino) {
      return CupertinoApp(
        title: title,
        debugShowCheckedModeBanner: debugShowCheckedModeBanner,
        theme: cupertinoTheme,
        navigatorObservers: [tichRouteObserver],
        builder: builder,
        localizationsDelegates: const [
          DefaultMaterialLocalizations.delegate,
          DefaultCupertinoLocalizations.delegate,
          DefaultWidgetsLocalizations.delegate,
        ],
        home: Material(type: MaterialType.transparency, child: home),
      );
    }

    return MaterialApp(
      title: title,
      debugShowCheckedModeBanner: debugShowCheckedModeBanner,
      theme: materialTheme,
      darkTheme: materialDarkTheme ?? materialTheme,
      themeMode: themeMode,
      navigatorObservers: [tichRouteObserver],
      builder: builder,
      home: home,
    );
  }
}

class AdaptiveProgress extends StatelessWidget {
  const AdaptiveProgress({super.key, this.color});

  final Color? color;

  @override
  Widget build(BuildContext context) {
    if (usesCupertino) {
      return const CupertinoActivityIndicator();
    }
    return CircularProgressIndicator(color: color);
  }
}

/// Keeps a page to the web container width on a tablet or desktop window.
class TichContentWidth extends StatelessWidget {
  const TichContentWidth({
    super.key,
    required this.child,
    this.maxWidth = TichLayout.contentMaxWidth,
  });

  final Widget child;
  final double maxWidth;

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: Alignment.topCenter,
      child: ConstrainedBox(
        constraints: BoxConstraints(maxWidth: maxWidth),
        child: child,
      ),
    );
  }
}

class AdaptiveScaffold extends StatelessWidget {
  const AdaptiveScaffold({
    super.key,
    required this.title,
    required this.body,
    this.actions,
    this.floatingActionButton,
    this.leading,
    this.onLogout,
    this.brandEyebrow = 'TICH in Africa',
  });

  final String title;
  final Widget body;
  final List<Widget>? actions;
  final Widget? floatingActionButton;
  final Widget? leading;
  final VoidCallback? onLogout;
  final String brandEyebrow;

  @override
  Widget build(BuildContext context) {
    // A page can ask for sign out itself; inside a portal the header always offers it.
    final signOut = onLogout ?? TichPortal.onSignOut;
    final logout = signOut == null
        ? null
        : (usesCupertino
              ? CupertinoButton(
                  padding: EdgeInsets.zero,
                  onPressed: signOut,
                  child: const Icon(
                    CupertinoIcons.square_arrow_right,
                    color: TichColors.white,
                  ),
                )
              : IconButton(
                  tooltip: 'Sign out',
                  onPressed: signOut,
                  icon: const Icon(Icons.logout),
                ));

    final trailingActions = <Widget>[...?actions, if (logout != null) logout];

    // An inner page says which portal it belongs to and pops back to it.
    final portalName = TichPortal.name;
    final canPop = ModalRoute.of(context)?.canPop ?? false;
    final backToPortal = canPop && portalName != null
        ? () => Navigator.of(context).pop()
        : null;

    if (usesCupertino) {
      return CupertinoPageScaffold(
        navigationBar: CupertinoNavigationBar(
          backgroundColor: TichColors.blue,
          border: null,
          middle: SizedBox(
            width: MediaQuery.sizeOf(context).width * 0.52,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                _AdaptiveHeaderEyebrow(
                  portalName: backToPortal == null ? null : portalName,
                  brandEyebrow: brandEyebrow,
                  onTap: backToPortal,
                ),
                Text(
                  title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontSize: 16,
                    color: TichColors.white,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ),
          trailing: trailingActions.isEmpty
              ? null
              : Row(mainAxisSize: MainAxisSize.min, children: trailingActions),
          leading:
              leading ??
              (backToPortal == null
                  ? null
                  : CupertinoButton(
                      padding: EdgeInsets.zero,
                      onPressed: backToPortal,
                      child: const Icon(
                        CupertinoIcons.chevron_back,
                        color: TichColors.white,
                        size: 20,
                      ),
                    )),
        ),
        child: SafeArea(
          child: Stack(
            children: [
              Positioned.fill(child: body),
              if (floatingActionButton != null)
                Positioned(right: 16, bottom: 16, child: floatingActionButton!),
            ],
          ),
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(
        toolbarHeight: TichLayout.headerHeight,
        titleSpacing: TichLayout.headerInset,
        actionsPadding: const EdgeInsets.only(right: 6),
        leading: leading,
        // Two lines sharing one left edge, vertically centred against the icons.
        title: Column(
          mainAxisSize: MainAxisSize.min,
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _AdaptiveHeaderEyebrow(
              portalName: backToPortal == null ? null : portalName,
              brandEyebrow: brandEyebrow,
              onTap: backToPortal,
            ),
            const SizedBox(height: 1),
            Text(title, maxLines: 1, overflow: TextOverflow.ellipsis),
          ],
        ),
        actions: trailingActions,
      ),
      body: TichContentWidth(child: body),
      floatingActionButton: floatingActionButton,
    );
  }
}

/// The small line above a page title: the brand, or "‹ Employee portal" when
/// there is a portal to go back to.
class _AdaptiveHeaderEyebrow extends StatelessWidget {
  const _AdaptiveHeaderEyebrow({
    required this.portalName,
    required this.brandEyebrow,
    required this.onTap,
  });

  final String? portalName;
  final String brandEyebrow;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final style = TextStyle(
      fontSize: 11,
      fontWeight: FontWeight.w700,
      letterSpacing: 1.2,
      height: 1.1,
      color: portalName == null
          ? const Color(0xCCFFFFFF)
          : TichColors.greenLight,
    );

    final label = Text(
      portalName ?? brandEyebrow,
      maxLines: 1,
      overflow: TextOverflow.ellipsis,
      textAlign: usesCupertino ? TextAlign.center : TextAlign.start,
      style: style,
    );

    if (onTap == null) return label;

    return Tooltip(
      message: 'Back to $portalName',
      child: InkWell(
        onTap: onTap,
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (!usesCupertino)
              const Icon(
                Icons.chevron_left,
                size: 16,
                color: TichColors.greenLight,
              ),
            label,
          ],
        ),
      ),
    );
  }
}

/// Which portal the open shell belongs to. Pages are pushed onto the root
/// navigator, above the shell, so they read the name from here rather than from
/// an inherited scope and can say "‹ Employee portal" with a way back.
abstract final class TichPortal {
  static String? name;

  /// Set by the open shell so any page header can offer sign out.
  static VoidCallback? onSignOut;

  /// The shell that wrote the two above, so a shell being torn down cannot clear
  /// the values a replacement shell has already published.
  static Object? owner;
}

/// Portal chrome: the five footer tabs stay put at every window size, and the
/// [moreIndex] tab slides open [moreDrawer] instead of replacing them.
class AdaptiveTabScaffold extends StatefulWidget {
  const AdaptiveTabScaffold({
    super.key,
    required this.currentIndex,
    required this.onTap,
    required this.pages,
    required this.items,
    this.portalName,
    this.moreIndex,
    this.moreDrawer,
    this.onSignOut,
  });

  final int currentIndex;
  final ValueChanged<int> onTap;
  final List<Widget> pages;
  final List<AdaptiveTabItem> items;
  final String? portalName;
  final int? moreIndex;
  final WidgetBuilder? moreDrawer;
  final VoidCallback? onSignOut;

  @override
  State<AdaptiveTabScaffold> createState() => _AdaptiveTabScaffoldState();
}

class _AdaptiveTabScaffoldState extends State<AdaptiveTabScaffold> {
  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();

  @override
  void initState() {
    super.initState();
    _publishPortal();
  }

  @override
  void didUpdateWidget(AdaptiveTabScaffold oldWidget) {
    super.didUpdateWidget(oldWidget);
    _publishPortal();
  }

  @override
  void dispose() {
    if (identical(TichPortal.owner, this)) {
      TichPortal.owner = null;
      TichPortal.name = null;
      TichPortal.onSignOut = null;
    }
    super.dispose();
  }

  void _publishPortal() {
    TichPortal.owner = this;
    TichPortal.name = widget.portalName;
    TichPortal.onSignOut = widget.onSignOut;
  }

  void _handleTap(int index) {
    if (widget.moreDrawer != null && index == widget.moreIndex) {
      _scaffoldKey.currentState?.openDrawer();
      return;
    }
    widget.onTap(index);
  }

  @override
  Widget build(BuildContext context) {
    assert(widget.pages.length == widget.items.length);

    if (usesCupertino) {
      return CupertinoTabScaffold(
        tabBar: CupertinoTabBar(
          currentIndex: widget.currentIndex,
          onTap: widget.onTap,
          items: [
            for (final item in widget.items)
              BottomNavigationBarItem(
                icon: item._cupertinoIcon(active: false),
                activeIcon: item._cupertinoIcon(active: true),
                label: item.label,
              ),
          ],
        ),
        tabBuilder: (context, index) => TichTabActiveScope(
          activeIndex: widget.currentIndex,
          tabIndex: index,
          child: widget.pages[index],
        ),
      );
    }

    return Scaffold(
      key: _scaffoldKey,
      drawer: widget.moreDrawer == null
          ? null
          : Builder(builder: widget.moreDrawer!),
      body: LazyIndexedStack(
        index: widget.currentIndex,
        children: widget.pages,
      ),
      bottomNavigationBar: NavigationBarTheme(
        data: NavigationBarTheme.of(context).copyWith(height: 68),
        child: NavigationBar(
          selectedIndex: widget.currentIndex,
          onDestinationSelected: _handleTap,
          labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
          destinations: [
            for (final item in widget.items)
              NavigationDestination(
                icon: item._materialIcon(active: false),
                selectedIcon: item._materialIcon(active: true),
                label: item.label,
              ),
          ],
        ),
      ),
    );
  }
}

class AdaptiveTabItem {
  const AdaptiveTabItem({
    required this.label,
    required this.materialIcon,
    required this.cupertinoIcon,
    this.materialActiveIcon,
    this.cupertinoActiveIcon,
    this.badgeCount = 0,
    this.badgeDotOnly = false,
    this.badgePulse = false,
  });

  final String label;
  final IconData materialIcon;
  final IconData cupertinoIcon;
  final IconData? materialActiveIcon;
  final IconData? cupertinoActiveIcon;
  final int badgeCount;
  final bool badgeDotOnly;
  final bool badgePulse;

  Widget _materialIcon({required bool active}) {
    final icon = Icon(
      active ? (materialActiveIcon ?? materialIcon) : materialIcon,
    );
    return TichNavBadge.wrap(
      child: icon,
      count: badgeCount,
      dotOnly: badgeDotOnly,
      pulse: badgePulse,
    );
  }

  Widget _cupertinoIcon({required bool active}) {
    final icon = Icon(
      active ? (cupertinoActiveIcon ?? cupertinoIcon) : cupertinoIcon,
    );
    return TichNavBadge.wrap(
      child: icon,
      count: badgeCount,
      dotOnly: badgeDotOnly,
      pulse: badgePulse,
    );
  }
}

class AdaptiveFilledButton extends StatelessWidget {
  const AdaptiveFilledButton({
    super.key,
    required this.onPressed,
    required this.child,
    this.backgroundColor,
  });

  final VoidCallback? onPressed;
  final Widget child;
  final Color? backgroundColor;

  @override
  Widget build(BuildContext context) {
    if (usesCupertino) {
      return CupertinoButton.filled(
        onPressed: onPressed,
        color: backgroundColor,
        child: child,
      );
    }
    return FilledButton(
      onPressed: onPressed,
      style: backgroundColor == null
          ? null
          : FilledButton.styleFrom(backgroundColor: backgroundColor),
      child: child,
    );
  }
}

class AdaptiveTextField extends StatelessWidget {
  const AdaptiveTextField({
    super.key,
    required this.controller,
    this.placeholder,
    this.obscureText = false,
    this.enabled = true,
    this.keyboardType,
    this.maxLines = 1,
    this.suffix,
    this.onSubmitted,
  });

  final TextEditingController controller;
  final String? placeholder;
  final bool obscureText;
  final bool enabled;
  final TextInputType? keyboardType;
  final int maxLines;
  final Widget? suffix;
  final ValueChanged<String>? onSubmitted;

  @override
  Widget build(BuildContext context) {
    if (usesCupertino) {
      return CupertinoTextField(
        controller: controller,
        placeholder: placeholder,
        obscureText: obscureText,
        enabled: enabled,
        keyboardType: keyboardType,
        maxLines: maxLines,
        suffix: suffix,
        onSubmitted: onSubmitted,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      );
    }

    return TextField(
      controller: controller,
      obscureText: obscureText,
      enabled: enabled,
      keyboardType: keyboardType,
      maxLines: maxLines,
      onSubmitted: onSubmitted,
      decoration: InputDecoration(labelText: placeholder, suffixIcon: suffix),
    );
  }
}

class AdaptiveSwitchListTile extends StatelessWidget {
  const AdaptiveSwitchListTile({
    super.key,
    required this.title,
    required this.value,
    required this.onChanged,
  });

  final String title;
  final bool value;
  final ValueChanged<bool>? onChanged;

  @override
  Widget build(BuildContext context) {
    if (usesCupertino) {
      return Row(
        children: [
          Expanded(child: Text(title)),
          CupertinoSwitch(value: value, onChanged: onChanged),
        ],
      );
    }
    return SwitchListTile(
      contentPadding: EdgeInsets.zero,
      title: Text(title),
      value: value,
      onChanged: onChanged,
    );
  }
}

Future<T?> showAdaptiveConfirm<T>({
  required BuildContext context,
  required String title,
  required Widget content,
  required String confirmLabel,
  String cancelLabel = 'Cancel',
}) {
  if (usesCupertino) {
    return showCupertinoDialog<T>(
      context: context,
      builder: (ctx) => CupertinoAlertDialog(
        title: Text(title),
        content: content,
        actions: [
          CupertinoDialogAction(
            onPressed: () => Navigator.pop(ctx, false as T?),
            child: Text(cancelLabel),
          ),
          CupertinoDialogAction(
            isDefaultAction: true,
            onPressed: () => Navigator.pop(ctx, true as T?),
            child: Text(confirmLabel),
          ),
        ],
      ),
    );
  }

  return showDialog<T>(
    context: context,
    builder: (ctx) => AlertDialog(
      insetPadding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
      title: Text(title),
      content: SizedBox(width: tichFormDialogWidth(ctx), child: content),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(ctx, false as T?),
          child: Text(cancelLabel),
        ),
        FilledButton(
          onPressed: () => Navigator.pop(ctx, true as T?),
          child: Text(confirmLabel),
        ),
      ],
    ),
  );
}
