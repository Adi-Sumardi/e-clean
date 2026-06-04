import {
  ActivityIndicator,
  Pressable,
  RefreshControl,
  ScrollView,
  Text,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { Stack, useRouter } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { useNotifications } from "@/lib/hooks";
import type { NotificationItem } from "@/lib/types";

type IoniconName = React.ComponentProps<typeof Ionicons>["name"];

const META: Record<string, { icon: IoniconName; color: string }> = {
  approval: { icon: "hourglass-outline", color: "#e08a14" },
  guest_complaint: { icon: "warning-outline", color: "#d62828" },
  report_approved: { icon: "checkmark-circle", color: "#0a7e3e" },
  report_rejected: { icon: "close-circle", color: "#d62828" },
};

function timeAgo(iso?: string): string {
  if (!iso) return "";
  const diff = Date.now() - new Date(iso).getTime();
  const m = Math.floor(diff / 60000);
  if (m < 1) return "Baru saja";
  if (m < 60) return `${m} mnt lalu`;
  const h = Math.floor(m / 60);
  if (h < 24) return `${h} jam lalu`;
  const d = Math.floor(h / 24);
  return `${d} hari lalu`;
}

export default function NotificationsScreen() {
  const router = useRouter();
  const { data, isLoading, isError, refetch, isFetching } = useNotifications();
  const items = data?.items ?? [];

  const onPressItem = (n: NotificationItem) => {
    // Approvals / complaints route the supervisor to the dashboard approval area.
    if (n.type === "approval" || n.type === "guest_complaint") {
      router.push("/(tabs)");
    }
  };

  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <SafeAreaView className="flex-1 bg-background" edges={["top"]}>
        {/* Header */}
        <View className="flex-row items-center gap-3 px-5 h-16 border-b border-surface-variant bg-surface">
          <Pressable onPress={() => router.back()} hitSlop={8} className="p-1">
            <Ionicons name="arrow-back" size={24} color="#1a1c1e" />
          </Pressable>
          <View className="flex-1">
            <Text className="text-lg font-bold text-on-surface">Notifikasi</Text>
            {items.length > 0 ? (
              <Text className="text-on-surface-variant text-xs">
                {items.length} notifikasi
              </Text>
            ) : null}
          </View>
          <Ionicons name="notifications" size={22} color="#005bbf" />
        </View>

        <ScrollView
          contentContainerStyle={{ padding: 16, paddingBottom: 40 }}
          refreshControl={
            <RefreshControl refreshing={isFetching} onRefresh={() => refetch()} />
          }
        >
          {isLoading ? (
            <View className="items-center py-20">
              <ActivityIndicator color="#005bbf" />
            </View>
          ) : isError ? (
            <View className="items-center py-20">
              <Ionicons name="cloud-offline-outline" size={48} color="#c1c6d6" />
              <Text className="text-on-surface-variant mt-3">
                Gagal memuat notifikasi.
              </Text>
            </View>
          ) : items.length === 0 ? (
            <View className="items-center py-20">
              <Ionicons
                name="notifications-off-outline"
                size={56}
                color="#c1c6d6"
              />
              <Text className="text-on-surface-variant mt-3">
                Belum ada notifikasi.
              </Text>
            </View>
          ) : (
            <View className="gap-2">
              {items.map((n) => {
                const meta = META[n.type] ?? {
                  icon: "notifications-outline" as IoniconName,
                  color: "#005bbf",
                };
                return (
                  <Pressable
                    key={n.id}
                    onPress={() => onPressItem(n)}
                    className="flex-row items-start gap-3 p-4 rounded-2xl bg-surface-container-lowest border border-outline-variant active:opacity-80"
                  >
                    <View
                      className="w-10 h-10 rounded-full items-center justify-center"
                      style={{ backgroundColor: `${meta.color}1a` }}
                    >
                      <Ionicons name={meta.icon} size={20} color={meta.color} />
                    </View>
                    <View className="flex-1">
                      <Text className="font-bold text-on-surface" numberOfLines={1}>
                        {n.title}
                      </Text>
                      <Text
                        className="text-on-surface-variant text-xs mt-0.5"
                        numberOfLines={2}
                      >
                        {n.body}
                      </Text>
                      <Text className="text-on-surface-variant text-[10px] mt-1">
                        {timeAgo(n.time)}
                      </Text>
                    </View>
                  </Pressable>
                );
              })}
            </View>
          )}
        </ScrollView>
      </SafeAreaView>
    </>
  );
}
