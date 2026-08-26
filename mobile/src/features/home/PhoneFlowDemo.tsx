import { useFocusEffect } from "expo-router";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { Animated, AppState, type AppStateStatus, Easing, StyleSheet, Text, View, useWindowDimensions } from "react-native";

// This is a self-running, illustrative animation reproducing the demo phone
// widget embedded in the website's homepage hero block. All data below is
// fake/hardcoded, matching the source demo exactly - it never calls any API.
const PASTE_URL = "https://shopee.vn/product/...";
const PRODUCT_NAME = "Kem sáng da đa tầng ngày/đêm Pond's Bright Miracle mờ thâm sạm";
const PRODUCT_PRICE = "174.000đ";
const COMMISSION = "20.880đ";
const CASHBACK = "+20.880đ";
const SHORT_LINK = "mesale.vn/go/shopee.....";
const VARIANTS = ["Kem ban ngày", "Kem ban đêm"] as const;
const SELECTED_VARIANT = "Kem ban ngày";
const SUBTOTAL = "174.000đ";
const VOUCHER = "-15.000đ";
const GRAND_TOTAL = "159.000đ";
const ORDER_CODE = "SPX-082026-54821";

const FRAME_WIDTH = 326;
const FRAME_HEIGHT = 700;

type Stage =
  | "idle"
  | "pasting"
  | "processing"
  | "product"
  | "success"
  | "floatLeft"
  | "details"
  | "floatRight"
  | "ready"
  | "shopOpen"
  | "sheetOpen"
  | "variantSelected"
  | "checkout"
  | "placingOrder"
  | "orderSuccess"
  | "cashbackShown";

type PlaybackController = {
  cancelled: boolean;
  pendingWaits: Set<() => void>;
};

function wait(ms: number, playback: PlaybackController): Promise<void> {
  return new Promise((resolve, reject) => {
    if (playback.cancelled) {
      reject(new Error("cancelled"));
      return;
    }

    const cancel = () => {
      clearTimeout(timeout);
      playback.pendingWaits.delete(cancel);
      reject(new Error("cancelled"));
    };
    const timeout = setTimeout(() => {
      playback.pendingWaits.delete(cancel);
      if (playback.cancelled) reject(new Error("cancelled"));
      else resolve();
    }, ms);
    playback.pendingWaits.add(cancel);
  });
}

export function shouldAutoplayPhoneFlow(isFocused: boolean, appState: AppStateStatus): boolean {
  return isFocused && appState === "active";
}

function useAppState(): AppStateStatus {
  const [appState, setAppState] = useState<AppStateStatus>(AppState.currentState);

  useEffect(() => {
    const subscription = AppState.addEventListener("change", setAppState);
    return () => subscription.remove();
  }, []);

  return appState;
}

function useRouteIsFocused(): boolean {
  const [isFocused, setIsFocused] = useState(false);

  useFocusEffect(useCallback(() => {
    setIsFocused(true);
    return () => setIsFocused(false);
  }, []));

  return isFocused;
}

function usePhoneFlowAutoplay(shouldPlay: boolean): Stage {
  const [stage, setStage] = useState<Stage>("idle");

  useEffect(() => {
    if (!shouldPlay) {
      setStage("idle");
      return;
    }

    const playback: PlaybackController = { cancelled: false, pendingWaits: new Set() };
    const updateStage = (nextStage: Stage) => {
      if (!playback.cancelled) setStage(nextStage);
    };

    async function playIntro() {
      updateStage("idle");
      await wait(500, playback);
      updateStage("pasting");
      await wait(300, playback);
      updateStage("processing");
      await wait(900, playback);
      updateStage("product");
      await wait(650, playback);
      updateStage("success");
      await wait(450, playback);
      updateStage("floatLeft");
      await wait(550, playback);
      updateStage("details");
      await wait(450, playback);
      updateStage("floatRight");
      await wait(600, playback);
      updateStage("ready");
    }

    async function autoplayShopJourney() {
      updateStage("shopOpen");
      await wait(900, playback);
      updateStage("sheetOpen");
      await wait(650, playback);
      updateStage("variantSelected");
      await wait(550 + 380, playback);
      updateStage("checkout");
      await wait(900, playback);
      updateStage("placingOrder");
      await wait(1100, playback);
      updateStage("orderSuccess");
      await wait(1200, playback);
      updateStage("cashbackShown");
      await wait(2600, playback);
    }

    async function autoplayLoop() {
      try {
        while (!playback.cancelled) {
          await playIntro();
          await wait(1200, playback);
          await autoplayShopJourney();
          updateStage("idle");
          await wait(700, playback);
        }
      } catch {
        // Focus/background cleanup cancels the pending wait and ends this run.
      }
    }

    void autoplayLoop();
    return () => {
      playback.cancelled = true;
      for (const cancelWait of playback.pendingWaits) cancelWait();
      playback.pendingWaits.clear();
    };
  }, [shouldPlay]);

  return stage;
}

function useFadeSlideIn(visible: boolean) {
  const value = useRef(new Animated.Value(0)).current;
  useEffect(() => {
    const animation = Animated.timing(value, {
      toValue: visible ? 1 : 0,
      duration: visible ? 520 : 0,
      easing: Easing.out(Easing.cubic),
      useNativeDriver: true
    });
    animation.start();
    return () => animation.stop();
  }, [visible, value]);
  return {
    opacity: value,
    transform: [{ translateY: value.interpolate({ inputRange: [0, 1], outputRange: [18, 0] }) }]
  };
}

function useFloatingNoticeIn(visible: boolean, direction: "left" | "right", scale: number) {
  const value = useRef(new Animated.Value(0)).current;
  useEffect(() => {
    const animation = Animated.timing(value, {
      toValue: visible ? 1 : 0,
      duration: visible ? 650 : 0,
      easing: Easing.out(Easing.cubic),
      useNativeDriver: true
    });
    animation.start();
    return () => animation.stop();
  }, [value, visible]);

  const offset = (direction === "left" ? -28 : 28) * scale;
  return {
    opacity: value,
    transform: [
      { translateX: value.interpolate({ inputRange: [0, 1], outputRange: [offset, 0] }) },
      { translateY: value.interpolate({ inputRange: [0, 1], outputRange: [10 * scale, 0] }) },
      { scale: value.interpolate({ inputRange: [0, 1], outputRange: [0.94, 1] }) }
    ]
  };
}

function Spinner({ spinning }: { spinning: boolean }) {
  const rotation = useRef(new Animated.Value(0)).current;
  useEffect(() => {
    if (!spinning) return;
    rotation.setValue(0);
    const animation = Animated.loop(
      Animated.timing(rotation, { toValue: 1, duration: 700, easing: Easing.linear, useNativeDriver: true })
    );
    animation.start();
    return () => animation.stop();
  }, [spinning, rotation]);

  if (!spinning) return null;
  const spin = rotation.interpolate({ inputRange: [0, 1], outputRange: ["0deg", "360deg"] });
  return <Animated.View style={[styles.spinner, { transform: [{ rotate: spin }] }]} />;
}

function DemoRow({ label, value, tone = "neutral" }: { label: string; value: string; tone?: "neutral" | "rate" | "total" }) {
  return (
    <View style={[styles.row, tone === "total" && styles.rowTotal]}>
      <Text style={[styles.rowLabel, tone === "total" && styles.rowLabelTotal]}>{label}</Text>
      <Text style={[styles.rowValue, tone === "rate" && styles.rowValueRate, tone === "total" && styles.rowValueTotal]}>{value}</Text>
    </View>
  );
}

function HeroContent({ stage }: { stage: Stage }) {
  const pasted = stage !== "idle" && stage !== "pasting";
  const pastePressing = stage === "pasting";
  const pasteLabel = pasted ? "Đã dán" : pastePressing ? "Đang dán" : "Dán link";
  const processing = stage === "processing";
  const productVisible = ["product", "success", "floatLeft", "details", "floatRight", "ready", "shopOpen", "sheetOpen", "variantSelected", "checkout", "placingOrder", "orderSuccess", "cashbackShown"].includes(stage);
  const successVisible = ["success", "floatLeft", "details", "floatRight", "ready", "shopOpen", "sheetOpen", "variantSelected", "checkout", "placingOrder", "orderSuccess", "cashbackShown"].includes(stage);
  const detailsVisible = ["details", "floatRight", "ready", "shopOpen", "sheetOpen", "variantSelected", "checkout", "placingOrder", "orderSuccess", "cashbackShown"].includes(stage);
  const guideVisible = stage === "ready";

  const productStyle = useFadeSlideIn(productVisible);
  const successStyle = useFadeSlideIn(successVisible);
  const detailsStyle = useFadeSlideIn(detailsVisible);

  return (
    <View style={styles.screen}>
      <Text style={styles.title}>
        Tạo link mua sắm <Text style={{ color: "#1763e8" }}>MeSale.vn</Text>
      </Text>

      <View style={styles.panel}>
        <View style={[styles.urlBox, pastePressing && styles.urlBoxActive, pasted && styles.urlBoxPasted]}>
          <View style={styles.shopeeMark}>
            <Text style={styles.shopeeMarkText}>S</Text>
          </View>
          <Text ellipsizeMode="tail" numberOfLines={1} style={[styles.urlText, !pasted && styles.urlPlaceholder]}>
            {pasted ? PASTE_URL : "Dán link sản phẩm Shopee"}
          </Text>
          <View style={[styles.pasteButton, pastePressing && styles.pasteButtonPressing, pasted && styles.pasteButtonDone]}>
            <Text style={[styles.pasteSymbol, pastePressing && styles.pasteSymbolActive, pasted && styles.pasteSymbolDone]}>▣</Text>
            <Text style={[styles.pasteButtonText, pastePressing && styles.pasteButtonTextActive, pasted && styles.pasteButtonTextDone]}>{pasteLabel}</Text>
          </View>
        </View>
        <View style={[styles.primaryButton, processing && styles.primaryButtonProcessing]}>
          <Spinner spinning={processing} />
          <Text style={styles.primaryButtonText}>{processing ? "Đang xử lý..." : "Tạo link ngay"}</Text>
        </View>
      </View>

      <Animated.View style={[styles.productCard, productStyle]}>
        <View style={styles.bag}>
          <Text style={styles.bagMark}>IMG</Text>
        </View>
        <View style={styles.prodInfo}>
          <Text numberOfLines={1} style={styles.prodName}>{PRODUCT_NAME}</Text>
          <Text style={styles.prodSource}>Sản phẩm Shopee</Text>
          <Text style={styles.price}>{PRODUCT_PRICE}</Text>
        </View>
      </Animated.View>

      <Animated.View style={[styles.successCard, successStyle]}>
        <View style={styles.successHead}>
          <View style={styles.check}><Text style={styles.checkMark}>✓</Text></View>
          <Text style={styles.successHeadText}>Đã tạo link mua sắm</Text>
        </View>
        <Text numberOfLines={1} style={styles.shortLink}>{SHORT_LINK}</Text>
        <View style={styles.actionsRow}>
          <View style={styles.copyButton}><Text style={styles.copyButtonText}>Sao chép</Text></View>
          <View style={styles.buyButton}><Text style={styles.buyButtonText}>Mua ngay</Text></View>
        </View>
      </Animated.View>

      <Animated.View style={[styles.detailsCard, detailsStyle]}>
        <DemoRow label="Hoa hồng sản phẩm" value={COMMISSION} />
        <DemoRow label="Tỷ lệ hoàn" tone="rate" value="12%" />
        <DemoRow label="Ước tính hoàn tiền" tone="total" value={CASHBACK} />
      </Animated.View>

      {guideVisible ? (
        <View style={styles.clickGuide}>
          <Text style={styles.clickGuideText}>Hệ thống tự động chuyển sang Shopee</Text>
        </View>
      ) : null}
    </View>
  );
}

function ShopProductPage() {
  return (
    <View style={styles.appPage}>
      <View style={styles.shopeeHead}>
        <Text style={styles.shopeeTitle}>Shopee</Text>
        <View style={styles.demoPill}><Text style={styles.demoPillText}>MÔ PHỎNG</Text></View>
      </View>
      <View style={styles.shopeeScroll}>
        <View style={styles.shopCard}>
          <Text numberOfLines={2} style={styles.shopName}>
            <Text style={styles.mallTag}> Mall </Text> {PRODUCT_NAME}
          </Text>
          <Text style={styles.shopStats}>4.9 ★★★★★ · 8,5k đánh giá · Đã bán 40k+</Text>
          <Text style={styles.shopPrice}>₫{PRODUCT_PRICE.replace("đ", "")}</Text>
          <View style={styles.cashbackNote}>
            <Text style={styles.cashbackNoteText}>Bạn đang mua qua link MeSale.vn. Đơn hợp lệ sẽ được ghi nhận quyền lợi hoàn tiền.</Text>
          </View>
        </View>
        <View style={styles.shopCard}>
          <DemoRow label="Mã giảm giá của Shop" value="Giảm 15.000đ" />
          <DemoRow label="Vận chuyển" value="Miễn phí" />
          <DemoRow label="An tâm mua sắm" value="Trả hàng miễn phí 15 ngày" />
        </View>
      </View>
      <View style={styles.shopeeBottom}>
        <Text style={styles.shopeeBuyText}>Chọn phân loại & mua</Text>
      </View>
    </View>
  );
}

function VariantSheet({ stage }: { stage: Stage }) {
  const open = ["sheetOpen", "variantSelected"].includes(stage);
  const selected = stage === "variantSelected";
  if (!open) return null;

  return (
    <View style={styles.sheetBackdrop}>
      <View style={styles.variantSheet}>
        <View style={styles.sheetHandle} />
        <Text style={styles.variantLabel}>Phân loại</Text>
        <View style={styles.variantGrid}>
          {VARIANTS.map((variant) => (
            <View
              key={variant}
              style={[styles.variantChip, selected && variant === SELECTED_VARIANT && styles.variantChipSelected]}
            >
              <Text style={[styles.variantChipText, selected && variant === SELECTED_VARIANT && styles.variantChipTextSelected]}>
                {variant}
              </Text>
            </View>
          ))}
        </View>
        <View style={styles.quantityRow}>
          <Text style={styles.quantityLabel}>Số lượng</Text>
          <View style={styles.quantityControl}>
            <Text style={styles.quantityButton}>−</Text>
            <Text style={styles.quantityValue}>1</Text>
            <Text style={styles.quantityButton}>+</Text>
          </View>
        </View>
        <View style={[styles.sheetContinue, selected && styles.sheetContinueEnabled]}>
          <Text style={[styles.sheetContinueText, selected && styles.sheetContinueTextEnabled]}>
            {selected ? "Mua ngay" : "Vui lòng chọn phân loại"}
          </Text>
        </View>
      </View>
    </View>
  );
}

function CheckoutPage({ stage }: { stage: Stage }) {
  const loading = stage === "placingOrder";
  return (
    <View style={styles.appPage}>
      <View style={styles.checkoutHead}>
        <Text style={styles.checkoutHeadText}>Thanh toán</Text>
      </View>
      <View style={styles.checkoutScroll}>
        <View style={styles.shopCard}>
          <Text style={styles.smallLabel}>ĐỊA CHỈ NHẬN HÀNG</Text>
          <Text style={styles.addressStrong}>Nguyễn Minh · 09•• ••• 888</Text>
          <Text style={styles.addressText}>123 Đường Mua Sắm, Phường Trung Tâm, Việt Nam</Text>
        </View>
        <View style={styles.shopCard}>
          <DemoRow label="Tổng tiền hàng" value={SUBTOTAL} />
          <DemoRow label="Voucher" value={VOUCHER} />
          <DemoRow label="Phí vận chuyển" value="0đ" />
          <DemoRow label="Thành tiền" tone="total" value={GRAND_TOTAL} />
          <View style={styles.cashbackRow}>
            <Text style={styles.cashbackRowLabel}>MeSale ước tính hoàn tiền</Text>
            <Text style={styles.cashbackRowValue}>{CASHBACK}</Text>
          </View>
        </View>
        <View style={styles.shopCard}>
          <DemoRow label="Phương thức thanh toán" value="Thanh toán khi nhận hàng" />
        </View>
      </View>
      <View style={styles.checkoutBottom}>
        <View>
          <Text style={styles.checkoutTotalLabel}>Tổng thanh toán</Text>
          <Text style={styles.checkoutTotalValue}>{GRAND_TOTAL}</Text>
        </View>
        <View style={styles.placeOrderButton}>
          <Text style={styles.placeOrderButtonText}>{loading ? "Đang đặt hàng..." : "Đặt hàng"}</Text>
        </View>
      </View>
    </View>
  );
}

function SuccessPage() {
  return (
    <View style={styles.centerPage}>
      <View style={styles.successIcon}><Text style={styles.successIconMark}>✓</Text></View>
      <Text style={styles.centerTitle}>Đặt hàng thành công</Text>
      <Text style={styles.centerText}>Shopee đã tiếp nhận đơn. MeSale.vn sẽ ghi nhận lượt mua và đối soát sau khi đơn hợp lệ.</Text>
      <View style={styles.statusCard}>
        <View style={styles.statusHead}>
          <Text style={styles.statusHeadText}>Đơn Shopee đã tạo</Text>
          <View style={styles.statusPill}><Text style={styles.statusPillText}>Thành công</Text></View>
        </View>
        <Text style={styles.statusCode}>Mã đơn: {ORDER_CODE}</Text>
        <View style={styles.statusMoney}>
          <Text style={styles.statusMoneyLabel}>Ước tính hoàn tiền</Text>
          <Text style={styles.statusMoneyValue}>{CASHBACK}</Text>
        </View>
      </View>
      <View style={styles.primaryAction}><Text style={styles.primaryActionText}>Xem trạng thái đơn hàng</Text></View>
    </View>
  );
}

const cashbackTimeline = [
  { label: "Đã tạo link MeSale", note: "Link hoàn tiền đã được sử dụng", done: true },
  { label: "Đã ghi nhận đơn Shopee", note: `Mã đơn ${ORDER_CODE}`, done: true },
  { label: "Đang đối soát", note: "Chờ Shopee xác nhận đơn hợp lệ", done: false },
  { label: "Xác nhận quyền lợi", note: "Sau khi hoàn tất đối soát", done: false }
];

function CashbackPage() {
  return (
    <View style={styles.appPage}>
      <View style={styles.resultHead}>
        <Text style={styles.resultLogo}>
          <Text style={{ color: "#ff5a00" }}>Mê</Text>
          <Text style={{ color: "#1763e8" }}>Sale.vn</Text>
        </Text>
      </View>
      <View style={styles.resultBody}>
        <Text style={styles.resultTitle}>Đơn hàng đã được ghi nhận</Text>
        <Text style={styles.resultText}>MeSale.vn đã nhận tín hiệu mua hàng từ link mua sắm của bạn.</Text>
        <View style={styles.cashbackBig}>
          <Text style={styles.cashbackBigLabel}>Ước tính hoàn tiền</Text>
          <Text style={styles.cashbackBigValue}>{CASHBACK}</Text>
          <Text style={styles.cashbackBigNote}>Khoản tiền sẽ được xác nhận sau khi Shopee đối soát đơn hàng hợp lệ và hết thời gian đổi trả.</Text>
        </View>
        <View style={styles.timeline}>
          {cashbackTimeline.map((step, index) => (
            <View key={step.label} style={styles.timelineRow}>
              <View style={[styles.timelineDot, !step.done && styles.timelineDotPending]}>
                <Text style={styles.timelineDotText}>{step.done ? "✓" : String(index + 1)}</Text>
              </View>
              <View style={styles.timelineBody}>
                <Text style={styles.timelineLabel}>{step.label}</Text>
                <Text style={styles.timelineNote}>{step.note}</Text>
              </View>
            </View>
          ))}
        </View>
      </View>
    </View>
  );
}

function ShopApp({ stage }: { stage: Stage }) {
  const open = ["shopOpen", "sheetOpen", "variantSelected", "checkout", "placingOrder", "orderSuccess", "cashbackShown"].includes(stage);
  const openStyle = useFadeSlideIn(open);
  if (!open) return null;

  let page: React.ReactNode;
  if (stage === "orderSuccess") page = <SuccessPage />;
  else if (stage === "cashbackShown") page = <CashbackPage />;
  else if (stage === "checkout" || stage === "placingOrder") page = <CheckoutPage stage={stage} />;
  else page = <ShopProductPage />;

  return (
    <Animated.View style={[styles.shopApp, { opacity: openStyle.opacity }]}>
      {page}
      <VariantSheet stage={stage} />
    </Animated.View>
  );
}

export function PhoneFlowDemo() {
  const isFocused = useRouteIsFocused();
  const appState = useAppState();
  const shouldPlay = shouldAutoplayPhoneFlow(isFocused, appState);
  const stage = usePhoneFlowAutoplay(shouldPlay);
  const { width } = useWindowDimensions();

  const scale = useMemo(() => {
    const available = Math.min(width - 32, FRAME_WIDTH);
    return Math.max(0.72, Math.min(1, available / FRAME_WIDTH));
  }, [width]);

  const sceneWidth = Math.min(width - 32, 540 * scale);
  // Keep the notices inside the rendered phone even when the demo scene is
  // wider than the phone frame on tablet/desktop-sized layouts.
  const phoneInset = Math.max(0, (sceneWidth - FRAME_WIDTH * scale) / 2);
  const noticeInset = 8 * scale;
  const floatLeftVisible = ["floatLeft", "details", "floatRight", "ready"].includes(stage);
  const floatRightVisible = ["floatRight", "ready"].includes(stage);
  const noticeLeftStyle = useFloatingNoticeIn(floatLeftVisible, "left", scale);
  const noticeRightStyle = useFloatingNoticeIn(floatRightVisible, "right", scale);

  return (
    <View style={styles.stageWrapper} pointerEvents="none">
      <View
        style={[styles.phoneStage, {
          height: FRAME_HEIGHT * scale,
          width: sceneWidth
        }]}
      >
        <View style={{ height: FRAME_HEIGHT * scale, width: FRAME_WIDTH * scale }}>
          <View
            style={[
              styles.phone,
              {
                height: FRAME_HEIGHT,
                transform: [{ scale }],
                transformOrigin: "top left",
                width: FRAME_WIDTH
              } as never
            ]}
          >
            <View style={styles.screenClip}>
              <View style={styles.island} />
              <View style={styles.header}>
                <Text style={styles.brand}>
                  <Text style={{ color: "#ff5a00" }}>Mê</Text>
                  <Text style={{ color: "#1763e8" }}>Sale</Text>
                  <Text style={{ color: "#27344d", fontSize: 11 }}>.vn</Text>
                </Text>
                <View style={styles.avatar} />
              </View>

              <HeroContent stage={stage} />

              <View style={styles.nav}>
                <Text style={styles.navItem}>Tổng quan</Text>
                <Text style={[styles.navItem, styles.navItemActive]}>Tạo link</Text>
                <Text style={styles.navItem}>Đơn hàng</Text>
              </View>

              <ShopApp stage={stage} />
            </View>
          </View>
        </View>

        <Animated.View
          accessibilityElementsHidden={!floatLeftVisible}
          importantForAccessibility={floatLeftVisible ? "auto" : "no-hide-descendants"}
          style={[
            styles.floatingNotice,
            styles.floatingNoticeLeft,
            {
              borderRadius: 21 * scale,
              gap: 8 * scale,
              left: phoneInset + noticeInset,
              minHeight: 58 * scale,
              paddingHorizontal: 10 * scale,
              paddingVertical: 9 * scale,
              top: 110 * scale,
              width: 190 * scale
            },
            noticeLeftStyle
          ]}
        >
          <View style={[styles.noticeIcon, styles.noticeIconCyan, { height: 34 * scale, width: 34 * scale }]} />
          <View style={styles.noticeBody}>
            <Text numberOfLines={1} style={[styles.noticeStrong, { fontSize: 11 * scale }]}>Tạo link thành công</Text>
            <Text numberOfLines={1} style={[styles.noticeSpan, { fontSize: 8.5 * scale, marginTop: 2 * scale }]}>Sẵn sàng mua sắm</Text>
          </View>
        </Animated.View>

        <Animated.View
          accessibilityElementsHidden={!floatRightVisible}
          importantForAccessibility={floatRightVisible ? "auto" : "no-hide-descendants"}
          style={[
            styles.floatingNotice,
            styles.floatingNoticeRight,
            {
              borderRadius: 21 * scale,
              gap: 8 * scale,
              minHeight: 62 * scale,
              paddingHorizontal: 10 * scale,
              paddingVertical: 9 * scale,
              right: phoneInset + noticeInset,
              top: 355 * scale,
              width: 170 * scale
            },
            noticeRightStyle
          ]}
        >
          <View style={[styles.noticeIcon, styles.noticeIconMint, { height: 34 * scale, width: 34 * scale }]} />
          <View style={styles.noticeBody}>
            <Text numberOfLines={1} style={[styles.noticeSpanLabel, { fontSize: 8.5 * scale, marginBottom: 2 * scale }]}>TIỀN HOÀN</Text>
            <Text numberOfLines={1} style={[styles.noticeStrongGreen, { fontSize: 17 * scale }]}>{CASHBACK}</Text>
          </View>
        </Animated.View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  stageWrapper: { alignItems: "center", width: "100%" },
  phoneStage: { alignItems: "center", position: "relative" },
  phone: {
    backgroundColor: "#14171c",
    borderColor: "#343941",
    borderRadius: 52,
    borderWidth: 2,
    elevation: 10,
    padding: 5,
    shadowColor: "#02050b",
    shadowOffset: { height: 12, width: 0 },
    shadowOpacity: 0.28,
    shadowRadius: 18,
    position: "relative"
  },
  screenClip: {
    backgroundColor: "#f5f7fc",
    borderRadius: 45,
    flex: 1,
    overflow: "hidden",
    position: "relative"
  },
  island: {
    backgroundColor: "#050608",
    borderColor: "#1b1e23",
    borderRadius: 999,
    borderWidth: 1,
    height: 27,
    left: "50%",
    marginLeft: -47,
    position: "absolute",
    top: 10,
    width: 94,
    zIndex: 30
  },
  header: {
    alignItems: "center",
    backgroundColor: "#ffffff",
    borderBottomColor: "#e7ebf2",
    borderBottomWidth: 1,
    flexDirection: "row",
    height: 92,
    justifyContent: "space-between",
    paddingBottom: 8,
    paddingHorizontal: 18,
    paddingTop: 32
  },
  brand: { fontSize: 19, fontWeight: "900" },
  avatar: { backgroundColor: "#f1f5fa", borderRadius: 999, height: 31, width: 31 },
  screen: { backgroundColor: "#f5f7fc", height: 553, paddingBottom: 90, paddingHorizontal: 17, paddingTop: 18, position: "relative" },
  title: { color: "#26334d", fontSize: 13, fontWeight: "800", marginBottom: 12 },
  panel: {
    backgroundColor: "#ffffff",
    borderColor: "rgba(28,42,74,0.08)",
    borderRadius: 24,
    borderWidth: 1,
    padding: 13
  },
  urlBox: {
    alignItems: "center",
    backgroundColor: "#f8fafc",
    borderColor: "#dfe5ef",
    borderRadius: 999,
    borderWidth: 1,
    flexDirection: "row",
    height: 41,
    gap: 8,
    overflow: "hidden",
    paddingLeft: 10,
    paddingRight: 7
  },
  urlBoxActive: {
    backgroundColor: "#ffffff",
    borderColor: "#ff7a58",
    shadowColor: "#ee4d2d",
    shadowOffset: { height: 0, width: 0 },
    shadowOpacity: 0.12,
    shadowRadius: 4
  },
  urlBoxPasted: { backgroundColor: "#f8fffb", borderColor: "#bfe9d8" },
  shopeeMark: {
    alignItems: "center",
    backgroundColor: "#ee4d2d",
    borderRadius: 7,
    flexShrink: 0,
    height: 22,
    justifyContent: "center",
    width: 22
  },
  shopeeMarkText: { color: "#ffffff", fontSize: 10, fontWeight: "900" },
  urlText: { color: "#536078", flex: 1, flexShrink: 1, fontSize: 9, minWidth: 0 },
  urlPlaceholder: { color: "#8794aa" },
  pasteButton: {
    alignItems: "center",
    backgroundColor: "#fff5f2",
    borderColor: "#ffd2c7",
    borderRadius: 999,
    borderWidth: 1,
    flexDirection: "row",
    flexShrink: 0,
    gap: 4,
    height: 29,
    justifyContent: "center",
    minWidth: 67,
    paddingHorizontal: 9
  },
  pasteButtonPressing: {
    backgroundColor: "#ee4d2d",
    borderColor: "#ee4d2d",
    transform: [{ scale: 0.91 }]
  },
  pasteButtonDone: { backgroundColor: "#eafbf3", borderColor: "#bfe9d8" },
  pasteSymbol: { color: "#ee4d2d", fontSize: 8 },
  pasteSymbolActive: { color: "#ffffff" },
  pasteSymbolDone: { color: "#008b60" },
  pasteButtonText: { color: "#ee4d2d", fontSize: 8, fontWeight: "800" },
  pasteButtonTextActive: { color: "#ffffff" },
  pasteButtonTextDone: { color: "#008b60" },
  primaryButton: {
    alignItems: "center",
    backgroundColor: "#ff6a00",
    borderRadius: 999,
    flexDirection: "row",
    height: 38,
    justifyContent: "center",
    marginTop: 10
  },
  primaryButtonProcessing: { backgroundColor: "#ff8a2a" },
  primaryButtonText: { color: "#ffffff", fontSize: 11, fontWeight: "800" },
  spinner: {
    borderColor: "rgba(255,255,255,0.45)",
    borderRadius: 999,
    borderTopColor: "#ffffff",
    borderWidth: 2,
    height: 11,
    marginRight: 6,
    width: 11
  },
  productCard: {
    alignItems: "center",
    backgroundColor: "#ffffff",
    borderColor: "rgba(28,42,74,0.08)",
    borderRadius: 24,
    borderWidth: 1,
    flexDirection: "row",
    gap: 11,
    marginTop: 12,
    padding: 13
  },
  bag: { alignItems: "center", backgroundColor: "#f2f3f5", borderRadius: 14, height: 46, justifyContent: "center", width: 46 },
  bagMark: { color: "#bec7d5", fontSize: 8, fontWeight: "900" },
  prodInfo: { flex: 1 },
  prodName: { color: "#34415c", fontSize: 10, lineHeight: 13 },
  prodSource: { color: "#8994a8", fontSize: 9, marginTop: 1 },
  price: { color: "#ff5b4a", fontSize: 12, fontWeight: "800", marginTop: 4 },
  successCard: {
    backgroundColor: "#e9fbf3",
    borderColor: "#c9f0df",
    borderRadius: 22,
    borderWidth: 1,
    marginTop: 12,
    padding: 12
  },
  successHead: { alignItems: "center", flexDirection: "row", gap: 6 },
  check: { alignItems: "center", borderColor: "#00a86b", borderRadius: 999, borderWidth: 1.5, height: 15, justifyContent: "center", width: 15 },
  checkMark: { color: "#00a86b", fontSize: 8 },
  successHeadText: { color: "#008b60", fontSize: 9, fontWeight: "800" },
  shortLink: { backgroundColor: "#ffffff", borderColor: "#d7ece4", borderRadius: 999, borderWidth: 1, color: "#526179", fontSize: 9, height: 34, marginTop: 8, paddingHorizontal: 11, textAlignVertical: "center" },
  actionsRow: { flexDirection: "row", gap: 7, marginTop: 8 },
  copyButton: { alignItems: "center", backgroundColor: "#ffffff", borderColor: "#d7dfe9", borderRadius: 999, borderWidth: 1, flex: 1, height: 31, justifyContent: "center" },
  copyButtonText: { color: "#68758c", fontSize: 9, fontWeight: "800" },
  buyButton: { alignItems: "center", backgroundColor: "#00a86b", borderRadius: 999, flex: 1, height: 31, justifyContent: "center" },
  buyButtonText: { color: "#ffffff", fontSize: 9, fontWeight: "800" },
  detailsCard: { backgroundColor: "#ffffff", borderColor: "rgba(28,42,74,0.08)", borderRadius: 24, borderWidth: 1, marginTop: 11, padding: 13 },
  row: { borderBottomColor: "#eef1f5", borderBottomWidth: 1, flexDirection: "row", justifyContent: "space-between", paddingVertical: 6 },
  rowTotal: { borderBottomWidth: 0, marginTop: 3 },
  rowLabel: { color: "#7e8aa0", fontSize: 9 },
  rowLabelTotal: { color: "#283650", fontWeight: "800" },
  rowValue: { color: "#536078", fontSize: 9, fontWeight: "700" },
  rowValueRate: { backgroundColor: "#eaf0ff", borderRadius: 5, color: "#2357ff", paddingHorizontal: 5, paddingVertical: 2 },
  rowValueTotal: { color: "#00a86b", fontSize: 13, fontWeight: "800" },
  floatingNotice: {
    alignItems: "center",
    backgroundColor: "rgba(255,255,255,0.96)",
    borderColor: "rgba(255,255,255,0.96)",
    borderWidth: 1,
    elevation: 8,
    flexDirection: "row",
    position: "absolute",
    shadowColor: "#23325f",
    shadowOffset: { height: 18, width: 0 },
    shadowOpacity: 0.1,
    shadowRadius: 22,
    zIndex: 50
  },
  floatingNoticeLeft: { left: 0 },
  floatingNoticeRight: { right: 0 },
  noticeIcon: { borderRadius: 999, height: 34, width: 34 },
  noticeIconCyan: { backgroundColor: "#d3f9ff" },
  noticeIconMint: { backgroundColor: "#d4fae9" },
  noticeBody: { flex: 1 },
  noticeStrong: { color: "#34415b", fontWeight: "800" },
  noticeSpan: { color: "#8995aa" },
  noticeSpanLabel: { color: "#6c7890", fontWeight: "800" },
  noticeStrongGreen: { color: "#00a86b", fontWeight: "800" },
  clickGuide: {
    alignSelf: "center",
    backgroundColor: "rgba(22,31,50,0.9)",
    borderRadius: 999,
    bottom: 67,
    paddingHorizontal: 11,
    paddingVertical: 7,
    position: "absolute"
  },
  clickGuideText: { color: "#ffffff", fontSize: 8, fontWeight: "700" },
  nav: {
    alignItems: "center",
    backgroundColor: "#ffffff",
    borderTopColor: "#edf0f5",
    borderTopWidth: 1,
    bottom: 0,
    flexDirection: "row",
    height: 55,
    justifyContent: "space-around",
    left: 0,
    position: "absolute",
    right: 0
  },
  navItem: { color: "#9aa4b3", fontSize: 8 },
  navItemActive: { color: "#1763e8", fontWeight: "800" },
  shopApp: {
    backgroundColor: "#f6f6f6",
    borderRadius: 0,
    bottom: 0,
    left: 0,
    position: "absolute",
    right: 0,
    top: 0
  },
  appPage: { flex: 1 },
  shopeeHead: {
    alignItems: "flex-end",
    backgroundColor: "#ee4d2d",
    flexDirection: "row",
    gap: 10,
    height: 78,
    justifyContent: "space-between",
    paddingBottom: 11,
    paddingHorizontal: 13
  },
  shopeeTitle: { color: "#ffffff", flex: 1, fontSize: 14, fontWeight: "800" },
  demoPill: { backgroundColor: "rgba(255,255,255,0.13)", borderColor: "rgba(255,255,255,0.45)", borderRadius: 999, borderWidth: 1, paddingHorizontal: 7, paddingVertical: 4 },
  demoPillText: { color: "#ffffff", fontSize: 7, fontWeight: "800" },
  shopeeScroll: { flex: 1, paddingBottom: 12 },
  shopCard: { backgroundColor: "#ffffff", borderRadius: 12, margin: 8, padding: 11 },
  shopName: { color: "#282d38", fontSize: 11, fontWeight: "600", lineHeight: 15 },
  mallTag: { backgroundColor: "#d0011b", borderRadius: 3, color: "#ffffff", fontSize: 7, paddingHorizontal: 4 },
  shopStats: { color: "#7e8491", fontSize: 7, marginTop: 7 },
  shopPrice: { color: "#ee4d2d", fontSize: 20, marginTop: 9 },
  cashbackNote: { backgroundColor: "#edfff7", borderColor: "#cfeddf", borderRadius: 10, borderWidth: 1, marginTop: 10, padding: 9 },
  cashbackNoteText: { color: "#087a58", fontSize: 7, lineHeight: 10 },
  shopeeBottom: { alignItems: "center", backgroundColor: "#ee4d2d", bottom: 0, height: 55, justifyContent: "center", left: 0, position: "absolute", right: 0 },
  shopeeBuyText: { color: "#ffffff", fontSize: 10, fontWeight: "800" },
  sheetBackdrop: { backgroundColor: "rgba(12,18,30,0.35)", bottom: 0, justifyContent: "flex-end", left: 0, position: "absolute", right: 0, top: 0 },
  variantSheet: { backgroundColor: "#ffffff", borderRadius: 20, paddingBottom: 16, paddingHorizontal: 13, paddingTop: 14 },
  sheetHandle: { alignSelf: "center", backgroundColor: "#e3e5e9", borderRadius: 99, height: 4, marginBottom: 11, width: 38 },
  variantLabel: { color: "#626977", fontSize: 8, marginBottom: 8 },
  variantGrid: { flexDirection: "row", gap: 7 },
  variantChip: { alignItems: "center", borderColor: "#e2e4e8", borderRadius: 8, borderWidth: 1, flex: 1, justifyContent: "center", minHeight: 43, padding: 6 },
  variantChipSelected: { backgroundColor: "#fff5f2", borderColor: "#ee4d2d" },
  variantChipText: { color: "#555d6b", fontSize: 8 },
  variantChipTextSelected: { color: "#ee4d2d" },
  quantityRow: { alignItems: "center", flexDirection: "row", justifyContent: "space-between", marginTop: 11 },
  quantityLabel: { color: "#616977", fontSize: 8 },
  quantityControl: { borderColor: "#e2e4e8", borderRadius: 7, borderWidth: 1, flexDirection: "row" },
  quantityButton: { color: "#6f7684", fontSize: 10, paddingHorizontal: 9, paddingVertical: 6 },
  quantityValue: { borderColor: "#e2e4e8", borderLeftWidth: 1, borderRightWidth: 1, color: "#6f7684", fontSize: 10, paddingHorizontal: 9, paddingVertical: 6 },
  sheetContinue: { alignItems: "center", backgroundColor: "#f49a84", borderRadius: 8, height: 38, justifyContent: "center", marginTop: 12 },
  sheetContinueEnabled: { backgroundColor: "#ee4d2d" },
  sheetContinueText: { color: "#ffd7cd", fontSize: 10, fontWeight: "800" },
  sheetContinueTextEnabled: { color: "#ffffff" },
  checkoutHead: { alignItems: "flex-end", backgroundColor: "#ffffff", borderBottomColor: "#e8eaee", borderBottomWidth: 1, height: 78, justifyContent: "center", paddingBottom: 11, paddingHorizontal: 13 },
  checkoutHeadText: { color: "#343944", fontSize: 14, fontWeight: "800" },
  checkoutScroll: { flex: 1, paddingVertical: 8 },
  smallLabel: { color: "#8a909d", fontSize: 7, textTransform: "uppercase" },
  addressStrong: { color: "#353b47", fontSize: 9, marginTop: 4 },
  addressText: { color: "#727a89", fontSize: 7, lineHeight: 10, marginTop: 4 },
  cashbackRow: { backgroundColor: "#effcf7", borderRadius: 7, flexDirection: "row", justifyContent: "space-between", marginTop: 4, padding: 9 },
  cashbackRowLabel: { color: "#087a58", fontSize: 8 },
  cashbackRowValue: { color: "#00a86b", fontSize: 8, fontWeight: "800" },
  checkoutBottom: { alignItems: "center", backgroundColor: "#ffffff", borderTopColor: "#e7e9ed", borderTopWidth: 1, bottom: 0, flexDirection: "row", height: 57, justifyContent: "space-between", left: 0, paddingHorizontal: 13, position: "absolute", right: 0 },
  checkoutTotalLabel: { color: "#6c7381", fontSize: 7 },
  checkoutTotalValue: { color: "#ee4d2d", fontSize: 14, fontWeight: "800", marginTop: 2 },
  placeOrderButton: { alignItems: "center", backgroundColor: "#ee4d2d", borderRadius: 8, height: 40, justifyContent: "center", paddingHorizontal: 16 },
  placeOrderButtonText: { color: "#ffffff", fontSize: 10, fontWeight: "800" },
  centerPage: { alignItems: "center", backgroundColor: "#f7faf9", flex: 1, paddingHorizontal: 22, paddingTop: 70 },
  successIcon: { alignItems: "center", backgroundColor: "#00a86b", borderRadius: 999, height: 78, justifyContent: "center", width: 78 },
  successIconMark: { color: "#ffffff", fontSize: 36 },
  centerTitle: { color: "#273148", fontSize: 16, marginTop: 17 },
  centerText: { color: "#818a9b", fontSize: 8, lineHeight: 12, marginTop: 5, maxWidth: 245, textAlign: "center" },
  statusCard: { backgroundColor: "#ffffff", borderColor: "#d8eee5", borderRadius: 15, borderWidth: 1, marginTop: 18, padding: 13, width: "100%" },
  statusHead: { alignItems: "center", flexDirection: "row", justifyContent: "space-between" },
  statusHeadText: { color: "#087a58", fontSize: 8, fontWeight: "800" },
  statusPill: { backgroundColor: "#e9fbf3", borderRadius: 99, paddingHorizontal: 6, paddingVertical: 3 },
  statusPillText: { color: "#00a86b", fontSize: 7 },
  statusCode: { color: "#7f8899", fontSize: 7, marginTop: 8 },
  statusMoney: { alignItems: "flex-end", borderTopColor: "#edf1ef", borderTopWidth: 1, flexDirection: "row", justifyContent: "space-between", marginTop: 11, paddingTop: 10 },
  statusMoneyLabel: { color: "#687385", fontSize: 8 },
  statusMoneyValue: { color: "#00a86b", fontSize: 18, fontWeight: "800" },
  primaryAction: { alignItems: "center", backgroundColor: "#ff5a00", borderRadius: 999, height: 39, justifyContent: "center", marginTop: 13, width: "100%" },
  primaryActionText: { color: "#ffffff", fontSize: 9, fontWeight: "800" },
  resultHead: { alignItems: "flex-end", backgroundColor: "#ffffff", borderBottomColor: "#e7ebf2", borderBottomWidth: 1, height: 78, justifyContent: "center", paddingBottom: 11, paddingHorizontal: 14 },
  resultLogo: { fontSize: 14, fontWeight: "900" },
  resultBody: { padding: 18 },
  resultTitle: { color: "#26334d", fontSize: 15 },
  resultText: { color: "#7b879a", fontSize: 8, lineHeight: 12, marginTop: 6 },
  cashbackBig: { backgroundColor: "#effff8", borderColor: "#cdf0df", borderRadius: 18, borderWidth: 1, marginTop: 16, padding: 16 },
  cashbackBigLabel: { color: "#708095", fontSize: 8 },
  cashbackBigValue: { color: "#00a86b", fontSize: 26, fontWeight: "800", marginTop: 5 },
  cashbackBigNote: { color: "#7c8798", fontSize: 7, lineHeight: 11, marginTop: 5 },
  timeline: { backgroundColor: "#ffffff", borderRadius: 15, marginTop: 13, padding: 13 },
  timelineRow: { flexDirection: "row", gap: 9, paddingVertical: 7 },
  timelineDot: { alignItems: "center", backgroundColor: "#00a86b", borderRadius: 999, height: 17, justifyContent: "center", width: 17 },
  timelineDotPending: { backgroundColor: "#edf0f5" },
  timelineDotText: { color: "#ffffff", fontSize: 8 },
  timelineBody: { flex: 1 },
  timelineLabel: { color: "#34415b", fontSize: 8, fontWeight: "700" },
  timelineNote: { color: "#939baa", fontSize: 7, marginTop: 2 }
});
