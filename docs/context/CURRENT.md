# Current Migration Context

- Latest checkpoint: `CKP-20260813-041`
- Session: `SES-20260813-035`
- Plan: [migration-plan.md](../migration-plan.md)
- Phase: M0 P0 remediation and M1 MVP foundation; production referral eligibility gap closed with a scoped additive patch
- Milestone status: M0 open; M1 open and not complete
- Code gate: received
- Push policy: owner standing instruction is direct fast-forward publication to `origin/main` after verified work
- Repository: `thichmmo/mesale-app`, intentionally maintained as a Laravel + Expo monorepo
- Local branch: `codex/mvp-p0-20260809`
- Remote target: `origin/main`
- Current batch: `TSK-MOB-050` / `CHG-20260813-041`; launcher wordmark and installed app name follow-up is locally verified.
- Source state, Android guide interaction, and app-selected light/dark presentation are locally verified; physical-device and store gates remain open.

## 2026-08-13 Android Launcher Icon Safe Zone

- Confirmed the previous Android adaptive foreground used the complete opaque square logo outside Android's central safe zone, so launcher masks cropped its corner artwork.
- Added a dedicated transparent 1024x1024 foreground containing the shopping bag, percent tag, and sparkle with safe padding; the full in-app logo remains unchanged.
- Expo prebuild and Android debug reinstall passed; Pixel Launcher home and app drawer showed the complete, sharper mark without clipping.
- Verification: mobile `107/107`, TypeScript, Expo Doctor `18/18`, Android build/install, launcher inspection, and diff checks passed.
- Source commit `2effeb2` is published directly to `origin/main`; GitHub Actions run `31702131858` passed mobile tests, Laravel tests, context/secret checks, and both platform exports.
- Operational IDs: `SES-20260813-034`, `CKP-20260813-040`, `TSK-MOB-049`, `CHG-20260813-040`, `TST-20260813-040`.
- Google Sheet synchronization is pending because no live connector is available; release remains `NOT READY`.

## 2026-08-13 Launcher Wordmark Follow-up

- Updated the adaptive launcher foreground to retain the exact `Mê Sale` wordmark beneath the shopping mark while still excluding the unreadable tiny tagline.
- Reduced the complete mark and wordmark into Android's adaptive-icon safe zone so circular masks keep every corner visible.
- Changed the installed app display name from `Mesale` to `Mê Sale`.
- Operational IDs: `SES-20260813-035`, `CKP-20260813-041`, `TSK-MOB-050`, `CHG-20260813-041`, `TST-20260813-041`.
- Verification: mobile `108/108`, TypeScript, Expo Doctor `18/18`, Expo prebuild, Android debug build/install, and Pixel Launcher app-drawer inspection passed.

## 2026-08-13 Account Tab Hub Recovery

- Confirmed the full Account hub and all nested Account screens remain in source; the reported UI was the nested Settings route retained as the tab state.
- Selecting the Account bottom tab now always opens the canonical Account hub, including after a direct child deep link or after leaving the tab.
- Android runtime checks passed for Settings to Account, Settings to Home to Account, Account re-tap, child back navigation, and the full vertical Account inventory.
- Verification: mobile `106/106`, TypeScript, Expo Doctor `18/18`, and diff checks passed.
- Source commit `f575378` is published directly to `origin/main`; GitHub Actions run `31690494256` passed mobile tests, Laravel tests, context/secret checks, and both platform exports.
- Operational IDs: `SES-20260813-033`, `CKP-20260813-039`, `TSK-MOB-048`, `CHG-20260813-039`, `TST-20260813-039`.
- Google Sheet synchronization is pending because no live connector is available; release remains `NOT READY`.

## 2026-08-13 Native Usage Guide

- Added a full native `Hướng dẫn sử dụng` route with themed cards, nine expandable FAQs, safe-area handling, and no WebView.
- Account now opens the native guide and existing native Tips route; the primary tab bar is hidden on the full-screen guide.
- Guide financial conditions come from live config and referral guidance uses the server-confirmed three-day eligibility window; unsupported fixed earning claims and store cards are omitted.
- Preserved the reviewed Home HTTPS affiliate normalization, Android VIEW-intent fallback, and referral copy update.
- Verification: mobile `106/106`, TypeScript, Expo Doctor `18/18`, Android debug build/install, FAQ expansion, light mode, dark mode, and diff checks passed.
- Operational IDs: `SES-20260813-032`, `CKP-20260813-038`, `TSK-MOB-047`, `CHG-20260813-038`, `TST-20260813-038`.
- Google Sheet synchronization is pending because no live connector is available; release remains `NOT READY`.

## 2026-08-13 Android Test APK

- Reconfirmed at source commit `232ec18` that the Home cashback result no longer contains the `Bạn nhận 100%` pill while server-provided cashback details remain intact.
- Built an ARM64 release APK for a physical Android device and copied it to `C:\Users\ThichMMO\Desktop\mesale-android-test-232ec18-arm64.apk`.
- Verified package `vn.mesale.app`, version `0.1.0`, min SDK `24`, target SDK `35`, ABI `arm64-v8a`, APK Signature Scheme v2, and the embedded production API base.
- Built and installed a separate x86_64 release variant on `emulator-5554`; it opened `MainActivity` without Metro forwarding or a fatal startup error.
- The test artifact is signed with the local Android debug certificate and is not Play-ready. Physical-device feature testing, release signing, AAB, dependency remediation, and store audits remain open.
- Operational IDs: `SES-20260813-031`, `CKP-20260813-037`, `TSK-MOB-046`, `CHG-20260813-037`, `TST-20260813-037`.
- Google Sheet synchronization is pending because no live connector is available; release remains `NOT READY`.

## 2026-08-13 Removed Home Member Share Pill

- Removed the `Bạn nhận X%` pill, its client-side ratio calculation, and its unused styles from the compact Home result.
- Preserved total commission and the server-provided user cashback amount in expanded calculation details.
- No API, backend, marketplace handoff, sharing, database, or production behavior changed.
- Operational IDs: `SES-20260813-030`, `CKP-20260813-036`, `TSK-MOB-045`, `CHG-20260813-036`, `TST-20260813-036`.
- Google Sheet synchronization is pending because no live connector is available; release remains `NOT READY`.

## 2026-08-13 Compact Home Cashback Result

- Moved the generated product result directly below the pasted link and reduced it to product image/name/price, truthful commission summary, blue total block, member share, expandable details, and `Mua ngay` / `Chia sẻ` actions.
- Removed the raw affiliate URL, marketplace badge, reference, verbose notice, and large metrics from the collapsed card.
- Kept calculations server-derived; no unsupported base/XTRA commission split was fabricated.
- Added estimated-mode semantics, per-product state reset, blue theme-safe accents, and narrow-screen text constraints.
- Verification: mobile `103/103`, TypeScript, diff check, and Android debug build/install on `emulator-5554` passed.
- Operational IDs: `SES-20260813-029`, `CKP-20260813-035`, `TSK-MOB-044`, `CHG-20260813-035`, `TST-20260813-035`.
- Google Sheet synchronization is pending because no live connector is available; release remains `NOT READY`.

## 2026-08-13 Avatar Upload Emulator Verification

- Production had an older `AccountController` while the live route referenced `uploadAvatar`; the reviewed controller, avatar service, user model, and targeted `avatar_path` migration were synchronized to the host.
- Production `public/storage -> storage/app/public` was created with Laravel's `storage:link`; the helper was removed immediately after execution.
- Android emulator smoke passed: Photo Picker opened, crop completed, `Tải ảnh lên` returned to the profile without `Server Error` or a fatal logcat exception.
- The uploaded WebP was confirmed under the production public avatar storage and its HTTPS `/storage/avatars/...` response returned `200 image/webp`; the app profile rendered an `ImageView` instead of the initials fallback.
- Local temporary diagnostic helpers were removed; no credential, token, member data, or raw production response was recorded.
- Verification IDs: `SES-20260813-028`, `CKP-20260813-034`, `TSK-MOB-043`, `CHG-20260813-034`, `TST-20260813-034`.
- Release remains `NOT READY`; physical-device, signed-artifact, staging, privacy, and store evidence remain open.

## 2026-08-13 Production Avatar And Payment-Account Remediation

- Production was behind the mobile contract: the avatar handler, `users.avatar_path`, idempotency storage, payment-account destination hash, and its global uniqueness constraint were missing from the live snapshot.
- Only the reviewed backend files and three targeted migrations were deployed. The remaining pending migrations were not run, and no member, balance, order, payout, token, or credential data was changed.
- Runtime evidence: live `/api/v1/openapi/config` returns HTTP `200`; unauthenticated payment-account access returns expected `401`; avatar route is registered and returns the expected method response without credentials.
- Local verification: backend focused suites `74/74` (`733` assertions), mobile `102/102`, TypeScript, PHP syntax, and `git diff --check` pass.
- Authenticated upload/create smoke remains required for one disposable member: upload avatar, add one bank account, add one e-wallet account. Record only status, API code, and request ID.
- Operational IDs: `SES-20260813-027`, `CKP-20260813-033`, `TSK-MOB-042`, `CHG-20260813-033`, `TST-20260813-033`.

## 2026-08-13 Performance Audit Remediation

- Session restore uses a validated SecureStore v2 record containing the Bearer session and a minimal token-bound user preview. The preview only unblocks the shell; `/account` remains the source of truth and refreshes immediately in the background.
- Account and Referral share the canonical infinite referral query. Account no longer fetches withdrawal history that it does not render. Referral data and Referral/Withdraw route modules are warmed after interactions.
- Home's native phone walkthrough is lifecycle-aware: it pauses on tab blur/background, cancels pending waits, stops native animations, and restarts deterministically from `idle`.
- Verification: mobile `102/102`, TypeScript, Expo Doctor `18/18`, diff check, Android emulator reload/MainActivity and filtered logcat without JS fatal errors.
- Operational IDs: `SES-20260813-026`, `CKP-20260813-032`, `TSK-MOB-041`, `CHG-20260813-026`, and `TST-20260813-026`.
- No Laravel/API/database/production/credential/member-data change. Google Sheet sync is pending because the live connector is unavailable; release remains `NOT READY`.
- Source `365b968`, checkpoint `f417a07`, and publication record `76239cb` are published directly to `origin/main` without force-push.
- Round 5 implementation commit: `72a1f14`; latest published Round 5 context commit: `4f6f2bf`
- Round 4 GitHub Actions: run `31328158427` passed
- Production API: `GET /api/v1/openapi/config` returned HTTP `200` with a config payload on 2026-08-10. Activation happened outside this Round A task and still requires security, staging, monitoring, and rollback review.

## 2026-08-12 Retired Rewards Hub

- Removed `Đổi quà tặng` from Account Explore and from the legacy More sheet source.
- Kept the requested direct Account rows for Coupons, Check-in, Tasks, Tips & Trick, and Usage Guide.
- Replaced the duplicate `Nhận thưởng` root screen with a compatibility redirect to Account so stale `/earn` navigation cannot reopen the retired UI or hit a missing route.
- Preserved the direct gift, gift-history, and gift-code implementation and Laravel contracts without promoting them in the current Account UI.
- Android Account layout and stale-route redirect passed; mobile `99/99`, TypeScript, and Expo Doctor `18/18` pass.
- Operational IDs: `SES-20260812-025`, `CKP-20260812-031`, `TSK-MOB-040`, `CHG-20260812-025`, and `TST-20260812-025`.
- Google Sheet synchronization is pending because no live connector is available; no local credential fallback was used.

## 2026-08-12 Home Referral CTA

- Removed the two Home cashback-help actions already covered by the native Tips & Trick screen.
- Added a compact orange `Rủ bạn dùng Mê Sale` CTA with localized copy and an accessible action chip.
- Tapping the CTA opens the existing `/(tabs)/referrals` screen and selects the visible Referral tab.
- Deliberately omitted the sample `50.000đ` reward because the server does not provide an approved fixed CTA amount.
- Android emulator layout and click-through passed; mobile `98/98`, TypeScript, and Expo Doctor `18/18` pass.
- Operational IDs: `SES-20260812-024`, `CKP-20260812-030`, `TSK-MOB-039`, `CHG-20260812-024`, and `TST-20260812-024`.
- Google Sheet synchronization is pending because no live connector is available; no local credential fallback was used.

## 2026-08-12 Native Tips & Zalo Support

- Replaced the Home `Tips & Trick` alert with a themed native Home-stack screen modeled on the owner-provided references.
- Added nine static cashback-tracking risk cards, multi-expand accordion behavior, the first card expanded by default, accessible expanded state, example/solution panels, and a Mê Sale disclosure footer.
- Kept the content local and virtualized; no API, WebView, Laravel, database, financial mutation, credential, or production setting is involved.
- Hid the primary tab bar while the full-screen Tips route is active and warmed the static route after Home interactions.
- Changed Home Support from the website support page to the fixed HTTPS Zalo group requested by the owner, retaining direct asynchronous `Linking.openURL` handoff.
- Android emulator light-mode screenshot and accordion interaction passed; Support launched Chrome with the requested `https://zalo.me/` group URL.
- Mobile `98/98`, TypeScript, Expo Doctor `18/18`, Android/iOS export, and diff checks pass.
- Operational IDs: `SES-20260812-023`, `CKP-20260812-029`, `TSK-MOB-038`, `CHG-20260812-023`, and `TST-20260812-023`.
- Google Sheet synchronization is pending because no live connector is available; no local credential fallback was used.

## 2026-08-12 Quick Access Latency Remediation

- Added exact shared query contracts and post-interaction route/data prefetch for Coupons and Check-in.
- Coupons and Check-in now render their native destination shell immediately instead of showing a full-screen loading gate while awaiting Laravel.
- Support now opens the fixed HTTPS URL directly; `Tips & Trick` remains an immediate local alert.
- Android emulator inspection showed warmed Coupons and Check-in content within the inspected 500 ms window.
- Live read-only timings confirm backend variance remains possible, including a coupon request sample near 6.65 seconds; cache-first UI prevents that variance from blocking navigation presentation.
- Mobile `94/94`, TypeScript, Expo Doctor `18/18`, and diff checks pass.
- Operational IDs: `SES-20260812-022`, `CKP-20260812-028`, `TSK-MOB-037`, `CHG-20260812-022`, and `TST-20260812-022`.
- Google Sheet synchronization is pending because no live connector is available; no credential fallback was used.

## 2026-08-12 Referral Primary Tab

- Replaced the visible `Ví` bottom-tab destination with `Giới thiệu` using the existing native, Laravel-backed referral screen.
- Primary order is now `Trang chủ`, `Giới thiệu`, `Đơn hàng`, `Rút tiền`, and `Tài khoản`.
- The Wallet route tree remains registered and directly routable; no wallet screen, API contract, financial mutation, or data was removed.
- Mobile `93/93`, TypeScript, Expo Doctor `18/18`, Android/iOS exports, and diff checks pass.
- Operational IDs: `SES-20260812-021`, `CKP-20260812-027`, `TSK-MOB-036`, `CHG-20260812-021`, and `TST-20260812-021`.
- Google Sheet synchronization is pending because no live connector is available; no credential fallback was used.

## 2026-08-12 Immediate Post-login Home Preview

- Preserves the complete integer VND balance, total cashback, referral earnings, and total withdrawn snapshot returned by authentication.
- Home uses the snapshot only as a component-local preview and still starts `/account` immediately; shared React Query account data is never seeded with a partial object.
- Pending cashback remains unknown until `/account` responds and is rendered as an accessible skeleton instead of a fabricated value.
- Failed `/account` refreshes retain the preview with the existing warning/retry action; successful refreshes replace it automatically.
- Mobile `84/84`, TypeScript, Expo Doctor `18/18`, Android/iOS exports, emulator inspection, and diff checks pass.
- GitHub Actions run `31586236320` passed context/secret checks, Laravel tests, mobile tests, TypeScript, Expo Doctor, and both platform exports.
- Operational IDs: `SES-20260812-019`, `CKP-20260812-025`, `TSK-MOB-034`, `CHG-20260812-019`, and `TST-20260812-019`.
- Google Sheet synchronization is pending because the required connector is unavailable in this session.
- No Laravel, API, database, production, credential, token, or member-data change was made.

## 2026-08-12 Primary Tab Loading Optimization

- Wallet and Account render immediately from current authenticated identity/financial snapshots while complete Laravel responses refresh in the background.
- Orders, config, and payment accounts are prefetched after the authenticated gate without delaying tab rendering.
- Session restoration, Home, Wallet, Account, and withdrawal flows now reuse one complete `/account` cache; Home and withdrawal flows reuse one complete `/config` cache.
- The withdrawal form shell renders immediately, but Laravel-confirmed balance, policy, fees, OTP, and saved payment accounts still gate every financial submission.
- Mobile `92/92`, TypeScript, Expo Doctor `18/18`, Android/iOS exports, and Android emulator tab transitions pass.
- Source `e891bd5` is published directly to `origin/main`; GitHub Actions run `31590669219` passed all context/secret, Laravel, mobile, TypeScript, Expo Doctor, and platform-export gates.
- Operational IDs: `SES-20260812-020`, `CKP-20260812-026`, `TSK-MOB-035`, `CHG-20260812-020`, and `TST-20260812-020`.
- Google Sheet synchronization is pending because no live connector is available; no credential fallback was used.
- No Laravel, database, production setting, credential, token, or member-data change was made.

## 2026-08-12 Compact Native Login Copy

- Changed the hero tagline to `Hệ thống mua sắm hoàn tiền Shopee - Tiktok`.
- Removed the login cashback/withdrawal subtitle, safe-server disclosure, and unauthenticated `Hỗ trợ · Xóa tài khoản` footer row.
- Privacy Policy and Terms remain on login; support and account deletion remain available under authenticated Account screens.
- Google/Apple native actions and all backend authentication contracts are unchanged.
- Android emulator inspection, mobile `80/80`, TypeScript, Expo Doctor `18/18`, focused OAuth `6/6`, diff, and scoped secret checks pass.
- Operational IDs: `SES-20260812-017`, `CKP-20260812-023`, `TSK-MOB-032`, `CHG-20260812-017`, and `TST-20260812-017`.
- Google Sheet sync is pending because the required connector is unavailable in this session.
- Source `62fcb2d` and context `7e39dcd` are published directly to `origin/main`; GitHub Actions run `31580498286` passed context/secret, Laravel, mobile, TypeScript, Expo Doctor, and both exports.

## 2026-08-12 Login Legal Footer Placement

- Added flex-auto spacing before the login disclosure and legal links so they sit at the bottom of tall screens instead of leaving unused space below.
- Preserved ScrollView, safe-area bottom padding, and KeyboardAvoidingView behavior; short screens and open-keyboard states remain scrollable.
- No authentication, OAuth, API, legal URL, support, account-deletion, database, or production behavior changed.
- Android emulator inspection passed; mobile `80/80`, TypeScript, diff check, and staged sensitive-pattern review pass.
- Operational IDs: `SES-20260812-018`, `CKP-20260812-024`, `TSK-MOB-033`, `CHG-20260812-018`, and `TST-20260812-018`.
- Google Sheet synchronization is pending because the required connector is unavailable in this session.

## 2026-08-12 Native Google Login And iOS Apple Groundwork

- Rebuilt the native login screen with Vietnamese Mê Sale branding and placed Sign in with Apple before Google on supported iOS devices.
- Android Google uses the native Google Play Services flow; Laravel validates the Google ID token and returns the canonical device-scoped Bearer session.
- Provider identity linking is based on Google/Apple `sub`; an existing email-only match fails closed with `ACCOUNT_LINK_REQUIRED` and is never merged automatically.
- Production Google OAuth was enabled only after the exact full UNIQUE `google_id` migration and a redacted invalid-token canary. Live Apple OAuth remains disabled.
- Verification passed before publication: Laravel `94/94` with `866` assertions, focused Google `4/20`, focused Google/Apple exchange `12/98`, mobile `80/80`, TypeScript, Expo Doctor `18/18`, both exports, Android native activity, and diff checks.
- Operational IDs: `SES-20260812-016`, `CKP-20260812-022`, `TSK-MOB-031`, `DEC-20260812-012`, `CHG-20260812-016`, `TST-20260812-016`, and `GATE-BOTH-021`.
- Google Sheet synchronization is pending because the required Google Drive/Sheets connector is unavailable in this session; no service-account fallback or credential was copied into the repository.
- Source commit `acfd137` and context commit `89e09b8` are published directly to `origin/main`; GitHub Actions run `31578792474` passed context/secret, Laravel, mobile, TypeScript, Expo Doctor, and both export jobs.
- Real Google account-to-Bearer evidence and all signed iOS Google/Apple evidence remain pending. Release remains `NOT READY`.

## 2026-08-12 Production Referral Eligibility Patch

- Audited the production migration ledger before deployment: nine repository migrations were absent, and seven unrelated migrations were explicitly excluded.
- Deployed only the two nullable referral eligibility columns by exact path, without `after(...)`, defaults, indexes, or existing-member backfill.
- Patched the older production snapshot with reviewed route/controller/model/service hunks instead of copying current repository files wholesale.
- Verified both columns and migration records, zero existing non-null prompt/deadline values, expected unauthenticated `401` on the referral endpoint instead of `404`, public homepage availability, zero reviewed-file mismatches, and cleanup of temporary deploy helpers.
- Local source verification passed: focused Laravel `57/57` with `520` assertions, PHP syntax, diff check, and staged sensitive-pattern review.
- Source commit `42abd4c` and context commit `3e7425a` are published directly to `origin/main`; source run `31564614647` and final run `31564806218` pass all workflow jobs.
- Operational IDs: `SES-20260812-015`, `CKP-20260812-021`, `TSK-MOB-030`, `DEC-20260812-011`, `CHG-20260812-015`, `TST-20260812-015`, and `GATE-BOTH-020`.
- The seven redacted operational Sheet rows were appended once and every ID read back exactly once; GitHub Issue #23 has matching redacted evidence and remains open.
- No disposable production account was created; registration/apply/skip/replay canary evidence remains pending. Release remains `NOT READY`.

## 2026-08-12 Withdrawal History And Request Routing Parity

- `Lich su rut tien` now consistently opens the Laravel-backed history route, while `Rut tien` and `Tao yeu cau rut tien` open the existing native creation form through the direct withdrawal tab.
- The direct tab keeps `Rut tien` highlighted, provides a themed safe-area header, and returns to history through its back action.
- Home, Wallet, Account, and history-create CTAs now follow the same route contract. Explicit history actions remain unchanged.
- No withdrawal API, validation, OTP, idempotency, saved-account, fee, minimum, balance, database, or production behavior changed.
- Verification passed: mobile `78/78`, TypeScript, Expo Doctor `18/18`, iOS/Android exports, Android emulator history/create/back click-through, diff checks, staged sensitive-pattern review, and unique Google Sheet ID read-back.
- Source commit `3dd93d2` and context commit `fa32fa3` are published directly to `origin/main`; GitHub Actions runs `31518925269` and `31519331351` passed all three jobs, including both exports.
- Operational IDs: `SES-20260812-014`, `CKP-20260812-020`, `TSK-MOB-029`, `DEC-20260812-010`, `CHG-20260812-014`, `TST-20260812-014`, and `GATE-BOTH-019`; `BLK-DEVICE-001` was updated in place.
- Physical devices, dark mode, full accessibility/keyboard/offline/performance matrices, signed artifacts, staging/API security, OAuth, privacy declarations, and store-console evidence remain open. Release remains `NOT READY`.

## 2026-08-12 Bank Search, Native Coupons, And Referral Gate

- Payment-account creation now uses a native searchable picker and scrollable server allow-list for banks and wallets; existing validation, idempotency, and mutation behavior remain intact.
- Home Quick Access and Account `San ma giam gia` open a native coupon screen backed by Laravel `/coupons`, with category filters, pagination, clipboard copy, HTTPS-only handoff, and loading/empty/error/offline/retry states.
- Authenticated sessions with Laravel `referral_prompt_pending` now route to referral onboarding; Laravel still enforces the 72-hour window and expiry.
- Verification passed: mobile `76/76`, TypeScript, Expo Doctor `18/18`, iOS/Android exports, Android emulator live-coupon inspection, and staged diff check.
- Source commit `67311d7`, context commit `10c103e`, operational sync `a7515cf`, and final docs sync `ae2ed14` were fast-forwarded directly to `origin/main`; GitHub Actions run `31516177892` passed all three jobs. Operational rows for the session, checkpoint, task, decision, change, test, release gate, and the existing device blocker were read back with unique IDs.
- No backend, database, production setting, credential, or member-data change was made. Physical device, staging, OAuth, signed-artifact, privacy, and store-console gates remain open.

## 2026-08-11 Compact Withdrawal Form Follow-up

- Reordered withdrawal creation into a compact native form matching the approved web reference: amount, server-enabled receiving method, saved destination, read-only destination details, optional OTP, and submit action.
- Bank and wallet availability remains controlled by Laravel flags; destination values and the mutation payload remain derived from saved payment accounts.
- Existing OTP, validation, retry, stable idempotency, live balance, minimum, fee, and success behavior are preserved.
- Bottom-tab-aware scroll padding keeps the action reachable above the tab bar and keyboard flow.
- Verification passed: mobile `73/73`, TypeScript, Expo Doctor `18/18`, local iOS/Android exports, focused withdrawal contracts, scoped diff check, Android emulator light-mode inspection, and GitHub Actions run `31511866617`.
- Operational IDs: `SES-20260811-012`, `CKP-20260811-018`, `TSK-MOB-027`, `DEC-20260811-008`, `CHG-20260811-012`, `TST-20260811-012`, and `GATE-BOTH-017`.
- Source `0548e23` and context `3be7c0b` are published directly to `origin/main`; runs `31511866617` and `31512308009` passed.
- Google Sheet records for the session, checkpoint, task, decision, change, test, blocker, and release gate were upserted and verified unique; `BLK-DEVICE-001` was updated without duplication.
- Physical devices, dark mode, full accessibility/keyboard/offline/performance matrices, signed artifacts, privacy declarations, and store-console evidence remain open. App Store and Google Play remain `NOT READY`.

## 2026-08-11 Account, Check-in, Referral, And Withdrawal UI Parity

- Removed redundant Account-section headings while retaining the four compact parent cards and all nested destinations.
- Referral-code entry is collapsed and shown only when Laravel reports eligibility within the server-authoritative 72-hour window.
- Daily Check-in now renders a native seven-day streak, server milestones, mutation feedback, history, pagination, and dark-aware loading/error/offline/empty states.
- Referrals now uses the live F1 rate, code/link share flow, live invited/earned totals, network and commission filters, and the owner-approved three-step copy without App Store/Google Play cards.
- Withdrawal creation now uses live balance, minimum, fee, OTP policy, saved payment accounts, idempotency, and truthful processing copy.
- Shared async states and wallet headers follow runtime light/dark theme; wallet header safe area and title remain correct at Android font scale `1.0` and `1.3`.
- Verification passed: mobile `71/71`; TypeScript; Expo Doctor `18/18`; iOS/Android exports; Android emulator light/dark checks; wallet header font scaling; diff and staged credential checks.
- Source `305838c` and context `33a90cd` are published directly to `origin/main`; GitHub Actions runs `31500747827` and `31501266934` passed all jobs, including both exports.
- Operational IDs: `SES-20260811-011`, `CKP-20260811-016`, `CKP-20260811-017`, `TSK-MOB-026`, `DEC-20260811-007`, `CHG-20260811-011`, `TST-20260811-011`, and `GATE-BOTH-016`.
- The redacted operational rows were synchronized and verified unique by ID; `BLK-DEVICE-001` was updated in place with the new emulator evidence.
- Physical devices, full accessibility/offline/performance matrices, signed artifacts, privacy declarations, and store-console evidence remain open. App Store and Google Play remain `NOT READY`.

## 2026-08-11 Account, Avatar, And Referral Hardening

- The two previously observed Home files are restored and clean; Home is excluded from this batch.
- Account keeps one high-level row for each `Thông tin tài khoản`, `Tài chính`, `Thông báo`, and `Cài đặt` section. Child actions open on focused nested screens under a themed safe-area-aware header with a deep-link back fallback.
- Avatar editing uses the system Photo Picker, bounded square resize, authenticated upload/delete, and shared account refresh. It requests neither camera nor broad media access.
- Referral-code entry is controlled by a Laravel-authoritative 72-hour eligibility window with no existing-account backfill and no client-clock authorization.
- Backend review removed `avatar_path` from mass assignment and prevents post-commit activity-log failure from converting successful avatar upload/delete into HTTP 500.
- Verification passed: Laravel `89/89` with `840` assertions; focused backend `11/109`; mobile `63/63`; TypeScript; Expo Doctor `18/18`; iOS/Android exports; Android native debug, emulator Account/header, and Photo Picker interaction; diff check.
- SQLite coverage does not prove production-database concurrency. Production migrations and deployment were not run.
- Operational IDs: `SES-20260811-010`, local checkpoint `CKP-20260811-014`, final checkpoint `CKP-20260811-015`, `TSK-MOB-025`, `DEC-20260811-006`, `CHG-20260811-010`, `TST-20260811-010`, and `GATE-BOTH-015`.
- Source commit `f9d87c9` and context commit `4eeafff` are published directly to `origin/main`. Runs `31494516026` and `31494964995` passed; the final run passed `context-log-check`, `laravel-tests`, and `mobile-tests`, including both exports. App Store and Google Play remain `NOT READY`.

## 2026-08-11 Native Account Tab Redesign

- Replaced the primary `Thêm` button and More sheet with a real `Tài khoản` tab based on the latest owner-provided Account reference.
- Rebuilt Account as a native hub for profile, wallet, bank accounts, withdrawals, referrals, security, preferences, legal/support, theme, and logout.
- Wallet, payment-account, withdrawal, and referral states use existing Laravel APIs; no mobile database, fake member data, Laravel route, controller, schema, or production-data change was introduced.
- Android light and dark screenshots passed visual inspection. Mobile tests `54/54`, TypeScript, Android Expo export, diff check, and HTTP 200 checks for privacy, terms, and support passed.
- Source commit `83ecc53` was published directly to `origin/main`; GitHub Actions run `31487510569` passed `context-log-check`, `laravel-tests`, and `mobile-tests`.
- Operational IDs: `SES-20260811-009`, local checkpoint `CKP-20260811-012`, final checkpoint `CKP-20260811-013`, `TSK-MOB-024`, `DEC-20260811-005`, `CHG-20260811-009`, `TST-20260811-009`, and `GATE-BOTH-014`.
- M0/M1, physical-device, signed-artifact, accessibility/performance, and store-console gates remain open. App Store and Google Play remain `NOT READY`.

## 2026-08-11 Native Orders Tracking Redesign

- Rebuilt the native Orders screen from the latest blue process-guide reference using Mê Sale copy, two server-backed summary cards, exactly four status chips, native list cards, and an empty-state Home CTA.
- Unrecorded marketplace clicks remain explicitly labelled and appear only inside `Tất cả`; recorded pending, approved, and rejected filters remain server-side.
- Laravel now returns an additive recorded/unrecorded union feed with stable pagination, integer VND fields, and a dedicated approved-order cashback amount. Bot API remains recorded-order-only.
- Verification passed: mobile `54/54`, TypeScript, Laravel `66` tests / `646` assertions, diff check, staged sensitive-data scan, Android emulator visual inspection, and empty CTA routing.
- Source commit `439c1da` and context commit `c14a5ac` were fast-forwarded directly to `origin/main`; GitHub Actions run `31482816055` passed Laravel, mobile/export, context, and secret checks.
- Operational IDs: `SES-20260811-008`, `CKP-20260811-011`, `TSK-MOB-023`, `DEC-20260811-004`, `CHG-20260811-008`, `TST-20260811-008`, and `GATE-BOTH-013`.
- Production deployment, physical iOS/Android, signed artifacts, accessibility/performance matrices, and store-console evidence remain open. App Store and Google Play remain `NOT READY`.

## 2026-08-11 Home Wallet Summary

- Replaced the old promotional Home hero with the owner-approved member summary: greeting and notification action, wallet balance and withdrawal action, and three statistics for total received, pending cashback and total withdrawn.
- The cashback-link form remains immediately below the summary; `% HH ròng` is intentionally absent.
- Laravel `/account` now returns integer VND `wallet.pending_cashback`, scoped to the authenticated user and calculated server-side.
- By explicit product decision, Home displays an omitted pending field as `0đ`; the API parser remains nullable and real supplied values remain server-authoritative.
- Successful withdrawals refresh both Wallet and Home account caches.
- Verification passed: TypeScript, mobile `50/50`, Laravel `77/708`, Expo Doctor `18/18`, iOS/Android exports, Android emulator visual inspection, diff check and staged credential-pattern scan.
- Implementation commit `0a375b0` and context commit `e989475` are published on `origin/main`; GitHub Actions runs `31471558647` and `31471865183` passed.
- Operational IDs `SES-20260811-003`, `CKP-20260811-005`, `TSK-MOB-018`, `DEC-20260811-002`, `CHG-20260811-003`, and `TST-20260811-003` were synchronized uniquely; final handoff checkpoint is `CKP-20260811-006`.
- Production deployment of the additive pending field and physical-device/store evidence remain open; M0/M1 and release gates are not complete.
- Product override commit `d8263f8` is published on `origin/main`; GitHub Actions run `31472999223` passed. Operational handoff is `CKP-20260811-007`.

## 2026-08-11 Home Summary and Quick Access

- Added the server-authoritative `wallet.totalWithdrawn` value as the third compact Home statistic card labelled `Tổng đã rút` in Vietnamese UI.
- Added the approved cashback claim above the supported-platform row: `Hoàn tiền mua sắm Shopee - Tiktok Shop lên đến 15% giá trị đơn hàng`.
- Added Quick Access immediately below the cashback-link card and before the native phone walkthrough: coupons, daily check-in, usage guidance and HTTPS support.
- Coupon Quick Access handles loading, error and empty states, and measures a direct ScrollView-child anchor so a successful tap lands on the coupon content instead of the top of Home.
- Android emulator visual and interaction checks passed; the three cards and Quick Access fit without horizontal overflow.
- Local verification passed: mobile tests `51/51`, TypeScript, Expo Doctor `18/18`, diff check and staged credential-pattern scan.
- Source commit `142f265` was fast-forwarded directly to `origin/main`; GitHub Actions run `31475087577` passed Laravel, mobile/export and context/secret jobs.
- Operational IDs: `SES-20260811-005`, `CKP-20260811-008`, `TSK-MOB-020`, `CHG-20260811-005`, and `TST-20260811-005`.
- No Laravel route, API contract, database, production setting, credential or member data changed.

## 2026-08-11 Compact Home Greeting Header

- Removed the entire standalone brand/search/theme/menu bar above the Home greeting.
- Moved the existing Mesale logo inline immediately before `Chào`, while preserving the notification button and inbox route on the right.
- Kept search on the cashback action itself; theme and menu remain available through the bottom `Thêm` entry and More sheet.
- Reduced the top layout to one safe-area-aware 8dp gap and removed the duplicated account-summary top padding.
- Removed unused header imports, More-sheet hook usage, stale input focus ref and obsolete brand styles.
- Android emulator visual verification passed with no status-bar overlap or horizontal overflow.
- Local verification passed: mobile tests `51/51`, TypeScript, Expo Doctor `18/18`, diff check and staged credential-pattern scan.
- Source commit `538f543` was fast-forwarded directly to `origin/main`; GitHub Actions run `31476719324` passed Laravel, mobile/export and context/secret jobs.
- Operational IDs: `SES-20260811-006`, `CKP-20260811-009`, `TSK-MOB-021`, `CHG-20260811-006`, and `TST-20260811-006`.
- No Laravel, API, database, production setting, credential or member data changed.

## 2026-08-11 Home Ends At Phone Walkthrough

- Made `PhoneFlowDemo` the final rendered child of the Home ScrollView.
- Removed the complete Round C coupon, cashback timeline and ranking sections that previously appeared below the phone walkthrough.
- Removed their live Home observers, scroll anchors, components, icons and types; Home no longer fetches coupon or ranking data for hidden content.
- Kept the existing Quick Access layout. Because no native coupon route exists, `Săn mã` now displays an honest localized unavailable message instead of opening Blade/WebView or a fake route.
- Android emulator verification passed at the bottom of Home: only the phone walkthrough remains above the tab bar, with no trailing section or empty wrapper.
- Local verification passed: mobile tests `51/51`, TypeScript, Expo Doctor `18/18`, diff check and staged credential-pattern scan.
- Source commit `4680f70` was fast-forwarded directly to `origin/main`; GitHub Actions run `31477892613` passed Laravel, mobile/export and context/secret jobs.
- Operational IDs: `SES-20260811-007`, `CKP-20260811-010`, `TSK-MOB-022`, `CHG-20260811-007`, and `TST-20260811-007`.
- No Laravel, API contract, database, production setting, credential or member data changed.

## 2026-08-11 Claude Code Handoff

- Added root `CLAUDE.md` as the receiving agent's primary source map and operating guide.
- The handoff covers non-negotiable data/auth/security rules, Laravel/Open API architecture, Expo/mobile screen inventory, current Home walkthrough, commands, CI evidence, Git/Sheet workflow, blockers, owner inputs and prioritized next work.
- Verified repository facts: private monorepo, default branch `main`, current local/remote commit `fffa8be`, and successful CI run `31457169695`.
- Recorded the local stale-upstream risk: do not use bare `git push`; only publish a reviewed fast-forward with explicit `HEAD:main` target.
- Read-only live check on 2026-08-11: home, manifest and Open API config respond HTTP 200; privacy/terms/support redirect to the homepage; account deletion remains HTTP 404.
- No application source, database, route, middleware, package, production data or credential changed in this handoff.
- Operational IDs: `SES-20260811-002`, `CKP-20260811-004`, `TSK-MOB-017`, `CHG-20260811-002`, and `TST-20260811-002`.
- The five operational Sheet IDs were upserted with redacted content and verified to occur exactly once.

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

Keep M0/M1 open and store verdicts at `NOT READY`. Source `f9d87c9` and context `4eeafff` are published directly under `CKP-20260811-015`; runs `31494516026` and `31494964995` passed, while production deployment was not performed. Continue with staging migration/rollback rehearsal, production-engine concurrency validation, OAuth owner inputs, push transport, physical-device evidence, the staged Expo dependency upgrade, and signed-artifact/store verification. Source and tests are authoritative if any log disagrees.

No secrets, service-account information, raw HTTP responses, or member data are included in this context.
