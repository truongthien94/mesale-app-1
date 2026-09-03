import { useEffect, useState } from "react";
import * as AppleAuthentication from "expo-apple-authentication";
import { ActivityIndicator, StyleSheet, Text, View } from "react-native";
import { useRouter } from "expo-router";
import { ApiError } from "@/api/client";
import { useAuth } from "@/auth/AuthProvider";
import { useDeleteAccount } from "@/features/account/api";
import { AccountButton, AccountCard, AccountField, AccountFormScreen, AccountHeader, AccountMutationError, AccountNotice, accountStyles } from "@/features/account/components";
import { isAppleNativeSignInAvailable, oauthUiStateFromReason, requestAppleNativeCredential } from "@/features/auth/nativeOAuth";
import { getDeviceLocale } from "@/i18n";

const providerCodes = new Set([
  "PROVIDER_REAUTHENTICATION_REQUIRED",
  "PASSWORD_OR_PROVIDER_REAUTHENTICATION_REQUIRED",
  "APPLE_REAUTH_REQUIRED_FOR_DELETION"
]);

export default function DeleteAccountScreen() {
  const router = useRouter();
  const locale = getDeviceLocale();
  const { completeAccountDeletion } = useAuth();
  const mutation = useDeleteAccount();
  const [password, setPassword] = useState("");
  const [confirmation, setConfirmation] = useState("");
  const [appleAvailable, setAppleAvailable] = useState(false);
  const [appleBusy, setAppleBusy] = useState(false);
  const [appleError, setAppleError] = useState<string | null>(null);
  const isConfirmed = confirmation.trim().toUpperCase() === "XÓA";

  const providerReauthRequired = mutation.error instanceof ApiError
    && mutation.error.code !== undefined
    && providerCodes.has(mutation.error.code);
  const appleReauthRequired = mutation.error instanceof ApiError
    && mutation.error.code === "APPLE_REAUTH_REQUIRED_FOR_DELETION";

  useEffect(() => {
    let active = true;
    void isAppleNativeSignInAvailable().then((available) => {
      if (active) setAppleAvailable(available);
    });
    return () => { active = false; };
  }, []);

  async function finishDeletion(input: Parameters<typeof mutation.mutateAsync>[0]) {
    await mutation.mutateAsync(input);
    await completeAccountDeletion();
    router.replace("/login");
  }

  async function submit() {
    if (!isConfirmed) return;
    setAppleError(null);
    try {
      await finishDeletion({ password: password || undefined });
    } catch {
      // Server errors render below and can activate the native Apple reauth step.
    }
  }

  async function submitWithApple() {
    if (!isConfirmed) return;
    setAppleError(null);
    setAppleBusy(true);
    try {
      await finishDeletion({ appleCredential: await requestAppleNativeCredential() });
    } catch (reason) {
      if (!(reason instanceof ApiError && reason.code === "APPLE_REAUTH_REQUIRED_FOR_DELETION")) {
        setAppleError(oauthUiStateFromReason(reason, "apple", locale).message);
      }
    } finally {
      setAppleBusy(false);
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
            Tài khoản này cần xác thực lại bằng nhà cung cấp danh tính trước khi xóa. Credential chỉ được tạo bởi luồng đăng nhập native và gửi thẳng tới Laravel.
          </AccountNotice>
        ) : null}
        {appleReauthRequired ? (
          appleAvailable ? (
            <View pointerEvents={appleBusy || mutation.isPending ? "none" : "auto"} style={(appleBusy || mutation.isPending) ? styles.disabled : undefined}>
              {appleBusy ? (
                <View style={styles.appleLoading}><ActivityIndicator color="#ffffff" /></View>
              ) : (
                <AppleAuthentication.AppleAuthenticationButton
                  buttonStyle={AppleAuthentication.AppleAuthenticationButtonStyle.BLACK}
                  buttonType={AppleAuthentication.AppleAuthenticationButtonType.CONTINUE}
                  cornerRadius={12}
                  onPress={() => void submitWithApple()}
                  style={styles.appleButton}
                />
              )}
            </View>
          ) : <AccountNotice tone="danger">Sign in with Apple không khả dụng trên thiết bị này, nên chưa thể hoàn tất ngắt liên kết Apple.</AccountNotice>
        ) : null}
        {appleError ? <Text accessibilityRole="alert" style={styles.error}>{appleError}</Text> : null}
        <AccountMutationError error={mutation.error} />
        <AccountButton disabled={!isConfirmed || appleBusy} label="Xóa vĩnh viễn tài khoản" loading={mutation.isPending} onPress={() => void submit()} tone="danger" />
        <AccountButton label="Quay lại" onPress={() => router.back()} tone="secondary" />
      </AccountCard>
      <AccountNotice>Không nhập ID token Google, Apple identity token hoặc authorization code vào biểu mẫu. Các credential này chỉ được tạo bởi luồng đăng nhập native và xác minh trực tiếp ở Laravel.</AccountNotice>
      <Text style={accountStyles.body}>Nếu máy chủ đang tắt tính năng tự xóa, ứng dụng sẽ hiển thị nguyên trạng lỗi `FEATURE_DISABLED` để người dùng biết cần liên hệ hỗ trợ.</Text>
    </AccountFormScreen>
  );
}

const styles = StyleSheet.create({
  appleButton: { height: 50, width: "100%" },
  appleLoading: { alignItems: "center", backgroundColor: "#000000", borderRadius: 12, height: 50, justifyContent: "center" },
  disabled: { opacity: 0.45 },
  error: { color: "#dc2626", fontSize: 13 }
});
