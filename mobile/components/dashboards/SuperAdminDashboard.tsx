import { Pressable, ScrollView, Text, View } from "react-native";
import { useRouter } from "expo-router";
import { Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";
import { useAuthStore } from "@/stores/auth-store";
import { ROLE_LABEL } from "@/constants/role";
import { StatCard } from "@/components/StatCard";
import { BarChart, type BarItem } from "@/components/charts/BarChart";
import { useIsTablet } from "@/lib/useIsTablet";
import { NotificationBell } from "@/components/NotificationBell";
import { DashboardHeader } from "@/components/DashboardHeader";

// Dummy data — gantikan dengan API call ke endpoint admin/stats
const DUMMY_STATS = {
  totalLokasi: 24,
  lokasiTrend: [18, 19, 20, 21, 22, 23, 24],
  totalPetugas: 38,
  petugasTrend: [30, 32, 33, 34, 35, 37, 38],
  jadwalAktif: 142,
  jadwalTrend: [100, 110, 120, 130, 135, 138, 142],
  laporanBulanIni: 286,
  laporanTrend: [10, 18, 22, 26, 30, 35, 42],
  laporanDiapprove: 248,
  approvalRate: 86.7,
};

const MONTHLY_BAR_DATA: BarItem[] = [
  { label: "Jun", value: 180 },
  { label: "Jul", value: 195 },
  { label: "Agu", value: 210 },
  { label: "Sep", value: 225 },
  { label: "Okt", value: 240 },
  { label: "Nov", value: 268 },
  { label: "Des", value: 255 },
  { label: "Jan", value: 245 },
  { label: "Feb", value: 260 },
  { label: "Mar", value: 275 },
  { label: "Apr", value: 290 },
  { label: "Mei", value: 286 },
];

interface RecentReport {
  id: number;
  tanggal: string;
  petugas: string;
  lokasi: string;
  kegiatan: string;
  status: "draft" | "submitted" | "approved" | "rejected";
  rating: number | null;
}

const RECENT_REPORTS: RecentReport[] = [
  {
    id: 1,
    tanggal: "02 Jun 2026",
    petugas: "Rahmat Hidayat",
    lokasi: "Toilet Lt.1 - Gedung A",
    kegiatan: "Pembersihan rutin pagi",
    status: "approved",
    rating: 5,
  },
  {
    id: 2,
    tanggal: "02 Jun 2026",
    petugas: "Siti Nurhaliza",
    lokasi: "Lobi Utama",
    kegiatan: "Mopping & dusting",
    status: "submitted",
    rating: null,
  },
  {
    id: 3,
    tanggal: "01 Jun 2026",
    petugas: "Andi Setiawan",
    lokasi: "Pantry Lt.2",
    kegiatan: "Pembersihan setelah makan siang",
    status: "approved",
    rating: 4,
  },
  {
    id: 4,
    tanggal: "01 Jun 2026",
    petugas: "Budi Hartono",
    lokasi: "Ruang Rapat Besar",
    kegiatan: "Setup & pembersihan ruangan",
    status: "approved",
    rating: 5,
  },
  {
    id: 5,
    tanggal: "01 Jun 2026",
    petugas: "Citra Wijaya",
    lokasi: "Toilet Lt.3 - Gedung B",
    kegiatan: "Penanganan tumpahan",
    status: "rejected",
    rating: 2,
  },
];

const STATUS_TONE: Record<RecentReport["status"], { bg: string; text: string; label: string }> = {
  draft: { bg: "bg-on-surface-variant/15", text: "text-on-surface-variant", label: "Draft" },
  submitted: { bg: "bg-tertiary/15", text: "text-tertiary", label: "Submitted" },
  approved: { bg: "bg-secondary/15", text: "text-secondary", label: "Approved" },
  rejected: { bg: "bg-error/15", text: "text-error", label: "Rejected" },
};

function ratingColor(rating: number | null) {
  if (rating === null) return "#5a6072";
  if (rating >= 4) return "#0a7e3e";
  if (rating >= 3) return "#e08a14";
  return "#d62828";
}

export function SuperAdminDashboard() {
  const router = useRouter();
  const isTablet = useIsTablet();
  const user = useAuthStore((s) => s.user);

  const headerPad = isTablet ? "px-8" : "px-5";
  const contentPad = isTablet ? 32 : 20;

  return (
    <View className="flex-1 bg-background">
      <DashboardHeader
        colors={["#005bbf", "#003a80"]}
        title="e-Office Kopkaryapi"
        subtitle="Admin Panel"
        icon={
          <MaterialCommunityIcons name="shield-crown" size={22} color="#fff" />
        }
        right={<NotificationBell size={22} color="#fff" />}
      />

      <ScrollView
        contentContainerStyle={{ padding: contentPad, paddingBottom: 120 }}
        showsVerticalScrollIndicator={false}
      >
        {/* Greeting */}
        <View className="mb-6">
          <Text className="text-on-surface-variant">Selamat datang kembali,</Text>
          <Text
            className={`font-bold text-on-surface ${isTablet ? "text-4xl" : "text-2xl"}`}
          >
            {user?.name ?? "Admin"}
          </Text>
          <View className="flex-row items-center gap-2 mt-2">
            <View className="px-3 py-1 rounded-full bg-primary/10 flex-row items-center gap-1">
              <Ionicons name="shield-checkmark" size={12} color="#005bbf" />
              <Text className="text-primary text-xs font-bold">
                {user ? ROLE_LABEL[user.role] : "Super Admin"}
              </Text>
            </View>
            {user?.unit && (
              <View className="px-3 py-1 rounded-full bg-secondary/10 flex-row items-center gap-1">
                <Ionicons name="business-outline" size={12} color="#0a7e3e" />
                <Text className="text-secondary text-xs font-bold">
                  {user.unit.name}
                </Text>
              </View>
            )}
          </View>
        </View>

        {/* Stats Overview */}
        <Text
          className={`font-bold text-on-surface mb-3 ${isTablet ? "text-xl" : "text-lg"}`}
        >
          Ringkasan Sistem
        </Text>
        {isTablet ? (
          <View className="flex-row gap-4 mb-6">
            <StatCard
              icon="business-outline"
              label="Lokasi Aktif"
              value={DUMMY_STATS.totalLokasi}
              hint="Lokasi sedang aktif"
              tone="secondary"
              trend={DUMMY_STATS.lokasiTrend}
            />
            <StatCard
              icon="people-outline"
              label="Total Petugas"
              value={DUMMY_STATS.totalPetugas}
              hint="Terdaftar di sistem"
              tone="primary"
              trend={DUMMY_STATS.petugasTrend}
            />
            <StatCard
              icon="calendar-outline"
              label="Jadwal Aktif"
              value={DUMMY_STATS.jadwalAktif}
              hint="Jadwal mendatang"
              tone="warning"
              trend={DUMMY_STATS.jadwalTrend}
            />
            <StatCard
              icon="clipboard-outline"
              label="Laporan Bulan Ini"
              value={DUMMY_STATS.laporanBulanIni}
              hint={`${DUMMY_STATS.laporanDiapprove} disetujui (${DUMMY_STATS.approvalRate}%)`}
              tone="info"
              trend={DUMMY_STATS.laporanTrend}
            />
          </View>
        ) : (
          <View className="gap-3 mb-6">
            <View className="flex-row gap-3">
              <StatCard
                icon="business-outline"
                label="Lokasi Aktif"
                value={DUMMY_STATS.totalLokasi}
                hint="Lokasi sedang aktif"
                tone="secondary"
                trend={DUMMY_STATS.lokasiTrend}
              />
              <StatCard
                icon="people-outline"
                label="Petugas"
                value={DUMMY_STATS.totalPetugas}
                hint="Terdaftar"
                tone="primary"
                trend={DUMMY_STATS.petugasTrend}
              />
            </View>
            <View className="flex-row gap-3">
              <StatCard
                icon="calendar-outline"
                label="Jadwal Aktif"
                value={DUMMY_STATS.jadwalAktif}
                hint="Mendatang"
                tone="warning"
                trend={DUMMY_STATS.jadwalTrend}
              />
              <StatCard
                icon="clipboard-outline"
                label="Laporan"
                value={DUMMY_STATS.laporanBulanIni}
                hint={`${DUMMY_STATS.approvalRate}% disetujui`}
                tone="info"
                trend={DUMMY_STATS.laporanTrend}
              />
            </View>
          </View>
        )}

        {/* Tablet: 2-column for chart + quick actions */}
        <View className={isTablet ? "flex-row gap-6" : ""}>
          {/* LEFT column */}
          <View className={isTablet ? "flex-[2]" : ""}>
            {/* Bar Chart */}
            <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6">
              <View className="flex-row items-center justify-between mb-3">
                <View>
                  <Text
                    className={`font-bold text-on-surface ${isTablet ? "text-lg" : "text-base"}`}
                  >
                    Laporan Bulanan
                  </Text>
                  <Text className="text-on-surface-variant text-xs">
                    12 bulan terakhir
                  </Text>
                </View>
                <View className="px-3 py-1 rounded-full bg-primary/10 flex-row items-center gap-1">
                  <Ionicons name="trending-up" size={14} color="#005bbf" />
                  <Text className="text-primary text-xs font-bold">
                    +12.3%
                  </Text>
                </View>
              </View>
              <BarChart data={MONTHLY_BAR_DATA} height={180} color="#005bbf" />
            </View>

            {/* Recent Activity Table */}
            <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6">
              <View className="flex-row items-center justify-between mb-3">
                <View>
                  <Text
                    className={`font-bold text-on-surface ${isTablet ? "text-lg" : "text-base"}`}
                  >
                    Laporan Terbaru
                  </Text>
                  <Text className="text-on-surface-variant text-xs">
                    30 hari terakhir
                  </Text>
                </View>
                <Pressable className="flex-row items-center gap-1">
                  <Text className="text-primary text-xs font-bold">
                    Lihat Semua
                  </Text>
                  <Ionicons name="chevron-forward" size={14} color="#005bbf" />
                </Pressable>
              </View>

              <View className="gap-3">
                {RECENT_REPORTS.map((r) => {
                  const tone = STATUS_TONE[r.status];
                  return (
                    <View
                      key={r.id}
                      className="flex-row items-center gap-3 p-3 rounded-xl border border-outline-variant bg-surface"
                    >
                      <View className="w-10 h-10 rounded-full bg-primary/10 items-center justify-center">
                        <Ionicons
                          name="person"
                          size={18}
                          color="#005bbf"
                        />
                      </View>
                      <View className="flex-1">
                        <Text className="font-bold text-on-surface" numberOfLines={1}>
                          {r.petugas}
                        </Text>
                        <Text
                          className="text-on-surface-variant text-xs"
                          numberOfLines={1}
                        >
                          {r.lokasi}
                        </Text>
                        <Text
                          className="text-on-surface-variant text-xs mt-0.5"
                          numberOfLines={1}
                        >
                          {r.kegiatan}
                        </Text>
                      </View>
                      <View className="items-end gap-1">
                        <View
                          className={`px-2 py-0.5 rounded-full ${tone.bg}`}
                        >
                          <Text className={`text-[10px] font-bold ${tone.text}`}>
                            {tone.label}
                          </Text>
                        </View>
                        <Text className="text-[10px] text-on-surface-variant">
                          {r.tanggal}
                        </Text>
                        <View className="flex-row items-center gap-0.5">
                          <Ionicons
                            name="star"
                            size={10}
                            color={ratingColor(r.rating)}
                          />
                          <Text
                            className="text-[10px] font-bold"
                            style={{ color: ratingColor(r.rating) }}
                          >
                            {r.rating !== null ? `${r.rating}/5` : "N/A"}
                          </Text>
                        </View>
                      </View>
                    </View>
                  );
                })}
              </View>
            </View>
          </View>

          {/* RIGHT column */}
          <View className={isTablet ? "flex-1" : ""}>
            {/* Open Menu shortcut */}
            <Pressable
              onPress={() => router.push("/menu")}
              className="flex-row items-center gap-3 bg-primary rounded-2xl p-4 mb-6 active:opacity-90"
            >
              <View className="w-11 h-11 rounded-xl bg-white/20 items-center justify-center">
                <Ionicons name="grid" size={22} color="#ffffff" />
              </View>
              <View className="flex-1">
                <Text className="text-white font-bold">Buka Menu Admin</Text>
                <Text className="text-white/80 text-xs">
                  13 menu manajemen sistem
                </Text>
              </View>
              <Ionicons name="chevron-forward" size={20} color="#ffffff" />
            </Pressable>

            {/* System Status */}
            <Text
              className={`font-bold text-on-surface mb-3 ${isTablet ? "text-lg" : "text-base"}`}
            >
              Status Sistem
            </Text>
            <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6 gap-3">
              <View className="flex-row items-center gap-3">
                <View className="w-2.5 h-2.5 rounded-full bg-secondary" />
                <Text className="flex-1 text-on-surface text-sm font-semibold">
                  Server
                </Text>
                <Text className="text-secondary text-xs font-bold">Online</Text>
              </View>
              <View className="flex-row items-center gap-3">
                <View className="w-2.5 h-2.5 rounded-full bg-secondary" />
                <Text className="flex-1 text-on-surface text-sm font-semibold">
                  Database
                </Text>
                <Text className="text-secondary text-xs font-bold">
                  Healthy
                </Text>
              </View>
              <View className="flex-row items-center gap-3">
                <View className="w-2.5 h-2.5 rounded-full bg-tertiary" />
                <Text className="flex-1 text-on-surface text-sm font-semibold">
                  WhatsApp Gateway
                </Text>
                <Text className="text-tertiary text-xs font-bold">Slow</Text>
              </View>
              <View className="flex-row items-center gap-3">
                <View className="w-2.5 h-2.5 rounded-full bg-secondary" />
                <Text className="flex-1 text-on-surface text-sm font-semibold">
                  Storage
                </Text>
                <Text className="text-on-surface-variant text-xs font-bold">
                  62% used
                </Text>
              </View>
              <View className="flex-row items-center gap-3">
                <Ionicons name="cloud-upload-outline" size={14} color="#5a6072" />
                <Text className="flex-1 text-on-surface-variant text-xs">
                  Backup terakhir: Hari ini 03:00
                </Text>
              </View>
            </View>

            {/* Top Performers */}
            <Text
              className={`font-bold text-on-surface mb-3 ${isTablet ? "text-lg" : "text-base"}`}
            >
              Top Petugas Bulan Ini
            </Text>
            <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6 gap-3">
              {[
                { name: "Rahmat Hidayat", reports: 42, avg: 4.8 },
                { name: "Siti Nurhaliza", reports: 39, avg: 4.7 },
                { name: "Andi Setiawan", reports: 36, avg: 4.6 },
              ].map((p, i) => (
                <View key={p.name} className="flex-row items-center gap-3">
                  <View
                    className="w-8 h-8 rounded-full items-center justify-center"
                    style={{
                      backgroundColor:
                        i === 0 ? "#ffd700" : i === 1 ? "#c0c0c0" : "#cd7f32",
                    }}
                  >
                    <Text className="text-xs font-bold text-white">
                      #{i + 1}
                    </Text>
                  </View>
                  <View className="flex-1">
                    <Text className="font-bold text-on-surface text-sm">
                      {p.name}
                    </Text>
                    <Text className="text-on-surface-variant text-xs">
                      {p.reports} laporan
                    </Text>
                  </View>
                  <View className="flex-row items-center gap-1">
                    <Ionicons name="star" size={12} color="#e08a14" />
                    <Text className="text-tertiary text-xs font-bold">
                      {p.avg}
                    </Text>
                  </View>
                </View>
              ))}
            </View>
          </View>
        </View>

        {/* Footer info */}
        <View className="bg-primary/5 rounded-2xl p-4 flex-row items-center gap-3">
          <Ionicons name="information-circle" size={20} color="#005bbf" />
          <Text className="flex-1 text-primary text-xs">
            Data ditampilkan berdasarkan 30 hari terakhir. Untuk laporan lengkap,
            buka menu Laporan Bulanan.
          </Text>
        </View>
      </ScrollView>
    </View>
  );
}
