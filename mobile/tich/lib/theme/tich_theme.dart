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

/// Tokens from web/public/css/tich-platform.css (:root).
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
      error: const Color(0xFFB3261E),
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
        centerTitle: false,
        titleTextStyle: display(
          color: TichColors.white,
          fontSize: 18,
        ),
      ),
      cardTheme: CardThemeData(
        color: TichColors.white,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
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
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: TichColors.blue,
          side: const BorderSide(color: TichColors.blue),
          textStyle: tichUi(fontWeight: FontWeight.w600, fontSize: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: TichColors.white,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: TichColors.neutralBorder),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: TichColors.neutralBorder),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: TichColors.blue, width: 1.5),
        ),
        labelStyle: tichUi(color: TichColors.neutralMuted, fontSize: 14),
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: TichColors.white,
        indicatorColor: TichColors.blueLight,
        labelTextStyle: WidgetStatePropertyAll(
          tichUi(fontSize: 11, fontWeight: FontWeight.w600),
        ),
      ),
      chipTheme: ChipThemeData(
        backgroundColor: TichColors.greenLight,
        labelStyle: tichUi(fontSize: 12, color: TichColors.greenDark),
        side: BorderSide.none,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      ),
      dividerColor: TichColors.neutralBorder,
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
    );

    final built = ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: const Color(0xFF121417),
      textTheme: merriweather,
      appBarTheme: AppBarTheme(
        backgroundColor: TichColors.blueDark,
        foregroundColor: TichColors.white,
        titleTextStyle: display(
          color: TichColors.white,
          fontSize: 18,
        ),
      ),
      cardTheme: CardThemeData(
        color: const Color(0xFF1C1F24),
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
          side: const BorderSide(color: Color(0xFF2A2E35)),
        ),
        margin: EdgeInsets.zero,
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: TichColors.blue,
          foregroundColor: TichColors.white,
        ),
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
