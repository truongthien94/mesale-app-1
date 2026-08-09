# Mesale Cashback Mobile

Native React Native/Expo migration of the Mesale Cashback member experience for iOS and Android.

Repository: `https://github.com/thichmmo/mesale-app`

Status: migration in progress. The repository contains the Laravel product and the Expo client foundation. It is not a production release, M1 is not complete, and the production Open API remains disabled.

## Repository State

- Default branch: `main`, restored to the reviewed migration baseline at commit `6854dec`; it is not an empty/orphan branch.
- Migration branch: `codex/migration-20260809`.
- Review: draft Pull Request `#6` from the migration branch to `main`.
- Merge policy: no merge, force-push, history rewrite, production activation, or credential change without explicit approval.
- Current repository shape: a de facto Laravel + Expo monorepo. Long-term ownership as a monorepo versus a mobile-only repository is still a product-owner decision; no restructure is authorized until that decision is explicit.

## Product Architecture

- Laravel and mesale.vn remain the only business backend, database, and source of truth.
- The mobile app is a native Expo/React Native API client. It does not use a WebView and does not read MariaDB directly.
- Production API target: `https://mesale.vn/api/v1/openapi`.
- Development, staging, and production API URLs are supplied by environment/EAS profiles.
- Business data is never copied into a mobile database. Read-only cache is allowed only when it is safe and disposable.
- Admin CMS, cron, scheduler, imports, bot webhooks, synchronization services, and system updates remain Laravel web/server responsibilities.

## Authentication and Credentials

Mobile authentication uses a session Bearer token returned by Laravel:

```text
Login -> Laravel validates credentials -> access_token -> SecureStore -> Authorization: Bearer <access_token>
```

- Canonical response field: `data.access_token`.
- Temporary compatibility alias: `data.token`.
- Token type: `Bearer`.
- Optional expiry: `data.expires_at`.
- iOS storage: Keychain-backed `expo-secure-store`.
- Android storage: Keystore-backed `expo-secure-store`.
- Logout must revoke the session and clear local secure storage.

The personal member API key format `sk_live_...` is a different credential. It is reserved for Bot API/server-to-server integrations and must never be embedded in the mobile app. The mobile app must not use `users.api_token`, `/api/v1/bot`, affiliate credentials, AI keys, Firebase service-account keys, SMTP credentials, Apple private keys, or Android signing keys.

Apple authentication is called **Sign in with Apple**, not iCloud login. Apple and Google credentials are exchanged and verified server-side; they are never stored in the mobile bundle.

## Repository Layout

```text
app/                         Laravel application, services, models, middleware
database/                    migrations, factories, seeders
lang/                        Vietnamese and English translations
resources/                   Blade, CSS, and JavaScript visual reference
routes/                      web, admin, console, and API routes
public/assets/               reviewed source assets only
mobile/                      Expo/React Native app
docs/migration-plan.md       migration architecture and delivery plan
docs/api/                    redacted API audit and contract notes
docs/assessment/             current migration-scope and readiness assessment
docs/inventory/              web-to-native feature matrix
docs/context/                sessions, checkpoints, and current handoff
docs/decisions/              redacted architecture/operations decisions
docs/blockers/               open blocker register
docs/release/                store and release gates
.github/                     issue templates, PR template, context checks
```

Runtime and sensitive areas are intentionally excluded from Git:

- `.env`, `key.json`, private keys, OAuth secrets, keystores, service-account files;
- `vendor/`, `node_modules/`, Laravel `storage/`, mobile build output;
- production uploads, CKFinder, generated HTML, backups, database dumps, and local server configuration.

## Mobile Development

Requirements:

- Node.js and npm compatible with Expo SDK 53;
- PHP 8.3+ or an equivalent portable PHP runtime for isolated Laravel tests;
- Xcode for iOS device/simulator verification;
- Android Studio/emulator for Android verification;
- staging API access before any production business-data test.

From the repository root:

```powershell
cd mobile
npm ci
Copy-Item .env.example .env.local
npm run typecheck
npx expo-doctor
npx expo start
```

Set `EXPO_PUBLIC_API_BASE_URL` in the local environment only. Do not place secrets in `EXPO_PUBLIC_*`; only public configuration such as an API base URL is allowed there.

Useful verification commands:

```powershell
npm run typecheck
npx expo-doctor
npx expo export --platform all --output-dir dist
php artisan test
```

The current foundation includes Expo Router, a compatible Expo development client, named EAS environments, a typed API client, SecureStore session persistence with expiry validation, authenticated `/account` session restoration, login/logout bootstrap, stale-token-safe 401 invalidation, theme tokens, and Vietnamese/English placeholders. Login now models authenticated, email-verification-required, and 2FA-required outcomes; only a valid Bearer outcome is persisted, while the short-lived 2FA challenge remains in memory. The auth screens include safe-area padding, keyboard avoidance, explicit labels, submit behavior, loading/disabled states, and accessibility metadata. This still does not represent the full member feature inventory, and no representative iOS/Android device evidence exists yet.

Current measured foundation evidence:

- Mobile auth contract parser tests pass 5/5.
- TypeScript check passes after the auth continuation changes.
- Expo Doctor passes 18/18 checks after the auth continuation changes.
- The final integrated Expo export passes for iOS and Android with JavaScript bundles of approximately 2.72 MB each.
- The local portable Laravel runner reports 22 passed tests and 235 assertions; the focused financial suite reports 9 passed tests and 116 assertions.
- PHP syntax passes for every changed PHP file, the reviewed PHP set passes Pint, the CI workflow parses as YAML, and `git diff --check` passes.
- A tracked-file secret scan finds no credential filenames or private-key/service-account patterns. No production mutation was performed.
- `npm audit --omit=dev` still reports 22 advisories: 7 high and 15 moderate. Do not apply `npm audit fix --force`; remediation must follow an Expo-compatible upgrade path with regression testing.

GitHub Actions now enforces context/secret checks, Laravel tests, mobile parser tests, TypeScript, Expo Doctor, and iOS/Android exports. Push run `31310086133` and Pull Request run `31310087406` pass all three jobs for checkpoint commit `9d475f3`. The Linux PHPUnit summary nevertheless classifies the 22 tests as warnings while retaining 235 successful assertions; this environment-specific warning state is tracked by `BLK-TEST-001`, while the non-failing Node 20 action deprecations are noted for workflow maintenance.

## Laravel API Readiness

The Open API is protected by its feature flag, throttling, request logging, and Bearer authentication middleware. These controls must not be bypassed.

At the latest audit, `GET /api/v1/openapi/config` returned HTTP `503` with code `API_DISABLED`. This is a P0 readiness blocker. Production activation requires staging contract tests, security review, logging/request IDs, rollback, and explicit operational approval.

Implemented P0 API contract corrections include:

- recursive redaction of nested password, token, challenge, OTP, secret and payment-account fields;
- typed login continuation contracts that do not issue or persist a session before verification/2FA succeeds;
- required replay-safe `Idempotency-Key` handling for withdrawal creation and gift redemption;
- integer VND response fields across audited member money endpoints while rates and percentages remain decimals;
- account-deletion logging that avoids raw email/balance data and does not turn an already committed deletion into HTTP 500 when its audit sink fails.

Remaining M0 API work includes:

- OAuth exchange, provider reauthentication, and safe account-linking contracts, while preserving the canonical password-login response through staging;
- Apple `sub`/JWKS/issuer/audience/expiry/nonce verification;
- native Google credential verification and account linking;
- stable error envelopes and request IDs;
- consistent pagination;
- retention/pruning for stored idempotency records;
- a database-backed normalized payment-account uniqueness/concurrency contract across users;
- rate limits, audit logging, and rollback verification.

The current HTTP-level tests cover login, registration, 2FA token deferral/exchange, the API feature flag, Bearer middleware, logout revocation, invalid credentials, hashed token persistence, recursive sensitive-field redaction, account-deletion safety, integer VND contracts, and withdrawal/gift idempotency replay/conflict behavior. These tests use an isolated in-memory SQLite schema and do not activate or mutate production. Real MariaDB concurrency and cross-user normalized payment-account uniqueness remain unverified.

## Feature Scope

The mobile roadmap preserves member functionality: home, cashback links, product results, marketplace handoff, saved products, coupons, orders, cashback history, wallet, balance/activity logs, withdrawals, payment accounts, referrals, ranking, check-in, tasks, gifts, gift codes, notifications, push, profile, security, sessions, deletion, legal pages, support, blog, comments, likes, and shares.

The complete mapping is in [docs/inventory/feature-matrix.md](docs/inventory/feature-matrix.md). Visual parity is measured against Blade/Tailwind/CSS/assets with screenshot regression across iOS and Android states.

## Milestones

- M0: API contract, staging readiness, Bearer auth, OAuth, security, idempotency.
- M1: Expo shell, theme, i18n, API client, SecureStore, session restore.
- M2: Login, registration, verification, OTP, 2FA, Google, Apple.
- M3: Home, cashback link, product result, marketplace handoff.
- M4: Orders, wallet, cashback history, balance/activity logs.
- M5: Withdrawal, OTP, payment accounts.
- M6: Referrals, check-in, tasks, gifts, gift codes, coupons, ranking.
- M7: Notifications, push, profile, security, sessions, deletion.
- M8: Blog, legal, support, comments, likes, shares.
- M9: Screenshot parity, accessibility, performance, release builds, store audits.

A milestone is not complete with only a happy path. Each requires loading, empty, validation, server error, offline, retry, expired session, disabled feature, screenshot evidence, tests, and iOS/Android verification.

## Logging and Context

Detailed operations are logged in the approved Google Sheet using the tabs and IDs defined in the migration plan. Secrets and member data are never written there.

GitHub Markdown is the redacted, versioned handoff:

- `docs/context/CURRENT.md` points to the latest checkpoint;
- checkpoint and session files preserve decisions and handoffs;
- `docs/blockers/OPEN.md` lists unresolved risks;
- `docs/release/RELEASE-GATES.md` tracks store readiness.

If logs disagree with source or tests, source/test evidence wins and the discrepancy is recorded as a decision.

Operational records use stable IDs and the `Asia/Bangkok` timezone:

- sessions: `SES-YYYYMMDD-NNN`;
- checkpoints: `CKP-YYYYMMDD-NNN`;
- tasks: `TSK-MOB-NNN`;
- decisions: `DEC-YYYYMMDD-NNN`;
- changes: `CHG-YYYYMMDD-NNN`;
- tests: `TST-YYYYMMDD-NNN`;
- blockers: `BLK-GROUP-NNN`;
- release gates: `GATE-IOS/AND/BOTH-NNN`.

The detailed Sheet retains session/task/change/test rows. GitHub keeps only redacted checkpoints, decisions, blockers, and release gates; it must never contain the Sheet URL, local service-account path, secrets, or member data.

## GitHub Workflow

- Use a bounded branch named `codex/migration-YYYYMMDD` for migration work.
- Never force-push, blindly stage, delete history, or overwrite an existing remote.
- Inspect `git status --short`, `git diff --check`, staged paths, tests, builds, and secret scans before pushing.
- Do not commit credentials, tokens, cookies, private keys, raw production responses, or personal member data.
- Issues track tasks, blockers, release risks, and user decisions. Do not close issues without evidence.
- Pull Requests must include Session ID, Checkpoint ID, Task IDs, changed files, tests, blockers, store gates, rollback plan, and a checkpoint link.
- Do not merge into the default branch without explicit user approval.

## Store Compliance

Current source audits are recorded in [docs/release/APP-STORE-CURRENT-AUDIT.md](docs/release/APP-STORE-CURRENT-AUDIT.md) and [docs/release/PLAY-STORE-CURRENT-AUDIT.md](docs/release/PLAY-STORE-CURRENT-AUDIT.md). Both verdicts are `NOT READY`; these are evidence-backed current-state audits, not submission certifications.

Before release, required gates include a reviewer-reachable backend, Sign in with Apple, native Google login, server-verified OAuth reauthentication for deletion, account deletion in-app and on the web, privacy/support URLs, an app Privacy Manifest and Required Reason API inventory, Data Safety, minimal permissions, current Android target API, AAB/Play App Signing, 16 KB page-size compatibility, UGC moderation, accurate cashback claims, and no WebView-only wrapper or dynamic native code loading. ATS and broad Android storage/overlay permissions are remediated in source config, but the generated release artifacts still require inspection. The current orange-on-white primary action contrast is approximately 2.80:1 and remains an approval-dependent visual-parity risk; the palette has not been changed unilaterally.

Marketplace checkout for Shopee, TikTok Shop, and Lazada concerns physical goods and is not an in-app digital purchase. Digital features, subscriptions, vouchers, or content would require a separate StoreKit/Play Billing policy review.

## Current Blockers

- `BLK-API-001`: production Open API is disabled (`503 API_DISABLED`).
- `BLK-AUTH-001`: Apple/Google OAuth server credentials and exchange contracts are pending.
- `BLK-STORE-001`: deletion/support/legal/deep-link store resources are incomplete.
- `BLK-GH-001`: GitHub Project scope is unavailable to the current CLI token.
- `BLK-DEP-001`: 22 npm production-tree advisories require an Expo-compatible remediation decision and regression evidence.
- `BLK-SCOPE-001`: product owner must confirm whether this repository remains a Laravel + Expo monorepo or becomes mobile-only.
- `BLK-DATA-001`: encrypted idempotency replay records need a retention and pruning policy.
- `BLK-FIN-001`: normalized cross-user payment-account uniqueness needs a database-backed MariaDB concurrency contract.
- `BLK-UI-001`: the approved orange/white primary action palette is about `2.80:1` and needs a product decision backed by screenshots.
- `BLK-TEST-001`: Linux CI passes but PHPUnit reports 22 warnings for 235 assertions; the warning source must be isolated and removed before release-quality sign-off.

Residual P0 risks tracked for follow-up include idempotency-record retention/pruning, cross-user normalized payment-account uniqueness under real database concurrency, and the approval-dependent 2.80:1 primary-action contrast. `BLK-TOOL-001` is resolved: portable PHP exists and the Laravel test suite now runs. M1 remains open because device verification, remote config/maintenance behavior, complete navigation/theme/i18n, screenshots, screen/integration tests, and signed-artifact evidence are still missing.

See [docs/blockers/OPEN.md](docs/blockers/OPEN.md) for the maintained register.
