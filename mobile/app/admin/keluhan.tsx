import { useMemo, useState } from "react";
import { Pressable, ScrollView, Text, View } from "react-native";
import { Stack } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { AdminScreen, EmptyState } from "@/components/admin/AdminScreen";
import { useIsTablet } from "@/lib/useIsTablet";

type Status = "pending" | "in_progress" | "resolved" | "rejected";
type Filter = "all" | Status;

interface ComplaintRow {
  id: number;
  jenis: string;
  lokasi: string;
  unit: string;
  pelapor: string;
  telepon: string;
  deskripsi: string;
  waktu: string;
  status: Status;
  assignedTo?: string;
}

const COMPLAINTS: ComplaintRow[] = [
  {
    id: 1,
    jenis: "Tumpahan",
    lokasi: "Toilet Lt.1 - Gedung A",
    unit: "Office",
    pelapor: "Ibu Ratna (Tamu)",
    telepon: "0812-3456-7890",
    deskripsi: "Tumpahan air di area wastafel, lantai licin",
    waktu: "30 menit lalu",
    status: "in_progress",
    assignedTo: "Rahmat Hidayat",
  },
  {
    id: 2,
    jenis: "Kotor",
    lokasi: "Lobi Utama",
    unit: "Office",
    pelapor: "Bp. Hasan (Karyawan)",
    telepon: "0813-9876-5432",
    deskripsi: "Sampah kertas berserakan di pojok lobi",
    waktu: "1 jam lalu",
    status: "pending",
  },
  {
    id: 3,
    jenis: "Bau",
    lokasi: "Pantry Lt.3",
    unit: "Office",
    pelapor: "Bu. Sari",
    telepon: "0811-2233-4455",
    deskripsi: "Tempat sampah berbau menyengat",
    waktu: "2 jam lalu",
    status: "resolved",
    assignedTo: "Citra Wijaya",
  },
  {
    id: 4,
    jenis: "Fasilitas Rusak",
    lokasi: "Toilet Wanita Lt.2",
    unit: "Office",
    pelapor: "Ibu Maya",
    telepon: "0822-1111-2222",
    deskripsi: "Pintu cubicle rusak engselnya",
    waktu: "3 jam lalu",
    status: "in_progress",
    assignedTo: "Andi Setiawan",
  },
];

const STATUS_TONE: Record<Status, { bg: string; text: string; label: string }> = {
  pending: { bg: "bg-error/15", text: "text-error", label: "Menunggu" },
  in_progress: {
    bg: "bg-tertiary/15",
    text: "text-tertiary",
    label: "Sedang Ditangani",
  },
  resolved: { bg: "bg-secondary/15", text: "text-secondary", label: "Selesai" },
  rejected: {
    bg: "bg-on-surface-variant/15",
    text: "text-on-surface-variant",
    label: "Ditolak",
  },
};

const JENIS_TONE: Record<string, string> = {
  Tumpahan: "#d62828",
  Kotor: "#e08a14",
  Bau: "#0891b2",
  "Fasilitas Rusak": "#5a6072",
};

const FILTERS: { key: Filter; label: string }[] = [
  { key: "all", label: "Semua" },
  { key: "pending", label: "Menunggu" },
  { key: "in_progress", label: "Ditangani" },
  { key: "resolved", label: "Selesai" },
  { key: "rejected", label: "Ditolak" },
];

export default function KeluhanScreen() {
  const isTablet = useIsTablet();
  const [q, setQ] = useState("");
  const [filter, setFilter] = useState<Filter>("all");

  const stats = useMemo(
    () => ({
      total: COMPLAINTS.length,
      pending: COMPLAINTS.filter((c) => c.status === "pending").length,
      inProgress: COMPLAINTS.filter((c) => c.status === "in_progress").length,
    }),
    []
  );

  const filtered = useMemo(() => {
    const s = q.toLowerCase().trim();
    let list = COMPLAINTS;
    if (filter !== "all") list = list.filter((c) => c.status === filter);
    if (s) {
      list = list.filter(
        (c) =>
          c.lokasi.toLowerCase().includes(s) ||
          c.pelapor.toLowerCase().includes(s) ||
          c.deskripsi.toLowerCase().includes(s)
      );
    }
    return list;
  }, [q, filter]);

  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <AdminScreen
        title="Keluhan Tamu"
        subtitle={`${stats.pending} menunggu · ${stats.inProgress} ditangani`}
        icon="chatbubble-ellipses-outline"
        color="#d62828"
        searchValue={q}
        onSearchChange={setQ}
        searchPlaceholder="Cari lokasi / pelapor..."
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
                    borderColor: active ? "#d62828" : "#e1e3e4",
                    backgroundColor: active ? "#d62828" : "#ffffff",
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
            <EmptyState
              icon="checkmark-done-circle-outline"
              title="Tidak ada keluhan"
            />
          ) : (
            <View className={isTablet ? "flex-row flex-wrap -m-2" : "gap-3"}>
              {filtered.map((c) => {
                const tone = STATUS_TONE[c.status];
                const jenisColor = JENIS_TONE[c.jenis] ?? "#5a6072";
                return (
                  <View key={c.id} className={isTablet ? "w-1/2 p-2" : ""}>
                    <Pressable className="p-4 rounded-2xl bg-surface-container-lowest border border-outline-variant active:opacity-80">
                      <View className="flex-row items-start gap-3">
                        <View
                          className="w-12 h-12 rounded-xl items-center justify-center"
                          style={{ backgroundColor: `${jenisColor}1a` }}
                        >
                          <Ionicons
                            name="warning"
                            size={22}
                            color={jenisColor}
                          />
                        </View>
                        <View className="flex-1">
                          <View className="flex-row items-center justify-between">
                            <View
                              className="px-2 py-0.5 rounded-full"
                              style={{ backgroundColor: `${jenisColor}1a` }}
                            >
                              <Text
                                className="text-[10px] font-bold"
                                style={{ color: jenisColor }}
                              >
                                {c.jenis}
                              </Text>
                            </View>
                            <Text className="text-on-surface-variant text-[10px]">
                              {c.waktu}
                            </Text>
                          </View>
                          <Text
                            className="font-bold text-on-surface mt-1"
                            numberOfLines={1}
                          >
                            {c.lokasi}
                          </Text>
                          <View className="flex-row items-center gap-1 mt-0.5">
                            <Ionicons
                              name="person-outline"
                              size={11}
                              color="#5a6072"
                            />
                            <Text
                              className="text-on-surface-variant text-xs"
                              numberOfLines={1}
                            >
                              {c.pelapor}
                            </Text>
                          </View>
                          <Text
                            className="text-on-surface-variant text-xs mt-1 italic"
                            numberOfLines={2}
                          >
                            "{c.deskripsi}"
                          </Text>
                        </View>
                      </View>
                      <View className="flex-row items-center justify-between mt-3 pt-3 border-t border-outline-variant/50">
                        <View className={`px-2 py-0.5 rounded-full ${tone.bg}`}>
                          <Text className={`text-[10px] font-bold ${tone.text}`}>
                            {tone.label}
                          </Text>
                        </View>
                        {c.assignedTo ? (
                          <View className="flex-row items-center gap-1">
                            <Ionicons
                              name="person"
                              size={11}
                              color="#005bbf"
                            />
                            <Text className="text-primary text-[11px] font-semibold">
                              {c.assignedTo}
                            </Text>
                          </View>
                        ) : (
                          <Pressable className="px-3 py-1 rounded-lg bg-primary active:opacity-80">
                            <Text className="text-white text-[11px] font-bold">
                              Assign
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
