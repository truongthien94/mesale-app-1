# App Store Current-State Audit

Audit date: 2026-08-09
Timezone: Asia/Bangkok
Target: `mobile/`
Verdict: **NOT READY FOR APP STORE SUBMISSION**
Milestone status: **M1 is not complete**

## Evidence labels

- **Observed:** directly present in repository files.
- **Measured:** produced by a local read-only command against the current working tree.
- **Reported:** stated in the approved migration documents but not yet proven in the binary.
- **Unknown:** requires generated native projects, an EAS/App Store artifact, device evidence, App Store Connect, or credentials not inspected in this audit.

## Project summary

- **Observed:** Expo managed application using Expo SDK 53, React Native 0.79.6 and Expo Router 5, which uses React Navigation underneath (`mobile/package.json:17`, `mobile/package.json:22`, `mobile/package.json:26`).
- **Observed:** app name `Mesale`, version `0.1.0`, iOS bundle identifier `vn.mesale.app`, tablet support enabled (`mobile/app.json:3`, `mobile/app.json:5`, `mobile/app.json:24`, `mobile/app.json:25`).
- **Observed:** implemented user routes are bootstrap, email/password login, email verification, two-factor verification and a session-active placeholder home (`mobile/app/index.tsx:6`, `mobile/app/(auth)/login.tsx:9`, `mobile/app/(auth)/verify-email.tsx:9`, `mobile/app/(auth)/two-factor.tsx:9`, `mobile/app/home.tsx:7`).
- **Measured:** mobile auth parser tests pass 5/5 and `npm run typecheck` passes.
- **Measured:** `npm audit --omit=dev` reports 22 production-tree advisories: 7 high, 15 moderate, 0 critical. The direct affected packages include `react-native`, `expo`, `expo-constants`, `expo-dev-client` and `expo-linking`; remediation must be tested through Expo-compatible upgrades, not blind forced updates.
- **Measured:** production-mode Expo export produces approximately 2.72 MB JavaScript bundles for both iOS and Android. These are bundle-size baselines only; they do not establish startup TTI, FPS, render cost, memory behavior or final IPA size.

## Critical blockers

### [CRITICAL] Guidelines 2.1 and 4.2 - App is an incomplete foundation shell

- **Observed:** no registration, password reset, Google login, Sign in with Apple, cashback, wallet, withdrawal, account deletion, legal/support or complete native member navigation exists in `mobile/app/`; email-verification and 2FA continuation routes now exist.
- **Observed:** the home route only confirms an active session and exposes logout (`mobile/app/home.tsx:12`).
- **Required:** do not submit until the agreed native product flows, failure states, accessibility, device screenshots and review account are implemented and verified. This current shell does not establish minimum native utility or visual parity.

### [CRITICAL] Guideline 2.1 - Reviewer backend is unavailable

- **Measured:** `https://mesale.vn/api/v1/openapi/config` returns HTTP 503 with code `API_DISABLED`; the public site and manifest remain HTTP 200.
- **Required:** provide a reviewer-reachable staging or production API, full review credentials, monitoring and rollback coverage for the complete review window. Do not bypass the API feature flag or Blade routes.

### [SOURCE CONFIG REMEDIATED; RELEASE VERIFICATION PENDING] Guidelines 1.6 and 5.1.1 - App Transport Security

- **Observed:** `mobile/app.json` now explicitly sets `NSAppTransportSecurity.NSAllowsArbitraryLoads = false` with an empty exception-domain map (`mobile/app.json:28`).
- **Measured after remediation:** fresh Expo introspection resolves `NSAllowsArbitraryLoads = false` and no ATS exception domains.
- **Observed:** TypeScript rejects non-HTTPS URLs outside development (`mobile/src/config/env.ts:14`), but that application-level guard does not replace an App Transport Security policy.
- **Remaining gate:** inspect the generated production/release `Info.plist` and signed archive before closing this finding. Permit only narrowly justified HTTPS exceptions, if any.

### [CRITICAL] Guideline 5.1.1 - No app privacy manifest

- **Observed:** `mobile/app.json` has no `ios.privacyManifests` configuration (`mobile/app.json:23`).
- **Measured:** Expo introspection reports no app-level privacy manifest. Dependency manifests in `node_modules` do not replace the app's own collected-data and Required Reason API declaration.
- **Required:** inventory app and SDK data access, add the app-level declaration, rerun introspection, inspect the generated `PrivacyInfo.xcprivacy`, and reconcile it with App Privacy answers before closing this finding.

### [CRITICAL] Guidelines 5.1.1(i) and 5.1.1(v) - Privacy, support and deletion are absent

- **Observed:** there are no privacy policy, terms, support or account-deletion routes/screens in `mobile/app/`.
- **Observed:** the Laravel deletion endpoint now avoids raw email/balance audit content and does not convert a committed deletion into HTTP 500 when its audit sink fails. Provider-only accounts remain blocked until server-verifiable OAuth reauthentication proof exists.
- **Required:** add readily discoverable in-app privacy/support/legal links and deletion flow, implement provider reauthentication, verify the public deletion URL, retained-data disclosure and post-deletion token cleanup.

### [CRITICAL BEFORE SOCIAL LOGIN] Guideline 4.8 - Sign in with Apple is not implemented

- **Observed:** neither `expo-apple-authentication` nor a native Google authentication package is declared in dependencies (`mobile/package.json:15`).
- **Reported:** native Google login is required by the approved product scope; therefore an equivalent Sign in with Apple option is also required before that login is exposed on iOS.
- **Required:** implement both exchanges with Laravel server verification and safe account linking. Apple identity must be keyed by verified `sub` and nonce; no Apple private key belongs in the app.

## Warnings and release gaps

- **Metadata/assets:** no source icon, splash or store screenshot assets were found outside generated/dependency directories. `ios.buildNumber` is not in app config; production uses EAS `autoIncrement`, so the resolved build number is **unknown** until a build exists (`mobile/eas.json:16`).
- **Universal links:** a custom `mesale` scheme exists, but there is no `ios.associatedDomains` configuration or verified AASA evidence (`mobile/app.json:7`, `mobile/app.json:23`).
- **Navigation/safe areas:** auth screens now use explicit safe-area insets, keyboard avoidance, scroll recovery and accessibility labels. Physical small-screen, Dynamic Type and platform keyboard behavior remain unverified; the placeholder home is not parity evidence.
- **Contrast:** primary orange `#f97316` with white text is approximately `2.80:1`. The palette remains unchanged to preserve the approved visual reference until the product owner reviews screenshot evidence.
- **Supply chain:** high/moderate npm advisories remain open. Record the exact dependency resolution, update through a compatible Expo SDK path, rerun audit/typecheck/build, and assess whether advisories affect production runtime or build tooling.
- **Test quality:** local Laravel tests report 22 passed/235 assertions, but Linux CI reports 22 warnings/235 assertions. The warning source must be isolated before review-quality evidence is accepted.
- **UGC:** comments/likes/shares are planned but absent. If exposed, filtering, reporting, blocking and a published contact channel are mandatory before submission.

## Positive current evidence

- **Observed:** session Bearer tokens use `expo-secure-store` and `WHEN_UNLOCKED_THIS_DEVICE_ONLY`; no AsyncStorage credential storage is present (`mobile/src/auth/session.ts:1`, `mobile/src/auth/session.ts:47`).
- **Observed:** the API client attaches the Bearer token at request time and clears it on HTTP 401 (`mobile/src/api/client.ts:49`, `mobile/src/api/client.ts:57`).
- **Observed:** production EAS profile is not a development client (`mobile/eas.json:15`).
- **Observed:** no WebView, IAP, advertising, tracking, analytics, crypto or dynamic native-code loading package is declared in `mobile/package.json`.
- **Observed:** marketplace commerce is planned for physical goods; no conflicting digital-payment implementation exists yet.

## Unknown/manual checks

- Apple Team/contract status, certificates, provisioning, export-compliance answers and App Store Connect metadata.
- App Privacy answers, review notes, reviewer account and support response process.
- Generated release `Info.plist`, entitlements, privacy manifest and signed archive contents.
- Physical-device behavior, crashes, startup time, memory, IPv6-only networking and accessibility.
- Final universal-link/AASA response and credential revocation behavior.

## Audit trail

Applied on 2026-08-09: `appstore-review` with the February 6, 2026 Apple guideline reference; `migrate-cashback-to-react-native`; `assess-react-native-migration`; `react-native-best-practices`; and `react-navigation` (React Navigation 7 stack/safe-area guidance). This is a current-state audit, not a store-readiness certification.
