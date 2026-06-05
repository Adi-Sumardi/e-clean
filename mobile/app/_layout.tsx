import "../global.css";
import { useEffect, useState, useCallback } from "react";
import { AppState } from "react-native";
import { GestureHandlerRootView } from "react-native-gesture-handler";
import {
  Slot,
  useRouter,
  useSegments,
  useRootNavigationState,
} from "expo-router";
import { StatusBar } from "expo-status-bar";
import { SafeAreaProvider } from "react-native-safe-area-context";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { useAuthStore } from "@/stores/auth-store";
import { useNotifStore } from "@/stores/notif-store";
import { syncQueue } from "@/lib/offline-queue";
import { useFonts } from "expo-font";
import { Ionicons, MaterialCommunityIcons, FontAwesome } from "@expo/vector-icons";
import AnimatedSplash from "@/components/AnimatedSplash";

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      staleTime: 5 * 60 * 1000, // 5 minutes
      gcTime: 10 * 60 * 1000,    // 10 minutes cache time
      refetchOnWindowFocus: false,
    },
  },
});

function RouterGate() {
  const status = useAuthStore((s) => s.status);
  const segments = useSegments();
  const router = useRouter();
  const navState = useRootNavigationState();

  useEffect(() => {
    // Wait until the root navigator is fully mounted to avoid
    // "Attempted to navigate before mounting the Root Layout" error.
    if (!navState?.key) return;
    if (status === "idle" || status === "loading") return;
    const inAuthGroup = segments[0] === "(auth)";
    if (status === "unauthenticated" && !inAuthGroup) {
      router.replace("/(auth)/login");
    } else if (status === "authenticated" && inAuthGroup) {
      router.replace("/(tabs)");
    }
  }, [navState?.key, status, segments]);

  return <Slot />;
}

export default function RootLayout() {
  const hydrate = useAuthStore((s) => s.hydrate);
  const status = useAuthStore((s) => s.status);
  
  const [fontsLoaded, fontError] = useFonts({
    ...Ionicons.font,
    ...MaterialCommunityIcons.font,
    ...FontAwesome.font,
  });

  const [animationFinished, setAnimationFinished] = useState(false);

  useEffect(() => {
    hydrate();
    useNotifStore.getState().hydrate();
  }, [hydrate]);

  // Log font error if any
  useEffect(() => {
    if (fontError) {
      console.warn("Failed to load fonts:", fontError);
    }
  }, [fontError]);

  // Flush any queued offline report submissions on launch and whenever the
  // app returns to the foreground (best-effort; safe when there's nothing).
  useEffect(() => {
    if (status !== "authenticated") return;
    void syncQueue();
    const sub = AppState.addEventListener("change", (state) => {
      if (state === "active") void syncQueue();
    });
    return () => sub.remove();
  }, [status]);

  const handleSplashFinish = useCallback(() => {
    setAnimationFinished(true);
  }, []);

  const showActiveSplash = (!fontsLoaded && !fontError) || !animationFinished;

  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <QueryClientProvider client={queryClient}>
        <SafeAreaProvider>
          <StatusBar style="dark" />
          <RouterGate />
          {showActiveSplash && <AnimatedSplash onFinish={handleSplashFinish} />}
        </SafeAreaProvider>
      </QueryClientProvider>
    </GestureHandlerRootView>
  );
}

