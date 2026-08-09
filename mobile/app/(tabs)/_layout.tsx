import { Redirect, Tabs } from "expo-router";
import { useAuth } from "@/auth/AuthProvider";
import { resolveAuthGate } from "@/auth/routing";
import { LoadingState } from "@/components/AsyncState";
import { getDeviceLocale } from "@/i18n";
import { colors } from "@/theme/tokens";

export default function TabsLayout() {
  const { isLoading, pendingAuth, session, user } = useAuth();
  const isVietnamese = getDeviceLocale() === "vi";

  if (isLoading) return <LoadingState />;
  const authGate = resolveAuthGate(pendingAuth, Boolean(session), user?.referralPromptPending ?? false);
  if (authGate !== "/home") return <Redirect href={authGate ?? "/login"} />;

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.mutedText,
        tabBarHideOnKeyboard: true,
        tabBarStyle: {
          backgroundColor: colors.surface,
          borderTopColor: colors.border
        }
      }}
    >
      <Tabs.Screen name="home" options={{ title: isVietnamese ? "Trang chủ" : "Home" }} />
      <Tabs.Screen name="wallet" options={{ title: isVietnamese ? "Ví" : "Wallet" }} />
      <Tabs.Screen name="earn" options={{ title: isVietnamese ? "Nhận thưởng" : "Earn" }} />
      <Tabs.Screen name="inbox" options={{ title: isVietnamese ? "Thông báo" : "Inbox" }} />
      <Tabs.Screen name="account" options={{ title: isVietnamese ? "Tài khoản" : "Account" }} />
    </Tabs>
  );
}
