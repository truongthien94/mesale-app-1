const { withInfoPlist, withXcodeProject } = require("expo/config-plugins");

function applyInfoPlistVersionSettings(infoPlist) {
  return {
    ...infoPlist,
    CFBundleShortVersionString: "$(MARKETING_VERSION)",
    CFBundleVersion: "$(CURRENT_PROJECT_VERSION)"
  };
}

function applyXcodeVersionSettings(project, version, buildNumber) {
  project.addBuildProperty("MARKETING_VERSION", version);
  project.addBuildProperty("CURRENT_PROJECT_VERSION", buildNumber);
  return project;
}

function withIosVersionSettings(config) {
  const version = config.version;
  const buildNumber = config.ios?.buildNumber;
  if (!version || !buildNumber) {
    throw new Error("withIosVersionSettings requires expo.version and expo.ios.buildNumber.");
  }

  config = withInfoPlist(config, (modConfig) => {
    modConfig.modResults = applyInfoPlistVersionSettings(modConfig.modResults);
    return modConfig;
  });

  return withXcodeProject(config, (modConfig) => {
    modConfig.modResults = applyXcodeVersionSettings(
      modConfig.modResults,
      version,
      buildNumber
    );
    return modConfig;
  });
}

module.exports = withIosVersionSettings;
module.exports.default = withIosVersionSettings;
module.exports.applyInfoPlistVersionSettings = applyInfoPlistVersionSettings;
module.exports.applyXcodeVersionSettings = applyXcodeVersionSettings;
