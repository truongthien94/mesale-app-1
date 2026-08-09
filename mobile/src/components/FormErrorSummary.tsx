import { StyleSheet, Text, View } from "react-native";
import type { ApiFieldErrors } from "@/api/contract";
import { colors, spacing } from "@/theme/tokens";

export function FormErrorSummary({
  errors,
  message
}: {
  errors?: ApiFieldErrors;
  message?: string;
}) {
  const fieldMessages = errors ? [...new Set(Object.values(errors).flat())] : [];
  if (!message && fieldMessages.length === 0) return null;

  return (
    <View accessibilityLiveRegion="polite" accessibilityRole="alert" style={styles.container}>
      {message ? <Text style={styles.message}>{message}</Text> : null}
      {fieldMessages.map((fieldMessage) => (
        <Text key={fieldMessage} style={styles.message}>{fieldMessage}</Text>
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: "#fef2f2",
    borderColor: "#fecaca",
    borderRadius: 12,
    borderWidth: 1,
    gap: spacing.xs,
    padding: spacing.md
  },
  message: {
    color: colors.danger,
    fontSize: 14,
    lineHeight: 20
  }
});
