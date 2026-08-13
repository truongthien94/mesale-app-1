const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const test = require("node:test");

const mobileRoot = path.join(__dirname, "..");
const config = JSON.parse(fs.readFileSync(path.join(mobileRoot, "app.json"), "utf8"));

test("Android launcher uses a dedicated safe-zone adaptive foreground", () => {
  const foreground = config.expo.android.adaptiveIcon.foregroundImage;
  const foregroundPath = path.join(mobileRoot, foreground);
  const png = fs.readFileSync(foregroundPath);

  assert.equal(foreground, "./assets/mesale-adaptive-icon.png");
  assert.notEqual(foreground, config.expo.icon);
  assert.equal(config.expo.android.adaptiveIcon.backgroundColor, "#ffffff");
  assert.equal(png.toString("ascii", 1, 4), "PNG");
  assert.equal(png.readUInt32BE(16), 1024);
  assert.equal(png.readUInt32BE(20), 1024);
  assert.equal(png[25], 6, "adaptive foreground must preserve RGBA transparency");
});

test("the installed application name preserves the Mê Sale brand", () => {
  assert.equal(config.expo.name, "Mê Sale");
});
