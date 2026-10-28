# Google Play Console checklist (TICH mobile)

Use this when completing the store listing, Data safety form, and app review. **Do not commit real passwords or production test accounts to git** — paste credentials only in Play Console.

## Privacy policy

- **URL (must match app and listing):** `https://tich.africa/privacy`
- In-app: **More → Help and feedback → Privacy policy** (uses `ApiConfig.webOrigin`).

## App access (reviewers)

The app requires an **institution-issued account** (ERP login). In **App access**, choose “All or some functionality is restricted” and provide:

| Role | Purpose |
|------|---------|
| Employee | Clock-in, leave, payslips |
| Student | Timetable, finance, academics |
| Staff (faculty) | Attendance capture, grading, timetable |
| Procurement (if enabled) | Requisitions |

**Instructions template:**

1. Install the app and open it.
2. Sign in with the provided email and password (no self-registration in the app).
3. Employee: open **Clock** tab to see attendance; **Home** for dashboard.
4. Student: **Home** and **Timetable** for PDF timetable; **Finance** for fee status.
5. Staff: **Attendance** to test sheet scan (camera); **Timetable** for PDF.

Replace placeholders with real test users created on staging/production as appropriate.

## Account deletion

- Users **do not create accounts in the app**; accounts are provisioned by the employer/institution.
- In the listing, state that account removal is handled by the organization.
- Direct users to **Help and feedback** for privacy contact and the web privacy policy (data erasure requests).

## Data safety mapping (draft for Play form)

Copy/adapt into **App content → Data safety**. Mark **encrypted in transit** (HTTPS in production release builds). **Data is not sold.**

| Data type | Likely collected | Purpose | Notes for the form |
|-----------|------------------|---------|-------------------|
| Email, name, employee/student IDs | Yes | Account / portal | From ERP API after login |
| Precise location | Yes (employee clock-in) | Fraud prevention / attendance | Foreground only; not continuous tracking; in-app disclosure before OS prompt |
| Photos / files | Yes | Attendance sheets, uploads, exam docs | User-initiated (camera, picker, API documents) |
| Financial info | Yes (student finance) | Fees / payments | May include M-Pesa-related fields from API |
| App interactions / crash logs | No (today) | — | No Firebase/Crashlytics in app; declare if you add analytics/crash SDK later |
| Device or other IDs | Yes | Push / device token | App-generated UUID → `POST /auth/device-token`, stored server-side |
| App activity / diagnostics | Yes (portal usage via API) | Notifications, attendance, portal features | Institutional backend only; not shared with third parties for ads |

Also declare where the form asks:

- **Authentication info** — passwords/tokens (secure storage); biometrics **on-device only**, not uploaded.
- **Personal info** — HR/student profile fields shown in portals.
- **Background data** — Workmanager sync hits your API when signed in (if the form version asks).

Biometric unlock is **on-device only** (no upload).

## Engineering priority checklist

| # | Item | Status |
|---|------|--------|
| 1 | Release signing + upload AAB to **internal testing** track | **You** — finish `upload-keystore.jks` + `android/key.properties`, then `.\scripts\build-play-aab.ps1` |
| 2 | Disable cleartext in **release** | **Done** — `network_security_config.xml` (HTTPS only); debug manifest override for LAN dev only |
| 3 | Exact alarm policy (permissions + product copy) | **Done** — no `USE_EXACT_ALARM`; inexact schedules; Help/disclosures describe reminders |
| 4 | Location disclosure before `Geolocator.requestPermission()` | **Done** — `TichPermissionDisclosures.confirmLocationForClockIn` on employee clock-in |
| 5 | Dynamic `app_version` on device registration | **Done** — `package_info_plus` in `registerDeviceToken()` |
| 6 | Reviewer test accounts in Play Console | **Doc ready** — paste real credentials in **App access** only (see section above); not in git |

## Permissions (Android)

| Permission | Why |
|------------|-----|
| `POST_NOTIFICATIONS` | Clock-in and class reminders, portal alerts |
| `ACCESS_FINE/COARSE_LOCATION` | Foreground clock-in verification only |
| `CAMERA` | Attendance sheet scan / photos |
| `RECEIVE_BOOT_COMPLETED` | Reschedule local reminders after reboot |
| `WAKE_LOCK` | Workmanager background sync |
| `FOREGROUND_SERVICE` | Declared by background task plugins |

**Exact alarms:** The app uses **inexact** local scheduling for reminders (not alarm-clock core functionality). `USE_EXACT_ALARM` / `SCHEDULE_EXACT_ALARM` are **not** declared.

## Background work

Workmanager runs periodic tasks (~15 min notification sync, ~30 min clock-in reminder refresh) when the network is available. Tasks call the authenticated API to refresh schedules and notification payloads. Declare **background data collection** in Data safety if required for your form version.

## Exact alarm declaration

If Play Console still prompts about exact alarms, confirm the manifest does **not** include `USE_EXACT_ALARM` and that scheduling uses inexact modes only.

## Release build

See project `README.md` and `scripts/build-play-aab.ps1`. Release builds require `android/key.properties` (not in repo).

After a release AAB upload, open **Pre-launch report** in Play Console for target API and 16 KB page-size warnings.

## Target API / SDK levels

| Setting | Value (current project) |
|---------|-------------------------|
| `compileSdk` | **37** (required by `permission_handler_android`) |
| `targetSdk` | From Flutter (`flutter.targetSdkVersion`, **36** on Flutter 3.44) |
| `minSdk` | From Flutter (**24** on Flutter 3.44) |
| NDK | Flutter default (**28.2.x**) |

Verify locally:

```powershell
cd mobile/tich/android
.\gradlew.bat :app:printPlaySdkInfo
```

Or inspect the merged manifest after `flutter build appbundle --release`:

`build\app\intermediates\merged_manifest\release\processReleaseMainManifest\AndroidManifest.xml`

Google requires a current **target API** for new apps and updates; this project follows Flutter’s stable target (36+).

## R8 / ProGuard

Release builds enable **minify + shrink** (`proguard-rules.pro`). Smoke-test a **release** build on a real device (login, clock-in, camera scan, notifications, biometrics) before shipping. If a plugin breaks, add keep rules to `android/app/proguard-rules.pro`.

## 16 KB memory page size

Play may flag native libraries on newer 64-bit devices. This app uses Flutter’s NDK **28** toolchain and `jniLibs.useLegacyPackaging = false`. Confirm compliance via Play **Pre-launch report** after uploading an AAB. If Google reports misaligned `.so` files, upgrade Flutter to the latest stable and rebuild.

## App version sent to API

`POST /auth/device-token` sends `app_version` from **`package_info_plus`** (matches `pubspec.yaml` `version`, e.g. `1.0.0`). Bump `version:` in `pubspec.yaml` for each store release.

## Education / minors (student portal)

The app includes a **student portal** for enrolled learners (institution-managed accounts, no ads, no social features aimed at children). In Play Console:

- **Target audience**: not a “kids” app; primary users are students and staff at TICH (may include minors under institutional supervision).
- Complete the **IARC content rating** questionnaire honestly (education/ERP, user-generated content minimal, no gambling, no ads).
- **Families / Designed for children**: typically **No**.

## Ads and deep linking

- **No ad SDK** in `pubspec.yaml` — declare “No” for ads in the store form.
- **Exported components**: only `MainActivity` with `MAIN` / `LAUNCHER` (no unintended exported receivers from custom code; plugin manifests may merge additional components — review merged manifest if Play flags them).
