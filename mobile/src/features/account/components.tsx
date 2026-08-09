import type { PropsWithChildren, ReactNode } from "react";
import { ActivityIndicator, KeyboardAvoidingView, Platform, Pressable, ScrollView, StyleSheet, Text, TextInput, View, type TextInputProps } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { ApiError } from "@/api/client";
import { FormErrorSummary } from "@/components/FormErrorSummary";
import { colors, spacing, theme } from "@/theme/tokens";

export function AccountFormScreen({ children }: PropsWithChildren) {
  const insets = useSafeAreaInsets();
  return (
    <KeyboardAvoidingView behavior={Platform.OS === "ios" ? "padding" : undefined} style={styles.screen}>
      <ScrollView
        contentContainerStyle={[styles.content, { paddingBottom: spacing.xl + insets.bottom }]}
        contentInsetAdjustmentBehavior="automatic"
        keyboardShouldPersistTaps="handled"
      >
        {children}
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

export function AccountHeader({ title, subtitle, eyebrow }: { title: string; subtitle?: string; eyebrow?: string }) {
  return (
    <View style={styles.header}>
      {eyebrow ? <Text style={styles.eyebrow}>{eyebrow}</Text> : null}
      <Text accessibilityRole="header" style={styles.title}>{title}</Text>
      {subtitle ? <Text style={styles.subtitle}>{subtitle}</Text> : null}
    </View>
  );
}

export function AccountCard({ children, tone = "default" }: PropsWithChildren<{ tone?: "default" | "danger" }>) {
  return <View style={[styles.card, tone === "danger" && styles.dangerCard]}>{children}</View>;
}

export function AccountField({ label, error, ...props }: TextInputProps & { label: string; error?: string }) {
  return (
    <View style={styles.field}>
      <Text style={styles.label}>{label}</Text>
      <TextInput
        accessibilityLabel={label}
        placeholderTextColor={colors.mutedText}
        style={[styles.input, props.multiline && styles.multiline, error && styles.inputError]}
        {...props}
      />
      {error ? <Text accessibilityRole="alert" style={styles.fieldError}>{error}</Text> : null}
    </View>
  );
}

export function AccountButton({ label, onPress, loading, disabled, tone = "primary", compact = false }: {
  label: string;
  onPress(): void;
  loading?: boolean;
  disabled?: boolean;
  tone?: "primary" | "secondary" | "danger";
  compact?: boolean;
}) {
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityState={{ busy: loading, disabled: disabled || loading }}
      disabled={disabled || loading}
      onPress={onPress}
      style={({ pressed }) => [
        styles.button,
        compact && styles.compactButton,
        tone === "secondary" && styles.secondaryButton,
        tone === "danger" && styles.dangerButton,
        (disabled || loading) && styles.disabled,
        pressed && styles.pressed
      ]}
    >
      {loading ? <ActivityIndicator color={tone === "secondary" ? colors.primary : colors.surface} /> : null}
      <Text style={[styles.buttonText, tone === "secondary" && styles.secondaryButtonText]}>{label}</Text>
    </Pressable>
  );
}

export function AccountMenuRow({ title, subtitle, onPress, accessory }: { title: string; subtitle?: string; onPress(): void; accessory?: ReactNode }) {
  return (
    <Pressable accessibilityRole="button" onPress={onPress} style={({ pressed }) => [styles.menuRow, pressed && styles.pressed]}>
      <View style={styles.menuCopy}>
        <Text style={styles.menuTitle}>{title}</Text>
        {subtitle ? <Text style={styles.menuSubtitle}>{subtitle}</Text> : null}
      </View>
      {accessory ?? <Text style={styles.chevron}>›</Text>}
    </Pressable>
  );
}

export function AccountMutationError({ error }: { error: unknown }) {
  if (!error) return null;
  const apiError = error instanceof ApiError ? error : null;
  return <FormErrorSummary errors={apiError?.errors} message={error instanceof Error ? error.message : "Đã có lỗi xảy ra."} />;
}

export function AccountNotice({ children, tone = "info" }: PropsWithChildren<{ tone?: "info" | "success" | "danger" }>) {
  return (
    <View accessibilityLiveRegion="polite" style={[
      styles.notice,
      tone === "success" && styles.successNotice,
      tone === "danger" && styles.dangerNotice
    ]}>
      <Text style={[styles.noticeText, tone === "danger" && styles.dangerNoticeText]}>{children}</Text>
    </View>
  );
}

export const accountStyles = StyleSheet.create({
  section: { gap: spacing.md },
  row: { alignItems: "center", flexDirection: "row", gap: spacing.md, justifyContent: "space-between" },
  body: { color: colors.mutedText, fontSize: 13, lineHeight: 20 },
  strong: { color: colors.text, fontSize: 14, fontWeight: "800" },
  dangerText: { color: colors.danger, fontSize: 13, lineHeight: 20 },
  options: { flexDirection: "row", flexWrap: "wrap", gap: spacing.sm }
});

const styles = StyleSheet.create({
  screen: { backgroundColor: colors.background, flex: 1 },
  content: { gap: spacing.md, padding: spacing.md },
  header: { gap: spacing.xs, paddingVertical: spacing.sm },
  eyebrow: { color: colors.primary, fontSize: 11, fontWeight: "900", letterSpacing: 0.8, textTransform: "uppercase" },
  title: { color: colors.text, fontSize: 27, fontWeight: "900" },
  subtitle: { color: colors.mutedText, fontSize: 14, lineHeight: 21 },
  card: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: theme.radius.lg, borderWidth: 1, gap: spacing.md, padding: spacing.md },
  dangerCard: { borderColor: "#fecaca" },
  field: { gap: spacing.xs },
  label: { color: colors.text, fontSize: 12, fontWeight: "800", letterSpacing: 0.3, textTransform: "uppercase" },
  input: { backgroundColor: "#f8fafc", borderColor: colors.border, borderRadius: theme.radius.md, borderWidth: 1, color: colors.text, fontSize: 14, minHeight: 50, paddingHorizontal: spacing.md, paddingVertical: 12 },
  multiline: { minHeight: 96, textAlignVertical: "top" },
  inputError: { borderColor: colors.danger },
  fieldError: { color: colors.danger, fontSize: 12 },
  button: { alignItems: "center", backgroundColor: colors.primary, borderRadius: theme.radius.md, flexDirection: "row", gap: spacing.sm, justifyContent: "center", minHeight: 50, paddingHorizontal: spacing.lg },
  compactButton: { minHeight: 40, paddingHorizontal: spacing.md },
  secondaryButton: { backgroundColor: "#fff7ed", borderColor: "#fed7aa", borderWidth: 1 },
  dangerButton: { backgroundColor: colors.danger },
  buttonText: { color: colors.surface, fontSize: 14, fontWeight: "800" },
  secondaryButtonText: { color: colors.primary },
  disabled: { opacity: 0.45 },
  pressed: { opacity: 0.78 },
  menuRow: { alignItems: "center", flexDirection: "row", gap: spacing.md, minHeight: 62, paddingVertical: spacing.sm },
  menuCopy: { flex: 1, gap: spacing.xs },
  menuTitle: { color: colors.text, fontSize: 15, fontWeight: "800" },
  menuSubtitle: { color: colors.mutedText, fontSize: 12, lineHeight: 17 },
  chevron: { color: colors.mutedText, fontSize: 30, fontWeight: "300" },
  notice: { backgroundColor: "#fff7ed", borderColor: "#fed7aa", borderRadius: theme.radius.md, borderWidth: 1, padding: spacing.md },
  successNotice: { backgroundColor: "#f0fdf4", borderColor: "#bbf7d0" },
  dangerNotice: { backgroundColor: "#fef2f2", borderColor: "#fecaca" },
  noticeText: { color: colors.text, fontSize: 13, lineHeight: 20 },
  dangerNoticeText: { color: colors.danger }
});
