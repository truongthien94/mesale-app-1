import { useState } from "react";
import { router } from "expo-router";
import { StatusBar } from "expo-status-bar";
import { LinearGradient } from "expo-linear-gradient";
import {
  AlertTriangle,
  ChevronDown,
  ChevronLeft,
  ShieldCheck,
  XCircle
} from "lucide-react-native";
import {
  FlatList,
  Pressable,
  StyleSheet,
  Text,
  View
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { useIosPayoutFeaturesEnabled } from "@/config/features";
import { useTheme } from "@/theme/ThemeProvider";
import type { Theme } from "@/theme/tokens";

type TipExample = {
  steps: string[];
  result: string;
};

type CashbackTip = {
  id: number;
  title: string;
  description: string[];
  solution?: string;
  example?: TipExample;
};

const CASHBACK_TIPS: CashbackTip[] = [
  {
    id: 1,
    title: 'Dính "dấu vết" từ Shopee Video/Live trong 7 ngày',
    description: [
      "Nếu trong vòng 7 ngày trước khi mua, bạn từng bấm vào sản phẩm đó từ Shopee Live, Shopee Video hoặc link YouTube, hệ thống sàn có thể ưu tiên ghi nhận đơn cho kênh đó thay vì Mê Sale."
    ],
    example: {
      steps: [
        "Trong 7 ngày qua, bạn từng bấm vào Sản phẩm A từ Shopee Live/Video để xem giá hoặc thử áp mã.",
        "Bạn xoá sản phẩm đó khỏi giỏ hàng.",
        "Bạn copy link Sản phẩm A, dán vào Mê Sale để mua hàng nhận hoàn tiền.",
        "Chốt đơn và thanh toán."
      ],
      result: 'Shopee có thể tính "công giới thiệu" cho kênh Live/Video trước đó (dấu vết click cũ còn lưu 7 ngày) — đơn KHÔNG được hoàn tiền trên Mê Sale.'
    },
    solution: "Không bấm xem hoặc thêm giỏ hàng sản phẩm từ Video, Live hay YouTube trong vòng 7 ngày trước khi đặt qua Mê Sale."
  },
  {
    id: 2,
    title: "Thanh toán trước bằng thẻ tín dụng / thẻ ngân hàng",
    description: [
      "Theo phản hồi từ cộng đồng, nhiều đơn Shopee không được ghi nhận rơi vào trường hợp thanh toán trước qua thẻ. Thực tế vẫn có nhiều đơn thanh toán trước được hoàn bình thường — nhưng rủi ro cao hơn."
    ],
    solution: "Nếu tiện, ưu tiên thanh toán khi nhận hàng (COD) để giảm nguy cơ đơn không được ghi nhận."
  },
  {
    id: 3,
    title: "Bấm link nơi khác SAU khi bấm link Mê Sale",
    description: [
      "Bạn đã bấm link từ Mê Sale nhưng sau đó lỡ tay bấm thêm một link quảng cáo, banner hoặc link bạn bè gửi rồi mới đặt hàng — đơn có thể thuộc về link cuối cùng đó, không phải Mê Sale."
    ],
    solution: "Sau khi bấm link Mê Sale, hãy đặt đơn mà không mở thêm link quảng cáo hoặc link sản phẩm khác để tránh ghi đè lượt giới thiệu."
  },
  {
    id: 4,
    title: "Đặt nhiều đơn nhưng chỉ bấm link 1 lần",
    description: [
      "Mỗi lần bấm link qua Mê Sale chỉ được tính cho 01 đơn hàng đầu tiên."
    ],
    solution: "Muốn mua đơn thứ 2, thứ 3... hãy quay lại Mê Sale bấm link mới trước MỖI lần thanh toán."
  },
  {
    id: 5,
    title: "Bấm link dồn dập rồi mới đặt hàng một loạt",
    description: [
      "Dù bạn bấm link Mê Sale 10 lần liên tiếp rồi mới đi thanh toán một loạt đơn, hệ thống cũng có thể chỉ ghi nhận cho đơn đầu tiên."
    ],
    solution: "Quy tắc an toàn: bấm link → đặt 1 đơn → quay lại Mê Sale bấm link mới → đặt đơn tiếp theo."
  },
  {
    id: 6,
    title: "Bấm link sàn này nhưng mua hàng sàn khác",
    description: [
      "Ví dụ: bạn bấm link Shopee trên Mê Sale nhưng lại sang TikTok Shop đặt hàng (hoặc ngược lại) — đơn này không được ghi nhận hoàn tiền."
    ],
    solution: "Mua sàn nào thì dán link và bấm Mua ngay của đúng sàn đó trên Mê Sale."
  },
  {
    id: 7,
    title: "Bấm link trên máy này, đặt hàng trên máy khác",
    description: [
      "Bạn bấm link Mê Sale trên điện thoại A nhưng lại cầm điện thoại B (hoặc máy tính) để đặt hàng — hệ thống sàn không thể nối dữ liệu giữa 2 thiết bị."
    ],
    solution: "Bấm link và đặt hàng phải thực hiện trên CÙNG một thiết bị."
  },
  {
    id: 8,
    title: "Sản phẩm không được sàn tài trợ hoa hồng",
    description: [
      "Một số sản phẩm hoặc ngành hàng không được sàn chi hoa hồng affiliate, nên đơn không có tiền hoàn dù bạn làm đúng mọi bước.",
      "Shopee thường gồm: chăm sóc thú cưng, thời trang trẻ em, sữa cho trẻ dưới 2 tuổi và sản phẩm liên quan, e-voucher, dịch vụ, SIM thẻ..."
    ],
    solution: "Dán link vào Mê Sale trước khi mua — ô ước tính hiển thị tiền hoàn khoảng 0đ là dấu hiệu sản phẩm có thể không được tài trợ."
  },
  {
    id: 9,
    title: "Lỗi ghi nhận khách quan từ phía sàn",
    description: [
      'Dù bạn làm đúng toàn bộ các bước, vẫn có một tỉ lệ nhỏ đơn bị hệ thống Shopee/TikTok "bỏ sót" do lỗi đồng bộ dữ liệu. Đây là sự cố kỹ thuật từ phía sàn mà Mê Sale chưa thể can thiệp hoàn toàn.'
    ]
  }
];

const SHOPPING_TIPS: CashbackTip[] = [
  {
    id: 1,
    title: "Kiểm tra đúng gian hàng trước khi mua",
    description: ["Ưu tiên gian hàng chính hãng hoặc gian hàng có lịch sử đánh giá rõ ràng, mô tả sản phẩm đầy đủ và chính sách đổi trả minh bạch."],
    solution: "Đối chiếu tên shop, số lượt bán, đánh giá gần đây và thông tin bảo hành trước khi đặt hàng."
  },
  {
    id: 2,
    title: "Đọc kỹ điều kiện mã giảm giá",
    description: ["Mỗi mã có thể giới hạn ngành hàng, giá trị đơn tối thiểu, phương thức thanh toán, khung giờ hoặc số lượt sử dụng."],
    solution: "Mở phần điều kiện của mã và kiểm tra sản phẩm trong giỏ có đáp ứng đầy đủ hay không."
  },
  {
    id: 3,
    title: "So sánh tổng chi phí thay vì chỉ nhìn giá",
    description: ["Giá niêm yết thấp chưa chắc là lựa chọn tiết kiệm nhất nếu phí vận chuyển, phụ phí hoặc thời gian giao hàng cao hơn."],
    solution: "So sánh giá sau mã, phí vận chuyển và thời gian nhận hàng giữa các gian hàng."
  },
  {
    id: 4,
    title: "Không chia sẻ mã OTP hoặc mật khẩu",
    description: ["Sàn thương mại điện tử và Mê Sale không yêu cầu bạn gửi mật khẩu hoặc mã OTP qua tin nhắn để nhận ưu đãi."],
    solution: "Chỉ đăng nhập trên ứng dụng hoặc website chính thức và liên hệ hỗ trợ nếu gặp yêu cầu đáng ngờ."
  },
  {
    id: 5,
    title: "Kiểm tra lại giỏ hàng trước khi thanh toán",
    description: ["Số lượng, phân loại, địa chỉ nhận hàng và mã giảm giá có thể thay đổi trong quá trình mua sắm."],
    solution: "Xác nhận lại toàn bộ thông tin đơn hàng trên sàn trước khi bấm đặt hàng."
  }
];

function goBack() {
  if (router.canGoBack()) router.back();
  else router.replace("/(tabs)/home");
}

function ExamplePanel({ example, scheme }: { example: TipExample; scheme: Theme["scheme"] }) {
  const dark = scheme === "dark";

  return (
    <View style={[styles.examplePanel, { backgroundColor: dark ? "#3b1d24" : "#fff1f2" }]}>
      <Text style={[styles.exampleTitle, { color: dark ? "#fda4af" : "#dc2626" }]}>😢 Ví dụ: vì sao mất tiền hoàn?</Text>
      {example.steps.map((step, index) => (
        <View key={step} style={styles.exampleStep}>
          <View style={[styles.exampleNumber, { backgroundColor: dark ? "#7f1d2d" : "#fecdd3" }]}>
            <Text style={[styles.exampleNumberText, { color: dark ? "#fecdd3" : "#dc2626" }]}>{index + 1}</Text>
          </View>
          <Text style={[styles.exampleCopy, { color: dark ? "#ffe4e6" : "#1e293b" }]}>{step}</Text>
        </View>
      ))}
      <View style={styles.resultRow}>
        <XCircle color={dark ? "#fb7185" : "#dc2626"} fill={dark ? "#7f1d2d" : "#fee2e2"} size={22} />
        <Text style={[styles.resultText, { color: dark ? "#fda4af" : "#dc2626" }]}>→ Kết quả: {example.result}</Text>
      </View>
    </View>
  );
}

function SolutionPanel({ scheme, text }: { scheme: Theme["scheme"]; text: string }) {
  const dark = scheme === "dark";

  return (
    <View style={[styles.solutionPanel, { backgroundColor: dark ? "#0c3328" : "#effcf3" }]}>
      <ShieldCheck color={dark ? "#4ade80" : "#16a34a"} fill={dark ? "#14532d" : "#dcfce7"} size={23} />
      <Text style={[styles.solutionCopy, { color: dark ? "#dcfce7" : "#1e293b" }]}>
        <Text style={[styles.solutionLabel, { color: dark ? "#4ade80" : "#16a34a" }]}>Cách khắc phục: </Text>
        {text}
      </Text>
    </View>
  );
}

function TipCard({ expanded, onToggle, tip }: { expanded: boolean; onToggle: () => void; tip: CashbackTip }) {
  const { colors, scheme } = useTheme();
  const dark = scheme === "dark";

  return (
    <View style={[styles.tipCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
      <Pressable
        accessibilityLabel={`${expanded ? "Thu gọn" : "Mở"} lưu ý ${tip.id}: ${tip.title}`}
        accessibilityRole="button"
        accessibilityState={{ expanded }}
        hitSlop={4}
        onPress={onToggle}
        style={({ pressed }) => [styles.tipHeader, pressed && styles.pressed]}
      >
        <View style={[styles.tipNumber, { backgroundColor: dark ? "#4a351d" : "#fff5df" }]}>
          <Text style={[styles.tipNumberText, { color: dark ? "#fbbf24" : "#f59e0b" }]}>{tip.id}</Text>
        </View>
        <Text style={[styles.tipTitle, { color: colors.text }]}>{tip.title}</Text>
        <ChevronDown
          color={colors.mutedText}
          size={24}
          style={expanded ? styles.chevronExpanded : undefined}
        />
      </Pressable>

      {expanded ? (
        <View style={styles.tipBody}>
          {tip.description.map((paragraph) => (
            <Text key={paragraph} style={[styles.description, { color: colors.text }]}>{paragraph}</Text>
          ))}
          {tip.example ? <ExamplePanel example={tip.example} scheme={scheme} /> : null}
          {tip.solution ? <SolutionPanel scheme={scheme} text={tip.solution} /> : null}
        </View>
      ) : null}
    </View>
  );
}

export default function TipsScreen() {
  const insets = useSafeAreaInsets();
  const { colors, scheme } = useTheme();
  const payoutFeaturesEnabled = useIosPayoutFeaturesEnabled();
  const tips = payoutFeaturesEnabled ? CASHBACK_TIPS : SHOPPING_TIPS;
  const [expandedIds, setExpandedIds] = useState<Set<number>>(() => new Set([1]));

  function toggleTip(id: number) {
    setExpandedIds((current) => {
      const next = new Set(current);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  }

  return (
    <View style={[styles.screen, { backgroundColor: scheme === "dark" ? "#08111f" : "#f2f6fc" }]}>
      <StatusBar style={scheme === "dark" ? "light" : "dark"} />
      <View style={[styles.screenHeader, { paddingTop: insets.top, borderBottomColor: colors.border }]}>
        <Pressable accessibilityLabel="Quay lại Trang chủ" accessibilityRole="button" hitSlop={8} onPress={goBack} style={({ pressed }) => [styles.backButton, pressed && styles.pressed]}>
          <ChevronLeft color={colors.text} size={30} strokeWidth={2.5} />
        </Pressable>
        <Text accessibilityRole="header" style={[styles.screenTitle, { color: colors.text }]}>Tips & Trick</Text>
        <View accessibilityElementsHidden importantForAccessibility="no-hide-descendants" style={styles.headerSpacer} />
      </View>

      <FlatList
        contentContainerStyle={{ paddingBottom: insets.bottom + 28, paddingHorizontal: 14, paddingTop: 14 }}
        data={tips}
        extraData={expandedIds}
        keyExtractor={(item) => String(item.id)}
        ListFooterComponent={(
          <View style={[styles.footerCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <Text style={[styles.footerTitle, { color: colors.text }]}>📌 Lưu ý từ Mê Sale</Text>
            <Text style={[styles.footerCopy, { color: colors.mutedText }]}>{payoutFeaturesEnabled
              ? "Việc ghi nhận đơn hàng phụ thuộc hoàn toàn vào hệ thống của sàn (Shopee, TikTok Shop). Mê Sale cập nhật ví sau khi nhận và xử lý dữ liệu từ sàn — bạn không cần bấm xác nhận thủ công trong app, nên Mê Sale không thể “thêm” đơn mà sàn không ghi nhận."
              : "Giá, mã giảm giá và điều kiện mua hàng do từng sàn và gian hàng cập nhật. Hãy kiểm tra thông tin cuối cùng trên sàn trước khi đặt hàng."}</Text>
          </View>
        )}
        ListHeaderComponent={(
          <View style={styles.introStack}>
            <LinearGradient colors={["#37a7f6", "#1976df"]} end={{ x: 1, y: 1 }} start={{ x: 0, y: 0 }} style={styles.hero}>
              <AlertTriangle color="#854d0e" fill="#facc15" size={46} strokeWidth={2.3} />
              <Text style={styles.heroTitle}>{payoutFeaturesEnabled ? "9 trường hợp đơn KHÔNG được ghi nhận hoàn tiền" : "5 mẹo mua sắm trực tuyến an toàn"}</Text>
              <Text style={styles.heroSubtitle}>Áp dụng cho tất cả các sàn: Shopee · TikTok Shop</Text>
            </LinearGradient>
            <Text style={[styles.introCopy, { color: colors.mutedText }]}>{payoutFeaturesEnabled
              ? "Đa số đơn “mất hoàn tiền” rơi vào đúng 9 tình huống dưới đây — bấm từng mục để xem chi tiết và cách tránh:"
              : "Tham khảo các lưu ý dưới đây trước khi mở sàn và hoàn tất đơn hàng:"}</Text>
          </View>
        )}
        renderItem={({ item }) => (
          <TipCard expanded={expandedIds.has(item.id)} onToggle={() => toggleTip(item.id)} tip={item} />
        )}
        showsVerticalScrollIndicator={false}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1 },
  screenHeader: { alignItems: "center", borderBottomWidth: StyleSheet.hairlineWidth, flexDirection: "row", minHeight: 56, paddingHorizontal: 8 },
  backButton: { alignItems: "center", height: 48, justifyContent: "center", width: 44 },
  screenTitle: { flex: 1, fontSize: 24, fontWeight: "900", letterSpacing: -0.6 },
  headerSpacer: { height: 44, width: 44 },
  introStack: { gap: 14, marginBottom: 14 },
  hero: { alignItems: "center", borderRadius: 20, gap: 9, overflow: "hidden", paddingHorizontal: 20, paddingVertical: 25 },
  heroTitle: { color: "#ffffff", fontSize: 22, fontWeight: "900", lineHeight: 28, textAlign: "center" },
  heroSubtitle: { color: "#eaf5ff", fontSize: 15, lineHeight: 21, textAlign: "center" },
  introCopy: { fontSize: 15, lineHeight: 23 },
  tipCard: { borderRadius: 18, borderWidth: 1, marginBottom: 12, overflow: "hidden" },
  tipHeader: { alignItems: "center", flexDirection: "row", gap: 11, minHeight: 78, paddingHorizontal: 14, paddingVertical: 12 },
  tipNumber: { alignItems: "center", borderRadius: 999, height: 38, justifyContent: "center", width: 38 },
  tipNumberText: { fontSize: 17, fontWeight: "900" },
  tipTitle: { flex: 1, fontSize: 17, fontWeight: "900", lineHeight: 23 },
  chevronExpanded: { transform: [{ rotate: "180deg" }] },
  tipBody: { gap: 14, paddingBottom: 14, paddingHorizontal: 14 },
  description: { fontSize: 15, lineHeight: 24 },
  examplePanel: { borderRadius: 15, gap: 11, padding: 14 },
  exampleTitle: { fontSize: 16, fontWeight: "900", lineHeight: 22 },
  exampleStep: { alignItems: "flex-start", flexDirection: "row", gap: 10 },
  exampleNumber: { alignItems: "center", borderRadius: 999, height: 23, justifyContent: "center", marginTop: 1, width: 23 },
  exampleNumberText: { fontSize: 12, fontWeight: "900" },
  exampleCopy: { flex: 1, fontSize: 14, lineHeight: 22 },
  resultRow: { alignItems: "flex-start", flexDirection: "row", gap: 9, paddingTop: 2 },
  resultText: { flex: 1, fontSize: 14, fontWeight: "800", lineHeight: 22 },
  solutionPanel: { alignItems: "flex-start", borderRadius: 15, flexDirection: "row", gap: 10, padding: 14 },
  solutionCopy: { flex: 1, fontSize: 14, lineHeight: 22 },
  solutionLabel: { fontWeight: "900" },
  footerCard: { borderRadius: 18, borderWidth: 1, gap: 8, marginTop: 2, padding: 16 },
  footerTitle: { fontSize: 18, fontWeight: "900" },
  footerCopy: { fontSize: 15, lineHeight: 24 },
  pressed: { opacity: 0.68 }
});
