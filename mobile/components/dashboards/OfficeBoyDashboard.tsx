import { useMemo } from "react";
import { Pressable, ScrollView, Text, View } from "react-native";
import { useRouter } from "expo-router";
import { Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";
import { useAuthStore } from "@/stores/auth-store";
import { ROLE_LABEL } from "@/constants/role";
import { StatCard } from "@/components/StatCard";
import { useIsTablet } from "@/lib/useIsTablet";
import { NotificationBell } from "@/components/NotificationBell";
import { DashboardHeader } from "@/components/DashboardHeader";

type IoniconName = React.ComponentProps<typeof Ionicons>["name"];

interface AreaTask {
  id: number;
  area: string;
  icon: IoniconName;
  time: string;
  status: "done" | "in_progress" | "pending";
  note?: string;
}

const AREA_TASKS: AreaTask[] = [
  {
    id: 1,
    area: "Pantry Lantai 1",
    icon: "cafe-outline",
    time: "07:00",
    status: "done",
    note: "Setup pagi: kopi, teh, air panas",
  },
  {
    id: 2,
    area: "Lobi Utama",
    icon: "business-outline",
    time: "07:30",
    status: "done",
    note: "Cek koran & majalah",
  },
  {
    id: 3,
    area: "Ruang Rapat Besar",
    icon: "easel-outline",
    time: "09:00",
    status: "in_progress",
    note: "Setup untuk rapat direksi",
  },
  {
    id: 4,
    area: "Pantry Lantai 2",
    icon: "cafe-outline",
    time: "10:00",
    status: "pending",
  },
  {
    id: 5,
    area: "Toilet Pria Lt.1",
    icon: "water-outline",
    time: "11:00",
    status: "pending",
  },
  {
    id: 6,
    area: "Ruang Rapat Kecil",
    icon: "easel-outline",
    time: "14:00",
    status: "pending",
    note: "Setup untuk training",
  },
];

interface RoomRequest {
  id: number;
  pemohon: string;
  ruangan: string;
  waktu: string;
  jumlah: number;
  items: string[];
  status: "new" | "in_progress" | "done";
  prioritas: "tinggi" | "normal";
}

const ROOM_REQUESTS: RoomRequest[] = [
  {
    id: 1,
    pemohon: "Bp. Wibowo - Direksi",
    ruangan: "Ruang Rapat Besar",
    waktu: "Sekarang (09:30)",
    jumlah: 8,
    items: ["Kopi 4", "Teh 4", "Snack"],
    status: "in_progress",
    prioritas: "tinggi",
  },
  {
    id: 2,
    pemohon: "Bu. Sari - HRD",
    ruangan: "Ruang Rapat Kecil",
    waktu: "14:00",
    jumlah: 6,
    items: ["Air mineral", "Snack"],
    status: "new",
    prioritas: "normal",
  },
];

interface DocumentDelivery {
  id: number;
  from: string;
  to: string;
  type: string;
  waktu: string;
}

const DELIVERIES: DocumentDelivery[] = [
  {
    id: 1,
    from: "Finance Lt.3",
    to: "Direksi Lt.5",
    type: "Dokumen Tanda Tangan",
    waktu: "10:30",
  },
  {
    id: 2,
    from: "HRD Lt.2",
    to: "Marketing Lt.4",
    type: "Paket Internal",
    waktu: "13:00",
  },
];

const STATUS_TONE: Record<
  AreaTask["status"],
  { bg: string; text: string; icon: IoniconName; label: string }
> = {
  done: {
    bg: "bg-secondary/15",
    text: "text-secondary",
    icon: "checkmark-circle",
    label: "Selesai",
  },
  in_progress: {
    bg: "bg-primary/15",
    text: "text-primary",
    icon: "play-circle",
    label: "Berjalan",
  },
  pending: {
    bg: "bg-on-surface-variant/15",
    text: "text-on-surface-variant",
    icon: "ellipse-outline",
    label: "Belum",
  },
};

const PRIORITY_TONE: Record<
  RoomRequest["prioritas"],
  { bg: string; text: string; label: string }
> = {
  tinggi: { bg: "bg-error/15", text: "text-error", label: "Prioritas Tinggi" },
  normal: { bg: "bg-primary/15", text: "text-primary", label: "Normal" },
};

export function OfficeBoyDashboard() {
  const router = useRouter();
  const isTablet = useIsTablet();
  const user = useAuthStore((s) => s.user);

  const stats = useMemo(() => {
    const done = AREA_TASKS.filter((t) => t.status === "done").length;
    const pending = AREA_TASKS.filter((t) => t.status === "pending").length;
    const inProgress = AREA_TASKS.filter(
      (t) => t.status === "in_progress"
    ).length;
    return {
      done,
      pending,
      inProgress,
      total: AREA_TASKS.length,
      progress: Math.round((done / AREA_TASKS.length) * 100),
      activeRequests: ROOM_REQUESTS.filter((r) => r.status !== "done").length,
      pendingDeliveries: DELIVERIES.length,
    };
  }, []);

  const headerPad = isTablet ? "px-8" : "px-5";
  const contentPad = isTablet ? 32 : 20;

  return (
    <View className="flex-1 bg-background">
      <DashboardHeader
        colors={["#a06a1e", "#6e4912"]}
        title={`Halo, ${user?.name ?? "Office Boy"} 👋`}
        subtitle={user ? ROLE_LABEL[user.role] : "Office Boy"}
        icon={
          <MaterialCommunityIcons name="coffee-outline" size={22} color="#fff" />
        }
        right={<NotificationBell size={22} color="#fff" />}
      />

      <ScrollView
        contentContainerStyle={{ padding: contentPad, paddingBottom: 120 }}
        showsVerticalScrollIndicator={false}
      >
        {/* Urgent request banner */}
        {ROOM_REQUESTS.some((r) => r.prioritas === "tinggi") && (
          <Pressable
            onPress={() => router.push("/(tabs)/laporan")}
            className="bg-error/10 border border-error/30 rounded-2xl p-4 mb-5 active:opacity-80"
          >
            <View className="flex-row items-center gap-3">
              <View className="w-10 h-10 rounded-xl bg-error/15 items-center justify-center">
                <Ionicons name="flash" size={20} color="#d62828" />
              </View>
              <View className="flex-1">
                <Text className="font-bold text-error">
                  Ada permintaan prioritas tinggi
                </Text>
                <Text className="text-error/80 text-xs">
                  Segera tangani permintaan rapat direksi
                </Text>
              </View>
              <Ionicons name="chevron-forward" size={20} color="#d62828" />
            </View>
          </Pressable>
        )}

        {/* Progress card */}
        <View className="bg-tertiary rounded-2xl p-5 flex-row items-center justify-between shadow-md mb-5">
          <View className="flex-1">
            <Text className="text-white/80 font-semibold">
              Progress Hari Ini
            </Text>
            <Text
              className={`text-white font-bold mt-1 ${isTablet ? "text-3xl" : "text-2xl"}`}
            >
              {stats.done} dari {stats.total} area
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

        {/* Stats */}
        <View className="flex-row gap-3 mb-6">
          <StatCard
            icon="cafe-outline"
            label="Permintaan"
            value={stats.activeRequests}
            hint="Belum selesai"
            tone={stats.activeRequests > 0 ? "error" : "secondary"}
          />
          <StatCard
            icon="document-outline"
            label="Antar Dok."
            value={stats.pendingDeliveries}
            hint="Menunggu"
            tone="warning"
          />
          {isTablet && (
            <StatCard
              icon="home-outline"
              label="Area"
              value={stats.total}
              hint="Dijadwalkan"
              tone="primary"
            />
          )}
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
              className={`flex-1 rounded-2xl bg-tertiary items-center gap-2 active:opacity-90 ${
                isTablet ? "p-6" : "p-4"
              }`}
            >
              <Ionicons
                name="camera"
                size={isTablet ? 36 : 28}
                color="#ffffff"
              />
              <Text className="font-bold text-white text-center">
                Lapor Selesai
              </Text>
            </Pressable>
            <Pressable
              className={`flex-1 rounded-2xl border border-outline-variant bg-surface-container-lowest items-center gap-2 active:opacity-80 ${
                isTablet ? "p-6" : "p-4"
              }`}
            >
              <Ionicons
                name="cafe"
                size={isTablet ? 36 : 28}
                color="#7e5a17"
              />
              <Text className="font-bold text-on-surface text-center">
                Cek Permintaan
              </Text>
            </Pressable>
          </View>
          <Pressable
            className={`rounded-2xl border border-outline-variant bg-surface-container-lowest items-center justify-center flex-row gap-2 active:opacity-80 ${
              isTablet ? "p-6" : "p-4"
            }`}
          >
            <Ionicons
              name="document"
              size={isTablet ? 28 : 22}
              color="#005bbf"
            />
            <Text className="font-bold text-on-surface text-center text-base">
              Antar Dokumen
            </Text>
          </Pressable>
        </View>

        {/* Tablet: 2 columns | Mobile: stacked */}
        <View className={isTablet ? "flex-row gap-6" : ""}>
          {/* LEFT — Room Requests */}
          <View className={isTablet ? "flex-1" : ""}>
            <View className="flex-row items-end justify-between mb-3">
              <Text
                className={`font-bold text-on-surface ${isTablet ? "text-xl" : "text-lg"}`}
              >
                Permintaan Karyawan
              </Text>
              <View className="px-3 py-1 rounded-full bg-error/10">
                <Text className="text-error text-xs font-bold">
                  {stats.activeRequests} aktif
                </Text>
              </View>
            </View>
            <View className="gap-3 mb-6">
              {ROOM_REQUESTS.map((r) => {
                const pTone = PRIORITY_TONE[r.prioritas];
                return (
                  <View
                    key={r.id}
                    className="p-4 rounded-2xl border border-outline-variant bg-surface-container-lowest"
                  >
                    <View className="flex-row items-start gap-3">
                      <View className="w-11 h-11 rounded-xl bg-tertiary/15 items-center justify-center">
                        <Ionicons name="cafe" size={22} color="#7e5a17" />
                      </View>
                      <View className="flex-1">
                        <View className="flex-row items-center justify-between">
                          <Text
                            className="font-bold text-on-surface"
                            numberOfLines={1}
                          >
                            {r.ruangan}
                          </Text>
                          <View
                            className={`px-2 py-0.5 rounded-full ${pTone.bg}`}
                          >
                            <Text className={`text-[10px] font-bold ${pTone.text}`}>
                              {pTone.label}
                            </Text>
                          </View>
                        </View>
                        <Text
                          className="text-on-surface-variant text-xs"
                          numberOfLines={1}
                        >
                          {r.pemohon}
                        </Text>
                        <View className="flex-row items-center gap-3 mt-1">
                          <View className="flex-row items-center gap-1">
                            <Ionicons
                              name="time-outline"
                              size={12}
                              color="#5a6072"
                            />
                            <Text className="text-on-surface-variant text-xs">
                              {r.waktu}
                            </Text>
                          </View>
                          <View className="flex-row items-center gap-1">
                            <Ionicons
                              name="people-outline"
                              size={12}
                              color="#5a6072"
                            />
                            <Text className="text-on-surface-variant text-xs">
                              {r.jumlah} orang
                            </Text>
                          </View>
                        </View>
                      </View>
                    </View>
                    <View className="flex-row flex-wrap gap-1.5 mt-3">
                      {r.items.map((item, i) => (
                        <View
                          key={i}
                          className="px-2 py-1 rounded-full bg-surface-container border border-outline-variant"
                        >
                          <Text className="text-on-surface text-[11px] font-semibold">
                            {item}
                          </Text>
                        </View>
                      ))}
                    </View>
                    <Pressable className="mt-3 h-10 rounded-lg bg-tertiary items-center justify-center flex-row gap-2 active:opacity-90">
                      <Ionicons
                        name={
                          r.status === "in_progress"
                            ? "checkmark-done"
                            : "play-circle"
                        }
                        size={16}
                        color="#ffffff"
                      />
                      <Text className="text-white text-xs font-bold">
                        {r.status === "in_progress"
                          ? "Tandai Selesai"
                          : "Mulai Tangani"}
                      </Text>
                    </Pressable>
                  </View>
                );
              })}
            </View>

            {/* Deliveries */}
            <View className="flex-row items-end justify-between mb-3">
              <Text
                className={`font-bold text-on-surface ${isTablet ? "text-xl" : "text-lg"}`}
              >
                Pengantaran Dokumen
              </Text>
              <View className="px-3 py-1 rounded-full bg-tertiary/10">
                <Text className="text-tertiary text-xs font-bold">
                  {DELIVERIES.length} item
                </Text>
              </View>
            </View>
            <View className="gap-3 mb-6">
              {DELIVERIES.map((d) => (
                <View
                  key={d.id}
                  className="p-4 rounded-2xl border border-outline-variant bg-surface-container-lowest flex-row items-center gap-3"
                >
                  <View className="w-11 h-11 rounded-xl bg-primary/15 items-center justify-center">
                    <Ionicons name="document" size={22} color="#005bbf" />
                  </View>
                  <View className="flex-1">
                    <Text className="font-bold text-on-surface text-sm">
                      {d.type}
                    </Text>
                    <View className="flex-row items-center gap-1 mt-1">
                      <Text className="text-on-surface-variant text-xs">
                        {d.from}
                      </Text>
                      <Ionicons
                        name="arrow-forward"
                        size={10}
                        color="#5a6072"
                      />
                      <Text className="text-on-surface-variant text-xs font-semibold">
                        {d.to}
                      </Text>
                    </View>
                  </View>
                  <View className="items-end">
                    <Text className="text-on-surface-variant text-[10px]">
                      Jam {d.waktu}
                    </Text>
                    <Pressable className="mt-1 px-3 py-1 rounded-full bg-primary active:opacity-80">
                      <Text className="text-white text-[10px] font-bold">
                        Antar
                      </Text>
                    </Pressable>
                  </View>
                </View>
              ))}
            </View>
          </View>

          {/* RIGHT — Area Tasks */}
          <View className={isTablet ? "flex-1" : ""}>
            <View className="flex-row items-end justify-between mb-3">
              <Text
                className={`font-bold text-on-surface ${isTablet ? "text-xl" : "text-lg"}`}
              >
                Jadwal Area
              </Text>
              <Text className="text-tertiary text-xs font-bold bg-tertiary/10 px-3 py-1 rounded-full">
                Office Boy
              </Text>
            </View>
            <View className="gap-2">
              {AREA_TASKS.map((t) => {
                const tone = STATUS_TONE[t.status];
                const isDone = t.status === "done";
                return (
                  <View
                    key={t.id}
                    className={`p-3 rounded-2xl border-2 ${
                      isDone
                        ? "bg-secondary/5 border-secondary/30"
                        : "bg-surface-container-lowest border-outline-variant"
                    }`}
                  >
                    <View className="flex-row items-center gap-3">
                      <View
                        className={`w-11 h-11 rounded-xl ${tone.bg} items-center justify-center`}
                      >
                        <Ionicons
                          name={t.icon}
                          size={20}
                          color={
                            t.status === "done"
                              ? "#0a7e3e"
                              : t.status === "in_progress"
                                ? "#005bbf"
                                : "#5a6072"
                          }
                        />
                      </View>
                      <View className="flex-1">
                        <View className="flex-row items-center gap-2">
                          <Text
                            className={`font-bold text-on-surface ${
                              isDone ? "line-through opacity-60" : ""
                            }`}
                            numberOfLines={1}
                          >
                            {t.area}
                          </Text>
                          <View className="px-2 py-0.5 rounded-full bg-on-surface-variant/10">
                            <Text className="text-on-surface-variant text-[10px] font-bold">
                              {t.time}
                            </Text>
                          </View>
                        </View>
                        {t.note ? (
                          <Text
                            className="text-on-surface-variant text-xs mt-0.5"
                            numberOfLines={1}
                          >
                            {t.note}
                          </Text>
                        ) : null}
                      </View>
                      <View className={`px-2 py-1 rounded-full ${tone.bg}`}>
                        <Text className={`text-[10px] font-bold ${tone.text}`}>
                          {tone.label}
                        </Text>
                      </View>
                    </View>

                    {!isDone && (
                      <View className="flex-row gap-2 mt-3">
                        <Pressable
                          onPress={() => router.push("/(tabs)/laporan")}
                          className="px-3 h-9 rounded-lg bg-surface-container-highest items-center justify-center"
                        >
                          <Ionicons
                            name="camera-outline"
                            size={16}
                            color="#414754"
                          />
                        </Pressable>
                        <Pressable
                          onPress={() => router.push("/(tabs)/laporan")}
                          className="flex-1 h-9 rounded-lg bg-tertiary items-center justify-center flex-row gap-1 active:opacity-90"
                        >
                          <Ionicons
                            name="checkmark"
                            size={14}
                            color="#ffffff"
                          />
                          <Text className="text-white text-xs font-bold">
                            {t.status === "in_progress"
                              ? "Selesaikan"
                              : "Mulai"}
                          </Text>
                        </Pressable>
                      </View>
                    )}
                  </View>
                );
              })}
            </View>
          </View>
        </View>
      </ScrollView>
    </View>
  );
}
