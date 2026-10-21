# TICH mobile (`mobile/tich`)

Flutter client for the TICH ERP Laravel API — **My Employee Portal**.

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

**Do not use `http://127.0.0.1:8000` on a physical device** — that points at the phone, not the PC.

Android emulator alternative: `http://10.0.2.2:8000`.

## Production

```bash
flutter run --dart-define=API_BASE_URL=https://tich.africa
```

The release build can omit the define when connecting to production because the production URL is the default.

## API

Employee self-service lives under `/api/employee/*` (Sanctum + `employee.portal`).

PHP controllers live under:

`web/app/Http/Controllers/Api/Employee/mobile/`
