import { useMemo, useState } from "react";
import { ActivityIndicator, Pressable, ScrollView, Text, View } from "react-native";
import { Stack, useRouter } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { AdminScreen, EmptyState } from "@/components/admin/AdminScreen";
import { useIsTablet } from "@/lib/useIsTablet";
import { useActivityReports } from "@/lib/hooks";

type Status = "draft" | "submitted" | "approved" | "rejected";
type Filter = "all" | Status;

const STATUS_TONE: Record<
  Status,
  { bg: string; text: string; label: string }
> = {
  draft: {
    bg: "bg-on-surface-variant/15",
    text: "text-on-surface-variant",
    label: "Draft",
  },
  submitted: { bg: "bg-tertiary/15", text: "text-tertiary", label: "Submitted" },
  approved: { bg: "bg-secondary/15", text: "text-secondary", label: "Approved" },
  rejected: { bg: "bg-error/15", text: "text-error", label: "Rejected" },
};

const FILTERS: { key: Filter; label: string }[] = [
  { key: "all", label: "Semua" },
  { key: "submitted", label: "Submitted" },
  { key: "approved", label: "Approved" },
  { key: "rejected", label: "Rejected" },
  { key: "draft", label: "Draft" },
];

function ratingColor(r: number | null | undefined) {
  if (r === null || r === undefined) return "#5a6072";
  if (r >= 4) return "#0a7e3e";
  if (r >= 3) return "#e08a14";
  return "#d62828";
}

export default function LaporanKegiatanScreen() {
  const router = useRouter();
  const isTablet = useIsTablet();
  const [q, setQ] = useState("");
  const [filter, setFilter] = useState<Filter>("all");

  const { data, isLoading } = useActivityReports({
    per_page: "all",
  });

  const reports = data ?? [];

  const filtered = useMemo(() => {
    const s = q.toLowerCase().trim();
    let list = reports;
    if (filter !== "all") list = list.filter((r) => r.status === filter);
    if (s) {
      list = list.filter(
        (r) =>
          (r.petugas?.name ?? "").toLowerCase().includes(s) ||
          (r.lokasi?.nama_lokasi ?? "").toLowerCase().includes(s) ||
          (r.kegiatan ?? "").toLowerCase().includes(s)
      );
    }
    return list;
  }, [reports, q, filter]);

  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <AdminScreen
        title="Laporan Kegiatan"
        subtitle={`${reports.length} laporan (30 hari)`}
        icon="clipboard-outline"
        color="#005bbf"
        searchValue={q}
        onSearchChange={setQ}
        searchPlaceholder="Cari petugas / lokasi / kegiatan..."
      >
        {/* Filter pills */}
        <View className="border-b border-surface-variant bg-surface">
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={{
              paddingHorizontal: isTablet ? 32 : 20,
              paddingVertical: 12,
            }}
          >
            {FILTERS.map((f, idx) => {
              const active = filter === f.key;
              return (
                <Pressable
                  key={f.key}
                  onPress={() => setFilter(f.key)}
                  style={{
                    height: 36,
                    marginRight: idx === FILTERS.length - 1 ? 0 : 8,
                    paddingHorizontal: 14,
                    borderRadius: 999,
                    borderWidth: 1,
                    justifyContent: "center",
                    borderColor: active ? "#005bbf" : "#e1e3e4",
                    backgroundColor: active ? "#005bbf" : "#ffffff",
                  }}
                >
                  <Text
                    style={{
                      fontSize: 13,
                      fontWeight: "600",
                      lineHeight: 18,
                      color: active ? "#ffffff" : "#1a1c1e",
                    }}
                  >
                    {f.label}
                  </Text>
                </Pressable>
              );
            })}
          </ScrollView>
        </View>

        <ScrollView
          contentContainerStyle={{ padding: isTablet ? 32 : 20, paddingBottom: 40 }}
        >
          {isLoading ? (
            <View className="items-center py-20">
              <ActivityIndicator size="large" color="#005bbf" />
            </View>
          ) : filtered.length === 0 ? (
            <EmptyState icon="search-outline" title="Tidak ada laporan" />
          ) : (
            <View className={isTablet ? "flex-row flex-wrap -m-2" : "gap-3"}>
              {filtered.map((r) => {
                const sTone = STATUS_TONE[r.status as Status] ?? STATUS_TONE.draft;
                return (
                  <View key={r.id} className={isTablet ? "w-1/2 p-2" : ""}>
                    <Pressable
                      onPress={() =>
                        router.push({
                          pathname: "/admin/laporan-detail",
                          params: {
                            id: r.id,
                            scope: "kebersihan",
                            petugas: r.petugas?.name ?? "-",
                            lokasi: r.lokasi?.nama_lokasi ?? "-",
                            unit: r.lokasi?.unit?.nama_unit ?? "-",
                            tanggal: r.tanggal,
                            summary: r.kegiatan,
                            status: r.status,
                          },
                        })
                      }
                      className="p-4 rounded-2xl bg-surface-container-lowest border border-outline-variant active:opacity-80"
                    >
                      <View className="flex-row items-start gap-3">
                        <View className="w-10 h-10 rounded-full bg-primary/10 items-center justify-center">
                          <Ionicons name="person" size={18} color="#005bbf" />
                        </View>
                        <View className="flex-1">
                          <View className="flex-row items-center justify-between">
                            <Text
                              className="font-bold text-on-surface"
                              numberOfLines={1}
                            >
                              {r.petugas?.name ?? "-"}
                            </Text>
                            <View className={`px-2 py-0.5 rounded-full ${sTone.bg}`}>
                              <Text
                                className={`text-[10px] font-bold ${sTone.text}`}
                              >
                                {sTone.label}
                              </Text>
                            </View>
                          </View>
                          <Text
                            className="text-on-surface-variant text-xs"
                            numberOfLines={1}
                          >
                            {r.lokasi?.nama_lokasi ?? "-"} · {r.lokasi?.unit?.nama_unit ?? "-"}
                          </Text>
                          <Text
                            className="text-on-surface-variant text-xs mt-1"
                            numberOfLines={2}
                          >
                            {r.kegiatan}
                          </Text>
                        </View>
                      </View>
                      <View className="flex-row items-center justify-between mt-3 pt-3 border-t border-outline-variant/50">
                        <View className="flex-row items-center gap-2">
                          <Text className="text-on-surface-variant text-[10px]">
                            {r.tanggal}
                          </Text>
                        </View>
                        <View className="flex-row items-center gap-1">
                          <Ionicons
                            name="star"
                            size={12}
                            color={ratingColor(r.rating)}
                          />
                          <Text
                            className="text-xs font-bold"
                            style={{ color: ratingColor(r.rating) }}
                          >
                            {r.rating !== null && r.rating !== undefined ? `${r.rating}/5` : "N/A"}
                          </Text>
                        </View>
                      </View>
                    </Pressable>
                  </View>
                );
              })}
            </View>
          )}
        </ScrollView>
      </AdminScreen>
    </>
  );
}
