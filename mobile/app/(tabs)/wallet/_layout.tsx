import type { NativeStackHeaderProps } from "@react-navigation/native-stack";
import { getHeaderTitle } from "@react-navigation/elements";
import { Stack, useRouter } from "expo-router";
import { ChevronLeft } from "lucide-react-native";
import { Pressable, StyleSheet, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { useTheme } from "@/theme/ThemeProvider";

function WalletStackHeader({ navigation, options, route }: NativeStackHeaderProps) {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const title = getHeaderTitle(options, route.name);
  const showBack = navigation.canGoBack() || route.name !== "index";

  function goBack() {
    if (navigation.canGoBack()) {
      navigation.goBack();
      return;
    }
    router.replace(route.name === "withdrawals/create" ? "/(tabs)/wallet/withdrawals" : "/(tabs)/wallet");
  }

  return (
    <View style={[styles.header, { backgroundColor: colors.surface, borderBottomColor: colors.border, paddingTop: insets.top }]}>
      <View style={styles.headerContent}>
        {showBack ? (
          <Pressable
            accessibilityLabel="Quay lại"
            accessibilityRole="button"
            hitSlop={12}
            onPress={goBack}
            style={({ pressed }) => [styles.backButton, pressed && styles.pressed]}
          >
            <ChevronLeft color={colors.text} size={26} strokeWidth={2.5} />
          </Pressable>
        ) : <View style={styles.headerSide} />}
        <Text
          accessibilityRole="header"
          allowFontScaling={false}
          numberOfLines={1}
          style={[styles.headerTitle, { color: colors.text }]}
        >
          {title}
        </Text>
        <View accessibilityElementsHidden importantForAccessibility="no-hide-descendants" style={styles.headerSide} />
      </View>
    </View>
  );
}

export default function WalletLayout() {
  const { colors, scheme } = useTheme();

  return (
    <Stack
      screenOptions={{
        contentStyle: { backgroundColor: colors.background },
        header: (props) => <WalletStackHeader {...props} />,
        statusBarStyle: scheme === "dark" ? "light" : "dark"
      }}
    >
      <Stack.Screen name="index" options={{ title: "Ví của tôi" }} />
      <Stack.Screen name="orders/index" options={{ title: "Đơn hoàn tiền" }} />
      <Stack.Screen name="orders/[id]" options={{ title: "Chi tiết đơn hàng" }} />
      <Stack.Screen name="balance-logs" options={{ title: "Biến động số dư" }} />
      <Stack.Screen name="withdrawals/index" options={{ title: "Lịch sử rút tiền" }} />
      <Stack.Screen
        name="withdrawals/create"
        options={{
          presentation: "card",
          title: "Rút tiền"
        }}
      />
      <Stack.Screen name="payment-accounts/index" options={{ title: "Tài khoản nhận tiền" }} />
      <Stack.Screen name="payment-accounts/create" options={{ title: "Thêm tài khoản", presentation: "modal" }} />
    </Stack>
  );
}

const styles = StyleSheet.create({
  header: { borderBottomWidth: StyleSheet.hairlineWidth },
  headerContent: { alignItems: "center", flexDirection: "row", minHeight: 56, paddingHorizontal: 8 },
  backButton: { alignItems: "center", height: 44, justifyContent: "center", width: 44 },
  headerSide: { height: 44, width: 44 },
  headerTitle: { flex: 1, fontSize: 17, fontWeight: "800", textAlign: "center" },
  pressed: { opacity: 0.62 }
});
