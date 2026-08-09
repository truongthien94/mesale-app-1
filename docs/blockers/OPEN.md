# Open Blockers

| ID | Priority | Area | Description | Owner | Status |
|---|---|---|---|---|---|
| `BLK-API-001` | P0 | API | Production Open API config returns `503 API_DISABLED`; staging and safe activation plan are required. | Laravel/release | Open |
| `BLK-AUTH-001` | P1 | OAuth | Apple and native Google OAuth server credentials and exchange contracts are not yet supplied. | Product/backend | Open |
| `BLK-STORE-001` | P1 | Store | Account deletion URL, support URL, AASA/Asset Links, review accounts, and store declarations are incomplete. | Release | Open |
| `BLK-GH-001` | P1 | GitHub | CLI token lacks `read:project`; the migration Project cannot be inspected or created yet. | User/GitHub | Open |
| `BLK-DEP-001` | P1 | Dependencies | `npm audit --omit=dev` reports 22 advisories (7 high, 15 moderate); a compatible Expo/RN upgrade and regression plan is required. | Mobile/release | Open |
| `BLK-SCOPE-001` | P1 | Repository | The repository currently contains Laravel and Expo; product-owner confirmation is required before retaining the monorepo or moving to a mobile-only scope. | Product owner | Open |

No secrets, tokens, raw member records, or private URLs are recorded in this file.
