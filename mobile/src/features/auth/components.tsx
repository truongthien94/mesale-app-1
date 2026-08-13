import type { PropsWithChildren } from "react";
import { ActivityIndicator, Image, Pressable, StyleSheet, Text, TextInput, View, type ImageSourcePropType, type TextInputProps } from "react-native";
import { Link } from "expo-router";
import { AuthKeyboardScreen } from "@/auth/AuthKeyboardScreen";
import { colors, spacing, theme } from "@/theme/tokens";

export function AuthForm({ children, title, subtitle, logo }: PropsWithChildren<{ title: string; subtitle: string; logo?: ImageSourcePropType }>) {
  return (
    <AuthKeyboardScreen>
      <View style={styles.heading}>
        <View style={styles.mark}>
          {logo ? <Image accessibilityLabel="Logo Mê Sale" resizeMode="contain" source={logo} style={styles.logo} /> : <Text style={styles.markText}>M</Text>}
        </View>
        <Text accessibilityRole="header" style={styles.title}>{title}</Text>
        <Text style={styles.subtitle}>{subtitle}</Text>
      </View>
      {children}
    </AuthKeyboardScreen>
  );
}

export function AuthField({ label, error, ...props }: TextInputProps & { label: string; error?: string }) {
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

export function AuthButton({ label, loading, disabled, onPress }: {
  label: string;
  loading?: boolean;
  disabled?: boolean;
  onPress(): void;
}) {
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityState={{ busy: loading, disabled: disabled || loading }}
      disabled={disabled || loading}
      onPress={onPress}
      style={({ pressed }) => [styles.button, (disabled || loading) && styles.disabled, pressed && styles.pressed]}
    >
      {loading ? <ActivityIndicator color={colors.surface} /> : <Text style={styles.buttonText}>{label}</Text>}
    </Pressable>
  );
}

export function AuthLink({ href, children }: PropsWithChildren<{ href: "/login" | "/register" | "/forgot-password" | "/reset-password" }>) {
  return <Link href={href} style={styles.link}>{children}</Link>;
}

export function AuthNotice({ message, tone = "info" }: { message?: string | null; tone?: "info" | "error" | "success" }) {
  if (!message) return null;
  return (
    <View accessibilityLiveRegion="polite" accessibilityRole={tone === "error" ? "alert" : undefined} style={[
      styles.notice,
      tone === "error" && styles.noticeError,
      tone === "success" && styles.noticeSuccess
    ]}>
      <Text style={[styles.noticeText, tone === "error" && styles.noticeErrorText]}>{message}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  heading: { alignItems: "center", gap: spacing.sm, marginBottom: spacing.sm },
  mark: { alignItems: "center", backgroundColor: colors.primary, borderRadius: 16, height: 52, justifyContent: "center", width: 52 },
  markText: { color: colors.surface, fontSize: 24, fontWeight: "900" },
  logo: { borderRadius: 14, height: 48, width: 48 },
  title: { color: colors.text, fontSize: 28, fontWeight: "900", textAlign: "center" },
  subtitle: { color: colors.mutedText, fontSize: 14, lineHeight: 21, textAlign: "center" },
  field: { gap: spacing.xs },
  label: { color: colors.text, fontSize: 13, fontWeight: "700" },
  input: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: theme.radius.md, borderWidth: 1, color: colors.text, fontSize: 15, minHeight: 50, paddingHorizontal: spacing.md, paddingVertical: 13 },
  multiline: { minHeight: 96, textAlignVertical: "top" },
  inputError: { borderColor: colors.danger },
  fieldError: { color: colors.danger, fontSize: 12 },
  button: { alignItems: "center", backgroundColor: colors.primary, borderRadius: theme.radius.md, justifyContent: "center", minHeight: 50, paddingHorizontal: spacing.lg },
  buttonText: { color: colors.surface, fontSize: 15, fontWeight: "800" },
  disabled: { opacity: 0.45 },
  pressed: { opacity: 0.78 },
  link: { color: colors.primary, fontSize: 14, fontWeight: "700", textAlign: "center" },
  notice: { backgroundColor: "#fff7ed", borderColor: "#fed7aa", borderRadius: theme.radius.md, borderWidth: 1, padding: spacing.md },
  noticeError: { backgroundColor: "#fef2f2", borderColor: "#fecaca" },
  noticeSuccess: { backgroundColor: "#f0fdf4", borderColor: "#bbf7d0" },
  noticeText: { color: colors.text, fontSize: 13, lineHeight: 19 },
  noticeErrorText: { color: colors.danger }
});
