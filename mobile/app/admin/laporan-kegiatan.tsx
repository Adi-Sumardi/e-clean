import { useMemo, useState } from "react";
import { Pressable, ScrollView, Text, View } from "react-native";
import { Stack, useRouter } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { AdminScreen, EmptyState } from "@/components/admin/AdminScreen";
import { useIsTablet } from "@/lib/useIsTablet";

type Status = "draft" | "submitted" | "approved" | "rejected";
type Filter = "all" | Status;

interface ReportRow {
  id: number;
  tanggal: string;
  petugas: string;
  lokasi: string;
  unit: string;
  kegiatan: string;
  status: Status;
  rating: number | null;
  reportingStatus: "ontime" | "late" | "expired";
}

const REPORTS: ReportRow[] = [
  {
    id: 1,
    tanggal: "02 Jun 2026",
    petugas: "Rahmat Hidayat",
    lokasi: "Toilet Lt.1 - Gedung A",
    unit: "Office",
    kegiatan: "Pembersihan rutin pagi",
    status: "approved",
    rating: 5,
    reportingStatus: "ontime",
  },
  {
    id: 2,
    tanggal: "02 Jun 2026",
    petugas: "Siti Nurhaliza",
    lokasi: "Lobi Utama",
    unit: "Office",
    kegiatan: "Mopping & dusting",
    status: "submitted",
    rating: null,
    reportingStatus: "ontime",
  },
  {
    id: 3,
    tanggal: "02 Jun 2026",
    petugas: "Andi Setiawan",
    lokasi: "Pantry Lt.2",
    unit: "Office",
    kegiatan: "Pembersihan setelah lunch",
    status: "approved",
    rating: 4,
    reportingStatus: "late",
  },
  {
    id: 4,
    tanggal: "01 Jun 2026",
    petugas: "Budi Hartono",
    lokasi: "Ruang Rapat Besar",
    unit: "Office",
    kegiatan: "Setup + pembersihan ruangan",
    status: "approved",
    rating: 5,
    reportingStatus: "ontime",
  },
  {
    id: 5,
    tanggal: "01 Jun 2026",
    petugas: "Citra Wijaya",
    lokasi: "Toilet Lt.3 - Gedung B",
    unit: "Office",
    kegiatan: "Penanganan tumpahan",
    status: "rejected",
    rating: 2,
    reportingStatus: "late",
  },
  {
    id: 6,
    tanggal: "01 Jun 2026",
    petugas: "Eko Prasetyo",
    lokasi: "Lobi Toko",
    unit: "Toko",
    kegiatan: "Sweeping + display cleaning",
    status: "draft",
    rating: null,
    reportingStatus: "ontime",
  },
];

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

const REPORTING_TONE: Record<
  ReportRow["reportingStatus"],
  { bg: string; text: string; label: string }
> = {
  ontime: {
    bg: "bg-secondary/15",
    text: "text-secondary",
    label: "On-Time",
  },
  late: { bg: "bg-tertiary/15", text: "text-tertiary", label: "Telat" },
  expired: { bg: "bg-error/15", text: "text-error", label: "Tidak Lapor" },
};

const FILTERS: { key: Filter; label: string }[] = [
  { key: "all", label: "Semua" },
  { key: "submitted", label: "Submitted" },
  { key: "approved", label: "Approved" },
  { key: "rejected", label: "Rejected" },
  { key: "draft", label: "Draft" },
];

function ratingColor(r: number | null) {
  if (r === null) return "#5a6072";
  if (r >= 4) return "#0a7e3e";
  if (r >= 3) return "#e08a14";
  return "#d62828";
}

export default function LaporanKegiatanScreen() {
  const router = useRouter();
  const isTablet = useIsTablet();
  const [q, setQ] = useState("");
  const [filter, setFilter] = useState<Filter>("all");

  const filtered = useMemo(() => {
    const s = q.toLowerCase().trim();
    let list = REPORTS;
    if (filter !== "all") list = list.filter((r) => r.status === filter);
    if (s) {
      list = list.filter(
        (r) =>
          r.petugas.toLowerCase().includes(s) ||
          r.lokasi.toLowerCase().includes(s) ||
          r.kegiatan.toLowerCase().includes(s)
      );
    }
    return list;
  }, [q, filter]);

  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <AdminScreen
        title="Laporan Kegiatan"
        subtitle={`${REPORTS.length} laporan (30 hari)`}
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
          {filtered.length === 0 ? (
            <EmptyState icon="search-outline" title="Tidak ada laporan" />
          ) : (
            <View className={isTablet ? "flex-row flex-wrap -m-2" : "gap-3"}>
              {filtered.map((r) => {
                const sTone = STATUS_TONE[r.status];
                const rTone = REPORTING_TONE[r.reportingStatus];
                return (
                  <View key={r.id} className={isTablet ? "w-1/2 p-2" : ""}>
                    <Pressable
                      onPress={() =>
                        router.push({
                          pathname: "/admin/laporan-detail",
                          params: { id: r.id },
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
                              {r.petugas}
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
                            {r.lokasi} · {r.unit}
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
                          <View
                            className={`px-2 py-0.5 rounded-full ${rTone.bg}`}
                          >
                            <Text
                              className={`text-[10px] font-bold ${rTone.text}`}
                            >
                              {rTone.label}
                            </Text>
                          </View>
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
                            {r.rating !== null ? `${r.rating}/5` : "N/A"}
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
