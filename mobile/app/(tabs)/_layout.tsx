import { Redirect, Tabs } from "expo-router";
import { Banknote, Home, ShoppingBag, UserRound, Wallet } from "lucide-react-native";
import { useAuth } from "@/auth/AuthProvider";
import { resolveAuthGate } from "@/auth/routing";
import { LoadingState } from "@/components/AsyncState";
import { getDeviceLocale, resolveLocale } from "@/i18n";
import { useTheme } from "@/theme/ThemeProvider";

export default function TabsLayout() {
  const { isLoading, pendingAuth, session, user } = useAuth();
  const { colors } = useTheme();
  const isVietnamese = resolveLocale(user?.preferences?.locale ?? getDeviceLocale()) === "vi";

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
        <Tabs.Screen
          name="home"
          options={{
            title: isVietnamese ? "Trang chủ" : "Home",
            tabBarIcon: ({ color, size }) => <Home color={color} size={size} />
          }}
        />
        <Tabs.Screen
          name="wallet"
          options={{
            title: isVietnamese ? "Ví" : "Wallet",
            tabBarIcon: ({ color, size }) => <Wallet color={color} size={size} />
          }}
        />
        <Tabs.Screen
          name="orders"
          options={{
            title: isVietnamese ? "Đơn hàng" : "Orders",
            tabBarIcon: ({ color, size }) => <ShoppingBag color={color} size={size} />
          }}
        />
        <Tabs.Screen
          name="withdraw"
          options={{
            title: isVietnamese ? "Rút tiền" : "Withdraw",
            tabBarIcon: ({ color, size }) => <Banknote color={color} size={size} />
          }}
        />
      <Tabs.Screen name="earn" options={{ href: null }} />
      <Tabs.Screen name="inbox" options={{ href: null }} />
      <Tabs.Screen
        name="account"
        options={{
          title: isVietnamese ? "Tài khoản" : "Account",
          tabBarActiveTintColor: "#2f9af5",
          tabBarIcon: ({ color, size }) => <UserRound color={color} size={size} />
        }}
      />
      <Tabs.Screen name="more" options={{ href: null }} />
    </Tabs>
  );
}
