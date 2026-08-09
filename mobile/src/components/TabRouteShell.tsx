import { StyleSheet, View } from "react-native";
import { colors } from "@/theme/tokens";

export function TabRouteShell() {
  return <View accessibilityElementsHidden importantForAccessibility="no-hide-descendants" style={styles.container} />;
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: colors.background,
    flex: 1
  }
});
