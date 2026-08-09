# Release Gates

Current verdicts:

- [App Store current-state audit](APP-STORE-CURRENT-AUDIT.md): `NOT READY`
- [Google Play current-state audit](PLAY-STORE-CURRENT-AUDIT.md): `NOT READY`
- M0 and M1 remain open; passing local foundation checks is not milestone completion evidence.
- Production Open API remains disabled and must not be bypassed.
- Local evidence source commit: `e916d58`; Laravel `65` tests / `625` assertions, mobile `30/30` contract tests, TypeScript typecheck, and Expo Doctor `18/18` passed. Apple-linked deletion now requires fresh Apple reauthentication and provider revocation, saved payout destinations are globally unique, and referral counterparty ledgers survive referrer deletion locally.

An ID is complete only when source, tests, generated artifact, device behavior, and store-console evidence agree. Local checks do not substitute for staging, remote CI, signed artifact, or device evidence.

| Gate ID | Status | Requirement | Current evidence / missing proof |
| --- | --- | --- | --- |
| `GATE-IOS-001` | Blocked | Sign in with Apple when Google/social login is offered | Laravel Apple exchange and verification are implemented with flags OFF; native Apple/Google client packages, owner credentials, staging test, and review evidence are missing. |
| `GATE-IOS-002` | Blocked | In-app account deletion and public deletion resource | Native deletion UI and server-side Apple reauthentication/revocation policy exist locally. Public deletion URL, device/provider evidence, wider ledger retention policy, retained-data disclosure, and store-review evidence are still missing. |
| `GATE-IOS-003` | Blocked | Privacy Policy, App Privacy, Privacy Manifest, Required Reason APIs, permissions, and ATS | The public privacy page is reachable and source HTTPS/ATS safeguards exist, but in-app legal links, a dedicated public support/deletion resource, app Privacy Manifest, generated `Info.plist`/archive inspection, and declarations are incomplete. |
| `GATE-IOS-004` | Open | UGC filtering, report, block, and support contact if comments are exposed | Comments/likes/shares are not yet released. Moderation, report/block/contact controls must be verified before UGC exposure. |
| `GATE-IOS-005` | Blocked | Native utility, universal links, metadata, screenshots, and reviewer resources | Native MVP tabs/screens exist, but no device screenshots, AASA evidence, signed build, metadata, push setup, or review account is available. |
| `GATE-AND-001` | Blocked | Data Safety and Financial Features declarations | API/SDK data inventory and Play Console declarations are incomplete; wallet/withdrawal scope requires an explicit Financial Features review. |
| `GATE-AND-002` | Blocked | In-app deletion plus public web deletion URL | Native deletion UI plus local Apple disconnect and referral-ledger safeguards exist. Public URL, device evidence, wider retention policy/disclosure, and Console evidence are still missing. |
| `GATE-AND-003` | Blocked | Submission-date target API, merged manifest, and cleartext behavior | No signed production artifact exists. Target API (API 35/36 deadline), merged manifest, and network-security evidence must be inspected at build time. |
| `GATE-AND-004` | Blocked | Production AAB, Play App Signing, 16 KB compatibility, App Links, and rollout | No signed AAB, Play App Signing evidence, native-library 16 KB scan, verified `assetlinks.json`, or staged rollout evidence exists. |
| `GATE-BOTH-001` | Open | Physical-goods payment classification and truthful cashback claims | Marketplace handoff is native and concerns physical goods; copy, terms, attribution, and end-to-end evidence remain unverified. |
| `GATE-BOTH-002` | Partial | Minimal permissions, HTTPS, credential hygiene, and dependency/test quality | Local source checks, 65/625 Laravel tests, 30/30 mobile tests, typecheck, Expo Doctor 18/18, exports, and secret hygiene pass. Apple deletion, global payout uniqueness, referral-ledger preservation, strict replay, token revocation, and payment-log masking pass locally; 22 npm advisories, remote CI, signed artifacts, and device checks remain open. |
| `GATE-BOTH-003` | Open | No fake earnings, hidden features, incentivized reviews, or dynamic native code | No prohibited behavior is present in the current source; complete product, metadata, remote-config, and reviewer-path audit remains open. |

## Required Release Evidence

- Reviewer-reachable staging or production API with feature flags, auth, throttle, logging, request IDs, rollback, and monitoring verified.
- Native Google and Sign in with Apple clients, server verification, account linking, revoked-credential handling, and owner credentials.
- In-app and public account deletion, Apple/provider disconnect behavior, financial-ledger retention/anonymization, privacy/support/legal links, data-retention disclosure, and support response process.
- Push transport decision and evidence: use FCM for both platforms or approve an APNs bridge; provide Firebase/APNs/EAS configuration and verify registration/unregistration on physical devices.
- iOS Privacy Manifest/Required Reason APIs, App Privacy answers, Android Data Safety/Financial Features answers, current target API, and minimal permissions.
- AASA/Asset Links, push/APNs/Firebase setup, signed IPA/AAB, Play App Signing, 16 KB compatibility, and release metadata.
- Physical iOS/Android screenshots for parity states, accessibility/font scaling, offline/retry/expired-session checks, performance baseline, and reviewer accounts.
