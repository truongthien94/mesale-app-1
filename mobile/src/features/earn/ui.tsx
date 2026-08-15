import type { ReactNode } from "react";
import {
  ActivityIndicator,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
  type KeyboardTypeOptions,
  type TextInputProps
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { colors, spacing } from "@/theme/tokens";

export function formatMoney(amount: number): string {
  return `${new Intl.NumberFormat("vi-VN", { maximumFractionDigits: 0 }).format(amount)} đ`;
}

export function formatDate(value: string | null): string {
  if (!value) return "—";
  const timestamp = Date.parse(value);
  if (Number.isNaN(timestamp)) return value;
  return new Intl.DateTimeFormat("vi-VN", { dateStyle: "medium" }).format(timestamp);
}

export function ScreenHeader({
  eyebrow,
  title,
  subtitle,
  action
}: {
  eyebrow?: string;
  title: string;
  subtitle?: string;
  action?: ReactNode;
}) {
  const insets = useSafeAreaInsets();

  return (
    <View style={[styles.header, { paddingTop: insets.top + spacing.md }]}>
      <View style={styles.headerText}>
        {eyebrow ? <Text style={styles.eyebrow}>{eyebrow}</Text> : null}
        <Text accessibilityRole="header" style={styles.title}>{title}</Text>
        {subtitle ? <Text style={styles.subtitle}>{subtitle}</Text> : null}
      </View>
      {action}
    </View>
  );
}

export function Card({ children, accent = false }: { children: ReactNode; accent?: boolean }) {
  return <View style={[styles.card, accent && styles.cardAccent]}>{children}</View>;
}

export function ActionButton({
  label,
  onPress,
  disabled = false,
  loading = false,
  tone = "primary"
}: {
  label: string;
  onPress: () => void;
  disabled?: boolean;
  loading?: boolean;
  tone?: "primary" | "secondary" | "danger";
}) {
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityState={{ disabled: disabled || loading, busy: loading }}
      disabled={disabled || loading}
      onPress={onPress}
      style={({ pressed }) => [
        styles.button,
        tone === "secondary" && styles.buttonSecondary,
        tone === "danger" && styles.buttonDanger,
        (disabled || loading) && styles.buttonDisabled,
        pressed && styles.buttonPressed
      ]}
    >
      {loading ? <ActivityIndicator color={tone === "secondary" ? colors.primary : colors.surface} /> : null}
      <Text style={[styles.buttonText, tone === "secondary" && styles.buttonSecondaryText]}>{label}</Text>
    </Pressable>
  );
}

export function FilterChip({ label, selected, onPress }: { label: string; selected: boolean; onPress: () => void }) {
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityState={{ selected }}
      onPress={onPress}
      style={({ pressed }) => [styles.chip, selected && styles.chipSelected, pressed && styles.buttonPressed]}
    >
      <Text style={[styles.chipText, selected && styles.chipTextSelected]}>{label}</Text>
    </Pressable>
  );
}

export function Field({
  label,
  value,
  onChangeText,
  placeholder,
  keyboardType,
  multiline = false,
  autoCapitalize = "sentences",
  ...props
}: {
  label: string;
  value: string;
  onChangeText: (value: string) => void;
  placeholder?: string;
  keyboardType?: KeyboardTypeOptions;
  multiline?: boolean;
} & Omit<TextInputProps, "value" | "onChangeText" | "placeholder" | "keyboardType" | "multiline">) {
  return (
    <View style={styles.field}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <TextInput
        {...props}
        autoCapitalize={autoCapitalize}
        keyboardType={keyboardType}
        multiline={multiline}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor="#94a3b8"
        style={[styles.input, multiline && styles.inputMultiline]}
        value={value}
      />
    </View>
  );
}

export function StatusBadge({ status }: { status: string }) {
  const normalized = status.toLowerCase();
  const positive = ["approved", "claimable", "completed", "claimed", "success"].includes(normalized);
  const negative = ["rejected", "failed", "expired"].includes(normalized);

  return (
    <View style={[styles.badge, positive && styles.badgePositive, negative && styles.badgeNegative]}>
      <Text style={[styles.badgeText, positive && styles.badgePositiveText, negative && styles.badgeNegativeText]}>
        {status.replaceAll("_", " ")}
      </Text>
    </View>
  );
}

export function ListFooterLoading({ visible }: { visible: boolean }) {
  return visible ? <ActivityIndicator color={colors.primary} style={styles.footerLoader} /> : null;
}

export const earnStyles = StyleSheet.create({
  screen: { backgroundColor: colors.background, flex: 1 },
  listContent: { gap: spacing.md, padding: spacing.md },
  row: { alignItems: "center", flexDirection: "row", gap: spacing.sm },
  rowBetween: { alignItems: "center", flexDirection: "row", gap: spacing.sm, justifyContent: "space-between" },
  sectionTitle: { color: colors.text, fontSize: 16, fontWeight: "800" },
  body: { color: colors.mutedText, fontSize: 14, lineHeight: 21 },
  strong: { color: colors.text, fontSize: 15, fontWeight: "800" },
  amount: { color: colors.primary, fontSize: 18, fontWeight: "900" },
  divider: { backgroundColor: colors.border, height: 1 },
  chips: { flexDirection: "row", flexWrap: "wrap", gap: spacing.sm },
  feedback: { backgroundColor: "#fff7ed", borderColor: "#fed7aa", borderRadius: 12, borderWidth: 1, padding: spacing.md },
  feedbackText: { color: "#9a3412", fontSize: 14, lineHeight: 20 },
  form: { gap: spacing.md },
  modalBackdrop: { backgroundColor: "rgba(15, 23, 42, 0.45)", flex: 1, justifyContent: "flex-end" },
  modalCard: { backgroundColor: colors.background, borderTopLeftRadius: 24, borderTopRightRadius: 24, maxHeight: "92%", padding: spacing.md },
  progressTrack: { backgroundColor: "#e2e8f0", borderRadius: 999, height: 8, overflow: "hidden" },
  progressFill: { backgroundColor: colors.primary, borderRadius: 999, height: "100%" }
});

const styles = StyleSheet.create({
  header: { alignItems: "flex-start", flexDirection: "row", gap: spacing.md, justifyContent: "space-between", paddingBottom: spacing.sm },
  headerText: { flex: 1, gap: spacing.xs },
  eyebrow: { color: colors.primary, fontSize: 12, fontWeight: "900", letterSpacing: 1.2, textTransform: "uppercase" },
  title: { color: colors.text, fontSize: 25, fontWeight: "900", letterSpacing: -0.5 },
  subtitle: { color: colors.mutedText, fontSize: 14, lineHeight: 20 },
  card: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 20, borderWidth: 1, gap: spacing.md, padding: spacing.md, shadowColor: "#0f172a", shadowOffset: { width: 0, height: 5 }, shadowOpacity: 0.05, shadowRadius: 12, elevation: 2 },
  cardAccent: { borderColor: "#fdba74", borderLeftColor: colors.primary, borderLeftWidth: 4 },
  button: { alignItems: "center", backgroundColor: colors.primary, borderRadius: 12, flexDirection: "row", gap: spacing.sm, justifyContent: "center", minHeight: 48, paddingHorizontal: spacing.md },
  buttonSecondary: { backgroundColor: "#fff7ed", borderColor: "#fed7aa", borderWidth: 1 },
  buttonDanger: { backgroundColor: colors.danger },
  buttonDisabled: { opacity: 0.5 },
  buttonPressed: { opacity: 0.78 },
  buttonText: { color: colors.surface, fontSize: 14, fontWeight: "800" },
  buttonSecondaryText: { color: colors.primary },
  chip: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 999, borderWidth: 1, minHeight: 40, paddingHorizontal: spacing.md, justifyContent: "center" },
  chipSelected: { backgroundColor: "#fff7ed", borderColor: colors.primary },
  chipText: { color: colors.mutedText, fontSize: 13, fontWeight: "700" },
  chipTextSelected: { color: colors.primary },
  field: { gap: spacing.xs },
  fieldLabel: { color: colors.text, fontSize: 13, fontWeight: "800" },
  input: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 12, borderWidth: 1, color: colors.text, fontSize: 15, minHeight: 48, paddingHorizontal: spacing.md, paddingVertical: spacing.sm },
  inputMultiline: { minHeight: 96, textAlignVertical: "top" },
  badge: { alignSelf: "flex-start", backgroundColor: "#f1f5f9", borderRadius: 999, paddingHorizontal: spacing.sm, paddingVertical: spacing.xs },
  badgeText: { color: colors.mutedText, fontSize: 11, fontWeight: "800", textTransform: "uppercase" },
  badgePositive: { backgroundColor: "#ecfdf5" },
  badgePositiveText: { color: "#047857" },
  badgeNegative: { backgroundColor: "#fef2f2" },
  badgeNegativeText: { color: colors.danger },
  footerLoader: { padding: spacing.lg }
});
