import { useMemo, useState } from "react";
import {
  ActivityIndicator,
  Pressable,
  RefreshControl,
  ScrollView,
  Text,
  View,
} from "react-native";
import { useRouter } from "expo-router";
import { Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";
import { useAuthStore } from "@/stores/auth-store";
import { ROLE_LABEL } from "@/constants/role";
import { NotificationBell } from "@/components/NotificationBell";
import { DashboardHeader } from "@/components/DashboardHeader";
import { BarChart, type BarItem } from "@/components/charts/BarChart";
import { useIsTablet } from "@/lib/useIsTablet";
import {
  useUnits,
  usePendingApprovals,
  useJadwalToday,
  useFieldJadwalToday,
  useDashboard,
} from "@/lib/hooks";
import type { ApprovalItem, ApprovalScope } from "@/lib/types";

type Scope = "all" | "kebersihan" | "satpam" | "ob" | "toko";
type IoniconName = React.ComponentProps<typeof Ionicons>["name"];

interface ScopeConfig {
  key: Scope;
  label: string;
  shortLabel: string;
  iconLib: "ionicons" | "mci";
  icon: string;
  color: string;
}

const SCOPES: ScopeConfig[] = [
  {
    key: "all",
    label: "Semua Tim",
    shortLabel: "Semua",
    iconLib: "ionicons",
    icon: "apps",
    color: "#005bbf",
  },
  {
    key: "kebersihan",
    label: "Petugas Kebersihan",
    shortLabel: "Kebersihan",
    iconLib: "mci",
    icon: "broom",
    color: "#0a7e3e",
  },
  {
    key: "satpam",
    label: "Satpam / Security",
    shortLabel: "Satpam",
    iconLib: "mci",
    icon: "shield-account",
    color: "#005bbf",
  },
  {
    key: "ob",
    label: "Office Boy",
    shortLabel: "Office Boy",
    iconLib: "mci",
    icon: "coffee-outline",
    color: "#7e5a17",
  },
  {
    key: "toko",
    label: "Petugas Toko",
    shortLabel: "Toko",
    iconLib: "mci",
    icon: "storefront-outline",
    color: "#0891b2",
  },
];

interface ScopeStats {
  pendingApproval: number;
  pendingTrend: number[];
  jadwalHariIni: number;
  laporanHariIni: number;
  completionRate: number;
  jadwalTrend: number[];
  totalPetugas: number;
  petugasTrend: number[];
  laporanBulanIni: number;
  laporanTrend: number[];
  keterlambatan: number;
  keterlambatanTrend: number[];
}

interface MonthlySummary {
  total: number;
  ontime: number;
  ontimePct: number;
  late: number;
  latePct: number;
  expired: number;
  expiredPct: number;
  avgRating: number;
}

interface ScheduleItem {
  id: number;
  shift: string;
  petugas: string;
  lokasi: string;
  status: "selesai" | "berjalan" | "belum" | "terlambat";
  keterangan?: string;
  tim: Scope;
}

// -------------------- MOCK DATA --------------------
const STATS_BY_SCOPE: Record<Scope, ScopeStats> = {
  all: {
    pendingApproval: 4,
    pendingTrend: [3, 5, 6, 4, 4],
    jadwalHariIni: 42,
    laporanHariIni: 33,
    completionRate: 79,
    jadwalTrend: [30, 32, 36, 38, 42],
    totalPetugas: 24,
    petugasTrend: [20, 21, 22, 23, 24],
    laporanBulanIni: 486,
    laporanTrend: [100, 200, 300, 400, 486],
    keterlambatan: 3,
    keterlambatanTrend: [1, 2, 3, 3, 3],
  },
  kebersihan: {
    pendingApproval: 2,
    pendingTrend: [1, 2, 3, 2, 2],
    jadwalHariIni: 18,
    laporanHariIni: 14,
    completionRate: 78,
    jadwalTrend: [10, 12, 14, 14, 18],
    totalPetugas: 9,
    petugasTrend: [7, 8, 8, 9, 9],
    laporanBulanIni: 186,
    laporanTrend: [40, 80, 120, 160, 186],
    keterlambatan: 2,
    keterlambatanTrend: [0, 1, 2, 2, 2],
  },
  satpam: {
    pendingApproval: 1,
    pendingTrend: [1, 1, 2, 1, 1],
    jadwalHariIni: 12,
    laporanHariIni: 10,
    completionRate: 83,
    jadwalTrend: [10, 10, 11, 12, 12],
    totalPetugas: 6,
    petugasTrend: [5, 5, 6, 6, 6],
    laporanBulanIni: 168,
    laporanTrend: [30, 60, 90, 130, 168],
    keterlambatan: 0,
    keterlambatanTrend: [0, 1, 0, 0, 0],
  },
  ob: {
    pendingApproval: 1,
    pendingTrend: [0, 1, 1, 2, 1],
    jadwalHariIni: 8,
    laporanHariIni: 7,
    completionRate: 88,
    jadwalTrend: [6, 7, 7, 8, 8],
    totalPetugas: 5,
    petugasTrend: [4, 4, 5, 5, 5],
    laporanBulanIni: 92,
    laporanTrend: [20, 40, 60, 80, 92],
    keterlambatan: 1,
    keterlambatanTrend: [0, 0, 1, 0, 1],
  },
  toko: {
    pendingApproval: 0,
    pendingTrend: [0, 1, 0, 0, 0],
    jadwalHariIni: 4,
    laporanHariIni: 2,
    completionRate: 50,
    jadwalTrend: [4, 4, 4, 4, 4],
    totalPetugas: 4,
    petugasTrend: [3, 4, 4, 4, 4],
    laporanBulanIni: 40,
    laporanTrend: [10, 18, 25, 32, 40],
    keterlambatan: 0,
    keterlambatanTrend: [0, 0, 0, 0, 0],
  },
};

const MONTHLY_SUMMARY_BY_SCOPE: Record<Scope, MonthlySummary> = {
  all: {
    total: 486,
    ontime: 398,
    ontimePct: 81.9,
    late: 73,
    latePct: 15.0,
    expired: 15,
    expiredPct: 3.1,
    avgRating: 4.6,
  },
  kebersihan: {
    total: 186,
    ontime: 152,
    ontimePct: 81.7,
    late: 28,
    latePct: 15.1,
    expired: 6,
    expiredPct: 3.2,
    avgRating: 4.6,
  },
  satpam: {
    total: 168,
    ontime: 148,
    ontimePct: 88.1,
    late: 16,
    latePct: 9.5,
    expired: 4,
    expiredPct: 2.4,
    avgRating: 4.7,
  },
  ob: {
    total: 92,
    ontime: 78,
    ontimePct: 84.8,
    late: 11,
    latePct: 12.0,
    expired: 3,
    expiredPct: 3.2,
    avgRating: 4.5,
  },
  toko: {
    total: 40,
    ontime: 20,
    ontimePct: 50.0,
    late: 18,
    latePct: 45.0,
    expired: 2,
    expiredPct: 5.0,
    avgRating: 4.2,
  },
};

const BAR_BY_SCOPE: Record<Scope, BarItem[]> = {
  all: [
    { label: "Des", value: 412 },
    { label: "Jan", value: 428 },
    { label: "Feb", value: 454 },
    { label: "Mar", value: 470 },
    { label: "Apr", value: 482 },
    { label: "Mei", value: 486 },
  ],
  kebersihan: [
    { label: "Des", value: 162 },
    { label: "Jan", value: 158 },
    { label: "Feb", value: 174 },
    { label: "Mar", value: 180 },
    { label: "Apr", value: 192 },
    { label: "Mei", value: 186 },
  ],
  satpam: [
    { label: "Des", value: 150 },
    { label: "Jan", value: 158 },
    { label: "Feb", value: 162 },
    { label: "Mar", value: 165 },
    { label: "Apr", value: 170 },
    { label: "Mei", value: 168 },
  ],
  ob: [
    { label: "Des", value: 78 },
    { label: "Jan", value: 82 },
    { label: "Feb", value: 86 },
    { label: "Mar", value: 90 },
    { label: "Apr", value: 88 },
    { label: "Mei", value: 92 },
  ],
  toko: [
    { label: "Des", value: 22 },
    { label: "Jan", value: 30 },
    { label: "Feb", value: 32 },
    { label: "Mar", value: 35 },
    { label: "Apr", value: 32 },
    { label: "Mei", value: 40 },
  ],
};

const ALL_SCHEDULE: ScheduleItem[] = [
  // Kebersihan
  {
    id: 1,
    shift: "Pagi",
    petugas: "Rahmat Hidayat",
    lokasi: "Toilet Lt.1 - Gedung A",
    status: "selesai",
    keterangan: "Selesai 08:45",
    tim: "kebersihan",
  },
  {
    id: 2,
    shift: "Pagi",
    petugas: "Siti Nurhaliza",
    lokasi: "Lobi Utama",
    status: "berjalan",
    keterangan: "Mulai 08:30",
    tim: "kebersihan",
  },
  {
    id: 3,
    shift: "Siang",
    petugas: "Budi Hartono",
    lokasi: "Ruang Rapat Besar",
    status: "terlambat",
    keterangan: "Telat 25 menit",
    tim: "kebersihan",
  },
  // Satpam
  {
    id: 4,
    shift: "Pagi",
    petugas: "Pak Joko",
    lokasi: "Gate Utama",
    status: "selesai",
    keterangan: "Patroli 08:00 OK",
    tim: "satpam",
  },
  {
    id: 5,
    shift: "Siang",
    petugas: "Pak Hendro",
    lokasi: "Parkir Belakang",
    status: "berjalan",
    keterangan: "Sedang patroli",
    tim: "satpam",
  },
  // OB
  {
    id: 6,
    shift: "Pagi",
    petugas: "Rahmat OB",
    lokasi: "Pantry Lt.1",
    status: "selesai",
    keterangan: "Setup pagi OK",
    tim: "ob",
  },
  {
    id: 7,
    shift: "Pagi",
    petugas: "Andi OB",
    lokasi: "Ruang Rapat Direksi",
    status: "berjalan",
    keterangan: "Setup untuk rapat 09:30",
    tim: "ob",
  },
  // Toko
  {
    id: 8,
    shift: "Pagi",
    petugas: "Mbak Sari",
    lokasi: "Toko Utama",
    status: "berjalan",
    keterangan: "Shift aktif sejak 08:00",
    tim: "toko",
  },
];

const STATUS_TONE: Record<
  ScheduleItem["status"],
  { bg: string; text: string; icon: IoniconName; label: string; color: string }
> = {
  selesai: {
    bg: "bg-secondary/15",
    text: "text-secondary",
    icon: "checkmark-circle",
    label: "Selesai",
    color: "#0a7e3e",
  },
  berjalan: {
    bg: "bg-primary/15",
    text: "text-primary",
    icon: "play-circle",
    label: "Berjalan",
    color: "#005bbf",
  },
  belum: {
    bg: "bg-on-surface-variant/15",
    text: "text-on-surface-variant",
    icon: "ellipse-outline",
    label: "Belum",
    color: "#5a6072",
  },
  terlambat: {
    bg: "bg-error/15",
    text: "text-error",
    icon: "alert-circle",
    label: "Terlambat",
    color: "#d62828",
  },
};

const SCOPE_BADGE_COLOR: Record<Scope, string> = {
  all: "#005bbf",
  kebersihan: "#0a7e3e",
  satpam: "#005bbf",
  ob: "#7e5a17",
  toko: "#0891b2",
};

export function SupervisorDashboard() {
  const router = useRouter();
  const isTablet = useIsTablet();
  const user = useAuthStore((s) => s.user);
  const [scope, setScope] = useState<Scope>("all");
  // Per-unit approval filter (null = all units).
  const [unitId, setUnitId] = useState<number | null>(null);

  const barData = BAR_BY_SCOPE[scope];

  // --- Live approval queue (real API), filtered by scope + unit ---
  const unitsQuery = useUnits();
  const approvalsQuery = usePendingApprovals(scope as ApprovalScope | "all", unitId);
  const pending = approvalsQuery.data ?? [];

  // --- Live queries for Today's Schedule across all 4 teams ---
  const todayKebersihanQuery = useJadwalToday();
  const todaySatpamQuery = useFieldJadwalToday("satpam");
  const todayObQuery = useFieldJadwalToday("ob");
  const todayTokoQuery = useFieldJadwalToday("toko");
  const dashboardQuery = useDashboard();

  const isScheduleLoading =
    todayKebersihanQuery.isLoading ||
    todaySatpamQuery.isLoading ||
    todayObQuery.isLoading ||
    todayTokoQuery.isLoading;

  const schedules = useMemo(() => {
    const mapJadwal = (j: any, tim: Scope): ScheduleItem => {
      let status: ScheduleItem["status"] = "belum";
      if (j.status === "completed") status = "selesai";
      else if (j.status === "in_progress") status = "berjalan";
      else if (j.status === "missed") status = "terlambat";

      const timeStr = j.jam_mulai && j.jam_selesai ? `${j.jam_mulai} - ${j.jam_selesai}` : "";

      return {
        id: j.id,
        shift: j.shift ? (j.shift.charAt(0).toUpperCase() + j.shift.slice(1)) : "Pagi",
        petugas: j.petugas?.name ?? "-",
        lokasi: j.lokasi?.nama_lokasi ?? "-",
        status,
        keterangan: timeStr || undefined,
        tim,
      };
    };

    const list: ScheduleItem[] = [];
    if (scope === "all" || scope === "kebersihan") {
      (todayKebersihanQuery.data ?? []).forEach((j) => list.push(mapJadwal(j, "kebersihan")));
    }
    if (scope === "all" || scope === "satpam") {
      (todaySatpamQuery.data ?? []).forEach((j) => list.push(mapJadwal(j, "satpam")));
    }
    if (scope === "all" || scope === "ob") {
      (todayObQuery.data ?? []).forEach((j) => list.push(mapJadwal(j, "ob")));
    }
    if (scope === "all" || scope === "toko") {
      (todayTokoQuery.data ?? []).forEach((j) => list.push(mapJadwal(j, "toko")));
    }
    return list;
  }, [
    scope,
    todayKebersihanQuery.data,
    todaySatpamQuery.data,
    todayObQuery.data,
    todayTokoQuery.data,
  ]);

  const dynamicStats = useMemo(() => {
    const total = schedules.length;
    const completed = schedules.filter((s) => s.status === "selesai").length;
    const late = schedules.filter((s) => s.status === "terlambat").length;
    const uniquePetugas = new Set(schedules.map((s) => s.petugas)).size;
    const completionRate = total > 0 ? Math.round((completed / total) * 100) : 0;

    return {
      pendingApproval: pending.length,
      jadwalHariIni: total,
      laporanHariIni: completed,
      completionRate,
      totalPetugas: uniquePetugas,
      keterlambatan: late,
    };
  }, [schedules, pending.length]);

  const monthly = useMemo(() => {
    if ((scope === "all" || scope === "kebersihan") && dashboardQuery.data?.monthly_stats) {
      const m = dashboardQuery.data.monthly_stats as any;
      const total = m.reports?.total ?? 0;
      const approved = m.reports?.approved ?? 0;
      const late = m.performance?.late_submissions ?? 0;
      const pendingCount = m.performance?.pending ?? 0;

      const ontimePct = total > 0 ? Math.round(((approved - late) / total) * 1000) / 10 : 0;
      const latePct = total > 0 ? Math.round((late / total) * 1000) / 10 : 0;
      const expiredPct = total > 0 ? Math.round((pendingCount / total) * 1000) / 10 : 0;

      return {
        total,
        ontime: Math.max(0, approved - late),
        ontimePct,
        late,
        latePct,
        expired: pendingCount,
        expiredPct,
        avgRating: m.reports?.average_rating ?? 5.0,
      };
    }
    return MONTHLY_SUMMARY_BY_SCOPE[scope];
  }, [scope, dashboardQuery.data]);

  const handleRefresh = async () => {
    await Promise.all([
      todayKebersihanQuery.refetch(),
      todaySatpamQuery.refetch(),
      todayObQuery.refetch(),
      todayTokoQuery.refetch(),
      approvalsQuery.refetch(),
      dashboardQuery.refetch(),
      unitsQuery.refetch(),
    ]);
  };


  const currentScopeConfig = SCOPES.find((s) => s.key === scope) ?? SCOPES[0];

  const headerPad = isTablet ? "px-8" : "px-5";
  const contentPad = isTablet ? 32 : 20;

  const renderScopeIcon = (s: ScopeConfig, color: string, size: number) =>
    s.iconLib === "mci" ? (
      <MaterialCommunityIcons name={s.icon as never} size={size} color={color} />
    ) : (
      <Ionicons name={s.icon as IoniconName} size={size} color={color} />
    );

  // Team scope filter — rendered above the summary. Edge-bleeds horizontally.
  const ScopeBar = (
    <View className="mb-4">
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        style={{ marginHorizontal: -contentPad }}
        contentContainerStyle={{ paddingHorizontal: contentPad, paddingVertical: 2 }}
      >
        {SCOPES.map((s, idx) => {
          const active = scope === s.key;
          return (
            <Pressable
              key={s.key}
              onPress={() => setScope(s.key)}
              style={{
                height: 38,
                marginRight: idx === SCOPES.length - 1 ? 0 : 8,
                paddingHorizontal: 14,
                borderRadius: 999,
                borderWidth: 1,
                flexDirection: "row",
                alignItems: "center",
                borderColor: active ? s.color : "#e1e3e4",
                backgroundColor: active ? s.color : "#ffffff",
              }}
            >
              {renderScopeIcon(s, active ? "#ffffff" : s.color, 16)}
              <Text
                style={{
                  fontSize: 13,
                  fontWeight: "600",
                  lineHeight: 18,
                  color: active ? "#ffffff" : "#1a1c1e",
                  marginLeft: 6,
                }}
              >
                {s.shortLabel}
              </Text>
            </Pressable>
          );
        })}
      </ScrollView>
    </View>
  );

  // Unit filter — rendered below the summary, directly above the approvals.
  const UnitBar = (
    <View className="mb-5">
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        style={{ marginHorizontal: -contentPad }}
        contentContainerStyle={{
          paddingHorizontal: contentPad,
          paddingTop: 8,
          paddingBottom: 4,
          alignItems: "center",
        }}
      >
        <View className="flex-row items-center gap-1 mr-2">
          <Ionicons name="business-outline" size={14} color="#5a6072" />
          <Text className="text-on-surface-variant text-xs font-semibold">
            Unit:
          </Text>
        </View>
        {[{ id: null, nama_unit: "Semua Unit" }, ...(unitsQuery.data ?? [])].map(
          (u) => {
            const active = unitId === u.id;
            return (
              <Pressable
                key={String(u.id)}
                onPress={() => setUnitId(u.id as number | null)}
                style={{
                  height: 32,
                  marginRight: 8,
                  paddingHorizontal: 12,
                  borderRadius: 999,
                  borderWidth: 1,
                  justifyContent: "center",
                  borderColor: active ? "#7e5a17" : "#e1e3e4",
                  backgroundColor: active ? "#7e5a17" : "#ffffff",
                }}
              >
                <Text
                  style={{
                    fontSize: 12,
                    fontWeight: "600",
                    color: active ? "#ffffff" : "#414754",
                  }}
                >
                  {u.nama_unit}
                </Text>
              </Pressable>
            );
          }
        )}
      </ScrollView>
    </View>
  );

  return (
    <View className="flex-1 bg-background">
      <DashboardHeader
        colors={["#0a5fd6", "#0a3aa0"]}
        title={`Halo, ${user?.name ?? "Supervisor"} 👋`}
        subtitle={user ? ROLE_LABEL[user.role] : "Supervisor"}
        icon={<MaterialCommunityIcons name="account-tie" size={22} color="#fff" />}
        right={<NotificationBell size={22} color="#fff" />}
      />

      <ScrollView
        contentContainerStyle={{ padding: contentPad, paddingBottom: 120 }}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={
              todayKebersihanQuery.isFetching ||
              todaySatpamQuery.isFetching ||
              todayObQuery.isFetching ||
              todayTokoQuery.isFetching ||
              approvalsQuery.isFetching ||
              dashboardQuery.isFetching
            }
            onRefresh={handleRefresh}
          />
        }
      >
        {/* Team scope filter — above the summary */}
        {ScopeBar}

        {/* Stats Overview — clean uniform grid */}
        <Text className="font-bold text-on-surface text-lg mb-3">
          Ringkasan{" "}
          {scope === "all" ? "Semua Tim" : `Tim ${currentScopeConfig.shortLabel}`}
        </Text>
        <View className="flex-row flex-wrap -mx-1.5 mb-4">
          {(
            [
              {
                icon: "hourglass-outline",
                label: "Menunggu",
                hint: "Approval",
                value: dynamicStats.pendingApproval,
                color: dynamicStats.pendingApproval > 0 ? "#d62828" : "#0a7e3e",
              },
              {
                icon: "calendar-outline",
                label: "Jadwal",
                hint: `${dynamicStats.completionRate}% selesai`,
                value: dynamicStats.jadwalHariIni,
                color: "#e08a14",
              },
              {
                icon: "people-outline",
                label: "Petugas",
                hint: "Aktif",
                value: dynamicStats.totalPetugas,
                color: "#0a5fd6",
              },
              {
                icon: "time-outline",
                label: "Terlambat",
                hint: "Hari ini",
                value: dynamicStats.keterlambatan,
                color: dynamicStats.keterlambatan > 0 ? "#d62828" : "#0a7e3e",
              },
            ] as const
          ).map((t) => (
            <View
              key={t.label}
              className={`${isTablet ? "w-1/4" : "w-1/2"} px-1.5 mb-3`}
            >
              <View className="bg-surface-container-lowest rounded-2xl p-4 border border-outline-variant">
                <View
                  className="w-9 h-9 rounded-xl items-center justify-center mb-3"
                  style={{ backgroundColor: `${t.color}1a` }}
                >
                  <Ionicons
                    name={t.icon as IoniconName}
                    size={18}
                    color={t.color}
                  />
                </View>
                <Text className="text-on-surface font-bold text-2xl">
                  {t.value}
                </Text>
                <Text
                  className="text-on-surface-variant text-xs mt-0.5"
                  numberOfLines={1}
                >
                  {t.label} · {t.hint}
                </Text>
              </View>
            </View>
          ))}
        </View>

        {/* Unit filter — below summary, directly above approvals */}
        {UnitBar}

        {/* 2-col on tablet */}
        <View className={isTablet ? "flex-row gap-6" : ""}>
          {/* LEFT */}
          <View className={isTablet ? "flex-[3]" : ""}>
            {/* Pending Approvals */}
            <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6">
              <View className="flex-row items-center justify-between mb-3">
                <View>
                  <Text
                    className={`font-bold text-on-surface ${isTablet ? "text-lg" : "text-base"}`}
                  >
                    Menunggu Approval
                  </Text>
                  <Text className="text-on-surface-variant text-xs">
                    {scope === "all"
                      ? "Lintas tim"
                      : `Tim ${currentScopeConfig.shortLabel}`}
                  </Text>
                </View>
                <View className="px-3 py-1 rounded-full bg-error/10">
                  <Text className="text-error text-xs font-bold">
                    {pending.length} laporan
                  </Text>
                </View>
              </View>

              {approvalsQuery.isLoading ? (
                <View className="items-center py-6">
                  <ActivityIndicator color="#005bbf" />
                  <Text className="text-on-surface-variant text-sm mt-2">
                    Memuat approval...
                  </Text>
                </View>
              ) : approvalsQuery.isError ? (
                <View className="items-center py-6">
                  <Ionicons name="cloud-offline-outline" size={40} color="#c1c6d6" />
                  <Text className="text-on-surface-variant text-sm mt-2 text-center px-4">
                    {(approvalsQuery.error as Error)?.message ??
                      "Gagal memuat data approval."}
                  </Text>
                  <Pressable
                    onPress={() => approvalsQuery.refetch()}
                    className="mt-3 px-4 h-9 rounded-full bg-primary items-center justify-center"
                  >
                    <Text className="text-on-primary text-xs font-bold">
                      Coba lagi
                    </Text>
                  </Pressable>
                </View>
              ) : pending.length === 0 ? (
                <View className="items-center py-6">
                  <Ionicons
                    name="checkmark-done-circle-outline"
                    size={48}
                    color="#0a7e3e"
                  />
                  <Text className="text-on-surface-variant text-sm mt-2">
                    Tidak ada laporan menunggu approval
                  </Text>
                </View>
              ) : (
                <View className="gap-3">
                  {pending.map((r) => {
                    const teamConfig = SCOPES.find((s) => s.key === r.scope);
                    return (
                      <Pressable
                        key={`${r.scope}-${r.id}`}
                        onPress={() =>
                          router.push({
                            pathname: "/admin/laporan-detail",
                            params: {
                              id: r.id,
                              scope: r.scope,
                              petugas: r.petugasName,
                              lokasi: r.lokasiName,
                              unit: r.unit?.nama_unit ?? "",
                              tanggal: r.tanggal,
                              summary: r.summary,
                              status: r.status,
                            },
                          })
                        }
                        className="p-3 rounded-xl border border-outline-variant bg-surface active:opacity-80"
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
                                {r.petugasName}
                              </Text>
                              <Text className="text-on-surface-variant text-[10px]">
                                {r.tanggal}
                              </Text>
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
                                {r.lokasiName}
                              </Text>
                              {scope === "all" && teamConfig && (
                                <View
                                  className="px-2 py-0.5 rounded-full flex-row items-center gap-1"
                                  style={{
                                    backgroundColor: `${SCOPE_BADGE_COLOR[r.scope]}1a`,
                                  }}
                                >
                                  <Text
                                    className="text-[10px] font-bold"
                                    style={{ color: SCOPE_BADGE_COLOR[r.scope] }}
                                  >
                                    {teamConfig.shortLabel}
                                  </Text>
                                </View>
                              )}
                            </View>
                            {r.unit && (
                              <View className="flex-row items-center gap-1 mt-0.5">
                                <Ionicons
                                  name="business-outline"
                                  size={12}
                                  color="#7e5a17"
                                />
                                <Text className="text-tertiary text-[11px] font-semibold">
                                  {r.unit.nama_unit}
                                </Text>
                              </View>
                            )}
                            <Text
                              className="text-on-surface-variant text-xs mt-1"
                              numberOfLines={2}
                            >
                              {r.summary}
                            </Text>
                          </View>
                        </View>

                        <View className="flex-row items-center justify-end gap-1 mt-2 pt-2 border-t border-outline-variant/50">
                          <Text className="text-primary text-xs font-bold">
                            Tinjau & approve
                          </Text>
                          <Ionicons
                            name="chevron-forward"
                            size={14}
                            color="#0a5fd6"
                          />
                        </View>
                      </Pressable>
                    );
                  })}
                </View>
              )}
            </View>

            {/* Today's Schedule */}
            <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6">
              <View className="flex-row items-center justify-between mb-3">
                <View>
                  <Text
                    className={`font-bold text-on-surface ${isTablet ? "text-lg" : "text-base"}`}
                  >
                    Jadwal Hari Ini
                  </Text>
                  <Text className="text-on-surface-variant text-xs">
                    {dynamicStats.laporanHariIni} dari {dynamicStats.jadwalHariIni} selesai
                  </Text>
                </View>
                <View
                  className="w-12 h-12 rounded-full items-center justify-center"
                  style={{
                    borderWidth: 3,
                    borderColor:
                      dynamicStats.completionRate >= 80
                        ? "#0a7e3e"
                        : dynamicStats.completionRate >= 50
                          ? "#e08a14"
                          : "#d62828",
                  }}
                >
                  <Text
                    className="font-bold text-xs"
                    style={{
                      color:
                        dynamicStats.completionRate >= 80
                          ? "#0a7e3e"
                          : dynamicStats.completionRate >= 50
                            ? "#e08a14"
                            : "#d62828",
                    }}
                  >
                    {dynamicStats.completionRate}%
                  </Text>
                </View>
              </View>

              {isScheduleLoading ? (
                <View className="items-center py-6">
                  <ActivityIndicator color="#005bbf" />
                  <Text className="text-on-surface-variant text-sm mt-2">
                    Memuat jadwal...
                  </Text>
                </View>
              ) : schedules.length === 0 ? (
                <View className="items-center py-6">
                  <Ionicons name="calendar-outline" size={48} color="#c1c6d6" />
                  <Text className="text-on-surface-variant text-sm mt-2">
                    Tidak ada jadwal untuk tim ini
                  </Text>
                </View>
              ) : (
                <View className="gap-2">
                  {schedules.map((s) => {
                    const tone = STATUS_TONE[s.status];
                    const teamConfig = SCOPES.find((sc) => sc.key === s.tim);
                    return (
                      <View
                        key={s.id}
                        className="flex-row items-center gap-3 p-3 rounded-xl border border-outline-variant bg-surface"
                      >
                        <View
                          className={`w-10 h-10 rounded-full ${tone.bg} items-center justify-center`}
                        >
                          <Ionicons
                            name={tone.icon}
                            size={20}
                            color={tone.color}
                          />
                        </View>
                        <View className="flex-1">
                          <View className="flex-row items-center gap-2 flex-wrap">
                            <Text
                              className="font-bold text-on-surface"
                              numberOfLines={1}
                            >
                              {s.petugas}
                            </Text>
                            <View className="px-2 py-0.5 rounded-full bg-primary/10">
                              <Text className="text-primary text-[10px] font-bold">
                                {s.shift}
                              </Text>
                            </View>
                            {scope === "all" && teamConfig && (
                              <View
                                className="px-2 py-0.5 rounded-full"
                                style={{
                                  backgroundColor: `${SCOPE_BADGE_COLOR[s.tim]}1a`,
                                }}
                              >
                                <Text
                                  className="text-[10px] font-bold"
                                  style={{ color: SCOPE_BADGE_COLOR[s.tim] }}
                                >
                                  {teamConfig.shortLabel}
                                </Text>
                              </View>
                            )}
                          </View>
                          <Text
                            className="text-on-surface-variant text-xs"
                            numberOfLines={1}
                          >
                            {s.lokasi}
                          </Text>
                          {s.keterangan ? (
                            <Text
                              className="text-on-surface-variant text-[11px] mt-0.5"
                              numberOfLines={1}
                            >
                              {s.keterangan}
                            </Text>
                          ) : null}
                        </View>
                        <View className={`px-2 py-1 rounded-full ${tone.bg}`}>
                          <Text className={`text-[10px] font-bold ${tone.text}`}>
                            {tone.label}
                          </Text>
                        </View>
                      </View>
                    );
                  })}
                </View>
              )}
            </View>
          </View>

          {/* RIGHT */}
          <View className={isTablet ? "flex-[2]" : ""}>
            {/* Monthly Report Summary */}
            <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6">
              <View className="flex-row items-center justify-between mb-4">
                <View>
                  <Text
                    className={`font-bold text-on-surface ${isTablet ? "text-lg" : "text-base"}`}
                  >
                    Laporan Bulan Ini
                  </Text>
                  <Text className="text-on-surface-variant text-xs">
                    {scope === "all"
                      ? "Semua tim · Juni 2026"
                      : `${currentScopeConfig.label} · Juni 2026`}
                  </Text>
                </View>
                <View className="flex-row items-center gap-1">
                  <Ionicons name="star" size={16} color="#e08a14" />
                  <Text className="text-tertiary text-base font-bold">
                    {monthly.avgRating}
                  </Text>
                  <Text className="text-on-surface-variant text-xs">/ 5</Text>
                </View>
              </View>

              <View className="gap-3">
                <View className="flex-row items-center gap-3 p-3 rounded-xl bg-primary/5">
                  <View className="w-10 h-10 rounded-xl bg-primary/15 items-center justify-center">
                    <Ionicons name="documents" size={20} color="#005bbf" />
                  </View>
                  <View className="flex-1">
                    <Text className="text-on-surface-variant text-xs">
                      Total Laporan
                    </Text>
                    <Text className="text-on-surface text-xl font-bold">
                      {monthly.total}
                    </Text>
                  </View>
                </View>

                <View>
                  <View className="flex-row items-center justify-between mb-1">
                    <View className="flex-row items-center gap-2">
                      <View className="w-2.5 h-2.5 rounded-full bg-secondary" />
                      <Text className="text-on-surface text-sm font-semibold">
                        Tepat Waktu
                      </Text>
                    </View>
                    <Text className="text-on-surface text-sm font-bold">
                      {monthly.ontime}{" "}
                      <Text className="text-secondary text-xs">
                        ({monthly.ontimePct}%)
                      </Text>
                    </Text>
                  </View>
                  <View className="h-2 bg-on-surface-variant/10 rounded-full overflow-hidden">
                    <View
                      className="h-full bg-secondary"
                      style={{ width: `${monthly.ontimePct}%` }}
                    />
                  </View>
                </View>

                <View>
                  <View className="flex-row items-center justify-between mb-1">
                    <View className="flex-row items-center gap-2">
                      <View className="w-2.5 h-2.5 rounded-full bg-tertiary" />
                      <Text className="text-on-surface text-sm font-semibold">
                        Terlambat
                      </Text>
                    </View>
                    <Text className="text-on-surface text-sm font-bold">
                      {monthly.late}{" "}
                      <Text className="text-tertiary text-xs">
                        ({monthly.latePct}%)
                      </Text>
                    </Text>
                  </View>
                  <View className="h-2 bg-on-surface-variant/10 rounded-full overflow-hidden">
                    <View
                      className="h-full bg-tertiary"
                      style={{ width: `${monthly.latePct}%` }}
                    />
                  </View>
                </View>

                <View>
                  <View className="flex-row items-center justify-between mb-1">
                    <View className="flex-row items-center gap-2">
                      <View className="w-2.5 h-2.5 rounded-full bg-error" />
                      <Text className="text-on-surface text-sm font-semibold">
                        Tidak Lapor
                      </Text>
                    </View>
                    <Text className="text-on-surface text-sm font-bold">
                      {monthly.expired}{" "}
                      <Text className="text-error text-xs">
                        ({monthly.expiredPct}%)
                      </Text>
                    </Text>
                  </View>
                  <View className="h-2 bg-on-surface-variant/10 rounded-full overflow-hidden">
                    <View
                      className="h-full bg-error"
                      style={{ width: `${monthly.expiredPct}%` }}
                    />
                  </View>
                </View>
              </View>
            </View>

            {/* Trend Chart */}
            <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6">
              <View className="flex-row items-center justify-between mb-3">
                <View>
                  <Text
                    className={`font-bold text-on-surface ${isTablet ? "text-lg" : "text-base"}`}
                  >
                    Tren 6 Bulan
                  </Text>
                  <Text className="text-on-surface-variant text-xs">
                    Jumlah laporan {scope === "all" ? "semua tim" : currentScopeConfig.shortLabel}
                  </Text>
                </View>
                <View className="px-3 py-1 rounded-full bg-primary/10 flex-row items-center gap-1">
                  <Ionicons name="trending-up" size={14} color="#005bbf" />
                  <Text className="text-primary text-xs font-bold">+3.3%</Text>
                </View>
              </View>
              <BarChart
                data={barData}
                height={140}
                color={currentScopeConfig.color}
              />
            </View>

            {/* Menu shortcut */}
            <Pressable
              onPress={() => router.push("/menu")}
              className="flex-row items-center gap-3 bg-tertiary/90 rounded-2xl p-4 mb-2 active:opacity-90"
            >
              <View className="w-11 h-11 rounded-xl bg-white/20 items-center justify-center">
                <Ionicons name="grid" size={22} color="#ffffff" />
              </View>
              <View className="flex-1">
                <Text className="text-white font-bold">Buka Menu Supervisor</Text>
                <Text className="text-white/80 text-xs">
                  Akses semua fitur monitoring
                </Text>
              </View>
              <Ionicons name="chevron-forward" size={20} color="#ffffff" />
            </Pressable>
          </View>
        </View>
      </ScrollView>
    </View>
  );
}
