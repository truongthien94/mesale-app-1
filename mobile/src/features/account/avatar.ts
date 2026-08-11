import { manipulateAsync, SaveFormat } from "expo-image-manipulator";
import * as ImagePicker from "expo-image-picker";
import type { AvatarUpload } from "@/features/account/api";

const MAX_AVATAR_SIZE = 512;

export async function pickAvatarImage(): Promise<AvatarUpload | null> {
  const result = await ImagePicker.launchImageLibraryAsync({
    mediaTypes: ["images"],
    allowsEditing: true,
    aspect: [1, 1],
    allowsMultipleSelection: false,
    base64: false,
    exif: false,
    quality: 1
  });

  if (result.canceled) return null;
  const asset = result.assets[0];
  if (!asset || asset.width <= 0 || asset.height <= 0) {
    throw new Error("Không thể đọc kích thước ảnh đã chọn.");
  }

  const cropSize = Math.min(asset.width, asset.height);
  const outputSize = Math.min(MAX_AVATAR_SIZE, cropSize);
  const image = await manipulateAsync(asset.uri, [
    {
      crop: {
        originX: Math.floor((asset.width - cropSize) / 2),
        originY: Math.floor((asset.height - cropSize) / 2),
        width: cropSize,
        height: cropSize
      }
    },
    { resize: { width: outputSize, height: outputSize } }
  ], {
    base64: false,
    compress: 0.85,
    format: SaveFormat.JPEG
  });

  return { uri: image.uri, name: "avatar.jpg", type: "image/jpeg" };
}
