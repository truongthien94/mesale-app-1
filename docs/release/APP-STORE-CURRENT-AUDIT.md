# App Store Current-State Audit

Audit date: 2026-08-09
Timezone: Asia/Bangkok
Target: `mobile/`
Branch: `codex/mvp-p0-20260809`
Verdict: **NOT READY FOR APP STORE SUBMISSION**
Milestone status: **M0 and M1 remain open**

This is a source/current-state audit, not a submission certification. Evidence marked unknown requires a generated signed artifact, physical device, App Store Connect, or owner credentials.

## Current Evidence

- Expo SDK 53 / React Native 0.79.6 / Expo Router 5 managed app; bundle identifier is `vn.mesale.app`.
- Local evidence source commit: `4fd38f7`; Laravel `60` tests / `606` assertions, mobile `30/30` contract tests, TypeScript typecheck, and Expo Doctor `18/18` passed.
- Native MVP routes exist for auth, Home, Wallet, Earn, Inbox, and Account, including orders, wallet logs, withdrawals/OTP, payment accounts, referrals, check-in, tasks, gifts, gift codes, notifications, profile, security, sessions, and deletion UI.
- Session restore validates expiry and restores `/account`; API requests use SecureStore-backed Bearer tokens, clear stale sessions on `401`, and never use member `sk_live_...` API keys.
- Backend Google and Sign in with Apple OAuth exchange/verification is implemented with server-only provider checks, Apple `sub`/JWKS/issuer/audience/expiry/nonce handling, authorization-code exchange, and flags defaulting to OFF. Native provider packages and owner credentials are not supplied. Apple grant revocation is not yet complete for every permitted deletion-confirmation path.
- Local Laravel verification: `60` tests / `606` assertions, zero warnings.
- Mobile contract tests: `30/30` passed; TypeScript passed; Expo Doctor passed `18/18`; iOS/Android exports passed with approximately `3.03 MB` JavaScript per platform.
- No signed IPA, physical-device test, screenshot regression, remote CI run for this branch, push configuration, public deletion URL, dedicated support URL, AASA, or App Store Connect metadata is available.
- Production API read-only check: `GET /api/v1/openapi/config` returns HTTP `503` / `API_DISABLED`; no bypass or production mutation occurred.
- Dependency audit: `22` production-tree advisories (`7` high, `15` moderate); no forced upgrade was attempted.

## Blocking Findings

### Guideline 2.1 - Reviewer backend and complete functionality

The reviewer cannot use the production Open API while it returns `503 API_DISABLED`. The MVP screen surface is implemented locally, but the full member inventory, staging data, review account, API readiness, and production monitoring/rollback are not proven.

Required before submission: reviewer-reachable staging/production service, complete member flows, stable auth/error/pagination contracts, review credentials, and device evidence for loading, empty, validation, server-error, offline, retry, and expired-session states.

### Guideline 4.8 - Sign in with Apple

The backend exchange is present but native Apple and Google client packages, provider configuration, and end-to-end staging/device evidence are missing. If Google login is exposed on iOS, Sign in with Apple must be available as an equivalent option. The UI and documents must use the name `Sign in with Apple`, never "iCloud login".

### Guidelines 5.1.1(i)/(v) - Privacy, support, and deletion

Account deletion UI and backend confirmation handling exist, but a public deletion resource, provider reauthentication on-device, retained-data explanation, Privacy Policy/Terms/Support links, and a support response path are incomplete. The current hard-delete cascades through financial history/ledger data without an approved retention/anonymization contract. An Apple-linked account confirmed by password or Google can also be deleted locally without guaranteed Apple grant revocation. Resolve both backend contracts before enabling deletion for review.

### Guideline 5.1.1 - Privacy Manifest and Required Reason APIs

No app-level `PrivacyInfo.xcprivacy`/Expo privacy manifest or reconciled App Privacy inventory has been verified in a signed archive. Inventory app and SDK data access, declare Required Reason APIs, inspect generated `Info.plist`/entitlements, and reconcile App Store Connect answers.

### Guidelines 1.6/5.1.1 - Network and permissions

Source config rejects cleartext/non-HTTPS production URLs and disables arbitrary ATS loads. Generated release artifacts must still be inspected for ATS exceptions, permissions, entitlements, notification prompts, and IPv6-only behavior.

## Open Release Gaps

- No Apple Team/Services ID/key, Google client IDs, APNs/Firebase push configuration, EAS production project, provisioning, or review account has been verified.
- No AASA/universal-link configuration or verified response exists.
- No source/device screenshot set covers small iPhone, Pro Max, light/dark, font scaling, keyboard, loading, empty, error, and offline states.
- No startup, FPS, memory, crash, accessibility, or low-end device baseline exists.
- Google provider identity still needs a duplicate audit, safe-linking decision, and database uniqueness strategy before rollout.
- Strict replay preflight, admin password/2FA reset token revocation, and payment-account activity-log masking are implemented and pass local regression coverage; remote CI confirmation remains pending.
- Idempotency replay retention/pruning and real MariaDB payment-account uniqueness/concurrency remain unresolved.
- Push transport remains unresolved: the current backend sends FCM HTTP v1 messages and cannot treat a raw iOS APNs token as an FCM token. Choose FCM for both platforms or approve an APNs bridge before enabling push.
- Orange `#f97316` with white text is approximately `2.80:1`; preserve parity pending product approval and screenshot evidence rather than changing it unilaterally.
- UGC comments/likes/shares require moderation, report, block, and contact mechanisms before exposure.
- Physical marketplace goods are not IAP; any future digital features require a separate StoreKit review.
- `npm audit` advisories need an Expo-compatible remediation plan.

## Audit Trail

Applied on 2026-08-09 using `appstore-review`, `migrate-cashback-to-react-native`, `assess-react-native-migration`, `react-native-best-practices`, and `react-navigation` guidance. Current verdict remains **NOT READY**.
