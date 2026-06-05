import { useMemo, useState } from "react";
import { Alert, Pressable, ScrollView, Text, View, ActivityIndicator } from "react-native";
import { Stack } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { AdminScreen, EmptyState } from "@/components/admin/AdminScreen";
import { EntityFormModal, type FieldDef, type FormValues } from "@/components/admin/EntityFormModal";
import { useIsTablet } from "@/lib/useIsTablet";
import {
  useFieldJadwalList,
  useUsers,
  useLokasi,
  useCreateFieldJadwal,
  useDeleteFieldJadwal,
} from "@/lib/hooks";
import type { FieldScope } from "@/lib/services";
import { ApiError } from "@/lib/api";

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

function todayStr() {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
}

function showApiError(err: unknown) {
  const msg =
    err instanceof ApiError && err.errors
      ? Object.values(err.errors).flat().join("\n")
      : err instanceof Error
        ? err.message
        : "Terjadi kesalahan.";
  Alert.alert("Gagal", msg);
}

export function JadwalTimScreen({ config }: { config: TimConfig }) {
  const isTablet = useIsTablet();
  const [q, setQ] = useState("");
  const [filter, setFilter] = useState<Filter>("all");
  const [modalOpen, setModalOpen] = useState(false);

  const { data: rawJadwal, isLoading, refetch } = useFieldJadwalList(config.scope);

  const role =
    config.scope === "ob"
      ? "office_boy"
      : config.scope === "toko"
        ? "petugas_toko"
        : "satpam";

  const { data: petugasList } = useUsers({ role, active_only: true });
  const { data: lokasiList } = useLokasi();

  const createMutation = useCreateFieldJadwal(config.scope);
  const deleteMutation = useDeleteFieldJadwal(config.scope);

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

  const fields = useMemo<FieldDef[]>(
    () => [
      {
        key: "petugas_id",
        label: "Petugas",
        type: "select",
        required: true,
        options: (petugasList ?? []).map((u) => ({ value: u.id, label: u.name })),
      },
      {
        key: "lokasi_id",
        label: "Lokasi",
        type: "select",
        required: true,
        options: (lokasiList ?? []).map((l) => ({
          value: l.id,
          label: l.lantai ? `${l.nama_lokasi} - ${l.lantai}` : l.nama_lokasi,
        })),
      },
      { key: "tanggal", label: "Tanggal", type: "text", required: true, placeholder: "YYYY-MM-DD" },
      {
        key: "shift",
        label: "Shift",
        type: "select",
        required: true,
        options: config.shifts.map((s) => ({ value: s.toLowerCase(), label: s })),
      },
      { key: "jam_mulai", label: "Jam Mulai", type: "text", required: true, placeholder: "08:00" },
      { key: "jam_selesai", label: "Jam Selesai", type: "text", required: true, placeholder: "10:00" },
      { key: "catatan", label: "Catatan", type: "textarea" },
    ],
    [petugasList, lokasiList, config.shifts]
  );

  const onSubmit = (values: FormValues) => {
    createMutation.mutate(
      {
        petugas_id: Number(values.petugas_id),
        lokasi_id: Number(values.lokasi_id),
        tanggal: String(values.tanggal),
        shift: String(values.shift),
        jam_mulai: String(values.jam_mulai),
        jam_selesai: String(values.jam_selesai),
        catatan: values.catatan ? String(values.catatan) : undefined,
      },
      {
        onSuccess: () => {
          setModalOpen(false);
          Alert.alert("Berhasil", "Jadwal dibuat.");
          refetch();
        },
        onError: showApiError,
      }
    );
  };

  const confirmDelete = (id: number, name: string) =>
    Alert.alert("Hapus Jadwal", `Hapus jadwal untuk ${name}?`, [
      { text: "Batal", style: "cancel" },
      {
        text: "Hapus",
        style: "destructive",
        onPress: () =>
          deleteMutation.mutate(id, {
            onSuccess: () => {
              Alert.alert("Berhasil", "Jadwal dihapus.");
              refetch();
            },
            onError: showApiError,
          }),
      },
    ]);

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
        onAdd={() => setModalOpen(true)}
        addLabel="Buat"
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
          contentContainerStyle={{ padding: isTablet ? 32 : 20, paddingBottom: 130 }}
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
                    <View className="p-4 rounded-2xl bg-surface-container-lowest border border-outline-variant">
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
                        <Pressable
                          onPress={() => confirmDelete(j.id, j.petugas)}
                          className="flex-row items-center gap-1 active:opacity-70"
                        >
                          <Ionicons name="trash-outline" size={14} color="#d62828" />
                          <Text className="text-error text-xs font-bold">Hapus</Text>
                        </Pressable>
                      </View>
                    </View>
                  </View>
                );
              })}
            </View>
          )}
        </ScrollView>
      </AdminScreen>

      <EntityFormModal
        visible={modalOpen}
        title={`Buat Jadwal ${config.title}`}
        fields={fields}
        initialValues={{
          tanggal: todayStr(),
          jam_mulai: "08:00",
          jam_selesai: "10:00",
          shift: config.shifts[0]?.toLowerCase() ?? "pagi",
        }}
        submitting={createMutation.isPending}
        submitLabel="Buat Jadwal"
        onCancel={() => setModalOpen(false)}
        onSubmit={onSubmit}
      />
    </>
  );
}
