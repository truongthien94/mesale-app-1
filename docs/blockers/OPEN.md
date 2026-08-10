# Open Blockers

Round 5 checkpoint `CKP-20260810-006`: local Laravel `77` tests / `702` assertions, mobile `37/37`, TypeScript, Expo Doctor `18/18`, iOS/Android exports, lint, diff, and secret checks passed. Commits `72a1f14` and `a9c7659` are published on `main`; CI run `31330756601` passed all jobs. Production API, staging, device, and store evidence remain open.

| ID | Priority | Area | Description | Owner | Status |
|---|---|---|---|---|---|
| `BLK-API-001` | P0 | API | Production Open API config returned HTTP `200` on 2026-08-10; staging contract tests, security review, monitoring, rollback, reviewer access, and explicit activation approval are still required. | Laravel/release | Open |
| `BLK-AUTH-001` | P0 | OAuth | Backend verification/exchange and native Google/Apple source flows are implemented with feature flags OFF, but provider credentials, safe account-linking rollout, staging, and physical-device evidence are missing. | Product/backend | Open |
| `BLK-AUTH-002` | P1 | Identity data | Google provider identity is not protected by a database unique constraint. Perform a read-only duplicate audit and obtain an owner decision before adding a constraint or linking records. GitHub Issue #18. | Backend/database/product | Open |
| `BLK-AUTH-003` | P0 | Apple account deletion | Policy option B is implemented locally: every Apple-linked deletion requires fresh Apple reauthentication, then revokes the grant before local deletion. Staging and physical-device provider evidence remain required. | Backend/product/release | Resolved locally; staging/device pending |
| `BLK-SEC-001` | P1 | Session security | Admin password or 2FA credential reset now revokes the member's mobile API tokens in the local remediation. Keep remote CI and release evidence separate from this local resolution. | Backend/security | Resolved locally; remote pending |
| `BLK-STORE-001` | P0 | Store | Public account-deletion/support/legal routes, privacy manifest/declarations, AASA/Asset Links, and Android App Links groundwork exist locally, but owner-approved content, production association verification, push setup, review accounts, signed artifacts, and store-console evidence are incomplete. | Release | Open |
| `BLK-DEP-001` | P1 | Dependencies | `npm audit --omit=dev` remains 22 advisories (7 high, 15 moderate). No safe SDK 53 fix exists; use the documented Expo 54 -> 55 -> 56 -> 57 path with native/device regression evidence and upstream rechecks. | Mobile/release | Open; upgrade plan ready |
| `BLK-DATA-001` | P1 | Data lifecycle | Completed keys retain 24 hours; stale processing markers retain seven days; bounded daily pruning is implemented and tested. Production deployment, scheduler monitoring, row-count/runtime evidence, and rollback observation remain required. GitHub Issue #14. | Backend/operations/product | Resolved locally; deployment evidence pending |
| `BLK-DATA-002` | P0 | Account deletion/financial records | Referral relationships and commissions now survive referrer deletion with the referrer FK anonymized. Wider financial-record retention and disclosure remain an owner/legal decision before production hard-delete is enabled. | Product/legal/backend/database | Partially resolved locally |
| `BLK-FIN-001` | P1 | Financial concurrency | Global hard-block is implemented locally with normalized destination hashes, a database unique constraint, and `ACCOUNT_ALREADY_CLAIMED`. A read-only production duplicate audit and MariaDB deployment/concurrency evidence remain required. | Product/backend/database | Resolved locally; deployment evidence pending |
| `BLK-LOG-001` | P1 | Privacy logging | Payment-account create/delete activity logs are now masked in the local remediation and covered by local regression tests. Keep remote CI/release evidence separate from this local resolution. | Backend/security | Resolved locally; remote pending |
| `BLK-PUSH-001` | P1 | Push transport | The current Laravel push service sends through FCM HTTP v1 and cannot accept a raw iOS APNs token as an FCM registration token. Owner must choose FCM for both platforms or approve an APNs bridge, then supply Firebase/APNs/EAS configuration and tests. | Product/backend/release | Open |
| `BLK-DEVICE-001` | P1 | QA | No physical iOS/Android verification, screenshot regression, font scaling, accessibility, keyboard, offline, retry, performance, or signed-artifact evidence exists. GitHub Issue #20. | Mobile/QA/release | Open |
| `BLK-UI-001` | P1 | Accessibility/parity | Approved orange `#f97316` with white text measures about `2.80:1`; do not change the visual reference without product approval and screenshot evidence. | Product/design | Open |
| `BLK-HOME-001` | P1 | Home content parity | Coupons and blog are live database-backed homepage blocks without a public mobile JSON contract. Round C uses the observed production snapshot; codes, expiry, view counts, thumbnails, and copy may become stale. GitHub Issue #24. | Product/backend/mobile | Open |
| `BLK-TEST-001` | P2 | Test quality | Remote CI run `31325626880` passed for commit `62e6678`; device/store evidence remains outside CI. | Backend/CI | Resolved |

Resolved in this checkpoint:

- `BLK-SCOPE-001` is resolved and GitHub Issue #8 is closed: the repository is intentionally a Laravel + Expo monorepo.
- `BLK-TOOL-001` is resolved: portable PHP is available and the Laravel suite runs locally.
- `BLK-SEC-001` is resolved locally: admin password/2FA resets revoke mobile API tokens; remote CI/release evidence remains pending.
- `BLK-LOG-001` is resolved locally: payment-account activity logs mask account numbers; remote CI/release evidence remains pending.
- `BLK-AUTH-003` is resolved locally with Apple deletion policy option B; staging/device evidence remains pending.
- `BLK-FIN-001` is resolved locally at the code/SQLite boundary; production duplicate audit and MariaDB evidence remain pending.
- `BLK-DATA-002` has a bounded local safeguard for referral counterparties; the long-term legal retention policy remains open.
- `BLK-DATA-001` is resolved locally at the source/test boundary with configurable bounded pruning; production scheduler evidence remains pending.

GitHub Issues #18 (`BLK-AUTH-002`), #19 (`TSK-MOB-008`) and #20 (`BLK-DEVICE-001`) were created for this checkpoint. The Project remains unavailable because the CLI token lacks `read:project`. Do not close or claim remote CI evidence without a workflow result or other reproducible evidence.

No secrets, tokens, raw production responses, private URLs, or member records are recorded here.
