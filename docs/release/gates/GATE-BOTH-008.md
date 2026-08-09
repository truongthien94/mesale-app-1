# GATE-BOTH-008 - Round 5 Referral Prompt Gate

- Date: 2026-08-10
- Session: `SES-20260810-002`
- Task: `TSK-MOB-012`
- Status: partial / blocked for release

## Passed Local Evidence

- Post-registration referral apply/skip is implemented natively and server-authoritative.
- Existing users are backfilled as decided; invalid and same-IP codes remain retryable.
- Auth continuation priority is preserved for email verification and 2FA.
- PHPUnit, mobile tests, TypeScript, Expo Doctor, both platform exports, lint, diff, and secret checks pass locally.

## Blocking Evidence

- Production Open API remains disabled (`503 API_DISABLED`); staging and activation review are missing.
- Native OAuth credentials/device evidence, physical-device screenshots, signed artifacts, push configuration, and store-console declarations remain unavailable.
- The dependency audit remains `22` advisories (`7 high`, `15 moderate`) with the staged SDK upgrade plan still required.

## Verdict

`NOT READY` for both stores. This round closes the referral onboarding contract gap only; it does not complete M0, M1, or release readiness.
