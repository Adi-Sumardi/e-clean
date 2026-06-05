import { ActivityIndicator, ScrollView, Text, View } from "react-native";
import { useMemo } from "react";
import { Stack } from "expo-router";
import { Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";
import { AdminScreen } from "@/components/admin/AdminScreen";
import { useIsTablet } from "@/lib/useIsTablet";
import { useLeaderboard } from "@/lib/hooks";

interface LeaderRow {
  rank: number;
  petugas: string;
  unit: string;
  reports: number;
  avgRating: number;
  ontimePct: number;
  badge: string;
}

const MEDAL_COLORS = ["#ffd700", "#c0c0c0", "#cd7f32"];

export default function LeaderboardScreen() {
  const isTablet = useIsTablet();
  const { data, isLoading, isError } = useLeaderboard();

  const LEADERBOARD = useMemo<LeaderRow[]>(
    () =>
      (data ?? []).map((p, i) => ({
        rank: (p.rank as number) ?? i + 1,
        petugas: (p.name as string) ?? "-",
        unit: (p.unit as string) ?? "",
        reports: (p.total_reports as number) ?? 0,
        avgRating: (p.average_rating as number) ?? 0,
        ontimePct: Math.round((p.punctuality_rate as number) ?? 0),
        badge: (p.evaluation_kategori as string) ?? "",
      })),
    [data]
  );
  const top3 = LEADERBOARD.slice(0, 3);

  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <AdminScreen
        title="Peringkat Petugas"
        subtitle="Top performer bulan ini"
        icon="trophy-outline"
        color="#e08a14"
      >
        <ScrollView
          contentContainerStyle={{ padding: isTablet ? 32 : 20, paddingBottom: 40 }}
        >
          {isLoading ? (
            <View className="items-center py-16">
              <ActivityIndicator color="#e08a14" />
            </View>
          ) : isError ? (
            <Text className="text-on-surface-variant text-center py-16">
              Gagal memuat peringkat.
            </Text>
          ) : LEADERBOARD.length === 0 ? (
            <Text className="text-on-surface-variant text-center py-16">
              Belum ada data peringkat bulan ini.
            </Text>
          ) : (
          <>
          {/* Podium */}
          <View className="bg-tertiary/5 border border-tertiary/30 rounded-2xl p-5 mb-5">
            <View className="flex-row items-center gap-2 mb-4 justify-center">
              <MaterialCommunityIcons
                name="trophy-variant"
                size={20}
                color="#e08a14"
              />
              <Text className="font-bold text-tertiary">
                Podium Bulan Ini
              </Text>
            </View>
            <View className="flex-row items-end justify-center gap-3">
              {/* Rank 2 */}
              <PodiumColumn
                rank={2}
                data={top3[1]}
                heightClass="h-24"
                medalColor={MEDAL_COLORS[1]}
              />
              {/* Rank 1 */}
              <PodiumColumn
                rank={1}
                data={top3[0]}
                heightClass="h-32"
                medalColor={MEDAL_COLORS[0]}
              />
              {/* Rank 3 */}
              <PodiumColumn
                rank={3}
                data={top3[2]}
                heightClass="h-20"
                medalColor={MEDAL_COLORS[2]}
              />
            </View>
          </View>

          {/* Full ranking */}
          <Text className="font-bold text-on-surface text-base mb-3">
            Peringkat Lengkap
          </Text>
          <View className="gap-2">
            {LEADERBOARD.map((p) => {
              const isTop = p.rank <= 3;
              return (
                <View
                  key={p.rank}
                  className={`p-4 rounded-2xl border ${
                    isTop
                      ? "bg-tertiary/5 border-tertiary/30"
                      : "bg-surface-container-lowest border-outline-variant"
                  }`}
                >
                  <View className="flex-row items-center gap-3">
                    <View
                      className="w-12 h-12 rounded-xl items-center justify-center"
                      style={{
                        backgroundColor: isTop
                          ? MEDAL_COLORS[p.rank - 1]
                          : "#f3f4f6",
                      }}
                    >
                      <Text
                        className="font-bold text-base"
                        style={{ color: isTop ? "#ffffff" : "#414754" }}
                      >
                        #{p.rank}
                      </Text>
                    </View>
                    <View className="flex-1">
                      <Text className="font-bold text-on-surface" numberOfLines={1}>
                        {p.petugas}
                      </Text>
                      <View className="flex-row items-center gap-2 mt-0.5">
                        <View className="flex-row items-center gap-1">
                          <Ionicons name="business-outline" size={11} color="#5a6072" />
                          <Text className="text-on-surface-variant text-xs">
                            {p.unit}
                          </Text>
                        </View>
                        {p.badge ? (
                          <View className="px-2 py-0.5 rounded-full bg-tertiary/10">
                            <Text className="text-tertiary text-[10px] font-bold">
                              {p.badge}
                            </Text>
                          </View>
                        ) : null}
                      </View>
                    </View>
                    <View className="items-end">
                      <View className="flex-row items-center gap-1">
                        <Ionicons name="star" size={14} color="#e08a14" />
                        <Text className="text-tertiary text-base font-bold">
                          {p.avgRating}
                        </Text>
                      </View>
                      <Text className="text-on-surface-variant text-[10px]">
                        {p.reports} laporan
                      </Text>
                    </View>
                  </View>
                  <View className="flex-row items-center justify-between mt-3 pt-3 border-t border-outline-variant/50">
                    <View className="flex-row items-center gap-1">
                      <Ionicons name="checkmark-circle" size={12} color="#0a7e3e" />
                      <Text className="text-secondary text-xs font-bold">
                        {p.ontimePct}% On-Time
                      </Text>
                    </View>
                    <View className="flex-row items-center gap-1">
                      <Ionicons name="trending-up" size={12} color="#005bbf" />
                      <Text className="text-primary text-xs">Detail</Text>
                    </View>
                  </View>
                </View>
              );
            })}
          </View>
          </>
          )}
        </ScrollView>
      </AdminScreen>
    </>
  );
}

function PodiumColumn({
  rank,
  data,
  heightClass,
  medalColor,
}: {
  rank: number;
  data?: LeaderRow;
  heightClass: string;
  medalColor: string;
}) {
  return (
    <View className="flex-1 items-center">
      <View
        className="w-14 h-14 rounded-full items-center justify-center mb-2"
        style={{ backgroundColor: medalColor }}
      >
        <Text className="text-white font-bold text-base">#{rank}</Text>
      </View>
      <Text
        className="text-on-surface font-bold text-xs text-center"
        numberOfLines={1}
      >
        {data?.petugas ?? "-"}
      </Text>
      <View className="flex-row items-center gap-0.5 mb-1 mt-0.5">
        <Ionicons name="star" size={10} color="#e08a14" />
        <Text className="text-tertiary text-[11px] font-bold">
          {data?.avgRating ?? "-"}
        </Text>
      </View>
      <View
        className={`${heightClass} w-full rounded-t-xl`}
        style={{ backgroundColor: medalColor, opacity: 0.7 }}
      />
    </View>
  );
}
