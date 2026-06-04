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
  useManagedLokasi,
  useUnits,
  useCreateLokasi,
  useUpdateLokasi,
  useDeleteLokasi,
} from "@/lib/hooks";
import { ApiError } from "@/lib/api";
import type { Lokasi } from "@/lib/types";

const KATEGORI: { value: string; label: string }[] = [
  { value: "toilet", label: "Toilet" },
  { value: "ruang_kelas", label: "Ruangan" },
  { value: "kantor", label: "Kantor" },
  { value: "aula", label: "Aula" },
  { value: "koridor", label: "Koridor" },
  { value: "taman", label: "Taman" },
  { value: "lainnya", label: "Lainnya" },
];

function showApiError(err: unknown) {
  const msg =
    err instanceof ApiError && err.errors
      ? Object.values(err.errors).flat().join("\n")
      : err instanceof Error
        ? err.message
        : "Terjadi kesalahan.";
  Alert.alert("Gagal", msg);
}

export default function LokasiAdminScreen() {
  const isTablet = useIsTablet();
  const [q, setQ] = useState("");
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<Lokasi | null>(null);

  const { data, isLoading } = useManagedLokasi();
  const { data: units } = useUnits();
  const createLokasi = useCreateLokasi();
  const updateLokasi = useUpdateLokasi();
  const deleteLokasi = useDeleteLokasi();

  const lokasi = data ?? [];
  const filtered = useMemo(() => {
    const s = q.toLowerCase().trim();
    if (!s) return lokasi;
    return lokasi.filter(
      (l) =>
        l.nama_lokasi.toLowerCase().includes(s) ||
        l.kode_lokasi.toLowerCase().includes(s) ||
        (l.unit?.nama_unit ?? "").toLowerCase().includes(s)
    );
  }, [q, lokasi]);

  const fields = useMemo<FieldDef[]>(
    () => [
      {
        key: "unit_id",
        label: "Unit",
        type: "select",
        required: true,
        options: (units ?? []).map((u) => ({ value: u.id, label: u.nama_unit })),
      },
      { key: "kode_lokasi", label: "Kode Lokasi", type: "text", required: true, placeholder: "LK-A01" },
      { key: "nama_lokasi", label: "Nama Lokasi", type: "text", required: true },
      { key: "kategori", label: "Kategori", type: "select", required: true, options: KATEGORI },
      { key: "lantai", label: "Lantai", type: "text", placeholder: "Lantai 1" },
      { key: "deskripsi", label: "Deskripsi", type: "textarea" },
      { key: "is_active", label: "Aktif", type: "switch" },
    ],
    [units]
  );

  const openCreate = () => {
    setEditing(null);
    setModalOpen(true);
  };
  const openEdit = (l: Lokasi) => {
    setEditing(l);
    setModalOpen(true);
  };

  const onSubmit = (values: FormValues) => {
    const payload = {
      unit_id: Number(values.unit_id),
      kode_lokasi: String(values.kode_lokasi),
      nama_lokasi: String(values.nama_lokasi),
      kategori: String(values.kategori),
      lantai: values.lantai ? String(values.lantai) : undefined,
      deskripsi: values.deskripsi ? String(values.deskripsi) : undefined,
      is_active: Boolean(values.is_active),
    };
    const onDone = { onSuccess: () => setModalOpen(false), onError: showApiError };
    if (editing) {
      updateLokasi.mutate({ id: editing.id, data: payload }, onDone);
    } else {
      createLokasi.mutate(payload, onDone);
    }
  };

  const confirmDelete = (l: Lokasi) =>
    Alert.alert("Hapus Lokasi", `Hapus "${l.nama_lokasi}"?`, [
      { text: "Batal", style: "cancel" },
      {
        text: "Hapus",
        style: "destructive",
        onPress: () => deleteLokasi.mutate(l.id, { onError: showApiError }),
      },
    ]);

  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <AdminScreen
        title="Lokasi"
        subtitle={`${lokasi.length} lokasi terdaftar`}
        icon="location-outline"
        color="#0891b2"
        searchValue={q}
        onSearchChange={setQ}
        searchPlaceholder="Cari lokasi / kode QR..."
        onAdd={openCreate}
        addLabel="Lokasi"
      >
        <ScrollView
          contentContainerStyle={{ padding: isTablet ? 32 : 20, paddingBottom: 40 }}
        >
          {isLoading ? (
            <View className="items-center py-16">
              <ActivityIndicator color="#0891b2" />
            </View>
          ) : filtered.length === 0 ? (
            <EmptyState icon="search-outline" title="Tidak ditemukan" />
          ) : (
            <View className={isTablet ? "flex-row flex-wrap -m-2" : "gap-3"}>
              {filtered.map((l) => (
                <View key={l.id} className={isTablet ? "w-1/2 p-2" : ""}>
                  <Pressable
                    onPress={() => openEdit(l)}
                    onLongPress={() => confirmDelete(l)}
                    className="p-4 rounded-2xl bg-surface-container-lowest border border-outline-variant active:opacity-80"
                  >
                    <View className="flex-row items-center gap-3">
                      <View className="w-12 h-12 rounded-xl bg-primary/10 items-center justify-center">
                        <Ionicons name="location" size={22} color="#005bbf" />
                      </View>
                      <View className="flex-1">
                        <Text className="font-bold text-on-surface" numberOfLines={2}>
                          {l.nama_lokasi}
                        </Text>
                        <View className="flex-row items-center gap-2 mt-0.5">
                          <View className="flex-row items-center gap-1">
                            <Ionicons name="business-outline" size={11} color="#5a6072" />
                            <Text className="text-on-surface-variant text-xs">
                              {l.unit?.nama_unit ?? "-"}
                            </Text>
                          </View>
                          <View className="px-2 py-0.5 rounded-full bg-primary/10">
                            <Text className="text-primary text-[10px] font-bold">
                              {l.kode_lokasi}
                            </Text>
                          </View>
                        </View>
                      </View>
                      <View
                        className={`px-2 py-0.5 rounded-full ${
                          l.is_active ? "bg-secondary/15" : "bg-on-surface-variant/15"
                        }`}
                      >
                        <Text
                          className={`text-[10px] font-bold ${
                            l.is_active ? "text-secondary" : "text-on-surface-variant"
                          }`}
                        >
                          {l.is_active ? "Aktif" : "Nonaktif"}
                        </Text>
                      </View>
                    </View>
                    <View className="flex-row items-center justify-between mt-3 pt-3 border-t border-outline-variant/50">
                      <Text className="text-on-surface-variant text-xs">
                        {l.kategori ?? "-"}
                        {l.lantai ? ` · ${l.lantai}` : ""}
                      </Text>
                      <Pressable onPress={() => confirmDelete(l)} hitSlop={8} className="flex-row items-center gap-1">
                        <Ionicons name="trash-outline" size={14} color="#d62828" />
                        <Text className="text-error text-xs font-bold">Hapus</Text>
                      </Pressable>
                    </View>
                  </Pressable>
                </View>
              ))}
            </View>
          )}
        </ScrollView>
      </AdminScreen>

      <EntityFormModal
        visible={modalOpen}
        title={editing ? "Edit Lokasi" : "Tambah Lokasi"}
        fields={fields}
        initialValues={
          editing
            ? {
                unit_id: editing.unit?.id ?? null,
                kode_lokasi: editing.kode_lokasi,
                nama_lokasi: editing.nama_lokasi,
                kategori: editing.kategori ?? "",
                lantai: editing.lantai ?? "",
                deskripsi: editing.deskripsi ?? "",
                is_active: editing.is_active ?? true,
              }
            : undefined
        }
        submitting={createLokasi.isPending || updateLokasi.isPending}
        onCancel={() => setModalOpen(false)}
        onSubmit={onSubmit}
      />
    </>
  );
}
