# Open Blockers

Local evidence at source commit `4fd38f7`: Laravel `60` tests / `606` assertions, mobile `30/30` contract tests, TypeScript typecheck, and Expo Doctor `18/18` passed. Strict idempotency replay preflight, payment-account activity-log masking, and admin password/2FA reset token revocation are resolved locally; remote CI, production, device, and store evidence remain unclaimed.

| ID | Priority | Area | Description | Owner | Status |
|---|---|---|---|---|---|
| `BLK-API-001` | P0 | API | Production Open API config returns `503 API_DISABLED`; staging contract tests, security review, monitoring, rollback, reviewer access, and explicit activation approval are required. | Laravel/release | Open |
| `BLK-AUTH-001` | P0 | OAuth | Backend Google and Apple verification/exchange is implemented with feature flags OFF, but native client packages, provider credentials, safe account-linking rollout, and staging evidence are missing. | Product/backend | Open |
| `BLK-AUTH-002` | P1 | Identity data | Google provider identity is not protected by a database unique constraint. Perform a read-only duplicate audit and obtain an owner decision before adding a constraint or linking records. GitHub Issue #18. | Backend/database/product | Open |
| `BLK-AUTH-003` | P0 | Apple account deletion | Apple grant revocation currently depends on an Apple reauthentication refresh token. A password- or Google-confirmed deletion of an Apple-linked account can hard-delete locally without revoking the Apple grant. Define and test a provider-disconnect contract before release. | Backend/product/release | Open |
| `BLK-SEC-001` | P1 | Session security | Admin password or 2FA credential reset now revokes the member's mobile API tokens in the local remediation. Keep remote CI and release evidence separate from this local resolution. | Backend/security | Resolved locally; remote pending |
| `BLK-STORE-001` | P0 | Store | Public account-deletion and support resources, privacy manifest/declarations, AASA/Asset Links, push setup, review accounts, signed artifacts, and store-console evidence are incomplete. | Release | Open |
| `BLK-DEP-001` | P1 | Dependencies | `npm audit --omit=dev` reports 22 advisories (7 high, 15 moderate). Remediate through an Expo-compatible upgrade path with regression evidence; do not use a blind forced fix. | Mobile/release | Open |
| `BLK-DATA-001` | P1 | Data lifecycle | Strict replay preflight and idempotency coverage are implemented, but encrypted replay records still need an owner-approved retention/pruning policy. A 24-hour default is proposed but requires an operational cleanup job. GitHub Issue #14. | Backend/operations/product | Open |
| `BLK-DATA-002` | P0 | Account deletion/financial records | Account deletion currently hard-deletes the user and cascade-linked cashback, withdrawal, referral-commission, and balance-ledger records. Approve legal/business retention and an anonymization design before enabling production hard-delete. | Product/legal/backend/database | Open |
| `BLK-FIN-001` | P1 | Financial concurrency | Owner must decide whether normalized payment-account uniqueness is global across users or scoped per payment method; then prove the chosen contract under real MariaDB concurrency and add the appropriate constraint. | Product/backend/database | Open |
| `BLK-LOG-001` | P1 | Privacy logging | Payment-account create/delete activity logs are now masked in the local remediation and covered by local regression tests. Keep remote CI/release evidence separate from this local resolution. | Backend/security | Resolved locally; remote pending |
| `BLK-PUSH-001` | P1 | Push transport | The current Laravel push service sends through FCM HTTP v1 and cannot accept a raw iOS APNs token as an FCM registration token. Owner must choose FCM for both platforms or approve an APNs bridge, then supply Firebase/APNs/EAS configuration and tests. | Product/backend/release | Open |
| `BLK-DEVICE-001` | P1 | QA | No physical iOS/Android verification, screenshot regression, font scaling, accessibility, keyboard, offline, retry, performance, or signed-artifact evidence exists. GitHub Issue #20. | Mobile/QA/release | Open |
| `BLK-UI-001` | P1 | Accessibility/parity | Approved orange `#f97316` with white text measures about `2.80:1`; do not change the visual reference without product approval and screenshot evidence. | Product/design | Open |
| `BLK-TEST-001` | P2 | Test quality | Resolved locally: portable PHP reports 60 tests/606 assertions with zero warnings. Remote CI confirmation is pending the next authorized push; no GitHub run is claimed in this checkpoint. | Backend/CI | Resolved locally; remote pending |

Resolved in this checkpoint:

- `BLK-SCOPE-001` is resolved and GitHub Issue #8 is closed: the repository is intentionally a Laravel + Expo monorepo.
- `BLK-TOOL-001` is resolved: portable PHP is available and the Laravel suite runs locally.
- `BLK-SEC-001` is resolved locally: admin password/2FA resets revoke mobile API tokens; remote CI/release evidence remains pending.
- `BLK-LOG-001` is resolved locally: payment-account activity logs mask account numbers; remote CI/release evidence remains pending.

GitHub Issues #18 (`BLK-AUTH-002`), #19 (`TSK-MOB-008`) and #20 (`BLK-DEVICE-001`) were created for this checkpoint. The Project remains unavailable because the CLI token lacks `read:project`. Do not close or claim remote CI evidence without a workflow result or other reproducible evidence.

No secrets, tokens, raw production responses, private URLs, or member records are recorded here.
