import { Linking, Pressable, StyleSheet, Text, View } from "react-native";
import { useIosPayoutFeaturesEnabled } from "@/config/features";
import { legalUrlsForPayoutFeatures } from "@/features/legal/urls";
import { colors, spacing } from "@/theme/tokens";

export function LegalLinks({ compact = false }: { compact?: boolean }) {
  const payoutFeaturesEnabled = useIosPayoutFeaturesEnabled();
  const legalUrls = legalUrlsForPayoutFeatures(payoutFeaturesEnabled);
  async function open(url: string) {
    if (await Linking.canOpenURL(url)) await Linking.openURL(url);
  }

  return (
    <View style={[styles.container, compact && styles.compact]}>
      <Text style={styles.label}>Mesale</Text>
      <View style={styles.links}>
        <Pressable accessibilityRole="link" onPress={() => void open(legalUrls.privacy)}>
          <Text style={styles.link}>Chính sách bảo mật</Text>
        </Pressable>
        <Pressable accessibilityRole="link" onPress={() => void open(legalUrls.terms)}>
          <Text style={styles.link}>Điều khoản</Text>
        </Pressable>
        <Pressable accessibilityRole="link" onPress={() => void open(legalUrls.support)}>
          <Text style={styles.link}>Hỗ trợ</Text>
        </Pressable>
        <Pressable accessibilityRole="link" onPress={() => void open(legalUrls.deletion)}>
          <Text style={styles.link}>Xóa tài khoản</Text>
        </Pressable>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { gap: spacing.xs, paddingTop: spacing.sm },
  compact: { paddingTop: 0 },
  label: { color: colors.mutedText, fontSize: 11, textAlign: "center" },
  links: { alignItems: "center", flexDirection: "row", flexWrap: "wrap", gap: spacing.sm, justifyContent: "center" },
  link: { color: colors.primary, fontSize: 12, fontWeight: "700", textDecorationLine: "underline" }
});
