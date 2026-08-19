import { useRouter } from "expo-router";
import { ChevronLeft } from "lucide-react-native";
import { Pressable, StyleSheet, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import {
  resolveHeaderTitle,
  type HeaderTitleOptions,
} from "@/navigation/header";
import { useTheme } from "@/theme/ThemeProvider";

type StackHeaderProps = {
  navigation: { canGoBack: () => boolean; goBack: () => void };
  options: HeaderTitleOptions;
  route: { name: string };
};

export function AccountStackHeader({
  navigation,
  options,
  route,
}: StackHeaderProps) {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const title = resolveHeaderTitle(options, route.name);

  function goBack() {
    if (navigation.canGoBack()) {
      navigation.goBack();
      return;
    }
    router.replace("/(tabs)/account");
  }

  return (
    <View
      style={[
        styles.header,
        {
          backgroundColor: colors.surface,
          borderBottomColor: colors.border,
          paddingTop: insets.top,
        },
      ]}
    >
      <View style={styles.content}>
        <Pressable
          accessibilityLabel="Quay lại Tài khoản"
          accessibilityRole="button"
          hitSlop={8}
          onPress={goBack}
          style={({ pressed }) => [
            styles.backButton,
            pressed && styles.pressed,
          ]}
        >
          <ChevronLeft color={colors.text} size={26} />
        </Pressable>
        <Text
          accessibilityRole="header"
          numberOfLines={1}
          style={[styles.title, { color: colors.text }]}
        >
          {title}
        </Text>
        <View
          accessibilityElementsHidden
          importantForAccessibility="no-hide-descendants"
          style={styles.trailingSpace}
        />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  header: { borderBottomWidth: StyleSheet.hairlineWidth },
  content: {
    alignItems: "center",
    flexDirection: "row",
    minHeight: 56,
    paddingHorizontal: 8,
  },
  backButton: {
    alignItems: "center",
    height: 44,
    justifyContent: "center",
    width: 44,
  },
  title: { flex: 1, fontSize: 17, fontWeight: "800", textAlign: "center" },
  trailingSpace: { height: 44, width: 44 },
  pressed: { opacity: 0.62 },
});
