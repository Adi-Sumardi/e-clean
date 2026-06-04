import { useMemo, useState } from "react";
import { Pressable, ScrollView, Text, View } from "react-native";
import { Stack } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { AdminScreen, EmptyState } from "@/components/admin/AdminScreen";
import { useIsTablet } from "@/lib/useIsTablet";

interface LateRow {
  id: number;
  tanggal: string;
  petugas: string;
  lokasi: string;
  unit: string;
  jadwal: string;
  terlambat: number;
  alasan: string;
  status: "ditangani" | "pending";
}

const LATE_REPORTS: LateRow[] = [
  {
    id: 1,
    tanggal: "02 Jun 2026",
    petugas: "Budi Hartono",
    lokasi: "Ruang Rapat Besar",
    unit: "Office",
    jadwal: "13:00",
    terlambat: 25,
    alasan: "Menangani permintaan tamu mendesak",
    status: "ditangani",
  },
  {
    id: 2,
    tanggal: "02 Jun 2026",
    petugas: "Eko Prasetyo",
    lokasi: "Pantry Lt.2",
    unit: "Office",
    jadwal: "10:00",
    terlambat: 45,
    alasan: "-",
    status: "pending",
  },
  {
    id: 3,
    tanggal: "01 Jun 2026",
    petugas: "Citra Wijaya",
    lokasi: "Toilet Lt.3",
    unit: "Office",
    jadwal: "15:00",
    terlambat: 18,
    alasan: "Tunggu petugas lain selesai briefing",
    status: "ditangani",
  },
  {
    id: 4,
    tanggal: "31 Mei 2026",
    petugas: "Andi Setiawan",
    lokasi: "Lobi Utama",
    unit: "Office",
    jadwal: "08:00",
    terlambat: 12,
    alasan: "-",
    status: "pending",
  },
];

function severityColor(min: number) {
  if (min >= 30) return "#d62828";
  if (min >= 15) return "#e08a14";
  return "#0891b2";
}

export default function LaporanKeterlambatanScreen() {
  const isTablet = useIsTablet();
  const [q, setQ] = useState("");

  const stats = useMemo(
    () => ({
      total: LATE_REPORTS.length,
      pending: LATE_REPORTS.filter((r) => r.status === "pending").length,
      avgLate: Math.round(
        LATE_REPORTS.reduce((sum, r) => sum + r.terlambat, 0) /
          LATE_REPORTS.length
      ),
    }),
    []
  );

  const filtered = useMemo(() => {
    const s = q.toLowerCase().trim();
    if (!s) return LATE_REPORTS;
    return LATE_REPORTS.filter(
      (r) =>
        r.petugas.toLowerCase().includes(s) ||
        r.lokasi.toLowerCase().includes(s)
    );
  }, [q]);

  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <AdminScreen
        title="Laporan Keterlambatan"
        subtitle={`${stats.total} kejadian · rata-rata telat ${stats.avgLate} menit`}
        icon="time-outline"
        color="#e08a14"
        searchValue={q}
        onSearchChange={setQ}
        searchPlaceholder="Cari petugas / lokasi..."
      >
        <ScrollView
          contentContainerStyle={{ padding: isTablet ? 32 : 20, paddingBottom: 40 }}
        >
          {/* Summary banner */}
          <View className="flex-row gap-3 mb-5">
            <View className="flex-1 p-4 rounded-2xl bg-error/10 border border-error/30">
              <Text className="text-error text-xs font-bold">PENDING</Text>
              <Text className="text-error text-2xl font-bold mt-1">
                {stats.pending}
              </Text>
              <Text className="text-error/80 text-[10px] mt-1">
                Belum ditangani
              </Text>
            </View>
            <View className="flex-1 p-4 rounded-2xl bg-tertiary/10 border border-tertiary/30">
              <Text className="text-tertiary text-xs font-bold">RATA-RATA</Text>
              <Text className="text-tertiary text-2xl font-bold mt-1">
                {stats.avgLate}m
              </Text>
              <Text className="text-tertiary/80 text-[10px] mt-1">
                Telat per kejadian
              </Text>
            </View>
            <View className="flex-1 p-4 rounded-2xl bg-primary/10 border border-primary/30">
              <Text className="text-primary text-xs font-bold">TOTAL</Text>
              <Text className="text-primary text-2xl font-bold mt-1">
                {stats.total}
              </Text>
              <Text className="text-primary/80 text-[10px] mt-1">
                30 hari terakhir
              </Text>
            </View>
          </View>

          {filtered.length === 0 ? (
            <EmptyState
              icon="checkmark-done-circle-outline"
              title="Tidak ada keterlambatan"
              description="Semua jadwal berjalan sesuai rencana"
            />
          ) : (
            <View className={isTablet ? "flex-row flex-wrap -m-2" : "gap-3"}>
              {filtered.map((r) => {
                const sevColor = severityColor(r.terlambat);
                const isPending = r.status === "pending";
                return (
                  <View key={r.id} className={isTablet ? "w-1/2 p-2" : ""}>
                    <Pressable className="p-4 rounded-2xl bg-surface-container-lowest border border-outline-variant active:opacity-80">
                      <View className="flex-row items-start gap-3">
                        <View
                          className="w-12 h-12 rounded-xl items-center justify-center"
                          style={{ backgroundColor: `${sevColor}1a` }}
                        >
                          <Ionicons name="alarm" size={22} color={sevColor} />
                        </View>
                        <View className="flex-1">
                          <View className="flex-row items-center justify-between">
                            <Text
                              className="font-bold text-on-surface"
                              numberOfLines={1}
                            >
                              {r.petugas}
                            </Text>
                            <View
                              className="px-2 py-0.5 rounded-full"
                              style={{ backgroundColor: `${sevColor}1a` }}
                            >
                              <Text
                                className="text-[10px] font-bold"
                                style={{ color: sevColor }}
                              >
                                +{r.terlambat} menit
                              </Text>
                            </View>
                          </View>
                          <View className="flex-row items-center gap-1 mt-0.5">
                            <Ionicons
                              name="location-outline"
                              size={11}
                              color="#5a6072"
                            />
                            <Text
                              className="text-on-surface-variant text-xs flex-1"
                              numberOfLines={1}
                            >
                              {r.lokasi}
                            </Text>
                          </View>
                          <View className="flex-row items-center gap-2 mt-1">
                            <View className="flex-row items-center gap-1">
                              <Ionicons
                                name="time-outline"
                                size={11}
                                color="#5a6072"
                              />
                              <Text className="text-on-surface-variant text-[11px]">
                                Jadwal {r.jadwal}
                              </Text>
                            </View>
                            <Text className="text-on-surface-variant text-[11px]">
                              · {r.tanggal}
                            </Text>
                          </View>
                          {r.alasan !== "-" && (
                            <Text
                              className="text-on-surface-variant text-xs mt-1 italic"
                              numberOfLines={2}
                            >
                              "{r.alasan}"
                            </Text>
                          )}
                        </View>
                      </View>
                      <View className="flex-row items-center justify-between mt-3 pt-3 border-t border-outline-variant/50">
                        <View
                          className={`px-2 py-0.5 rounded-full ${
                            isPending ? "bg-error/15" : "bg-secondary/15"
                          }`}
                        >
                          <Text
                            className={`text-[10px] font-bold ${
                              isPending ? "text-error" : "text-secondary"
                            }`}
                          >
                            {isPending ? "● Pending" : "✓ Ditangani"}
                          </Text>
                        </View>
                        {isPending && (
                          <Pressable className="px-3 py-1 rounded-lg bg-primary active:opacity-80">
                            <Text className="text-white text-[11px] font-bold">
                              Tinjau
                            </Text>
                          </Pressable>
                        )}
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
