# Current Migration Context

- Latest checkpoint: `CKP-20260811-003`
- Session: `SES-20260811-001`
- Plan: [migration-plan.md](../migration-plan.md)
- Phase: M0 P0 remediation and M1 MVP foundation; native Home parity and animated phone walkthrough locally verified
- Milestone status: M0 open; M1 open and not complete
- Code gate: received
- Push gate: received and completed for the current Home parity batch
- Repository: `thichmmo/mesale-app`, intentionally maintained as a Laravel + Expo monorepo
- Local branch: `codex/mvp-p0-20260809`
- Remote target: `origin/main`
- Remote `main`: Home parity and push context published through `643f6ce`; final GitHub/Sheet synchronization checkpoint follows this head
- Round 5 implementation commit: `72a1f14`; latest published Round 5 context commit: `4f6f2bf`
- Round 4 GitHub Actions: run `31328158427` passed
- Production API: `GET /api/v1/openapi/config` returned HTTP `200` with a config payload on 2026-08-10. Activation happened outside this Round A task and still requires security, staging, monitoring, and rollback review.

## 2026-08-11 Home Parity Work

- Rebuilt the native Home hero around the current Mesale branding and Vietnamese copy, with paste, help/caution, theme, notifications, and More controls.
- Vietnamese is now the deterministic default; English is used only when the authenticated account explicitly prefers `en`.
- Home, More, tab labels, and Android status-bar icons now resolve from the active light/dark theme and account locale.
- The enabled coupon and ranking sections use existing live `/coupons` and `/ranking` contracts; the disabled blog block is no longer rendered. Page Builder ordering/toggle synchronization remains incomplete.
- Added `PhoneFlowDemo.tsx`, a native self-running walkthrough with hardcoded sample values and no API/database writes. It covers link paste, product/cashback details, marketplace checkout, order confirmation, and cashback tracking.
- Replaced the square fake phone with an iPhone Pro Max-style titanium frame, rounded screen clipping, and Dynamic Island. The visible illustration heading/caption was removed at owner request.
- Verification passed: TypeScript, mobile `46/46`, Expo Doctor `18/18`, iOS/Android exports, Android emulator visual inspection, diff check, and staged sensitive-data scan.
- Operational IDs: `SES-20260811-001`, `CKP-20260811-001`, `TSK-MOB-016`, `DEC-20260811-001`, `CHG-20260811-001`, `TST-20260811-001`, `BLK-UI-002`, and `GATE-BOTH-012`.
- Code commit `88e2095` and context commit `4fd81cf` are published on `origin/main`; GitHub Issue #26 tracks `BLK-UI-002`.
- Google Sheet records for the session, checkpoints, task, decision, change, test, blocker, and release gate were upserted and verified unique; `BLK-HOME-001` was updated in place.

## Historical Round 5 Work

- Move referral-code entry out of registration into a dedicated post-authentication screen.
- Add an internal decision timestamp, backfill existing users, and add authenticated apply/skip behavior.
- Preserve email-verification and 2FA priority before the new screen.
- No production flag/data, referral-reporting, idempotency, OAuth, deletion, package, or Round 1-4 behavior change.

## Round A Work

- Added `lightColors`/`darkColors` token sets and retained the light-mode `colors` compatibility alias.
- Added `ThemeProvider`, `useTheme()`, and SecureStore-backed `system`/`light`/`dark` preference persistence.
- Added revision guards and serialized persistence so stale hydration or rapid preference changes cannot restore an older value.
- Added `lucide-react-native` and `react-native-svg`; no icon usage or visible UI redesign was made.
- Android emulator persistence evidence passed across force-stop/relaunch; temporary debug files were removed.
- Round A verification passed: TypeScript, `40/40` mobile tests including three theme contracts, Expo Doctor `18/18`, iOS export, and Android export. Native Android rebuild passed before temporary-artifact cleanup; the final post-cleanup retry reached Gradle with an online emulator but timed out after 304 seconds without new APK/install evidence.
- Round A intentionally keeps `StatusBar style="auto"` per the owner prompt. Resolved-scheme status-bar mapping and startup hydration-flash handling remain required before dark-themed screens are released.
- Local tracking IDs: `TSK-MOB-013`, `DEC-20260810-004`, `CHG-20260810-003`, `TST-20260810-009`, `GATE-BOTH-009`; push checkpoint: `CKP-20260810-008`.

## Round B Work

- Replaced the five flat legacy tabs with the website's four visible destinations: Home, Wallet, Orders, and Withdraw, plus a non-navigating More button.
- Added Lucide tab icons and switched the tab chrome to `useTheme()` colors.
- Kept `earn`, `inbox`, and `account` routes registered but hidden with `href: null`; Expo Router 5.1.11 confirms this renders no tab button while preserving deep-link navigation.
- Added direct-child alias routes for Orders and Withdraw because Expo Router does not register nested `wallet/orders` or `wallet/withdrawals` as direct tab children while `wallet/_layout.tsx` owns the nested stack. Canonical screens and deep-link paths remain unchanged.
- Added the native More bottom sheet with account header, referral code, ten approved navigation rows, unread/task badges, theme controls, and logout. No backend/API changes were made.
- Round B local verification passed: TypeScript, mobile tests `43/43`, Expo Doctor `18/18`, and iOS/Android exports. Physical device/manual navigation evidence is still pending because `adb` is unavailable in this environment.
- Local tracking IDs: `TSK-MOB-014`, `DEC-20260810-005`, `CHG-20260810-004`, `TST-20260810-010`, `GATE-BOTH-010`; local checkpoint: `CKP-20260810-009`.
- Round B implementation commit `c1b6581` and local context commit `6f34120` were fast-forwarded directly to `origin/main` without force-push.
- Round B push checkpoint: `CKP-20260810-010`.

## Round C Work

- Task 0 could not query local PageBlock data because MariaDB at `127.0.0.1:3306` refused the connection. Production homepage HTML returned HTTP 200 and rendered `hero -> coupons -> timeline -> blog`.
- A later direct shell recheck was denied by the live host with HTTP 403/406; no bypass or middleware change was attempted. The earlier 200 response is retained as snapshot evidence, and live availability remains open.
- Appended native coupon, timeline, and blog snapshot blocks below the existing Home cashback tool. Website-only blog links, unsupported blocks, fake stats, leaderboard, and created-links data were not added.
- Rebuilt Wallet with the orange/red gradient banner, balance visibility toggle, withdrawal/orders quick actions, three account stats, and up to five recent orders from the existing `useOrders()` cache.
- Added native `expo-linear-gradient` and `expo-clipboard`; no Laravel/API/database changes were made.
- Round C verification passed: TypeScript, mobile `45/45`, Expo Doctor `18/18`, iOS export, and Android export. `adb devices` is unavailable, so device and screenshot evidence remain open.
- Round C tracking IDs: `SES-20260810-005`, `CKP-20260810-011`, `TSK-MOB-015`, `DEC-20260810-006`, `CHG-20260810-005`, `TST-20260810-011`, `BLK-HOME-001`, `GATE-BOTH-011`; GitHub Issues #25 and #24 contain matching redacted records.
- Round C implementation commit `d02eb15` and context commit `2032b99` were fast-forwarded directly to `origin/main` without force-push; push checkpoint: `CKP-20260810-012`.

## Verified Current State

- Round 5 implementation: backend migration/backfill, authenticated referral apply/skip, auth response/account pending flag, native referral screen, and continuation-first routing are published on `main`.
- Round 5 verification: PHPUnit `77/702`, mobile `37/37`, TypeScript, Expo Doctor `18/18`, iOS/Android export, PHP syntax, targeted Pint, diff, and secret checks passed.

- Local Laravel portable PHP run before Round 5: `72` tests / `664` assertions.
- Focused idempotency pruning run: `4` tests / `16` assertions.
- Apple-linked deletion now uses policy option B: fresh Sign in with Apple reauthentication is mandatory before deletion, so the transient refresh token can be revoked before the local transaction. Password-only deletion returns `APPLE_REAUTH_REQUIRED_FOR_DELETION`.
- Saved payout destinations are normalized into a SHA-256 destination identity and globally hard-blocked by a database unique constraint; cross-user attempts return `ACCOUNT_ALREADY_CLAIMED`.
- Deleting a referrer now nulls only `referrer_id` in `referrals` and `referral_commissions`; relationship and commission amounts remain queryable for the surviving referred member. No denormalized referrer PII exists in those tables.
- Both new migrations passed a clean SQLite test migration and two-step rollback. Task-specific Apple, payment-account, and referral-ledger regressions passed individually and in the full suite.
- Redacted operational sync was verified for `SES-20260809-005`, `CKP-20260809-015`, `TSK-MOB-009`, `DEC-20260809-009`, `CHG-20260809-013`, `TST-20260809-014`, `BLK-AUTH-003`, `BLK-FIN-001`, `BLK-DATA-002`, and `GATE-BOTH-005`; each ID occurs exactly once. No Sheet location, service-account path, credentials, or member data is recorded here.
- Mobile contract tests before Round 5: `34/34` passed; Round 5: `37/37` passed.
- TypeScript typecheck: passed.
- Expo Doctor: `18/18` passed for Round 5.
- iOS and Android Expo exports: passed; Round 5 bundles approximately `3.09 MB` each.
- Strict idempotency replay preflight, payment-account activity-log masking, and admin password/2FA reset token revocation: resolved locally and covered by local regression tests.
- Backend native Google and Apple OAuth verification/exchange and native mobile provider flows are implemented locally, but feature flags are OFF and owner credentials are not yet supplied.
- Expo MVP tabs/screens cover auth, home/cashback, wallet/orders/balance/withdrawal/payment accounts, earn/referrals/check-in/tasks/gifts/gift code, inbox notifications, and account/security/sessions/deletion UI.
- SecureStore session expiry validation, `/account` restore, 401 invalidation, logout/account-switch cache clearing, idempotency-key retry stability, and native external handoff/share are implemented.
- `npm audit --omit=dev` remains `22` findings (`7 high`, `15 moderate`, `0 critical`). No safe SDK 53 remediation exists; the staged SDK 54-to-57 plan is documented. Round A intentionally added only the approved icon dependencies and their lockfile entries.
- No physical-device screenshots, signed IPA/AAB, production AASA/Asset Links deployment, or performance baseline exists. Source legal/deletion/support resources and association groundwork are present; Round 3 GitHub Actions runs `31325626880` and `31325812368` passed.
- Redacted operational sync evidence was verified via API for `SES-20260809-004`, `CKP-20260809-014`, `TSK-MOB-008`, `DEC-20260809-008`, `CHG-20260809-012`, `TST-20260809-013`, `BLK-AUTH-003`, `BLK-DATA-002`, `BLK-PUSH-001`, and `GATE-BOTH-004`; each returned count `1`. No Sheet URL/ID, key path, or content is recorded.
- Round 4 redacted operational sync was verified exactly once for `SES-20260810-001`, `CKP-20260810-001`, `CKP-20260810-002`, `TSK-MOB-011`, `DEC-20260810-001`, `DEC-20260810-002`, `CHG-20260810-001`, `TST-20260810-001` through `003`, `BLK-DATA-001`, `BLK-DEP-001`, and `GATE-BOTH-007`. GitHub Issues #14 and #7 contain matching local evidence and remain open.
- Round 4 remote CI run `31328158427` passed all three jobs. It emitted a non-failing warning that Actions v4 JavaScript runtimes target deprecated Node.js 20 and are being forced to Node.js 24.
- Round 5 operational IDs are `SES-20260810-002`, `CKP-20260810-004` through `006`, `TSK-MOB-012`, `DEC-20260810-003`, `CHG-20260810-002`, `TST-20260810-005` through `008`, and `GATE-BOTH-008`; redacted Sheet sync was verified exactly once for each ID.
- Round A operational IDs are `SES-20260810-003`, `CKP-20260810-007`/`008`, `TSK-MOB-013`, `DEC-20260810-004`, `CHG-20260810-003`, `TST-20260810-009`, and `GATE-BOTH-009`; GitHub Markdown and redacted Google Sheet records are synchronized, with each ID verified exactly once.

## Open Blockers

- `BLK-API-001`: production config now responds HTTP `200`; activation provenance, auth/security validation, staging, review access, monitoring, and rollback evidence remain open.
- `BLK-AUTH-001`: native Apple/Google source flows exist, but provider credentials, safe account-linking rollout, staging, and device evidence remain missing.
- `BLK-AUTH-003`: resolved locally with mandatory fresh Apple reauthentication for every Apple-linked deletion; native-device and staging provider evidence remain pending.
- `BLK-AUTH-002`: Google provider identity lacks a database unique constraint; duplicate audit and owner decision are required.
- `BLK-DATA-001`: 24-hour completed retention, seven-day stale-processing retention, bounded pruning, and scheduling are resolved locally; deployment/monitoring evidence remains pending.
- `BLK-DATA-002`: bounded referral-ledger preservation is resolved locally, but long-term retention/anonymization for the wider financial ledger remains an owner/legal decision before production activation.
- `BLK-FIN-001`: global hard-block policy and database constraint are resolved locally; production duplicate audit and MariaDB deployment/concurrency evidence remain pending.
- `BLK-PUSH-001`: choose FCM-for-both-platforms or an APNs bridge, then provide transport, credentials, EAS configuration, and device tests.
- `BLK-STORE-001`: owner-approved deletion/support/legal content, production association verification, push, review accounts, signed artifacts, and store-console evidence; source groundwork is present.
- `BLK-DEP-001`: no safe SDK 53 remediation for 22 npm advisories; execute the documented staged Expo 54-to-57 upgrade with native/device evidence.
- `BLK-DEVICE-001`: physical iOS/Android, screenshot regression, accessibility, offline, keyboard, and performance evidence.
- `BLK-UI-001`: orange/white primary action contrast is approximately `2.80:1`; product decision requires screenshot evidence.
- `BLK-HOME-001`: coupon/blog static snapshot can become stale because the current site blocks are dynamic and no public mobile content API exists.
- `BLK-UI-002`: the native phone walkthrough contains hardcoded order/cashback values without a visible illustration label; owner-approved store/user disclosure is required before release. GitHub Issue #26.
- `BLK-TEST-001`: resolved for remote `bd1b802`; GitHub Actions run `31325812368` passed. Device/store evidence remains separate.

## Handoff

Keep M0/M1 open and store verdicts at `NOT READY`. Round C source/tests and direct push are verified. Continue with staging/API readiness, OAuth owner inputs/device evidence, push transport, the staged Expo dependency upgrade, and device/screenshot/signed-artifact verification. Source and tests are authoritative if any log disagrees.

No secrets, service-account information, raw HTTP responses, or member data are included in this context.
