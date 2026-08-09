import { ActivityIndicator, StyleSheet, View } from "react-native";
import { Redirect } from "expo-router";
import { useAuth } from "@/auth/AuthProvider";
import { colors } from "@/theme/tokens";

export default function Index() {
  const { isLoading, session } = useAuth();
  if (isLoading) return <View style={styles.loading}><ActivityIndicator color={colors.primary} /></View>;
  return <Redirect href={session ? "/home" : "/login"} />;
}

const styles = StyleSheet.create({ loading: { flex: 1, alignItems: "center", justifyContent: "center", backgroundColor: colors.background } });
