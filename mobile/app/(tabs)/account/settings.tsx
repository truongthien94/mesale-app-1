import { Alert } from "react-native";
import { useRouter } from "expo-router";
import { Languages, SunMoon } from "lucide-react-native";
import { useIosPayoutFeaturesEnabled } from "@/config/features";
import { AccountSectionMenu } from "@/features/account/AccountSectionMenu";
import { useAccount } from "@/features/account/api";
import { useTheme } from "@/theme/ThemeProvider";
import type { ThemePreference } from "@/theme/themePreference";

export default function AccountSettingsScreen() {
  const router = useRouter();
  const payoutFeaturesEnabled = useIosPayoutFeaturesEnabled();
  const accountQuery = useAccount();
  const { preference, scheme, setPreference } = useTheme();
  const accountLocale = accountQuery.data?.preferences?.locale?.trim() || "vi";
  const accountCurrency = accountQuery.data?.preferences?.currency?.trim() || accountQuery.data?.wallet?.currency?.trim() || "VND";
  const currentThemeLabel = preference === "system" ? "Theo hệ thống" : preference === "dark" ? "Tối" : "Sáng";
  const softBlue = scheme === "dark" ? "#102a44" : "#eaf5ff";
  const softOrange = scheme === "dark" ? "#3b2910" : "#fff6df";

  function chooseTheme() {
    const options: Array<{ label: string; value: ThemePreference }> = [
      { label: "Theo hệ thống", value: "system" },
      { label: "Chế độ sáng", value: "light" },
      { label: "Chế độ tối", value: "dark" }
    ];
    Alert.alert(
      "Giao diện",
      "Chọn chế độ hiển thị cho ứng dụng.",
      [
        ...options.map((option) => ({ text: option.label, onPress: () => setPreference(option.value) })),
        { text: "Hủy", style: "cancel" as const }
      ]
    );
  }

  return (
    <AccountSectionMenu
      items={[
        {
          actionLabel: payoutFeaturesEnabled
            ? `${accountLocale.toUpperCase()} · ${accountCurrency}`
            : accountLocale.toUpperCase(),
          icon: Languages,
          iconBackground: softBlue,
          iconColor: "#2f9af5",
          onPress: () => router.push("/(tabs)/account/preferences"),
          subtitle: payoutFeaturesEnabled
            ? "Tùy chọn hiển thị dùng chung với tài khoản"
            : "Chọn ngôn ngữ hiển thị cho ứng dụng",
          title: payoutFeaturesEnabled ? "Ngôn ngữ & tiền tệ" : "Ngôn ngữ"
        },
        {
          actionLabel: currentThemeLabel,
          icon: SunMoon,
          iconBackground: softOrange,
          iconColor: "#f59e0b",
          onPress: chooseTheme,
          subtitle: "Sáng, tối hoặc theo thiết bị",
          title: "Giao diện"
        }
      ]}
      label="CÀI ĐẶT"
    />
  );
}
