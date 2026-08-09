# Open Blockers

Local and remote evidence at Round 3 checkpoint `CKP-20260809-019`: Laravel `68` tests / `648` assertions, mobile `34/34` contract tests, TypeScript, Expo Doctor `18/18`, Expo export, PHP lint/Pint, secret scan, and GitHub Actions run `31325626880` passed. Apple deletion reauthentication, global saved payout-destination uniqueness, and bounded referral-ledger preservation are resolved locally; production, device, and store evidence remain unclaimed.

| ID | Priority | Area | Description | Owner | Status |
|---|---|---|---|---|---|
| `BLK-API-001` | P0 | API | Production Open API config returns `503 API_DISABLED`; staging contract tests, security review, monitoring, rollback, reviewer access, and explicit activation approval are required. | Laravel/release | Open |
| `BLK-AUTH-001` | P0 | OAuth | Backend Google and Apple verification/exchange is implemented with feature flags OFF, but native client packages, provider credentials, safe account-linking rollout, and staging evidence are missing. | Product/backend | Open |
| `BLK-AUTH-002` | P1 | Identity data | Google provider identity is not protected by a database unique constraint. Perform a read-only duplicate audit and obtain an owner decision before adding a constraint or linking records. GitHub Issue #18. | Backend/database/product | Open |
| `BLK-AUTH-003` | P0 | Apple account deletion | Policy option B is implemented locally: every Apple-linked deletion requires fresh Apple reauthentication, then revokes the grant before local deletion. Staging and physical-device provider evidence remain required. | Backend/product/release | Resolved locally; staging/device pending |
| `BLK-SEC-001` | P1 | Session security | Admin password or 2FA credential reset now revokes the member's mobile API tokens in the local remediation. Keep remote CI and release evidence separate from this local resolution. | Backend/security | Resolved locally; remote pending |
| `BLK-STORE-001` | P0 | Store | Public account-deletion/support/legal routes, privacy manifest/declarations, AASA/Asset Links, and Android App Links groundwork exist locally, but owner-approved content, production association verification, push setup, review accounts, signed artifacts, and store-console evidence are incomplete. | Release | Open |
| `BLK-DEP-001` | P1 | Dependencies | `npm audit --omit=dev` reports 22 advisories (7 high, 15 moderate). Remediate through an Expo-compatible upgrade path with regression evidence; do not use a blind forced fix. | Mobile/release | Open |
| `BLK-DATA-001` | P1 | Data lifecycle | Strict replay preflight and idempotency coverage are implemented, but encrypted replay records still need an owner-approved retention/pruning policy. A 24-hour default is proposed but requires an operational cleanup job. GitHub Issue #14. | Backend/operations/product | Open |
| `BLK-DATA-002` | P0 | Account deletion/financial records | Referral relationships and commissions now survive referrer deletion with the referrer FK anonymized. Wider financial-record retention and disclosure remain an owner/legal decision before production hard-delete is enabled. | Product/legal/backend/database | Partially resolved locally |
| `BLK-FIN-001` | P1 | Financial concurrency | Global hard-block is implemented locally with normalized destination hashes, a database unique constraint, and `ACCOUNT_ALREADY_CLAIMED`. A read-only production duplicate audit and MariaDB deployment/concurrency evidence remain required. | Product/backend/database | Resolved locally; deployment evidence pending |
| `BLK-LOG-001` | P1 | Privacy logging | Payment-account create/delete activity logs are now masked in the local remediation and covered by local regression tests. Keep remote CI/release evidence separate from this local resolution. | Backend/security | Resolved locally; remote pending |
| `BLK-PUSH-001` | P1 | Push transport | The current Laravel push service sends through FCM HTTP v1 and cannot accept a raw iOS APNs token as an FCM registration token. Owner must choose FCM for both platforms or approve an APNs bridge, then supply Firebase/APNs/EAS configuration and tests. | Product/backend/release | Open |
| `BLK-DEVICE-001` | P1 | QA | No physical iOS/Android verification, screenshot regression, font scaling, accessibility, keyboard, offline, retry, performance, or signed-artifact evidence exists. GitHub Issue #20. | Mobile/QA/release | Open |
| `BLK-UI-001` | P1 | Accessibility/parity | Approved orange `#f97316` with white text measures about `2.80:1`; do not change the visual reference without product approval and screenshot evidence. | Product/design | Open |
| `BLK-TEST-001` | P2 | Test quality | Remote CI run `31325626880` passed for commit `62e6678`; device/store evidence remains outside CI. | Backend/CI | Resolved |

Resolved in this checkpoint:

- `BLK-SCOPE-001` is resolved and GitHub Issue #8 is closed: the repository is intentionally a Laravel + Expo monorepo.
- `BLK-TOOL-001` is resolved: portable PHP is available and the Laravel suite runs locally.
- `BLK-SEC-001` is resolved locally: admin password/2FA resets revoke mobile API tokens; remote CI/release evidence remains pending.
- `BLK-LOG-001` is resolved locally: payment-account activity logs mask account numbers; remote CI/release evidence remains pending.
- `BLK-AUTH-003` is resolved locally with Apple deletion policy option B; staging/device evidence remains pending.
- `BLK-FIN-001` is resolved locally at the code/SQLite boundary; production duplicate audit and MariaDB evidence remain pending.
- `BLK-DATA-002` has a bounded local safeguard for referral counterparties; the long-term legal retention policy remains open.

GitHub Issues #18 (`BLK-AUTH-002`), #19 (`TSK-MOB-008`) and #20 (`BLK-DEVICE-001`) were created for this checkpoint. The Project remains unavailable because the CLI token lacks `read:project`. Do not close or claim remote CI evidence without a workflow result or other reproducible evidence.

No secrets, tokens, raw production responses, private URLs, or member records are recorded here.
