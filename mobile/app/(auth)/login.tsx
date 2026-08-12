import { useEffect, useRef, useState } from "react";
import * as AppleAuthentication from "expo-apple-authentication";
import { LinearGradient } from "expo-linear-gradient";
import { Link, Redirect } from "expo-router";
import { ArrowRight, Eye, EyeOff, LockKeyhole, Mail, ShieldCheck } from "lucide-react-native";
import {
  ActivityIndicator,
  Image,
  KeyboardAvoidingView,
  Linking,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import Svg, { Path } from "react-native-svg";
import { useAuth } from "@/auth/AuthProvider";
import { resolveAuthGate } from "@/auth/routing";
import { getDeviceLocale, t } from "@/i18n";
import {
  isAppleNativeSignInAvailable,
  oauthUiStateFromReason
} from "@/features/auth/nativeOAuth";
import type { NativeOAuthProvider } from "@/features/auth/nativeOAuthContract";
import { useTheme } from "@/theme/ThemeProvider";

const LEGAL_URLS = {
  privacy: "https://mesale.vn/privacy",
  terms: "https://mesale.vn/terms",
  support: "https://mesale.vn/support",
  deletion: "https://mesale.vn/account-deletion"
} as const;

const BRAND_BLUE = "#1684e8";
const BRAND_BLUE_DARK = "#075fc4";
const BRAND_ORANGE = "#f97316";

export default function LoginScreen() {
  const locale = getDeviceLocale();
  const { colors, radius, spacing, scheme } = useTheme();
  const insets = useSafeAreaInsets();
  const { isLoading: isAuthLoading, pendingAuth, session, user, login, loginWithApple, loginWithGoogle } = useAuth();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [passwordVisible, setPasswordVisible] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [isSubmitting, setSubmitting] = useState(false);
  const [nativeProvider, setNativeProvider] = useState<NativeOAuthProvider | null>(null);
  const [appleAvailable, setAppleAvailable] = useState(false);
  const [disabledProviders, setDisabledProviders] = useState<Set<NativeOAuthProvider>>(() => new Set());
  const passwordInputRef = useRef<TextInput>(null);
  const normalizedLogin = email.trim();
  const isBusy = isSubmitting || nativeProvider !== null;
  const cannotSubmit = isBusy || !normalizedLogin || !password;
  const fieldBackground = scheme === "dark" ? colors.background : "#f5f9fd";

  useEffect(() => {
    let active = true;
    void isAppleNativeSignInAvailable().then((available) => {
      if (active) setAppleAvailable(available);
    });
    return () => { active = false; };
  }, []);

  async function submit() {
    setError(null);
    setNotice(null);
    setSubmitting(true);
    try {
      await login(normalizedLogin, password);
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : "Không thể đăng nhập lúc này.");
    } finally {
      setSubmitting(false);
    }
  }

  async function submitNative(provider: NativeOAuthProvider) {
    setError(null);
    setNotice(null);
    setNativeProvider(provider);
    try {
      if (provider === "google") await loginWithGoogle();
      else await loginWithApple();
    } catch (reason) {
      const state = oauthUiStateFromReason(reason, provider, locale);
      if (state.disablesProvider) {
        setDisabledProviders((current) => new Set(current).add(provider));
      }
      if (state.isCancellation) setNotice(state.message);
      else setError(state.message);
    } finally {
      setNativeProvider(null);
    }
  }

  async function openLegal(url: string) {
    if (await Linking.canOpenURL(url)) await Linking.openURL(url);
  }

  if (isAuthLoading) {
    return <View style={[styles.loading, { backgroundColor: colors.background }]}><ActivityIndicator color={BRAND_BLUE} /></View>;
  }

  const authGate = resolveAuthGate(pendingAuth, Boolean(session), user?.referralPromptPending ?? false);
  if (authGate) return <Redirect href={authGate} />;

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === "ios" ? "padding" : "height"}
      style={[styles.screen, { backgroundColor: colors.background }]}
    >
      <ScrollView
        contentContainerStyle={[styles.scrollContent, { paddingBottom: Math.max(insets.bottom, spacing.md) + spacing.lg }]}
        keyboardDismissMode={Platform.OS === "ios" ? "interactive" : "on-drag"}
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={false}
      >
        <LinearGradient
          colors={["#31a8f4", BRAND_BLUE, BRAND_BLUE_DARK]}
          end={{ x: 1, y: 1 }}
          start={{ x: 0, y: 0 }}
          style={[styles.hero, { paddingTop: insets.top + spacing.lg }]}
        >
          <View pointerEvents="none" style={styles.heroGlowLarge} />
          <View pointerEvents="none" style={styles.heroGlowSmall} />
          <View style={styles.brandRow}>
            <View style={styles.logoFrame}>
              <Image
                accessibilityIgnoresInvertColors
                accessibilityLabel="Logo Mê Sale"
                resizeMode="contain"
                source={require("../../assets/mesale-logo.png")}
                style={styles.logo}
              />
            </View>
            <View style={styles.brandCopy}>
              <Text style={styles.brandName}><Text style={styles.brandOrange}>Mê</Text> Sale</Text>
              <Text style={styles.brandTagline}>Mua sắm thông minh, nhận hoàn tiền</Text>
            </View>
          </View>
        </LinearGradient>

        <View
          style={[
            styles.card,
            {
              backgroundColor: colors.surface,
              borderColor: colors.border,
              borderRadius: radius.lg
            }
          ]}
        >
          <View style={styles.heading}>
            <Text accessibilityRole="header" style={[styles.title, { color: colors.text }]}>Chào mừng trở lại</Text>
              <Text style={[styles.subtitle, { color: colors.mutedText }]}>Đăng nhập để quản lý cashback và rút tiền</Text>
          </View>

          <View style={styles.fields}>
            <View style={styles.field}>
              <Text style={[styles.label, { color: colors.text }]}>{t(locale, "email")}</Text>
              <View style={[styles.inputShell, { backgroundColor: fieldBackground, borderColor: colors.border }]}>
                <Mail color={colors.mutedText} size={20} strokeWidth={1.8} />
                <TextInput
                  accessibilityHint="Nhập email hoặc số điện thoại dùng để đăng nhập."
                  accessibilityLabel={t(locale, "email")}
                  autoCapitalize="none"
                  autoComplete="email"
                  editable={!isBusy}
                  enablesReturnKeyAutomatically
                  keyboardType="email-address"
                  onChangeText={setEmail}
                  onSubmitEditing={() => passwordInputRef.current?.focus()}
                  placeholder="email@example.com"
                  placeholderTextColor={colors.mutedText}
                  returnKeyType="next"
                  style={[styles.input, { color: colors.text }]}
                  value={email}
                />
              </View>
            </View>

            <View style={styles.field}>
              <Text style={[styles.label, { color: colors.text }]}>{t(locale, "password")}</Text>
              <View style={[styles.inputShell, { backgroundColor: fieldBackground, borderColor: colors.border }]}>
                <LockKeyhole color={colors.mutedText} size={20} strokeWidth={1.8} />
                <TextInput
                  accessibilityHint="Nhập mật khẩu rồi nhấn đăng nhập trên bàn phím."
                  accessibilityLabel={t(locale, "password")}
                  autoCapitalize="none"
                  autoComplete="password"
                  editable={!isBusy}
                  enablesReturnKeyAutomatically
                  onChangeText={setPassword}
                  onSubmitEditing={() => { if (!cannotSubmit) void submit(); }}
                  placeholder="Mật khẩu"
                  placeholderTextColor={colors.mutedText}
                  ref={passwordInputRef}
                  returnKeyType="go"
                  secureTextEntry={!passwordVisible}
                  style={[styles.input, { color: colors.text }]}
                  value={password}
                />
                <Pressable
                  accessibilityLabel={passwordVisible ? "Ẩn mật khẩu" : "Hiện mật khẩu"}
                  accessibilityRole="button"
                  accessibilityState={{ expanded: passwordVisible }}
                  hitSlop={8}
                  onPress={() => setPasswordVisible((current) => !current)}
                  style={({ pressed }) => [styles.eyeButton, pressed && styles.pressed]}
                >
                  {passwordVisible
                    ? <EyeOff color={colors.mutedText} size={22} strokeWidth={1.8} />
                    : <Eye color={colors.mutedText} size={22} strokeWidth={1.8} />}
                </Pressable>
              </View>
            </View>
          </View>

          <Link href="/forgot-password" style={styles.forgotLink}>Quên mật khẩu?</Link>

          {error ? (
            <View accessibilityRole="alert" style={[styles.message, styles.errorMessage]}>
              <Text style={styles.errorText}>{error}</Text>
            </View>
          ) : null}
          {notice ? (
            <View accessibilityLiveRegion="polite" style={[styles.message, styles.noticeMessage]}>
              <Text style={styles.noticeText}>{notice}</Text>
            </View>
          ) : null}

          <Pressable
            accessibilityHint="Xác thực thông tin và tiếp tục đăng nhập."
            accessibilityLabel={t(locale, "signIn")}
            accessibilityRole="button"
            accessibilityState={{ disabled: cannotSubmit, busy: isSubmitting }}
            disabled={cannotSubmit}
            onPress={() => void submit()}
            style={({ pressed }) => [styles.primaryButtonShell, cannotSubmit && styles.disabled, pressed && styles.pressed]}
          >
            <LinearGradient
              colors={["#2da9f7", BRAND_BLUE_DARK]}
              end={{ x: 1, y: 0 }}
              start={{ x: 0, y: 0 }}
              style={styles.primaryButton}
            >
              {isSubmitting ? (
                <ActivityIndicator color="#ffffff" />
              ) : (
                <View style={styles.primaryContent}>
                  <Text style={styles.primaryButtonText}>Đăng nhập</Text>
                  <ArrowRight color="#ffffff" size={21} strokeWidth={2.4} />
                </View>
              )}
            </LinearGradient>
          </Pressable>

          <View style={styles.dividerRow}>
            <View style={[styles.divider, { backgroundColor: colors.border }]} />
            <Text style={[styles.dividerText, { color: colors.mutedText }]}>HOẶC ĐĂNG NHẬP BẰNG</Text>
            <View style={[styles.divider, { backgroundColor: colors.border }]} />
          </View>

          {appleAvailable ? (
            <View
              accessibilityElementsHidden={disabledProviders.has("apple")}
              importantForAccessibility={disabledProviders.has("apple") ? "no-hide-descendants" : "auto"}
              pointerEvents={isBusy || disabledProviders.has("apple") ? "none" : "auto"}
              style={[styles.appleButtonContainer, (isBusy || disabledProviders.has("apple")) && styles.disabled]}
            >
              {nativeProvider === "apple" ? (
                <View style={styles.appleLoading}><ActivityIndicator color="#ffffff" /></View>
              ) : (
                <AppleAuthentication.AppleAuthenticationButton
                  buttonStyle={AppleAuthentication.AppleAuthenticationButtonStyle.BLACK}
                  buttonType={AppleAuthentication.AppleAuthenticationButtonType.CONTINUE}
                  cornerRadius={14}
                  onPress={() => void submitNative("apple")}
                  style={styles.appleButton}
                />
              )}
            </View>
          ) : null}

          <Pressable
            accessibilityHint="Mở luồng đăng nhập Google an toàn trên thiết bị."
            accessibilityLabel="Tiếp tục với Google"
            accessibilityRole="button"
            accessibilityState={{ disabled: isBusy || disabledProviders.has("google"), busy: nativeProvider === "google" }}
            disabled={isBusy || disabledProviders.has("google")}
            onPress={() => void submitNative("google")}
            style={({ pressed }) => [
              styles.oauthButton,
              { backgroundColor: colors.surface, borderColor: colors.border },
              (isBusy || disabledProviders.has("google")) && styles.disabled,
              pressed && styles.pressed
            ]}
          >
            {nativeProvider === "google" ? (
              <ActivityIndicator color={colors.text} />
            ) : (
              <View style={styles.oauthContent}>
                <GoogleLogo />
                <Text style={[styles.oauthButtonText, { color: colors.text }]}>Tiếp tục với Google</Text>
              </View>
            )}
          </Pressable>

          <View style={styles.registerRow}>
            <Text style={[styles.registerCopy, { color: colors.mutedText }]}>Chưa có tài khoản? </Text>
            <Link href="/register" style={styles.registerLink}>Đăng ký ngay</Link>
          </View>
        </View>

        <View style={styles.footer}>
          <View style={styles.trustRow}>
            <ShieldCheck color={BRAND_BLUE} size={16} strokeWidth={2} />
            <Text style={[styles.disclosure, { color: colors.mutedText }]}>Đăng nhập an toàn qua máy chủ Mê Sale.</Text>
          </View>
          <Text style={[styles.disclosure, { color: colors.mutedText }]}>Mê Sale là ứng dụng hoàn tiền độc lập.</Text>
          <View style={styles.legalRow}>
            <LegalLink label="Chính sách bảo mật" onPress={() => void openLegal(LEGAL_URLS.privacy)} />
            <Text style={[styles.legalSeparator, { color: colors.mutedText }]}>·</Text>
            <LegalLink label="Điều khoản sử dụng" onPress={() => void openLegal(LEGAL_URLS.terms)} />
          </View>
          <View style={styles.legalRow}>
            <LegalLink label="Hỗ trợ" onPress={() => void openLegal(LEGAL_URLS.support)} />
            <Text style={[styles.legalSeparator, { color: colors.mutedText }]}>·</Text>
            <LegalLink label="Xóa tài khoản" onPress={() => void openLegal(LEGAL_URLS.deletion)} />
          </View>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

function LegalLink({ label, onPress }: { label: string; onPress(): void }) {
  return (
    <Pressable accessibilityLabel={label} accessibilityRole="link" hitSlop={6} onPress={onPress}>
      <Text style={styles.legalLink}>{label}</Text>
    </Pressable>
  );
}

function GoogleLogo() {
  return (
    <Svg accessibilityElementsHidden height={22} viewBox="0 0 24 24" width={22}>
      <Path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285f4" />
      <Path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34a853" />
      <Path d="M5.84 14.09A6.4 6.4 0 0 1 5.49 12c0-.73.13-1.43.35-2.09V7.07H2.18A11 11 0 0 0 1 12c0 1.78.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#fbbc05" />
      <Path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#ea4335" />
    </Svg>
  );
}

const styles = StyleSheet.create({
  loading: { alignItems: "center", flex: 1, justifyContent: "center" },
  screen: { flex: 1 },
  scrollContent: { flexGrow: 1 },
  hero: { minHeight: 248, overflow: "hidden", paddingHorizontal: 24 },
  heroGlowLarge: { backgroundColor: "rgba(255,255,255,0.10)", borderRadius: 180, height: 300, position: "absolute", right: -115, top: -135, width: 300 },
  heroGlowSmall: { backgroundColor: "rgba(255,255,255,0.12)", borderRadius: 90, bottom: -78, height: 180, left: -68, position: "absolute", width: 180 },
  brandRow: { alignItems: "center", flexDirection: "row", gap: 14, justifyContent: "center", marginTop: 12 },
  logoFrame: { alignItems: "center", backgroundColor: "#ffffff", borderRadius: 20, height: 72, justifyContent: "center", overflow: "hidden", width: 72 },
  logo: { height: 68, width: 68 },
  brandCopy: { flexShrink: 1 },
  brandName: { color: "#ffffff", fontSize: 31, fontWeight: "900", letterSpacing: -1 },
  brandOrange: { color: "#ff8a24" },
  brandTagline: { color: "rgba(255,255,255,0.90)", fontSize: 13, fontWeight: "600", lineHeight: 19, marginTop: 2 },
  card: { borderWidth: StyleSheet.hairlineWidth, elevation: 8, gap: 16, marginHorizontal: 18, marginTop: -82, padding: 22, shadowColor: "#0c4a6e", shadowOffset: { height: 8, width: 0 }, shadowOpacity: 0.14, shadowRadius: 22 },
  heading: { gap: 5 },
  title: { fontSize: 27, fontWeight: "900", letterSpacing: -0.7 },
  subtitle: { fontSize: 14, lineHeight: 21 },
  fields: { gap: 14 },
  field: { gap: 7 },
  label: { fontSize: 13, fontWeight: "800" },
  inputShell: { alignItems: "center", borderRadius: 14, borderWidth: 1, flexDirection: "row", minHeight: 54, paddingHorizontal: 14 },
  input: { flex: 1, fontSize: 15, minHeight: 52, paddingHorizontal: 11, paddingVertical: 12 },
  eyeButton: { alignItems: "center", height: 44, justifyContent: "center", marginRight: -8, width: 44 },
  forgotLink: { alignSelf: "flex-end", color: BRAND_BLUE, fontSize: 13, fontWeight: "800", marginTop: -4 },
  message: { borderRadius: 12, borderWidth: 1, paddingHorizontal: 13, paddingVertical: 11 },
  errorMessage: { backgroundColor: "#fef2f2", borderColor: "#fecaca" },
  errorText: { color: "#b91c1c", fontSize: 13, lineHeight: 19 },
  noticeMessage: { backgroundColor: "#eff6ff", borderColor: "#bfdbfe" },
  noticeText: { color: "#1d4ed8", fontSize: 13, lineHeight: 19 },
  primaryButtonShell: { borderRadius: 14, minHeight: 54, overflow: "hidden" },
  primaryButton: { alignItems: "center", justifyContent: "center", minHeight: 54, paddingHorizontal: 18 },
  primaryContent: { alignItems: "center", flexDirection: "row", gap: 9 },
  primaryButtonText: { color: "#ffffff", fontSize: 16, fontWeight: "900" },
  disabled: { opacity: 0.48 },
  pressed: { opacity: 0.78 },
  dividerRow: { alignItems: "center", flexDirection: "row", gap: 10, marginVertical: 2 },
  divider: { flex: 1, height: StyleSheet.hairlineWidth },
  dividerText: { fontSize: 10, fontWeight: "800", letterSpacing: 0.35 },
  appleButtonContainer: { minHeight: 52 },
  appleButton: { height: 52, width: "100%" },
  appleLoading: { alignItems: "center", backgroundColor: "#000000", borderRadius: 14, height: 52, justifyContent: "center" },
  oauthButton: { alignItems: "center", borderRadius: 14, borderWidth: 1, justifyContent: "center", minHeight: 52, paddingHorizontal: 16 },
  oauthContent: { alignItems: "center", flexDirection: "row", gap: 12 },
  oauthButtonText: { fontSize: 15, fontWeight: "800" },
  registerRow: { alignItems: "center", flexDirection: "row", flexWrap: "wrap", justifyContent: "center", paddingTop: 2 },
  registerCopy: { fontSize: 14 },
  registerLink: { color: BRAND_BLUE, fontSize: 14, fontWeight: "900" },
  footer: { alignItems: "center", gap: 8, paddingHorizontal: 24, paddingTop: 22 },
  trustRow: { alignItems: "center", flexDirection: "row", gap: 6, justifyContent: "center" },
  disclosure: { fontSize: 12, lineHeight: 18, textAlign: "center" },
  legalRow: { alignItems: "center", flexDirection: "row", flexWrap: "wrap", gap: 8, justifyContent: "center" },
  legalSeparator: { fontSize: 13 },
  legalLink: { color: BRAND_BLUE, fontSize: 12, fontWeight: "700" }
});
