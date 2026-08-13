import { useEffect, useRef, useState, type ReactNode, type RefObject } from "react";
import * as AppleAuthentication from "expo-apple-authentication";
import { LinearGradient } from "expo-linear-gradient";
import { Link, Redirect } from "expo-router";
import { Eye, EyeOff, LockKeyhole, Mail, Phone, UserRound } from "lucide-react-native";
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
  View,
  type TextInputProps
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import Svg, { Path } from "react-native-svg";
import { ApiError } from "@/api/client";
import { useAuth } from "@/auth/AuthProvider";
import { resolveAuthGate } from "@/auth/routing";
import { FormErrorSummary } from "@/components/FormErrorSummary";
import {
  isAppleNativeSignInAvailable,
  oauthUiStateFromReason
} from "@/features/auth/nativeOAuth";
import type { NativeOAuthProvider } from "@/features/auth/nativeOAuthContract";
import { validateRegistration } from "@/features/auth/validation";
import { getDeviceLocale } from "@/i18n";
import { useTheme } from "@/theme/ThemeProvider";

const LEGAL_URLS = {
  privacy: "https://mesale.vn/privacy",
  terms: "https://mesale.vn/terms"
} as const;

const BRAND_BLUE = "#1684e8";
const BRAND_BLUE_DARK = "#075fc4";

export default function RegisterScreen() {
  const locale = getDeviceLocale();
  const { colors, radius, scheme, spacing } = useTheme();
  const insets = useSafeAreaInsets();
  const {
    isLoading: isAuthLoading,
    loginWithApple,
    loginWithGoogle,
    pendingAuth,
    register,
    session,
    user
  } = useAuth();
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [passwordVisible, setPasswordVisible] = useState(false);
  const [confirmationVisible, setConfirmationVisible] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [requestError, setRequestError] = useState<ApiError | null>(null);
  const [oauthError, setOauthError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [nativeProvider, setNativeProvider] = useState<NativeOAuthProvider | null>(null);
  const [appleAvailable, setAppleAvailable] = useState(false);
  const [disabledProviders, setDisabledProviders] = useState<Set<NativeOAuthProvider>>(() => new Set());
  const emailInputRef = useRef<TextInput>(null);
  const phoneInputRef = useRef<TextInput>(null);
  const passwordInputRef = useRef<TextInput>(null);
  const confirmationInputRef = useRef<TextInput>(null);
  const isBusy = submitting || nativeProvider !== null;

  useEffect(() => {
    let active = true;
    void isAppleNativeSignInAvailable().then((available) => {
      if (active) setAppleAvailable(available);
    });
    return () => { active = false; };
  }, []);

  if (isAuthLoading) {
    return <View style={[styles.loading, { backgroundColor: colors.background }]}><ActivityIndicator color={BRAND_BLUE} /></View>;
  }

  const authGate = resolveAuthGate(pendingAuth, Boolean(session), user?.referralPromptPending ?? false);
  if (authGate) return <Redirect href={authGate} />;

  async function submit() {
    const nextErrors = validateRegistration({
      email,
      password,
      passwordConfirmation,
      acceptedTerms: true
    });
    setErrors(nextErrors);
    setRequestError(null);
    setOauthError(null);
    setNotice(null);
    if (Object.keys(nextErrors).length > 0) return;

    setSubmitting(true);
    try {
      await register({ name, email, phone, password, passwordConfirmation });
    } catch (reason) {
      setRequestError(reason instanceof ApiError
        ? reason
        : new ApiError(reason instanceof Error ? reason.message : "Không thể đăng ký.", 0));
    } finally {
      setSubmitting(false);
    }
  }

  async function submitNative(provider: NativeOAuthProvider) {
    setRequestError(null);
    setOauthError(null);
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
      else setOauthError(state.message);
    } finally {
      setNativeProvider(null);
    }
  }

  async function openLegal(url: string) {
    if (await Linking.canOpenURL(url)) await Linking.openURL(url);
  }

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === "ios" ? "padding" : "height"}
      style={[styles.screen, { backgroundColor: colors.background }]}
    >
      <ScrollView
        contentContainerStyle={[styles.scrollContent, { paddingBottom: Math.max(insets.bottom, spacing.md) + spacing.xl }]}
        keyboardDismissMode={Platform.OS === "ios" ? "interactive" : "on-drag"}
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={false}
      >
        <LinearGradient
          colors={["#36aaf5", BRAND_BLUE, BRAND_BLUE_DARK]}
          end={{ x: 1, y: 1 }}
          start={{ x: 0, y: 0 }}
          style={[styles.hero, { paddingTop: insets.top + spacing.md }]}
        >
          <View pointerEvents="none" style={styles.heroGlowLarge} />
          <View pointerEvents="none" style={styles.heroGlowSmall} />
          <View style={styles.logoFrame}>
            <Image
              accessibilityIgnoresInvertColors
              accessibilityLabel="Logo Mê Sale"
              resizeMode="contain"
              source={require("../../assets/mesale-logo.png")}
              style={styles.logo}
            />
          </View>
          <Text style={styles.heroTagline}>Hệ thống mua sắm hoàn tiền Shopee - TikTok Shop</Text>
        </LinearGradient>

        <View
          style={[
            styles.card,
            {
              backgroundColor: colors.surface,
              borderColor: colors.border,
              borderRadius: Math.max(radius.lg, 28)
            }
          ]}
        >
          <Text accessibilityRole="header" style={[styles.title, { color: colors.text }]}>Tạo tài khoản</Text>

          <View style={styles.fields}>
            <RegisterField
              autoCapitalize="words"
              autoComplete="name"
              editable={!isBusy}
              icon={<UserRound color={colors.mutedText} size={21} strokeWidth={1.8} />}
              onChangeText={setName}
              onSubmitEditing={() => emailInputRef.current?.focus()}
              placeholder="Tên hiển thị (tùy chọn)"
              returnKeyType="next"
              value={name}
            />
            <RegisterField
              autoCapitalize="none"
              autoComplete="email"
              editable={!isBusy}
              error={errors.email}
              icon={<Mail color={colors.mutedText} size={21} strokeWidth={1.8} />}
              inputRef={emailInputRef}
              keyboardType="email-address"
              onChangeText={setEmail}
              onSubmitEditing={() => phoneInputRef.current?.focus()}
              placeholder="Email"
              returnKeyType="next"
              value={email}
            />
            <RegisterField
              autoCapitalize="none"
              autoComplete="tel"
              editable={!isBusy}
              icon={<Phone color={colors.mutedText} size={21} strokeWidth={1.8} />}
              inputRef={phoneInputRef}
              keyboardType="phone-pad"
              onChangeText={setPhone}
              onSubmitEditing={() => passwordInputRef.current?.focus()}
              placeholder="Số điện thoại (tùy chọn)"
              returnKeyType="next"
              value={phone}
            />
            <View style={styles.passwordField}>
              <RegisterField
                autoCapitalize="none"
                autoComplete="new-password"
                editable={!isBusy}
                error={errors.password}
                icon={<LockKeyhole color={colors.mutedText} size={21} strokeWidth={1.8} />}
                inputRef={passwordInputRef}
                onChangeText={setPassword}
                onSubmitEditing={() => confirmationInputRef.current?.focus()}
                placeholder="Mật khẩu"
                returnKeyType="next"
                rightAction={(
                  <PasswordToggle
                    color={colors.mutedText}
                    onPress={() => setPasswordVisible((current) => !current)}
                    visible={passwordVisible}
                  />
                )}
                secureTextEntry={!passwordVisible}
                value={password}
              />
              {!errors.password ? <Text style={[styles.passwordHint, { color: colors.mutedText }]}>Tối thiểu 8 ký tự.</Text> : null}
            </View>
            <RegisterField
              autoCapitalize="none"
              autoComplete="new-password"
              editable={!isBusy}
              error={errors.password_confirmation}
              icon={<LockKeyhole color={colors.mutedText} size={21} strokeWidth={1.8} />}
              inputRef={confirmationInputRef}
              onChangeText={setPasswordConfirmation}
              onSubmitEditing={() => { if (!isBusy) void submit(); }}
              placeholder="Nhập lại mật khẩu"
              returnKeyType="go"
              rightAction={(
                <PasswordToggle
                  color={colors.mutedText}
                  onPress={() => setConfirmationVisible((current) => !current)}
                  visible={confirmationVisible}
                />
              )}
              secureTextEntry={!confirmationVisible}
              value={passwordConfirmation}
            />
          </View>

          <Text style={[styles.termsCopy, { color: colors.mutedText }]}>
            Bằng việc tạo tài khoản, bạn đồng ý với{" "}
            <Text accessibilityRole="link" onPress={() => void openLegal(LEGAL_URLS.terms)} style={styles.legalLink}>Điều khoản sử dụng</Text>
            {" "}và xác nhận đã đọc{" "}
            <Text accessibilityRole="link" onPress={() => void openLegal(LEGAL_URLS.privacy)} style={styles.legalLink}>Chính sách bảo mật</Text>.
          </Text>

          <FormErrorSummary errors={requestError?.errors} message={requestError?.message} />
          {oauthError ? <AuthMessage message={oauthError} tone="error" /> : null}
          {notice ? <AuthMessage message={notice} tone="notice" /> : null}

          <Pressable
            accessibilityHint="Tạo tài khoản Mê Sale bằng thông tin đã nhập."
            accessibilityLabel="Đăng ký"
            accessibilityRole="button"
            accessibilityState={{ busy: submitting, disabled: isBusy }}
            disabled={isBusy}
            onPress={() => void submit()}
            style={({ pressed }) => [styles.primaryButtonShell, isBusy && styles.disabled, pressed && styles.pressed]}
          >
            <LinearGradient
              colors={["#31a9f5", BRAND_BLUE_DARK]}
              end={{ x: 1, y: 0 }}
              start={{ x: 0, y: 0 }}
              style={styles.primaryButton}
            >
              {submitting ? <ActivityIndicator color="#ffffff" /> : <Text style={styles.primaryButtonText}>Đăng ký</Text>}
            </LinearGradient>
          </Pressable>

          <View style={styles.dividerRow}>
            <View style={[styles.divider, { backgroundColor: colors.border }]} />
            <Text style={[styles.dividerText, { color: colors.mutedText }]}>hoặc</Text>
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
            accessibilityHint="Tiếp tục bằng tài khoản Google trên thiết bị."
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
        </View>

        <View style={styles.loginRow}>
          <Text style={[styles.loginCopy, { color: colors.mutedText }]}>Đã có tài khoản? </Text>
          <Link href="/login" style={styles.loginLink}>Đăng nhập</Link>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );

}

function RegisterField({ icon, error, inputRef, rightAction, ...props }: TextInputProps & {
  icon: ReactNode;
  error?: string;
  inputRef?: RefObject<TextInput | null>;
  rightAction?: ReactNode;
}) {
  const { colors, scheme } = useTheme();
  return (
    <View style={styles.field}>
      <View style={[
        styles.inputShell,
        {
          backgroundColor: scheme === "dark" ? colors.background : "#f3f8fd",
          borderColor: error ? colors.danger : colors.border
        }
      ]}>
        {icon}
        <TextInput
          accessibilityLabel={typeof props.placeholder === "string" ? props.placeholder : undefined}
          placeholderTextColor={colors.mutedText}
          ref={inputRef}
          style={[styles.input, { color: colors.text }]}
          {...props}
        />
        {rightAction}
      </View>
      {error ? <Text accessibilityRole="alert" style={[styles.fieldError, { color: colors.danger }]}>{error}</Text> : null}
    </View>
  );
}

function PasswordToggle({ color, onPress, visible }: { color: string; onPress(): void; visible: boolean }) {
  return (
    <Pressable
      accessibilityLabel={visible ? "Ẩn mật khẩu" : "Hiện mật khẩu"}
      accessibilityRole="button"
      accessibilityState={{ expanded: visible }}
      hitSlop={8}
      onPress={onPress}
      style={({ pressed }) => [styles.eyeButton, pressed && styles.pressed]}
    >
      {visible
        ? <EyeOff color={color} size={22} strokeWidth={1.8} />
        : <Eye color={color} size={22} strokeWidth={1.8} />}
    </Pressable>
  );
}

function AuthMessage({ message, tone }: { message: string; tone: "error" | "notice" }) {
  return (
    <View
      accessibilityLiveRegion="polite"
      accessibilityRole={tone === "error" ? "alert" : undefined}
      style={[styles.message, tone === "error" ? styles.errorMessage : styles.noticeMessage]}
    >
      <Text style={tone === "error" ? styles.errorText : styles.noticeText}>{message}</Text>
    </View>
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
  hero: { alignItems: "center", minHeight: 286, overflow: "hidden", paddingHorizontal: 24 },
  heroGlowLarge: { backgroundColor: "rgba(255,255,255,0.10)", borderRadius: 200, height: 330, position: "absolute", right: -128, top: -165, width: 330 },
  heroGlowSmall: { backgroundColor: "rgba(255,255,255,0.11)", borderRadius: 100, bottom: -88, height: 200, left: -82, position: "absolute", width: 200 },
  logoFrame: { alignItems: "center", backgroundColor: "#ffffff", borderRadius: 24, height: 112, justifyContent: "center", marginTop: 4, overflow: "hidden", width: 112 },
  logo: { height: 106, width: 106 },
  heroTagline: { color: "rgba(255,255,255,0.94)", fontSize: 14, fontWeight: "700", lineHeight: 20, marginTop: 12, maxWidth: 310, textAlign: "center" },
  card: { borderWidth: StyleSheet.hairlineWidth, elevation: 10, gap: 16, marginHorizontal: 16, marginTop: -50, padding: 22, shadowColor: "#0c4a6e", shadowOffset: { height: 10, width: 0 }, shadowOpacity: 0.15, shadowRadius: 24 },
  title: { fontSize: 29, fontWeight: "900", letterSpacing: -0.7 },
  fields: { gap: 13 },
  field: { gap: 6 },
  passwordField: { gap: 6 },
  inputShell: { alignItems: "center", borderRadius: 15, borderWidth: 1, flexDirection: "row", minHeight: 56, paddingHorizontal: 14 },
  input: { flex: 1, fontSize: 15.5, minHeight: 54, paddingHorizontal: 12, paddingVertical: 12 },
  eyeButton: { alignItems: "center", height: 44, justifyContent: "center", marginRight: -8, width: 44 },
  fieldError: { fontSize: 12, lineHeight: 17, paddingHorizontal: 4 },
  passwordHint: { fontSize: 12, lineHeight: 17, paddingHorizontal: 4 },
  termsCopy: { fontSize: 12.5, lineHeight: 19, textAlign: "center" },
  legalLink: { color: BRAND_BLUE, fontWeight: "900" },
  primaryButtonShell: { borderRadius: 15, minHeight: 56, overflow: "hidden" },
  primaryButton: { alignItems: "center", justifyContent: "center", minHeight: 56, paddingHorizontal: 18 },
  primaryButtonText: { color: "#ffffff", fontSize: 17, fontWeight: "900" },
  dividerRow: { alignItems: "center", flexDirection: "row", gap: 12, marginVertical: 2 },
  divider: { flex: 1, height: StyleSheet.hairlineWidth },
  dividerText: { fontSize: 13, fontWeight: "700" },
  appleButtonContainer: { minHeight: 54 },
  appleButton: { height: 54, width: "100%" },
  appleLoading: { alignItems: "center", backgroundColor: "#000000", borderRadius: 14, height: 54, justifyContent: "center" },
  oauthButton: { alignItems: "center", borderRadius: 15, borderWidth: 1, justifyContent: "center", minHeight: 54, paddingHorizontal: 16 },
  oauthContent: { alignItems: "center", flexDirection: "row", gap: 12 },
  oauthButtonText: { fontSize: 15.5, fontWeight: "900" },
  loginRow: { alignItems: "center", flexDirection: "row", flexWrap: "wrap", justifyContent: "center", paddingHorizontal: 20, paddingTop: 24 },
  loginCopy: { fontSize: 14.5 },
  loginLink: { color: BRAND_BLUE, fontSize: 14.5, fontWeight: "900" },
  message: { borderRadius: 12, borderWidth: 1, paddingHorizontal: 13, paddingVertical: 11 },
  errorMessage: { backgroundColor: "#fef2f2", borderColor: "#fecaca" },
  errorText: { color: "#b91c1c", fontSize: 13, lineHeight: 19 },
  noticeMessage: { backgroundColor: "#eff6ff", borderColor: "#bfdbfe" },
  noticeText: { color: "#1d4ed8", fontSize: 13, lineHeight: 19 },
  disabled: { opacity: 0.48 },
  pressed: { opacity: 0.78 }
});
