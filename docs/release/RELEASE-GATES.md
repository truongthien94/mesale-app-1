# Release Gates

Current verdicts:

- [App Store current-state audit](APP-STORE-CURRENT-AUDIT.md): `NOT READY`
- [Google Play current-state audit](PLAY-STORE-CURRENT-AUDIT.md): `NOT READY`
- M1 status: open; foundation checks are not milestone completion evidence

## Both Stores

- [ ] Native member utility and representative vertical flows verified; no WebView-only wrapper
- [x] Session Bearer auth only; no personal member API key in the mobile bundle
- [ ] Account deletion in app and public web resource
- [ ] Privacy Policy, Terms, Support URL, and review account available
- [ ] Apple/Google native OAuth and safe server-side account linking implemented
- [ ] Staging API, versioning, request IDs, error envelope, pagination, idempotency, logging, and rollback verified
- [ ] No hardcoded credential, service-account file, signing key, or sensitive production data
- [ ] UGC moderation/report/block/contact implemented if comments are exposed
- [ ] Financial/cashback claims are accurate, non-guaranteed, and supported by terms
- [ ] Dependency advisories resolved or explicitly risk-accepted with compatible regression evidence
- [ ] Physical-device loading, offline, retry, expired-session, accessibility, screenshot, and performance evidence exists for iOS and Android

## iOS

- [ ] Sign in with Apple
- [ ] App-level Privacy Manifest and Required Reason API inventory
- [x] ATS arbitrary loads disabled in source config
- [ ] Generated release `Info.plist`, entitlements, Privacy Manifest, and signed archive inspected
- [ ] Permission usage descriptions and App Privacy declarations reconciled with actual SDK behavior
- [ ] AASA/universal links verified
- [ ] Export compliance, metadata, screenshots, support contact, and review notes complete

## Android

- [ ] Submission-date target API requirement verified from the production artifact
- [ ] Production AAB and Play App Signing verified
- [ ] 16 KB page-size compatibility verified for every native library in the release artifact
- [x] Broad storage and overlay permissions blocked in source config
- [ ] Production merged manifest and cleartext behavior inspected
- [ ] Data Safety and Financial Features declarations match actual app/API/SDK behavior
- [ ] Asset Links/App Links verified
- [ ] Target audience, IARC rating, review credentials, rollout, and notification permission behavior verified

No gate is complete solely because it appears in source configuration. Generated release artifacts, device behavior, store-console declarations, and review evidence remain authoritative for release readiness.
