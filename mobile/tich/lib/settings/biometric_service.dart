import 'package:flutter/services.dart';
import 'package:local_auth/local_auth.dart';

class BiometricService {
  BiometricService({LocalAuthentication? auth})
      : _auth = auth ?? LocalAuthentication();

  final LocalAuthentication _auth;

  /// Hardware can do biometrics OR device has a lock screen (PIN/pattern).
  Future<bool> get isSupported async {
    try {
      final canBio = await _auth.canCheckBiometrics;
      final device = await _auth.isDeviceSupported();
      return canBio || device;
    } on PlatformException {
      return false;
    } catch (_) {
      return false;
    }
  }

  Future<bool> get canCheckBiometrics => isSupported;

  Future<List<BiometricType>> get availableTypes async {
    try {
      return await _auth.getAvailableBiometrics();
    } on PlatformException {
      return const [];
    } catch (_) {
      return const [];
    }
  }

  Future<String> get label async {
    final types = await availableTypes;
    if (types.contains(BiometricType.face)) return 'Face unlock';
    if (types.contains(BiometricType.fingerprint)) return 'Fingerprint';
    if (types.contains(BiometricType.strong) ||
        types.contains(BiometricType.weak)) {
      return 'Biometric unlock';
    }
    return 'Fingerprint / Face unlock';
  }

  /// Returns null on success, or an error message on failure.
  Future<String?> authenticate({String reason = 'Unlock TICH'}) async {
    try {
      final ok = await _auth.authenticate(
        localizedReason: reason,
        options: const AuthenticationOptions(
          biometricOnly: false,
          stickyAuth: true,
          useErrorDialogs: true,
        ),
      );
      return ok ? null : 'Authentication cancelled.';
    } on PlatformException catch (e) {
      return e.message ?? 'Biometric authentication failed (${e.code}).';
    } catch (e) {
      return e.toString();
    }
  }
}
