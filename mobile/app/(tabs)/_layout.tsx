import { Redirect, Tabs, usePathname, useRouter } from "expo-router";
import { Home, ListChecks, ShoppingBag, UserRound, UsersRound } from "lucide-react-native";
import { useAuth } from "@/auth/AuthProvider";
import { resolveAuthGate } from "@/auth/routing";
import { LoadingState } from "@/components/AsyncState";
import { AuthenticatedPrefetch } from "@/api/AuthenticatedPrefetch";
import { getDeviceLocale, resolveLocale } from "@/i18n";
import { useTheme } from "@/theme/ThemeProvider";

export default function TabsLayout() {
  const { isLoading, pendingAuth, session, user } = useAuth();
  const { colors } = useTheme();
  const router = useRouter();
  const pathname = usePathname();
  const isVietnamese = resolveLocale(user?.preferences?.locale ?? getDeviceLocale()) === "vi";
  const hideTabBar = pathname === "/home/tips" || pathname === "/account/guide";

  if (isLoading) return <LoadingState />;
  const authGate = resolveAuthGate(pendingAuth, Boolean(session), user?.referralPromptPending ?? false);
  if (authGate !== "/home") return <Redirect href={authGate ?? "/login"} />;

  return (
    <>
      <AuthenticatedPrefetch />
      <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.mutedText,
        tabBarHideOnKeyboard: true,
        tabBarStyle: {
          backgroundColor: colors.surface,
          borderTopColor: colors.border,
          display: hideTabBar ? "none" : "flex"
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
          name="referrals"
          options={{
            title: isVietnamese ? "Giới thiệu" : "Referral",
            tabBarIcon: ({ color, size }) => <UsersRound color={color} size={size} />
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
          name="tasks"
          options={{
            title: isVietnamese ? "Nhiệm vụ" : "Tasks",
            tabBarIcon: ({ color, size }) => <ListChecks color={color} size={size} />
          }}
        />
        <Tabs.Screen name="withdraw" options={{ href: null }} />
      <Tabs.Screen name="wallet" options={{ href: null }} />
      <Tabs.Screen name="earn" options={{ href: null }} />
      <Tabs.Screen name="inbox" options={{ href: null }} />
      <Tabs.Screen
        name="account"
        listeners={{
          tabPress: (event) => {
            event.preventDefault();
            router.replace("/(tabs)/account");
          },
        }}
        options={{
          popToTopOnBlur: true,
          title: isVietnamese ? "Tài khoản" : "Account",
          tabBarActiveTintColor: "#2f9af5",
          tabBarIcon: ({ color, size }) => <UserRound color={color} size={size} />
        }}
      />
      <Tabs.Screen name="more" options={{ href: null }} />
      </Tabs>
    </>
  );
}
