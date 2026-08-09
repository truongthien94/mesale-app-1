# Current Migration Context

- Latest checkpoint: `CKP-20260809-014`
- Session: `SES-20260809-004`
- Plan: [migration-plan.md](../migration-plan.md)
- Phase: M0 P0 remediation and M1 MVP foundation
- Milestone status: M0 open; M1 open and not complete
- Code gate: received
- Push gate: not requested for this checkpoint
- Repository: `thichmmo/mesale-app`, intentionally maintained as a Laravel + Expo monorepo
- Branch: `codex/mvp-p0-20260809`
- Source commit: `4fd38f7` (local verification target; not pushed)
- Production API: `GET /api/v1/openapi/config` returns `503 API_DISABLED`; no production activation was performed

## Verified Current State

- Local Laravel portable PHP run: `60` tests / `606` assertions, zero warnings.
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
- `BLK-AUTH-003`: Apple grant revocation strategy for every account-deletion confirmation path.
- `BLK-AUTH-002`: Google provider identity lacks a database unique constraint; duplicate audit and owner decision are required.
- `BLK-DATA-001`: idempotency retention/pruning; proposed 24-hour policy is unapproved.
- `BLK-DATA-002`: financial ledger retention/anonymization must be approved before production account hard-delete.
- `BLK-FIN-001`: owner decision required for normalized payment-account uniqueness scope (global versus per payment method), then MariaDB concurrency proof.
- `BLK-PUSH-001`: choose FCM-for-both-platforms or an APNs bridge, then provide transport, credentials, EAS configuration, and device tests.
- `BLK-STORE-001`: deletion/support/legal URLs, privacy manifest and declarations, deep links, push, review accounts, signed artifacts, and store-console evidence.
- `BLK-DEP-001`: Expo-compatible remediation for 22 npm advisories.
- `BLK-DEVICE-001`: physical iOS/Android, screenshot regression, accessibility, offline, keyboard, and performance evidence.
- `BLK-UI-001`: orange/white primary action contrast is approximately `2.80:1`; product decision requires screenshot evidence.
- `BLK-TEST-001`: resolved locally; remote CI confirmation awaits the next authorized push and must not be inferred here.

## Handoff

Keep M0/M1 open and store verdicts at `NOT READY`. Next work is staging/API readiness, OAuth owner inputs, Apple grant-revocation and identity-linking decisions, financial-ledger retention/anonymization, idempotency retention, payment-account uniqueness, push transport/configuration, dependency remediation, then device/screenshot and signed-artifact verification. Source and tests are authoritative if any log disagrees.

No secrets, service-account information, raw HTTP responses, or member data are included in this context.
