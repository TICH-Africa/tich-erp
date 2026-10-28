# TICH mobile (`mobile/tich`)

Flutter client for the TICH ERP Laravel API - **My Employee Portal**.

## Features

- Auth (Sanctum token, session restore, logout)
- Dashboard (contract, leave balances, recent leave)
- Leave (balances, apply, cancel, stand-in accept/decline)
- Clock in / out (GPS)
- Payslips
- Profile (contact, emergency, next of kin)
- Documents (list + upload)
- HR policies (acknowledge)
- WFH, time logs, appraisals, concerns
- Feedback (submit, review status, and read HR responses)
- Notifications (available from the dashboard bell)

Design tokens match `web/public/css/tich-platform.css` (green `#6cab33`, blue `#1669a6`, Merriweather / Arial).

**Adaptive UI:** checks the OS and renders **Material** on Android and **Cupertino** on iOS (`lib/adaptive/adaptive.dart`).

MFA is **not** enforced on mobile in v1.

## Local setup

1. Serve Laravel:

```bash
cd web
php artisan serve --host=0.0.0.0 --port=8000
```

2. Run Flutter (use your PC LAN IP):

```bash
cd mobile/tich
flutter pub get
flutter run --dart-define=API_BASE_URL=http://192.168.0.102:8000
```

The app defaults to the production API (`https://tich.africa`). For local development, pass your PC's current LAN IP with `--dart-define` as shown above.

**Do not use `http://127.0.0.1:8000` on a physical device** - that points at the phone, not the PC.

Android emulator alternative: `http://10.0.2.2:8000`.

## Production

```bash
flutter run --dart-define=API_BASE_URL=https://tich.africa
```

The release build can omit the define when connecting to production because the production URL is the default.

Release/profile builds use **HTTPS only** on Android. Plain `http://` LAN URLs work in **debug** builds only (see `android/app/src/debug/res/xml/network_security_config.xml`).

## Google Play release (Android App Bundle)

Play Console requires an **AAB** signed with your **upload key** (enable **Play App Signing** on first upload).

1. Create an upload keystore once (store passwords safely; back up the `.jks` file):

```bash
cd mobile/tich/android
keytool -genkey -v -keystore upload-keystore.jks -keyalg RSA -keysize 2048 -validity 10000 -alias upload
```

2. Copy `android/key.properties.example` to `android/key.properties` and fill in paths/passwords.

3. Build the bundle:

```powershell
cd mobile/tich
.\scripts\build-play-aab.ps1
```

Or:

```bash
flutter build appbundle --release
```

Output: `build/app/outputs/bundle/release/app-release.aab` — upload in Play Console (**Release** → **Create new release**).

For **Data safety**, **App access** (reviewer credentials), privacy URL, and permission notes, see [`docs/google-play-console.md`](docs/google-play-console.md).

`assembleRelease` / `bundleRelease` **fail** if `key.properties` is missing, so debug-signed builds cannot be uploaded by mistake.

## API

Employee self-service lives under `/api/employee/*` (Sanctum + `employee.portal`).

PHP controllers live under:

`web/app/Http/Controllers/Api/Employee/mobile/`
