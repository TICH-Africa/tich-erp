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

## Data safety mapping (high level)

| Data type | Collected | Purpose | Shared |
|-----------|-----------|---------|--------|
| Email, name, employee/student ID | Yes | Authentication, portal | Not shared (institution backend only) |
| Precise location | Yes, foreground only at clock-in | Verify attendance location | Not shared |
| Photos (camera) | Yes, user-initiated | Attendance sheets, class photos | Institution only |
| Financial info (fees, M-Pesa refs) | Yes, from API | Student finance portal | Not shared |
| Files/documents | Yes, from API | HR/student documents | Not shared |
| Device or other IDs | Yes (app-generated UUID) | `/auth/device-token` for notification targeting | Not shared |
| App activity | Yes | Notifications, attendance, portal usage via API | Not shared |

Declare **encryption in transit** (HTTPS). Biometric unlock is **on-device only** (no upload).

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
