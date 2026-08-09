import { Stack } from "expo-router";
import { colors } from "@/theme/tokens";

export default function WalletLayout() {
  return (
    <Stack
      screenOptions={{
        contentStyle: { backgroundColor: colors.background },
        headerShadowVisible: false,
        headerStyle: { backgroundColor: colors.surface },
        headerTintColor: colors.text,
        headerTitleStyle: { fontWeight: "800" }
      }}
    >
      <Stack.Screen name="index" options={{ title: "Ví của tôi" }} />
      <Stack.Screen name="orders/index" options={{ title: "Đơn hoàn tiền" }} />
      <Stack.Screen name="orders/[id]" options={{ title: "Chi tiết đơn hàng" }} />
      <Stack.Screen name="balance-logs" options={{ title: "Biến động số dư" }} />
      <Stack.Screen name="withdrawals/index" options={{ title: "Lịch sử rút tiền" }} />
      <Stack.Screen name="withdrawals/create" options={{ title: "Tạo yêu cầu rút tiền", presentation: "modal" }} />
      <Stack.Screen name="payment-accounts/index" options={{ title: "Tài khoản nhận tiền" }} />
      <Stack.Screen name="payment-accounts/create" options={{ title: "Thêm tài khoản", presentation: "modal" }} />
    </Stack>
  );
}
