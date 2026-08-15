# Google Play Current-State Audit

Audit date: 2026-08-15
Timezone: Asia/Bangkok
Target: `mobile/`
Branch: `codex/mvp-p0-20260809`
Verdict: **READY TO UPLOAD TO INTERNAL TESTING; NOT READY FOR PRODUCTION ROLLOUT**
Milestone status: **M0 and M1 remain open**

This remains a source/artifact audit, not a Play Console certification. The signed AAB is verified locally; Play-installed device behavior and Console declarations still require evidence.

## Current Evidence

- Expo SDK 53 / React Native 0.79.6 / Expo Router 5 managed app with application ID `vn.mesale.app`.
- Current source base: `8d60594`; mobile `114/114`, TypeScript, and Expo Doctor `18/18` passed.
- Native MVP tabs/screens cover auth, Home, Wallet, Earn, Inbox, Account, orders, balance logs, withdrawals/OTP, payment accounts, referrals, check-in, tasks, gifts, gift codes, notifications, security, sessions, and deletion UI.
- Session Bearer tokens use SecureStore; no personal Bot API key, service account, keystore, or WebView is present in the mobile source.
- Signed production AAB exists for package `vn.mesale.app`, version `0.1.1`, code `2`; size `49,620,794` bytes and SHA-256 `C5DE525AAD341B66552B5E51B3E859A5E5E6CB01723E8149E1F339FFB6CD2DBB`.
- Bundletool, upload signature, API 35 merged manifest, minimal permissions, no debuggable/cleartext opt-in, and 16 KB native-library alignment checks pass.
- Native Google OAuth uses the embedded public Web client, includes Google `SignInHubActivity`, and exchanges the ID token through Laravel. Production config is HTTP `200` with Google OAuth enabled; the invalid-token canary returns HTTP `422` / `OAUTH_CREDENTIAL_INVALID`.
- Public privacy, terms, support, and account-deletion URLs return HTTP `200`.
- `npm audit --omit=dev`: `22` advisories (`7` high, `15` moderate`). Do not use a blind forced fix.

## Blocking Findings

### Broken functionality and reviewer access

The MVP and signed AAB are implemented, and the production Open API responds. It has not yet been verified from a Google Play installation with a real reviewer/test account. Required: Internal Testing install, authenticated Google-to-Laravel Bearer evidence, device regression, reviewer access, monitoring, and rollback readiness.

### Account deletion and privacy

In-app deletion and public legal/deletion/support resources exist and the public URLs return HTTP `200`. Play Console deletion/Data Safety answers, physical-device deletion evidence, retained-data disclosure, and the wider financial-ledger retention/anonymization decision remain open.

### Target API and artifact policy

The AAB targets and compiles API 35, which satisfies the reviewed requirement on 2026-08-15. From 2026-08-31, new submissions and updates must prepare for API 36. Recheck the official requirement immediately before production submission and schedule the Expo/RN upgrade before the deadline.

### Google OAuth on Google Play

The AAB-side implementation is correct, but Google Play signs installed APK splits with the Play App Signing certificate rather than the upload certificate. Google Cloud Console was not authenticated during this audit, so the required Android OAuth client could not be verified externally. Before relying on Google login, confirm an Android client exists for package `vn.mesale.app` plus the Play App Signing SHA-1, then test a real login from Internal Testing. A missing or mismatched client normally produces Google status `10` / `DEVELOPER_ERROR` before Laravel receives a token.

### Data Safety and Financial Features

The app exchanges account identifiers, cashback, orders, wallet balances, referrals, notifications, and withdrawals through Laravel. Complete the Data Safety form from actual API/SDK behavior and review the Financial Features declaration; do not infer Console answers from source assumptions.

## Open Release Gaps

- Signed AAB, version code, merged manifest, upload certificate, and 16 KB compatibility now have local evidence; Play App Signing runtime behavior and staged rollout evidence remain open.
- Android App Links intent filters and `assetlinks.json` groundwork exist in source with a release-certificate fingerprint placeholder; production HTTPS verification and the owner fingerprint remain required.
- The release merged manifest confirms no restricted SMS/Call Log, broad storage/media, location, `QUERY_ALL_PACKAGES`, `MANAGE_EXTERNAL_STORAGE`, camera, overlay, or package-inventory permission.
- Notification permission/push registration is not complete; OTP remains server/email based and does not request SMS permission.
- No physical-device accessibility, edge-to-edge, keyboard, offline/retry, crash, performance, or screenshot regression evidence.
- Google provider identity has a database uniqueness invariant; safe authenticated linking and real Play-installed provider evidence remain open.
- Strict replay preflight, admin password/2FA reset token revocation, and payment-account activity-log masking are implemented and pass local regression coverage; remote CI confirmation remains pending.
- Idempotency replay retention/pruning and real MariaDB payment-account uniqueness/concurrency are unresolved.
- Push transport remains unresolved: the current backend sends FCM HTTP v1 messages and cannot treat a raw iOS APNs token as an FCM token. Choose FCM for both platforms or approve an APNs bridge before enabling push.
- UGC comments/likes/shares require filtering, report, block, and contact mechanisms before exposure.
- Marketplace handoff concerns physical goods and should not use Play Billing. Future digital features require a separate policy review.
- Target audience, content rating, support contact, store metadata, Data Safety, and Financial Features Console answers are unknown.
- Dependency advisories require an Expo-compatible remediation and regression plan.

## Audit Trail

Updated on 2026-08-15 using `playstore-review`, `migrate-cashback-to-react-native`, and `react-native-best-practices` Android 16 KB guidance. Current verdict is **READY FOR INTERNAL TESTING UPLOAD; NOT READY FOR PRODUCTION ROLLOUT**.
