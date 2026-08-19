const { withAppBuildGradle } = require("expo/config-plugins");

// Anchor comment used by mergeContents so re-running prebuild is idempotent.
const SIGNING_CONFIG_TAG = "mesale-release-signing";
const BUILD_TYPE_TAG = "mesale-release-buildtype";

// Groovy injected into android/app/build.gradle. All credential values are read
// from Gradle properties (or environment variables) at build time — never
// embedded here. Missing inputs fail the release build loudly instead of
// silently producing an unsigned/debug-signed artifact.
const SIGNING_CONFIG_SNIPPET = [
  "        // @generated begin " + SIGNING_CONFIG_TAG + " - do not modify by hand",
  "        release {",
  "            def resolveSigningProp = { String propName, String envName ->",
  "                if (project.hasProperty(propName)) {",
  "                    return project.property(propName)",
  "                }",
  "                return System.getenv(envName)",
  "            }",
  "            def storeFilePath = resolveSigningProp('MESALE_ANDROID_KEYSTORE_PATH', 'MESALE_ANDROID_KEYSTORE_PATH')",
  "            def storePasswordValue = resolveSigningProp('MESALE_ANDROID_KEYSTORE_PASSWORD', 'MESALE_ANDROID_KEYSTORE_PASSWORD')",
  "            def keyAliasValue = resolveSigningProp('MESALE_ANDROID_KEY_ALIAS', 'MESALE_ANDROID_KEY_ALIAS')",
  "            def keyPasswordValue = resolveSigningProp('MESALE_ANDROID_KEY_PASSWORD', 'MESALE_ANDROID_KEY_PASSWORD')",
  "            def hasReleaseSigning = storeFilePath && storePasswordValue && keyAliasValue && keyPasswordValue",
  "            if (hasReleaseSigning) {",
  "                storeFile file(storeFilePath)",
  "                storePassword storePasswordValue",
  "                keyAlias keyAliasValue",
  "                keyPassword keyPasswordValue",
  "            }",
  "        }",
  "        // @generated end " + SIGNING_CONFIG_TAG
].join("\n");

// Wires buildTypes.release to the release signingConfig only when all four
// signing inputs are present; otherwise the release build aborts with a clear
// message. This snippet REPLACES the Expo template's default
// `signingConfig signingConfigs.debug` line inside the release block — appending
// after it would lose, because Groovy applies the LAST signingConfig assignment
// (that debug fallback is exactly why an earlier build shipped a debug-signed
// AAB). The else branch keeps debug only as a non-release fallback; the
// fail-fast above guarantees a real release build never reaches it unsigned.
const BUILD_TYPE_SNIPPET = [
  "            // @generated begin " + BUILD_TYPE_TAG + " - do not modify by hand",
  "            def mesaleHasReleaseSigning = (project.hasProperty('MESALE_ANDROID_KEYSTORE_PATH') || System.getenv('MESALE_ANDROID_KEYSTORE_PATH')) && (project.hasProperty('MESALE_ANDROID_KEYSTORE_PASSWORD') || System.getenv('MESALE_ANDROID_KEYSTORE_PASSWORD')) && (project.hasProperty('MESALE_ANDROID_KEY_ALIAS') || System.getenv('MESALE_ANDROID_KEY_ALIAS')) && (project.hasProperty('MESALE_ANDROID_KEY_PASSWORD') || System.getenv('MESALE_ANDROID_KEY_PASSWORD'))",
  "            gradle.taskGraph.whenReady { taskGraph ->",
  "                def buildingRelease = taskGraph.allTasks.any { it.name.toLowerCase().contains('release') }",
  "                if (buildingRelease && !mesaleHasReleaseSigning) {",
  "                    throw new GradleException('Missing MESALE_ANDROID_* signing inputs for the Android release build. Provide the keystore path, store password, key alias and key password via Gradle properties or environment variables.')",
  "                }",
  "            }",
  "            if (mesaleHasReleaseSigning) {",
  "                signingConfig signingConfigs.release",
  "            } else {",
  "                signingConfig signingConfigs.debug",
  "            }",
  "            // @generated end " + BUILD_TYPE_TAG
].join("\n");

function injectSigningConfig(contents) {
  if (contents.includes(SIGNING_CONFIG_TAG)) {
    return contents;
  }
  // Add a `release {}` entry inside the existing signingConfigs block.
  const signingConfigsMatch = contents.match(/signingConfigs\s*\{/);
  if (!signingConfigsMatch) {
    throw new Error(
      "withAndroidReleaseSigning: could not find a signingConfigs block in build.gradle."
    );
  }
  const insertAt = signingConfigsMatch.index + signingConfigsMatch[0].length;
  return contents.slice(0, insertAt) + "\n" + SIGNING_CONFIG_SNIPPET + "\n" + contents.slice(insertAt);
}

function injectReleaseBuildType(contents) {
  if (contents.includes(BUILD_TYPE_TAG)) {
    return contents;
  }
  // Replace the template's default `signingConfig signingConfigs.debug` line
  // INSIDE the release block. Appending after it would lose (Groovy keeps the
  // last signingConfig assignment). The debug block precedes the release block
  // in buildTypes, so its own debug line is consumed by the prefix match and the
  // first `signingConfig signingConfigs.debug` we hit is the release one.
  const releaseDebugMatch = contents.match(
    /(buildTypes\s*\{[\s\S]*?release\s*\{[\s\S]*?)signingConfig\s+signingConfigs\.debug/
  );
  if (!releaseDebugMatch) {
    throw new Error(
      "withAndroidReleaseSigning: could not find `signingConfig signingConfigs.debug` inside the buildTypes.release block in build.gradle."
    );
  }
  const prefix = releaseDebugMatch[1];
  const matchStart = releaseDebugMatch.index;
  const matchEnd = matchStart + releaseDebugMatch[0].length;
  // Keep everything up to and including the prefix, drop the debug signingConfig
  // line, splice in our wiring, then keep the rest of the file.
  return (
    contents.slice(0, matchStart) +
    prefix +
    BUILD_TYPE_SNIPPET +
    contents.slice(matchEnd)
  );
}

/**
 * Pure transform: inject the release signingConfig + buildType wiring into the
 * groovy build.gradle contents. Exported for unit testing without the Expo mod
 * machinery. Returns the original string unchanged for non-groovy input.
 */
function applyReleaseSigningToGradle(contents, language) {
  if (language && language !== "groovy") {
    return contents;
  }
  let next = injectSigningConfig(contents);
  next = injectReleaseBuildType(next);
  return next;
}

/**
 * Expo config plugin that configures Android release signing to read the
 * Play upload key from Gradle properties/environment at build time.
 */
function withAndroidReleaseSigning(config) {
  return withAppBuildGradle(config, (gradleConfig) => {
    if (gradleConfig.modResults.language !== "groovy") {
      // eslint-disable-next-line no-console
      console.warn(
        "withAndroidReleaseSigning: skipped because build.gradle is not Groovy."
      );
      return gradleConfig;
    }
    gradleConfig.modResults.contents = applyReleaseSigningToGradle(
      gradleConfig.modResults.contents,
      "groovy"
    );
    return gradleConfig;
  });
}

module.exports = withAndroidReleaseSigning;
module.exports.default = withAndroidReleaseSigning;
module.exports.applyReleaseSigningToGradle = applyReleaseSigningToGradle;
module.exports.SIGNING_CONFIG_TAG = SIGNING_CONFIG_TAG;
module.exports.BUILD_TYPE_TAG = BUILD_TYPE_TAG;
