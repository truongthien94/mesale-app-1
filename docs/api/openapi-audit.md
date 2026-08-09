# Laravel Open API v1 Audit

Status: M0 audit plus local correction evidence; no production runtime change

Date: 2026-08-09
Timezone: Asia/Bangkok
Repository scope: `<workspace>`
API target: `https://mesale.vn/api/v1/openapi`

## Executive findings

- The Open API is routed below `/api/v1/openapi` and is protected by the global `api.enabled`, `throttle:120,1`, and `api.log:openapi` middleware. Authenticated routes additionally use `api.auth`.
- The live `GET /api/v1/openapi/config` endpoint currently returns HTTP `503` with code `API_DISABLED`. This is an M0 release blocker; do not bypass the middleware or enable it directly in production.
- Password login, registration, email verification, password reset, 2FA and device/session token revocation exist. Native Google and Apple OAuth exchange endpoints do not exist in the current route/controller set.
- At audit time, `AuthController::respondWithToken()` returned only `data.token`. The local M0 implementation now adds canonical `data.access_token`, keeps a temporary equal `data.token` alias, and exposes `expires_at`; this is not deployed while production Open API remains disabled.
- Session tokens are random plaintext values returned once and SHA-256 hashes are stored in `api_tokens`. Personal `users.api_token` lookup is only available under Bot API `api.auth:allow_key`; it must never be used by the mobile client.
- The local P0 correction serializes audited member VND amounts as integers while keeping percentages/rates decimal; staging and deployment verification are still required before financial screens are released.
- List endpoints still paginate inconsistently. Withdrawal creation and gift redemption now require replay-safe `Idempotency-Key` handling locally, while other retryable mutations and the shared error/request-ID contract remain open.

## Middleware and credential boundary

### Open API

`routes/api.php` defines:

```text
/api/v1/openapi/*
  api.enabled
  throttle:120,1
  api.log:openapi
```

`ApiEndpointEnabled` checks `Setting::getVal('openapi_status', '0')`. When not equal to `1`, it returns:

```json
{
  "success": false,
  "code": "API_DISABLED",
  "message": "..."
}
```

with HTTP `503`. Group middleware checks `openapi_{group}_status` and returns HTTP `403`, code `ENDPOINT_DISABLED` when disabled.

`ApiAuthenticate` reads `Authorization: Bearer <credential>`, hashes the credential with SHA-256, resolves a valid `ApiToken`, checks expiration and `users.status = active`, and attaches the user plus token to the request. Missing and invalid credentials return HTTP `401` with `UNAUTHENTICATED` or `INVALID_TOKEN`; inactive users return HTTP `403` with `ACCOUNT_INACTIVE`.

### Bot API (separate; not mobile auth)

`/api/v1/bot/*` uses `bot.enabled`, `throttle:120,1`, `api.auth:allow_key`, and `api.log:bot`. In `allow_key` mode, the middleware may also read `api_key`/`api_token` request fields and compare them with the member's static `users.api_token`. This credential is for Bot/server-to-server integrations only. Never embed, expose, or fallback to this key in Expo/React Native.

## Response envelope

`ApiController` provides the common shape:

```json
{ "success": true, "message": "optional", "data": {} }
```

and:

```json
{ "success": false, "message": "...", "code": "OPTIONAL", "errors": {} }
```

Laravel's framework validation responses may still occur outside controllers/middleware, so M0 should add a global API exception/validation adapter that preserves this envelope and emits a request/correlation ID without leaking secrets.

## Existing endpoint inventory

All paths below are relative to `/api/v1/openapi` unless marked Bot API. Auth column: `public` means no Bearer token; `Bearer` means `api.auth` is required.

| Group | Method and path | Auth | Current contract notes |
|---|---|---|---|
| Config | `GET /config` | public | Site/theme/cashback/withdraw/referral/feature flags, app update metadata, active banners. Live currently disabled (`503 API_DISABLED`). |
| Pages | `GET /pages` | public | Published static page index (`slug`, `title`, order, updated time). |
| Pages | `GET /pages/{slug}` | public | Static page detail; slug is unconstrained string. |
| Auth | `POST /auth/register` | public | `email`, `password`, `password_confirmation`; optional `name`, `phone`, `referral_code`, `device_name`; returns token envelope, or verification-required response. |
| Auth | `POST /auth/login` | public | `email` accepts email or normalized phone; `password`; optional `device_name`; may return 2FA challenge instead of token. |
| Auth | `POST /auth/login/2fa` | public | `challenge_token`, optional `google2fa_code`, `email_otp_code`, `device_name`; returns token envelope on success. |
| Auth | `POST /auth/login/2fa/resend` | public | `challenge_token`; resends email OTP when challenge includes email OTP. |
| Auth | `POST /auth/verify-email` | public | `email`, six-digit `otp_code`, optional `device_name`; activates account and returns token. |
| Auth | `POST /auth/verify-email/resend` | public | `email`; anti-enumeration response; may send OTP. |
| Auth | `POST /auth/forgot-password` | public | `email`; anti-enumeration response, sends web reset link. |
| Auth | `POST /auth/reset-password` | public | `token`, `email`, `password`, `password_confirmation`; resets password and revokes tokens. |
| Auth | `POST /auth/logout` | Bearer | Deletes only the current `ApiToken`; idempotent success when no token model is attached. |
| Account | `GET /account` | Bearer | Profile, wallet totals, status and aggregate order/referral/withdrawal stats. |
| Account | `POST /account/profile` | Bearer | Required `name`; optional unique normalized `phone`. |
| Account | `POST /account/password` | Bearer | `current_password`, `password`, `password_confirmation`; revokes other sessions. |
| Account | `POST /account/delete` | Bearer | Requires server feature flag and password; permanently deletes the user and related records. Store compliance also needs a public web deletion resource. |
| Notifications | `GET /notifications` | Bearer | Filters `type`, `filter`; page/per_page max 50; returns notification items and pagination. |
| Notifications | `GET /notifications/unread-count` | Bearer | Unread count. |
| Notifications | `POST /notifications/read-all` | Bearer | Marks all current-user notifications read. |
| Notifications | `POST /notifications/{id}/read` | Bearer | Marks one notification read. |
| Cashback | `POST /cashback/link` | Bearer | `url`; validates SSRF and allowlisted Shopee/TikTok/Lazada domains; returns `trans_id`, platform, product data, estimated amounts and affiliate URL. |
| Orders | `GET /orders` | Bearer | Filters `status`, `platform`, `search`; page/per_page max 50. |
| Orders | `GET /orders/{id}` | Bearer | Numeric member-owned order detail. |
| Check-in | `GET /checkin` | Bearer | Today's status, configured reward and paginated history (fixed 10 per page). |
| Check-in | `POST /checkin` | Bearer | Daily check-in mutation; server-calculates reward and balance. |
| Referrals | `GET /referrals` | Bearer | Summary plus commission list; filters `level`, `status`; page/per_page max 50. |
| Wallet logs | `GET /balance-logs` | Bearer | Filters `type`, `search`; page/per_page max 50; audited VND response fields are integers in the local correction. |
| Activity logs | `GET /logs` | Bearer | Filters `search`; page/per_page max 50. |
| Gifts | `GET /gifts` | Bearer | Filters `search`, `tag`, `type`, `sort`; page/per_page max 50. |
| Gifts | `GET /gifts/redemptions` | Bearer | Filters `search`, `status`; page/per_page max 50. |
| Gifts | `POST /gifts/redeem` | Bearer | Gift redemption mutation, throttle 20/min; local route requires `Idempotency-Key` and supports replay/conflict responses. |
| Gift code | `POST /giftcode/redeem` | Bearer | Gift-code mutation, throttle 10/min; no idempotency key. |
| Saved products | `GET /saved-products` | Bearer | page/per_page max 50. |
| Saved products | `POST /saved-products` | Bearer | Product payload validation; throttle 30/min. |
| Saved products | `DELETE /saved-products/{id}` | Bearer | Numeric member-owned saved product. |
| Coupons | `GET /coupons` | Bearer | Filters `platform`, `category`, `search`; page/per_page max 50. |
| Ranking | `GET /ranking` | Bearer | Ranking type/limit data; bounded query, no pagination. |
| Push | `GET /devices` | Bearer | Registered device metadata. |
| Push | `POST /devices/register` | Bearer | `token`, optional `platform` (`android`, `ios`, `web`) and `device_name`; throttle 30/min; token value is stored server-side. |
| Push | `POST /devices/unregister` | Bearer | `token`; throttle 30/min. |
| Security | `GET /security` | Bearer | Current 2FA/email OTP status. |
| Security | `POST /security/2fa/setup` | Bearer | Creates encrypted Google Authenticator secret/QR payload. |
| Security | `POST /security/2fa/enable` | Bearer | Six-digit `code`; throttle 10/min. |
| Security | `POST /security/2fa/disable` | Bearer | Current password/2FA code according to controller validation. |
| Security | `POST /security/email-otp/send` | Bearer | Sends email OTP; throttle 5/min. |
| Security | `POST /security/email-otp/enable` | Bearer | Six-digit `otp_code`. |
| Security | `POST /security/email-otp/disable` | Bearer | Password/OTP validation. |
| Sessions | `GET /sessions` | Bearer | Lists hashed-token sessions as device metadata and marks current session. |
| Sessions | `POST /sessions/revoke-others` | Bearer | Deletes all other sessions; mutation currently non-idempotency-keyed. |
| Sessions | `POST /sessions/{id}/revoke` | Bearer | Revokes another numeric session; rejects current session. |
| Withdrawals | `GET /withdrawals` | Bearer | `status` filter and page/per_page max 50; returns amount, fee, net amount and payment details. |
| Withdrawals | `POST /withdrawals/otp` | Bearer | Sends email OTP when configured; throttle 3/min. |
| Withdrawals | `POST /withdrawals` | Bearer | Creates financial withdrawal; validates amount/payment account/optional OTP; local route requires `Idempotency-Key` and stores replay-safe results around the transactional balance lock. |
| Payment accounts | `GET /payment-accounts` | Bearer | Lists saved bank/wallet accounts. |
| Payment accounts | `POST /payment-accounts` | Bearer | Creates account; throttle 20/min. |
| Payment accounts | `POST /payment-accounts/{id}/default` | Bearer | Sets member-owned account default. |
| Payment accounts | `DELETE /payment-accounts/{id}` | Bearer | Deletes member-owned account. |
| Tasks | `GET /tasks` | Bearer | Task list and progress. |
| Tasks | `GET /tasks/{task}/sync` | Bearer | Syncs task progress; throttle 30/min. |
| Tasks | `POST /tasks/{task}/claim` | Bearer | Claims reward; throttle 20/min; financial mutation without idempotency key. |
| Tasks | `POST /tasks/{task}/submit` | Bearer | Manual submission with optional `note` max 500; throttle 10/min. |

### Separate Bot API

| Method and path | Credential | Notes |
|---|---|---|
| `POST /api/v1/bot/cashback/link` | Personal API key or Bearer session | Server-to-server Bot integration only; same URL/domain safeguards as mobile link creation. |
| `GET /api/v1/bot/orders` | Personal API key or Bearer session | Bot-specific response hides internal numeric ID and uses marketplace order identifiers. |
| `GET /api/v1/bot/orders/{order_id}` | Personal API key or Bearer session | External order-id lookup. |

## Authentication response discrepancy

Current password/register/2FA/verification success response is equivalent to:

```json
{
  "success": true,
  "message": "...",
  "data": {
    "token": "<one-time plaintext session token>",
    "token_type": "Bearer",
    "user": {
      "id": 123,
      "name": "redacted",
      "email": "redacted",
      "phone": null,
      "balance": 0.0,
      "total_cashback": 0.0,
      "total_referral_earned": 0.0,
      "total_withdrawn": 0.0,
      "referral_code": "redacted",
      "status": "active",
      "email_verified": true,
      "created_at": "ISO-8601"
    }
  }
}
```

M0 mobile contract decision: `access_token` is canonical. The local implementation retains `token` as a documented temporary alias and never interprets it as a personal API key. Mobile stores only this session token in Keychain/Keystore-backed storage.

## Error and retry behavior

Observed/implemented codes include `API_DISABLED`, `ENDPOINT_DISABLED`, `UNAUTHENTICATED`, `INVALID_TOKEN`, `ACCOUNT_INACTIVE`, `VALIDATION_ERROR`, `TOO_MANY_REQUESTS`, `INVALID_CREDENTIALS`, `OTP_INVALID`, `INVALID_CHALLENGE`, `WITHDRAW_FAILED`, `WITHDRAW_DISABLED`, and feature/platform-specific codes. The mobile client must treat HTTP `401` as session expiry, `503 API_DISABLED` as a server readiness/maintenance state, and `429` as retry-after/backoff.

No shared `request_id`/correlation ID contract exists. The local P0 implementation scopes hashed idempotency keys by user and operation, hashes canonical request payloads, encrypts stored replay responses, and covers withdrawal creation plus gift redemption. Retention/pruning and coverage for gift codes, task claims, check-in, payment-account writes, and other retryable mutations remain required.

## M0 remaining deployment and contract work

1. Provide isolated staging and a documented health/config contract; keep production `openapi_status` disabled until security review and smoke tests pass.
2. Deploy and verify the local auth normalization (`access_token`, temporary `token` alias, `token_type`, `expires_at`, `user`) through staging before production activation.
3. Add server-side Google credential exchange and Apple Sign in with Apple exchange. Verify issuer, audience, expiry, signature/JWKS, nonce and Apple `sub`; link existing users only through verified account-linking flow.
4. Standardize pagination (`items` + `pagination` with stable page/per-page metadata) and validation/error envelopes, including framework exceptions.
5. Deploy and verify the local integer-VND normalization across audited member endpoints; extend the same versioned policy to any remaining or newly added money fields while keeping percentages/rates decimal.
6. Deploy and verify withdrawal/gift idempotency, define retention/pruning, then extend replay-safe contracts to gift-code redemption, task claim, check-in and other retryable mutations with duplicate/concurrent tests.
7. Expose missing member data needed for parity: home/page-builder blocks, dashboard savings chart and created links, blog/feed/search/comments/likes/shares, language/currency preferences, bot unlink, and any missing marketplace-specific filters including Lazada.
8. Add privacy/support/account-deletion public resources and universal-link files (`apple-app-site-association`, `assetlinks.json`) before store gates.

## Safety notes

- This report contains no production tokens, cookies, member email, order, wallet or secret values.
- No production setting was changed and no database/API middleware was bypassed.
- The local repository currently has no commit history; this report is intentionally uncommitted until the parent agent completes repository setup and review.
