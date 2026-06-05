import { Pressable, Text, View } from "react-native";
import { useRouter } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { useNotifications } from "@/lib/hooks";
import { useNotifStore } from "@/stores/notif-store";

/**
 * Bell icon used on every dashboard header. Shows a live UNREAD count badge
 * (notifications not yet opened) and opens the notifications screen on tap.
 */
export function NotificationBell({
  size = 22,
  color = "#414754",
}: {
  size?: number;
  color?: string;
}) {
  const router = useRouter();
  const { data } = useNotifications();
  const seen = useNotifStore((s) => s.seen);
  const items = data?.items ?? [];
  const count = items.reduce((n, i) => (seen[i.id] ? n : n + 1), 0);

  return (
    <Pressable
      onPress={() => router.push("/notifications")}
      className="w-11 h-11 rounded-full items-center justify-center active:bg-surface-container-high"
    >
      <View>
        <Ionicons name="notifications-outline" size={size} color={color} />
        {count > 0 ? (
          <View className="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 rounded-full bg-error items-center justify-center">
            <Text className="text-white text-[9px] font-bold">
              {count > 99 ? "99+" : count}
            </Text>
          </View>
        ) : null}
      </View>
    </Pressable>
  );
}
