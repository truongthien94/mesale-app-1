import { useState } from "react";
import { Text } from "react-native";
import { useRouter } from "expo-router";
import { ApiError } from "@/api/client";
import { useAuth } from "@/auth/AuthProvider";
import { useDeleteAccount } from "@/features/account/api";
import { AccountButton, AccountCard, AccountField, AccountFormScreen, AccountHeader, AccountMutationError, AccountNotice, accountStyles } from "@/features/account/components";

const providerCodes = new Set(["PROVIDER_REAUTHENTICATION_REQUIRED", "PASSWORD_OR_PROVIDER_REAUTHENTICATION_REQUIRED"]);

export default function DeleteAccountScreen() {
  const router = useRouter();
  const { completeAccountDeletion } = useAuth();
  const mutation = useDeleteAccount();
  const [password, setPassword] = useState("");
  const [confirmation, setConfirmation] = useState("");

  const providerReauthRequired = mutation.error instanceof ApiError && mutation.error.code !== undefined && providerCodes.has(mutation.error.code);

  async function submit() {
    if (confirmation.trim().toUpperCase() !== "XÓA") return;
    try {
      await mutation.mutateAsync(password || undefined);
      await completeAccountDeletion();
      router.replace("/login");
    } catch {
      // The server requirement is shown without collecting raw OAuth credentials.
    }
  }

  return (
    <AccountFormScreen>
      <AccountHeader title="Xóa tài khoản" subtitle="Thao tác này xóa hoặc vô hiệu hóa cùng tài khoản đang dùng trên website, không phải một bản dữ liệu mobile riêng." />
      <AccountCard tone="danger">
        <AccountNotice tone="danger">Hành động không thể hoàn tác. Lịch sử hoàn tiền, hoa hồng, thông báo và phiên đăng nhập gắn với tài khoản có thể bị xóa vĩnh viễn. Hãy kiểm tra số dư và yêu cầu rút tiền trước khi tiếp tục.</AccountNotice>
        <AccountField autoCapitalize="none" label="Mật khẩu hiện tại (nếu có)" onChangeText={setPassword} placeholder="Tài khoản chỉ dùng Google/Apple có thể để trống" secureTextEntry value={password} />
        <AccountField autoCapitalize="characters" label="Nhập XÓA để xác nhận" onChangeText={setConfirmation} value={confirmation} />
        {providerReauthRequired ? (
          <AccountNotice>
            Tài khoản này cần đăng nhập lại bằng Google hoặc Sign in with Apple trước khi xóa. App chưa thu thập token thủ công; chức năng sẽ dùng credential native khi cấu hình OAuth production được cung cấp.
          </AccountNotice>
        ) : null}
        <AccountMutationError error={mutation.error} />
        <AccountButton disabled={confirmation.trim().toUpperCase() !== "XÓA"} label="Xóa vĩnh viễn tài khoản" loading={mutation.isPending} onPress={() => void submit()} tone="danger" />
        <AccountButton label="Quay lại" onPress={() => router.back()} tone="secondary" />
      </AccountCard>
      <AccountNotice>Không nhập ID token Google, Apple identity token hoặc authorization code vào biểu mẫu. Các credential này chỉ được tạo bởi luồng đăng nhập native và xác minh trực tiếp ở Laravel.</AccountNotice>
      <Text style={accountStyles.body}>Nếu máy chủ đang tắt tính năng tự xóa, ứng dụng sẽ hiển thị nguyên trạng lỗi `FEATURE_DISABLED` để người dùng biết cần liên hệ hỗ trợ.</Text>
    </AccountFormScreen>
  );
}
