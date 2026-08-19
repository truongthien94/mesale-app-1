const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const test = require("node:test");
const ts = require("typescript");

function read(relativePath) {
  return fs.readFileSync(path.resolve(__dirname, relativePath), "utf8");
}

function loadThemePreference(platform, secureStore) {
  const filePath = path.resolve(__dirname, "../src/theme/themePreference.ts");
  const output = ts.transpileModule(fs.readFileSync(filePath, "utf8"), {
    compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 },
    fileName: filePath,
    reportDiagnostics: true
  });
  const errors = output.diagnostics?.filter((diagnostic) => diagnostic.category === ts.DiagnosticCategory.Error) ?? [];
  assert.equal(errors.length, 0, "theme preference module must transpile without diagnostics");

  const loadedModule = { exports: {} };
  const localRequire = (specifier) => {
    if (specifier === "expo-secure-store") return secureStore;
    if (specifier === "react-native") return { Platform: { OS: platform } };
    return require(specifier);
  };
  const execute = new Function("exports", "require", "module", "__filename", "__dirname", output.outputText);
  execute(loadedModule.exports, localRequire, loadedModule, filePath, path.dirname(filePath));
  return loadedModule.exports;
}

test("theme preference storage is validated and web-safe", async () => {
  let reads = 0;
  let writes = 0;
  const secureStore = {
    WHEN_UNLOCKED_THIS_DEVICE_ONLY: "device-only",
    getItemAsync: async () => {
      reads += 1;
      return JSON.stringify("dark");
    },
    setItemAsync: async () => {
      writes += 1;
    }
  };
  const web = loadThemePreference("web", secureStore);
  assert.equal(await web.loadThemePreference(), null);
  await web.saveThemePreference("dark");
  assert.equal(reads, 0);
  assert.equal(writes, 0);

  const native = loadThemePreference("android", secureStore);
  assert.equal(await native.loadThemePreference(), "dark");
  secureStore.getItemAsync = async () => JSON.stringify("unexpected");
  assert.equal(await native.loadThemePreference(), null);
  secureStore.getItemAsync = async () => "not-json";
  assert.equal(await native.loadThemePreference(), null);
});

test("theme preference writes are serialized and stale hydration revisions are rejected", async () => {
  const calls = [];
  const resolvers = [];
  const module = loadThemePreference("android", {
    WHEN_UNLOCKED_THIS_DEVICE_ONLY: "device-only",
    getItemAsync: async () => null,
    setItemAsync: async () => undefined
  });
  const coordinator = module.createThemePreferenceCoordinator((value) => new Promise((resolve) => {
    calls.push(value);
    resolvers.push(resolve);
  }));

  const hydrationRevision = coordinator.currentRevision();
  coordinator.persist("dark");
  coordinator.persist("light");
  await new Promise(setImmediate);
  assert.deepEqual(calls, ["dark"]);
  assert.equal(coordinator.isCurrentRevision(hydrationRevision), false);

  resolvers.shift()();
  await new Promise(setImmediate);
  assert.deepEqual(calls, ["dark", "light"]);
  resolvers.shift()();
  await coordinator.waitForIdle();
  assert.equal(coordinator.currentRevision(), 2);
});

test("Round A keeps current UI stable while mounting the theme boundary", () => {
  const tokens = read("../src/theme/tokens.ts");
  const layout = read("../app/_layout.tsx");
  const appConfig = JSON.parse(read("../app.json"));
  assert.match(tokens, /export const colors = lightColors/);
  assert.match(tokens, /primary: "#f97316"/g);
  assert.match(layout, /<ThemeProvider>[\s\S]*<AuthProvider>/);
  assert.match(layout, /function ThemedStatusBar/);
  assert.match(layout, /style=\{scheme === "dark" \? "light" : "dark"\}/);
  assert.match(layout, /<ThemedStatusBar \/>/);
  assert.equal(appConfig.expo.ios.infoPlist.UIViewControllerBasedStatusBarAppearance, false);
});

test("shared async states use the active theme in light and dark mode", () => {
  const source = read("../src/components/AsyncState.tsx");

  assert.match(source, /import \{ useTheme \} from "@\/theme\/ThemeProvider"/);
  assert.match(source, /const \{ colors \} = useTheme\(\)/g);
  assert.match(source, /backgroundColor: colors\.background/g);
  assert.match(source, /color: colors\.text/);
  assert.match(source, /color: colors\.mutedText/g);
  assert.match(source, /backgroundColor: colors\.primary/);
  assert.doesNotMatch(source, /import \{ colors, spacing \} from "@\/theme\/tokens"/);
});
