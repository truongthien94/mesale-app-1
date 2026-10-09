const assert = require("node:assert/strict");
const test = require("node:test");

const {
  applyReleaseOptimizationToProperties,
  applyReleaseOptimizationToGradle,
  RELEASE_OPTIMIZATION_PROPERTIES
} = require("../plugins/withAndroidReleaseOptimization");
const { applyReleaseSigningToGradle } = require("../plugins/withAndroidReleaseSigning");
const { validateR8Metadata } = require("../tools/verify-android-release.cjs");

const VALID_R8_METADATA = {
  options: {
    isObfuscationEnabled: true,
    isOptimizationsEnabled: true,
    isShrinkingEnabled: true,
    isProGuardCompatibilityModeEnabled: false,
    isDebugModeEnabled: false
  },
  resourceOptimization: { isOptimizedShrinkingEnabled: true }
};

const BASE_GRADLE = `
def enableMinifyInReleaseBuilds = (findProperty('android.enableMinifyInReleaseBuilds') ?: false).toBoolean()
android {
    signingConfigs {
        debug {
            storeFile file('debug.keystore')
        }
    }
    buildTypes {
        debug {
            signingConfig signingConfigs.debug
        }
        release {
            signingConfig signingConfigs.debug
            def enableShrinkResources = findProperty('android.enableShrinkResourcesInReleaseBuilds') ?: 'false'
            shrinkResources enableShrinkResources.toBoolean()
            minifyEnabled enableMinifyInReleaseBuilds
            proguardFiles getDefaultProguardFile("proguard-android.txt"), "proguard-rules.pro"
        }
    }
}
`;

test("enables release code optimization, resource shrinking and R8 full mode", () => {
  const result = applyReleaseOptimizationToProperties([]);
  const properties = Object.fromEntries(result.map(({ key, value }) => [key, value]));

  assert.deepEqual(properties, {
    "android.enableMinifyInReleaseBuilds": "true",
    "android.enableShrinkResourcesInReleaseBuilds": "true",
    "android.enableR8.fullMode": "true",
    "android.r8.optimizedResourceShrinking": "true"
  });
});

test("replaces disabled or duplicated optimization properties without changing unrelated settings", () => {
  const preservedProperties = [
    { type: "comment", value: "Signing settings" },
    { type: "property", key: "MESALE_ANDROID_KEYSTORE_PATH", value: "upload.jks" },
    { type: "property", key: "hermesEnabled", value: "true" },
    { type: "empty" }
  ];
  const disabledProperties = Object.keys(RELEASE_OPTIMIZATION_PROPERTIES).flatMap((key) => [
    { type: "property", key, value: "false" },
    { type: "property", key, value: "false" }
  ]);
  const original = [...preservedProperties, ...disabledProperties];
  const snapshot = structuredClone(original);
  const result = applyReleaseOptimizationToProperties(original);

  assert.deepEqual(result.slice(0, preservedProperties.length), preservedProperties);
  assert.deepEqual(original, snapshot);
  for (const key of Object.keys(RELEASE_OPTIMIZATION_PROPERTIES)) {
    const matches = result.filter((property) => property.type === "property" && property.key === key);
    assert.equal(matches.length, 1);
    assert.equal(matches[0].value, "true");
  }
});

test("repeated prebuilds leave Gradle properties unchanged", () => {
  const first = applyReleaseOptimizationToProperties([]);
  assert.deepEqual(applyReleaseOptimizationToProperties(first), first);
});

test("uses the optimizing ProGuard defaults and preserves project keep rules", () => {
  const result = applyReleaseOptimizationToGradle(BASE_GRADLE, "groovy");

  assert.match(result, /getDefaultProguardFile\("proguard-android-optimize\.txt"\)/);
  assert.doesNotMatch(result, /getDefaultProguardFile\("proguard-android\.txt"\)/);
  assert.match(result, /"proguard-rules\.pro"/);
  assert.match(result, /minifyEnabled enableMinifyInReleaseBuilds/);
  assert.match(result, /shrinkResources enableShrinkResources\.toBoolean\(\)/);
  assert.match(result, /debug\s*\{\s*signingConfig signingConfigs\.debug\s*\}/);
});

test("supports single quoted ProGuard defaults and whitespace", () => {
  const input = BASE_GRADLE.replace(
    'getDefaultProguardFile("proguard-android.txt")',
    "getDefaultProguardFile ( 'proguard-android.txt' )"
  );
  assert.match(
    applyReleaseOptimizationToGradle(input, "groovy"),
    /getDefaultProguardFile\("proguard-android-optimize\.txt"\)/
  );
});

test("repeated prebuilds leave the optimizing ProGuard defaults unchanged", () => {
  const first = applyReleaseOptimizationToGradle(BASE_GRADLE, "groovy");
  assert.equal(applyReleaseOptimizationToGradle(first, "groovy"), first);
});

test("release optimization composes with upload-key signing in either order", () => {
  const signingFirst = applyReleaseOptimizationToGradle(
    applyReleaseSigningToGradle(BASE_GRADLE, "groovy"),
    "groovy"
  );
  const optimizationFirst = applyReleaseSigningToGradle(
    applyReleaseOptimizationToGradle(BASE_GRADLE, "groovy"),
    "groovy"
  );

  assert.equal(signingFirst, optimizationFirst);
  assert.match(signingFirst, /signingConfig signingConfigs\.release/);
  assert.match(signingFirst, /MESALE_ANDROID_KEYSTORE_PATH/);
  assert.match(signingFirst, /getDefaultProguardFile\("proguard-android-optimize\.txt"\)/);
});

test("fails visibly if the native template no longer exposes the default ProGuard configuration", () => {
  assert.throws(
    () => applyReleaseOptimizationToGradle("android { buildTypes { release {} } }", "groovy"),
    /could not find the default ProGuard configuration/
  );
});

test("fails visibly for unsupported Kotlin Gradle scripts rather than silently skipping optimization", () => {
  assert.throws(
    () => applyReleaseOptimizationToGradle(BASE_GRADLE, "kotlin"),
    /requires a Groovy app build\.gradle/
  );
});

test("the artifact gate accepts a release bundle with full R8 optimization", () => {
  assert.doesNotThrow(() => validateR8Metadata(VALID_R8_METADATA));
});

for (const option of Object.keys(VALID_R8_METADATA.options)) {
  test(`the artifact gate rejects an AAB with incorrect ${option}`, () => {
    const metadata = structuredClone(VALID_R8_METADATA);
    metadata.options[option] = !metadata.options[option];
    assert.throws(() => validateR8Metadata(metadata), new RegExp(option));
  });
}

test("the artifact gate rejects missing metadata instead of reporting success", () => {
  assert.throws(() => validateR8Metadata({}), /Invalid release R8 option/);
});

test("the artifact gate rejects a bundle without optimized resource shrinking", () => {
  const metadata = structuredClone(VALID_R8_METADATA);
  delete metadata.resourceOptimization;
  assert.throws(() => validateR8Metadata(metadata), /must enable optimized resource shrinking/);
});
