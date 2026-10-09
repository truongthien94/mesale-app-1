const assert = require("node:assert/strict");
const { execFileSync } = require("node:child_process");
const path = require("node:path");

function validateR8Metadata(metadata) {
  const requiredOptions = {
    isObfuscationEnabled: true,
    isOptimizationsEnabled: true,
    isShrinkingEnabled: true,
    isProGuardCompatibilityModeEnabled: false,
    isDebugModeEnabled: false
  };

  for (const [option, expected] of Object.entries(requiredOptions)) {
    assert.equal(metadata.options?.[option], expected, `Invalid release R8 option: ${option}`);
  }
  assert.equal(
    metadata.resourceOptimization?.isOptimizedShrinkingEnabled,
    true,
    "Android release must enable optimized resource shrinking."
  );
}

function verifyAndroidReleaseBundle(bundlePath) {
  const absolutePath = path.resolve(bundlePath);
  const entries = execFileSync("unzip", ["-Z1", absolutePath], { encoding: "utf8" }).split("\n");
  const metadataPath = "BUNDLE-METADATA/com.android.tools/r8.json";
  const mappingPath = "BUNDLE-METADATA/com.android.tools.build.obfuscation/proguard.map";
  assert(entries.includes(metadataPath), "AAB is missing R8 metadata. Run an optimized release build.");
  assert(entries.includes(mappingPath), "AAB is missing the R8 obfuscation mapping.");

  const metadata = JSON.parse(
    execFileSync("unzip", ["-p", absolutePath, metadataPath], { encoding: "utf8" })
  );
  validateR8Metadata(metadata);
  return { bundle: absolutePath, r8Version: metadata.version, mode: "full", optimized: true };
}

if (require.main === module) {
  try {
    const result = verifyAndroidReleaseBundle(
      process.argv[2] ?? "android/app/build/outputs/bundle/release/app-release.aab"
    );
    console.log(JSON.stringify(result, null, 2));
  } catch (error) {
    console.error(`Android release optimization verification failed: ${error.message}`);
    process.exitCode = 1;
  }
}

module.exports = { validateR8Metadata, verifyAndroidReleaseBundle };
