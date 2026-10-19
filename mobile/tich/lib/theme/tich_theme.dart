import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

/// UI font stack from CSS `--font-ui: Arial, Calibri, …` (not a Google Font).
TextStyle tichUi({
  double? fontSize,
  FontWeight? fontWeight,
  Color? color,
  double? height,
  double? letterSpacing,
}) {
  return TextStyle(
    fontFamily: 'Arial',
    fontSize: fontSize,
    fontWeight: fontWeight,
    color: color,
    height: height,
    letterSpacing: letterSpacing,
  );
}

/// Tokens from web/public/css/tich-platform.css (:root) and the sidebar sheet.
abstract final class TichColors {
  static const green = Color(0xFF6CAB33);
  static const blue = Color(0xFF1669A6);
  static const grey = Color(0xFF494C50);
  static const white = Color(0xFFFFFFFF);

  static const greenDark = Color(0xFF5A9430);
  static const greenLight = Color(0xFFE8F3DC);
  static const blueDark = Color(0xFF125A8C);
  static const blueLight = Color(0xFFD6E8F5);
  static const neutral = Color(0xFFF5F6F6);
  static const neutralBorder = Color(0xFFE2E4E5);
  static const neutralMuted = Color(0xFF6B6E72);

  /// --tich-table-head-bg, the blue a section header or table strip wears.
  static const blueStrong = Color(0xFF1565C0);

  /// --tich-error and its soft background.
  static const error = Color(0xFFC53030);
  static const errorDark = Color(0xFF991B1B);
  static const errorSoft = Color(0xFFFEF2F2);
  static const danger = Color(0xFFDC2626);

  /// Status badge tones (--tich-status-*).
  static const successSoft = Color(0xFFD8F3DC);
  static const successText = Color(0xFF1B4332);
  static const cautionSoft = Color(0xFFE3F2FD);
  static const cautionText = Color(0xFF0D3A6E);
  static const warningSoft = Color(0xFFFFF8E6);
  static const warningBorder = Color(0xFFF0D48A);
  static const warningText = Color(0xFF8A6D1A);
  static const statusSoft = Color(0xFFE2E6EA);
  static const statusText = Color(0xFF454B54);

  /// Sidebar (--sb-*): deep blue rail, cream links, green active bar.
  static const sidebar = Color(0xFF125A8C);
  static const sidebarLink = Color(0xFFE8F3DC);
  static const sidebarMuted = Color(0xFFB9D4E8);
  static const sidebarActive = Color(0xFFFFFFFF);
  static const sidebarActiveBg = Color(0x1FFFFFFF);
  static const sidebarHoverBg = Color(0x14FFFFFF);

  static const darkSurface = Color(0xFF1C1F24);
  static const darkBorder = Color(0xFF2A2E35);
  static const darkBackground = Color(0xFF121417);
}

/// --radius-sm / --radius-md / --radius-lg.
abstract final class TichRadius {
  static const double sm = 4;
  static const double md = 8;
  static const double lg = 12;

  static BorderRadius get borderRadiusSm => BorderRadius.circular(sm);
  static BorderRadius get borderRadiusMd => BorderRadius.circular(md);
  static BorderRadius get borderRadiusLg => BorderRadius.circular(lg);
}

/// Page measure. The web container is capped, so a tablet or desktop window
/// keeps the same reading width instead of stretching rows across the screen.
abstract final class TichLayout {
  /// --sb-width, the width the More side bar slides in at.
  static const double railWidth = 264;
  static const double contentMaxWidth = 980;

  /// Tall enough to hold the brand line and the page title without crowding.
  static const double headerHeight = 64;
  static const double headerInset = 16;
  static const EdgeInsets pagePadding = EdgeInsets.fromLTRB(16, 12, 16, 24);
}

abstract final class TichTheme {
  static ThemeData? _light;
  static ThemeData? _dark;
  static CupertinoThemeData? _cupertino;
  static TextStyle? _displayBase;

  /// Pre-build themes so first paint and settings toggles stay cheap.
  static void warmCache() {
    light();
    dark();
    cupertino();
    display();
  }

  /// Cached Merriweather style for titles (avoids GoogleFonts work per widget).
  static TextStyle display({
    double fontSize = 16,
    FontWeight fontWeight = FontWeight.w700,
    Color? color,
    double? height,
  }) {
    _displayBase ??= GoogleFonts.merriweather(
      fontWeight: FontWeight.w700,
      fontSize: 16,
    );
    return _displayBase!.copyWith(
      fontSize: fontSize,
      fontWeight: fontWeight,
      color: color,
      height: height,
    );
  }

  static ThemeData light() {
    if (_light != null) return _light!;
    final merriweather = GoogleFonts.merriweatherTextTheme();
    final colorScheme = ColorScheme.fromSeed(
      seedColor: TichColors.blue,
      primary: TichColors.blue,
      secondary: TichColors.green,
      surface: TichColors.white,
      onPrimary: TichColors.white,
      onSecondary: TichColors.white,
      onSurface: TichColors.grey,
      error: TichColors.error,
    );

    final built = ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: TichColors.neutral,
      textTheme: merriweather.apply(
        bodyColor: TichColors.grey,
        displayColor: TichColors.grey,
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: TichColors.blue,
        foregroundColor: TichColors.white,
        elevation: 0,
        scrolledUnderElevation: 0,
        centerTitle: false,
        iconTheme: const IconThemeData(color: TichColors.white),
        actionsIconTheme: const IconThemeData(color: TichColors.white),
        titleTextStyle: display(
          color: TichColors.white,
          fontSize: 18,
        ),
      ),
      cardTheme: CardThemeData(
        color: TichColors.white,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: TichRadius.borderRadiusLg,
          side: const BorderSide(color: TichColors.neutralBorder),
        ),
        margin: EdgeInsets.zero,
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: TichColors.blue,
          foregroundColor: TichColors.white,
          textStyle: tichUi(fontWeight: FontWeight.w600, fontSize: 14),
          padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: TichRadius.borderRadiusMd),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: TichColors.blue,
          side: const BorderSide(color: TichColors.blue),
          textStyle: tichUi(fontWeight: FontWeight.w600, fontSize: 14),
          shape: RoundedRectangleBorder(borderRadius: TichRadius.borderRadiusMd),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: TichColors.blue,
          textStyle: tichUi(fontWeight: FontWeight.w600, fontSize: 14),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: TichColors.white,
        floatingLabelBehavior: FloatingLabelBehavior.auto,
        alignLabelWithHint: true,
        isDense: true,
        errorMaxLines: 2,
        border: OutlineInputBorder(
          borderRadius: TichRadius.borderRadiusMd,
          borderSide: const BorderSide(color: TichColors.neutralBorder),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: TichRadius.borderRadiusMd,
          borderSide: const BorderSide(color: TichColors.neutralBorder),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: TichRadius.borderRadiusMd,
          borderSide: const BorderSide(color: TichColors.blue, width: 1.5),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: TichRadius.borderRadiusMd,
          borderSide: const BorderSide(color: TichColors.error),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: TichRadius.borderRadiusMd,
          borderSide: const BorderSide(color: TichColors.error, width: 1.5),
        ),
        labelStyle: tichUi(color: TichColors.neutralMuted, fontSize: 14),
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: TichColors.white,
        indicatorColor: TichColors.blueLight,
        height: 68,
        elevation: 0,
        labelTextStyle: WidgetStateProperty.resolveWith(
          (states) => tichUi(
            fontSize: 11,
            fontWeight: FontWeight.w600,
            color: states.contains(WidgetState.selected)
                ? TichColors.blueDark
                : TichColors.neutralMuted,
          ),
        ),
        iconTheme: WidgetStateProperty.resolveWith(
          (states) => IconThemeData(
            size: 24,
            color: states.contains(WidgetState.selected)
                ? TichColors.blueDark
                : TichColors.neutralMuted,
          ),
        ),
      ),
      drawerTheme: const DrawerThemeData(
        backgroundColor: TichColors.sidebar,
        surfaceTintColor: Colors.transparent,
      ),
      chipTheme: ChipThemeData(
        backgroundColor: TichColors.greenLight,
        labelStyle: tichUi(fontSize: 12, color: TichColors.greenDark),
        side: BorderSide.none,
        shape: RoundedRectangleBorder(borderRadius: TichRadius.borderRadiusMd),
      ),
      dividerTheme: const DividerThemeData(
        color: TichColors.neutralBorder,
        thickness: 1,
        space: 1,
      ),
      dividerColor: TichColors.neutralBorder,
      listTileTheme: const ListTileThemeData(
        contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 4),
        iconColor: TichColors.blue,
      ),
      snackBarTheme: SnackBarThemeData(
        backgroundColor: TichColors.grey,
        contentTextStyle: tichUi(color: TichColors.white, fontSize: 14),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: TichRadius.borderRadiusMd),
      ),
      floatingActionButtonTheme: const FloatingActionButtonThemeData(
        backgroundColor: TichColors.green,
        foregroundColor: TichColors.white,
      ),
      progressIndicatorTheme: const ProgressIndicatorThemeData(
        color: TichColors.blue,
      ),
    );
    return _light = built;
  }

  static ThemeData dark() {
    if (_dark != null) return _dark!;
    final merriweather = GoogleFonts.merriweatherTextTheme(ThemeData.dark().textTheme);
    final colorScheme = ColorScheme.fromSeed(
      seedColor: TichColors.blue,
      brightness: Brightness.dark,
      primary: TichColors.blueLight,
      secondary: TichColors.green,
      surface: TichColors.darkSurface,
      error: TichColors.danger,
    );

    final built = ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: TichColors.darkBackground,
      textTheme: merriweather,
      appBarTheme: AppBarTheme(
        backgroundColor: TichColors.blueDark,
        foregroundColor: TichColors.white,
        elevation: 0,
        scrolledUnderElevation: 0,
        titleTextStyle: display(
          color: TichColors.white,
          fontSize: 18,
        ),
      ),
      cardTheme: CardThemeData(
        color: TichColors.darkSurface,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: TichRadius.borderRadiusLg,
          side: const BorderSide(color: TichColors.darkBorder),
        ),
        margin: EdgeInsets.zero,
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: TichColors.blue,
          foregroundColor: TichColors.white,
          textStyle: tichUi(fontWeight: FontWeight.w600, fontSize: 14),
          shape: RoundedRectangleBorder(borderRadius: TichRadius.borderRadiusMd),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: TichColors.blueLight,
          side: const BorderSide(color: Color(0xFF3D4F5F)),
          textStyle: tichUi(fontWeight: FontWeight.w600, fontSize: 14),
          shape: RoundedRectangleBorder(borderRadius: TichRadius.borderRadiusMd),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: TichColors.blueLight,
          textStyle: tichUi(fontWeight: FontWeight.w600, fontSize: 14),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: TichColors.darkSurface,
        floatingLabelBehavior: FloatingLabelBehavior.auto,
        alignLabelWithHint: true,
        isDense: true,
        errorMaxLines: 2,
        border: OutlineInputBorder(
          borderRadius: TichRadius.borderRadiusMd,
          borderSide: const BorderSide(color: TichColors.darkBorder),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: TichRadius.borderRadiusMd,
          borderSide: const BorderSide(color: TichColors.darkBorder),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: TichRadius.borderRadiusMd,
          borderSide: const BorderSide(color: TichColors.blueLight, width: 1.5),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: TichRadius.borderRadiusMd,
          borderSide: const BorderSide(color: TichColors.danger),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: TichRadius.borderRadiusMd,
          borderSide: const BorderSide(color: TichColors.danger, width: 1.5),
        ),
        labelStyle: tichUi(color: const Color(0xFF9AA0A6), fontSize: 14),
        hintStyle: tichUi(color: const Color(0xFF9AA0A6), fontSize: 14),
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: TichColors.darkSurface,
        indicatorColor: const Color(0xFF24384A),
        height: 68,
        elevation: 0,
        labelTextStyle: WidgetStateProperty.resolveWith(
          (states) => tichUi(
            fontSize: 11,
            fontWeight: FontWeight.w600,
            color: states.contains(WidgetState.selected)
                ? TichColors.blueLight
                : const Color(0xFF9AA0A6),
          ),
        ),
        iconTheme: WidgetStateProperty.resolveWith(
          (states) => IconThemeData(
            size: 24,
            color: states.contains(WidgetState.selected)
                ? TichColors.blueLight
                : const Color(0xFF9AA0A6),
          ),
        ),
      ),
      drawerTheme: const DrawerThemeData(
        backgroundColor: TichColors.white,
        surfaceTintColor: Colors.transparent,
      ),
      listTileTheme: const ListTileThemeData(
        contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      ),
      dividerTheme: const DividerThemeData(
        color: TichColors.darkBorder,
        thickness: 1,
        space: 1,
      ),
      dividerColor: TichColors.darkBorder,
      snackBarTheme: SnackBarThemeData(
        contentTextStyle: tichUi(fontSize: 14),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: TichRadius.borderRadiusMd),
      ),
      floatingActionButtonTheme: const FloatingActionButtonThemeData(
        backgroundColor: TichColors.green,
        foregroundColor: TichColors.white,
      ),
      progressIndicatorTheme: const ProgressIndicatorThemeData(
        color: TichColors.blueLight,
      ),
    );
    return _dark = built;
  }

  static CupertinoThemeData cupertino() {
    if (_cupertino != null) return _cupertino!;
    final built = CupertinoThemeData(
      primaryColor: TichColors.blue,
      scaffoldBackgroundColor: TichColors.neutral,
      barBackgroundColor: TichColors.blue,
      primaryContrastingColor: TichColors.white,
      textTheme: CupertinoTextThemeData(
        primaryColor: TichColors.grey,
        textStyle: display(
          color: TichColors.grey,
          fontSize: 14,
          fontWeight: FontWeight.w400,
        ),
        navTitleTextStyle: display(
          color: TichColors.white,
          fontSize: 16,
        ),
        navLargeTitleTextStyle: display(
          color: TichColors.white,
          fontSize: 28,
        ),
        actionTextStyle: tichUi(
          color: TichColors.blue,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
    return _cupertino = built;
  }
}
