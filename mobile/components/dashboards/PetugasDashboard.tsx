import { useMemo } from "react";
import { Pressable, ScrollView, Text, View } from "react-native";
import { useRouter } from "expo-router";
import { Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";
import { useAuthStore } from "@/stores/auth-store";
import { ROLE_LABEL } from "@/constants/role";
import { StatCard } from "@/components/StatCard";
import { TaskCard } from "@/components/TaskCard";
import { NotificationBell } from "@/components/NotificationBell";
import { DashboardHeader } from "@/components/DashboardHeader";
import { useIsTablet } from "@/lib/useIsTablet";
import { useJadwalToday } from "@/lib/hooks";
import { jadwalToTask } from "@/lib/mappers";

interface GuestComplaintItem {
  id: number;
  jenis: string;
  lokasi: string;
  deskripsi: string;
  waktu: string;
}

const ASSIGNED_COMPLAINTS: GuestComplaintItem[] = [
  {
    id: 1,
    jenis: "Tumpahan",
    lokasi: "Toilet Lt.1 - Gedung A",
    deskripsi: "Tumpahan air di area wastafel",
    waktu: "15 menit lalu",
  },
];

export function PetugasDashboard() {
  const router = useRouter();
  const isTablet = useIsTablet();
  const user = useAuthStore((s) => s.user);

  const jadwalQuery = useJadwalToday();
  const todayTasks = useMemo(
    () => (jadwalQuery.data ?? []).map(jadwalToTask),
    [jadwalQuery.data]
  );

  const stats = useMemo(() => {
    const done = todayTasks.filter((t) => t.status === "done").length;
    const pending = todayTasks.filter((t) => t.status === "pending").length;
    const inProgress = todayTasks.filter(
      (t) => t.status === "in_progress"
    ).length;
    const total = todayTasks.length;
    return {
      done,
      pending,
      inProgress,
      total,
      progress: total > 0 ? Math.round((done / total) * 100) : 0,
      reportsToday: done,
      locations: new Set(todayTasks.map((t) => t.location)).size,
    };
  }, [todayTasks]);

  const headerPad = isTablet ? "px-8" : "px-5";
  const contentPad = isTablet ? 32 : 20;

  return (
    <View className="flex-1 bg-background">
      <DashboardHeader
        colors={["#0a7e3e", "#075c2d"]}
        title={user?.unit?.name ?? "e-Office Kopkaryapi"}
        subtitle="Petugas Kebersihan"
        icon={<MaterialCommunityIcons name="broom" size={22} color="#fff" />}
        right={<NotificationBell size={22} color="#fff" />}
      />

      <ScrollView
        contentContainerStyle={{ padding: contentPad, paddingBottom: 120 }}
        showsVerticalScrollIndicator={false}
      >
        {/* Greeting */}
        <View className="mb-5">
          <Text className="text-on-surface-variant">Selamat bekerja,</Text>
          <Text
            className={`font-bold text-on-surface ${isTablet ? "text-4xl" : "text-2xl"}`}
          >
            {user?.name ?? "Petugas"}
          </Text>
          <View className="self-start mt-2 px-3 py-1 rounded-full bg-secondary/10 flex-row items-center gap-1">
            <MaterialCommunityIcons name="broom" size={12} color="#0a7e3e" />
            <Text className="text-secondary text-xs font-bold">
              {user ? ROLE_LABEL[user.role] : "Petugas Kebersihan"}
            </Text>
          </View>
        </View>

        {/* Guest complaint banner */}
        {ASSIGNED_COMPLAINTS.length > 0 && (
          <View className="bg-error/10 border border-error/30 rounded-2xl p-4 mb-5">
            <View className="flex-row items-center gap-3 mb-2">
              <View className="w-10 h-10 rounded-xl bg-error/15 items-center justify-center">
                <Ionicons name="warning" size={20} color="#d62828" />
              </View>
              <View className="flex-1">
                <Text className="font-bold text-error">
                  {ASSIGNED_COMPLAINTS.length} keluhan tamu untuk Anda
                </Text>
                <Text className="text-error/80 text-xs">
                  Segera tangani keluhan berikut
                </Text>
              </View>
            </View>
            {ASSIGNED_COMPLAINTS.map((c) => (
              <Pressable
                key={c.id}
                className="flex-row items-center gap-3 p-3 mt-2 rounded-xl bg-surface active:opacity-80"
              >
                <View className="px-2 py-1 rounded-full bg-error/15">
                  <Text className="text-error text-[10px] font-bold">
                    {c.jenis}
                  </Text>
                </View>
                <View className="flex-1">
                  <Text className="text-on-surface text-sm font-semibold" numberOfLines={1}>
                    {c.lokasi}
                  </Text>
                  <Text
                    className="text-on-surface-variant text-xs"
                    numberOfLines={1}
                  >
                    {c.deskripsi}
                  </Text>
                </View>
                <Text className="text-on-surface-variant text-[10px]">
                  {c.waktu}
                </Text>
                <Ionicons name="chevron-forward" size={16} color="#d62828" />
              </Pressable>
            ))}
          </View>
        )}

        {/* Progress card */}
        <View className="bg-secondary rounded-2xl p-5 flex-row items-center justify-between shadow-md mb-5">
          <View className="flex-1">
            <Text className="text-white/80 font-semibold">Tugas Hari Ini</Text>
            <Text
              className={`text-white font-bold mt-1 ${isTablet ? "text-3xl" : "text-2xl"}`}
            >
              {stats.done} dari {stats.total} selesai
            </Text>
            <Text className="text-white/70 text-xs mt-1">
              {stats.pending} tertunda · {stats.inProgress} berjalan
            </Text>
          </View>
          <View
            className={`rounded-full bg-white/15 items-center justify-center ${
              isTablet ? "w-20 h-20" : "w-16 h-16"
            }`}
          >
            <Text
              className={`text-white font-bold ${isTablet ? "text-2xl" : "text-lg"}`}
            >
              {stats.progress}%
            </Text>
          </View>
        </View>

        {/* Stats — PetugasStatsOverviewWidget */}
        <View className="flex-row gap-3 mb-6">
          <StatCard
            icon="location-outline"
            label="Lokasi"
            value={stats.locations}
            hint="Dijadwalkan"
            tone="primary"
          />
          <StatCard
            icon="clipboard-outline"
            label="Laporan"
            value={stats.reportsToday}
            hint="Hari ini"
            tone="secondary"
          />
          <StatCard
            icon="hourglass-outline"
            label="Pending"
            value={stats.pending}
            hint="Belum dilapor"
            tone={stats.pending > 0 ? "warning" : "secondary"}
          />
        </View>

        {/* Quick Actions — PetugasQuickActionsWidget */}
        <Text
          className={`font-bold text-on-surface mb-3 ${isTablet ? "text-xl" : "text-lg"}`}
        >
          Aksi Cepat
        </Text>
        <View className={`flex-row gap-3 mb-6`}>
          <Pressable
            onPress={() => router.push("/(tabs)/laporan")}
            className={`flex-1 rounded-2xl bg-secondary items-center gap-2 active:opacity-90 ${
              isTablet ? "p-6" : "p-4"
            }`}
          >
            <Ionicons
              name="camera"
              size={isTablet ? 36 : 28}
              color="#ffffff"
            />
            <Text className="font-bold text-white text-center">
              Buat Laporan
            </Text>
          </Pressable>
          <Pressable
            className={`flex-1 rounded-2xl bg-primary items-center gap-2 active:opacity-90 ${
              isTablet ? "p-6" : "p-4"
            }`}
          >
            <Ionicons
              name="qr-code"
              size={isTablet ? 36 : 28}
              color="#ffffff"
            />
            <Text className="font-bold text-white text-center">
              Scan QR Code
            </Text>
          </Pressable>
          <Pressable
            onPress={() => router.push("/(tabs)/tugas")}
            className={`flex-1 rounded-2xl border border-outline-variant bg-surface-container-lowest items-center gap-2 active:opacity-80 ${
              isTablet ? "p-6" : "p-4"
            }`}
          >
            <Ionicons
              name="calendar"
              size={isTablet ? 36 : 28}
              color="#7e5a17"
            />
            <Text className="font-bold text-on-surface text-center">
              Jadwal Kerja
            </Text>
          </Pressable>
        </View>

        {/* Today's tasks */}
        <View className={isTablet ? "flex-row gap-6" : ""}>
          <View className={isTablet ? "flex-1" : ""}>
            <View className="flex-row items-end justify-between mb-3">
              <Text
                className={`font-bold text-on-surface ${isTablet ? "text-xl" : "text-lg"}`}
              >
                Jadwal Tugas
              </Text>
              <Pressable
                onPress={() => router.push("/(tabs)/tugas")}
                className="flex-row items-center gap-1"
              >
                <Text className="text-primary text-xs font-bold">
                  Lihat Semua
                </Text>
                <Ionicons name="chevron-forward" size={14} color="#005bbf" />
              </Pressable>
            </View>
            <View className="gap-3">
              {jadwalQuery.isLoading ? (
                <Text className="text-on-surface-variant text-sm py-4">
                  Memuat jadwal...
                </Text>
              ) : todayTasks.length === 0 ? (
                <Text className="text-on-surface-variant text-sm py-4">
                  Tidak ada jadwal kebersihan hari ini.
                </Text>
              ) : (
                todayTasks
                  .slice(0, isTablet ? todayTasks.length : 4)
                  .map((task) => (
                    <TaskCard
                      key={task.id}
                      task={task}
                      onPressReport={() => router.push("/(tabs)/laporan")}
                      onPressFinish={() => router.push("/(tabs)/laporan")}
                    />
                  ))
              )}
            </View>
          </View>

          {/* Tablet: side panel with tips/info */}
          {isTablet && (
            <View className="flex-1">
              <Text
                className={`font-bold text-on-surface mb-3 ${isTablet ? "text-xl" : "text-lg"}`}
              >
                Performa Bulan Ini
              </Text>
              <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-4">
                <View className="flex-row items-center gap-3 mb-3">
                  <View className="w-12 h-12 rounded-xl bg-tertiary/15 items-center justify-center">
                    <Ionicons name="star" size={24} color="#e08a14" />
                  </View>
                  <View className="flex-1">
                    <Text className="text-on-surface-variant text-xs">
                      Rata-rata Rating
                    </Text>
                    <View className="flex-row items-baseline gap-1">
                      <Text className="text-on-surface text-2xl font-bold">
                        4.7
                      </Text>
                      <Text className="text-on-surface-variant text-sm">/5</Text>
                    </View>
                  </View>
                </View>
                <View className="gap-2">
                  <View className="flex-row items-center justify-between">
                    <Text className="text-on-surface-variant text-sm">
                      Total Laporan
                    </Text>
                    <Text className="text-on-surface text-sm font-bold">
                      42
                    </Text>
                  </View>
                  <View className="flex-row items-center justify-between">
                    <Text className="text-on-surface-variant text-sm">
                      On-Time
                    </Text>
                    <Text className="text-secondary text-sm font-bold">
                      38 (90%)
                    </Text>
                  </View>
                  <View className="flex-row items-center justify-between">
                    <Text className="text-on-surface-variant text-sm">
                      Telat
                    </Text>
                    <Text className="text-tertiary text-sm font-bold">
                      4 (10%)
                    </Text>
                  </View>
                </View>
              </View>

              <View className="bg-primary/5 rounded-2xl p-4 flex-row items-center gap-3">
                <Ionicons name="trophy" size={20} color="#005bbf" />
                <Text className="flex-1 text-primary text-xs">
                  Anda berada di peringkat <Text className="font-bold">#3</Text>{" "}
                  di leaderboard bulan ini. Kerja bagus!
                </Text>
              </View>
            </View>
          )}
        </View>
      </ScrollView>
    </View>
  );
}
