# Release Gates

Current verdicts:

- [App Store current-state audit](APP-STORE-CURRENT-AUDIT.md): `NOT READY`
- [Google Play current-state audit](PLAY-STORE-CURRENT-AUDIT.md): `NOT READY`
- M0 and M1 remain open; foundation checks are not milestone completion evidence

The IDs below match the operational release-gate register. A gate is complete only when source, generated artifact, device behavior and store-console evidence agree.

| Gate ID | Status | Requirement | Current evidence / missing proof |
| --- | --- | --- | --- |
| `GATE-IOS-001` | Open | Sign in with Apple when Google/social login is offered | Neither native provider is implemented; server JWKS/nonce/`sub` verification and review evidence are missing. |
| `GATE-IOS-002` | Open | In-app account deletion | Backend logging is hardened, but provider reauthentication, native flow, public deletion resource and retention disclosure are missing. |
| `GATE-IOS-003` | Blocked | Privacy Policy, App Privacy, Privacy Manifest, Required Reason APIs, permissions and ATS artifact | ATS arbitrary loads are disabled in source; app manifest, declarations, generated `Info.plist`/archive and public policy remain missing. |
| `GATE-IOS-004` | Open | UGC filtering, report, block and support contact if comments are exposed | UGC is not exposed; safeguards must exist before implementation is released. |
| `GATE-IOS-005` | Open | Substantial native utility, universal links, metadata, screenshots and review resources | No WebView wrapper exists, but the app is still a foundation shell without device parity or reviewer resources. |
| `GATE-AND-001` | Open | Data Safety and Financial Features declarations | SDK/API data inventory and Play Console declarations are not complete. |
| `GATE-AND-002` | Open | In-app deletion plus public web deletion URL | Native flow, provider reauthentication, public URL and Console evidence are missing. |
| `GATE-AND-003` | Blocked | Submission-date target API, merged manifest and cleartext behavior | No production artifact exists; API 36 timing must be rechecked at submission. |
| `GATE-AND-004` | Blocked | Production AAB, Play App Signing, 16 KB compatibility, App Links and review rollout | No signed AAB or native-library scan exists; Asset Links and rollout evidence are missing. |
| `GATE-BOTH-001` | Open | Correct physical-goods payment classification and truthful cashback claims | Physical-goods policy is documented; end-to-end marketplace flow, copy and terms are unverified. |
| `GATE-BOTH-002` | Partial | Minimal permissions, HTTPS, credential hygiene and dependency/test quality | Source permissions/ATS and tracked secret scan pass at `8b81b53`; signed artifacts, npm remediation and Linux PHPUnit warning resolution remain open. |
| `GATE-BOTH-003` | Open | No fake earnings, hidden features, incentivized reviews or dynamic native code | No prohibited behavior is present in the foundation; full product, metadata and remote-config audit remain open. |

Open cross-cutting evidence also includes reviewer-accessible staging/production service, stable API envelopes/request IDs/pagination, complete OAuth, physical-device loading/offline/retry/session/accessibility tests, screenshot regression, performance measurements, support/review accounts and production monitoring/rollback.
