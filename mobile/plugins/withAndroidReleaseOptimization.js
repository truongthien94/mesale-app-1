const { withAppBuildGradle, withGradleProperties } = require("expo/config-plugins");

const RELEASE_OPTIMIZATION_PROPERTIES = {
  "android.enableMinifyInReleaseBuilds": "true",
  "android.enableShrinkResourcesInReleaseBuilds": "true",
  "android.enableR8.fullMode": "true",
  "android.r8.optimizedResourceShrinking": "true"
};

function applyReleaseOptimizationToProperties(properties) {
  const propertyNames = new Set(Object.keys(RELEASE_OPTIMIZATION_PROPERTIES));
  return [
    ...properties.filter(
      (property) => property.type !== "property" || !propertyNames.has(property.key)
    ),
    ...Object.entries(RELEASE_OPTIMIZATION_PROPERTIES).map(([key, value]) => ({
      type: "property",
      key,
      value
    }))
  ];
}

function applyReleaseOptimizationToGradle(contents, language) {
  if (language && language !== "groovy") {
    throw new Error("withAndroidReleaseOptimization requires a Groovy app build.gradle.");
  }

  const defaultProguardFile = /getDefaultProguardFile\s*\(\s*(["'])proguard-android(?:-optimize)?\.txt\1\s*\)/g;
  if (!defaultProguardFile.test(contents)) {
    throw new Error("withAndroidReleaseOptimization could not find the default ProGuard configuration.");
  }

  return contents.replace(
    defaultProguardFile,
    'getDefaultProguardFile("proguard-android-optimize.txt")'
  );
}

function withAndroidReleaseOptimization(config) {
  config = withGradleProperties(config, (gradleConfig) => {
    gradleConfig.modResults = applyReleaseOptimizationToProperties(gradleConfig.modResults);
    return gradleConfig;
  });

  return withAppBuildGradle(config, (gradleConfig) => {
    gradleConfig.modResults.contents = applyReleaseOptimizationToGradle(
      gradleConfig.modResults.contents,
      gradleConfig.modResults.language
    );
    return gradleConfig;
  });
}

module.exports = withAndroidReleaseOptimization;
module.exports.default = withAndroidReleaseOptimization;
module.exports.applyReleaseOptimizationToProperties = applyReleaseOptimizationToProperties;
module.exports.applyReleaseOptimizationToGradle = applyReleaseOptimizationToGradle;
module.exports.RELEASE_OPTIMIZATION_PROPERTIES = RELEASE_OPTIMIZATION_PROPERTIES;
