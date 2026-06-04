import { ScrollView, Text, View } from "react-native";

export interface BarItem {
  label: string;
  value: number;
}

interface Props {
  data: BarItem[];
  height?: number;
  color?: string;
  barWidth?: number;
  showValues?: boolean;
}

export function BarChart({
  data,
  height = 180,
  color = "#005bbf",
  barWidth = 28,
  showValues = true,
}: Props) {
  if (!data || data.length === 0) {
    return (
      <View
        className="items-center justify-center"
        style={{ height }}
      >
        <Text className="text-on-surface-variant text-sm">Tidak ada data</Text>
      </View>
    );
  }

  const max = Math.max(...data.map((d) => d.value), 1);
  const innerHeight = height - 40;

  return (
    <ScrollView
      horizontal
      showsHorizontalScrollIndicator={false}
      contentContainerStyle={{ paddingHorizontal: 8 }}
    >
      <View className="flex-row items-end gap-3" style={{ height }}>
        {data.map((d, i) => {
          const h = (d.value / max) * innerHeight;
          return (
            <View key={i} className="items-center" style={{ width: barWidth }}>
              {showValues && (
                <Text className="text-[10px] text-on-surface-variant mb-1 font-semibold">
                  {d.value}
                </Text>
              )}
              <View
                style={{
                  width: barWidth,
                  height: Math.max(h, 2),
                  backgroundColor: color,
                  borderTopLeftRadius: 6,
                  borderTopRightRadius: 6,
                }}
              />
              <Text
                className="text-[10px] text-on-surface-variant mt-1"
                numberOfLines={1}
                style={{ width: barWidth + 6, textAlign: "center" }}
              >
                {d.label}
              </Text>
            </View>
          );
        })}
      </View>
    </ScrollView>
  );
}
