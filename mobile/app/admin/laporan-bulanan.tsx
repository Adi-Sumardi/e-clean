import { useMemo, useState } from "react";
import {
  ActivityIndicator,
  Alert,
  Pressable,
  ScrollView,
  Text,
  View,
} from "react-native";
import { Stack } from "expo-router";
import { Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";
import { AdminScreen } from "@/components/admin/AdminScreen";
import { FormSelect, type SelectOption } from "@/components/FormSelect";
import { BarChart, type BarItem } from "@/components/charts/BarChart";
import { useIsTablet } from "@/lib/useIsTablet";
import { useMonthlyReports, useUnits, useUsers } from "@/lib/hooks";

const MONTH_NAMES = [
  "Januari", "Februari", "Maret", "April", "Mei", "Juni",
  "Juli", "Agustus", "September", "Oktober", "November", "Desember",
];

/** Last 6 months as { value: "YYYY-MM", label }. */
const BULAN_OPTIONS: SelectOption[] = Array.from({ length: 6 }, (_, i) => {
  const d = new Date();
  d.setDate(1);
  d.setMonth(d.getMonth() - i);
  const ym = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`;
  return { value: ym, label: `${MONTH_NAMES[d.getMonth()]} ${d.getFullYear()}` };
});

export default function LaporanBulananScreen() {
  const isTablet = useIsTablet();
  const [bulan, setBulan] = useState<string | number | null>(
    BULAN_OPTIONS[0].value
  );
  const [unit, setUnit] = useState<string | number | null>("all");
  const [petugas, setPetugas] = useState<string | number | null>("all");

  const [yearStr, monthStr] = String(bulan).split("-");
  const year = Number(yearStr);
  const month = Number(monthStr);

  const { data: units } = useUnits();
  const { data: petugasList } = useUsers({ role: "petugas", active_only: true });
  const { data: reports, isLoading } = useMonthlyReports({
    month,
    year,
    unit_id: unit !== "all" ? Number(unit) : undefined,
    petugas_id: petugas !== "all" ? Number(petugas) : undefined,
  });

  const unitOptions = useMemo<SelectOption[]>(
    () => [
      { value: "all", label: "Semua Unit" },
      ...(units ?? []).map((u) => ({ value: u.id, label: u.nama_unit })),
    ],
    [units]
  );
  const petugasOptions = useMemo<SelectOption[]>(
    () => [
      { value: "all", label: "Semua Petugas" },
      ...(petugasList ?? []).map((u) => ({ value: u.id, label: u.name })),
    ],
    [petugasList]
  );

  const summary = useMemo(() => {
    const rows = reports ?? [];
    const total = rows.length;
    const approved = rows.filter((r) => r.status === "approved").length;
    const pending = rows.filter((r) => r.status === "submitted").length;
    const rejected = rows.filter((r) => r.status === "rejected").length;

    const daysInMonth = new Date(year, month, 0).getDate();
    const daily: BarItem[] = Array.from({ length: daysInMonth }, (_, i) => ({
      label: String(i + 1),
      value: 0,
    }));
    rows.forEach((r) => {
      const day = Number(r.tanggal?.split("-")[2]);
      if (day >= 1 && day <= daysInMonth) daily[day - 1].value += 1;
    });

    const ratingDist = [5, 4, 3, 2, 1].map((star) => {
      const count = rows.filter((r) => Math.round(r.rating ?? 0) === star).length;
      return { star, count, pct: total ? (count / total) * 100 : 0 };
    });

    return { total, approved, pending, rejected, daily, ratingDist };
  }, [reports, month, year]);

  const onExport = () =>
    Alert.alert(
      "Export PDF",
      "Export PDF tersedia di panel web admin (Laporan Bulanan).",
      [{ text: "OK" }]
    );

  const onPrint = () =>
    Alert.alert("Print", "Halaman cetak tersedia di panel web admin.", [
      { text: "OK" },
    ]);

  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <AdminScreen
        title="Laporan Bulanan"
        subtitle="Rangkuman performa per bulan"
        icon="document-text-outline"
        color="#0891b2"
      >
        <ScrollView
          contentContainerStyle={{ padding: isTablet ? 32 : 20, paddingBottom: 40 }}
        >
          {/* Filter */}
          <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-5">
            <View className="flex-row items-center gap-2 mb-3">
              <Ionicons name="filter" size={18} color="#005bbf" />
              <Text className="font-bold text-on-surface">Filter Laporan</Text>
            </View>
            <View className={isTablet ? "flex-row gap-3" : ""}>
              <View className={isTablet ? "flex-1" : ""}>
                <FormSelect
                  label="Bulan"
                  icon="calendar-outline"
                  value={bulan}
                  options={BULAN_OPTIONS}
                  onChange={setBulan}
                />
              </View>
              <View className={isTablet ? "flex-1" : ""}>
                <FormSelect
                  label="Unit"
                  icon="business-outline"
                  value={unit}
                  options={unitOptions}
                  onChange={setUnit}
                />
              </View>
              <View className={isTablet ? "flex-1" : ""}>
                <FormSelect
                  label="Petugas"
                  icon="person-outline"
                  value={petugas}
                  options={petugasOptions}
                  onChange={setPetugas}
                />
              </View>
            </View>

            <View className="flex-row gap-2 mt-2">
              <Pressable
                onPress={onExport}
                className="flex-1 h-12 rounded-xl bg-primary items-center justify-center flex-row gap-2 active:opacity-90"
              >
                <Ionicons name="download" size={18} color="#ffffff" />
                <Text className="text-white font-bold">Download PDF</Text>
              </Pressable>
              <Pressable
                onPress={onPrint}
                className="px-4 h-12 rounded-xl border-2 border-primary items-center justify-center flex-row gap-2 active:opacity-80"
              >
                <Ionicons name="print" size={18} color="#005bbf" />
                <Text className="text-primary font-bold">Print</Text>
              </Pressable>
            </View>
          </View>

          {/* Summary */}
          <View className="flex-row gap-3 mb-5">
            <SummaryCard
              icon="documents"
              label="Total"
              value={String(summary.total)}
              color="#005bbf"
            />
            <SummaryCard
              icon="checkmark-circle"
              label="Disetujui"
              value={String(summary.approved)}
              hint={summary.total ? `${Math.round((summary.approved / summary.total) * 100)}%` : undefined}
              color="#0a7e3e"
            />
            <SummaryCard
              icon="hourglass"
              label="Pending"
              value={String(summary.pending)}
              color="#e08a14"
            />
            {isTablet && (
              <SummaryCard
                icon="close-circle"
                label="Ditolak"
                value={String(summary.rejected)}
                color="#d62828"
              />
            )}
          </View>

          {/* Chart */}
          <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-5">
            <View className="flex-row items-center justify-between mb-3">
              <View>
                <Text className="font-bold text-on-surface text-base">
                  Sebaran Harian
                </Text>
                <Text className="text-on-surface-variant text-xs">
                  Jumlah laporan per tanggal — {MONTH_NAMES[month - 1]} {year}
                </Text>
              </View>
            </View>
            {isLoading ? (
              <View className="items-center py-10">
                <ActivityIndicator color="#0891b2" />
              </View>
            ) : (
              <BarChart
                data={summary.daily}
                height={160}
                color="#0891b2"
                barWidth={18}
              />
            )}
          </View>

          {/* Rating distribution */}
          <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-5">
            <Text className="font-bold text-on-surface text-base mb-3">
              Distribusi Rating
            </Text>
            <View className="gap-3">
              {summary.ratingDist
                .map((r) => ({
                  ...r,
                  color:
                    r.star >= 4 ? "#0a7e3e" : r.star >= 3 ? "#e08a14" : "#d62828",
                }))
                .map((r) => (
                <View key={r.star}>
                  <View className="flex-row items-center justify-between mb-1">
                    <View className="flex-row items-center gap-2">
                      <View className="flex-row items-center gap-0.5">
                        {Array.from({ length: r.star }).map((_, i) => (
                          <Ionicons
                            key={i}
                            name="star"
                            size={12}
                            color="#e08a14"
                          />
                        ))}
                        {Array.from({ length: 5 - r.star }).map((_, i) => (
                          <Ionicons
                            key={i + r.star}
                            name="star-outline"
                            size={12}
                            color="#c1c6d6"
                          />
                        ))}
                      </View>
                      <Text className="text-on-surface text-sm font-semibold">
                        {r.star} Bintang
                      </Text>
                    </View>
                    <Text className="text-on-surface text-sm font-bold">
                      {r.count}{" "}
                      <Text className="text-on-surface-variant text-xs">
                        ({r.pct.toFixed(1)}%)
                      </Text>
                    </Text>
                  </View>
                  <View className="h-2 bg-on-surface-variant/10 rounded-full overflow-hidden">
                    <View
                      className="h-full"
                      style={{ width: `${r.pct}%`, backgroundColor: r.color }}
                    />
                  </View>
                </View>
              ))}
            </View>
          </View>

          {/* Info */}
          <View className="bg-primary/5 rounded-2xl p-4 flex-row items-center gap-3">
            <MaterialCommunityIcons
              name="information-outline"
              size={20}
              color="#005bbf"
            />
            <Text className="flex-1 text-primary text-xs">
              Untuk laporan lebih detail dengan grafik & tabel petugas, download
              file PDF di atas.
            </Text>
          </View>
        </ScrollView>
      </AdminScreen>
    </>
  );
}

function SummaryCard({
  icon,
  label,
  value,
  hint,
  color,
}: {
  icon: React.ComponentProps<typeof Ionicons>["name"];
  label: string;
  value: string;
  hint?: string;
  color: string;
}) {
  return (
    <View
      className="flex-1 p-4 rounded-2xl border"
      style={{ backgroundColor: `${color}10`, borderColor: `${color}40` }}
    >
      <View className="flex-row items-center gap-2 mb-2">
        <Ionicons name={icon} size={14} color={color} />
        <Text className="text-xs font-bold" style={{ color }}>
          {label}
        </Text>
      </View>
      <Text className="text-2xl font-bold text-on-surface">{value}</Text>
      {hint ? (
        <Text className="text-xs mt-1" style={{ color }}>
          {hint}
        </Text>
      ) : null}
    </View>
  );
}
