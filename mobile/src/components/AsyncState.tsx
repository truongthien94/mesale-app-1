import { ActivityIndicator, Pressable, StyleSheet, Text, View, type StyleProp, type ViewStyle } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { useTheme } from "@/theme/ThemeProvider";
import { spacing } from "@/theme/tokens";

type StateAction = {
  actionLabel?: string;
  onAction?: () => void;
};

type StateProps = StateAction & {
  alert?: boolean;
  title?: string;
  message?: string;
  style?: StyleProp<ViewStyle>;
};

function StateFrame({ alert = false, title, message, actionLabel, onAction, style }: StateProps) {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();

  return (
    <View
      accessibilityLiveRegion={alert ? "polite" : undefined}
      accessibilityRole={alert ? "alert" : undefined}
      style={[
        styles.frame,
        {
          backgroundColor: colors.background,
          paddingTop: spacing.lg + insets.top,
          paddingBottom: spacing.lg + insets.bottom,
          paddingLeft: spacing.lg + insets.left,
          paddingRight: spacing.lg + insets.right
        },
        style
      ]}
    >
      {title ? <Text accessibilityRole="header" style={[styles.title, { color: colors.text }]}>{title}</Text> : null}
      {message ? <Text style={[styles.message, { color: colors.mutedText }]}>{message}</Text> : null}
      {actionLabel && onAction ? (
        <Pressable
          accessibilityLabel={actionLabel}
          accessibilityRole="button"
          onPress={onAction}
          style={({ pressed }) => [styles.action, { backgroundColor: colors.primary }, pressed && styles.actionPressed]}
        >
          <Text style={styles.actionText}>{actionLabel}</Text>
        </Pressable>
      ) : null}
    </View>
  );
}

export function LoadingState({ label, style }: { label?: string; style?: StyleProp<ViewStyle> }) {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();

  return (
    <View
      accessibilityLabel={label ?? "Loading"}
      accessibilityRole="progressbar"
      style={[
        styles.frame,
        {
          backgroundColor: colors.background,
          paddingTop: spacing.lg + insets.top,
          paddingBottom: spacing.lg + insets.bottom,
          paddingLeft: spacing.lg + insets.left,
          paddingRight: spacing.lg + insets.right
        },
        style
      ]}
    >
      <ActivityIndicator color={colors.primary} size="large" />
      {label ? <Text style={[styles.message, { color: colors.mutedText }]}>{label}</Text> : null}
    </View>
  );
}

export function EmptyState(props: StateProps) {
  return <StateFrame {...props} />;
}

export function ErrorState(props: StateProps) {
  return <StateFrame {...props} alert />;
}

export function OfflineState(props: StateProps) {
  return <StateFrame {...props} alert />;
}

const styles = StyleSheet.create({
  frame: {
    alignItems: "center",
    flex: 1,
    gap: spacing.sm,
    justifyContent: "center"
  },
  title: {
    fontSize: 20,
    fontWeight: "800",
    textAlign: "center"
  },
  message: {
    fontSize: 15,
    lineHeight: 22,
    textAlign: "center"
  },
  action: {
    alignItems: "center",
    borderRadius: 12,
    justifyContent: "center",
    marginTop: spacing.sm,
    minHeight: 48,
    minWidth: 120,
    paddingHorizontal: spacing.lg
  },
  actionPressed: {
    opacity: 0.8
  },
  actionText: {
    color: "#ffffff",
    fontSize: 15,
    fontWeight: "700"
  }
});
