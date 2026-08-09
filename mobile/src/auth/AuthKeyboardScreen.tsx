import type { PropsWithChildren } from "react";
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { colors, spacing } from "@/theme/tokens";

export function AuthKeyboardScreen({ children }: PropsWithChildren) {
  const insets = useSafeAreaInsets();

  return (
    <KeyboardAvoidingView behavior={Platform.OS === "ios" ? "padding" : "height"} style={styles.keyboardAvoidingView}>
      <ScrollView
        contentContainerStyle={[
          styles.content,
          {
            paddingTop: spacing.lg + insets.top,
            paddingBottom: spacing.lg + insets.bottom,
            paddingLeft: spacing.lg + insets.left,
            paddingRight: spacing.lg + insets.right
          }
        ]}
        keyboardDismissMode={Platform.OS === "ios" ? "interactive" : "on-drag"}
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={false}
        style={styles.scrollView}
      >
        {children}
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  keyboardAvoidingView: { flex: 1, backgroundColor: colors.background },
  scrollView: { flex: 1 },
  content: { flexGrow: 1, justifyContent: "center", gap: spacing.md, backgroundColor: colors.background }
});
