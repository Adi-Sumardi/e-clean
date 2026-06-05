import { useMemo, useState } from "react";
import { Alert, Pressable, ScrollView, Text, View, ActivityIndicator } from "react-native";
import { Stack } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { AdminScreen, EmptyState } from "@/components/admin/AdminScreen";
import { useIsTablet } from "@/lib/useIsTablet";
import { useFieldJadwalList } from "@/lib/hooks";
import type { FieldScope } from "@/lib/services";

type IoniconName = React.ComponentProps<typeof Ionicons>["name"];

export interface JadwalItem {
  id: number;
  tanggal: string;
  shift: string;
  petugas: string;
  area: string;
  unit: string;
  status: "scheduled" | "completed" | "missed";
}

export interface TimConfig {
  title: string;
  subtitle?: string;
  icon: IoniconName;
  color: string;
  noun: string;
  shifts: string[];
  scope: FieldScope;
}

const STATUS_TONE: Record<
  JadwalItem["status"],
  { bg: string; text: string; label: string; icon: IoniconName }
> = {
  scheduled: {
    bg: "bg-primary/15",
    text: "text-primary",
    label: "Terjadwal",
    icon: "calendar",
  },
  completed: {
    bg: "bg-secondary/15",
    text: "text-secondary",
    label: "Selesai",
    icon: "checkmark-circle",
  },
  missed: {
    bg: "bg-error/15",
    text: "text-error",
    label: "Terlewat",
    icon: "close-circle",
  },
};

type Filter = "all" | JadwalItem["status"];

export function JadwalTimScreen({ config }: { config: TimConfig }) {
  const isTablet = useIsTablet();
  const [q, setQ] = useState("");
  const [filter, setFilter] = useState<Filter>("all");

  const { data: rawJadwal, isLoading } = useFieldJadwalList(config.scope);

  const data = useMemo<JadwalItem[]>(() => {
    if (!rawJadwal) return [];
    return rawJadwal.map((j) => {
      let status: JadwalItem["status"] = "scheduled";
      if (j.status === "completed") status = "completed";
      if (j.status === "missed") status = "missed";

      return {
        id: j.id,
        tanggal: j.tanggal,
        shift: j.shift,
        petugas: j.petugas?.name ?? "-",
        area: j.lokasi?.nama_lokasi ?? "-",
        unit: j.lokasi?.unit?.nama_unit ?? "-",
        status,
      };
    });
  }, [rawJadwal]);

  const counts = useMemo(
    () => ({
      all: data.length,
      scheduled: data.filter((j) => j.status === "scheduled").length,
      completed: data.filter((j) => j.status === "completed").length,
      missed: data.filter((j) => j.status === "missed").length,
    }),
    [data]
  );

  const filtered = useMemo(() => {
    let list = data;
    if (filter !== "all") list = list.filter((j) => j.status === filter);
    const s = q.toLowerCase().trim();
    if (s) {
      list = list.filter(
        (j) =>
          j.petugas.toLowerCase().includes(s) ||
          j.area.toLowerCase().includes(s)
      );
    }
    return list;
  }, [data, filter, q]);

  const FILTERS: { key: Filter; label: string; count: number }[] = [
    { key: "all", label: "Semua", count: counts.all },
    { key: "scheduled", label: "Terjadwal", count: counts.scheduled },
    { key: "completed", label: "Selesai", count: counts.completed },
    { key: "missed", label: "Terlewat", count: counts.missed },
  ];

  const handleKelola = () => {
    Alert.alert(
      "Kelola Jadwal",
      "Penjadwalan & pengelolaan petugas selengkapnya dapat diatur melalui dashboard web Filament admin."
    );
  };

  const formatDate = (dateStr: string) => {
    try {
      const parts = dateStr.split("-");
      if (parts.length === 3) {
        return {
          day: parts[2],
          month: parts[1] === "01" ? "Jan" :
                 parts[1] === "02" ? "Feb" :
                 parts[1] === "03" ? "Mar" :
                 parts[1] === "04" ? "Apr" :
                 parts[1] === "05" ? "Mei" :
                 parts[1] === "06" ? "Jun" :
                 parts[1] === "07" ? "Jul" :
                 parts[1] === "08" ? "Agu" :
                 parts[1] === "09" ? "Sep" :
                 parts[1] === "10" ? "Okt" :
                 parts[1] === "11" ? "Nov" : "Des"
        };
      }
    } catch {}
    return { day: dateStr, month: "" };
  };

  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <AdminScreen
        title={config.title}
        subtitle={config.subtitle ?? `${data.length} ${config.noun}`}
        icon={config.icon}
        color={config.color}
        searchValue={q}
        onSearchChange={setQ}
        searchPlaceholder={`Cari ${config.noun} / petugas...`}
        onAdd={handleKelola}
        addLabel="Kelola"
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
                    flexDirection: "row",
                    alignItems: "center",
                    borderColor: active ? config.color : "#e1e3e4",
                    backgroundColor: active ? config.color : "#ffffff",
                  }}
                >
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
                        : `${config.color}1a`,
                    }}
                  >
                    <Text
                      style={{
                        fontSize: 11,
                        fontWeight: "700",
                        lineHeight: 14,
                        color: active ? "#ffffff" : config.color,
                      }}
                    >
                      {f.count}
                    </Text>
                  </View>
                </Pressable>
              );
            })}
          </ScrollView>
        </View>

        <ScrollView
          contentContainerStyle={{ padding: isTablet ? 32 : 20, paddingBottom: 40 }}
        >
          {isLoading ? (
            <View className="items-center py-20">
              <ActivityIndicator size="large" color={config.color} />
            </View>
          ) : filtered.length === 0 ? (
            <EmptyState
              icon="calendar-outline"
              title={`Tidak ada ${config.noun}`}
            />
          ) : (
            <View className={isTablet ? "flex-row flex-wrap -m-2" : "gap-3"}>
              {filtered.map((j) => {
                const tone = STATUS_TONE[j.status] ?? STATUS_TONE.scheduled;
                const formattedDate = formatDate(j.tanggal);
                return (
                  <View key={j.id} className={isTablet ? "w-1/2 p-2" : ""}>
                    <Pressable
                      onPress={handleKelola}
                      className="p-4 rounded-2xl bg-surface-container-lowest border border-outline-variant active:opacity-80"
                    >
                      <View className="flex-row items-center gap-3">
                        <View
                          className="w-14 h-14 rounded-xl items-center justify-center"
                          style={{ backgroundColor: `${config.color}1a` }}
                        >
                          <Text
                            className="text-[10px] font-semibold"
                            style={{ color: config.color }}
                          >
                            {formattedDate.month}
                          </Text>
                          <Text
                            className="text-lg font-bold"
                            style={{ color: config.color }}
                          >
                            {formattedDate.day}
                          </Text>
                        </View>
                        <View className="flex-1">
                          <View className="flex-row items-center gap-2">
                            <Text
                              className="font-bold text-on-surface flex-1"
                              numberOfLines={1}
                            >
                              {j.petugas}
                            </Text>
                            <View className="px-2 py-0.5 rounded-full bg-tertiary/10">
                              <Text className="text-tertiary text-[10px] font-bold capitalize">
                                {j.shift}
                              </Text>
                            </View>
                          </View>
                          <View className="flex-row items-center gap-1 mt-1">
                            <Ionicons
                              name="location-outline"
                              size={11}
                              color="#5a6072"
                            />
                            <Text
                              className="text-on-surface-variant text-xs flex-1"
                              numberOfLines={1}
                            >
                              {j.area}
                            </Text>
                          </View>
                          <View className="flex-row items-center gap-1 mt-0.5">
                            <Ionicons
                              name="business-outline"
                              size={11}
                              color="#5a6072"
                            />
                            <Text className="text-on-surface-variant text-xs">
                              {j.unit}
                            </Text>
                          </View>
                        </View>
                      </View>
                      <View className="flex-row items-center justify-between mt-3 pt-3 border-t border-outline-variant/50">
                        <View
                          className={`px-3 py-1 rounded-full flex-row items-center gap-1 ${tone.bg}`}
                        >
                          <Ionicons
                            name={tone.icon}
                            size={12}
                            color={
                              j.status === "completed"
                                ? "#0a7e3e"
                                : j.status === "missed"
                                  ? "#d62828"
                                  : "#005bbf"
                            }
                          />
                          <Text className={`text-[10px] font-bold ${tone.text}`}>
                            {tone.label}
                          </Text>
                        </View>
                        <Pressable onPress={handleKelola} className="flex-row items-center gap-1 active:opacity-70">
                          <Text
                            className="text-xs font-bold"
                            style={{ color: config.color }}
                          >
                            Kelola
                          </Text>
                          <Ionicons
                            name="chevron-forward"
                            size={12}
                            color={config.color}
                          />
                        </Pressable>
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
