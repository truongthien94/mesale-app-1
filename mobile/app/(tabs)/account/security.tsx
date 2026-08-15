import { useState } from "react";
import { useRouter } from "expo-router";
import { Linking, Platform, StyleSheet, Text, View } from "react-native";
import { ApiError } from "@/api/client";
import { ErrorState, LoadingState, OfflineState } from "@/components/AsyncState";
import {
  useDisableEmailOtp,
  useDisableTwoFactor,
  useEnableEmailOtp,
  useEnableTwoFactor,
  useSecurity,
  useSendEmailOtp,
  useSetupTwoFactor
} from "@/features/account/api";
import { AccountButton, AccountCard, AccountField, AccountFormScreen, AccountHeader, AccountMenuRow, AccountMutationError, AccountNotice, accountStyles } from "@/features/account/components";
import { colors, spacing } from "@/theme/tokens";

export default function SecurityScreen() {
  const router = useRouter();
  const query = useSecurity();
  const setup = useSetupTwoFactor();
  const enableTwoFactor = useEnableTwoFactor();
  const disableTwoFactor = useDisableTwoFactor();
  const sendEmailOtp = useSendEmailOtp();
  const enableEmailOtp = useEnableEmailOtp();
  const disableEmailOtp = useDisableEmailOtp();
  const [twoFactorCode, setTwoFactorCode] = useState("");
  const [twoFactorPassword, setTwoFactorPassword] = useState("");
  const [emailOtpCode, setEmailOtpCode] = useState("");
  const [emailOtpPassword, setEmailOtpPassword] = useState("");
  const [notice, setNotice] = useState<string>();

  if (query.isPending) return <LoadingState label="Đang tải cài đặt bảo mật..." />;
  if (query.isError) {
    const props = { title: "Không thể tải bảo mật", message: query.error instanceof Error ? query.error.message : undefined, actionLabel: "Thử lại", onAction: () => void query.refetch() };
    return query.error instanceof ApiError && query.error.isNetworkError ? <OfflineState {...props} /> : <ErrorState {...props} />;
  }

  const activeError = setup.error ?? enableTwoFactor.error ?? disableTwoFactor.error ?? sendEmailOtp.error ?? enableEmailOtp.error ?? disableEmailOtp.error;
  const status = query.data;

  async function createSetup() {
    setNotice(undefined);
    try {
      await setup.mutateAsync();
    } catch {
      // Normalized error is rendered below.
    }
  }

  async function turnOnTwoFactor() {
    if (!setup.data) return;
    setNotice(undefined);
    try {
      const response = await enableTwoFactor.mutateAsync({ secret: setup.data.secret_key, otpCode: twoFactorCode });
      setNotice(response.message ?? "Đã bật Google Authenticator.");
      setup.reset();
      setTwoFactorCode("");
    } catch {
      // Normalized error is rendered below.
    }
  }

  async function turnOffTwoFactor() {
    setNotice(undefined);
    try {
      const response = await disableTwoFactor.mutateAsync({ password: twoFactorPassword, otpCode: twoFactorCode });
      setNotice(response.message ?? "Đã tắt Google Authenticator.");
      setTwoFactorPassword("");
      setTwoFactorCode("");
    } catch {
      // Normalized error is rendered below.
    }
  }

  async function sendOtp() {
    setNotice(undefined);
    try {
      const response = await sendEmailOtp.mutateAsync();
      setNotice(response.message ?? "Mã OTP đã được gửi đến email của bạn.");
    } catch {
      // Normalized error is rendered below.
    }
  }

  async function changeEmailOtp() {
    setNotice(undefined);
    try {
      const response = status.email_otp_enabled
        ? await disableEmailOtp.mutateAsync({ password: emailOtpPassword, otpCode: emailOtpCode })
        : await enableEmailOtp.mutateAsync(emailOtpCode);
      setNotice(response.message ?? (status.email_otp_enabled ? "Đã tắt OTP email." : "Đã bật OTP email."));
      setEmailOtpCode("");
      setEmailOtpPassword("");
    } catch {
      // Normalized error is rendered below.
    }
  }

  return (
    <AccountFormScreen>
      <AccountHeader title="Bảo mật tài khoản" />
      <AccountCard>
        <AccountMenuRow
          onPress={() => router.push("/(tabs)/account/password")}
          subtitle="Cập nhật mật khẩu đăng nhập và bảo vệ các phiên đang hoạt động"
          title="Đổi mật khẩu"
        />
      </AccountCard>
      <AccountCard>
        <View style={accountStyles.row}>
          <View style={styles.copy}><Text style={accountStyles.strong}>Google Authenticator</Text><Text style={accountStyles.body}>{status.google2fa_enabled ? "Đang bật" : "Chưa bật"}</Text></View>
          <View style={[styles.status, status.google2fa_enabled && styles.statusActive]}><Text style={[styles.statusText, status.google2fa_enabled && styles.statusTextActive]}>{status.google2fa_enabled ? "Bật" : "Tắt"}</Text></View>
        </View>
        {!status.google2fa_enabled && !setup.data ? <AccountButton label="Tạo khóa thiết lập 2FA" loading={setup.isPending} onPress={() => void createSetup()} tone="secondary" /> : null}
        {!status.google2fa_enabled && setup.data ? (
          <View style={accountStyles.section}>
            <AccountNotice>Khóa bí mật chỉ hiển thị trong phiên thiết lập này. Thêm khóa vào ứng dụng xác thực, sau đó nhập mã 6 số để bật 2FA.</AccountNotice>
            <View style={styles.secretBox}><Text selectable style={styles.secret}>{setup.data.secret_key}</Text></View>
            <AccountButton label="Mở ứng dụng xác thực" onPress={() => void Linking.openURL(setup.data.otpauth_url)} tone="secondary" />
            <AccountField keyboardType="number-pad" label="Mã 6 số" maxLength={6} onChangeText={(value) => setTwoFactorCode(value.replace(/\D/g, ""))} value={twoFactorCode} />
            <AccountButton disabled={twoFactorCode.length !== 6} label="Bật Google Authenticator" loading={enableTwoFactor.isPending} onPress={() => void turnOnTwoFactor()} />
          </View>
        ) : null}
        {status.google2fa_enabled ? (
          <View style={accountStyles.section}>
            <AccountField autoCapitalize="none" label="Mật khẩu hiện tại" onChangeText={setTwoFactorPassword} secureTextEntry value={twoFactorPassword} />
            <AccountField keyboardType="number-pad" label="Mã Google Authenticator" maxLength={6} onChangeText={(value) => setTwoFactorCode(value.replace(/\D/g, ""))} value={twoFactorCode} />
            <AccountButton disabled={!twoFactorPassword || twoFactorCode.length !== 6} label="Tắt Google Authenticator" loading={disableTwoFactor.isPending} onPress={() => void turnOffTwoFactor()} tone="danger" />
          </View>
        ) : null}
      </AccountCard>
      <AccountCard>
        <View style={accountStyles.row}>
          <View style={styles.copy}><Text style={accountStyles.strong}>OTP qua email</Text><Text style={accountStyles.body}>{status.email_verified ? "Email đã xác minh" : "Cần xác minh email trước"}</Text></View>
          <View style={[styles.status, status.email_otp_enabled && styles.statusActive]}><Text style={[styles.statusText, status.email_otp_enabled && styles.statusTextActive]}>{status.email_otp_enabled ? "Bật" : "Tắt"}</Text></View>
        </View>
        <AccountButton disabled={!status.email_verified} label="Gửi mã OTP email" loading={sendEmailOtp.isPending} onPress={() => void sendOtp()} tone="secondary" />
        <AccountField keyboardType="number-pad" label="Mã OTP email" maxLength={6} onChangeText={(value) => setEmailOtpCode(value.replace(/\D/g, ""))} value={emailOtpCode} />
        {status.email_otp_enabled ? <AccountField autoCapitalize="none" label="Mật khẩu hiện tại" onChangeText={setEmailOtpPassword} secureTextEntry value={emailOtpPassword} /> : null}
        <AccountButton
          disabled={emailOtpCode.length !== 6 || (status.email_otp_enabled && !emailOtpPassword)}
          label={status.email_otp_enabled ? "Tắt OTP email" : "Bật OTP email"}
          loading={enableEmailOtp.isPending || disableEmailOtp.isPending}
          onPress={() => void changeEmailOtp()}
          tone={status.email_otp_enabled ? "danger" : "primary"}
        />
      </AccountCard>
      <AccountMutationError error={activeError} />
      {notice ? <AccountNotice tone="success">{notice}</AccountNotice> : null}
    </AccountFormScreen>
  );
}

const styles = StyleSheet.create({
  copy: { flex: 1, gap: spacing.xs },
  status: { backgroundColor: "#f1f5f9", borderRadius: 999, paddingHorizontal: spacing.sm, paddingVertical: spacing.xs },
  statusActive: { backgroundColor: "#dcfce7" },
  statusText: { color: colors.mutedText, fontSize: 11, fontWeight: "800" },
  statusTextActive: { color: "#15803d" },
  secretBox: { backgroundColor: "#0f172a", borderRadius: 12, padding: spacing.md },
  secret: { color: "#fed7aa", fontFamily: Platform.select({ ios: "Menlo", android: "monospace" }), fontSize: 14, letterSpacing: 1.1, textAlign: "center" }
});
