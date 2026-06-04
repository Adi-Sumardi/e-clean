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
  useUnits,
  useCreateUnit,
  useUpdateUnit,
  useDeleteUnit,
} from "@/lib/hooks";
import { ApiError } from "@/lib/api";
import type { Unit } from "@/lib/types";

const FIELDS: FieldDef[] = [
  { key: "kode_unit", label: "Kode Unit", type: "text", required: true, placeholder: "OFC-01" },
  { key: "nama_unit", label: "Nama Unit", type: "text", required: true },
  { key: "alamat", label: "Alamat", type: "textarea" },
  { key: "penanggung_jawab", label: "Penanggung Jawab", type: "text" },
  { key: "telepon", label: "Telepon", type: "text", keyboardType: "phone-pad" },
  { key: "deskripsi", label: "Deskripsi", type: "textarea" },
  { key: "is_active", label: "Aktif", type: "switch" },
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

export default function UnitAdminScreen() {
  const isTablet = useIsTablet();
  const [q, setQ] = useState("");
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<Unit | null>(null);

  const { data, isLoading } = useUnits();
  const createUnit = useCreateUnit();
  const updateUnit = useUpdateUnit();
  const deleteUnit = useDeleteUnit();

  const units = data ?? [];
  const filtered = useMemo(() => {
    const s = q.toLowerCase().trim();
    if (!s) return units;
    return units.filter(
      (u) =>
        u.nama_unit.toLowerCase().includes(s) ||
        u.kode_unit.toLowerCase().includes(s) ||
        (u.alamat ?? "").toLowerCase().includes(s)
    );
  }, [q, units]);

  const openCreate = () => {
    setEditing(null);
    setModalOpen(true);
  };
  const openEdit = (u: Unit) => {
    setEditing(u);
    setModalOpen(true);
  };

  const onSubmit = (values: FormValues) => {
    const payload = {
      kode_unit: String(values.kode_unit),
      nama_unit: String(values.nama_unit),
      alamat: values.alamat ? String(values.alamat) : undefined,
      penanggung_jawab: values.penanggung_jawab
        ? String(values.penanggung_jawab)
        : undefined,
      telepon: values.telepon ? String(values.telepon) : undefined,
      deskripsi: values.deskripsi ? String(values.deskripsi) : undefined,
      is_active: Boolean(values.is_active),
    };
    const onDone = { onSuccess: () => setModalOpen(false), onError: showApiError };
    if (editing) {
      updateUnit.mutate({ id: editing.id, data: payload }, onDone);
    } else {
      createUnit.mutate(payload, onDone);
    }
  };

  const confirmDelete = (u: Unit) =>
    Alert.alert("Hapus Unit", `Hapus "${u.nama_unit}"?`, [
      { text: "Batal", style: "cancel" },
      {
        text: "Hapus",
        style: "destructive",
        onPress: () =>
          deleteUnit.mutate(u.id, {
            onError: showApiError,
          }),
      },
    ]);

  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <AdminScreen
        title="Unit"
        subtitle={`${units.length} unit terdaftar`}
        icon="business-outline"
        color="#7e5a17"
        searchValue={q}
        onSearchChange={setQ}
        searchPlaceholder="Cari unit..."
        onAdd={openCreate}
        addLabel="Unit"
      >
        <ScrollView
          contentContainerStyle={{ padding: isTablet ? 32 : 20, paddingBottom: 40 }}
        >
          {isLoading ? (
            <View className="items-center py-16">
              <ActivityIndicator color="#7e5a17" />
            </View>
          ) : filtered.length === 0 ? (
            <EmptyState icon="search-outline" title="Tidak ditemukan" />
          ) : (
            <View className={isTablet ? "flex-row flex-wrap -m-2" : "gap-3"}>
              {filtered.map((u) => (
                <View key={u.id} className={isTablet ? "w-1/2 p-2" : ""}>
                  <Pressable
                    onPress={() => openEdit(u)}
                    onLongPress={() => confirmDelete(u)}
                    className="p-4 rounded-2xl bg-surface-container-lowest border border-outline-variant active:opacity-80"
                  >
                    <View className="flex-row items-start gap-3">
                      <View className="w-12 h-12 rounded-xl bg-tertiary/15 items-center justify-center">
                        <Ionicons name="business" size={24} color="#7e5a17" />
                      </View>
                      <View className="flex-1">
                        <View className="flex-row items-center justify-between">
                          <Text className="font-bold text-on-surface flex-1" numberOfLines={1}>
                            {u.nama_unit}
                          </Text>
                          <View
                            className={`px-2 py-0.5 rounded-full ${
                              u.is_active ? "bg-secondary/15" : "bg-on-surface-variant/15"
                            }`}
                          >
                            <Text
                              className={`text-[10px] font-bold ${
                                u.is_active ? "text-secondary" : "text-on-surface-variant"
                              }`}
                            >
                              {u.is_active ? "Aktif" : "Nonaktif"}
                            </Text>
                          </View>
                        </View>
                        <Text className="text-tertiary text-xs font-bold mt-0.5">
                          {u.kode_unit}
                        </Text>
                        {u.alamat ? (
                          <View className="flex-row items-start gap-1 mt-1">
                            <Ionicons name="location-outline" size={12} color="#5a6072" />
                            <Text className="text-on-surface-variant text-xs flex-1" numberOfLines={2}>
                              {u.alamat}
                            </Text>
                          </View>
                        ) : null}
                      </View>
                      <Pressable onPress={() => confirmDelete(u)} hitSlop={8} className="p-1">
                        <Ionicons name="trash-outline" size={18} color="#d62828" />
                      </Pressable>
                    </View>
                    <View className="flex-row gap-3 mt-3 pt-3 border-t border-outline-variant/50">
                      <View className="flex-1 flex-row items-center gap-1">
                        <Ionicons name="location" size={12} color="#005bbf" />
                        <Text className="text-on-surface-variant text-xs">
                          <Text className="font-bold text-on-surface">{u.lokasi_count ?? 0}</Text> lokasi
                        </Text>
                      </View>
                      {u.penanggung_jawab ? (
                        <View className="flex-1 flex-row items-center gap-1">
                          <Ionicons name="person" size={12} color="#0a7e3e" />
                          <Text className="text-on-surface-variant text-xs flex-1" numberOfLines={1}>
                            {u.penanggung_jawab}
                          </Text>
                        </View>
                      ) : null}
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
        title={editing ? "Edit Unit" : "Tambah Unit"}
        fields={FIELDS}
        initialValues={
          editing
            ? {
                kode_unit: editing.kode_unit,
                nama_unit: editing.nama_unit,
                alamat: editing.alamat ?? "",
                penanggung_jawab: editing.penanggung_jawab ?? "",
                telepon: editing.telepon ?? "",
                deskripsi: editing.deskripsi ?? "",
                is_active: editing.is_active ?? true,
              }
            : undefined
        }
        submitting={createUnit.isPending || updateUnit.isPending}
        onCancel={() => setModalOpen(false)}
        onSubmit={onSubmit}
      />
    </>
  );
}
