# App Store Connect checklist (TICH mobile)

Use when creating the app record, TestFlight, and App Store review. **Do not commit production reviewer passwords to git** — use App Store Connect notes only.

**Android parity:** privacy and data-collection answers largely match [`google-play-console.md`](google-play-console.md).

## App record

| Field | Value |
|-------|--------|
| **Bundle ID** | `com.tich.africa.tich` (must match Xcode / Apple Developer) |
| **SKU** | Your choice (e.g. `tich-mobile-erp`) |
| **Name** | TICH in Africa (or listing title) |
| **Primary category** | Business or Education |
| **Privacy policy URL** | `https://tich.africa/privacy` |

Register the bundle ID in [Apple Developer → Identifiers](https://developer.apple.com/account/resources/identifiers/list) before the first archive upload.

## Signing and build

1. **Apple Developer Program** membership (org account recommended).
2. In Xcode (open `ios/Runner.xcworkspace` after `flutter pub get` on a Mac):

   - **Runner** target → **Signing & Capabilities**: Team, **Automatically manage signing**.
   - Confirm **Bundle Identifier** = `com.tich.africa.tich`.

3. CLI release build:

```bash
cd mobile/tich
flutter build ipa --release --dart-define=API_BASE_URL=https://tich.africa
```

Output: `build/ios/ipa/*.ipa` — upload with **Transporter** or Xcode **Organizer**.

4. **TestFlight** internal testers → external after beta review (if needed).

### Local API during development

- Production release must use **HTTPS** (`https://tich.africa`).
- Debug against a PC on Wi‑Fi: `flutter run --dart-define=API_BASE_URL=http://192.168.x.x:8000`
- iOS **14+**: `NSAllowsLocalNetworking` in `Info.plist` allows **HTTP on the local network** (LAN IP). Simulator: `localhost` exception is also configured.
- iOS **13** or stubborn ATS errors: use HTTPS on the dev machine or test on Android for plain HTTP.

## App Privacy (nutrition labels)

Mirror Play **Data safety** (see google-play doc). Typical declarations:

| Data | Linked to user | Purpose |
|------|----------------|--------|
| Contact info (email, name) | Yes | App functionality |
| Identifiers (device token UUID) | Yes | App functionality / notifications |
| Precise location | Yes | App functionality (clock-in, foreground) |
| Photos / files | Yes | App functionality (attendance, uploads) |
| Financial info (student fees) | Yes | App functionality |
| User content (forms, feedback) | Yes | App functionality |

- **Tracking:** No (unless you add analytics SDKs later).
- **Data not used for advertising.**
- **Encryption:** HTTPS in transit; tokens in Keychain (iOS) / encrypted prefs (Android).

## Export compliance

The app uses standard HTTPS and does not implement custom cryptography beyond the OS. In App Store Connect, you typically answer:

- **Uses encryption?** Yes (HTTPS).
- **Exempt / standard encryption** per Apple's questionnaire (most ERP apps qualify for the exempt path; confirm with your legal/compliance contact).

## Sign-in for review

Institution-issued ERP accounts only (no in-app registration). In **App Review Information**:

1. Provide a **demo email + password** for each role you want exercised (employee, student, staff, procurement if enabled).
2. Note **first mobile sign-in** requires a **6-digit email OTP** (expires in 15 minutes, from `otp@tich.africa`). Ensure reviewer mailbox can receive mail, or pre-verify `mobile_app_verified_at` for the demo user in staging.

**Steps for reviewers:**

1. Install via TestFlight or review build.
2. Sign in with supplied credentials.
3. Enter email OTP if prompted (first mobile login).
4. Employee: **Clock** tab; Student: **Timetable** / **Academics**; Staff: **Attendance** (camera disclosure appears first).

## Permission strings (Info.plist)

Already configured on iOS:

- Camera, photo library (attendance)
- Location when in use (clock-in)
- Face ID (app lock)

In-app **rationales** appear before system prompts (`TichPermissionDisclosures`).

## Background modes

Local reminders and limited background sync use **Workmanager** / notification APIs. Declare only what you enable in Xcode capabilities. The app does **not** claim continuous background location.

## Account deletion

Same as Play: accounts are provisioned by the institution; erasure requests via organization / privacy policy contact.

## Engineering checklist

- [ ] Bundle ID `com.tich.africa.tich` in Developer portal and Xcode
- [ ] Privacy policy URL live
- [ ] Demo users + OTP email works for review account
- [ ] `flutter build ipa --release` succeeds on CI or dev Mac
- [ ] TestFlight smoke: login, OTP, clock-in, notification prompt, staff camera flow
- [ ] App Privacy answers filed in App Store Connect