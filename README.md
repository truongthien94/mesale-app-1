# Mesale Cashback Mobile

Native React Native/Expo migration of the Mesale Cashback member experience for iOS and Android.

Repository: `https://github.com/thichmmo/mesale-app`

Status: migration in progress. The current branch contains the Laravel baseline, migration context, and the first Expo foundation. It is not a production release and it does not activate the production Open API.

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
docs/inventory/              web-to-native feature matrix
docs/context/                sessions, checkpoints, and current handoff
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
- an isolated Laravel/PHP environment for backend work;
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
```

The current foundation includes Expo Router, a typed API client, SecureStore session persistence, login/logout bootstrap, 401 session invalidation, theme tokens, and Vietnamese/English placeholders. It does not yet represent the full member feature inventory.

## Laravel API Readiness

The Open API is protected by its feature flag, throttling, request logging, and Bearer authentication middleware. These controls must not be bypassed.

At the latest audit, `GET /api/v1/openapi/config` returned HTTP `503` with code `API_DISABLED`. This is a P0 readiness blocker. Production activation requires staging contract tests, security review, logging/request IDs, rollback, and explicit operational approval.

M0 API work includes:

- canonical auth response and OAuth exchange contracts;
- Apple `sub`/JWKS/issuer/audience/expiry/nonce verification;
- native Google credential verification and account linking;
- stable error envelopes and request IDs;
- consistent pagination;
- integer VND money fields;
- idempotency for retryable reward and financial mutations;
- rate limits, audit logging, and rollback verification.

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

## GitHub Workflow

- Use a bounded branch named `codex/migration-YYYYMMDD` for migration work.
- Never force-push, blindly stage, delete history, or overwrite an existing remote.
- Inspect `git status --short`, `git diff --check`, staged paths, tests, builds, and secret scans before pushing.
- Do not commit credentials, tokens, cookies, private keys, raw production responses, or personal member data.
- Issues track tasks, blockers, release risks, and user decisions. Do not close issues without evidence.
- Pull Requests must include Session ID, Checkpoint ID, Task IDs, changed files, tests, blockers, store gates, rollback plan, and a checkpoint link.
- Do not merge into the default branch without explicit user approval.

## Store Compliance

Before release, complete the App Store and Google Play audits. Required gates include Sign in with Apple, account deletion, privacy/support URLs, Privacy Manifest, Required Reason APIs, Data Safety, minimal permissions, current Android target API, AAB/Play App Signing, 16 KB page-size compatibility, UGC moderation, accurate cashback claims, and no WebView-only wrapper or dynamic native code loading.

Marketplace checkout for Shopee, TikTok Shop, and Lazada concerns physical goods and is not an in-app digital purchase. Digital features, subscriptions, vouchers, or content would require a separate StoreKit/Play Billing policy review.

## Current Blockers

- `BLK-API-001`: production Open API is disabled (`503 API_DISABLED`).
- `BLK-AUTH-001`: Apple/Google OAuth server credentials and exchange contracts are pending.
- `BLK-STORE-001`: deletion/support/legal/deep-link store resources are incomplete.
- `BLK-GH-001`: GitHub Project scope is unavailable to the current CLI token.
- `BLK-GH-002`: the empty remote has no distinct PR base branch yet.
- `BLK-TOOL-001`: PHP CLI is unavailable for Laravel runtime tests.

See [docs/blockers/OPEN.md](docs/blockers/OPEN.md) for the maintained register.
