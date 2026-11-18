# Changelog — TICH mobile

All notable releases of the Flutter app (`com.tich.africa.tich`).

## [1.0.0] — 2026-11-18

**Build:** `1.0.0+1` (version name `1.0.0`, version code `1`)

### Highlights

- First production release of the TICH mobile app connected to `https://tich.africa`.
- Employee self-service: dashboard, leave, clock in/out (GPS), payslips, profile, documents, HR policies, WFH, time logs, appraisals, concerns, feedback, notifications.
- Student / staff / procurement portals (role-aware navigation).
- Sanctum auth with secure token storage (Android encrypted prefs, iOS Keychain).
- First-time mobile sign-in: email OTP verification (`otp@tich.africa`).
- Local notifications and background sync (battery-aware polling and Workmanager).
- Material UI on Android, Cupertino on iOS.
- Production API HTTPS only; LAN HTTP allowed in debug for development.

### Distribution

| Channel | Android | iOS |
|---------|---------|-----|
| Public download page | ICT uploads APK at `/ict/mobile-app` → [tich.africa/mobile-app](https://tich.africa/mobile-app) | TestFlight / App Store URL when published |
| Play Store | Upload `app-release.aab` (see `docs/google-play-console.md`) | — |
| App Store | — | See `docs/app-store-connect.md` |

[1.0.0]: https://tich.africa/mobile-app