import { Stack } from "expo-router";
import { colors } from "@/theme/tokens";

export default function AccountLayout() {
  return (
    <Stack screenOptions={{
      contentStyle: { backgroundColor: colors.background },
      headerBackTitle: "Tài khoản",
      headerShadowVisible: false,
      headerStyle: { backgroundColor: colors.surface },
      headerTintColor: colors.text,
      headerTitleStyle: { fontWeight: "800" }
    }}>
      <Stack.Screen name="index" options={{ headerShown: false }} />
      <Stack.Screen name="profile" options={{ title: "Hồ sơ" }} />
      <Stack.Screen name="password" options={{ title: "Đổi mật khẩu" }} />
      <Stack.Screen name="preferences" options={{ title: "Ngôn ngữ & tiền tệ" }} />
      <Stack.Screen name="security" options={{ title: "Bảo mật" }} />
      <Stack.Screen name="sessions" options={{ title: "Phiên đăng nhập" }} />
      <Stack.Screen name="delete" options={{ title: "Xóa tài khoản" }} />
    </Stack>
  );
}
