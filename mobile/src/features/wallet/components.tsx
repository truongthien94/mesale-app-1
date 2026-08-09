import type { PropsWithChildren, ReactNode } from "react";
import {
  ActivityIndicator,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
  type KeyboardTypeOptions,
  type StyleProp,
  type ViewStyle
} from "react-native";
import { ApiError } from "@/api/client";
import { ErrorState, OfflineState } from "@/components/AsyncState";
import { colors, spacing, theme } from "@/theme/tokens";
import { statusLabel } from "@/features/wallet/format";
import type { OrderStatus } from "@/features/wallet/types";

export function QueryFailure({ error, onRetry }: { error: unknown; onRetry: () => void }) {
  const message = error instanceof Error ? error.message : "Không thể tải dữ liệu.";
  const props = {
    title: "Không thể tải dữ liệu",
    message,
    actionLabel: "Thử lại",
    onAction: onRetry
  };
  return error instanceof ApiError && error.isNetworkError
    ? <OfflineState {...props} title="Bạn đang ngoại tuyến" />
    : <ErrorState {...props} />;
}

export function PageFrame({ children, style }: PropsWithChildren<{ style?: StyleProp<ViewStyle> }>) {
  return <View style={[styles.page, style]}>{children}</View>;
}

export function Card({ children, style }: PropsWithChildren<{ style?: StyleProp<ViewStyle> }>) {
  return <View style={[styles.card, style]}>{children}</View>;
}

export function SectionTitle({ title, caption, action }: { title: string; caption?: string; action?: ReactNode }) {
  return (
    <View style={styles.sectionHeading}>
      <View style={styles.sectionHeadingCopy}>
        <Text accessibilityRole="header" style={styles.sectionTitle}>{title}</Text>
        {caption ? <Text style={styles.sectionCaption}>{caption}</Text> : null}
      </View>
      {action}
    </View>
  );
}

export function PrimaryButton({
  label,
  onPress,
  disabled = false,
  loading = false,
  tone = "primary",
  compact = false
}: {
  label: string;
  onPress: () => void;
  disabled?: boolean;
  loading?: boolean;
  tone?: "primary" | "secondary" | "danger";
  compact?: boolean;
}) {
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityState={{ disabled: disabled || loading, busy: loading }}
      disabled={disabled || loading}
      onPress={onPress}
      style={({ pressed }) => [
        styles.button,
        compact && styles.buttonCompact,
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

export function TextField({
  label,
  value,
  onChangeText,
  placeholder,
  keyboardType,
  error,
  autoCapitalize = "sentences",
  secureTextEntry = false
}: {
  label: string;
  value: string;
  onChangeText: (value: string) => void;
  placeholder?: string;
  keyboardType?: KeyboardTypeOptions;
  error?: string;
  autoCapitalize?: "none" | "sentences" | "words" | "characters";
  secureTextEntry?: boolean;
}) {
  return (
    <View style={styles.fieldGroup}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <TextInput
        accessibilityLabel={label}
        autoCapitalize={autoCapitalize}
        keyboardType={keyboardType}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor={colors.mutedText}
        secureTextEntry={secureTextEntry}
        style={[styles.input, error && styles.inputError]}
        value={value}
      />
      {error ? <Text accessibilityRole="alert" style={styles.fieldError}>{error}</Text> : null}
    </View>
  );
}

export function ChoiceRow({
  label,
  options,
  value,
  onChange
}: {
  label: string;
  options: { label: string; value: string; disabled?: boolean }[];
  value: string;
  onChange: (value: string) => void;
}) {
  return (
    <View style={styles.fieldGroup}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <View style={styles.choiceWrap}>
        {options.map((option) => {
          const selected = value === option.value;
          return (
            <Pressable
              accessibilityRole="radio"
              accessibilityState={{ checked: selected, disabled: option.disabled }}
              disabled={option.disabled}
              key={option.value}
              onPress={() => onChange(option.value)}
              style={({ pressed }) => [
                styles.choice,
                selected && styles.choiceSelected,
                option.disabled && styles.choiceDisabled,
                pressed && styles.buttonPressed
              ]}
            >
              <Text style={[styles.choiceText, selected && styles.choiceTextSelected]}>{option.label}</Text>
            </Pressable>
          );
        })}
      </View>
    </View>
  );
}

export function StatusBadge({ status }: { status: OrderStatus }) {
  return (
    <View style={[
      styles.badge,
      status === "approved" && styles.badgeApproved,
      status === "rejected" && styles.badgeRejected
    ]}>
      <Text style={[
        styles.badgeText,
        status === "approved" && styles.badgeApprovedText,
        status === "rejected" && styles.badgeRejectedText
      ]}>{statusLabel(status)}</Text>
    </View>
  );
}

export function InlineError({ error, onRetry }: { error: unknown; onRetry?: () => void }) {
  if (!error) return null;
  return (
    <View accessibilityRole="alert" style={styles.inlineError}>
      <Text style={styles.inlineErrorText}>{error instanceof Error ? error.message : "Đã có lỗi xảy ra."}</Text>
      {onRetry ? <PrimaryButton compact label="Thử lại" onPress={onRetry} tone="secondary" /> : null}
    </View>
  );
}

export function ListFooter({ loading, error, onRetry }: { loading: boolean; error: unknown; onRetry: () => void }) {
  if (loading) return <ActivityIndicator color={colors.primary} style={styles.listFooter} />;
  if (error) return <InlineError error={error} onRetry={onRetry} />;
  return <View style={styles.listFooterSpacer} />;
}

const styles = StyleSheet.create({
  page: {
    backgroundColor: colors.background,
    flex: 1
  },
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: theme.radius.lg,
    borderWidth: 1,
    padding: spacing.md,
    shadowColor: "#0f172a",
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.05,
    shadowRadius: 10,
    elevation: 2
  },
  sectionHeading: {
    alignItems: "center",
    flexDirection: "row",
    gap: spacing.md,
    justifyContent: "space-between"
  },
  sectionHeadingCopy: {
    flex: 1,
    gap: spacing.xs
  },
  sectionTitle: {
    color: colors.text,
    fontSize: 17,
    fontWeight: "800"
  },
  sectionCaption: {
    color: colors.mutedText,
    fontSize: 12,
    lineHeight: 18
  },
  button: {
    alignItems: "center",
    backgroundColor: colors.primary,
    borderRadius: theme.radius.md,
    flexDirection: "row",
    gap: spacing.sm,
    justifyContent: "center",
    minHeight: 50,
    paddingHorizontal: spacing.lg
  },
  buttonCompact: {
    minHeight: 40,
    paddingHorizontal: spacing.md
  },
  buttonSecondary: {
    backgroundColor: "#fff7ed",
    borderColor: "#fed7aa",
    borderWidth: 1
  },
  buttonDanger: {
    backgroundColor: colors.danger
  },
  buttonDisabled: {
    opacity: 0.48
  },
  buttonPressed: {
    opacity: 0.78
  },
  buttonText: {
    color: colors.surface,
    fontSize: 14,
    fontWeight: "800"
  },
  buttonSecondaryText: {
    color: colors.primary
  },
  fieldGroup: {
    gap: spacing.sm
  },
  fieldLabel: {
    color: "#334155",
    fontSize: 12,
    fontWeight: "800",
    letterSpacing: 0.35,
    textTransform: "uppercase"
  },
  input: {
    backgroundColor: "#f8fafc",
    borderColor: colors.border,
    borderRadius: theme.radius.md,
    borderWidth: 1,
    color: colors.text,
    fontSize: 14,
    minHeight: 50,
    paddingHorizontal: spacing.md
  },
  inputError: {
    borderColor: colors.danger
  },
  fieldError: {
    color: colors.danger,
    fontSize: 12
  },
  choiceWrap: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: spacing.sm
  },
  choice: {
    backgroundColor: "#f8fafc",
    borderColor: colors.border,
    borderRadius: 999,
    borderWidth: 1,
    minHeight: 42,
    justifyContent: "center",
    paddingHorizontal: spacing.md
  },
  choiceSelected: {
    backgroundColor: "#fff7ed",
    borderColor: colors.primary
  },
  choiceDisabled: {
    opacity: 0.35
  },
  choiceText: {
    color: colors.mutedText,
    fontSize: 12,
    fontWeight: "700"
  },
  choiceTextSelected: {
    color: colors.primary
  },
  badge: {
    alignSelf: "flex-start",
    backgroundColor: "#fffbeb",
    borderColor: "#fde68a",
    borderRadius: 999,
    borderWidth: 1,
    paddingHorizontal: 9,
    paddingVertical: 4
  },
  badgeApproved: {
    backgroundColor: "#ecfdf5",
    borderColor: "#a7f3d0"
  },
  badgeRejected: {
    backgroundColor: "#fff1f2",
    borderColor: "#fecdd3"
  },
  badgeText: {
    color: "#b45309",
    fontSize: 10,
    fontWeight: "800"
  },
  badgeApprovedText: {
    color: "#047857"
  },
  badgeRejectedText: {
    color: "#be123c"
  },
  inlineError: {
    alignItems: "center",
    backgroundColor: "#fff1f2",
    borderColor: "#fecdd3",
    borderRadius: theme.radius.md,
    borderWidth: 1,
    gap: spacing.sm,
    margin: spacing.md,
    padding: spacing.md
  },
  inlineErrorText: {
    color: colors.danger,
    fontSize: 12,
    lineHeight: 18,
    textAlign: "center"
  },
  listFooter: {
    marginVertical: spacing.lg
  },
  listFooterSpacer: {
    height: spacing.lg
  }
});

export const walletStyles = styles;
