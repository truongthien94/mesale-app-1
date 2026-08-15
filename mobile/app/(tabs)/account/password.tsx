import { useState } from "react";
import { Eye, EyeOff } from "lucide-react-native";
import { Pressable, StyleSheet, Text, TextInput, View, type TextInputProps } from "react-native";
import { ApiError } from "@/api/client";
import { useChangePassword } from "@/features/account/api";
import { AccountButton, AccountCard, AccountFormScreen, AccountHeader, AccountMutationError, AccountNotice } from "@/features/account/components";
import { colors, spacing, theme } from "@/theme/tokens";

type PasswordErrorKey = "current_password" | "password" | "password_confirmation";
type PasswordErrors = Partial<Record<PasswordErrorKey, string>>;

export default function PasswordScreen() {
  const mutation = useChangePassword();
  const [currentPassword, setCurrentPassword] = useState("");
  const [password, setPassword] = useState("");
  const [confirmation, setConfirmation] = useState("");
  const [localErrors, setLocalErrors] = useState<PasswordErrors>({});
  const [currentVisible, setCurrentVisible] = useState(false);
  const [passwordVisible, setPasswordVisible] = useState(false);
  const [confirmationVisible, setConfirmationVisible] = useState(false);

  const serverErrors = mutation.error instanceof ApiError ? mutation.error.errors : undefined;

  function updateField(key: PasswordErrorKey, value: string, setter: (next: string) => void) {
    setter(value);
    mutation.reset();
    setLocalErrors((current) => ({ ...current, [key]: undefined }));
  }

  async function submit() {
    const nextErrors: PasswordErrors = {};
    if (!currentPassword) nextErrors.current_password = "Vui lòng nhập mật khẩu hiện tại.";
    if (password.length < 8) {
      nextErrors.password = "Mật khẩu mới phải có ít nhất 8 ký tự.";
    }
    if (password !== confirmation) {
      nextErrors.password_confirmation = "Mật khẩu xác nhận không khớp.";
    }
    setLocalErrors(nextErrors);
    if (Object.keys(nextErrors).length > 0) {
      return;
    }
    try {
      await mutation.mutateAsync({ currentPassword, password, passwordConfirmation: confirmation });
      setCurrentPassword("");
      setPassword("");
      setConfirmation("");
      setCurrentVisible(false);
      setPasswordVisible(false);
      setConfirmationVisible(false);
    } catch {
      // The mutation exposes normalized server and field errors below.
    }
  }

  return (
    <AccountFormScreen>
      <AccountHeader title="Đổi mật khẩu" subtitle="Sau khi đổi, Laravel sẽ thu hồi mọi access token trên thiết bị khác và giữ phiên hiện tại." />
      <AccountCard>
        <PasswordField autoComplete="password" error={localErrors.current_password ?? serverErrors?.current_password?.[0]} label="Mật khẩu hiện tại" onChangeText={(value) => updateField("current_password", value, setCurrentPassword)} onToggle={() => setCurrentVisible((current) => !current)} value={currentPassword} visible={currentVisible} />
        <PasswordField autoComplete="new-password" error={localErrors.password ?? serverErrors?.password?.[0]} label="Mật khẩu mới" onChangeText={(value) => updateField("password", value, setPassword)} onToggle={() => setPasswordVisible((current) => !current)} value={password} visible={passwordVisible} />
        <PasswordField autoComplete="new-password" error={localErrors.password_confirmation ?? serverErrors?.password_confirmation?.[0]} label="Xác nhận mật khẩu mới" onChangeText={(value) => updateField("password_confirmation", value, setConfirmation)} onToggle={() => setConfirmationVisible((current) => !current)} value={confirmation} visible={confirmationVisible} />
        <AccountMutationError error={mutation.error} />
        {mutation.isSuccess ? <AccountNotice tone="success">{mutation.data.message ?? "Đổi mật khẩu thành công."}</AccountNotice> : null}
        <AccountButton compact disabled={!currentPassword || !password || !confirmation} label="Cập nhật mật khẩu" loading={mutation.isPending} onPress={() => void submit()} />
      </AccountCard>
    </AccountFormScreen>
  );
}

function PasswordField({ error, label, onToggle, visible, ...props }: TextInputProps & { error?: string; label: string; onToggle(): void; visible: boolean }) {
  return (
    <View style={styles.field}>
      <Text style={styles.label}>{label}</Text>
      <View style={[styles.inputShell, error && styles.inputError]}>
        <TextInput
          accessibilityLabel={label}
          autoCapitalize="none"
          placeholderTextColor={colors.mutedText}
          secureTextEntry={!visible}
          style={styles.input}
          {...props}
        />
        <Pressable
          accessibilityLabel={visible ? `Ẩn ${label.toLowerCase()}` : `Hiện ${label.toLowerCase()}`}
          accessibilityRole="button"
          accessibilityState={{ expanded: visible }}
          hitSlop={4}
          onPress={onToggle}
          style={({ pressed }) => [styles.eyeButton, pressed && styles.pressed]}
        >
          {visible ? <EyeOff color={colors.mutedText} size={20} strokeWidth={1.8} /> : <Eye color={colors.mutedText} size={20} strokeWidth={1.8} />}
        </Pressable>
      </View>
      {error ? <Text accessibilityRole="alert" style={styles.fieldError}>{error}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  field: { gap: spacing.xs },
  label: { color: colors.text, fontSize: 12, fontWeight: "800", letterSpacing: 0.3, textTransform: "uppercase" },
  inputShell: { alignItems: "center", backgroundColor: "#f8fafc", borderColor: colors.border, borderRadius: theme.radius.md, borderWidth: 1, flexDirection: "row", minHeight: 46, paddingLeft: spacing.md },
  inputError: { borderColor: colors.danger },
  input: { color: colors.text, flex: 1, fontSize: 14, minHeight: 44, paddingVertical: 10 },
  eyeButton: { alignItems: "center", height: 44, justifyContent: "center", width: 44 },
  fieldError: { color: colors.danger, fontSize: 12 },
  pressed: { opacity: 0.72 }
});
