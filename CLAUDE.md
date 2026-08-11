# Mesale Migration Handoff for Claude Code

Tài liệu này là điểm bắt đầu bắt buộc khi Claude Code tiếp quản repository. Nội dung phản ánh source, Git và evidence được kiểm tra đến ngày **2026-08-11**. Nếu tài liệu này, README, checkpoint hoặc Google Sheet mâu thuẫn với source/test/Git hiện tại thì **source, test và Git thực tế được ưu tiên**; ghi lại sự khác biệt trước khi tiếp tục.

## 1. Tóm tắt dự án

- Repository GitHub: `thichmmo/mesale-app` (private), default branch `main`.
- Đây là monorepo có chủ đích gồm Laravel website/backend và Expo/React Native mobile client.
- Production website: `https://mesale.vn`.
- Laravel và database hiện tại của mesale.vn là nguồn sự thật duy nhất cho tài khoản, đơn hàng, cashback, ví, referral, task, gift, notification và withdrawal.
- Mobile là native API client, không phải WebView, không đọc MariaDB trực tiếp và không tạo business database riêng.
- Admin, CMS, cron, scheduler, import, bot webhook, sync service và system update tiếp tục chạy trên Laravel web/server.
- M0 và M1 vẫn đang mở. Foundation và nhiều native slice đã chạy được nhưng app chưa đủ evidence để release.
- Kết luận hiện tại của cả App Store và Google Play: **NOT READY**.

## 2. Thứ tự nguồn sự thật

1. Source code, migration, route, test, Git diff và artifact thực tế.
2. GitHub commit/CI và các file Markdown đã redacted trong `docs/`.
3. Google Sheet là nhật ký vận hành chi tiết để phục hồi context.
4. GitHub Issues/Project dùng cho task, blocker và release risk.
5. README và checkpoint cũ chỉ là lịch sử; không được dùng để phủ nhận evidence mới hơn.

Các tài liệu cần đọc trước khi sửa code:

- `docs/context/CURRENT.md`
- checkpoint mới nhất được trỏ bởi `docs/context/CURRENT.md`
- `docs/migration-plan.md`
- `docs/inventory/feature-matrix.md`
- `docs/blockers/OPEN.md`
- `docs/release/RELEASE-GATES.md`
- `docs/release/APP-STORE-CURRENT-AUDIT.md`
- `docs/release/PLAY-STORE-CURRENT-AUDIT.md`
- GitHub Issues đang mở có ID liên quan.

## 3. Quy tắc không được phá vỡ

### Dữ liệu và backend

- Không tạo database business riêng cho mobile.
- Không cho mobile truy cập MariaDB trực tiếp.
- Không copy dữ liệu production sang local storage ngoài cache read-only cần thiết.
- Mọi mutation tài chính/reward phải server-authoritative trong Laravel transaction.
- Không bypass `api.enabled`, feature flag, `api.auth`, throttle, request logging hoặc idempotency middleware.
- Không gọi Blade route để lấy JSON cho mobile; thêm hoặc sửa API versioned nếu thiếu contract.
- Không tự bật Open API production, chạy production migration hoặc thay đổi production data nếu chưa có security/release review cụ thể.

### Authentication và credential

- Mobile chỉ dùng Bearer access token theo phiên do Laravel trả sau đăng nhập.
- Token được lưu bằng `expo-secure-store`, tương ứng iOS Keychain/Android Keystore.
- Request authenticated gửi `Authorization: Bearer <access_token>`.
- Logout/revoke phải thu hồi token server-side khi có thể và xóa session local.
- Personal API key dạng `sk_live_...` chỉ dành cho `/api/v1/bot` và tích hợp server-to-server; tuyệt đối không dùng hoặc nhúng trong app.
- `openapi_status` là công tắc API, không phải API key.
- OpenAI, Shopee, TikTok, Lazada, Firebase, SMTP, OAuth secret, Apple private key và các service credential phải ở server/EAS secret store, không nằm trong bundle mobile.
- Sign in with Apple là đăng nhập Apple ID, không gọi là “iCloud login”. CloudKit/iCloud không phải nguồn dữ liệu business.

### Secret và dữ liệu nhạy cảm

Không được đọc ra màn hình, ghi log, commit, push, chụp screenshot hoặc đưa vào issue/PR:

- `.env`, access token, cookie, password, private key, OAuth secret;
- Apple `.p8`, Android keystore, Firebase private credential;
- file service account Google Sheet hoặc nội dung của file đó;
- member email/phone, wallet/order data hoặc raw production response;
- affiliate, bot, SMTP, AI hoặc update/license secret.

Không đặt secret trong `EXPO_PUBLIC_*`. Chỉ API base URL, timeout và OAuth client identifier công khai được phép dùng public Expo env.

## 4. Trạng thái Git hiện tại

Snapshot ngày 2026-08-11:

- `HEAD`: `fffa8be96425f39af9e91e6f60dad936aec0cadd`.
- `origin/main`: cùng commit `fffa8be`.
- CI run `31457169695`: pass cả `context-log-check`, `laravel-tests` và `mobile-tests`.
- Checkout local vẫn tên `codex/mvp-p0-20260809`, nhưng upstream của nhánh này trỏ tới remote branch cũ. Local branch `main` cũng có thể cũ hơn `origin/main`.
- Vì vậy không dùng `git push` trần. Khi owner yêu cầu/policy hiện hành cho phép push thẳng main, dùng refspec rõ ràng: `git push origin HEAD:main`.
- Không force-push, không rewrite history, không tự merge, không xóa branch/commit/remote.
- Trước mọi thay đổi phải chạy lại `git remote -v`, `git status --short`, `git log --oneline -10`, `git fetch origin` và so sánh `HEAD` với `origin/main`.
- Nếu `origin/main` có commit mới không nằm trong local HEAD, dừng push, fetch/reconcile an toàn và test lại.

Các artifact local đang untracked tại thời điểm bàn giao và không được stage tự động:

- `docs/context/codex-prompt-20260810-006.md`
- `docs/context/codex-prompt-20260810-007.md`
- `docs/context/codex-prompt-20260810-008.md`
- `logo.png`
- `mesale-phone-flow-auto-paste-price-fixed.html`

`logo.png` và file HTML là asset/prototype tham khảo. Không dùng `git add .`; chỉ stage danh sách file đã đọc và kiểm tra.

## 5. Kiến trúc Laravel hiện tại

Stack:

- Laravel 13.8, PHP 8.3+.
- Blade frontend, Tailwind CSS 4, Vite 8.
- MariaDB production; SQLite memory dùng cho isolated CI/test.
- Laravel web session cho website và custom hashed API tokens cho mobile/Open API.
- Vietnamese và English localization.

Source map:

```text
routes/web.php                         public/member/web/auth/legal routes
routes/admin.php                       admin panel
routes/api.php                         Open API v1 và Bot API
bootstrap/app.php                      route loading, middleware aliases, scheduler
app/Http/Controllers/Api/V1/          mobile-facing controllers
app/Http/Middleware/                   API feature/auth/log/idempotency middleware
app/Models/                            domain model và relationship
app/Services/                          cashback, referral, sync, notification, OAuth, update
resources/views/                       behavior/UI reference cho native parity
resources/css/ và resources/js/        web style/interaction reference
database/migrations/                   schema và hardening migrations
database/seeders/                      baseline settings/data
lang/                                  vi/en copy
tests/                                 Laravel contract/feature/regression tests
```

Laravel giữ toàn bộ business rule cho:

- account, session, verification, 2FA và provider identity;
- cashback link, order, click, wallet và balance log;
- withdrawal, OTP và payment account;
- referral F1/F2 và commission;
- check-in, task, gift, gift code, coupon và ranking;
- notification, push token và activity log;
- admin, CMS, cron, bot, imports, sync và system update.

## 6. Open API và Bot API

### Open API cho mobile

- Production base URL dự kiến: `https://mesale.vn/api/v1/openapi`.
- Development, preview và production phải dùng URL riêng qua Expo/EAS environment.
- Group middleware ngoài cùng: `api.enabled`, `throttle:120,1`, `api.log:openapi`.
- Mỗi domain có feature flag `api.enabled:<group>`.
- Endpoint có dữ liệu member dùng `api.auth`.
- Mutation nhạy cảm có throttle riêng; reward/financial mutation dùng `Idempotency-Key` khi contract yêu cầu.

Public/pre-auth contracts hiện có:

```text
GET  /config
GET  /pages
GET  /pages/{slug}
POST /auth/register
POST /auth/login
POST /auth/oauth/google
POST /auth/oauth/apple
POST /auth/login/2fa
POST /auth/login/2fa/resend
POST /auth/verify-email
POST /auth/verify-email/resend
POST /auth/forgot-password
POST /auth/reset-password
POST /auth/logout                  authenticated
```

Authenticated domain contracts hiện có:

```text
/account, /account/referral-code, /account/profile, /account/preferences,
/account/password, /account/delete

/notifications, /notifications/unread-count, /notifications/read-all,
/notifications/{id}/read

/cashback/link
/orders, /orders/{id}
/checkin
/referrals
/balance-logs
/logs
/gifts, /gifts/redemptions, /gifts/redeem
/giftcode/redeem
/saved-products
/coupons
/ranking
/devices, /devices/register, /devices/unregister
/security và các endpoint 2FA/email OTP
/sessions, /sessions/revoke-others, /sessions/{id}/revoke
/withdrawals, /withdrawals/otp
/payment-accounts và default/delete
/tasks, /tasks/{task}/sync, /tasks/{task}/claim, /tasks/{task}/submit
```

### Bot API tách biệt

- Base: `/api/v1/bot`.
- Middleware: `bot.enabled`, throttle, `api.auth:allow_key`, `api.log:bot`.
- Dùng personal member API key cho bot/server-to-server.
- Chỉ có surface hạn chế như tạo cashback link và đọc order; không được mở withdrawal/security mutation bằng static API key.
- Mobile không gọi Bot API.

## 7. Authentication và account linking

Luồng mobile chuẩn:

```text
email/password, Google native hoặc Sign in with Apple
                    -> Laravel verify
                    -> cùng một user record mesale.vn
                    -> session Bearer access token
                    -> SecureStore
                    -> Open API authenticated requests
```

Contract token:

- Response canonical dùng `access_token`, `token_type: Bearer`, `expires_at`.
- Alias `token` chỉ là compatibility tạm thời nếu còn tồn tại; code mới ưu tiên `access_token`.
- Plaintext token chỉ trả một lần; server lưu SHA-256 hash, expiry và trạng thái revoke.
- Session restore phải kiểm tra expiry, đọc SecureStore và gọi `/account` để khôi phục user.
- API `401` chỉ xóa đúng stale local token; không được tuyên bố đã revoke server nếu request revoke không thành công.

Google native:

- App lấy ID token/authorization code bằng native Google SDK.
- Laravel verify token và trả cùng loại session token.
- Không dùng Google web session của website cho mobile.
- Cần audit duplicate provider identities trước khi thêm database uniqueness constraint.

Sign in with Apple:

- Native dùng nonce chống replay, identity token và authorization code.
- Laravel verify JWKS signature, `iss`, `aud`, `exp`, nonce và định danh bằng Apple `sub`.
- Hỗ trợ private relay email; không auto-merge chỉ vì email trùng nếu chưa có proof/linking flow an toàn.
- Apple private key luôn server-only.
- Deletion/disconnect cần reauthentication và revoke provider grant theo policy đã chọn.

Account deletion trên app phải tác động cùng tài khoản website nhưng vẫn tuân thủ retention/anonymization bắt buộc cho financial ledger. Không hard-delete ledger một cách mù quáng.

## 8. Kiến trúc Expo/React Native

Stack hiện tại:

- Expo SDK 53, React Native 0.79.6, React 19, TypeScript 5.8.
- Expo Router 5, typed routes.
- TanStack Query cho server state/cache.
- Zustand chỉ cho UI state nhỏ, ví dụ More sheet.
- `expo-secure-store` cho session và theme preference.
- `expo-apple-authentication`, `@react-native-google-signin/google-signin`.
- Lucide React Native và `react-native-svg` cho icon.
- New Architecture bật.
- Bundle/application ID: `vn.mesale.app`; scheme: `mesale`.

Directory map:

```text
mobile/app/                      Expo Router screens/layouts
mobile/src/api/                  typed client, envelope, pagination, idempotency
mobile/src/auth/                 AuthProvider, SecureStore session, route gates
mobile/src/components/           shared async/form/tab components
mobile/src/config/               public runtime environment validation
mobile/src/features/account/     profile, password, preferences, security, sessions, deletion
mobile/src/features/auth/        native OAuth và auth validation/components
mobile/src/features/earn/        referrals, check-in, tasks, gifts, gift code
mobile/src/features/home/        home API/UI và PhoneFlowDemo
mobile/src/features/legal/       legal/support links
mobile/src/features/navigation/  More sheet/store
mobile/src/features/notifications/
mobile/src/features/wallet/      wallet, orders, withdrawals, payment accounts
mobile/src/i18n/                 locale resolution
mobile/src/theme/                tokens, provider, persisted preference
mobile/tests/                    contract/source-oriented tests
```

API client behavior:

- Base URL bắt buộc từ `EXPO_PUBLIC_API_BASE_URL`.
- HTTPS bắt buộc ngoài local development.
- Timeout mặc định 15 giây, có cancellation và normalized `ApiError`.
- Error giữ status, machine code, request ID và field errors.
- Auth header lấy từ session SecureStore.
- `401` invalidates đúng session đã dùng.

EAS profiles đã có: `development`, `preview`, `production`. Giá trị môi trường nằm trong EAS environments, không hardcode trong `eas.json` hoặc business logic.

## 9. Navigation và native screen inventory

Bottom navigation hiện tại:

- `Trang chủ` -> Home.
- `Ví` -> Wallet.
- `Đơn hàng` -> Orders alias.
- `Rút tiền` -> Withdraw alias.
- `Thêm` -> mở More sheet, không phải navigation route thông thường.

Earn, Inbox và Account vẫn là hidden routes (`href: null`) và được mở từ More/deep link. Không tự ý đổi lại thành năm tab cũ.

Screen đã có trong source:

- Auth: login, register, forgot/reset password, verify email, OTP/2FA, post-registration referral apply/skip.
- Home: account/config bootstrap, cashback link, product result, marketplace handoff, coupons, ranking.
- Wallet: dashboard, order list/detail, balance logs, withdrawal list/create/OTP, payment accounts.
- Earn: referral F1/F2, daily check-in, tasks/sync/claim/manual submit, gifts, redemption history, gift code.
- Inbox: notification pagination/filter/read/read-all.
- Account: dashboard, profile, password, language/preferences, security/2FA/email OTP, sessions/revoke, deletion.
- Legal links/components exist; blog/category/tag/feed/article/comments/likes/shares chưa hoàn chỉnh cho native release.

Mỗi screen chỉ được coi là hoàn thành khi có happy, loading, empty, validation, server error, offline, retry, expired session, disabled feature, screenshot evidence, tests và iOS/Android verification.

## 10. Home và PhoneFlowDemo hiện tại

Home đã được chỉnh gần nhất để:

- mặc định deterministic tiếng Việt; English chỉ khi account preference thật là `en`;
- hỗ trợ light/dark mode theo token hiện tại;
- có Mesale logo/hero, paste link, help/caution, theme, notification và More controls;
- gọi live coupon và ranking API nếu feature được bật;
- không render blog block khi chưa có contract bật;
- hiển thị native `PhoneFlowDemo.tsx` dưới nội dung Home.

`PhoneFlowDemo.tsx` là walkthrough native tự chạy, không gọi API và không ghi database. Nó mô phỏng paste link, product/cashback, Shopee checkout, order success và tracking trong khung iPhone Pro Max-style có Dynamic Island.

Lưu ý bắt buộc:

- Demo đang dùng order/cashback amount hardcoded.
- Nhãn “minh họa” từng bị gỡ theo yêu cầu visual của owner.
- GitHub Issue #26 / `BLK-UI-002` yêu cầu disclosure được owner duyệt để tránh bị hiểu là dữ liệu thật hoặc cam kết thu nhập.
- Không nối demo này với mutation production và không dùng fake values như dữ liệu account thật.
- Page Builder order/toggle vẫn chưa có mobile config contract đầy đủ; Issue #24 theo dõi nguy cơ Home drift với website.

## 11. Locale, theme và visual parity

- Locale mặc định là Vietnamese.
- English chỉ kích hoạt khi authenticated account preference là `en`.
- Theme hỗ trợ `system`, `light`, `dark`; preference được persist bằng SecureStore.
- UI native phải rebuild từ Blade, Tailwind/CSS, asset, font, color, spacing, radius, shadow, modal, bottom sheet, loading/empty/error/disabled/validation state.
- Không dùng WebView để giả parity và không tự modernize/simplify layout.
- Nếu native platform bắt buộc khác web, ghi chính xác khác biệt, screenshot, lý do và xin owner duyệt trước khi đổi thiết kế.

Screenshot matrix tối thiểu:

- iPhone nhỏ và iPhone Pro Max;
- Android nhỏ và Android lớn;
- light/dark;
- font scaling;
- safe area và keyboard mở;
- loading, empty, error, offline;
- authenticated, financial và destructive flows.

Orange/white primary action contrast đã được ghi nhận khoảng `2.80:1`; không tự đổi brand color nếu chưa có quyết định parity/accessibility của owner.

## 12. Cấu hình môi trường và chạy local

Tạo `mobile/.env.local` từ `mobile/.env.example`; không commit file local.

Public variables hiện dùng:

```text
EXPO_PUBLIC_API_BASE_URL
EXPO_PUBLIC_API_TIMEOUT_MS
EXPO_PUBLIC_GOOGLE_WEB_CLIENT_ID
EXPO_PUBLIC_GOOGLE_IOS_CLIENT_ID
EXPO_PUBLIC_GOOGLE_IOS_URL_SCHEME
```

Không dùng `localhost` làm API URL cho Android emulator hoặc physical phone:

- Android emulator tới Laravel host machine: `http://10.0.2.2:<port>/api/v1/openapi` trong local development.
- Physical phone: dùng HTTPS/LAN endpoint reachable từ thiết bị.
- Preview/production: dùng EAS environment và HTTPS endpoint tương ứng.

Mobile commands:

```powershell
cd mobile
npm ci
npm run typecheck
npm test
npx expo-doctor
npx expo start
```

Laravel commands:

```powershell
composer install
php artisan test
php artisan serve
```

Trên máy bàn giao, PHP có thể không nằm trên PATH. Portable runtime đã từng được dùng qua `%LOCALAPPDATA%\cashback-runtime\php-8.4\php.exe`; kiểm tra tồn tại trước khi gọi, không hardcode sang máy khác.

Không chạy production EAS build, production migration hoặc mutation tài chính chỉ để kiểm tra local UI.

## 13. Verification và CI

Latest verified evidence:

- GitHub Actions run `31457169695` tại `fffa8be`: success toàn bộ ba job.
- Mobile TypeScript: pass.
- Mobile tests: `46/46` pass.
- Expo Doctor: `18/18` pass.
- iOS/Android Expo exports: pass; bundle được ghi nhận khoảng 5.33 MB mỗi platform.
- Latest recorded Laravel baseline: `77 tests / 702 assertions`.
- Android emulator walkthrough đã được kiểm tra; physical-device matrix vẫn thiếu.
- `npm audit --omit=dev`: 22 advisory, gồm 7 high và 15 moderate.

Không chạy `npm audit fix --force`. Dependency remediation phải upgrade Expo tuần tự SDK 54 -> 55 -> 56 -> 57 theo `docs/release/MOBILE-DEPENDENCY-UPGRADE-PLAN.md`, test và native build ở từng bước.

CI chuẩn:

```text
PHP 8.4 + SQLite memory -> php artisan test
Node 22 -> npm ci -> npm test -> npm run typecheck
        -> npx expo-doctor
        -> npx expo export --platform all --output-dir dist
context/secret scan -> required docs, credential filenames, secret patterns
```

Trước commit/push phải chạy tối thiểu:

```powershell
git diff --check
git status --short
cd mobile
npm test
npm run typecheck
npx expo-doctor
```

Chạy Laravel tests khi thay backend/API/migration. Chạy export/build tương xứng khi thay config, dependency, router hoặc native package. Không gọi milestone/release gate complete chỉ vì typecheck hoặc export pass.

## 14. Live production snapshot

Read-only check ngày 2026-08-11:

- `https://mesale.vn/`: HTTP 200.
- `https://mesale.vn/manifest.json`: HTTP 200.
- `https://mesale.vn/api/v1/openapi/config`: HTTP 200.
- `/privacy`, `/terms`, `/support`: redirect về homepage; final HTTP 200 nhưng chưa phải evidence có legal/support resource đúng nội dung.
- `/account-deletion`: HTTP 404 dù source có groundwork route/resource; đây là deployment/routing blocker.

HTTP 200 của `/config` chỉ chứng minh endpoint đang reachable tại thời điểm check. Nó không chứng minh staging, auth security, monitoring, rollback, feature rollout hoặc store readiness đã hoàn tất. Không ghi raw response production vào repo.

## 15. Store/release blocker đang mở

App Store và Google Play vẫn **NOT READY** vì còn thiếu:

- reviewer-reachable staging/API với security, request ID, throttle, logging, monitoring và rollback evidence;
- Apple Team/Services/Key configuration, Google OAuth client configuration và physical-device provider tests;
- duplicate provider identity audit và safe account-linking rollout;
- owner-approved account deletion, financial-ledger retention/anonymization và provider revoke behavior;
- public Privacy/Terms/Support/Account Deletion resource đúng nội dung và URL;
- push transport/configuration, registration/unregistration và deep-link evidence;
- AASA/Associated Domains và Asset Links deployment evidence;
- physical iOS/Android screenshot matrix, accessibility, offline/retry/expired-session và performance baseline;
- signed IPA/AAB, App Store Connect/Play Console declarations, Play App Signing và Android 16 KB compatibility scan;
- App Privacy, Privacy Manifest/Required Reason reconciliation, Data Safety và Financial Features declaration;
- review accounts, support process, metadata và truthful cashback/reward copy;
- Expo SDK/dependency remediation;
- disclosure cho hardcoded cashback walkthrough;
- UGC moderation/report/block/contact trước khi expose comments/likes/shares.

Marketplace handoff tới Shopee/TikTok Shop/Lazada là hàng hóa vật lý, không dùng IAP. Nếu sau này bán digital subscription/content/feature, phải review StoreKit/Play Billing trước khi triển khai.

Các GitHub Issues quan trọng đang mở gồm #7, #9, #10, #13, #18, #20, #21, #24 và #26. Kiểm tra issue state thực tế trước khi làm; không đóng issue nếu chưa có reproducible evidence.

## 16. Input vẫn cần từ owner

- Apple Team ID, Services ID, Key ID/private key và redirect/audience configuration.
- Google OAuth client IDs cho đúng platform/environment.
- Firebase/APNs/push architecture và credential qua secret store.
- EAS project/organization và signing ownership.
- Staging API/reviewer environment.
- Review accounts không chứa production member data nhạy cảm.
- Owner/legal approval cho Privacy, Terms, Support, account deletion và retention wording.
- App Store/Play metadata, declarations, support contact và rollout approval.

Không ghi các giá trị thật vào file này. Lấy chúng qua kênh secret/owner-approved ngoài Git.

## 17. Workflow context, Sheet và GitHub

- Google Sheet là operational log chi tiết; không ghi URL, spreadsheet ID hoặc local credential path vào tracked docs.
- Credential Sheet chỉ được đọc local để xác thực API; không copy vào repository.
- GitHub Markdown chỉ chứa log kỹ thuật đã redacted.
- Stable IDs: `SES-`, `CKP-`, `TSK-MOB-`, `DEC-`, `CHG-`, `TST-`, `BLK-`, `GATE-` theo timezone `Asia/Bangkok`.
- Append hoặc update đúng row theo ID; không tạo ID trùng.
- Ghi checkpoint khi bắt đầu/kết thúc session, milestone, blocker, handoff hoặc trước nguy cơ mất context.
- `docs/context/CURRENT.md` luôn trỏ tới checkpoint mới nhất.

Owner đã yêu cầu các phần code độc lập phải giao cho subagent; primary agent chỉ đạo, review, tích hợp, test và chịu trách nhiệm kết quả. Subagent không tự push/merge.

Chỉ dẫn vận hành mới nhất của owner là sau khi thay đổi đã được kiểm tra thì push trực tiếp lên `origin/main`, không đưa qua branch/PR khác. Vì checkout local có upstream cũ, dùng explicit refspec và chỉ push fast-forward:

```powershell
git fetch origin
git merge-base --is-ancestor origin/main HEAD
git push origin HEAD:main
```

Nếu lệnh ancestor không thành công hoặc remote thay đổi trong lúc làm, không force; reconcile và test lại. Commit chỉ stage file đã audit. Sau push, kiểm tra CI và cập nhật checkpoint/Sheet đã redacted.

## 18. Việc Claude Code nên làm tiếp theo

Theo thứ tự ưu tiên:

1. Re-read Git/source/current checkpoint; sửa các statement cũ trong README/docs chỉ khi có evidence mới và giữ lịch sử checkpoint nguyên vẹn.
2. Chốt M0 staging/Open API readiness: contract, flags, auth, request IDs, rate limit, logging, monitoring, rollback và reviewer environment.
3. Deploy/verify đúng các public Privacy, Terms, Support và Account Deletion resources; không chấp nhận redirect về homepage như hoàn thành.
4. Audit provider identity duplicates, hoàn thiện Google/Apple credentials và account-linking rollout trên staging/device.
5. Chốt push transport và test register/unregister/deep link trên physical devices.
6. Giải quyết Issue #26 bằng disclosure truthful được owner duyệt mà vẫn giữ visual parity; không tự hứa mức hoàn tiền.
7. Thiết kế mobile config contract cho Page Builder order/toggles để Home không drift với website.
8. Upgrade Expo dependency tuần tự, kiểm tra test/doctor/export/native build ở mỗi SDK.
9. Thu thập screenshot/accessibility/offline/performance evidence trên iOS/Android vật lý.
10. Tạo signed release artifacts và hoàn thiện store-console evidence trước khi đổi verdict.

## 19. Definition of done

Một task/milestone chỉ hoàn thành khi:

- source và API contract đúng;
- tests liên quan pass;
- loading/empty/validation/server error/offline/retry/expired-session/disabled states được xử lý;
- mutation retry không gây duplicate financial/reward side effect;
- iOS và Android đã được xác minh tương xứng;
- screenshot/artifact/evidence được lưu ở nơi an toàn, không chứa PII/secret;
- blocker và release gate được cập nhật đúng;
- GitHub Markdown và Google Sheet đã redacted đồng bộ;
- commit/push fast-forward thành công và CI xanh;
- không tuyên bố release-ready trước khi cả hai store audit pass.

## 20. Checklist đầu phiên cho Claude Code

```text
[ ] Đọc CLAUDE.md và docs/context/CURRENT.md
[ ] Đọc checkpoint mới nhất và Issues liên quan
[ ] git remote -v / git status --short / git log --oneline -10
[ ] git fetch origin và so sánh HEAD với origin/main
[ ] Không stage 5 untracked artifact đã nêu nếu task không yêu cầu
[ ] Xác nhận API/environment không chứa production secret
[ ] Tạo Session/Task/Checkpoint ID không trùng
[ ] Giao bounded code subtasks cho subagent nếu có code
[ ] Primary review diff và chạy test phù hợp
[ ] Stage explicit files, secret scan, commit
[ ] Push explicit HEAD:main chỉ khi fast-forward
[ ] Theo dõi CI và đồng bộ checkpoint/Sheet đã redacted
```

Mục tiêu dài hạn không thay đổi: tạo app Mesale native production-ready cho iOS và Android với visual/behavior parity cao, dùng chung account và dữ liệu mesale.vn qua Laravel Open API an toàn, đồng thời đáp ứng đầy đủ App Store và Google Play trước release.
