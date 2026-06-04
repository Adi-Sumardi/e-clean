import { Text, View } from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { Sparkline } from "@/components/charts/Sparkline";

type IoniconName = React.ComponentProps<typeof Ionicons>["name"];

interface Props {
  label: string;
  value: string | number;
  hint?: string;
  tone?: "primary" | "secondary" | "tertiary" | "error" | "warning" | "info";
  icon?: IoniconName;
  trend?: number[];
}

const toneMap: Record<
  NonNullable<Props["tone"]>,
  { bg: string; text: string; color: string }
> = {
  primary: { bg: "bg-primary/10", text: "text-primary", color: "#005bbf" },
  secondary: {
    bg: "bg-secondary/10",
    text: "text-secondary",
    color: "#0a7e3e",
  },
  tertiary: { bg: "bg-tertiary/10", text: "text-tertiary", color: "#7e5a17" },
  error: { bg: "bg-error/10", text: "text-error", color: "#d62828" },
  warning: { bg: "bg-tertiary/10", text: "text-tertiary", color: "#e08a14" },
  info: { bg: "bg-primary/10", text: "text-primary", color: "#0891b2" },
};

export function StatCard({
  label,
  value,
  hint,
  tone = "primary",
  icon,
  trend,
}: Props) {
  const t = toneMap[tone];
  return (
    <View className="flex-1 p-4 rounded-2xl border border-outline-variant bg-surface-container-lowest">
      <View className="flex-row items-center gap-2 mb-2">
        <View
          className={`px-2 py-1 rounded-full ${t.bg} flex-row items-center gap-1`}
        >
          {icon ? <Ionicons name={icon} size={12} color={t.color} /> : null}
          <Text className={`text-xs font-bold ${t.text}`}>{label}</Text>
        </View>
      </View>
      <Text className="text-2xl font-bold text-on-surface">{value}</Text>
      {hint ? (
        <Text className="text-xs text-on-surface-variant mt-1">{hint}</Text>
      ) : null}
      {trend && trend.length > 0 ? (
        <View className="mt-2 -mx-1">
          <Sparkline data={trend} color={t.color} height={36} width={140} />
        </View>
      ) : null}
    </View>
  );
}
