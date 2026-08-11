# Release Gates

Current verdicts:

- [App Store current-state audit](APP-STORE-CURRENT-AUDIT.md): `NOT READY`
- [Google Play current-state audit](PLAY-STORE-CURRENT-AUDIT.md): `NOT READY`
- M0 and M1 remain open; passing local foundation checks is not milestone completion evidence.
- Production Open API config returned HTTP `200` on 2026-08-10; activation provenance, auth/security validation, staging, monitoring, and rollback evidence remain open.
- Round 5 referral prompt implementation is published and CI verified (`77/702` PHPUnit, `37/37` mobile, TypeScript, Expo Doctor `18/18`, and both exports in run `31330756601`); it does not change the store verdict.
- Round 4 evidence: local Laravel `72` tests / `664` assertions, focused pruning `4` tests / `16` assertions, mobile `34/34`, TypeScript, Expo Doctor `18/18`, dependency check, exports, lint, diff, and secret checks passed; remote CI run `31328158427` passed all jobs.
- Home parity evidence: code commit `88e2095`; mobile `46/46`, TypeScript, Expo Doctor `18/18`, iOS/Android exports, Android emulator visual inspection, diff check, and sensitive-data scan passed. Physical-device and store-copy evidence remain open.
- Native Orders evidence: source commit `439c1da`; mobile `54/54`, TypeScript, Laravel `66/646`, diff and staged sensitive-data checks, and Android emulator visual/empty-CTA checks pass. Production API deployment, physical devices, signed artifacts, and store evidence remain open.

An ID is complete only when source, tests, generated artifact, device behavior, and store-console evidence agree. Local checks do not substitute for staging, remote CI, signed artifact, or device evidence.

| Gate ID | Status | Requirement | Current evidence / missing proof |
| --- | --- | --- | --- |
| `GATE-IOS-001` | Blocked | Sign in with Apple when Google/social login is offered | Laravel exchange/verification and native Apple/Google client flows are implemented with flags OFF; owner credentials, staging test, rollout, and review evidence are missing. |
| `GATE-IOS-002` | Blocked | In-app deletion, provider disconnect, and public deletion resource | Native deletion UI, public deletion route, in-app legal links, and server-side Apple reauthentication/revocation policy exist locally. Owner-approved content, device/provider evidence, wider ledger retention policy, and store-review evidence are still missing. |
| `GATE-IOS-003` | Blocked | Privacy Policy, App Privacy, Privacy Manifest, Required Reason APIs, permissions, and ATS | Public privacy/terms/support/deletion routes, in-app links, and source privacy manifest are present. Generated archive inspection, owner/legal approval, Required Reason reconciliation, and App Store Connect declarations remain incomplete. |
| `GATE-IOS-004` | Open | UGC filtering, report, block, and support contact if comments are exposed | Comments/likes/shares are not yet released. Moderation, report/block/contact controls must be verified before UGC exposure. |
| `GATE-IOS-005` | Blocked | Native utility, universal links, metadata, screenshots, and reviewer resources | Native MVP tabs/screens and AASA/associated-domain groundwork exist, but Team ID deployment, device screenshots, signed build, metadata, push setup, and review account are unavailable. |
| `GATE-AND-001` | Blocked | Data Safety and Financial Features declarations | API/SDK data inventory and Play Console declarations are incomplete; wallet/withdrawal scope requires an explicit Financial Features review. |
| `GATE-AND-002` | Blocked | In-app deletion plus public web deletion URL | Native deletion UI, public deletion route, in-app links, local Apple disconnect, and referral-ledger safeguards exist. Owner-approved content, device evidence, wider retention policy/disclosure, and Console evidence are still missing. |
| `GATE-AND-003` | Blocked | Submission-date target API, merged manifest, and cleartext behavior | No signed production artifact exists. Target API (API 35/36 deadline), merged manifest, and network-security evidence must be inspected at build time. |
| `GATE-AND-004` | Blocked | Production AAB, Play App Signing, 16 KB compatibility, App Links, and rollout | Android App Links intent filters and `assetlinks.json` groundwork exist, but no release fingerprint, signed AAB, Play App Signing evidence, native-library 16 KB scan, or staged rollout evidence exists. |
| `GATE-BOTH-001` | Open | Physical-goods payment classification and truthful cashback claims | Marketplace handoff is native and concerns physical goods; copy, terms, attribution, and end-to-end evidence remain unverified. |
| `GATE-BOTH-002` | Partial | Minimal permissions, HTTPS, credential hygiene, and dependency/test quality | Local source checks pass; 22 npm advisories have no safe SDK 53 fix and now have a staged SDK 54-to-57 plan. Remote CI, signed artifacts, upgraded native builds, and device checks remain open. |
| `GATE-BOTH-003` | Open | No fake earnings, hidden features, incentivized reviews, or dynamic native code | The animated Home walkthrough is native and isolated from business APIs, but it displays hardcoded sample order/cashback values without a visible illustration caption. Owner-approved disclosure and final metadata/reviewer-path audit remain required. |
| `GATE-BOTH-006` | Partial / blocked | Round 3 store-compliance source groundwork | Source groundwork and local checks pass; staging, owner inputs, signed artifacts, devices, and store-console evidence remain missing. |
| `GATE-BOTH-007` | Partial / blocked | Round 4 idempotency retention and dependency remediation | Local pruning/tests, dependency plan, direct push, and remote CI pass; production scheduler evidence, SDK upgrade, signed artifacts, devices, and store-console proof remain missing. |
| `GATE-BOTH-008` | Partial / blocked | Round 5 one-time post-registration referral decision | Local backend/mobile implementation and test/export evidence pass; production API activation, staging OAuth, physical devices, signed artifacts, push, and store-console proof remain missing. |
| `GATE-BOTH-010` | Partial / blocked | Round B website-parity bottom navigation and More sheet | Source is published to `main`; TypeScript, `43/43` mobile tests, Expo Doctor `18/18`, and iOS/Android exports pass. Physical tab/back click-through, screenshots, accessibility, signed artifacts, and store evidence remain missing. |
| `GATE-BOTH-011` | Partial / blocked | Round C Home snapshot and Wallet dashboard | TypeScript, `45/45` mobile tests, Expo Doctor `18/18`, and iOS/Android exports pass locally. Dynamic coupon/blog freshness, physical screenshots, device interaction, signed artifacts, and store evidence remain missing. |
| `GATE-BOTH-012` | Partial / blocked | Native Home parity, locale/theme behavior, and animated iPhone walkthrough | TypeScript, `46/46` mobile tests, Expo Doctor `18/18`, iOS/Android exports, and Android emulator inspection pass. Physical iOS/Android matrices, accessibility/offline/performance evidence, signed artifacts, and owner-approved walkthrough disclosure remain missing. |
| `GATE-BOTH-013` | Partial / blocked | Native Orders tracking parity and additive order-feed contract | Source commit `439c1da`; mobile `54/54`, TypeScript, Laravel `66/646`, diff and staged sensitive-data checks, and Android emulator Orders/empty-CTA evidence pass. Production API deployment, physical iOS/Android matrices, signed artifacts, accessibility/performance evidence, and store-console proof remain missing. |

## Required Release Evidence

- Reviewer-reachable staging or production API with feature flags, auth, throttle, logging, request IDs, rollback, and monitoring verified.
- Native Google and Sign in with Apple clients, server verification, account linking, revoked-credential handling, and owner credentials.
- In-app and public account deletion, Apple/provider disconnect behavior, financial-ledger retention/anonymization, privacy/support/legal links, data-retention disclosure, and support response process.
- Push transport decision and evidence: use FCM for both platforms or approve an APNs bridge; provide Firebase/APNs/EAS configuration and verify registration/unregistration on physical devices.
- iOS Privacy Manifest/Required Reason APIs, App Privacy answers, Android Data Safety/Financial Features answers, current target API, and minimal permissions.
- AASA/Asset Links, push/APNs/Firebase setup, signed IPA/AAB, Play App Signing, 16 KB compatibility, and release metadata.
- Physical iOS/Android screenshots for parity states, accessibility/font scaling, offline/retry/expired-session checks, performance baseline, and reviewer accounts.
- Owner-approved user/store disclosure for hardcoded walkthrough order and cashback values so the animation cannot be read as real member data or guaranteed earnings.
