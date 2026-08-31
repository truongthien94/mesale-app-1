import { useState } from "react";
import { LinearGradient } from "expo-linear-gradient";
import { router } from "expo-router";
import { StatusBar } from "expo-status-bar";
import {
  AlertCircle,
  Banknote,
  Box,
  CalendarCheck,
  Check,
  ChevronDown,
  ChevronLeft,
  CircleHelp,
  Gift,
  Info,
  Lightbulb,
  Rocket,
  Tag
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
import { useAppConfig } from "@/features/wallet/api";
import { formatAccountMoney } from "@/features/home/format";
import { useTheme } from "@/theme/ThemeProvider";

type GuideChapter =
  | "recording"
  | "cashback"
  | "orders"
  | "coupons"
  | "checkin"
  | "referrals"
  | "withdraw"
  | "tips";

type Faq = {
  answer: string;
  payoutOnly?: boolean;
  question: string;
};

const GUIDE_CHAPTERS: GuideChapter[] = [
  "recording",
  "cashback",
  "orders",
  "coupons",
  "checkin",
  "referrals",
  "withdraw",
  "tips"
];

const FAQS: Faq[] = [
  {
    question: "Tôi có cần liên kết tài khoản Shopee/TikTok không?",
    answer: "Không. Bạn chỉ cần dán đúng link sản phẩm vào Mê Sale, bấm Mua ngay và hoàn tất đơn trên cùng thiết bị."
  },
  {
    question: "Bao lâu thì đơn xuất hiện trên app?",
    answer: "TikTok Shop thường khoảng 1 giờ, Shopee thường khoảng 1 ngày. Dữ liệu từ sàn đôi khi có thể đến chậm hơn."
  },
  {
    question: "Vì sao số thực nhận khác số ước tính lúc dán link?",
    answer: "Số lúc dán link là tạm tính. Số cuối cùng phụ thuộc giá trị sau voucher, sản phẩm đủ điều kiện, hoa hồng sàn duyệt, phí và thuế hiện hành."
  },
  {
    question: "Vì sao đơn bị từ chối?",
    answer: "Đơn có thể bị hủy, đổi trả, không đủ điều kiện affiliate hoặc bị ghi nhận cho một link khác. Mở Tips & Trick để xem các trường hợp phổ biến."
  },
  {
    question: "Tôi đổi/trả hàng thì hoàn tiền ra sao?",
    answer: "Sàn sẽ đối soát lại đơn. Phần hoàn tiền tương ứng có thể bị giảm hoặc từ chối theo kết quả cuối cùng của sàn."
  },
  {
    question: "Có giới hạn hoàn tiền tối đa không?",
    answer: "Giới hạn phụ thuộc chính sách từng sàn, chiến dịch và sản phẩm. Chi tiết tính toán trong app là căn cứ hiển thị cho từng link."
  },
  {
    question: "Đặt nhiều đơn cùng lúc được không?",
    answer: "Được, nhưng mỗi đơn nên bắt đầu bằng một lần tạo link mới: bấm Mua ngay, đặt một đơn, rồi quay lại Mê Sale để tạo link cho đơn tiếp theo."
  },
  {
    question: "Tiền hoàn có rút được ngay không?",
    answer: "Chỉ số dư đã được sàn duyệt mới có thể rút. Tiền ở trạng thái Chờ duyệt chưa phải số dư khả dụng.",
    payoutOnly: true
  },
  {
    question: "Đăng ký rồi mà chưa thấy chỗ nhập mã giới thiệu?",
    answer: "Tài khoản mới đủ điều kiện sẽ thấy ô nhập mã tại tab Tài khoản trong 3 ngày đầu. Thời hạn được máy chủ Mê Sale xác nhận.",
    payoutOnly: true
  }
];

function goBack() {
  if (router.canGoBack()) router.back();
  else router.replace("/(tabs)/account");
}

function GuideCard({ children, warning = false }: { children: React.ReactNode; warning?: boolean }) {
  const { colors, scheme } = useTheme();
  return (
    <View
      style={[
        styles.card,
        {
          backgroundColor: warning ? (scheme === "dark" ? "#332b12" : "#fffbed") : colors.surface,
          borderColor: warning ? (scheme === "dark" ? "#6b5815" : "#f7dc72") : colors.border
        }
      ]}
    >
      {children}
    </View>
  );
}

function CardTitle({ icon, title }: { icon: React.ReactNode; title: string }) {
  const { colors } = useTheme();
  return (
    <View style={styles.cardTitleRow}>
      {icon}
      <Text accessibilityRole="header" style={[styles.cardTitle, { color: colors.text }]}>{title}</Text>
    </View>
  );
}

function BodyText({ children, muted = false }: { children: React.ReactNode; muted?: boolean }) {
  const { colors } = useTheme();
  return <Text style={[styles.body, { color: muted ? colors.mutedText : colors.text }]}>{children}</Text>;
}

function SectionLabel({ children }: { children: React.ReactNode }) {
  const { colors } = useTheme();
  return <Text style={[styles.sectionLabel, { color: colors.mutedText }]}>{children}</Text>;
}

function NumberedStep({ children, number }: { children: React.ReactNode; number: number }) {
  const { colors } = useTheme();
  return (
    <View style={styles.listRow}>
      <View style={styles.numberCircle}><Text style={styles.numberText}>{number}</Text></View>
      <Text style={[styles.listCopy, { color: colors.text }]}>{children}</Text>
    </View>
  );
}

function CheckRow({ children, info = false }: { children: React.ReactNode; info?: boolean }) {
  const { colors } = useTheme();
  return (
    <View style={styles.listRow}>
      {info
        ? <Info color="#2f9af5" fill="#2f9af5" size={20} />
        : <View style={styles.checkCircle}><Check color="#ffffff" size={14} strokeWidth={3} /></View>}
      <Text style={[styles.listCopy, { color: colors.text }]}>{children}</Text>
    </View>
  );
}

function StatusRow({ color, label, text }: { color: string; label: string; text: string }) {
  const { colors } = useTheme();
  return (
    <View style={styles.statusRow}>
      <View style={[styles.statusDot, { backgroundColor: color }]} />
      <Text style={[styles.statusLabel, { color: colors.text }]}>{label}</Text>
      <Text style={[styles.statusCopy, { color: colors.mutedText }]}>{text}</Text>
    </View>
  );
}

function RecordingCard() {
  return (
    <GuideCard warning>
      <CardTitle icon={<AlertCircle color="#f59e0b" fill="#f59e0b" size={22} />} title="Để đơn được ghi nhận hoàn tiền" />
      <BodyText muted>Đa số đơn bị từ chối do bỏ qua một trong các điều dưới đây:</BodyText>
      <View style={styles.cardList}>
        <CheckRow>Dán link vào Mê Sale và bấm <Text style={styles.strong}>Mua ngay</Text> để tạo đường ghi nhận.</CheckRow>
        <CheckRow>Ưu tiên đặt đúng sản phẩm đã dán link; sản phẩm khác có thể không được sàn ghi nhận.</CheckRow>
        <CheckRow>Sau khi bấm Mua ngay, <Text style={styles.strong}>đừng mở link Shopee/TikTok ở nơi khác</Text> trước khi đặt.</CheckRow>
        <CheckRow>Mua nhiều đơn: <Text style={styles.strong}>tạo link → đặt 1 đơn → quay lại Mê Sale tạo link mới</Text>.</CheckRow>
        <CheckRow info>Không nên thêm sản phẩm vào giỏ từ link quảng cáo trước khi bắt đầu qua Mê Sale.</CheckRow>
      </View>
      <Pressable
        accessibilityLabel="Xem 9 trường hợp đơn không được ghi nhận"
        accessibilityRole="button"
        onPress={() => router.push("/(tabs)/home/tips")}
        style={({ pressed }) => [styles.inlineLink, pressed && styles.pressed]}
      >
        <Text style={styles.inlineLinkText}>Xem đủ 9 trường hợp đơn KHÔNG được ghi nhận</Text>
        <ChevronLeft color="#2f9af5" size={20} style={styles.linkArrow} />
      </Pressable>
    </GuideCard>
  );
}

function CashbackCard() {
  const { colors, scheme } = useTheme();
  return (
    <GuideCard>
      <CardTitle icon={<Banknote color="#16a34a" size={22} />} title="Quyền lợi hoàn tiền được tính thế nào?" />
      <BodyText>Khi mua qua link Mê Sale, quyền lợi hoàn tiền được máy chủ tính từ dữ liệu sàn, sau khi áp dụng tỷ lệ thành viên, phí và thuế theo cấu hình hiện hành.</BodyText>
      <SectionLabel>CHI TIẾT TÍNH TOÁN</SectionLabel>
      <View style={[styles.calculationBox, { backgroundColor: scheme === "dark" ? "#10243b" : "#edf6ff", borderColor: colors.border }]}>
        <View style={styles.calculationRow}><Text style={[styles.calculationLabel, { color: colors.text }]}>Hoa hồng sàn</Text><Text style={[styles.calculationValue, { color: colors.text }]}>Theo dữ liệu sàn</Text></View>
        <View style={styles.calculationRow}><Text style={[styles.calculationLabel, { color: colors.text }]}>Phí và thuế</Text><Text style={[styles.calculationValue, { color: colors.mutedText }]}>Nếu có</Text></View>
        <View style={[styles.calculationRow, styles.calculationTotal, { borderTopColor: colors.border }]}><Text style={[styles.calculationLabel, styles.strong, { color: colors.text }]}>Quyền lợi hoàn tiền</Text><Text style={styles.cashbackValue}>Hiển thị trong app</Text></View>
      </View>
      <BodyText muted>Số lúc dán link là ước tính. Số thực tế được chốt khi sàn duyệt đơn và có thể thay đổi do voucher, giá giảm hoặc điều kiện sản phẩm.</BodyText>
    </GuideCard>
  );
}

function OrdersCard() {
  return (
    <GuideCard>
      <CardTitle icon={<Box color="#a16207" size={22} />} title="Theo dõi đơn hàng" />
      <BodyText>Mở tab <Text style={styles.strong}>Đơn hàng</Text> để xem trạng thái và quyền lợi hoàn tiền của từng đơn.</BodyText>
      <SectionLabel>KHI NÀO ĐƠN HIỆN TRÊN APP?</SectionLabel>
      <BodyText><Text style={styles.strong}>TikTok sau khoảng 1 giờ · Shopee sau khoảng 1 ngày</Text> kể từ lúc đặt. Dữ liệu sàn có thể đến chậm hơn.</BodyText>
      <SectionLabel>TRẠNG THÁI ĐƠN</SectionLabel>
      <View style={styles.cardList}>
        <StatusRow color="#f59e0b" label="Chờ duyệt" text="Sàn đối soát sau khi giao thành công." />
        <StatusRow color="#16a34a" label="Đã duyệt" text="Quyền lợi hoàn tiền của đơn đã được xác nhận." />
        <StatusRow color="#ef4444" label="Từ chối" text="Đơn hủy, đổi/trả hoặc không đủ điều kiện." />
      </View>
    </GuideCard>
  );
}

function CouponsCard() {
  return (
    <GuideCard>
      <CardTitle icon={<Tag color="#ef4444" size={22} />} title="Săn mã giảm giá" />
      <BodyText>Mục <Text style={styles.strong}>Săn mã</Text> tại Trang chủ cập nhật mã giảm giá từ các sàn.</BodyText>
      <View style={styles.cardList}>
        <CheckRow>Mã “Toàn sàn” dùng cho nhiều shop theo điều kiện; mã có tên shop chỉ dùng tại shop đó.</CheckRow>
        <CheckRow>Chạm ô mã để sao chép, sau đó mua qua link Mê Sale để vừa dùng mã vừa theo dõi hoàn tiền.</CheckRow>
        <CheckRow info>Luôn đọc điều kiện, thời hạn và sản phẩm áp dụng trước khi đặt.</CheckRow>
      </View>
    </GuideCard>
  );
}

function CheckinCard() {
  return (
    <GuideCard>
      <CardTitle icon={<CalendarCheck color="#ef4444" size={22} />} title="Điểm danh mỗi ngày" />
      <BodyText>Tab <Text style={styles.strong}>Điểm danh</Text> hiển thị phần thưởng hiện hành do máy chủ Mê Sale xác nhận.</BodyText>
      <View style={styles.cardList}>
        <CheckRow>Mỗi ngày một lần, tính theo ngày Việt Nam.</CheckRow>
        <CheckRow>Tiền thưởng được cộng theo kết quả trả về từ máy chủ, không phải số cố định trong app.</CheckRow>
      </View>
    </GuideCard>
  );
}

function ReferralsCard() {
  return (
    <GuideCard>
      <CardTitle icon={<Gift color="#f97316" size={22} />} title="Giới thiệu bạn bè" />
      <BodyText>Chia sẻ mã giới thiệu trong tab <Text style={styles.strong}>Giới thiệu</Text>. Hoa hồng và điều kiện hiện hành luôn được hiển thị tại màn này.</BodyText>
      <SectionLabel>CÁCH MỜI</SectionLabel>
      <View style={styles.cardList}>
        <NumberedStep number={1}>Vào tab <Text style={styles.strong}>Giới thiệu</Text> để lấy mã của bạn.</NumberedStep>
        <NumberedStep number={2}>Chia sẻ mã qua Zalo, Facebook, Messenger hoặc kênh bạn chọn.</NumberedStep>
        <NumberedStep number={3}>Bạn bè đăng ký bằng mã đó; nếu quên, tài khoản mới đủ điều kiện có thể nhập tại tab <Text style={styles.strong}>Tài khoản trong 3 ngày đầu</Text>.</NumberedStep>
      </View>
      <BodyText muted>Thời hạn, tỷ lệ và điều kiện được máy chủ xác nhận và có thể thay đổi theo chương trình.</BodyText>
    </GuideCard>
  );
}

function WithdrawCard() {
  const configQuery = useAppConfig();
  const config = configQuery.data?.withdraw;
  const minimum = config ? formatAccountMoney(config.min_amount, "vi") : "xem tại màn Rút tiền";
  const fee = !config
    ? "hiển thị theo cấu hình máy chủ"
    : config.fee_value === 0
      ? "0đ"
      : config.fee_value_unit === "percent"
        ? `${config.fee_value}%`
        : formatAccountMoney(config.fee_value, "vi");

  return (
    <GuideCard>
      <CardTitle icon={<Banknote color="#16a34a" size={22} />} title="Rút tiền" />
      <View style={styles.cardList}>
        <NumberedStep number={1}>Liên kết tài khoản ngân hàng hoặc ví tại tab <Text style={styles.strong}>Tài khoản → Tài chính</Text>. Tên chủ tài khoản cần khớp chính xác.</NumberedStep>
        <NumberedStep number={2}>Vào <Text style={styles.strong}>Rút tiền</Text>, chọn tài khoản nhận và nhập số tiền.</NumberedStep>
        <NumberedStep number={3}>Theo dõi lệnh tại <Text style={styles.strong}>Lịch sử rút tiền</Text>.</NumberedStep>
      </View>
      <SectionLabel>ĐIỀU KIỆN HIỆN HÀNH</SectionLabel>
      <View style={styles.cardList}>
        <CheckRow>Số tiền tối thiểu: <Text style={styles.strong}>{minimum}</Text>.</CheckRow>
        <CheckRow>Chỉ rút phần <Text style={styles.strong}>đã duyệt</Text>; tiền Chờ duyệt chưa thể rút.</CheckRow>
        <CheckRow>Phí rút tiền hiện tại: <Text style={styles.strong}>{fee}</Text>.</CheckRow>
        <CheckRow info>Thời gian xử lý phụ thuộc kiểm duyệt và ngày làm việc; trạng thái chính xác hiển thị trong lịch sử.</CheckRow>
      </View>
    </GuideCard>
  );
}

function TipsCard() {
  const tips = [
    "Luôn tạo link Mê Sale trước khi vào sàn và đặt hàng trên cùng thiết bị.",
    "Mua nhiều sản phẩm cùng shop có thể gom một đơn; khác shop nên tách đơn và tạo link lại.",
    "Không mở link quảng cáo hoặc link bạn bè sau khi đã tạo link Mê Sale.",
    "TikTok thường hiện sau khoảng 1 giờ; Shopee thường hiện sau khoảng 1 ngày."
  ];
  const { colors } = useTheme();
  return (
    <GuideCard>
      <CardTitle icon={<Lightbulb color="#f59e0b" size={22} />} title="Mẹo tối đa hoàn tiền" />
      <View style={styles.cardList}>
        {tips.map((tip) => (
          <View key={tip} style={styles.listRow}>
            <Lightbulb color="#f59e0b" fill="#f59e0b" size={19} />
            <Text style={[styles.listCopy, { color: colors.text }]}>{tip}</Text>
          </View>
        ))}
      </View>
    </GuideCard>
  );
}

function Chapter({ chapter }: { chapter: GuideChapter }) {
  switch (chapter) {
    case "recording": return <RecordingCard />;
    case "cashback": return <CashbackCard />;
    case "orders": return <OrdersCard />;
    case "coupons": return <CouponsCard />;
    case "checkin": return <CheckinCard />;
    case "referrals": return <ReferralsCard />;
    case "withdraw": return <WithdrawCard />;
    case "tips": return <TipsCard />;
  }
}

function FaqCard({ payoutFeaturesEnabled }: { payoutFeaturesEnabled: boolean }) {
  const { colors } = useTheme();
  const [expanded, setExpanded] = useState<Set<number>>(() => new Set());
  const faqs = FAQS.filter((faq) => payoutFeaturesEnabled || !faq.payoutOnly);

  function toggle(index: number) {
    setExpanded((current) => {
      const next = new Set(current);
      if (next.has(index)) next.delete(index);
      else next.add(index);
      return next;
    });
  }

  return (
    <GuideCard>
      <CardTitle icon={<CircleHelp color="#dc2626" size={23} />} title="Câu hỏi thường gặp" />
      <View style={styles.faqList}>
        {faqs.map((faq, index) => {
          const open = expanded.has(index);
          return (
            <View key={faq.question} style={[styles.faqItem, index > 0 && { borderTopColor: colors.border, borderTopWidth: StyleSheet.hairlineWidth }]}>
              <Pressable
                accessibilityRole="button"
                accessibilityState={{ expanded: open }}
                onPress={() => toggle(index)}
                style={({ pressed }) => [styles.faqButton, pressed && styles.pressed]}
              >
                <Text style={[styles.faqQuestion, { color: colors.text }]}>{faq.question}</Text>
                <ChevronDown color={colors.mutedText} size={20} style={open ? styles.chevronOpen : undefined} />
              </Pressable>
              {open ? <Text style={[styles.faqAnswer, { color: colors.mutedText }]}>{faq.answer}</Text> : null}
            </View>
          );
        })}
      </View>
    </GuideCard>
  );
}

function GuideIntro() {
  const { colors } = useTheme();
  return (
    <View style={styles.introStack}>
      <LinearGradient colors={["#37a7f6", "#1976df"]} end={{ x: 1, y: 1 }} start={{ x: 0, y: 0 }} style={styles.hero}>
        <Rocket color="#ffffff" fill="#ffffff" size={43} />
        <Text style={styles.heroTitle}>Dán link → Mua ngay → nhận hàng → chờ xác nhận</Text>
        <Text style={styles.heroSubtitle}>Mức hoàn phụ thuộc hoa hồng và điều kiện của từng sàn, sản phẩm.</Text>
      </LinearGradient>
      <GuideCard>
        <CardTitle icon={<Rocket color="#2f9af5" size={22} />} title="Bắt đầu" />
        <View style={styles.cardList}>
          <NumberedStep number={1}>Vào <Text style={styles.strong}>Trang chủ</Text>, dán link sản phẩm Shopee hoặc TikTok Shop.</NumberedStep>
          <NumberedStep number={2}>Chờ Mê Sale tải thông tin, sau đó bấm <Text style={styles.strong}>Mua ngay</Text>.</NumberedStep>
          <NumberedStep number={3}>Đặt đơn như bình thường trong app sàn: chọn phân loại, áp mã và thanh toán.</NumberedStep>
          <NumberedStep number={4}>Nhận hàng thành công, chờ sàn đối soát để xem trạng thái hoàn tiền của đơn.</NumberedStep>
        </View>
        <SectionLabel>LẤY LINK SẢN PHẨM Ở ĐÂU?</SectionLabel>
        <View style={styles.cardList}>
          <CheckRow><Text style={styles.strong}>Shopee:</Text> mở sản phẩm → Chia sẻ → Sao chép liên kết → quay lại Mê Sale và dán.</CheckRow>
          <CheckRow><Text style={styles.strong}>TikTok:</Text> mở trang sản phẩm trong TikTok Shop → Chia sẻ → Sao chép liên kết.</CheckRow>
        </View>
        <Text style={[styles.italic, { color: colors.mutedText }]}>Vừa sao chép link xong, mở Mê Sale và chạm Dán là xong.</Text>
      </GuideCard>
    </View>
  );
}

function ShoppingGuideIntro() {
  const { colors } = useTheme();
  return (
    <View style={styles.introStack}>
      <LinearGradient colors={["#37a7f6", "#1976df"]} end={{ x: 1, y: 1 }} start={{ x: 0, y: 0 }} style={styles.hero}>
        <Rocket color="#ffffff" fill="#ffffff" size={43} />
        <Text style={styles.heroTitle}>Dán liên kết → xem sản phẩm → kiểm tra ưu đãi</Text>
        <Text style={styles.heroSubtitle}>Giá, mã giảm giá và điều kiện mua hàng được xác nhận trực tiếp trên từng sàn.</Text>
      </LinearGradient>
      <GuideCard>
        <CardTitle icon={<Rocket color="#2f9af5" size={22} />} title="Bắt đầu" />
        <View style={styles.cardList}>
          <NumberedStep number={1}>Sao chép liên kết sản phẩm từ Shopee hoặc TikTok Shop.</NumberedStep>
          <NumberedStep number={2}>Dán liên kết tại <Text style={styles.strong}>Trang chủ</Text> để Mê Sale nhận diện sản phẩm.</NumberedStep>
          <NumberedStep number={3}>Chạm <Text style={styles.strong}>Mua ngay</Text> để mở sản phẩm trên sàn.</NumberedStep>
          <NumberedStep number={4}>Kiểm tra phân loại, giá, mã giảm giá và điều kiện giao hàng trước khi đặt.</NumberedStep>
        </View>
        <Text style={[styles.italic, { color: colors.mutedText }]}>Thông tin cuối cùng luôn được hiển thị và xác nhận trên ứng dụng hoặc website của sàn.</Text>
      </GuideCard>
    </View>
  );
}

function ShoppingSafetyCard() {
  return (
    <GuideCard>
      <CardTitle icon={<Info color="#2f9af5" size={22} />} title="Lưu ý mua sắm" />
      <View style={styles.cardList}>
        <CheckRow>Không chia sẻ mật khẩu, mã OTP hoặc thông tin đăng nhập với người khác.</CheckRow>
        <CheckRow>Đọc kỹ điều kiện mã giảm giá, phí vận chuyển và chính sách đổi trả.</CheckRow>
        <CheckRow info>Mê Sale không phải ứng dụng chính thức của Shopee, TikTok Shop hoặc Lazada.</CheckRow>
      </View>
    </GuideCard>
  );
}

export default function UsageGuideScreen() {
  const insets = useSafeAreaInsets();
  const { colors, scheme } = useTheme();
  const payoutFeaturesEnabled = useIosPayoutFeaturesEnabled();
  const guideChapters = payoutFeaturesEnabled ? GUIDE_CHAPTERS : (["coupons"] as GuideChapter[]);

  return (
    <View style={[styles.screen, { backgroundColor: scheme === "dark" ? "#08111f" : "#eef6ff" }]}>
      <StatusBar style={scheme === "dark" ? "light" : "dark"} />
      <View style={[styles.header, { borderBottomColor: colors.border, paddingTop: insets.top }]}>
        <Pressable accessibilityLabel="Quay lại Tài khoản" accessibilityRole="button" hitSlop={8} onPress={goBack} style={({ pressed }) => [styles.backButton, pressed && styles.pressed]}>
          <ChevronLeft color={colors.text} size={29} strokeWidth={2.5} />
        </Pressable>
        <Text accessibilityRole="header" style={[styles.title, { color: colors.text }]}>Hướng dẫn sử dụng</Text>
        <View accessibilityElementsHidden importantForAccessibility="no-hide-descendants" style={styles.headerSpacer} />
      </View>
      <FlatList
        contentContainerStyle={{ paddingBottom: insets.bottom + 28, paddingHorizontal: 14, paddingTop: 14 }}
        data={guideChapters}
        keyExtractor={(item) => item}
        ListFooterComponent={payoutFeaturesEnabled ? <FaqCard payoutFeaturesEnabled /> : <ShoppingSafetyCard />}
        ListHeaderComponent={payoutFeaturesEnabled ? <GuideIntro /> : <ShoppingGuideIntro />}
        renderItem={({ item }) => <Chapter chapter={item} />}
        showsVerticalScrollIndicator={false}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1 },
  header: { alignItems: "center", borderBottomWidth: StyleSheet.hairlineWidth, flexDirection: "row", minHeight: 56, paddingHorizontal: 8 },
  backButton: { alignItems: "center", height: 48, justifyContent: "center", width: 44 },
  title: { flex: 1, fontSize: 22, fontWeight: "900", letterSpacing: -0.5 },
  headerSpacer: { height: 44, width: 44 },
  introStack: { gap: 14 },
  hero: { alignItems: "center", borderRadius: 20, gap: 8, marginBottom: 14, overflow: "hidden", paddingHorizontal: 24, paddingVertical: 28 },
  heroTitle: { color: "#ffffff", fontSize: 21, fontWeight: "900", lineHeight: 27, textAlign: "center" },
  heroSubtitle: { color: "#eaf5ff", fontSize: 14, lineHeight: 20, textAlign: "center" },
  card: { borderRadius: 19, borderWidth: 1, gap: 14, marginBottom: 14, padding: 16 },
  cardTitleRow: { alignItems: "flex-start", flexDirection: "row", gap: 10 },
  cardTitle: { flex: 1, fontSize: 18, fontWeight: "900", lineHeight: 23 },
  body: { fontSize: 15, lineHeight: 23 },
  sectionLabel: { fontSize: 13, fontWeight: "900", letterSpacing: 0.8, marginTop: 2 },
  cardList: { gap: 12 },
  listRow: { alignItems: "flex-start", flexDirection: "row", gap: 10 },
  listCopy: { flex: 1, fontSize: 15, lineHeight: 23 },
  numberCircle: { alignItems: "center", backgroundColor: "#2f9af5", borderRadius: 999, height: 30, justifyContent: "center", marginTop: 1, width: 30 },
  numberText: { color: "#ffffff", fontSize: 14, fontWeight: "900" },
  checkCircle: { alignItems: "center", backgroundColor: "#18ad63", borderRadius: 999, height: 20, justifyContent: "center", marginTop: 2, width: 20 },
  strong: { fontWeight: "900" },
  italic: { fontSize: 14, fontStyle: "italic", lineHeight: 22 },
  inlineLink: { alignItems: "center", flexDirection: "row", justifyContent: "space-between", minHeight: 44 },
  inlineLinkText: { color: "#2f9af5", flex: 1, fontSize: 14, fontWeight: "900", lineHeight: 20 },
  linkArrow: { transform: [{ rotate: "180deg" }] },
  calculationBox: { borderRadius: 14, borderWidth: 1, gap: 9, padding: 13 },
  calculationRow: { alignItems: "center", flexDirection: "row", gap: 10, justifyContent: "space-between" },
  calculationLabel: { flex: 1, fontSize: 14, lineHeight: 20 },
  calculationValue: { fontSize: 14, fontWeight: "800", textAlign: "right" },
  calculationTotal: { borderTopWidth: StyleSheet.hairlineWidth, marginTop: 2, paddingTop: 10 },
  cashbackValue: { color: "#16a34a", fontSize: 14, fontWeight: "900" },
  statusRow: { alignItems: "flex-start", flexDirection: "row", gap: 9 },
  statusDot: { borderRadius: 999, height: 11, marginTop: 6, width: 11 },
  statusLabel: { fontSize: 15, fontWeight: "900", lineHeight: 22, width: 82 },
  statusCopy: { flex: 1, fontSize: 14, lineHeight: 21 },
  faqList: { marginHorizontal: -2 },
  faqItem: { paddingVertical: 2 },
  faqButton: { alignItems: "center", flexDirection: "row", gap: 10, minHeight: 50, paddingVertical: 8 },
  faqQuestion: { flex: 1, fontSize: 15, fontWeight: "900", lineHeight: 21 },
  faqAnswer: { fontSize: 14, lineHeight: 22, paddingBottom: 12, paddingRight: 24 },
  chevronOpen: { transform: [{ rotate: "180deg" }] },
  pressed: { opacity: 0.65 }
});
