import { useState } from "react";
import { Stack } from "expo-router";
import { QueryClientProvider } from "@tanstack/react-query";
import { StatusBar } from "expo-status-bar";
import { createAppQueryClient } from "@/api/queryClient";
import { AuthProvider } from "@/auth/AuthProvider";
import { IosPayoutFeaturesProvider } from "@/config/features";
import { ThemeProvider, useTheme } from "@/theme/ThemeProvider";

function ThemedStatusBar() {
  const { scheme } = useTheme();
  return <StatusBar style={scheme === "dark" ? "light" : "dark"} />;
}

export default function RootLayout() {
  const [queryClient] = useState(createAppQueryClient);

  return (
    <QueryClientProvider client={queryClient}>
      <ThemeProvider>
        <IosPayoutFeaturesProvider>
          <AuthProvider>
            <ThemedStatusBar />
            <Stack screenOptions={{ headerShown: false }}>
              <Stack.Screen name="(tabs)" />
            </Stack>
          </AuthProvider>
        </IosPayoutFeaturesProvider>
      </ThemeProvider>
    </QueryClientProvider>
  );
}
