import { useRouter } from "expo-router";
import { ShieldCheck, UserRound, UsersRound } from "lucide-react-native";
import { AccountSectionMenu } from "@/features/account/AccountSectionMenu";
import { useTheme } from "@/theme/ThemeProvider";

export default function AccountInformationScreen() {
  const router = useRouter();
  const { scheme } = useTheme();
  const softBlue = scheme === "dark" ? "#102a44" : "#eaf5ff";
  const softGreen = scheme === "dark" ? "#0d3327" : "#eaf9f1";

  return (
    <AccountSectionMenu
      items={[
        {
          icon: UserRound,
          iconBackground: softBlue,
          iconColor: "#2f9af5",
          onPress: () => router.push("/(tabs)/account/profile"),
          subtitle: "Họ tên và số điện thoại",
          title: "Thông tin cá nhân"
        },
        {
          icon: ShieldCheck,
          iconBackground: softGreen,
          iconColor: "#16a34a",
          onPress: () => router.push("/(tabs)/account/security"),
          subtitle: "Mật khẩu, OTP email và xác thực hai lớp",
          title: "Bảo mật tài khoản"
        },
        {
          icon: UsersRound,
          iconBackground: softBlue,
          iconColor: "#2f9af5",
          onPress: () => router.push("/(tabs)/account/sessions"),
          subtitle: "Kiểm tra và thu hồi thiết bị",
          title: "Phiên đăng nhập"
        }
      ]}
      label="TÀI KHOẢN"
    />
  );
}
