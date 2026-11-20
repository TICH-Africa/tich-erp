# Mobile app release 1.0.0

| Field | Value |
|-------|--------|
| Version label | `1.0.0` |
| Build number | `1` |
| `pubspec.yaml` | `1.0.0+1` |
| Bundle ID | `com.tich.africa.tich` |

## Build artifacts (this machine)

From `mobile/tich`:

```powershell
.\scripts\package-v1-release.ps1
```

That runs a sideload release build and copies APKs into this folder.

- **Upload this (arm64, most phones):** `tich-mobile-1.0.0-build1-android.apk`
- Optional splits: `tich-mobile-1.0.0-build1-android-armeabi-v7a.apk`, `…-x86_64.apk`, or universal fat APK if produced

For Play Store, use `.\scripts\build-play-aab.ps1` (upload keystore required).

## Publish on tich.africa/mobile-app

1. Sign in to ERP → **ICT** → **Mobile app releases**.
2. **Android:** platform Android, version `1.0.0`, build `1`, paste notes from `release-notes.txt`, upload the **arm64-v8a** APK (or universal APK).
3. **iOS:** platform iOS, version `1.0.0`, build `1`, same release notes, add **TestFlight or App Store URL** (or enterprise IPA if applicable).

Students and staff will see the new version on [https://tich.africa/mobile-app](https://tich.africa/mobile-app).

### CLI (same as ICT upload)

From `web/` on the server:

```bash
php artisan mobile-app:publish android 1.0.0 --build=1 \
  --apk=../mobile/tich/releases/v1.0.0/tich-mobile-1.0.0-build1-android.apk
```

Release notes default to this folder’s `release-notes.txt`. For iOS, add `--url=` with TestFlight or App Store link when ready.