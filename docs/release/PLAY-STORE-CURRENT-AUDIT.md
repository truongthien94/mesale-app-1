# Google Play Current-State Audit

Audit date: 2026-08-09
Timezone: Asia/Bangkok
Target: `mobile/`
Verdict: **NOT READY FOR GOOGLE PLAY SUBMISSION**
Milestone status: **M1 is not complete**

## Evidence labels

- **Observed:** directly present in repository files.
- **Measured:** produced by a local read-only command against the current working tree.
- **Reported:** stated in approved migration documents but not yet proven in an AAB/device run.
- **Unknown:** requires a generated production manifest/AAB, Play Console, device evidence or credentials not inspected here.

## Project summary

- **Observed:** Expo SDK 53 / React Native 0.79.6 managed app with application ID `vn.mesale.app` (`mobile/package.json:15`, `mobile/package.json:24`, `mobile/app.json:33`).
- **Observed:** only bootstrap, email/password login and a placeholder authenticated home/logout flow exist (`mobile/app/index.tsx:6`, `mobile/app/(auth)/login.tsx:8`, `mobile/app/home.tsx:7`).
- **Measured:** TypeScript check passes.
- **Measured:** production dependency audit reports 7 high and 15 moderate advisories, with no critical advisories.
- **Measured:** production-mode Expo export produces approximately 2.69 MB JavaScript bundles for both iOS and Android. These are bundle-size baselines only; they do not establish startup TTI, FPS, render cost, memory behavior, final APK/AAB size or 16 KB page-size compatibility.

## Critical blockers

### [CRITICAL] Broken/Minimum Functionality - Product implementation is incomplete

- **Observed:** the current mobile surface does not yet include registration, account deletion, cashback, orders, wallet, withdrawal, tasks, gifts, notifications, legal/support, app links or store-review utility.
- **Required:** M1 and the representative native vertical slices must be completed and verified before an AAB can be considered for testing or review.

### [CRITICAL] Target API requirement is not verified

- **Observed:** `mobile/app.json` does not pin or expose an Android target SDK (`mobile/app.json:33`).
- **Unknown:** no generated Android project or release AAB exists in the audited source, so the resolved target SDK cannot be proven.
- **Policy timing:** on 2026-08-09, API 35 is the current new-app/update floor; from 2026-08-31 new apps and updates must target API 36 under the July 2026 policy snapshot.
- **Required:** choose an Expo/RN toolchain that resolves to the submission-date requirement, generate the production artifact, inspect `targetSdkVersion`, and repeat immediately before submission.

### [SOURCE CONFIG REMEDIATED; PRODUCTION MANIFEST VERIFICATION PENDING] Permissions policy

- **Observed:** `mobile/app.json` blocks `SYSTEM_ALERT_WINDOW`, `READ_EXTERNAL_STORAGE` and `WRITE_EXTERNAL_STORAGE` (`mobile/app.json:33`).
- **Measured after remediation:** fresh Expo introspection resolves `INTERNET` and `VIBRATE` as the effective permissions; the three blocked entries are retained only as manifest removals with `tools:node=remove`.
- **Remaining gate:** inspect the production release merged manifest/AAB before closing this finding. Use the Android photo picker for future avatar/manual-submission media.
- **Unknown:** production merged-manifest sources and any max-SDK constraints are not available until prebuild/AAB generation.

### [CRITICAL] User Data and Account Deletion requirements are not implemented

- **Observed:** no in-app Privacy Policy or account-deletion flow exists in `mobile/app/`.
- **Reported:** final app includes account creation and member data; it therefore needs both readily discoverable in-app deletion and a public web deletion resource.
- **Required:** implement deletion, verify server-side associated-data handling, publish the web resource, and reconcile the behavior with the Data safety form.

### [CRITICAL] Data safety and financial declarations are unknown

- **Observed:** current dependencies do not include ad, analytics or crash-reporting SDKs, reducing the current SDK declaration surface (`mobile/package.json:13`).
- **Reported:** the product handles account identifiers, cashback, wallet balances, orders, referrals and withdrawals through Laravel.
- **Required:** complete the Data safety inventory from actual network payloads and SDK behavior. Review the Financial Features Declaration because wallet/withdrawal functionality may be in scope; do not guess the Console answers.

## Warnings and release gaps

- **AAB and signing:** no production AAB, Play App Signing evidence, version code or release merged manifest has been inspected. EAS production `autoIncrement` is configured, but the resolved version code is **unknown** (`mobile/eas.json:15`).
- **16 KB page size:** RN 0.79+ provides aligned React Native core binaries, but every third-party native `.so` still requires release APK/AAB verification. No artifact exists, so compatibility is **unknown**, not passed.
- **App Links:** only a custom scheme exists; no Android `intentFilters` or verified `assetlinks.json` configuration is present (`mobile/app.json:7`, `mobile/app.json:33`).
- **Cleartext:** the TypeScript environment gate requires HTTPS outside development (`mobile/src/config/env.ts:14`). The release manifest/network-security behavior remains **unknown** until the generated project is checked.
- **Metadata/assets:** no source launcher icon or store screenshot assets were found. Target audience, IARC content rating, support contact and store listing are **unknown/manual**.
- **Supply chain:** npm reports 22 production-tree advisories. Upgrade only through an Expo-compatible resolution, then rerun audit, typecheck, build and device tests.
- **UGC:** planned comments require terms acceptance, moderation, in-app reporting and blocking before exposure.

## Positive current evidence

- **Observed:** no SMS/Call Log, background location, `QUERY_ALL_PACKAGES`, `MANAGE_EXTERNAL_STORAGE`, accessibility-service, exact-alarm or install-package functionality is declared in source app configuration (`mobile/app.json:33`). Production merged-manifest verification is still required.
- **Observed:** OTP is server/email based in the target architecture; no restricted SMS permission package is installed.
- **Observed:** no Play Billing or external digital-unlock payment SDK is installed. Planned Shopee/TikTok/Lazada purchases are physical-goods handoffs and should not use Play Billing.
- **Observed:** no keystore, service-account file or hardcoded credential exists under `mobile/`; root ignore rules cover common signing and secret formats (`.gitignore:1`, `.gitignore:43`).
- **Observed:** the app uses SecureStore for session credentials and no WebView wrapper is present (`mobile/src/auth/session.ts:1`).

## Unknown/manual checks

- Play Console Data safety, Financial Features, target audience, IARC and account deletion declarations.
- Developer account/organization status, Play App Signing, review credentials and staged rollout configuration.
- Production merged manifest, target SDK, native library page alignment and cleartext setting.
- Physical-device tests, Android 15/16 edge-to-edge behavior, accessibility and low-end performance.
- Final App Links/assetlinks response and notification permission behavior after push is implemented.

## Audit trail

Applied on 2026-08-09: `playstore-review` with the July 2026 Google Play policy reference; `migrate-cashback-to-react-native`; `assess-react-native-migration`; `react-native-best-practices` including Android 16 KB guidance; and `react-navigation` safe-area guidance. This is a current-state audit, not a Play-readiness certification.
