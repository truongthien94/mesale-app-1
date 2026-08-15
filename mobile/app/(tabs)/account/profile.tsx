import { useEffect, useState } from "react";
import { UserRound } from "lucide-react-native";
import { ApiError } from "@/api/client";
import { useAuth } from "@/auth/AuthProvider";
import { ErrorState, LoadingState, OfflineState } from "@/components/AsyncState";
import { CompactBlueHero } from "@/components/CompactBlueHero";
import { useAccount, useUpdateProfile } from "@/features/account/api";
import { AvatarEditor } from "@/features/account/AvatarEditor";
import { AccountButton, AccountCard, AccountField, AccountFormScreen, AccountMutationError, AccountNotice } from "@/features/account/components";

export default function ProfileScreen() {
  const query = useAccount();
  const mutation = useUpdateProfile();
  const { refreshUser } = useAuth();
  const [name, setName] = useState("");
  const [phone, setPhone] = useState("");
  const [nameError, setNameError] = useState<string>();

  useEffect(() => {
    if (query.data) {
      setName(query.data.name);
      setPhone(query.data.phone ?? "");
    }
  }, [query.data]);

  if (query.isPending) return <LoadingState label="Đang tải hồ sơ..." />;
  if (query.isError) {
    const props = { title: "Không thể tải hồ sơ", message: query.error instanceof Error ? query.error.message : undefined, actionLabel: "Thử lại", onAction: () => void query.refetch() };
    return query.error instanceof ApiError && query.error.isNetworkError ? <OfflineState {...props} /> : <ErrorState {...props} />;
  }

  async function submit() {
    if (!name.trim()) {
      setNameError("Vui lòng nhập họ và tên.");
      return;
    }
    setNameError(undefined);
    try {
      await mutation.mutateAsync({ name, phone });
      await refreshUser();
    } catch {
      // The mutation exposes normalized server and field errors below.
    }
  }

  return (
    <AccountFormScreen>
      <CompactBlueHero icon={UserRound} subtitle="Thông tin được lưu vào cùng hồ sơ thành viên trên mesale.vn." title="Thông tin cá nhân" />
      <AccountCard>
        <AvatarEditor avatar={query.data.avatar} name={query.data.name} />
      </AccountCard>
      <AccountCard>
        <AccountField error={nameError ?? (mutation.error instanceof ApiError ? mutation.error.errors?.name?.[0] : undefined)} label="Họ và tên" onChangeText={setName} value={name} />
        <AccountField autoCapitalize="none" error={mutation.error instanceof ApiError ? mutation.error.errors?.phone?.[0] : undefined} keyboardType="phone-pad" label="Số điện thoại" onChangeText={setPhone} placeholder="Để trống nếu chưa sử dụng" value={phone} />
        <AccountMutationError error={mutation.error} />
        {mutation.isSuccess ? <AccountNotice tone="success">{mutation.data.message ?? "Cập nhật hồ sơ thành công."}</AccountNotice> : null}
        <AccountButton label="Lưu thay đổi" loading={mutation.isPending} onPress={() => void submit()} />
      </AccountCard>
    </AccountFormScreen>
  );
}
