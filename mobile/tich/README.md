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

Design tokens match `web/public/css/tich-platform.css` (green `#6cab33`, blue `#1669a6`, Merriweather / Arial).

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

Android emulator: `http://10.0.2.2:8000`  
Do **not** use `127.0.0.1` on a physical phone.

## Production

```bash
flutter run --dart-define=API_BASE_URL=https://tich.africa
```

## API

Employee self-service lives under `/api/employee/*` (Sanctum + `employee.portal`).
