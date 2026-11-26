# Changelog — TICH mobile

All notable releases of the Flutter app (`com.tich.africa.tich`).

## [Unreleased]

### Sync & reliability (Android + iOS)

- Fix HTTP GET cache invalidation after writes (clock-in, leave, notifications, etc.) so lists update immediately instead of waiting up to ~25s.
- Clock-in/out: optimistic UI from server response, forced badge refresh, fresh attendance refetch.
- App resume: clear short-lived GET cache and nudge visible screens to refresh (throttled).
- Bottom-nav badges: refresh on data-stale signal, resume, and after mutations (employee, student, staff, procurement).
- Slightly faster background refresh when a screen opens with cached data (12s → 6s delay before first silent poll).

## [1.0.2] — 2026-11-18

**Build:** `1.0.2+3`

### Performance (first install — follow-up)

- Non-home screens wait until the portal home/dashboard payload settles before starting network work.
- Caps concurrent HTTP GETs on empty cache (2 at a time) so the API is not flooded.
- Safe post-frame live refresh scheduling across student, employee, staff, and procurement screens (no duplicate fetch while `ensure` runs).
- Tab and More-menu prefetch gated on cold start; explicit switch cases in portal warmup.
- Shell profile photo reads home cache after wait instead of firing a second dashboard request.

## [1.0.1] — 2026-11-18

**Build:** `1.0.1+2`

### Performance (first install / empty cache)

- Serializes API work on cold start so portals load one screen at a time instead of dozens of parallel calls.
- Stops duplicate post-frame refreshes while the first fetch is still running.
- Defers background warmup, WebSocket, notifications, and employee badge polls until the home dashboard loads.
- Drops the 90s default timeout on timetable JSON (uses the standard 30s API timeout).
- Longer GET dedupe window on first session to avoid repeat calls across tabs.

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