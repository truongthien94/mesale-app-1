import { useRouter } from "expo-router";
import { CreditCard, History } from "lucide-react-native";
import { IosPayoutRouteGuard } from "@/components/IosPayoutRouteGuard";
import { AccountSectionMenu } from "@/features/account/AccountSectionMenu";
import { usePaymentAccounts, useWithdrawals } from "@/features/wallet/api";
import { useTheme } from "@/theme/ThemeProvider";

export default function AccountFinanceScreen() {
  return <IosPayoutRouteGuard><AccountFinanceContent /></IosPayoutRouteGuard>;
}

function AccountFinanceContent() {
  const router = useRouter();
  const { scheme } = useTheme();
  const paymentAccountsQuery = usePaymentAccounts();
  const withdrawalsQuery = useWithdrawals();
  const paymentAccountCount = paymentAccountsQuery.data?.total;
  const withdrawalCount = withdrawalsQuery.data?.pages[0]?.pagination.total;
  const softBlue = scheme === "dark" ? "#102a44" : "#eaf5ff";
  const softOrange = scheme === "dark" ? "#3b2910" : "#fff6df";

  return (
    <AccountSectionMenu
      items={[
        {
          actionLabel: paymentAccountsQuery.isSuccess && paymentAccountCount === 0 ? "Thêm ngay" : undefined,
          icon: CreditCard,
          iconBackground: softOrange,
          iconColor: "#f59e0b",
          onPress: () => router.push("/(tabs)/wallet/payment-accounts"),
          subtitle: paymentAccountsQuery.isPending
            ? "Đang kiểm tra liên kết..."
            : paymentAccountsQuery.isError
              ? "Chưa thể kiểm tra lúc này"
              : typeof paymentAccountCount === "number" && paymentAccountCount > 0
                ? `${paymentAccountCount} tài khoản đã liên kết`
                : "Chưa liên kết",
          title: "Tài khoản ngân hàng"
        },
        {
          actionLabel: typeof withdrawalCount === "number" ? `${withdrawalCount} lệnh` : undefined,
          icon: History,
          iconBackground: softBlue,
          iconColor: "#2f9af5",
          onPress: () => router.push("/(tabs)/wallet/withdrawals"),
          subtitle: "Theo dõi trạng thái rút tiền",
          title: "Lịch sử rút tiền"
        }
      ]}
      label="TÀI CHÍNH"
    />
  );
}
