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
import { Ionicons } from "@expo/vector-icons";
import { AdminScreen, EmptyState } from "@/components/admin/AdminScreen";
import {
  EntityFormModal,
  type FieldDef,
  type FormValues,
} from "@/components/admin/EntityFormModal";
import { useIsTablet } from "@/lib/useIsTablet";
import {
  useJadwal,
  useUsers,
  useLokasi,
  useCreateJadwal,
  useDeleteJadwal,
} from "@/lib/hooks";
import { ApiError } from "@/lib/api";

const SHIFTS = [
  { value: "pagi", label: "Pagi" },
  { value: "siang", label: "Siang" },
  { value: "sore", label: "Sore" },
  { value: "standby", label: "Standby" },
  { value: "sweeping", label: "Sweeping" },
];

const STATUS_TONE: Record<string, { bg: string; text: string; label: string }> = {
  active: { bg: "bg-secondary/15", text: "text-secondary", label: "Aktif" },
  inactive: {
    bg: "bg-on-surface-variant/15",
    text: "text-on-surface-variant",
    label: "Nonaktif",
  },
};

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

export default function JadwalKebersihanScreen() {
  const isTablet = useIsTablet();
  const [q, setQ] = useState("");
  const [modalOpen, setModalOpen] = useState(false);

  const { data, isLoading } = useJadwal();
  const { data: petugasList } = useUsers({ role: "petugas", active_only: true });
  const { data: lokasiList } = useLokasi();
  const createJadwal = useCreateJadwal();
  const deleteJadwal = useDeleteJadwal();

  const jadwal = data ?? [];
  const filtered = useMemo(() => {
    const s = q.toLowerCase().trim();
    if (!s) return jadwal;
    return jadwal.filter(
      (j) =>
        (j.petugas?.name ?? "").toLowerCase().includes(s) ||
        (j.lokasi?.nama_lokasi ?? "").toLowerCase().includes(s)
    );
  }, [q, jadwal]);

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
      { key: "shift", label: "Shift", type: "select", required: true, options: SHIFTS },
      { key: "jam_mulai", label: "Jam Mulai", type: "text", required: true, placeholder: "08:00" },
      { key: "jam_selesai", label: "Jam Selesai", type: "text", required: true, placeholder: "10:00" },
      { key: "catatan", label: "Catatan", type: "textarea" },
    ],
    [petugasList, lokasiList]
  );

  const onSubmit = (values: FormValues) => {
    createJadwal.mutate(
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
          Alert.alert("Berhasil", "Jadwal kebersihan dibuat.");
        },
        onError: showApiError,
      }
    );
  };

  const confirmDelete = (id: number, name: string) =>
    Alert.alert("Hapus Jadwal", `Hapus jadwal ${name}?`, [
      { text: "Batal", style: "cancel" },
      {
        text: "Hapus",
        style: "destructive",
        onPress: () => deleteJadwal.mutate(id, { onError: showApiError }),
      },
    ]);

  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <AdminScreen
        title="Jadwal Kebersihan"
        subtitle={`${jadwal.length} jadwal`}
        icon="calendar-outline"
        color="#0a7e3e"
        searchValue={q}
        onSearchChange={setQ}
        searchPlaceholder="Cari petugas / lokasi..."
        onAdd={() => setModalOpen(true)}
        addLabel="Buat"
      >
        <ScrollView
          contentContainerStyle={{ padding: isTablet ? 32 : 20, paddingBottom: 130 }}
        >
          {isLoading ? (
            <View className="items-center py-16">
              <ActivityIndicator color="#0a7e3e" />
            </View>
          ) : filtered.length === 0 ? (
            <EmptyState icon="calendar-outline" title="Belum ada jadwal" />
          ) : (
            <View className="gap-3">
              {filtered.map((j) => {
                const tone = STATUS_TONE[j.status] ?? STATUS_TONE.active;
                return (
                  <View
                    key={j.id}
                    className="p-4 rounded-2xl bg-surface-container-lowest border border-outline-variant"
                  >
                    <View className="flex-row items-center gap-3">
                      <View className="w-11 h-11 rounded-xl bg-secondary/10 items-center justify-center">
                        <Ionicons name="calendar" size={20} color="#0a7e3e" />
                      </View>
                      <View className="flex-1">
                        <Text className="font-bold text-on-surface" numberOfLines={1}>
                          {j.petugas?.name ?? "-"}
                        </Text>
                        <Text className="text-on-surface-variant text-xs" numberOfLines={1}>
                          {j.lokasi?.nama_lokasi ?? "-"}
                        </Text>
                      </View>
                      <View className={`px-2 py-0.5 rounded-full ${tone.bg}`}>
                        <Text className={`text-[10px] font-bold ${tone.text}`}>
                          {tone.label}
                        </Text>
                      </View>
                    </View>
                    <View className="flex-row items-center justify-between mt-3 pt-3 border-t border-outline-variant/50">
                      <View className="flex-row items-center gap-3">
                        <View className="flex-row items-center gap-1">
                          <Ionicons name="today-outline" size={12} color="#5a6072" />
                          <Text className="text-on-surface-variant text-xs">
                            {j.tanggal}
                          </Text>
                        </View>
                        <View className="px-2 py-0.5 rounded-full bg-primary/10">
                          <Text className="text-primary text-[10px] font-bold capitalize">
                            {j.shift} · {j.jam_mulai}-{j.jam_selesai}
                          </Text>
                        </View>
                      </View>
                      <Pressable
                        onPress={() => confirmDelete(j.id, j.petugas?.name ?? "")}
                        hitSlop={8}
                        className="flex-row items-center gap-1"
                      >
                        <Ionicons name="trash-outline" size={14} color="#d62828" />
                        <Text className="text-error text-xs font-bold">Hapus</Text>
                      </Pressable>
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
        title="Buat Jadwal Kebersihan"
        fields={fields}
        initialValues={{
          tanggal: todayStr(),
          jam_mulai: "08:00",
          jam_selesai: "10:00",
          shift: "pagi",
        }}
        submitting={createJadwal.isPending}
        submitLabel="Buat Jadwal"
        onCancel={() => setModalOpen(false)}
        onSubmit={onSubmit}
      />
    </>
  );
}
