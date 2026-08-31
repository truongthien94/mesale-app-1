import { Stack } from "expo-router";
import { useIosPayoutFeaturesEnabled } from "@/config/features";
import { AccountStackHeader } from "@/features/account/AccountStackHeader";
import { useTheme } from "@/theme/ThemeProvider";

export default function AccountLayout() {
  const { colors } = useTheme();
  const payoutFeaturesEnabled = useIosPayoutFeaturesEnabled();

  return (
    <Stack
      screenOptions={{
        contentStyle: { backgroundColor: colors.background },
        header: (props) => <AccountStackHeader {...props} />,
      }}
    >
      <Stack.Screen name="index" options={{ headerShown: false }} />
      <Stack.Screen
        name="information"
        options={{ title: "Thông tin tài khoản" }}
      />
      <Stack.Screen name="finance" options={{ title: payoutFeaturesEnabled ? "Tài chính" : "Tài khoản" }} />
      <Stack.Screen name="settings" options={{ title: "Cài đặt" }} />
      <Stack.Screen
        name="guide"
        options={{ headerShown: false, title: "Hướng dẫn sử dụng" }}
      />
      <Stack.Screen name="profile" options={{ title: "Hồ sơ" }} />
      <Stack.Screen name="password" options={{ title: "Đổi mật khẩu" }} />
      <Stack.Screen
        name="preferences"
        options={{ title: payoutFeaturesEnabled ? "Ngôn ngữ & tiền tệ" : "Ngôn ngữ" }}
      />
      <Stack.Screen name="security" options={{ title: "Bảo mật" }} />
      <Stack.Screen name="sessions" options={{ title: "Phiên đăng nhập" }} />
      <Stack.Screen name="delete" options={{ title: "Xóa tài khoản" }} />
    </Stack>
  );
}
