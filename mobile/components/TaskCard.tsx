import { Pressable, Text, View } from "react-native";
import { Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";

export interface TaskItem {
  id: number | string;
  title: string;
  location?: string;
  time?: string;
  status: "pending" | "in_progress" | "done";
}

interface Props {
  task: TaskItem;
  onPressReport?: () => void;
  onPressFinish?: () => void;
  compact?: boolean;
}

export function TaskCard({ task, onPressReport, onPressFinish, compact }: Props) {
  const isDone = task.status === "done";
  const iconSize = compact ? 18 : 20;

  return (
    <View
      className={`p-4 rounded-2xl border-2 shadow-sm ${
        isDone
          ? "bg-secondary/5 border-secondary/30"
          : "bg-surface-container-lowest border-outline-variant"
      }`}
    >
      <View className="flex-row items-start gap-3">
        <View
          className={`w-11 h-11 rounded-full items-center justify-center ${
            isDone ? "bg-secondary" : "bg-surface-container"
          }`}
        >
          {isDone ? (
            <Ionicons name="checkmark" size={22} color="#ffffff" />
          ) : (
            <MaterialCommunityIcons
              name="shield-account"
              size={22}
              color="#005bbf"
            />
          )}
        </View>
        <View className="flex-1">
          <Text
            className={`font-bold text-base text-on-surface ${
              isDone ? "line-through opacity-60" : ""
            }`}
          >
            {task.title}
          </Text>
          {task.location ? (
            <View className="flex-row items-center gap-1 mt-1">
              <Ionicons
                name="location-outline"
                size={iconSize - 4}
                color="#5a6072"
              />
              <Text className="text-on-surface-variant text-sm">
                {task.location}
              </Text>
            </View>
          ) : null}
          {task.time ? (
            <View className="flex-row items-center gap-1 mt-0.5">
              <Ionicons
                name="time-outline"
                size={iconSize - 6}
                color="#5a6072"
              />
              <Text className="text-on-surface-variant text-xs">
                {task.time}
              </Text>
            </View>
          ) : null}
        </View>
      </View>

      {!isDone && (
        <View className="flex-row gap-2 mt-4">
          <Pressable
            onPress={onPressReport}
            className="px-4 h-11 rounded-xl bg-surface-container-highest items-center justify-center active:opacity-80"
          >
            <Ionicons name="camera-outline" size={20} color="#414754" />
          </Pressable>
          <Pressable
            onPress={onPressFinish}
            className="flex-1 h-11 rounded-xl bg-primary items-center justify-center active:opacity-90 flex-row gap-2"
          >
            <Ionicons name="checkmark-circle-outline" size={18} color="#ffffff" />
            <Text className="text-white font-bold">Selesaikan</Text>
          </Pressable>
        </View>
      )}
      {isDone && (
        <View className="mt-4 h-11 rounded-xl bg-secondary items-center justify-center flex-row gap-2">
          <Ionicons name="checkmark-done" size={18} color="#ffffff" />
          <Text className="text-white font-bold">Selesai</Text>
        </View>
      )}
    </View>
  );
}
