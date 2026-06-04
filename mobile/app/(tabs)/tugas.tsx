import { useMemo, useState } from "react";
import {
  ActivityIndicator,
  Pressable,
  RefreshControl,
  ScrollView,
  Text,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { useRouter } from "expo-router";
import { Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";
import { TaskCard, type TaskItem } from "@/components/TaskCard";
import { useIsTablet } from "@/lib/useIsTablet";
import { useAuthStore } from "@/stores/auth-store";
import type { UserRole } from "@/stores/auth-store";
import { useJadwalToday } from "@/lib/hooks";
import { jadwalToTask } from "@/lib/mappers";

type IoniconName = React.ComponentProps<typeof Ionicons>["name"];

// ----- DATA PER ROLE -----
const TASKS_BY_ROLE: Record<UserRole, TaskItem[]> = {
  petugas: [
    {
      id: 1,
      title: "Pembersihan Toilet Lt.1",
      location: "Toilet Lt.1 - Gedung A",
      time: "08:00 - 09:00",
      status: "done",
    },
    {
      id: 2,
      title: "Mopping Lobi Utama",
      location: "Lobi Utama",
      time: "10:00 - 11:00",
      status: "done",
    },
    {
      id: 3,
      title: "Pembersihan Pantry Lt.2",
      location: "Pantry Lt.2",
      time: "13:00 - 14:00",
      status: "in_progress",
    },
    {
      id: 4,
      title: "Pembersihan Ruang Rapat Besar",
      location: "Ruang Rapat Besar",
      time: "15:00 - 16:00",
      status: "pending",
    },
    {
      id: 5,
      title: "Pembersihan Toilet Lt.3",
      location: "Toilet Lt.3 - Gedung B",
      time: "17:00 - 18:00",
      status: "pending",
    },
  ],
  satpam: [
    {
      id: 1,
      title: "Patroli Gate Utama",
      location: "Pintu Masuk Depan",
      time: "08:00 - 09:00",
      status: "done",
    },
    {
      id: 2,
      title: "Patroli Area Parkir",
      location: "Parkir Belakang",
      time: "10:00 - 11:00",
      status: "done",
    },
    {
      id: 3,
      title: "Patroli Lantai 2 & 3",
      location: "Gedung A",
      time: "13:00 - 14:00",
      status: "in_progress",
    },
    {
      id: 4,
      title: "Patroli Gudang Logistik",
      location: "Gudang B",
      time: "15:00 - 16:00",
      status: "pending",
    },
    {
      id: 5,
      title: "Patroli Perimeter Malam",
      location: "Seluruh Area",
      time: "20:00 - 21:00",
      status: "pending",
    },
  ],
  office_boy: [
    {
      id: 1,
      title: "Setup Pantry Lt.1 (kopi, teh, air panas)",
      location: "Pantry Lantai 1",
      time: "07:00",
      status: "done",
    },
    {
      id: 2,
      title: "Cek Lobi & Koran",
      location: "Lobi Utama",
      time: "07:30",
      status: "done",
    },
    {
      id: 3,
      title: "Setup Ruang Rapat Direksi",
      location: "Ruang Rapat Besar",
      time: "09:00",
      status: "in_progress",
    },
    {
      id: 4,
      title: "Refill Pantry Lt.2",
      location: "Pantry Lantai 2",
      time: "10:00",
      status: "pending",
    },
    {
      id: 5,
      title: "Bersihkan Toilet Pria Lt.1",
      location: "Toilet Pria Lt.1",
      time: "11:00",
      status: "pending",
    },
    {
      id: 6,
      title: "Setup Ruang Rapat Kecil",
      location: "Ruang Rapat Kecil",
      time: "14:00",
      status: "pending",
    },
  ],
  petugas_toko: [
    {
      id: 1,
      title: "Pembukaan Toko + nyalakan lampu/AC",
      location: "Toko Utama",
      time: "07:30",
      status: "done",
    },
    {
      id: 2,
      title: "Restocking Display Minuman",
      location: "Rak Display Lt.1",
      time: "08:00",
      status: "done",
    },
    {
      id: 3,
      title: "Cek Harga & Label Produk",
      location: "Seluruh Rak",
      time: "09:00",
      status: "in_progress",
    },
    {
      id: 4,
      title: "Restocking Snack & Roti",
      location: "Rak Display Lt.2",
      time: "10:30",
      status: "pending",
    },
    {
      id: 5,
      title: "Cek Stok Kasir & Uang Kembali",
      location: "Area Kasir",
      time: "12:00",
      status: "pending",
    },
    {
      id: 6,
      title: "Briefing Tim + Tutup Kasir",
      location: "Ruang Briefing",
      time: "15:30",
      status: "pending",
    },
  ],
  supervisor: [],
  super_admin: [],
  pengurus: [],
};

const ROLE_CONFIG: Record<
  UserRole,
  { title: string; icon: IoniconName; itemNoun: string }
> = {
  petugas: {
    title: "Jadwal Kebersihan",
    icon: "list-circle",
    itemNoun: "tugas",
  },
  satpam: { title: "Jadwal Patroli", icon: "shield", itemNoun: "patroli" },
  office_boy: { title: "Jadwal Tugas Area", icon: "cafe", itemNoun: "tugas" },
  petugas_toko: {
    title: "Checklist Harian Toko",
    icon: "storefront",
    itemNoun: "tugas",
  },
  supervisor: { title: "Tugas", icon: "list-circle", itemNoun: "tugas" },
  super_admin: { title: "Tugas", icon: "list-circle", itemNoun: "tugas" },
  pengurus: { title: "Tugas", icon: "list-circle", itemNoun: "tugas" },
};

type Filter = "all" | "pending" | "in_progress" | "done";

const FILTERS: { key: Filter; label: string; icon: IoniconName }[] = [
  { key: "all", label: "Semua", icon: "apps" },
  { key: "pending", label: "Belum", icon: "ellipse-outline" },
  { key: "in_progress", label: "Berjalan", icon: "play-circle" },
  { key: "done", label: "Selesai", icon: "checkmark-circle" },
];

export default function TugasScreen() {
  const router = useRouter();
  const isTablet = useIsTablet();
  const role = useAuthStore((s) => s.user?.role) ?? "satpam";
  const [filter, setFilter] = useState<Filter>("all");

  // Petugas kebersihan pulls real schedules from the API; the other roles
  // do not have backend schedule endpoints yet, so they keep sample data.
  const isPetugas = role === "petugas";
  const jadwalQuery = useJadwalToday();
  const tasks: TaskItem[] = isPetugas
    ? (jadwalQuery.data ?? []).map(jadwalToTask)
    : (TASKS_BY_ROLE[role] ?? []);
  const config = ROLE_CONFIG[role];

  const filtered = useMemo(() => {
    if (filter === "all") return tasks;
    return tasks.filter((t) => t.status === filter);
  }, [filter, tasks]);

  const counts = useMemo(
    () => ({
      all: tasks.length,
      pending: tasks.filter((t) => t.status === "pending").length,
      in_progress: tasks.filter((t) => t.status === "in_progress").length,
      done: tasks.filter((t) => t.status === "done").length,
    }),
    [tasks]
  );

  const headerPad = isTablet ? "px-8" : "px-5";
  const contentPad = isTablet ? 32 : 20;

  return (
    <SafeAreaView className="flex-1 bg-background" edges={["top"]}>
      <View
        className={`${headerPad} h-16 justify-center border-b border-surface-variant bg-surface`}
      >
        <View className="flex-row items-center gap-3">
          {role === "petugas_toko" ? (
            <MaterialCommunityIcons
              name="clipboard-list-outline"
              size={isTablet ? 30 : 24}
              color="#7e5a17"
            />
          ) : (
            <Ionicons
              name={config.icon}
              size={isTablet ? 30 : 24}
              color="#005bbf"
            />
          )}
          <Text
            className={`font-bold text-on-surface ${isTablet ? "text-2xl" : "text-lg"}`}
          >
            {config.title}
          </Text>
        </View>
      </View>

      {/* Filter pills — fixed height & explicit spacing */}
      <View className="border-b border-surface-variant bg-surface">
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={{
            paddingHorizontal: contentPad,
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
                  height: 38,
                  marginRight: idx === FILTERS.length - 1 ? 0 : 8,
                  paddingHorizontal: 14,
                  borderRadius: 999,
                  borderWidth: 1,
                  flexDirection: "row",
                  alignItems: "center",
                  borderColor: active ? "#005bbf" : "#e1e3e4",
                  backgroundColor: active ? "#005bbf" : "#ffffff",
                }}
              >
                <Ionicons
                  name={f.icon}
                  size={16}
                  color={active ? "#ffffff" : "#414754"}
                  style={{ marginRight: 6 }}
                />
                <Text
                  style={{
                    fontSize: 13,
                    fontWeight: "600",
                    lineHeight: 18,
                    color: active ? "#ffffff" : "#1a1c1e",
                    marginRight: 6,
                  }}
                >
                  {f.label}
                </Text>
                <View
                  style={{
                    paddingHorizontal: 8,
                    paddingVertical: 2,
                    borderRadius: 999,
                    backgroundColor: active
                      ? "rgba(255,255,255,0.25)"
                      : "rgba(0,91,191,0.1)",
                  }}
                >
                  <Text
                    style={{
                      fontSize: 11,
                      fontWeight: "700",
                      lineHeight: 14,
                      color: active ? "#ffffff" : "#005bbf",
                    }}
                  >
                    {counts[f.key]}
                  </Text>
                </View>
              </Pressable>
            );
          })}
        </ScrollView>
      </View>

      <ScrollView
        contentContainerStyle={{ padding: contentPad, paddingBottom: 120 }}
        showsVerticalScrollIndicator={false}
        refreshControl={
          isPetugas ? (
            <RefreshControl
              refreshing={jadwalQuery.isFetching}
              onRefresh={() => jadwalQuery.refetch()}
            />
          ) : undefined
        }
      >
        {isPetugas && jadwalQuery.isLoading ? (
          <View className="items-center mt-20">
            <ActivityIndicator color="#005bbf" />
            <Text className="text-on-surface-variant mt-3">
              Memuat jadwal...
            </Text>
          </View>
        ) : isPetugas && jadwalQuery.isError ? (
          <View className="items-center mt-20">
            <Ionicons name="cloud-offline-outline" size={64} color="#c1c6d6" />
            <Text className="text-on-surface-variant mt-3 text-center px-8">
              {(jadwalQuery.error as Error)?.message ??
                "Gagal memuat jadwal."}
            </Text>
            <Pressable
              onPress={() => jadwalQuery.refetch()}
              className="mt-4 px-5 h-10 rounded-full bg-primary items-center justify-center"
            >
              <Text className="text-on-primary font-semibold">Coba lagi</Text>
            </Pressable>
          </View>
        ) : filtered.length === 0 ? (
          <View className="items-center mt-20">
            <Ionicons name="folder-open-outline" size={64} color="#c1c6d6" />
            <Text className="text-on-surface-variant mt-3">
              Tidak ada {config.itemNoun} pada kategori ini.
            </Text>
          </View>
        ) : isTablet ? (
          <View className="flex-row flex-wrap -m-2">
            {filtered.map((task) => (
              <View key={task.id} className="w-1/2 p-2">
                <TaskCard
                  task={task}
                  onPressReport={() => router.push("/(tabs)/laporan")}
                  onPressFinish={() => router.push("/(tabs)/laporan")}
                />
              </View>
            ))}
          </View>
        ) : (
          <View className="gap-3">
            {filtered.map((task) => (
              <TaskCard
                key={task.id}
                task={task}
                onPressReport={() => router.push("/(tabs)/laporan")}
                onPressFinish={() => router.push("/(tabs)/laporan")}
              />
            ))}
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}
