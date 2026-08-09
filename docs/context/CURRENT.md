# Current Migration Context

- Latest checkpoint: `CKP-20260809-010`
- Session: `SES-20260809-002`
- Plan: [migration-plan.md](../migration-plan.md)
- Phase: M0 auth/API contract correction and M1 foundation remediation
- Milestone status: M0 open; M1 open and not complete
- Code gate: received
- Push gate: received for the current migration branch; merge is not authorized
- Repository: `thichmmo/mesale-app`, currently a Laravel + Expo monorepo pending explicit scope confirmation (`BLK-SCOPE-001`)
- GitHub default branch: `main` restored to the reviewed migration baseline at `6854dec`
- GitHub branch: `codex/migration-20260809` is pushed and synchronized with restored `main` through merge commit `3d809e5`
- GitHub PR: draft PR #6 is mergeable with a bounded 33-file diff; checks pass; do not merge automatically
- GitHub Issues: PHP blocker #4 is closed; dependency, scope, OAuth, store, and M1 tracking are Issues #7-#11
- Operational log: Google Sheet is synchronized through `CKP-20260809-010`, `DEC-20260809-006`, `CHG-20260809-009`, and `TST-20260809-010`
- Production API: `GET /api/v1/openapi/config` returns `503 API_DISABLED`
- PHP verification: portable PHP is available; isolated HTTP auth tests pass
- Dependency status: 22 npm advisories remain (7 high, 15 moderate); no forced audit fix is authorized
- Store status: App Store and Google Play current-state audits both report `NOT READY`
- Security status: no credentials or production member data are included in repository context documents
- Current tasks: `TSK-MOB-001` (M0 auth/API) and `TSK-MOB-002` (foundation remediation)

## Corrected Evidence

- Session records validate `expiresAt`; malformed and expired records are removed.
- Session restore calls authenticated `GET /account` and restores the current user; HTTP 401 invalidates the local session.
- Navigation no longer mutates the router during render.
- Login and account wallet responses use integer VND money fields for the corrected contracts.
- Laravel tests exercise real HTTP login, registration, 2FA, feature-flag, Bearer middleware, logout/revocation, account restore, token hashing, and API-log redaction.
- EAS profiles use named environments; no localhost or placeholder staging URL remains in active mobile config.
- ATS and broad Android storage/overlay permissions are remediated in source configuration; release artifact verification remains pending.

## Next Action

Keep M1 open. Resolve the repository-scope decision, stage/API readiness, compatible dependency remediation, OAuth credentials/contracts, and representative iOS/Android device evidence before claiming milestone completion or store readiness.
