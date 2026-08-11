import type { ComponentType } from "react";
import { ChevronRight } from "lucide-react-native";
import { Pressable, ScrollView, StyleSheet, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { useTheme } from "@/theme/ThemeProvider";

type MenuIcon = ComponentType<{ color?: string; size?: number }>;

export type AccountSectionItem = {
  actionBackground?: string;
  actionColor?: string;
  actionLabel?: string;
  icon: MenuIcon;
  iconBackground: string;
  iconColor: string;
  onPress: () => void;
  subtitle?: string;
  title: string;
};

export function AccountSectionMenu({ label, items }: { label: string; items: AccountSectionItem[] }) {
  const insets = useSafeAreaInsets();
  const { colors, scheme } = useTheme();
  const screenBackground = scheme === "dark" ? "#08111f" : "#f2f7fc";

  return (
    <ScrollView
      contentContainerStyle={[styles.content, { paddingBottom: insets.bottom + 32 }]}
      contentInsetAdjustmentBehavior="automatic"
      showsVerticalScrollIndicator={false}
      style={{ backgroundColor: screenBackground }}
    >
      <Text style={[styles.sectionLabel, { color: colors.mutedText }]}>{label}</Text>
      <View style={[styles.menuCard, { backgroundColor: colors.surface }]}>
        {items.map((item, index) => {
          const Icon = item.icon;
          const showDivider = index < items.length - 1;
          return (
            <Pressable
              accessibilityHint={item.subtitle}
              accessibilityLabel={item.title}
              accessibilityRole="button"
              key={item.title}
              onPress={item.onPress}
              style={({ pressed }) => [styles.menuRow, pressed && styles.pressed]}
            >
              <View style={[styles.menuIcon, { backgroundColor: item.iconBackground }]}>
                <Icon color={item.iconColor} size={21} />
              </View>
              <View style={[styles.menuCopy, showDivider && { borderBottomColor: colors.border, borderBottomWidth: StyleSheet.hairlineWidth }]}>
                <View style={styles.menuTextWrap}>
                  <Text numberOfLines={1} style={[styles.menuTitle, { color: colors.text }]}>{item.title}</Text>
                  {item.subtitle ? <Text numberOfLines={1} style={[styles.menuSubtitle, { color: colors.mutedText }]}>{item.subtitle}</Text> : null}
                </View>
                {item.actionLabel ? (
                  <View style={[styles.actionPill, { backgroundColor: item.actionBackground ?? item.iconBackground }]}>
                    <Text style={[styles.actionPillText, { color: item.actionColor ?? item.iconColor }]}>{item.actionLabel}</Text>
                  </View>
                ) : null}
                <ChevronRight color={colors.mutedText} size={21} />
              </View>
            </Pressable>
          );
        })}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { gap: 12, paddingHorizontal: 18, paddingTop: 18 },
  sectionLabel: { fontSize: 13, fontWeight: "900", letterSpacing: 1.4, marginLeft: 4 },
  menuCard: { borderRadius: 22, overflow: "hidden" },
  menuRow: { alignItems: "center", flexDirection: "row", minHeight: 74, paddingLeft: 14 },
  menuIcon: { alignItems: "center", borderRadius: 13, height: 44, justifyContent: "center", width: 44 },
  menuCopy: { alignItems: "center", flex: 1, flexDirection: "row", gap: 8, marginLeft: 12, minHeight: 74, paddingRight: 13 },
  menuTextWrap: { flex: 1, gap: 3, minWidth: 0 },
  menuTitle: { fontSize: 15, fontWeight: "800" },
  menuSubtitle: { fontSize: 12 },
  actionPill: { borderRadius: 999, paddingHorizontal: 10, paddingVertical: 6 },
  actionPillText: { fontSize: 11, fontWeight: "900" },
  pressed: { opacity: 0.72 }
});
