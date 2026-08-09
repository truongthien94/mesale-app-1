# React Native Migration Current-State Assessment

Assessment date: 2026-08-09
Timezone: Asia/Bangkok
Decision confidence: **medium**
Current milestone status: **M0 in progress; M1 is not complete**

## Evidence labels

- **Observed:** repository or command evidence available now.
- **Measured:** locally executed check with a recorded result.
- **Reported:** approved product/migration requirement not yet demonstrated by an app artifact.
- **Unknown:** evidence is unavailable or requires external consoles, credentials, builds or device testing.

## Recommendation and decision boundary

- **Current path:** continue **Path B, greenfield Expo/React Native mobile client**, attached to the existing Laravel API and data model.
- **Why:** no pre-existing production iOS or Android native client was discovered; the recoverable product reference is the Laravel web/member implementation, and the approved architecture keeps all business/financial authority in Laravel (`docs/migration-plan.md:11`, `docs/migration-plan.md:39`).
- **Why brownfield loses:** there are no native hosts to embed into or installed-native-client continuity constraints evidenced in the workspace.
- **Why defer loses:** the user approved implementation and a small mobile foundation now exists; work can continue safely behind API, security and device-evidence gates.
- **Decision boundary:** reassess or fall back only if a required marketplace, authentication, notification, accessibility or release capability cannot be safely supported by Expo config plugins, or if representative device flows fail the parity/performance gates.

## Platform and product inventory

| Client/domain | Evidence status | Current source | Assessment |
|---|---|---|---|
| Laravel website/member app | Observed | Laravel 13/PHP 8.3 project (`composer.json:8`, `composer.json:12`) | Production behavior and visual reference; remains operational. |
| Laravel Open API/Bot API | Observed | `routes/api.php` and `app/Http/Controllers/Api/V1/` | Mobile must use Open API Bearer sessions; Bot personal keys remain separate. Production Open API readiness is still blocked. |
| iOS client | Observed, source only | Shared Expo source, bundle `vn.mesale.app` (`mobile/app.json:25`) | No generated iOS project, archive, device build or App Store evidence. |
| Android client | Observed, source only | Shared Expo source, package `vn.mesale.app` (`mobile/app.json:34`) | No generated Android project, AAB or device build. |
| Existing native iOS/Android apps | Unknown/none discovered | No product-owned `ios/` or `android/` project in the accessible source | Assessment assumes there is no legacy native client; confidence should be revisited if one exists elsewhere. |
| Admin/CMS/cron/import/sync | Observed | Laravel web/backend | Remain web/server-only. |
| Telegram/Zalo Bot clients | Observed | Laravel Bot API/services | Not mobile auth and not bundled into the app. |

## Repository shape and pending product decision

- **Observed layout:** the current Git repository is a de facto monorepo containing Laravel, web assets, API tests, governance docs and `mobile/` (`docs/migration-plan.md:27`, `docs/migration-plan.md:39`).
- **Decision status:** explicit product-owner confirmation of monorepo versus mobile-only long-term ownership is still pending. The current layout is evidence, not authorization to make a permanent repository-structure decision.
- **Scope blocker `BLK-SCOPE-001`:** until that confirmation is recorded, no split, subtree extraction, history rewrite, repository rename or other structural migration is authorized.
- **Interim rule:** continue only path-scoped work inside the existing repository. Keep Laravel and Expo CI/dependency caches/deployment credentials isolated, and do not couple backend deployment to a mobile-only change.
- **Observed Git baseline:** local and remote `main` are restored to the reviewed baseline at `6854dec`. The migration branch includes the normal synchronization commit `3d809e5` and its current pushed code head is `8b81b53`; draft PR #6 remains unmerged.

## Current mobile architecture evidence

- **Observed:** Expo Router is the single public routing layer; its installed dependency graph uses React Navigation 7 native stack and bottom-tab packages. The root currently renders one headerless stack (`mobile/app/_layout.tsx:1`, `mobile/app/_layout.tsx:13`).
- **Observed:** bootstrap, `/login`, `/verify-email`, `/two-factor` and placeholder `/home` behavior exists. The planned Home/Wallet/Earn/Inbox/Account tabs and nested full-screen stacks are not implemented.
- **Observed:** TanStack Query provider exists, but current auth calls use the central fetch client directly (`mobile/app/_layout.tsx:2`, `mobile/src/api/auth.ts:20`).
- **Observed:** Bearer session state uses SecureStore; invalid stored sessions are removed, 401 invalidates the session, and non-401 restore failures keep the token for later retry (`mobile/src/auth/session.ts:27`, `mobile/src/api/client.ts:57`, `mobile/src/auth/AuthProvider.tsx:34`).
- **Observed:** environment values come from EAS named environments; HTTPS is required outside development (`mobile/eas.json:6`, `mobile/src/config/env.ts:14`).
- **Observed:** i18n and theme are only small static skeletons, not parity inventories (`mobile/src/i18n/index.ts:9`, `mobile/src/theme/tokens.ts:1`).

## Navigation and native-layout assessment

- Keep Expo Router as the sole routing surface; do not add a parallel hand-written React Navigation container.
- Add route groups for authenticated tabs and nested stacks. Detail, withdrawal, security and destructive flows belong in parent/native stacks rather than attempts to hide a tab bar from an inner tab screen.
- Auth continuation screens use the shared safe-area/keyboard container and explicit accessibility metadata (`mobile/app/(auth)/login.tsx:5`, `mobile/app/(auth)/verify-email.tsx:63`, `mobile/app/(auth)/two-factor.tsx:74`). Physical small-screen, keyboard, Dynamic Type and screen-reader evidence remains missing; the placeholder home is not parity evidence.

## M1 completion audit

| M1 capability | Status | Evidence/gap |
|---|---|---|
| Expo TypeScript shell | Partial | Project exists and typechecks; no device build. |
| EAS dev/preview/production profiles | Partial | Named environments and the compatible development-client package exist; values, build credentials, and device builds are external/unknown. |
| Typed API client | Partial | Timeout, cancellation, envelope and stale-token-safe 401 handling exist; retry/offline/request-ID tests are absent. |
| Secure token storage | Partial pass | SecureStore is used; physical-device/keychain behavior is unverified. |
| Session restore/logout | Partial | Expiry validation and authenticated `/account` restore exist; Laravel HTTP and auth parser tests pass, but device/session-transition evidence is absent. |
| Remote config/maintenance/version gate | Missing | Production config endpoint is disabled and no bootstrap state is implemented. |
| Theme parity | Missing | Only a minimal hardcoded token set exists. |
| Vietnamese/English i18n | Partial | Six strings only; no server preference or full catalog. |
| Navigation map | Missing | Root stack only; five-tab/member screen architecture absent. |
| Accessibility/safe area/keyboard | Partial | Auth screens implement source handling; device/font-scaling/screen-reader evidence is absent. |
| Tests and screenshots | Partial | Five auth parser tests exist and pass; no screen/integration tests or screenshot baseline exists. |

M1 must remain open until every row has implementation plus iOS/Android evidence for loading, offline, server error, expired session and disabled API states.

## Performance and dependency baseline

- **Measured:** `npm run typecheck` passes.
- **Measured:** `npm audit --omit=dev` reports 22 advisories (7 high, 15 moderate, 0 critical). No automatic audit fix was run.
- **Measured by integration verification:** portable PHP 8.4 reports 22 passed Laravel tests/235 assertions; the focused financial suite reports 9 passed/116 assertions. Linux GitHub Actions passes but classifies the 22 tests as warnings, tracked by `BLK-TEST-001`.
- **Measured:** five mobile auth parser tests, TypeScript and Expo Doctor 18/18 pass. Production-mode Expo export produces approximately 2.72 MB JavaScript bundles for both iOS and Android.
- **Unknown:** cold-start TTI, JS/UI FPS, render commits, memory and final binary/IPA/AAB size; the JavaScript bundle measurements are not runtime or native-artifact evidence. Follow measure -> optimize -> re-measure -> validate.
- **Unknown:** Android 16 KB release compatibility. RN 0.79 provides aligned core native binaries, but third-party libraries and the final APK/AAB have not been checked.
- **Observed:** no long-list screens exist yet. Future order, notification and activity histories must use virtualized lists and be profiled before memoization or state-architecture changes are proposed.

## Current blockers and next checkpoint

1. Production Open API returns `503 API_DISABLED`; staging/API readiness and contract tests remain M0 blockers.
2. Long-term monorepo versus mobile-only ownership is pending product-owner confirmation (`BLK-SCOPE-001`); no restructure is authorized.
3. Google and Apple OAuth exchange, account linking and review credentials are missing.
4. Store-critical privacy, deletion, support, deep-link and generated-manifest work is missing.
5. npm high/moderate advisories require an Expo-compatible remediation decision and regression checks.
6. Financial idempotency retention/pruning and normalized cross-user payment-account uniqueness need production-safe data/database contracts (`BLK-DATA-001`, `BLK-FIN-001`).
7. Linux CI PHPUnit warnings require root-cause isolation before warning-clean release evidence (`BLK-TEST-001`).
8. The approved orange/white action palette is approximately `2.80:1` and requires a product-approved parity/accessibility decision (`BLK-UI-001`).

The next representative checkpoint remains:

1. Login, session restore, logout and expired-session behavior.
2. Cashback link resolution, native product result and marketplace handoff.
3. Wallet/order history plus withdrawal validation without real production money movement.

Each flow requires both platforms, loading/empty/error/offline states, accessibility, screenshots, API tests, and an independent review before migration scope expands.

## Audit trail

Applied on 2026-08-09: `assess-react-native-migration`; `migrate-cashback-to-react-native` plus its project map; `react-navigation` with React Navigation 7 stack, bottom-tab and safe-area references; `react-native-best-practices` including measurement and Android 16 KB guidance; `appstore-review` with the February 2026 Apple reference; and `playstore-review` with the July 2026 Play reference. This report records a migration decision and evidence gaps; it does not claim feature, milestone or store completion.
