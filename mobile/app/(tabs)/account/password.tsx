import { useState } from "react";
import { ApiError } from "@/api/client";
import { useChangePassword } from "@/features/account/api";
import { AccountButton, AccountCard, AccountField, AccountFormScreen, AccountHeader, AccountMutationError, AccountNotice } from "@/features/account/components";

export default function PasswordScreen() {
  const mutation = useChangePassword();
  const [currentPassword, setCurrentPassword] = useState("");
  const [password, setPassword] = useState("");
  const [confirmation, setConfirmation] = useState("");
  const [localError, setLocalError] = useState<string>();

  async function submit() {
    setLocalError(undefined);
    if (password.length < 8) {
      setLocalError("Mật khẩu mới phải có ít nhất 8 ký tự.");
      return;
    }
    if (password !== confirmation) {
      setLocalError("Mật khẩu xác nhận không khớp.");
      return;
    }
    try {
      await mutation.mutateAsync({ currentPassword, password, passwordConfirmation: confirmation });
      setCurrentPassword("");
      setPassword("");
      setConfirmation("");
    } catch {
      // The mutation exposes normalized server and field errors below.
    }
  }

  return (
    <AccountFormScreen>
      <AccountHeader title="Đổi mật khẩu" subtitle="Sau khi đổi, Laravel sẽ thu hồi mọi access token trên thiết bị khác và giữ phiên hiện tại." />
      <AccountCard>
        <AccountField autoCapitalize="none" autoComplete="password" error={mutation.error instanceof ApiError ? mutation.error.errors?.current_password?.[0] : undefined} label="Mật khẩu hiện tại" onChangeText={setCurrentPassword} secureTextEntry value={currentPassword} />
        <AccountField autoCapitalize="none" autoComplete="new-password" error={localError ?? (mutation.error instanceof ApiError ? mutation.error.errors?.password?.[0] : undefined)} label="Mật khẩu mới" onChangeText={setPassword} secureTextEntry value={password} />
        <AccountField autoCapitalize="none" autoComplete="new-password" label="Xác nhận mật khẩu mới" onChangeText={setConfirmation} secureTextEntry value={confirmation} />
        <AccountMutationError error={mutation.error} />
        {mutation.isSuccess ? <AccountNotice tone="success">{mutation.data.message ?? "Đổi mật khẩu thành công."}</AccountNotice> : null}
        <AccountButton disabled={!currentPassword || !password || !confirmation} label="Cập nhật mật khẩu" loading={mutation.isPending} onPress={() => void submit()} />
      </AccountCard>
    </AccountFormScreen>
  );
}
