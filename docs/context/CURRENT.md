# Current Migration Context

- Latest checkpoint: `CKP-20260809-012`
- Session: `SES-20260809-003`
- Plan: [migration-plan.md](../migration-plan.md)
- Phase: M0 P0 API safety/auth contract correction and M1 foundation remediation
- Milestone status: M0 open; M1 open and not complete
- Code gate: received
- Push gate: received for the current migration branch; merge is not authorized
- Repository: `thichmmo/mesale-app`, currently a Laravel + Expo monorepo pending explicit scope confirmation (`BLK-SCOPE-001`)
- GitHub default branch: `main` restored to the reviewed migration baseline at `6854dec`
- GitHub branch: `codex/migration-20260809` is pushed with verified code head `8b81b53`, checkpoint commit `9d475f3`, and restored-`main` synchronization commit `3d809e5`
- GitHub PR: draft PR #6 is updated, open and unmerged; push run `31310086133` and PR run `31310087406` both pass context/secret, Laravel, and mobile jobs for `9d475f3`
- GitHub Issues: #12 tracks the bounded P0 correction and remains open for warning cleanup; #13 is the current API blocker candidate while duplicate #1 awaits owner-directed reconciliation; #14-#17 track data lifecycle, financial concurrency, UI contrast, and Linux test warnings
- Operational log: Google Sheet is synchronized through `CKP-20260809-012`, `DEC-20260809-007`, `CHG-20260809-010`, and `TST-20260809-011`
- Production API: `GET /api/v1/openapi/config` returns `503 API_DISABLED`
- PHP verification: portable PHP is available; isolated HTTP auth tests pass
- Dependency status: 22 npm advisories remain (7 high, 15 moderate); no forced audit fix is authorized
- Test-quality status: local Laravel reports 22 passed/235 assertions, while Linux CI succeeds with 22 warnings/235 assertions (`BLK-TEST-001`)
- Store status: App Store and Google Play current-state audits both report `NOT READY`
- Security status: no credentials or production member data are included in repository context documents
- Current tasks: `TSK-MOB-001` (M0 auth/API) and `TSK-MOB-002` (foundation remediation) remain open; `TSK-MOB-007` P0 corrections are implemented and verified

## Verified P0 Corrections

- Recursive redaction covers nested authentication, OTP, bank, token, and payment-account fields.
- Login models authenticated, email-verification-required, or 2FA-required outcomes without persisting a session before a Bearer token exists.
- Withdrawal creation and gift redemption require replay-safe `Idempotency-Key` handling with encrypted stored replay responses.
- Audited member money responses use integer VND while percentages and rates remain decimal.
- Account deletion avoids raw identity/balance logs and does not return a false HTTP 500 after committed deletion; OAuth-only reauthentication remains pending.
- CI runs Laravel tests, mobile parser tests, TypeScript, Expo Doctor, exports, context checks, and secret checks.

## Next Action

Keep M0/M1 open, production API disabled, and both store verdicts at `NOT READY`. Next work must address the Linux PHPUnit warning source, staging/API readiness, Apple/Google OAuth, idempotency retention, database-backed normalized payment-account uniqueness, dependency remediation, and representative iOS/Android device evidence.
