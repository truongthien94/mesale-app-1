# Mesale Cashback Mobile

Mesale is a Laravel + Expo monorepo for the native member app migration. Laravel and `mesale.vn` remain the business backend, database, and source of truth; `mobile/` is a native React Native/Expo API client for iOS and Android.

## Current Status

- Branch under review: `codex/mvp-p0-20260809`.
- Source commit: `4fd38f7` (local verification target; not pushed).
- Scope decision: this repository intentionally remains a Laravel + Expo monorepo. `BLK-SCOPE-001` is resolved.
- M0 (API readiness/security) and M1 (foundation) are still open. The implementation is not a release candidate.
- Production Open API is intentionally disabled: `GET https://mesale.vn/api/v1/openapi/config` currently returns HTTP `503` / `API_DISABLED`.
- Local Laravel verification: `60` tests and `606` assertions, with no test warnings in the local portable PHP run.
- Mobile verification: `30/30` contract tests, TypeScript typecheck, Expo Doctor `18/18`, and iOS/Android Expo exports passed. Export JavaScript bundles are approximately `3.03 MB` per platform; this is not signed-build or device evidence.
- `npm audit --omit=dev` reports `22` production-tree advisories (`7` high, `15` moderate). Do not use `npm audit fix --force` without an Expo-compatible upgrade plan.

The current verdict for both stores is **NOT READY**. No production activation, production migration, signed AAB/IPA, physical-device screenshot regression, native OAuth client integration, push configuration, public account-deletion resource, or dedicated support URL has been completed.

## Architecture and Data Rules

- Laravel/mesale.vn is the only business backend and database. Mobile never reads MariaDB and never creates a business database.
- Mobile calls `https://mesale.vn/api/v1/openapi` (or an environment-specific development/staging URL) over HTTPS, through Laravel API middleware, throttling, request logging, and Bearer authentication.
- Login returns a session-scoped Bearer access token. The token is stored only in iOS Keychain/Android Keystore-backed `expo-secure-store` and attached as `Authorization: Bearer <token>`. Logout requests server-side revocation and clears local state; a `401` clears the matching stale local session without claiming a new server-side revocation.
- A member API key such as `sk_live_...` is a separate Bot API/server-to-server credential. It must never be embedded in the app, repository, issue, log, screenshot, or bundle.
- `openapi_status` is an API enable/disable flag, not an API key. A `503 API_DISABLED` response is a readiness blocker and must not be bypassed.
- Apple login is **Sign in with Apple**, not iCloud login. Apple/Google credentials are verified by Laravel; provider secrets remain server-only.
- Financial mutations are server-authoritative and use replay-safe idempotency where implemented. Currency amounts are represented as integer VND in audited API responses.
- Admin CMS, cron, scheduler, imports, bot webhooks, synchronization services, and system updates remain Laravel web/server responsibilities.

## Repository Layout

```text
app/                         Laravel application and API services
database/                    migrations, factories, seeders
lang/                        Vietnamese and English translations
resources/                   Blade, CSS, JavaScript visual reference
routes/                      web, admin, console, and API routes
public/assets/               reviewed source assets
mobile/                      Expo Router React Native app
docs/migration-plan.md       approved migration plan
docs/inventory/              web-to-native feature matrix
docs/context/                redacted sessions and checkpoints
docs/decisions/              redacted architecture decisions
docs/blockers/               open blocker register
docs/release/                store and release audits
.github/                     issue templates, PR template, context checks
```

Credentials and member data are intentionally excluded from Git. Never commit `.env`, service-account files, private keys, OAuth secrets, cookies, access tokens, Apple `.p8`, Android keystores, raw production responses, wallet/order records, or personal member data.

## Implemented MVP Surface

The current native surface includes:

- Authentication: email/password login and registration, password reset, email verification, OTP/2FA continuation, session restore, and logout.
- Tabs: Home, Wallet, Earn, Inbox, and Account.
- Home: `/account` bootstrap, config/banner loading, cashback link creation, native product results, and safe external marketplace handoff.
- Wallet: orders and order detail, balance logs, withdrawals with OTP, and payment-account list/create/set-default/delete operations.
- Earn: referrals (F1/F2), daily check-in, tasks/sync/claim/submit, gifts/redemption/history, and gift-code redemption.
- Inbox: notification pagination, filtering, read, and read-all.
- Account: dashboard, profile, password, preferences, security/2FA, sessions/revocation, and account-deletion UI.

Native Google and Sign in with Apple client packages, push notifications, blog/legal/support screens, and complete store-review resources are still pending. The full member inventory and website-to-native mapping are maintained in [docs/inventory/feature-matrix.md](docs/inventory/feature-matrix.md).

The strict replay preflight, payment-account activity-log masking, and admin password/2FA reset token revocation are implemented and pass local regression coverage. Remote CI confirmation remains pending the next authorized push. Ledger retention/anonymization, Apple grant revocation for every deletion path, provider identity uniqueness/linking, payment-account uniqueness/concurrency, and push token transport remain open blockers.

## Development

Requirements include Node/npm compatible with Expo SDK 53 and PHP 8.3+ (a portable PHP runtime may be used for isolated tests). Use a staging API before any business-data verification.

```powershell
cd mobile
npm ci
Copy-Item .env.example .env.local
npm run typecheck
npm test
npx expo-doctor
npx expo start
```

Only public configuration such as `EXPO_PUBLIC_API_BASE_URL` belongs in local Expo environment files. Never place secrets in `EXPO_PUBLIC_*` variables.

Useful checks from the repository root:

```powershell
php artisan test
git diff --check
```

Production EAS builds, device verification, screenshots, signing, and store submission require the relevant owner credentials and release approvals. Do not enable the production API or run production migrations from local development.

## Milestones and Completion Gate

- M0: API contract, staging/Open API readiness, Bearer auth, OAuth exchange, error envelope, pagination, integer VND, idempotency, logging, rollback.
- M1: Expo shell, theme, i18n, API client, SecureStore, session restore.
- M2-M8: auth providers and verification; home/cashback; wallet/withdrawal; earn/referrals/tasks/gifts; notifications/profile/deletion; blog/legal/support/UGC.
- M9: screenshot parity, accessibility, performance, release builds, App Store and Google Play audits.

Each milestone needs happy, loading, empty, validation, server-error, offline, retry, expired-session, disabled-feature, screenshot, test, and iOS/Android evidence. A passing export alone does not complete a milestone.

## Context and GitHub Workflow

Source code, tests, diffs, and pull requests are authoritative. Google Sheet is the detailed operational log; GitHub Markdown is the redacted, versioned handoff; Issues/Project track tasks, blockers, milestones, and release risks. If logs disagree with source/tests, source/tests win and the discrepancy is recorded.

Stable IDs use `SES-`, `CKP-`, `TSK-MOB-`, `DEC-`, `CHG-`, `TST-`, `BLK-`, and `GATE-` prefixes in `Asia/Bangkok` time. Update [docs/context/CURRENT.md](docs/context/CURRENT.md), append a checkpoint at session/milestone/blocker handoff, and keep secrets out of all logs.

The push gate is separate from the code gate. Never push, merge, force-push, or rewrite history without the exact owner approval `ĐỒNG Ý PUSH GITHUB`. Before an approved push, inspect staged paths, run tests/builds/secret scan, and include session/checkpoint/task IDs, blockers, release gates, and rollback details in the PR.

## Release Verdict

See [docs/release/APP-STORE-CURRENT-AUDIT.md](docs/release/APP-STORE-CURRENT-AUDIT.md), [docs/release/PLAY-STORE-CURRENT-AUDIT.md](docs/release/PLAY-STORE-CURRENT-AUDIT.md), and [docs/release/RELEASE-GATES.md](docs/release/RELEASE-GATES.md). Both store audits are current-state assessments and remain **NOT READY** until reviewer-accessible API/staging, native OAuth, account deletion and public support/legal resources, privacy/data declarations, deep links, signed artifacts, 16 KB verification, device evidence, and release approvals exist.
