# Mesale Mobile Migration Plan

Status: approved to begin implementation after the explicit `ĐỒNG Ý CODE` gate.
Plan version: 1.0
Date: 2026-08-09
Timezone: Asia/Bangkok
Target repository: `https://github.com/thichmmo/mesale-app`

## Operating Rules

- Laravel and mesale.vn remain the only business backend and database source of truth.
- The mobile app is a native Expo/React Native API client; it must not be a WebView wrapper or read MariaDB directly.
- Mobile authentication uses only a session Bearer access token returned by Laravel after login.
- Personal member API keys (`sk_live_...`) remain server-to-server Bot API credentials and must never be embedded in the app.
- Production, staging, and development API base URLs are configured per EAS profile; business logic must not hardcode production URLs.
- No production data, credentials, cookies, tokens, private keys, OAuth secrets, keystores, raw member data, or sensitive HTTP responses may enter GitHub, logs, screenshots, issues, PRs, or the mobile bundle.
- `openapi_status` is an API enable/disable switch, not an API key. A production `503 API_DISABLED` response is a release blocker.

## Source of Truth and Governance

1. Source code, tests, diffs, and pull requests are authoritative for implementation.
2. Google Sheet is the detailed operational log and context recovery journal.
3. GitHub Markdown stores redacted, versioned technical checkpoints and decisions.
4. GitHub Issues/Project tracks tasks, blockers, milestones, and release risk.
5. If logs conflict with source or tests, source/test evidence wins and the discrepancy becomes a recorded decision.

Before repository changes, verify the local repository, remote, default branch, status, and recent history. Do not force-push, delete/overwrite existing history, blindly stage files, or alter a different remote without explicit confirmation. The confirmed working directory is `C:\Users\ThichMMO\Desktop\cashback`; the intended GitHub remote is `https://github.com/thichmmo/mesale-app`.

## Current Evidence and Blockers

- Live home and manifest respond successfully.
- `https://mesale.vn/api/v1/openapi/config` currently responds with `503 API_DISABLED`; Open API staging and production readiness are mandatory M0 work.
- Audited auth responses used only `token`; local M0 code now adds canonical `access_token`, a temporary equal `token` alias, and `expires_at`, pending staging/deployment verification.
- Native Apple OAuth exchange, native Google OAuth exchange, idempotency for retryable financial mutations, complete home/dashboard data, blog JSON, language/currency preference APIs, bot unlink, integer-money normalization, account-deletion web resource, support URL, AASA, and Asset Links remain API or release gaps.
- Public legal/privacy/support and account-deletion resources must be verified before store submission.

## Architecture

- Expo managed workflow with EAS, strict TypeScript, Expo Router, TanStack Query for server state, a small UI/session store, and `expo-secure-store` for tokens.
- Laravel remains authoritative for authentication, balances, cashback, referrals, orders, withdrawals, tasks, gifts, notifications, settings, and all financial mutations.
- Native navigation uses tabs and nested stacks: Home, Wallet, Earn, Inbox, and Account.
- External Shopee, TikTok Shop, and Lazada handoff uses secure native linking with attribution preserved and no secrets in deep links.
- Push tokens register through Laravel; notification deep links identify mesale.vn entities.

## Authentication Contract

- Email/password, phone/email verification, OTP, 2FA, password reset, session restore, logout, account deletion, Google native credential exchange, and Sign in with Apple.
- Laravel verifies Google and Apple credentials server-side and returns the same session-token type used by password login.
- Apple identity is keyed by Apple `sub`; verification checks JWKS signature, `iss`, `aud`, `exp`, nonce, authorization state, and private relay handling.
- Mobile stores only the session access token in Keychain/Keystore-backed secure storage.
- Logout and revocation are device/session scoped; web and mobile still reference the same user record.

## Delivery Milestones

- M0: API contract, staging, Open API readiness, Bearer auth, OAuth exchange, error envelope, pagination, integer VND, idempotency, logging, rollback.
- M1: Expo shell, theme, i18n, API client, SecureStore, session restore.
- M2: Login, registration, verification, OTP, 2FA, Google, Apple.
- M3: Home, cashback link, product result, marketplace handoff.
- M4: Orders, wallet, cashback history, balance/activity logs.
- M5: Withdrawal, withdrawal OTP, payment accounts.
- M6: Referral, check-in, tasks, gifts, gift codes, coupons, ranking.
- M7: Notifications, push, profile, security, sessions, account deletion.
- M8: Blog, legal, support, comments, likes, shares.
- M9: Screenshot parity, accessibility, performance, release builds, store audits.

Every milestone requires happy, loading, empty, validation, server-error, offline, retry, expired-session, disabled-feature, screenshot, test, and iOS/Android evidence.

## UI Parity and Verification

Rebuild each member-facing Blade screen natively from templates, Tailwind/CSS, assets, fonts, icons, spacing, states, modals, sheets, and animations. Do not modernize or simplify layout without recorded evidence and approval.

Screenshot regression covers small iPhone, iPhone Pro Max, small Android, large Android, light/dark mode, font scaling, safe area, keyboard, loading, empty, error, and offline states. Performance work follows measure -> optimize -> re-measure -> validate, using virtualized lists for histories and feeds.

## GitHub and Logging Workflow

- Markdown structure: `docs/context/CURRENT.md`, `docs/context/index.md`, session/checkpoint files, decision ADRs, `docs/blockers/OPEN.md`, and `docs/release/RELEASE-GATES.md`.
- GitHub labels: `context`, `task`, `blocker`, `decision`, `release`, `p0`, `p1`, `ios`, `android`.
- Project columns: Backlog, In Progress, Blocked, Review, Done.
- Google Sheet tabs: `00_TỔNG_QUAN`, `01_PHIÊN_LÀM_VIỆC`, `02_CHECKPOINT`, `03_CÔNG_VIỆC`, `04_QUYẾT_ĐỊNH`, `05_THAY_ĐỔI`, `06_KIỂM_THỬ`, `07_BLOCKER`, `08_RELEASE_GATE`.
- IDs use the required prefixes: `SES-`, `CKP-`, `TSK-MOB-`, `DEC-`, `CHG-`, `TST-`, `BLK-`, and `GATE-`.
- Append or update by ID only. Record a checkpoint at session start/end, milestone completion, blocker, handoff, and context-risk point.
- The service-account file is local-only and is never copied, printed, uploaded, or committed.

## Code and Push Gates

- The plan/context files are created before feature implementation.
- Subagents may implement bounded, non-overlapping tasks only after the code gate. The primary agent reviews, integrates, verifies, and owns release decisions; subagents do not push or merge.
- Push requires the separate exact approval `ĐỒNG Ý PUSH GITHUB`.
- Before push: create `codex/migration-YYYYMMDD`, run `git diff --check`, inspect staged files, secret-scan, test, lint, and build.
- PR body must include Session ID, Checkpoint ID, Task IDs, files changed, tests, blockers, store gates, rollback plan, and a checkpoint link. Never merge automatically.

## Required Inputs Before Release

Apple Team ID, Services ID, Sign in with Apple key, Google OAuth client IDs, Firebase/APNs configuration, EAS project, bundle/application IDs, staging API, review accounts, privacy/support/account-deletion URLs, and store declarations.
