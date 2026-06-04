import { useMemo, useState } from "react";
import {
  ActivityIndicator,
  Pressable,
  ScrollView,
  Text,
  View,
} from "react-native";
import { Stack, useRouter } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { AdminScreen, EmptyState } from "@/components/admin/AdminScreen";
import { useIsTablet } from "@/lib/useIsTablet";
import { usePenilaianList } from "@/lib/hooks";

const MONTHS = [
  "", "Jan", "Feb", "Mar", "Apr", "Mei", "Jun",
  "Jul", "Agu", "Sep", "Okt", "Nov", "Des",
];

interface PenilaianRow {
  id: number;
  petugas: string;
  lokasi: string;
  tanggal: string;
  rating: number;
  reviewer: string;
  catatan?: string;
}

function ratingColor(r: number) {
  if (r >= 4) return "#0a7e3e";
  if (r >= 3) return "#e08a14";
  return "#d62828";
}

export default function PenilaianScreen() {
  const router = useRouter();
  const isTablet = useIsTablet();
  const [q, setQ] = useState("");
  const { data, isLoading } = usePenilaianList();

  // Monthly performance evaluations: rata_rata is 0-100, shown here as a /5
  // star rating (rata_rata / 20).
  const RATINGS = useMemo<PenilaianRow[]>(
    () =>
      (data ?? []).map((p) => ({
        id: p.id,
        petugas: p.petugas?.name ?? "-",
        lokasi: `Periode ${MONTHS[p.periode_bulan] ?? p.periode_bulan} ${p.periode_tahun}`,
        tanggal: p.kategori ?? "",
        rating: Math.max(1, Math.round((p.rata_rata ?? 0) / 20)),
        reviewer: p.penilai?.name ?? "Supervisor",
        catatan: p.catatan ?? undefined,
      })),
    [data]
  );

  const stats = useMemo(() => {
    const total = RATINGS.length || 1;
    const sum = RATINGS.reduce((s, r) => s + r.rating, 0);
    const avg = (sum / total).toFixed(2);
    const dist = [5, 4, 3, 2, 1].map((star) => ({
      star,
      count: RATINGS.filter((r) => r.rating === star).length,
    }));
    return { total: RATINGS.length, avg, dist };
  }, [RATINGS]);

  const filtered = useMemo(() => {
    const s = q.toLowerCase().trim();
    if (!s) return RATINGS;
    return RATINGS.filter(
      (r) =>
        r.petugas.toLowerCase().includes(s) ||
        r.lokasi.toLowerCase().includes(s)
    );
  }, [q, RATINGS]);

  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <AdminScreen
        title="Penilaian"
        subtitle={`${stats.total} penilaian · rata-rata ${stats.avg}/5`}
        icon="star-outline"
        color="#e08a14"
        searchValue={q}
        onSearchChange={setQ}
        searchPlaceholder="Cari petugas / lokasi..."
        onAdd={() => router.push("/admin/beri-penilaian")}
        addLabel="Nilai"
      >
        <ScrollView
          contentContainerStyle={{ padding: isTablet ? 32 : 20, paddingBottom: 40 }}
        >
          {/* Summary */}
          <View className="bg-tertiary/5 border border-tertiary/30 rounded-2xl p-4 mb-5 flex-row items-center gap-4">
            <View className="items-center">
              <Text className="text-tertiary text-4xl font-bold">
                {stats.avg}
              </Text>
              <View className="flex-row items-center gap-0.5 mt-1">
                {[1, 2, 3, 4, 5].map((i) => (
                  <Ionicons
                    key={i}
                    name="star"
                    size={14}
                    color={i <= Math.round(Number(stats.avg)) ? "#e08a14" : "#c1c6d6"}
                  />
                ))}
              </View>
              <Text className="text-tertiary text-xs mt-1">
                dari {stats.total} penilaian
              </Text>
            </View>
            <View className="flex-1 gap-1">
              {stats.dist.map((d) => {
                const pct = (d.count / stats.total) * 100;
                return (
                  <View key={d.star} className="flex-row items-center gap-2">
                    <View className="flex-row items-center gap-0.5 w-12">
                      <Text className="text-on-surface text-xs font-bold">
                        {d.star}
                      </Text>
                      <Ionicons name="star" size={10} color="#e08a14" />
                    </View>
                    <View className="flex-1 h-2 bg-on-surface-variant/10 rounded-full overflow-hidden">
                      <View
                        className="h-full bg-tertiary"
                        style={{ width: `${pct}%` }}
                      />
                    </View>
                    <Text className="text-on-surface-variant text-[10px] w-6 text-right">
                      {d.count}
                    </Text>
                  </View>
                );
              })}
            </View>
          </View>

          {isLoading ? (
            <View className="items-center py-10">
              <ActivityIndicator color="#e08a14" />
            </View>
          ) : filtered.length === 0 ? (
            <EmptyState icon="star-outline" title="Belum ada penilaian" />
          ) : (
            <View className="gap-3">
              {filtered.map((r) => {
                const color = ratingColor(r.rating);
                return (
                  <Pressable
                    key={r.id}
                    className="p-4 rounded-2xl bg-surface-container-lowest border border-outline-variant active:opacity-80"
                  >
                    <View className="flex-row items-center gap-3">
                      <View className="w-10 h-10 rounded-full bg-tertiary/15 items-center justify-center">
                        <Text className="text-tertiary font-bold">
                          {r.petugas.charAt(0)}
                        </Text>
                      </View>
                      <View className="flex-1">
                        <Text className="font-bold text-on-surface" numberOfLines={1}>
                          {r.petugas}
                        </Text>
                        <Text
                          className="text-on-surface-variant text-xs"
                          numberOfLines={1}
                        >
                          {r.lokasi} · {r.tanggal}
                        </Text>
                      </View>
                      <View className="items-end">
                        <View className="flex-row items-center gap-0.5">
                          {[1, 2, 3, 4, 5].map((i) => (
                            <Ionicons
                              key={i}
                              name="star"
                              size={14}
                              color={i <= r.rating ? color : "#e1e3e4"}
                            />
                          ))}
                        </View>
                        <Text
                          className="text-xs font-bold mt-0.5"
                          style={{ color }}
                        >
                          {r.rating}/5
                        </Text>
                      </View>
                    </View>
                    {r.catatan ? (
                      <View className="mt-3 p-3 rounded-xl bg-surface flex-row items-start gap-2">
                        <Ionicons
                          name="chatbubble-outline"
                          size={14}
                          color="#5a6072"
                        />
                        <Text
                          className="flex-1 text-on-surface-variant text-xs italic"
                          numberOfLines={3}
                        >
                          "{r.catatan}" — {r.reviewer}
                        </Text>
                      </View>
                    ) : null}
                  </Pressable>
                );
              })}
            </View>
          )}
        </ScrollView>
      </AdminScreen>
    </>
  );
}
