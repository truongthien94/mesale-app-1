import { useState } from "react";
import { useInfiniteQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { FlatList, Image, KeyboardAvoidingView, Modal, Platform, ScrollView, StyleSheet, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { ApiError } from "@/api/client";
import { useAuth } from "@/auth/AuthProvider";
import { EmptyState, ErrorState, LoadingState, OfflineState } from "@/components/AsyncState";
import { FormErrorSummary } from "@/components/FormErrorSummary";
import { env } from "@/config/env";
import { fetchGifts, redeemGift, type Gift, type GiftRedemptionInput } from "@/features/earn/api";
import { invalidateRewardCaches } from "@/features/earn/cache";
import { requiresPhysicalAddress } from "@/features/earn/contracts";
import { useStableEarnSubmission } from "@/features/earn/submission";
import { ActionButton, Card, Field, FilterChip, ListFooterLoading, ScreenHeader, earnStyles, formatMoney } from "@/features/earn/ui";
import { getDeviceLocale } from "@/i18n";
import { colors, spacing } from "@/theme/tokens";

function imageUrl(value: string | null): string | null {
  if (!value) return null;
  if (/^https?:\/\//i.test(value)) return value;
  try {
    return new URL(value.startsWith("/") ? value : `/${value}`, new URL(env.apiBaseUrl).origin).toString();
  } catch {
    return null;
  }
}

function giftRedemptionFingerprint(payload: GiftRedemptionInput) {
  return payload;
}

export default function GiftsScreen() {
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();
  const { user } = useAuth();
  const vi = getDeviceLocale() === "vi";
  const [searchDraft, setSearchDraft] = useState("");
  const [search, setSearch] = useState("");
  const [type, setType] = useState("all");
  const [sort, setSort] = useState("newest");
  const [selectedGift, setSelectedGift] = useState<Gift | null>(null);
  const [fullname, setFullname] = useState(user?.name ?? "");
  const [phone, setPhone] = useState("");
  const [email, setEmail] = useState(user?.email ?? "");
  const [address, setAddress] = useState("");
  const [notes, setNotes] = useState("");
  const [feedback, setFeedback] = useState<string | null>(null);
  const stableSubmission = useStableEarnSubmission("gift.redeem", giftRedemptionFingerprint);
  const query = useInfiniteQuery({
    queryKey: ["earn", "gifts", search, type, sort],
    initialPageParam: 1,
    queryFn: ({ pageParam, signal }) => fetchGifts(pageParam, {
      search: search || undefined,
      type: type === "all" ? undefined : type,
      sort
    }, signal),
    getNextPageParam: (page) => page.pagination.current_page < page.pagination.last_page
      ? page.pagination.current_page + 1
      : undefined
  });
  const mutation = useMutation({
    mutationFn: redeemGift,
    onSuccess: async (response, variables) => {
      setFeedback(response.message ?? (vi ? `Đổi quà thành công: ${response.data.code}` : `Gift redeemed: ${response.data.code}`));
      setSelectedGift(null);
      setAddress("");
      setNotes("");
      stableSubmission.reset(variables.payload);
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ["earn", "gifts"] }),
        queryClient.invalidateQueries({ queryKey: ["earn", "gift-redemptions"] }),
        invalidateRewardCaches(queryClient)
      ]);
    }
  });

  if (query.isPending) return <LoadingState label={vi ? "Đang tải kho quà..." : "Loading gifts..."} />;
  if (query.isError) {
    const props = {
      actionLabel: vi ? "Thử lại" : "Retry",
      message: query.error instanceof Error ? query.error.message : undefined,
      onAction: () => void query.refetch(),
      title: vi ? "Không thể tải quà" : "Unable to load gifts"
    };
    return query.error instanceof ApiError && query.error.isNetworkError ? <OfflineState {...props} /> : <ErrorState {...props} />;
  }

  const gifts = query.data.pages.flatMap((page) => page.items);
  const mutationError = mutation.error instanceof ApiError ? mutation.error : null;
  const missingRequired = !selectedGift || !fullname.trim() || !phone.trim() || !email.trim()
    || (requiresPhysicalAddress(selectedGift.type) && !address.trim());

  return (
    <View style={earnStyles.screen}>
      <FlatList
        contentContainerStyle={[earnStyles.listContent, { paddingBottom: insets.bottom + spacing.lg }]}
        contentInsetAdjustmentBehavior="automatic"
        data={gifts}
        keyExtractor={(gift) => String(gift.id)}
        keyboardShouldPersistTaps="handled"
        ListEmptyComponent={<EmptyState message={vi ? "Hãy thay đổi từ khóa hoặc bộ lọc." : "Try another search or filter."} style={styles.empty} title={vi ? "Không tìm thấy quà" : "No gifts found"} />}
        ListFooterComponent={<ListFooterLoading visible={query.isFetchingNextPage} />}
        ListHeaderComponent={
          <View style={styles.headerContent}>
            <ScreenHeader
              eyebrow={vi ? "Kho quà thành viên" : "Member gift catalog"}
              subtitle={vi ? "Số dư và tồn kho được Laravel xác nhận tại thời điểm đổi quà." : "Balance and stock are verified by Laravel when you redeem."}
              title={vi ? "Đổi quà" : "Gifts"}
            />
            {feedback ? <View accessibilityLiveRegion="polite" style={earnStyles.feedback}><Text style={earnStyles.feedbackText}>{feedback}</Text></View> : null}
            <Card>
              <Field
                autoCapitalize="none"
                label={vi ? "Tìm quà" : "Search gifts"}
                onChangeText={setSearchDraft}
                onSubmitEditing={() => setSearch(searchDraft.trim())}
                placeholder={vi ? "Tên phần quà..." : "Gift name..."}
                returnKeyType="search"
                value={searchDraft}
              />
              <ActionButton label={vi ? "Tìm kiếm" : "Search"} onPress={() => setSearch(searchDraft.trim())} />
            </Card>
            <View style={earnStyles.chips}>
              <FilterChip label={vi ? "Tất cả" : "All"} onPress={() => setType("all")} selected={type === "all"} />
              <FilterChip label={vi ? "Quà vật lý" : "Physical"} onPress={() => setType("physical")} selected={type === "physical"} />
              <FilterChip label="Giftcode" onPress={() => setType("giftcode")} selected={type === "giftcode"} />
            </View>
            <View style={earnStyles.chips}>
              <FilterChip label={vi ? "Mới nhất" : "Newest"} onPress={() => setSort("newest")} selected={sort === "newest"} />
              <FilterChip label={vi ? "Giá tăng" : "Price low"} onPress={() => setSort("price_asc")} selected={sort === "price_asc"} />
              <FilterChip label={vi ? "Giá giảm" : "Price high"} onPress={() => setSort("price_desc")} selected={sort === "price_desc"} />
            </View>
            <Text style={earnStyles.sectionTitle}>{vi ? "Quà đang có" : "Available gifts"}</Text>
          </View>
        }
        onEndReached={() => {
          if (query.hasNextPage && !query.isFetchingNextPage) void query.fetchNextPage();
        }}
        onEndReachedThreshold={0.4}
        renderItem={({ item }) => {
          const uri = imageUrl(item.image);
          return (
            <Card>
              <View style={styles.giftRow}>
                {uri ? <Image accessibilityIgnoresInvertColors source={{ uri }} style={styles.image} /> : <View style={styles.imagePlaceholder}><Text style={styles.imageMarker}>GF</Text></View>}
                <View style={styles.giftBody}>
                  {item.tag ? <Text style={styles.tag}>{item.tag}</Text> : null}
                  <Text style={styles.giftTitle}>{item.title}</Text>
                  {item.description ? <Text numberOfLines={3} style={earnStyles.body}>{item.description}</Text> : null}
                  <View style={earnStyles.rowBetween}>
                    <Text style={earnStyles.amount}>{formatMoney(item.price)}</Text>
                    <Text style={styles.stock}>{vi ? "Còn" : "Stock"}: {item.stock}</Text>
                  </View>
                </View>
              </View>
              <ActionButton
                disabled={item.stock <= 0}
                label={item.stock > 0 ? (vi ? "Đổi quà này" : "Redeem this gift") : (vi ? "Đã hết quà" : "Out of stock")}
                onPress={() => {
                  mutation.reset();
                  setSelectedGift(item);
                }}
              />
            </Card>
          );
        }}
      />

      <Modal animationType="slide" onRequestClose={() => setSelectedGift(null)} transparent visible={selectedGift !== null}>
        <KeyboardAvoidingView behavior={Platform.OS === "ios" ? "padding" : undefined} style={earnStyles.modalBackdrop}>
          <View style={[earnStyles.modalCard, { paddingBottom: insets.bottom + spacing.md }]}>
            <ScrollView contentInsetAdjustmentBehavior="automatic" keyboardShouldPersistTaps="handled">
              <View style={styles.formContent}>
                <View style={earnStyles.rowBetween}>
                  <View style={styles.giftBody}>
                    <Text style={styles.tag}>{vi ? "XÁC NHẬN ĐỔI QUÀ" : "CONFIRM REDEMPTION"}</Text>
                    <Text accessibilityRole="header" style={styles.giftTitle}>{selectedGift?.title}</Text>
                  </View>
                  <Text style={earnStyles.amount}>{selectedGift ? formatMoney(selectedGift.price) : ""}</Text>
                </View>
                {mutationError ? <FormErrorSummary errors={mutationError.errors} message={mutationError.message} /> : null}
                <Field autoComplete="name" label={vi ? "Họ và tên" : "Full name"} onChangeText={setFullname} value={fullname} />
                <Field autoComplete="tel" keyboardType="phone-pad" label={vi ? "Số điện thoại" : "Phone"} onChangeText={setPhone} value={phone} />
                <Field autoCapitalize="none" autoComplete="email" keyboardType="email-address" label="Email" onChangeText={setEmail} value={email} />
                {selectedGift && requiresPhysicalAddress(selectedGift.type) ? (
                  <Field label={vi ? "Địa chỉ nhận quà" : "Delivery address"} multiline onChangeText={setAddress} value={address} />
                ) : null}
                <Field label={vi ? "Ghi chú" : "Notes"} maxLength={1000} multiline onChangeText={setNotes} value={notes} />
                <Text style={styles.disclaimer}>{vi ? "Hệ thống sẽ kiểm tra lại số dư và tồn kho trước khi trừ tiền." : "The server rechecks your balance and stock before charging."}</Text>
                <ActionButton
                  disabled={missingRequired}
                  label={vi ? "Xác nhận đổi quà" : "Confirm redemption"}
                  loading={mutation.isPending}
                  onPress={() => {
                    if (!selectedGift) return;
                    mutation.mutate(stableSubmission.getVariables({
                      gift_id: selectedGift.id,
                      fullname: fullname.trim(),
                      phone: phone.trim(),
                      email: email.trim(),
                      address: address.trim() || undefined,
                      notes: notes.trim() || undefined
                    }));
                  }}
                />
                <ActionButton label={vi ? "Hủy" : "Cancel"} onPress={() => setSelectedGift(null)} tone="secondary" />
              </View>
            </ScrollView>
          </View>
        </KeyboardAvoidingView>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  headerContent: { gap: spacing.md },
  giftRow: { alignItems: "flex-start", flexDirection: "row", gap: spacing.md },
  image: { backgroundColor: "#f1f5f9", borderRadius: 16, height: 104, width: 104 },
  imagePlaceholder: { alignItems: "center", backgroundColor: "#fff7ed", borderRadius: 16, height: 104, justifyContent: "center", width: 104 },
  imageMarker: { color: colors.primary, fontSize: 20, fontWeight: "900" },
  giftBody: { flex: 1, gap: spacing.xs },
  giftTitle: { color: colors.text, fontSize: 17, fontWeight: "900", lineHeight: 22 },
  tag: { color: colors.primary, fontSize: 11, fontWeight: "900", letterSpacing: 0.8, textTransform: "uppercase" },
  stock: { color: colors.mutedText, fontSize: 12, fontWeight: "700" },
  formContent: { gap: spacing.md, paddingBottom: spacing.lg },
  disclaimer: { color: colors.mutedText, fontSize: 12, lineHeight: 18 },
  empty: { minHeight: 240 }
});
