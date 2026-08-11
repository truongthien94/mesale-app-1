import { Stack } from "expo-router";
import { AccountStackHeader } from "@/features/account/AccountStackHeader";
import { useTheme } from "@/theme/ThemeProvider";

export default function AccountLayout() {
  const { colors, scheme } = useTheme();

  return (
    <Stack screenOptions={{
      contentStyle: { backgroundColor: colors.background },
      header: (props) => <AccountStackHeader {...props} />,
      statusBarStyle: scheme === "dark" ? "light" : "dark"
    }}>
      <Stack.Screen name="index" options={{ headerShown: false }} />
      <Stack.Screen name="information" options={{ title: "Thông tin tài khoản" }} />
      <Stack.Screen name="finance" options={{ title: "Tài chính" }} />
      <Stack.Screen name="settings" options={{ title: "Cài đặt" }} />
      <Stack.Screen name="profile" options={{ title: "Hồ sơ" }} />
      <Stack.Screen name="password" options={{ title: "Đổi mật khẩu" }} />
      <Stack.Screen name="preferences" options={{ title: "Ngôn ngữ & tiền tệ" }} />
      <Stack.Screen name="security" options={{ title: "Bảo mật" }} />
      <Stack.Screen name="sessions" options={{ title: "Phiên đăng nhập" }} />
      <Stack.Screen name="delete" options={{ title: "Xóa tài khoản" }} />
    </Stack>
  );
}
