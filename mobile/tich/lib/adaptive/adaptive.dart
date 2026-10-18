import 'package:flutter/cupertino.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';

import '../theme/tich_theme.dart';
import '../ui/widgets/tich_live_refresh.dart';
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

  @override
  Widget build(BuildContext context) {
    final builder = (BuildContext ctx, Widget? child) {
      final media = MediaQuery.of(ctx);
      return MediaQuery(
        data: media.copyWith(
          textScaler: TextScaler.linear(textScaleFactor),
        ),
        child: child ?? const SizedBox.shrink(),
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

class AdaptiveScaffold extends StatelessWidget {
  const AdaptiveScaffold({
    super.key,
    required this.title,
    required this.body,
    this.actions,
    this.floatingActionButton,
    this.leading,
    this.showLogout = false,
    this.onLogout,
    this.brandEyebrow = 'TICH in Africa',
  });

  final String title;
  final Widget body;
  final List<Widget>? actions;
  final Widget? floatingActionButton;
  final Widget? leading;
  final bool showLogout;
  final VoidCallback? onLogout;
  final String brandEyebrow;

  @override
  Widget build(BuildContext context) {
    final logout = showLogout && onLogout != null
        ? (usesCupertino
            ? CupertinoButton(
                padding: EdgeInsets.zero,
                onPressed: onLogout,
                child: const Icon(
                  CupertinoIcons.square_arrow_right,
                  color: TichColors.white,
                ),
              )
            : IconButton(
                tooltip: 'Sign out',
                onPressed: onLogout,
                icon: const Icon(Icons.logout),
              ))
        : null;

    final trailingActions = <Widget>[
      ...?actions,
      if (logout != null) logout,
    ];

    if (usesCupertino) {
      return CupertinoPageScaffold(
        navigationBar: CupertinoNavigationBar(
          backgroundColor: TichColors.blue,
          border: null,
          middle: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                brandEyebrow,
                style: const TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.w700,
                  letterSpacing: 1.1,
                  color: TichColors.greenLight,
                ),
              ),
              Text(
                title,
                style: const TextStyle(
                  fontSize: 16,
                  color: TichColors.white,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
          trailing: trailingActions.isEmpty
              ? null
              : Row(
                  mainAxisSize: MainAxisSize.min,
                  children: trailingActions,
                ),
          leading: leading,
        ),
        child: SafeArea(
          child: Stack(
            children: [
              Positioned.fill(child: body),
              if (floatingActionButton != null)
                Positioned(
                  right: 16,
                  bottom: 16,
                  child: floatingActionButton!,
                ),
            ],
          ),
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(
        leading: leading,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              brandEyebrow,
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                letterSpacing: 1.2,
              ),
            ),
            Text(title),
          ],
        ),
        actions: trailingActions,
      ),
      body: body,
      floatingActionButton: floatingActionButton,
    );
  }
}

class AdaptiveTabScaffold extends StatelessWidget {
  const AdaptiveTabScaffold({
    super.key,
    required this.currentIndex,
    required this.onTap,
    required this.pages,
    required this.items,
  });

  final int currentIndex;
  final ValueChanged<int> onTap;
  final List<Widget> pages;
  final List<AdaptiveTabItem> items;

  @override
  Widget build(BuildContext context) {
    assert(pages.length == items.length);

    if (usesCupertino) {
      return CupertinoTabScaffold(
        tabBar: CupertinoTabBar(
          currentIndex: currentIndex,
          onTap: onTap,
          items: [
            for (final item in items)
              BottomNavigationBarItem(
                icon: Icon(item.cupertinoIcon),
                activeIcon: Icon(item.cupertinoActiveIcon ?? item.cupertinoIcon),
                label: item.label,
              ),
          ],
        ),
        tabBuilder: (context, index) => TichTabActiveScope(
          activeIndex: currentIndex,
          tabIndex: index,
          child: pages[index],
        ),
      );
    }

    return Scaffold(
      body: LazyIndexedStack(index: currentIndex, children: pages),
      bottomNavigationBar: NavigationBar(
        selectedIndex: currentIndex,
        onDestinationSelected: onTap,
        destinations: [
          for (final item in items)
            NavigationDestination(
              icon: Icon(item.materialIcon),
              selectedIcon: Icon(item.materialActiveIcon ?? item.materialIcon),
              label: item.label,
            ),
        ],
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
  });

  final String label;
  final IconData materialIcon;
  final IconData cupertinoIcon;
  final IconData? materialActiveIcon;
  final IconData? cupertinoActiveIcon;
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
      decoration: InputDecoration(
        labelText: placeholder,
        suffixIcon: suffix,
      ),
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
      title: Text(title),
      content: content,
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
