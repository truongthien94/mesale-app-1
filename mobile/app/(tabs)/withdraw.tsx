import { useRouter } from "expo-router";
import { ChevronLeft } from "lucide-react-native";
import { Pressable, StyleSheet, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { useTheme } from "@/theme/ThemeProvider";
import CreateWithdrawalScreen from "./wallet/withdrawals/create";

export default function WithdrawTabScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();

  return (
    <View style={[styles.screen, { backgroundColor: colors.background }]}>
      <View style={[styles.header, { backgroundColor: colors.surface, borderBottomColor: colors.border, paddingTop: insets.top }]}>
        <View style={styles.headerContent}>
          <Pressable
            accessibilityLabel="Mở lịch sử rút tiền"
            accessibilityRole="button"
            hitSlop={12}
            onPress={() => router.replace("/(tabs)/wallet/withdrawals")}
            style={({ pressed }) => [styles.headerSide, pressed && styles.pressed]}
          >
            <ChevronLeft color={colors.text} size={26} strokeWidth={2.5} />
          </Pressable>
          <Text accessibilityRole="header" allowFontScaling={false} numberOfLines={1} style={[styles.title, { color: colors.text }]}>Rút tiền</Text>
          <View accessibilityElementsHidden importantForAccessibility="no-hide-descendants" style={styles.headerSide} />
        </View>
      </View>
      <View style={styles.content}>
        <CreateWithdrawalScreen />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1 },
  header: { borderBottomWidth: StyleSheet.hairlineWidth },
  headerContent: { alignItems: "center", flexDirection: "row", minHeight: 56, paddingHorizontal: 8 },
  headerSide: { alignItems: "center", height: 44, justifyContent: "center", width: 44 },
  title: { flex: 1, fontSize: 17, fontWeight: "800", textAlign: "center" },
  content: { flex: 1 },
  pressed: { opacity: 0.62 }
});
