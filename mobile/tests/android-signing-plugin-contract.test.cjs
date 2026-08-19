const assert = require("node:assert/strict");
const test = require("node:test");

const {
  applyReleaseSigningToGradle,
  SIGNING_CONFIG_TAG,
  BUILD_TYPE_TAG
} = require("../plugins/withAndroidReleaseSigning");

// Minimal groovy build.gradle resembling the Expo-generated app module, with
// the blocks the plugin needs to extend.
const BASE_GRADLE = `
android {
    signingConfigs {
        debug {
            storeFile file('debug.keystore')
            storePassword 'android'
            keyAlias 'androiddebugkey'
            keyPassword 'android'
        }
    }
    buildTypes {
        debug {
            signingConfig signingConfigs.debug
        }
        release {
            signingConfig signingConfigs.debug
            minifyEnabled enableProguardInReleaseBuilds
        }
    }
}
`;

function applyPlugin(contents, language) {
  return applyReleaseSigningToGradle(contents, language ?? "groovy");
}

test("injects a release signingConfig that reads MESALE_ANDROID_* at build time", () => {
  const out = applyPlugin(BASE_GRADLE);
  assert.match(out, /signingConfigs\s*\{[\s\S]*release\s*\{/);
  assert.match(out, /MESALE_ANDROID_KEYSTORE_PATH/);
  assert.match(out, /MESALE_ANDROID_KEYSTORE_PASSWORD/);
  assert.match(out, /MESALE_ANDROID_KEY_ALIAS/);
  assert.match(out, /MESALE_ANDROID_KEY_PASSWORD/);
  assert.match(out, /System\.getenv/);
});

test("does not embed any literal keystore path, password or alias", () => {
  const out = applyPlugin(BASE_GRADLE);
  // Scope to the generated signing block only (the pre-existing debug block
  // legitimately keeps its literal debug credentials).
  const begin = out.indexOf("@generated begin " + SIGNING_CONFIG_TAG);
  const end = out.indexOf("@generated end " + SIGNING_CONFIG_TAG);
  assert.ok(begin >= 0 && end > begin, "generated signing block must be present");
  const generated = out.slice(begin, end);
  assert.doesNotMatch(generated, /storePassword\s+'[^']+'/);
  assert.doesNotMatch(generated, /keyPassword\s+'[^']+'/);
  assert.doesNotMatch(generated, /keyAlias\s+'[^']+'/);
});

test("leaves the debug signingConfig untouched", () => {
  const out = applyPlugin(BASE_GRADLE);
  assert.match(out, /debug\s*\{\s*storeFile file\('debug\.keystore'\)/);
  assert.match(out, /keyAlias 'androiddebugkey'/);
});

test("replaces the template release debug line so release signing wins", () => {
  const out = applyPlugin(BASE_GRADLE);
  // Isolate the release buildType block.
  const releaseStart = out.indexOf("release {", out.indexOf("buildTypes"));
  const release = out.slice(releaseStart);
  const genEnd = release.indexOf("@generated end " + BUILD_TYPE_TAG);
  assert.ok(genEnd >= 0, "release buildType wiring must be present");
  // After our generated block there must be no bare
  // `signingConfig signingConfigs.debug` left to override release signing —
  // that leftover line is exactly what shipped a debug-signed AAB before.
  const afterBlock = release.slice(genEnd);
  assert.doesNotMatch(afterBlock, /signingConfig\s+signingConfigs\.debug/);
  // The release signingConfig must be selected when inputs are present.
  assert.match(release, /if \(mesaleHasReleaseSigning\)\s*\{\s*signingConfig signingConfigs\.release/);
});

test("fails the release build loudly when signing inputs are missing", () => {
  const out = applyPlugin(BASE_GRADLE);
  assert.match(out, /throw new GradleException/);
  assert.match(out, /Missing MESALE_ANDROID_\* signing inputs/);
});

test("is idempotent across repeated prebuild runs", () => {
  const once = applyPlugin(BASE_GRADLE);
  const twice = applyPlugin(once);
  const signingBegin = new RegExp("@generated begin " + SIGNING_CONFIG_TAG, "g");
  const buildTypeBegin = new RegExp("@generated begin " + BUILD_TYPE_TAG, "g");
  assert.equal((twice.match(signingBegin) ?? []).length, 1, "signing config must be injected exactly once");
  assert.equal((twice.match(buildTypeBegin) ?? []).length, 1, "release buildType wiring must be injected exactly once");
});

test("leaves a non-groovy build.gradle unchanged", () => {
  const kts = "android { }";
  const out = applyPlugin(kts, "kt");
  assert.equal(out, kts);
});
