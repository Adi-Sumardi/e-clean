import { useMemo } from "react";
import { Pressable, ScrollView, Text, View } from "react-native";
import { useRouter } from "expo-router";
import { Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";
import { useAuthStore } from "@/stores/auth-store";
import { ROLE_LABEL } from "@/constants/role";
import { StatCard } from "@/components/StatCard";
import { TaskCard, type TaskItem } from "@/components/TaskCard";
import { useIsTablet } from "@/lib/useIsTablet";
import { NotificationBell } from "@/components/NotificationBell";
import { DashboardHeader } from "@/components/DashboardHeader";

const DUMMY_PATROLI: TaskItem[] = [
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
    title: "Patroli Perimeter Malam",
    location: "Seluruh Area",
    time: "20:00 - 21:00",
    status: "pending",
  },
];

type IoniconName = React.ComponentProps<typeof Ionicons>["name"];

interface IncidentItem {
  id: number;
  jenis: string;
  lokasi: string;
  waktu: string;
  severity: "rendah" | "sedang" | "tinggi";
  status: "open" | "tertangani";
}

const RECENT_INCIDENTS: IncidentItem[] = [
  {
    id: 1,
    jenis: "Akses tidak sah",
    lokasi: "Pintu Belakang Gd. B",
    waktu: "30 menit lalu",
    severity: "tinggi",
    status: "open",
  },
  {
    id: 2,
    jenis: "Kendaraan mencurigakan",
    lokasi: "Parkir Belakang",
    waktu: "2 jam lalu",
    severity: "sedang",
    status: "tertangani",
  },
];

interface VisitorEntry {
  id: number;
  nama: string;
  instansi: string;
  jamMasuk: string;
  jamKeluar?: string;
}

const RECENT_VISITORS: VisitorEntry[] = [
  {
    id: 1,
    nama: "Budi Santoso",
    instansi: "PT Maju Jaya",
    jamMasuk: "09:15",
  },
  {
    id: 2,
    nama: "Siti Nurhaliza",
    instansi: "Tamu Karyawan",
    jamMasuk: "08:30",
    jamKeluar: "10:45",
  },
  {
    id: 3,
    nama: "JNE Express",
    instansi: "Kurir",
    jamMasuk: "07:50",
    jamKeluar: "08:00",
  },
];

const SEVERITY_TONE: Record<
  IncidentItem["severity"],
  { bg: string; text: string; label: string }
> = {
  rendah: { bg: "bg-secondary/15", text: "text-secondary", label: "Rendah" },
  sedang: { bg: "bg-tertiary/15", text: "text-tertiary", label: "Sedang" },
  tinggi: { bg: "bg-error/15", text: "text-error", label: "Tinggi" },
};

export function SatpamDashboard() {
  const router = useRouter();
  const isTablet = useIsTablet();
  const user = useAuthStore((s) => s.user);

  const stats = useMemo(() => {
    const done = DUMMY_PATROLI.filter((t) => t.status === "done").length;
    const inProgress = DUMMY_PATROLI.filter(
      (t) => t.status === "in_progress"
    ).length;
    const openIncidents = RECENT_INCIDENTS.filter(
      (i) => i.status === "open"
    ).length;
    const activeVisitors = RECENT_VISITORS.filter((v) => !v.jamKeluar).length;
    return {
      done,
      inProgress,
      total: DUMMY_PATROLI.length,
      open: DUMMY_PATROLI.length - done,
      progress: Math.round((done / DUMMY_PATROLI.length) * 100),
      openIncidents,
      activeVisitors,
    };
  }, []);

  const headerPad = isTablet ? "px-8" : "px-5";
  const contentPad = isTablet ? 32 : 20;

  return (
    <View className="flex-1 bg-background">
      <DashboardHeader
        colors={["#0a5fd6", "#0a3aa0"]}
        title={`Halo, ${user?.name ?? "Satpam"} 👋`}
        subtitle={user ? ROLE_LABEL[user.role] : "Satpam"}
        icon={
          <MaterialCommunityIcons name="shield-account" size={22} color="#fff" />
        }
        right={<NotificationBell size={22} color="#fff" />}
      />

      <ScrollView
        contentContainerStyle={{ padding: contentPad, paddingBottom: 120 }}
        showsVerticalScrollIndicator={false}
      >
        {/* Incident urgent banner */}
        {stats.openIncidents > 0 && (
          <Pressable
            onPress={() => router.push("/(tabs)/laporan")}
            className="bg-error/10 border border-error/30 rounded-2xl p-4 flex-row items-center gap-3 mb-5 active:opacity-80"
          >
            <View className="w-11 h-11 rounded-xl bg-error/15 items-center justify-center">
              <Ionicons name="warning" size={22} color="#d62828" />
            </View>
            <View className="flex-1">
              <Text className="font-bold text-error">
                {stats.openIncidents} insiden terbuka
              </Text>
              <Text className="text-error/80 text-xs">
                Segera tindak lanjuti insiden aktif
              </Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#d62828" />
          </Pressable>
        )}

        {/* Progress card */}
        <View className="bg-primary-container rounded-2xl p-5 flex-row items-center justify-between shadow-md mb-5">
          <View className="flex-1">
            <Text className="text-on-primary-container/80 font-semibold">
              Patroli Hari Ini
            </Text>
            <Text
              className={`text-on-primary-container font-bold mt-1 ${isTablet ? "text-3xl" : "text-2xl"}`}
            >
              {stats.done} dari {stats.total} selesai
            </Text>
            <Text className="text-on-primary-container/70 text-xs mt-1">
              {stats.open} tertunda · {stats.inProgress} berjalan
            </Text>
          </View>
          <View
            className={`rounded-full bg-white/15 items-center justify-center ${
              isTablet ? "w-20 h-20" : "w-16 h-16"
            }`}
          >
            <Text
              className={`text-on-primary-container font-bold ${isTablet ? "text-2xl" : "text-lg"}`}
            >
              {stats.progress}%
            </Text>
          </View>
        </View>

        {/* Stats */}
        <View className="flex-row gap-3 mb-6">
          <StatCard
            icon="alert-circle-outline"
            label="Insiden"
            value={stats.openIncidents}
            hint="Terbuka"
            tone={stats.openIncidents > 0 ? "error" : "secondary"}
          />
          <StatCard
            icon="time-outline"
            label="Shift"
            value="Pagi"
            hint="06:00 - 14:00"
            tone="secondary"
          />
          <StatCard
            icon="people-outline"
            label="Tamu"
            value={stats.activeVisitors}
            hint="Aktif"
            tone="warning"
          />
        </View>

        {/* Quick Actions */}
        <Text
          className={`font-bold text-on-surface mb-3 ${isTablet ? "text-xl" : "text-lg"}`}
        >
          Aksi Cepat
        </Text>
        <View className="gap-3 mb-6">
          <View className="flex-row gap-3">
            <Pressable
              onPress={() => router.push("/(tabs)/laporan")}
              className={`flex-1 rounded-2xl bg-primary items-center gap-2 active:opacity-90 ${
                isTablet ? "p-6" : "p-4"
              }`}
            >
              <Ionicons
                name="camera"
                size={isTablet ? 36 : 28}
                color="#ffffff"
              />
              <Text className="font-bold text-white text-center">
                Lapor Patroli
              </Text>
            </Pressable>
            <Pressable
              onPress={() => router.push("/lapor-insiden")}
              className={`flex-1 rounded-2xl bg-error items-center gap-2 active:opacity-90 ${
                isTablet ? "p-6" : "p-4"
              }`}
            >
              <Ionicons
                name="warning"
                size={isTablet ? 36 : 28}
                color="#ffffff"
              />
              <Text className="font-bold text-white text-center">
                Lapor Insiden
              </Text>
            </Pressable>
          </View>
          <Pressable
            onPress={() => router.push("/buku-tamu")}
            className={`rounded-2xl bg-secondary items-center justify-center flex-row gap-2 active:opacity-90 ${
              isTablet ? "p-6" : "p-4"
            }`}
          >
            <MaterialCommunityIcons
              name="book-open-page-variant"
              size={isTablet ? 28 : 22}
              color="#ffffff"
            />
            <Text className="font-bold text-white text-center text-base">
              Buku Tamu
            </Text>
          </Pressable>
        </View>

        {/* Two-column on tablet */}
        <View className={isTablet ? "flex-row gap-6" : ""}>
          {/* LEFT — Patroli */}
          <View className={isTablet ? "flex-1" : ""}>
            <View className="flex-row items-end justify-between mb-3">
              <Text
                className={`font-bold text-on-surface ${isTablet ? "text-xl" : "text-lg"}`}
              >
                Jadwal Patroli
              </Text>
              <Text className="text-primary text-xs font-bold bg-primary/10 px-3 py-1 rounded-full">
                {stats.total} pos
              </Text>
            </View>
            <View className="gap-3">
              {DUMMY_PATROLI.map((task) => (
                <TaskCard
                  key={task.id}
                  task={task}
                  onPressReport={() => router.push("/(tabs)/laporan")}
                  onPressFinish={() => router.push("/(tabs)/laporan")}
                />
              ))}
            </View>
          </View>

          {/* RIGHT — Incidents + Visitors */}
          <View className={isTablet ? "flex-1" : "mt-6"}>
            {/* Recent Incidents */}
            <View className="flex-row items-end justify-between mb-3">
              <Text
                className={`font-bold text-on-surface ${isTablet ? "text-xl" : "text-lg"}`}
              >
                Insiden Terbaru
              </Text>
              <Pressable className="flex-row items-center gap-1">
                <Text className="text-primary text-xs font-bold">Semua</Text>
                <Ionicons name="chevron-forward" size={14} color="#005bbf" />
              </Pressable>
            </View>
            <View className="gap-3 mb-6">
              {RECENT_INCIDENTS.map((i) => {
                const tone = SEVERITY_TONE[i.severity];
                const isOpen = i.status === "open";
                return (
                  <View
                    key={i.id}
                    className="p-3 rounded-2xl border border-outline-variant bg-surface-container-lowest"
                  >
                    <View className="flex-row items-start gap-3">
                      <View
                        className={`w-10 h-10 rounded-xl ${
                          isOpen ? "bg-error/15" : "bg-secondary/15"
                        } items-center justify-center`}
                      >
                        <Ionicons
                          name={isOpen ? "warning" : "checkmark-done"}
                          size={20}
                          color={isOpen ? "#d62828" : "#0a7e3e"}
                        />
                      </View>
                      <View className="flex-1">
                        <View className="flex-row items-center justify-between">
                          <Text className="font-bold text-on-surface" numberOfLines={1}>
                            {i.jenis}
                          </Text>
                          <View className={`px-2 py-0.5 rounded-full ${tone.bg}`}>
                            <Text className={`text-[10px] font-bold ${tone.text}`}>
                              {tone.label}
                            </Text>
                          </View>
                        </View>
                        <View className="flex-row items-center gap-1 mt-0.5">
                          <Ionicons
                            name="location-outline"
                            size={12}
                            color="#5a6072"
                          />
                          <Text
                            className="text-on-surface-variant text-xs flex-1"
                            numberOfLines={1}
                          >
                            {i.lokasi}
                          </Text>
                          <Text className="text-on-surface-variant text-[10px]">
                            {i.waktu}
                          </Text>
                        </View>
                        <View className="mt-1">
                          <Text
                            className={`text-[10px] font-bold ${
                              isOpen ? "text-error" : "text-secondary"
                            }`}
                          >
                            {isOpen ? "● Terbuka — perlu ditangani" : "✓ Tertangani"}
                          </Text>
                        </View>
                      </View>
                    </View>
                  </View>
                );
              })}
            </View>

            {/* Recent Visitors */}
            <View className="flex-row items-end justify-between mb-3">
              <Text
                className={`font-bold text-on-surface ${isTablet ? "text-xl" : "text-lg"}`}
              >
                Tamu Hari Ini
              </Text>
              <Pressable
                onPress={() => router.push("/buku-tamu")}
                className="flex-row items-center gap-1"
              >
                <Text className="text-primary text-xs font-bold">Buku Tamu</Text>
                <Ionicons name="chevron-forward" size={14} color="#005bbf" />
              </Pressable>
            </View>
            <View className="gap-2">
              {RECENT_VISITORS.map((v) => (
                <View
                  key={v.id}
                  className="flex-row items-center gap-3 p-3 rounded-2xl border border-outline-variant bg-surface-container-lowest"
                >
                  <View className="w-10 h-10 rounded-full bg-secondary/10 items-center justify-center">
                    <Ionicons name="person" size={18} color="#0a7e3e" />
                  </View>
                  <View className="flex-1">
                    <Text className="font-bold text-on-surface text-sm" numberOfLines={1}>
                      {v.nama}
                    </Text>
                    <Text className="text-on-surface-variant text-xs" numberOfLines={1}>
                      {v.instansi}
                    </Text>
                  </View>
                  <View className="items-end">
                    <View className="flex-row items-center gap-1">
                      <Ionicons name="enter-outline" size={12} color="#0a7e3e" />
                      <Text className="text-secondary text-xs font-bold">
                        {v.jamMasuk}
                      </Text>
                    </View>
                    {v.jamKeluar ? (
                      <View className="flex-row items-center gap-1 mt-0.5">
                        <Ionicons name="exit-outline" size={12} color="#5a6072" />
                        <Text className="text-on-surface-variant text-[11px]">
                          {v.jamKeluar}
                        </Text>
                      </View>
                    ) : (
                      <Text className="text-error text-[10px] font-bold mt-0.5">
                        Di dalam
                      </Text>
                    )}
                  </View>
                </View>
              ))}
            </View>
          </View>
        </View>
      </ScrollView>
    </View>
  );
}
