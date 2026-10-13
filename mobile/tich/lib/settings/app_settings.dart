import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

enum AppFontSize { small, normal, large }

class AppSettings extends ChangeNotifier {
  AppSettings._();

  static final AppSettings instance = AppSettings._();

  static const _themeKey = 'tich_theme_mode';
  static const _fontKey = 'tich_font_size';
  static const _biometricKey = 'tich_biometric_enabled';
  static const _lockOnExitKey = 'tich_lock_on_exit';
  static const _signedInAtKey = 'tich_signed_in_at';
  static const _needsUnlockKey = 'tich_needs_unlock';

  /// Default unlocked session length (WhatsApp-style grace).
  static const sessionDays = 7;

  ThemeMode themeMode = ThemeMode.system;
  AppFontSize fontSize = AppFontSize.normal;
  bool biometricEnabled = false;
  bool lockOnExit = false;
  DateTime? signedInAt;
  bool needsUnlock = false;

  double get textScaleFactor => switch (fontSize) {
        AppFontSize.small => 0.90,
        AppFontSize.normal => 1.0,
        AppFontSize.large => 1.15,
      };

  Future<void> load() async {
    final prefs = await SharedPreferences.getInstance();
    themeMode = ThemeMode.values[prefs.getInt(_themeKey) ?? ThemeMode.system.index];
    fontSize = AppFontSize.values[prefs.getInt(_fontKey) ?? AppFontSize.normal.index];
    biometricEnabled = prefs.getBool(_biometricKey) ?? false;
    lockOnExit = prefs.getBool(_lockOnExitKey) ?? false;
    needsUnlock = prefs.getBool(_needsUnlockKey) ?? false;
    final signed = prefs.getString(_signedInAtKey);
    signedInAt = signed == null ? null : DateTime.tryParse(signed);
    notifyListeners();
  }

  Future<void> setThemeMode(ThemeMode mode) async {
    themeMode = mode;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt(_themeKey, mode.index);
    notifyListeners();
  }

  Future<void> setFontSize(AppFontSize size) async {
    fontSize = size;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt(_fontKey, size.index);
    notifyListeners();
  }

  Future<void> setBiometricEnabled(bool value) async {
    biometricEnabled = value;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_biometricKey, value);
    notifyListeners();
  }

  Future<void> setLockOnExit(bool value) async {
    lockOnExit = value;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_lockOnExitKey, value);
    notifyListeners();
  }

  Future<void> markSignedIn() async {
    signedInAt = DateTime.now();
    needsUnlock = false;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_signedInAtKey, signedInAt!.toIso8601String());
    await prefs.setBool(_needsUnlockKey, false);
    notifyListeners();
  }

  Future<void> clearSessionMeta() async {
    signedInAt = null;
    needsUnlock = false;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_signedInAtKey);
    await prefs.setBool(_needsUnlockKey, false);
    notifyListeners();
  }

  Future<void> markNeedsUnlock() async {
    needsUnlock = true;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_needsUnlockKey, true);
    notifyListeners();
  }

  Future<void> clearNeedsUnlock() async {
    needsUnlock = false;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_needsUnlockKey, false);
    notifyListeners();
  }

  bool get sessionExpired {
    if (signedInAt == null) return true;
    return DateTime.now().difference(signedInAt!).inDays >= sessionDays;
  }

  /// Password re-login required after 7 days, or when lock-on-exit flagged unlock.
  bool get requiresPasswordReauth => sessionExpired;

  bool get requiresAppLock =>
      needsUnlock || (biometricEnabled && needsUnlock) || (lockOnExit && needsUnlock);
}
