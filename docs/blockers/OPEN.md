# Open Blockers

| ID | Priority | Area | Description | Owner | Status |
|---|---|---|---|---|---|
| `BLK-API-001` | P0 | API | Production Open API config returns `503 API_DISABLED`; staging and safe activation plan are required. | Laravel/release | Open |
| `BLK-AUTH-001` | P1 | OAuth | Apple and native Google OAuth server credentials and exchange contracts are not yet supplied. | Product/backend | Open |
| `BLK-STORE-001` | P1 | Store | Account deletion URL, support URL, AASA/Asset Links, review accounts, and store declarations are incomplete. | Release | Open |
| `BLK-GH-001` | P1 | GitHub | CLI token lacks `read:project`; the migration Project cannot be inspected or created yet. | User/GitHub | Open |
| `BLK-DEP-001` | P1 | Dependencies | `npm audit --omit=dev` reports 22 advisories (7 high, 15 moderate); a compatible Expo/RN upgrade and regression plan is required. | Mobile/release | Open |
| `BLK-SCOPE-001` | P1 | Repository | The repository currently contains Laravel and Expo; product-owner confirmation is required before retaining the monorepo or moving to a mobile-only scope. | Product owner | Open |
| `BLK-DATA-001` | P1 | Data lifecycle | Financial idempotency records have no retention/pruning policy; encrypted replay rows will grow without an operational cleanup contract. | Backend/operations | Open |
| `BLK-FIN-001` | P1 | Financial concurrency | Cross-user uniqueness for normalized withdrawal account numbers is not enforced by a database constraint and has not been proven under real MariaDB concurrency. | Backend/database | Open |
| `BLK-UI-001` | P1 | Accessibility/parity | Primary orange `#f97316` with white text measures about `2.80:1`; changing the approved visual palette requires product-owner approval and screenshot evidence. | Product/design | Open |
| `BLK-TEST-001` | P1 | Test quality | Local Laravel tests report 22 passed/235 assertions, but Linux GitHub Actions reports 22 warnings/235 assertions; the truncated warning text includes `file_get_contents` and requires root-cause isolation. | Backend/CI | Open |

GitHub tracking: `BLK-DATA-001` #14, `BLK-FIN-001` #15, `BLK-UI-001` #16, and `BLK-TEST-001` #17. `BLK-API-001` remains duplicated as #1/#13 pending an explicit canonical-issue decision.

No secrets, tokens, raw member records, or private URLs are recorded in this file.
