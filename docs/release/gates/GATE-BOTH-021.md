# GATE-BOTH-021 - Native social login rollout

- Session: `SES-20260812-016`
- Checkpoint: `CKP-20260812-022`
- Status: `PARTIAL / BLOCKED`

## Passed

- Native Google UI and Android Google Play Services activity.
- Laravel Google identity verification and canonical Bearer contract tests.
- Production UNIQUE identity invariant, default-off rollout, invalid-token canary, recursive token redaction, and live config evidence.

## Blocked

- Real-account provider-to-Bearer production canary.
- Authenticated linking for existing password-only accounts with the same provider email.
- iOS Google Client ID/reversed scheme and signed-device evidence.
- Apple Team/Key/Services ID configuration, production endpoint rollout, revocation canary, and signed-device evidence.
- Apple credential-state polling for revoked long-lived device sessions.
- App Store/Google Play console declarations and signed release artifacts.
