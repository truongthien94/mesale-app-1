import { useState } from "react";
import { Image, Pressable, StyleSheet, Text, View } from "react-native";
import { ImagePlus, Trash2, Upload } from "lucide-react-native";
import { useAuth } from "@/auth/AuthProvider";
import { AccountButton, AccountMutationError, AccountNotice } from "@/features/account/components";
import { useDeleteAvatar, useUploadAvatar, type AvatarUpload } from "@/features/account/api";
import { pickAvatarImage } from "@/features/account/avatar";
import { useTheme } from "@/theme/ThemeProvider";
import { spacing } from "@/theme/tokens";

function secureRemoteAvatarUri(value: string | null): string | null {
  if (!value) return null;
  try {
    const url = new URL(value);
    if (url.protocol === "http:") url.protocol = "https:";
    return url.protocol === "https:" ? url.toString() : null;
  } catch {
    return null;
  }
}

export function AvatarEditor({ avatar, name }: { avatar: string | null; name: string }) {
  const { colors } = useTheme();
  const { refreshUser } = useAuth();
  const uploadMutation = useUploadAvatar();
  const deleteMutation = useDeleteAvatar();
  const [selectedImage, setSelectedImage] = useState<AvatarUpload | null>(null);
  const [pickerError, setPickerError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const currentAvatarUri = secureRemoteAvatarUri(avatar);
  const previewUri = selectedImage?.uri ?? currentAvatarUri;
  const initial = name.trim().slice(0, 1).toUpperCase() || "M";
  const isBusy = uploadMutation.isPending || deleteMutation.isPending;

  async function chooseImage() {
    setPickerError(null);
    setSuccessMessage(null);
    try {
      const image = await pickAvatarImage();
      if (!image) return;
      uploadMutation.reset();
      deleteMutation.reset();
      setSelectedImage(image);
    } catch (reason) {
      setPickerError(reason instanceof Error ? reason.message : "Không thể mở thư viện ảnh lúc này.");
    }
  }

  async function uploadImage() {
    if (!selectedImage) return;
    setSuccessMessage(null);
    try {
      await uploadMutation.mutateAsync(selectedImage);
      setSelectedImage(null);
      await refreshUser().catch(() => undefined);
      setSuccessMessage("Đã cập nhật ảnh đại diện.");
    } catch {
      // Normalized API and offline errors are rendered below for retry.
    }
  }

  async function removeImage() {
    setSuccessMessage(null);
    try {
      await deleteMutation.mutateAsync();
      setSelectedImage(null);
      await refreshUser().catch(() => undefined);
      setSuccessMessage("Đã xóa ảnh đại diện.");
    } catch {
      // Normalized API and offline errors are rendered below for retry.
    }
  }

  return (
    <View style={styles.section}>
      <Text style={[styles.title, { color: colors.text }]}>Ảnh đại diện</Text>
      <View style={styles.previewRow}>
        <View style={[styles.avatarRing, { borderColor: colors.primary }]}>
          <View style={[styles.avatar, { backgroundColor: colors.background }]}>
            {previewUri ? (
              <Image
                accessibilityIgnoresInvertColors
                accessibilityLabel={selectedImage ? "Ảnh đại diện mới đang chờ tải lên" : "Ảnh đại diện hiện tại"}
                source={{ uri: previewUri }}
                style={styles.image}
              />
            ) : (
              <Text style={[styles.initial, { color: colors.primary }]}>{initial}</Text>
            )}
          </View>
        </View>
        <View style={styles.previewCopy}>
          <Text style={[styles.previewTitle, { color: colors.text }]}>
            {selectedImage ? "Xem trước ảnh mới" : "Ảnh hồ sơ Mê Sale"}
          </Text>
          <Text style={[styles.previewHint, { color: colors.mutedText }]}>Ảnh vuông, tối đa 512 px sau khi xử lý.</Text>
        </View>
      </View>

      <Pressable
        accessibilityRole="button"
        accessibilityState={{ disabled: isBusy }}
        disabled={isBusy}
        onPress={() => void chooseImage()}
        style={({ pressed }) => [
          styles.chooseButton,
          { backgroundColor: colors.background, borderColor: colors.border },
          isBusy && styles.disabled,
          pressed && styles.pressed
        ]}
      >
        <ImagePlus color={colors.primary} size={18} strokeWidth={2.2} />
        <Text style={[styles.chooseButtonText, { color: colors.primary }]}>{selectedImage ? "Chọn ảnh khác" : "Chọn ảnh từ thư viện"}</Text>
      </Pressable>

      {selectedImage ? (
        <View style={styles.actions}>
          <View style={styles.actionGrow}>
            <AccountButton label="Tải ảnh lên" loading={uploadMutation.isPending} disabled={deleteMutation.isPending} onPress={() => void uploadImage()} />
          </View>
          <Pressable
            accessibilityRole="button"
            accessibilityState={{ disabled: isBusy }}
            disabled={isBusy}
            onPress={() => {
              setSelectedImage(null);
              setPickerError(null);
              uploadMutation.reset();
            }}
            style={({ pressed }) => [styles.cancelButton, { borderColor: colors.border }, isBusy && styles.disabled, pressed && styles.pressed]}
          >
            <Text style={[styles.cancelText, { color: colors.mutedText }]}>Hủy</Text>
          </Pressable>
        </View>
      ) : currentAvatarUri ? (
        <Pressable
          accessibilityRole="button"
          accessibilityState={{ busy: deleteMutation.isPending, disabled: isBusy }}
          disabled={isBusy}
          onPress={() => void removeImage()}
          style={({ pressed }) => [styles.removeButton, { borderColor: colors.danger }, isBusy && styles.disabled, pressed && styles.pressed]}
        >
          <Trash2 color={colors.danger} size={17} strokeWidth={2.2} />
          <Text style={[styles.removeText, { color: colors.danger }]}>Xóa ảnh đại diện</Text>
        </Pressable>
      ) : null}

      {selectedImage ? (
        <View style={[styles.pendingNotice, { backgroundColor: `${colors.primary}12`, borderColor: `${colors.primary}45` }]}>
          <Upload color={colors.primary} size={17} strokeWidth={2.2} />
          <Text style={[styles.pendingText, { color: colors.text }]}>Ảnh mới chỉ là bản xem trước. Chọn “Tải ảnh lên” để lưu.</Text>
        </View>
      ) : null}
      {pickerError ? <AccountNotice tone="danger">{pickerError}</AccountNotice> : null}
      <AccountMutationError error={uploadMutation.error ?? deleteMutation.error} />
      {successMessage ? <AccountNotice tone="success">{successMessage}</AccountNotice> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  section: { gap: spacing.md },
  title: { fontSize: 14, fontWeight: "900" },
  previewRow: { alignItems: "center", flexDirection: "row", gap: spacing.md },
  avatarRing: { alignItems: "center", borderRadius: 54, borderWidth: 3, height: 108, justifyContent: "center", width: 108 },
  avatar: { alignItems: "center", borderRadius: 48, height: 96, justifyContent: "center", overflow: "hidden", width: 96 },
  image: { height: 96, width: 96 },
  initial: { fontSize: 36, fontWeight: "900" },
  previewCopy: { flex: 1, gap: spacing.xs, minWidth: 0 },
  previewTitle: { fontSize: 15, fontWeight: "800" },
  previewHint: { fontSize: 12, lineHeight: 18 },
  chooseButton: { alignItems: "center", borderRadius: 12, borderWidth: 1, flexDirection: "row", gap: spacing.sm, justifyContent: "center", minHeight: 48, paddingHorizontal: spacing.md },
  chooseButtonText: { fontSize: 13, fontWeight: "800" },
  actions: { alignItems: "stretch", flexDirection: "row", gap: spacing.sm },
  actionGrow: { flex: 1 },
  cancelButton: { alignItems: "center", borderRadius: 12, borderWidth: 1, justifyContent: "center", minHeight: 50, paddingHorizontal: spacing.md },
  cancelText: { fontSize: 13, fontWeight: "800" },
  removeButton: { alignItems: "center", borderRadius: 12, borderWidth: 1, flexDirection: "row", gap: spacing.sm, justifyContent: "center", minHeight: 48, paddingHorizontal: spacing.md },
  removeText: { fontSize: 13, fontWeight: "800" },
  pendingNotice: { alignItems: "center", borderRadius: 12, borderWidth: 1, flexDirection: "row", gap: spacing.sm, padding: spacing.md },
  pendingText: { flex: 1, fontSize: 12, lineHeight: 18 },
  disabled: { opacity: 0.45 },
  pressed: { opacity: 0.78 }
});
