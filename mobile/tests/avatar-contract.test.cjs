const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const test = require("node:test");
const ts = require("typescript");
const bundledNativeModules = require("expo/bundledNativeModules.json");

function read(relativePath) {
  return fs.readFileSync(path.resolve(__dirname, relativePath), "utf8");
}

function loadAvatarHelper(mocks) {
  const filePath = path.resolve(__dirname, "../src/features/account/avatar.ts");
  const output = ts.transpileModule(read("../src/features/account/avatar.ts"), {
    compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 },
    fileName: filePath,
    reportDiagnostics: true
  });
  const errors = output.diagnostics?.filter((diagnostic) => diagnostic.category === ts.DiagnosticCategory.Error) ?? [];
  assert.equal(errors.length, 0, "avatar helper must transpile without diagnostics");
  const loadedModule = { exports: {} };
  const localRequire = (specifier) => Object.hasOwn(mocks, specifier) ? mocks[specifier] : require(specifier);
  const execute = new Function("exports", "require", "module", "__filename", "__dirname", output.outputText);
  execute(loadedModule.exports, localRequire, loadedModule, filePath, path.dirname(filePath));
  return loadedModule.exports;
}

test("avatar dependencies and system photo-picker configuration request no camera or broad media access", () => {
  const packageJson = JSON.parse(read("../package.json"));
  const appJson = JSON.parse(read("../app.json"));
  const imagePickerPlugin = appJson.expo.plugins.find((plugin) => Array.isArray(plugin) && plugin[0] === "expo-image-picker");

  assert.equal(packageJson.dependencies["expo-image-picker"], bundledNativeModules["expo-image-picker"]);
  assert.equal(packageJson.dependencies["expo-image-manipulator"], bundledNativeModules["expo-image-manipulator"]);
  assert.ok(imagePickerPlugin);
  assert.equal(imagePickerPlugin[1].cameraPermission, false);
  assert.equal(imagePickerPlugin[1].microphonePermission, false);
  assert.match(imagePickerPlugin[1].photosPermission, /ảnh đại diện/);
  for (const permission of [
    "android.permission.CAMERA",
    "android.permission.RECORD_AUDIO",
    "android.permission.READ_MEDIA_IMAGES",
    "android.permission.READ_MEDIA_VIDEO",
    "android.permission.READ_EXTERNAL_STORAGE",
    "android.permission.WRITE_EXTERNAL_STORAGE"
  ]) {
    assert.ok(appJson.expo.android.blockedPermissions.includes(permission));
  }
});

test("API client sends native FormData without overriding its multipart boundary", () => {
  const source = read("../src/api/client.ts");
  assert.match(source, /body instanceof FormData/);
  assert.match(source, /body !== undefined && !isFormDataBody/);
  assert.match(source, /isFormDataBody \? body : JSON\.stringify\(body\)/);
  assert.doesNotMatch(source, /multipart\/form-data/);
});

test("avatar hooks use one authenticated path and invalidate the account query prefix", () => {
  const contracts = read("../src/features/account/contracts.ts");
  const api = read("../src/features/account/api.ts");

  assert.match(contracts, /avatar: "account\/avatar"/);
  assert.match(api, /import \{ File \} from "expo-file-system"/);
  assert.match(api, /const file = new File\(avatar\.uri\)/);
  assert.match(api, /if \(!file\.exists\) throw new Error\("Không thể đọc tệp ảnh đã xử lý\."\)/);
  assert.match(api, /formData\.append\("avatar", file, avatar\.name\)/);
  assert.match(api, /type AvatarMutationResult = \{[\s\S]*avatar: string \| null;[\s\S]*avatar_url\?: string \| null/);
  assert.match(api, /requestEnvelope<AvatarMutationResult>\(accountPaths\.avatar,[\s\S]*method: "POST"/);
  assert.match(api, /requestEnvelope<AvatarMutationResult>\(accountPaths\.avatar, \{ method: "DELETE" \}\)/);
  assert.equal((api.match(/invalidateQueries\(\{ queryKey: \["account"\] \}\)/g) ?? []).length, 2);
  assert.doesNotMatch(api, /uri: avatar\.uri/);
  assert.doesNotMatch(api, /Content-Type|multipart\/form-data/);
});

test("avatar picker enforces image-only square output with a 512px ceiling and no metadata payload", () => {
  const source = read("../src/features/account/avatar.ts");

  assert.match(source, /mediaTypes: \["images"\]/);
  assert.match(source, /allowsEditing: true/);
  assert.match(source, /aspect: \[1, 1\]/);
  assert.match(source, /allowsMultipleSelection: false/);
  assert.match(source, /base64: false/);
  assert.match(source, /exif: false/);
  assert.match(source, /const MAX_AVATAR_SIZE = 512/);
  assert.match(source, /crop:[\s\S]*resize:/);
  assert.match(source, /format: SaveFormat\.JPEG/);
  assert.match(source, /compress: 0\.85/);
  assert.match(source, /if \(result\.canceled\) return null/);
  assert.doesNotMatch(source, /launchCameraAsync|requestMediaLibraryPermissionsAsync|requestCameraPermissionsAsync/);
});

test("avatar picker cancellation performs no image processing and large images are center-cropped", async () => {
  let pickerResult = { canceled: true, assets: null };
  const manipulations = [];
  const helper = loadAvatarHelper({
    "expo-image-picker": { launchImageLibraryAsync: async () => pickerResult },
    "expo-image-manipulator": {
      SaveFormat: { JPEG: "jpeg" },
      manipulateAsync: async (...args) => {
        manipulations.push(args);
        return { uri: "file:///avatar-output.jpg", width: 512, height: 512 };
      }
    }
  });

  assert.equal(await helper.pickAvatarImage(), null);
  assert.equal(manipulations.length, 0);

  pickerResult = { canceled: false, assets: [{ uri: "file:///source.jpg", width: 1024, height: 768 }] };
  assert.deepEqual(await helper.pickAvatarImage(), {
    uri: "file:///avatar-output.jpg",
    name: "avatar.jpg",
    type: "image/jpeg"
  });
  assert.deepEqual(manipulations[0][1], [
    { crop: { originX: 128, originY: 0, width: 768, height: 768 } },
    { resize: { width: 512, height: 512 } }
  ]);
  assert.deepEqual(manipulations[0][2], { base64: false, compress: 0.85, format: "jpeg" });
});

test("profile previews before upload and refreshes the authenticated user after upload or deletion", () => {
  const editor = read("../src/features/account/AvatarEditor.tsx");
  const profile = read("../app/(tabs)/account/profile.tsx");

  assert.match(profile, /<AvatarEditor avatar=\{query\.data\.avatar\} name=\{query\.data\.name\} \/>/);
  assert.match(editor, /const image = await pickAvatarImage\(\);[\s\S]*if \(!image\) return;[\s\S]*setSelectedImage\(image\)/);
  assert.match(editor, /await uploadMutation\.mutateAsync\(selectedImage\);[\s\S]*await refreshUser\(\)/);
  assert.match(editor, /await deleteMutation\.mutateAsync\(\);[\s\S]*await refreshUser\(\)/);
  assert.match(editor, /Xem trước ảnh mới/);
  assert.match(editor, /Tải ảnh lên/);
  assert.match(editor, /Xóa ảnh đại diện/);
});
