# Mobile Dependency Audit and Upgrade Plan

Status date: 2026-08-10 (Asia/Bangkok)

Scope: `mobile/` dependency risk only. This document contains no credentials, private repository metadata, member data, or raw authenticated responses.

## Decision

- Do not run `npm audit fix --force`.
- Do not accept npm's suggested React Native downgrade to `0.72.17`.
- Do not add transitive `overrides` outside Expo or React Native's tested ranges merely to reduce the audit counter.
- Keep package code unchanged in this round because npm provides no compatible, non-major remediation for the current Expo SDK 53 graph.
- Upgrade Expo incrementally, one SDK at a time, and stop at every SDK boundary for tests and native-device evidence.
- Target Expo SDK 57 / React Native 0.86.2 after the intermediate gates pass. Expo SDK 54 is the first mandatory checkpoint because it targets Android API 36.

## Current Baseline

| Component | Declared | Installed/active |
| --- | --- | --- |
| Node.js | Tooling environment | `22.14.0` |
| npm | Tooling environment | `10.9.2` |
| Expo | `~53.0.0` | `53.0.27` |
| React Native | `0.79.6` | `0.79.6` |
| React | `19.0.0` | `19.0.0` |
| TypeScript | `~5.8.3` | `5.8.3` |
| Architecture | `newArchEnabled: true` | New Architecture enabled |

The project uses Expo managed/CNG configuration and does not currently track generated `ios/` or `android/` directories. Every SDK change therefore requires a new development client/native build; a JavaScript-only test is insufficient.

## Audit Counts

`npm audit --omit=dev` reports package nodes affected through dependency propagation, not 22 independent vulnerabilities.

| Snapshot | Critical | High | Moderate | Low | Total |
| --- | ---: | ---: | ---: | ---: | ---: |
| Before review | 0 | 7 | 15 | 0 | 22 |
| After safe-fix review | 0 | 7 | 15 | 0 | 22 |

No package or lockfile change was applied. `npm audit fix --omit=dev --dry-run` left all 22 findings and reported that each remediation path requires `--force` and a breaking Expo/React Native change.

## Root Advisory Groups

| Leaf package | Installed | Severity | Underlying advisory group | Current path | Supported remediation status |
| --- | ---: | --- | --- | --- | --- |
| `postcss` | `8.4.49` | High | Four CSS serialization/source-map advisories | `expo -> @expo/metro-config -> postcss` | Patched versions are newer than `8.5.22`, but SDK 53 pins `~8.4.32`. Do not override that tested range. Expo SDK 57's Metro config accepts `^8.5.14`, which can resolve a patched release. |
| `image-size` | `1.2.1` | High | Two infinite-loop denial-of-service advisories for ICNS/JXL/HEIF parsing | `react-native -> community-cli-plugin -> metro -> image-size` | No fixed published release is currently available; registry latest `2.0.2` is still covered by the advisories. Track the upstream Metro/image-size remediation. |
| `ajv` | `8.11.0` | Moderate | ReDoS when the `$data` option is used | `expo-dev-client -> expo-dev-launcher -> ajv` | Fixed in `>=8.18.0`, but SDK 53's launcher pins `8.11.0`. Registry metadata for `expo-dev-launcher@57.0.10` no longer lists this dependency, so the staged Expo upgrade is the supported path. |
| `uuid` | `7.0.3` | Moderate | Missing buffer bounds check in selected UUID APIs | `expo -> config-plugins -> xcode -> uuid` | Fixed in `>=11.1.1`; current and SDK 57 config-plugin metadata still use `xcode@^3.0.1`, whose latest release depends on `uuid@^7.0.3`. Treat this as upstream-blocked rather than forcing a major override. |

These Node packages are used by development, prebuild, and Metro asset processing; they are not application JavaScript libraries bundled as executable Node tooling inside the signed IPA/AAB. The findings still matter for CI and developer-machine supply-chain safety. Until patched, only trusted repository assets/configuration should be processed, and untrusted pull-request code must not receive release credentials.

## Why No Direct Fix Is Safe

- npm proposes `expo@57.0.11` and `expo-dev-client@57.0.10` as breaking changes, not compatible SDK 53 patches.
- npm proposes `react-native@0.72.17` for the Metro/image-size chain. That is a destructive downgrade from React Native `0.79.6` and is incompatible with the current Expo SDK.
- `postcss@8.5.26` and `ajv@8.20.0` are patched same-major releases, but the owning Expo SDK 53 packages intentionally constrain or pin older versions. An override would bypass Expo's compatibility matrix.
- `uuid` requires a major jump and `image-size` has no published fixed version, so neither can be safely overridden.

## Official Incremental Upgrade Path

Expo's official upgrade guide recommends upgrading one SDK version at a time so that failures can be isolated. Use the latest stable patch in each SDK line:

| Stage | Expo target | React Native | React | TypeScript | Required risk checks |
| --- | --- | --- | --- | --- | --- |
| Current | `53.0.27` | `0.79.6` | `19.0.0` | `5.8.3` | Baseline only. |
| A | `54.0.36` | `0.81.5` | `19.1.0` | `5.9.2` | Android target API 36; edge-to-edge becomes mandatory; verify safe areas, keyboard, modals, bottom tabs, status/navigation bars, OAuth native callbacks, and iOS precompiled-framework builds. First-party JSC is removed; retain the supported Hermes path. |
| B | `55.0.28` | `0.83.10` | `19.2.0` | `5.9.2` | New Architecture is the supported architecture. The app already enables it, but every native package and config plugin still needs a clean development build and device verification. Do not opt into Hermes V1 separately during this stage. |
| C | `56.0.19` | `0.85.3` | `19.2.3` | `6.0.3` | Node minimum is `20.19.4` (current Node `22.14.0` qualifies); TypeScript 6 may expose new compile errors; Hermes V1 becomes the default, so repeat cold-start, memory, OAuth, SecureStore, navigation, and financial-mutation tests. |
| D | `57.0.11` | `0.86.2` | `19.2.3` | `6.0.3` | Minimum Node is `22.13.x` (current Node `22.14.0` qualifies). Expo describes the RN 0.85 to 0.86 step as non-breaking, but repeat all gates and the audit. Residual `image-size` and `uuid` advisories may remain until their upstream dependency owners publish compatible fixes. |

The end target is SDK 57, but Stage A should be landed and validated first because it is the minimum step in this path that supplies the Android API 36 toolchain required for the planned Google Play deadline.

## Procedure Per SDK Stage

Run each stage in its own reviewed commit/checkpoint. Advance with exactly one of these commands at a time:

```powershell
cd mobile

# Stage A
npm install expo@~54.0.36

# Stage B, only after Stage A passes
npm install expo@~55.0.28

# Stage C, only after Stage B passes
npm install expo@~56.0.19

# Stage D, only after Stage C passes
npm install expo@~57.0.11
```

After each individual install, run the common alignment and verification sequence:

```powershell
npx expo install --fix
npx expo install --check
npx expo-doctor
npm test
npm run typecheck
npm audit --omit=dev
npx expo config --type public --json
npx expo config --type introspect --json
npx expo export --platform ios
npx expo export --platform android
```

After dependency alignment:

1. Inspect `package.json` and `package-lock.json`; do not stage unrelated files.
2. Review the release notes for the SDK being entered, including its "Upgrading your app" and breaking-change sections.
3. Confirm Apple Sign in, Google native sign-in, account deletion reauthentication, SecureStore session restore, deep links, privacy manifest, and restricted Android permissions in introspected native configuration.
4. Rebuild the development client because Google Sign-In, Apple Authentication, Expo modules, React Native, and config plugins contain native code.
5. Test physical iOS and Android devices, including small/large layouts, light/dark mode, font scaling, safe areas, keyboard-open states, offline/retry, and expired sessions.
6. Produce preview signed artifacts. Inspect the AAB target SDK/merged manifest and the IPA entitlements/privacy manifest before advancing.
7. Stop the upgrade if audit severity increases, Expo Doctor fails, a native module lacks support, or visual/financial regression evidence is incomplete.

## Package-Specific Checks

- `@react-native-google-signin/google-signin`: verify RN 0.81, 0.83, 0.85, and 0.86/New Architecture support at each stage; test the reversed iOS URL scheme and Android OAuth return flow on devices.
- `expo-apple-authentication`: let `npx expo install --fix` choose the SDK-compatible version; verify entitlement generation, nonce flow, credential cancellation/revocation, and deletion reauthentication.
- `expo-router`: accept only the SDK-compatible release selected by Expo; regression-test redirects, tabs, deep links, route typing, headers, and back behavior.
- `expo-secure-store`: verify existing sessions, expiration handling, logout/token revocation, biometric configuration, and behavior across an app upgrade.
- `expo-dev-client`: rebuild for every SDK boundary and re-run the audit because it owns the current AJV chain.
- `react-native-screens` and `react-native-safe-area-context`: visually verify all screens after the mandatory Android edge-to-edge transition.

## Acceptance Gates

- `npm test`: all tests pass.
- `npm run typecheck`: passes with the target TypeScript version.
- `npx expo-doctor`: all checks pass.
- `npx expo install --check`: dependencies match the selected SDK.
- iOS and Android exports pass.
- Native development and preview builds pass on both platforms.
- No new direct production dependency vulnerability is introduced.
- Every residual advisory has an explicit dependency path, exploitability note, upstream owner, and recheck date.
- Android artifact reports target API 36 and passes 16 KB page-size validation.
- OAuth, account deletion, deep links, secure session restore, financial idempotency, and visual parity have device evidence.

## Verified Baseline Commands

| Command | Result |
| --- | --- |
| `npm test` | 34/34 passed |
| `npm run typecheck` | Passed |
| `npx expo-doctor` | 18/18 passed |
| `npx expo install --check` | Dependencies up to date for SDK 53 |
| `npm audit --omit=dev` | 22 total: 7 high, 15 moderate |
| `npm audit fix --omit=dev --dry-run` | No vulnerability reduction; breaking changes required |

## Official References

- Expo incremental upgrade walkthrough: <https://docs.expo.dev/workflow/upgrading-expo-sdk-walkthrough/>
- Expo SDK 53 release notes: <https://expo.dev/changelog/sdk-53>
- Expo SDK 54 release notes: <https://expo.dev/changelog/sdk-54>
- Expo SDK 55 release notes: <https://expo.dev/changelog/sdk-55>
- Expo SDK 56 release notes: <https://expo.dev/changelog/sdk-56>
- Expo SDK 57 release notes: <https://expo.dev/changelog/sdk-57>
- PostCSS advisories: <https://github.com/advisories/GHSA-qx2v-qp2m-jg93>, <https://github.com/advisories/GHSA-6g55-p6wh-862q>, <https://github.com/advisories/GHSA-r28c-9q8g-f849>, <https://github.com/advisories/GHSA-fxqj-rqcc-2cmp>
- image-size advisories: <https://github.com/advisories/GHSA-w3rx-r6r6-pgpr>, <https://github.com/advisories/GHSA-5p2g-fcmc-qvqq>
- AJV advisory: <https://github.com/advisories/GHSA-2g4f-4pwh-qvx6>
- UUID advisory: <https://github.com/advisories/GHSA-w5hq-g745-h8pq>
