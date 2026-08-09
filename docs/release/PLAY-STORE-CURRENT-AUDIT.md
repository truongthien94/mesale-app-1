# Google Play Current-State Audit

Audit date: 2026-08-09
Timezone: Asia/Bangkok
Target: `mobile/`
Branch: `codex/mvp-p0-20260809`
Verdict: **NOT READY FOR GOOGLE PLAY SUBMISSION**
Milestone status: **M0 and M1 remain open**

This is a source/current-state audit, not a Play Console certification. Artifact, device, and console answers remain unknown until generated and reviewed.

## Current Evidence

- Expo SDK 53 / React Native 0.79.6 / Expo Router 5 managed app with application ID `vn.mesale.app`.
- Local evidence source commit: `4fd38f7`; Laravel `60` tests / `606` assertions, mobile `30/30` contract tests, TypeScript typecheck, and Expo Doctor `18/18` passed.
- Native MVP tabs/screens cover auth, Home, Wallet, Earn, Inbox, Account, orders, balance logs, withdrawals/OTP, payment accounts, referrals, check-in, tasks, gifts, gift codes, notifications, security, sessions, and deletion UI.
- Session Bearer tokens use SecureStore; no personal Bot API key, service account, keystore, or WebView is present in the mobile source.
- Backend Google/Apple OAuth verification and exchange are implemented, but native provider packages, owner credentials, feature-flag rollout, safe identity-linking/uniqueness evidence, and staging/device evidence are pending. Apple grant revocation is not yet complete for every deletion-confirmation path.
- Local Laravel: `60` tests / `606` assertions with zero warnings. Mobile contracts `30/30`, TypeScript, Expo Doctor `18/18`, and iOS/Android exports pass. Export bundles are approximately `3.03 MB` per platform, not signed AAB evidence.
- Production API config remains HTTP `503` / `API_DISABLED`; no production activation or mutation occurred.
- `npm audit --omit=dev`: `22` advisories (`7` high, `15` moderate`). Do not use a blind forced fix.

## Blocking Findings

### Broken functionality and reviewer access

The MVP is implemented in source but has not been verified on physical Android devices, in a signed release, or against a reviewer-reachable API. Production Open API is disabled. Required: staging/production API with auth, throttle, request logging, monitoring, rollback, and review credentials.

### Account deletion and privacy

In-app deletion UI and backend confirmation controls exist, but a public web deletion resource, provider reauthentication/disconnect evidence, retained-data disclosure, Privacy Policy/Support links, and Play Console deletion/Data Safety evidence are missing. Current hard-delete behavior cascades through financial history/ledger records without an approved retention/anonymization design, and password/Google-confirmed deletion does not guarantee Apple grant revocation for an Apple-linked account.

### Target API and artifact policy

No production AAB or generated merged manifest exists. On 2026-08-09, API 35 is the current new-app/update floor in the reviewed policy snapshot; from 2026-08-31, new apps and updates must be prepared for API 36. Recheck the official deadline immediately before submission, inspect `targetSdkVersion`, and build an AAB with current Expo/RN support.

### Data Safety and Financial Features

The app exchanges account identifiers, cashback, orders, wallet balances, referrals, notifications, and withdrawals through Laravel. Complete the Data Safety form from actual API/SDK behavior and review the Financial Features declaration; do not infer Console answers from source assumptions.

## Open Release Gaps

- No signed AAB, Play App Signing, version code, production merged manifest, or staged-rollout evidence.
- Android 16 KB page-size compatibility is unknown until every native `.so` in the release AAB/APK is inspected.
- No verified `assetlinks.json` or Android App Links configuration exists.
- Effective permission removals are configured in source, but the release merged manifest must confirm no restricted SMS/Call Log, storage, location, `QUERY_ALL_PACKAGES`, `MANAGE_EXTERNAL_STORAGE`, overlay, or dynamic native-code behavior.
- Notification permission/push registration is not complete; OTP remains server/email based and does not request SMS permission.
- No physical-device accessibility, edge-to-edge, keyboard, offline/retry, crash, performance, or screenshot regression evidence.
- Google provider identity needs a duplicate audit, safe-linking decision, and database uniqueness strategy before production OAuth rollout.
- Strict replay preflight, admin password/2FA reset token revocation, and payment-account activity-log masking are implemented and pass local regression coverage; remote CI confirmation remains pending.
- Idempotency replay retention/pruning and real MariaDB payment-account uniqueness/concurrency are unresolved.
- Push transport remains unresolved: the current backend sends FCM HTTP v1 messages and cannot treat a raw iOS APNs token as an FCM token. Choose FCM for both platforms or approve an APNs bridge before enabling push.
- UGC comments/likes/shares require filtering, report, block, and contact mechanisms before exposure.
- Marketplace handoff concerns physical goods and should not use Play Billing. Future digital features require a separate policy review.
- Target audience, content rating, support contact, store metadata, Data Safety, and Financial Features Console answers are unknown.
- Dependency advisories require an Expo-compatible remediation and regression plan.

## Audit Trail

Applied on 2026-08-09 using `playstore-review`, `migrate-cashback-to-react-native`, `assess-react-native-migration`, `react-native-best-practices` (including Android 16 KB guidance), and `react-navigation` safe-area guidance. Current verdict remains **NOT READY**.
