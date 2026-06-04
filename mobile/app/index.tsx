import { Redirect } from "expo-router";
import { ActivityIndicator, View } from "react-native";
import { useAuthStore } from "@/stores/auth-store";

export default function Index() {
  const status = useAuthStore((s) => s.status);

  if (status === "idle" || status === "loading") {
    return (
      <View className="flex-1 items-center justify-center bg-background">
        <ActivityIndicator color="#005bbf" size="large" />
      </View>
    );
  }

  return status === "authenticated" ? (
    <Redirect href="/(tabs)" />
  ) : (
    <Redirect href="/(auth)/login" />
  );
}
