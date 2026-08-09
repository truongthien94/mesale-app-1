# Current Migration Context

- Latest checkpoint: `CKP-20260809-015`
- Session: `SES-20260809-005`
- Plan: [migration-plan.md](../migration-plan.md)
- Phase: M0 P0 remediation and M1 MVP foundation
- Milestone status: M0 open; M1 open and not complete
- Code gate: received
- Push gate: not received for Round 2; local commits only
- Repository: `thichmmo/mesale-app`, intentionally maintained as a Laravel + Expo monorepo
- Branch: `codex/mvp-p0-20260809`
- Source commit: `e916d58` (local Round 2 implementation; not pushed)
- Production API: `GET /api/v1/openapi/config` returns `503 API_DISABLED`; no production activation was performed

## Verified Current State

- Local Laravel portable PHP run: `65` tests / `625` assertions.
- Apple-linked deletion now uses policy option B: fresh Sign in with Apple reauthentication is mandatory before deletion, so the transient refresh token can be revoked before the local transaction. Password-only deletion returns `APPLE_REAUTH_REQUIRED_FOR_DELETION`.
- Saved payout destinations are normalized into a SHA-256 destination identity and globally hard-blocked by a database unique constraint; cross-user attempts return `ACCOUNT_ALREADY_CLAIMED`.
- Deleting a referrer now nulls only `referrer_id` in `referrals` and `referral_commissions`; relationship and commission amounts remain queryable for the surviving referred member. No denormalized referrer PII exists in those tables.
- Both new migrations passed a clean SQLite test migration and two-step rollback. Task-specific Apple, payment-account, and referral-ledger regressions passed individually and in the full suite.
- Redacted operational sync was verified for `SES-20260809-005`, `CKP-20260809-015`, `TSK-MOB-009`, `DEC-20260809-009`, `CHG-20260809-013`, `TST-20260809-014`, `BLK-AUTH-003`, `BLK-FIN-001`, `BLK-DATA-002`, and `GATE-BOTH-005`; each ID occurs exactly once. No Sheet location, service-account path, credentials, or member data is recorded here.
- Mobile contract tests: `30/30` passed.
- TypeScript typecheck: passed.
- Expo Doctor: `18/18` passed.
- iOS and Android Expo exports: passed; approximately `3.03 MB` JavaScript per platform.
- Strict idempotency replay preflight, payment-account activity-log masking, and admin password/2FA reset token revocation: resolved locally and covered by local regression tests.
- Backend native Google and Apple OAuth verification/exchange is implemented, but feature flags are OFF and native mobile provider packages/owner credentials are not yet supplied.
- Expo MVP tabs/screens cover auth, home/cashback, wallet/orders/balance/withdrawal/payment accounts, earn/referrals/check-in/tasks/gifts/gift code, inbox notifications, and account/security/sessions/deletion UI.
- SecureStore session expiry validation, `/account` restore, 401 invalidation, logout/account-switch cache clearing, idempotency-key retry stability, and native external handoff/share are implemented.
- `npm audit --omit=dev`: `22` advisories (`7` high, `15` moderate); no blind forced fix is authorized.
- No physical-device screenshots, signed IPA/AAB, remote CI evidence for this branch, push configuration, public deletion/support resources, AASA/Asset Links, or performance baseline exists.
- Redacted operational sync evidence was verified via API for `SES-20260809-004`, `CKP-20260809-014`, `TSK-MOB-008`, `DEC-20260809-008`, `CHG-20260809-012`, `TST-20260809-013`, `BLK-AUTH-003`, `BLK-DATA-002`, `BLK-PUSH-001`, and `GATE-BOTH-004`; each returned count `1`. No Sheet URL/ID, key path, or content is recorded.

## Open Blockers

- `BLK-API-001`: production Open API remains `503 API_DISABLED`; staging and production readiness, review access, activation approval, monitoring, and rollback.
- `BLK-AUTH-001`: native Apple/Google client packages, provider credentials, safe account linking, and rollout evidence.
- `BLK-AUTH-003`: resolved locally with mandatory fresh Apple reauthentication for every Apple-linked deletion; native-device and staging provider evidence remain pending.
- `BLK-AUTH-002`: Google provider identity lacks a database unique constraint; duplicate audit and owner decision are required.
- `BLK-DATA-001`: idempotency retention/pruning; proposed 24-hour policy is unapproved.
- `BLK-DATA-002`: bounded referral-ledger preservation is resolved locally, but long-term retention/anonymization for the wider financial ledger remains an owner/legal decision before production activation.
- `BLK-FIN-001`: global hard-block policy and database constraint are resolved locally; production duplicate audit and MariaDB deployment/concurrency evidence remain pending.
- `BLK-PUSH-001`: choose FCM-for-both-platforms or an APNs bridge, then provide transport, credentials, EAS configuration, and device tests.
- `BLK-STORE-001`: deletion/support/legal URLs, privacy manifest and declarations, deep links, push, review accounts, signed artifacts, and store-console evidence.
- `BLK-DEP-001`: Expo-compatible remediation for 22 npm advisories.
- `BLK-DEVICE-001`: physical iOS/Android, screenshot regression, accessibility, offline, keyboard, and performance evidence.
- `BLK-UI-001`: orange/white primary action contrast is approximately `2.80:1`; product decision requires screenshot evidence.
- `BLK-TEST-001`: resolved locally; remote CI confirmation awaits the next authorized push and must not be inferred here.

## Handoff

Keep M0/M1 open and store verdicts at `NOT READY`. Next work is staging/API readiness, OAuth owner inputs/device evidence, Google identity uniqueness, the owner/legal long-term ledger policy, idempotency retention, production duplicate audit for payout destinations, push transport/configuration, dependency remediation, then device/screenshot and signed-artifact verification. Source and tests are authoritative if any log disagrees.

No secrets, service-account information, raw HTTP responses, or member data are included in this context.
