const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const test = require("node:test");
const ts = require("typescript");

function loadTypeScriptModule(filePath) {
  const source = fs.readFileSync(filePath, "utf8");
  const output = ts.transpileModule(source, {
    compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 },
    fileName: filePath,
    reportDiagnostics: true
  });
  const errors = output.diagnostics?.filter((diagnostic) => diagnostic.category === ts.DiagnosticCategory.Error) ?? [];
  assert.equal(errors.length, 0, `${path.basename(filePath)} must transpile without diagnostics`);
  const loadedModule = { exports: {} };
  const execute = new Function("exports", "require", "module", "__filename", "__dirname", output.outputText);
  execute(loadedModule.exports, require, loadedModule, filePath, path.dirname(filePath));
  return loadedModule.exports;
}

const configPath = path.resolve(__dirname, "../app.config.ts");
const appJsonPath = path.resolve(__dirname, "../app.json");
const appConfigModule = loadTypeScriptModule(configPath);
const applyConfig = appConfigModule.default ?? appConfigModule;

const OAUTH_ENV_KEYS = ["EXPO_PUBLIC_GOOGLE_IOS_URL_SCHEME", "EAS_BUILD_PROFILE", "EAS_BUILD_PLATFORM"];

function withEnv(overrides, run) {
  const previous = {};
  for (const key of OAUTH_ENV_KEYS) {
    previous[key] = process.env[key];
    if (Object.prototype.hasOwnProperty.call(overrides, key)) {
      if (overrides[key] === undefined) {
        delete process.env[key];
      } else {
        process.env[key] = overrides[key];
      }
    } else {
      delete process.env[key];
    }
  }
  try {
    return run();
  } finally {
    for (const key of OAUTH_ENV_KEYS) {
      if (previous[key] === undefined) {
        delete process.env[key];
      } else {
        process.env[key] = previous[key];
      }
    }
  }
}

function baseConfig() {
  return { name: "Mê Sale", slug: "mesale-mobile", plugins: ["expo-router"] };
}

function evaluate(overrides) {
  return withEnv(overrides, () => applyConfig({ config: baseConfig() }));
}

const VALID_SCHEME = "com.googleusercontent.apps.example-client-id";

test("Android production build succeeds without the iOS URL scheme", () => {
  const result = evaluate({ EAS_BUILD_PROFILE: "production", EAS_BUILD_PLATFORM: "android" });
  assert.equal(result.slug, "mesale-mobile");
  const plugins = result.plugins ?? [];
  const hasGooglePlugin = plugins.some(
    (plugin) => Array.isArray(plugin) && plugin[0] === "@react-native-google-signin/google-signin"
  );
  assert.equal(hasGooglePlugin, false);
});

test("Android release optimization is registered without an iOS URL scheme", () => {
  const result = evaluate({ EAS_BUILD_PROFILE: "production", EAS_BUILD_PLATFORM: "android" });
  assert.equal(
    result.plugins.filter((plugin) => plugin === "./plugins/withAndroidReleaseOptimization").length,
    1
  );
});

test("Android release optimization remains registered when native Google Sign-In is configured", () => {
  const result = evaluate({
    EXPO_PUBLIC_GOOGLE_IOS_URL_SCHEME: VALID_SCHEME,
    EAS_BUILD_PROFILE: "production",
    EAS_BUILD_PLATFORM: "android"
  });
  assert.equal(
    result.plugins.filter((plugin) => plugin === "./plugins/withAndroidReleaseOptimization").length,
    1
  );
});

test("iOS production build still requires the iOS URL scheme", () => {
  assert.throws(
    () => evaluate({ EAS_BUILD_PROFILE: "production", EAS_BUILD_PLATFORM: "ios" }),
    /EXPO_PUBLIC_GOOGLE_IOS_URL_SCHEME is required for iOS production builds\./
  );
});

test("iOS TestFlight build still requires the iOS URL scheme", () => {
  assert.throws(
    () => evaluate({ EAS_BUILD_PROFILE: "testflight", EAS_BUILD_PLATFORM: "ios" }),
    /EXPO_PUBLIC_GOOGLE_IOS_URL_SCHEME is required for iOS production builds\./
  );
});

test("production build with unknown platform still requires the iOS URL scheme", () => {
  assert.throws(
    () => evaluate({ EAS_BUILD_PROFILE: "production" }),
    /EXPO_PUBLIC_GOOGLE_IOS_URL_SCHEME is required for iOS production builds\./
  );
});

test("non-production builds do not require the iOS URL scheme", () => {
  const result = evaluate({ EAS_BUILD_PROFILE: "preview" });
  assert.equal(result.slug, "mesale-mobile");
});

test("a provided scheme must be the reversed Google iOS client ID", () => {
  assert.throws(
    () => evaluate({ EXPO_PUBLIC_GOOGLE_IOS_URL_SCHEME: "not-a-reversed-client-id" }),
    /must be the reversed Google iOS client ID/
  );
});

test("a valid scheme adds the native Google Sign-In plugin", () => {
  const result = evaluate({
    EXPO_PUBLIC_GOOGLE_IOS_URL_SCHEME: VALID_SCHEME,
    EAS_BUILD_PROFILE: "production",
    EAS_BUILD_PLATFORM: "ios"
  });
  const plugins = result.plugins ?? [];
  const googlePlugin = plugins.find(
    (plugin) => Array.isArray(plugin) && plugin[0] === "@react-native-google-signin/google-signin"
  );
  assert.ok(googlePlugin, "expected the Google Sign-In plugin to be appended");
  assert.equal(googlePlugin[1].iosUrlScheme, VALID_SCHEME);
});

test("the iOS privacy manifest declares each collected data type once", () => {
  const appJson = JSON.parse(fs.readFileSync(appJsonPath, "utf8"));
  const collectedTypes = appJson.expo.ios.privacyManifests.NSPrivacyCollectedDataTypes;
  const typeNames = collectedTypes.map((item) => item.NSPrivacyCollectedDataType);

  assert.equal(typeNames.length, new Set(typeNames).size);
});
