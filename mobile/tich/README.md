# TICH mobile (`mobile/tich`)

Flutter client for the TICH ERP Laravel API.

## First milestone

- Email + password login (`POST /api/auth/login`)
- Sanctum token stored securely
- Session restore via `GET /api/auth/me`
- Logout (`POST /api/auth/logout`)
- Role-aware home stub (employee vs student)

MFA is **not** enforced on mobile in v1 (matches product decision; web MFA remains).

## Local setup (phone/emulator ↔ Laravel on PC)

1. On the PC, serve Laravel on all interfaces:

```bash
cd web
php artisan serve --host=0.0.0.0 --port=8000
```

2. Find the PC LAN IP (same Wi‑Fi as the phone), e.g. `192.168.0.104`.

3. Run Flutter with that base URL:

```bash
cd mobile/tich
flutter pub get
flutter run --dart-define=API_BASE_URL=http://192.168.0.104:8000
```

Default in code is `http://192.168.0.104:8000` — change via `--dart-define` if your IP differs.

**Do not use `http://127.0.0.1:8000` on a physical device** — that points at the phone, not the PC.

Android emulator alternative: `http://10.0.2.2:8000`.

## Production

```bash
flutter run --dart-define=API_BASE_URL=https://tich.africa
# or release build with the same define
```

## Stack

- HTTP: `http`
- State: `flutter_bloc`
- Token: `flutter_secure_storage`
