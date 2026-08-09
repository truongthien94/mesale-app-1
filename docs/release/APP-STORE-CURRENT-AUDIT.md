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
- Round 3 local evidence is recorded in checkpoint `CKP-20260809-017` and the final checkpoint for this push; prior baseline was Laravel `65` tests / `625` assertions, mobile `30/30` contract tests, TypeScript, and Expo Doctor `18/18`.
- Native MVP routes exist for auth, Home, Wallet, Earn, Inbox, and Account, including orders, wallet logs, withdrawals/OTP, payment accounts, referrals, check-in, tasks, gifts, gift codes, notifications, profile, security, sessions, and deletion UI.
- Session restore validates expiry and restores `/account`; API requests use SecureStore-backed Bearer tokens, clear stale sessions on `401`, and never use member `sk_live_...` API keys.
- Backend Google and Sign in with Apple OAuth exchange/verification is implemented with server-only provider checks, Apple `sub`/JWKS/issuer/audience/expiry/nonce handling, authorization-code exchange, and flags defaulting to OFF. Native provider packages and UI flows are present locally, while owner credentials, staging rollout, and physical-device evidence are missing. Apple-linked deletion requires fresh native Apple reauthentication and server-side grant revocation.
- Local Laravel verification: `60` tests / `606` assertions, zero warnings.
- Mobile contract tests: `30/30` passed; TypeScript passed; Expo Doctor passed `18/18`; iOS/Android exports passed with approximately `3.03 MB` JavaScript per platform.
- No signed IPA, physical-device test, screenshot regression, verified remote CI result for this round, push configuration, owner-approved support contact, production AASA deployment, or App Store Connect metadata is available. Public legal/deletion/support routes and in-app legal links now exist in source.
- Production API read-only check: `GET /api/v1/openapi/config` returns HTTP `503` / `API_DISABLED`; no bypass or production mutation occurred.
- Dependency audit: `22` production-tree advisories (`7` high, `15` moderate); no forced upgrade was attempted.

## Blocking Findings

### Guideline 2.1 - Reviewer backend and complete functionality

The reviewer cannot use the production Open API while it returns `503 API_DISABLED`. The MVP screen surface is implemented locally, but the full member inventory, staging data, review account, API readiness, and production monitoring/rollback are not proven.

Required before submission: reviewer-reachable staging/production service, complete member flows, stable auth/error/pagination contracts, review credentials, and device evidence for loading, empty, validation, server-error, offline, retry, and expired-session states.

### Guideline 4.8 - Sign in with Apple

The backend exchange and native Apple/Google client flows are present, but provider configuration and end-to-end staging/device evidence are missing. If Google login is exposed on iOS, Sign in with Apple is available as an equivalent option. The UI and documents use the name `Sign in with Apple`, never "iCloud login".

### Guidelines 5.1.1(i)/(v) - Privacy, support, and deletion

Account deletion UI and backend confirmation handling exist, including fresh Apple reauthentication and revocation. Public legal/deletion/support routes and in-app links exist in source, but owner-approved content, a verified support response path, staging/device evidence, and retained-data approval remain incomplete. Resolve the wider financial-ledger retention/anonymization contract before enabling deletion for review.

### Guideline 5.1.1 - Privacy Manifest and Required Reason APIs

An Expo app-level privacy manifest and collected-data declarations are configured in source, but no signed archive has verified the generated `PrivacyInfo.xcprivacy`, `Info.plist`, entitlements, or App Store Connect answers. Inventory app and SDK data access, declare Required Reason APIs, inspect the archive, and reconcile the console answers.

### Guidelines 1.6/5.1.1 - Network and permissions

Source config rejects cleartext/non-HTTPS production URLs and disables arbitrary ATS loads. Generated release artifacts must still be inspected for ATS exceptions, permissions, entitlements, notification prompts, and IPv6-only behavior.

## Open Release Gaps

- No Apple Team/Services ID/key, Google client IDs, APNs/Firebase push configuration, EAS production project, provisioning, or review account has been verified.
- AASA and iOS associated-domain configuration exist in source with an Apple Team ID placeholder; production HTTPS/CDN verification and owner-supplied Team ID remain required.
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
