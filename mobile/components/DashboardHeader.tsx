import { ReactNode } from "react";
import { Text, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { LinearGradient } from "expo-linear-gradient";

/**
 * Shared gradient header for the role dashboards — matches the login design
 * language (gradient + glass accents). `icon` is rendered in a translucent
 * circle; `right` holds actions (e.g. the notification bell).
 */
export function DashboardHeader({
  colors,
  title,
  subtitle,
  icon,
  right,
}: {
  colors: [string, string];
  title: string;
  subtitle?: string;
  icon: ReactNode;
  right?: ReactNode;
}) {
  return (
    <LinearGradient
      colors={colors}
      start={{ x: 0, y: 0 }}
      end={{ x: 1, y: 1 }}
      style={{
        borderBottomLeftRadius: 26,
        borderBottomRightRadius: 26,
        overflow: "hidden",
      }}
    >
      <View
        style={{
          position: "absolute",
          top: -50,
          right: -30,
          width: 150,
          height: 150,
          borderRadius: 75,
          backgroundColor: "rgba(255,255,255,0.08)",
        }}
      />
      <SafeAreaView edges={["top"]}>
        <View className="flex-row items-center justify-between px-5 h-16">
          <View className="flex-row items-center gap-3 flex-1">
            <View
              className="w-11 h-11 rounded-2xl items-center justify-center"
              style={{ backgroundColor: "rgba(255,255,255,0.2)" }}
            >
              {icon}
            </View>
            <View className="flex-1">
              <Text className="text-white font-bold text-lg" numberOfLines={1}>
                {title}
              </Text>
              {subtitle ? (
                <Text className="text-white/75 text-xs" numberOfLines={1}>
                  {subtitle}
                </Text>
              ) : null}
            </View>
          </View>
          {right}
        </View>
      </SafeAreaView>
    </LinearGradient>
  );
}
