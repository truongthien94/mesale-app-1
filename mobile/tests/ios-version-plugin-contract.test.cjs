const assert = require("node:assert/strict");
const test = require("node:test");

const {
  applyInfoPlistVersionSettings,
  applyXcodeVersionSettings
} = require("../plugins/withIosVersionSettings");

test("iOS Info.plist resolves version values from Xcode build settings", () => {
  const result = applyInfoPlistVersionSettings({ ExistingKey: true });
  assert.equal(result.CFBundleShortVersionString, "$(MARKETING_VERSION)");
  assert.equal(result.CFBundleVersion, "$(CURRENT_PROJECT_VERSION)");
  assert.equal(result.ExistingKey, true);
});

test("iOS Xcode project receives app.json marketing and build versions", () => {
  const calls = [];
  const project = {
    addBuildProperty(name, value) {
      calls.push([name, value]);
    }
  };

  assert.equal(applyXcodeVersionSettings(project, "1.0.1", "1"), project);
  assert.deepEqual(calls, [
    ["MARKETING_VERSION", "1.0.1"],
    ["CURRENT_PROJECT_VERSION", "1"]
  ]);
});
