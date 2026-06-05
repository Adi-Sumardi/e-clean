import { useMemo, useState } from "react";
import { ActivityIndicator, Alert, Pressable, ScrollView, Text, View } from "react-native";
import { Stack } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { AdminScreen, EmptyState } from "@/components/admin/AdminScreen";
import { EntityFormModal, type FieldDef, type FormValues } from "@/components/admin/EntityFormModal";
import { useIsTablet } from "@/lib/useIsTablet";
import {
  useGuestComplaints,
  useUsers,
  useAssignComplaint,
  useUpdateComplaintStatus,
} from "@/lib/hooks";
import { ApiError } from "@/lib/api";

type Status = "pending" | "in_progress" | "resolved" | "rejected";
type Filter = "all" | Status;

interface ComplaintRow {
  id: number;
  jenis: string;
  lokasi: string;
  unit: string;
  pelapor: string;
  telepon: string;
  deskripsi: string;
  waktu: string;
  status: Status;
  assignedTo?: string;
}

const STATUS_TONE: Record<Status, { bg: string; text: string; label: string }> = {
  pending: { bg: "bg-error/15", text: "text-error", label: "Menunggu" },
  in_progress: {
    bg: "bg-tertiary/15",
    text: "text-tertiary",
    label: "Sedang Ditangani",
  },
  resolved: { bg: "bg-secondary/15", text: "text-secondary", label: "Selesai" },
  rejected: {
    bg: "bg-on-surface-variant/15",
    text: "text-on-surface-variant",
    label: "Ditolak",
  },
};

const JENIS_TONE: Record<string, string> = {
  tumpahan: "#d62828",
  kotor: "#e08a14",
  bau: "#0891b2",
  rusak: "#5a6072",
  lainnya: "#7e5a17",
};

const JENIS_LABEL: Record<string, string> = {
  tumpahan: "Tumpahan",
  kotor: "Kotor",
  bau: "Bau",
  rusak: "Fasilitas Rusak",
  lainnya: "Lainnya",
};

const FILTERS: { key: Filter; label: string }[] = [
  { key: "all", label: "Semua" },
  { key: "pending", label: "Menunggu" },
  { key: "in_progress", label: "Ditangani" },
  { key: "resolved", label: "Selesai" },
  { key: "rejected", label: "Ditolak" },
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

export default function KeluhanScreen() {
  const isTablet = useIsTablet();
  const [q, setQ] = useState("");
  const [filter, setFilter] = useState<Filter>("all");

  const [assigningId, setAssigningId] = useState<number | null>(null);
  const [updatingId, setUpdatingId] = useState<number | null>(null);
  const [updatingStatus, setUpdatingStatus] = useState<Status | null>(null);

  const { data: rawComplaints, isLoading, refetch } = useGuestComplaints();
  const { data: petugasList } = useUsers({ role: "petugas", active_only: true });

  const assignMutation = useAssignComplaint();
  const updateStatusMutation = useUpdateComplaintStatus();

  const complaints = useMemo<ComplaintRow[]>(() => {
    if (!rawComplaints) return [];
    return rawComplaints.map((c) => {
      // Calculate relative time or format date
      let waktu = "Baru saja";
      if (c.created_at) {
        try {
          const d = new Date(c.created_at);
          waktu = d.toLocaleDateString("id-ID", {
            day: "numeric",
            month: "short",
            hour: "2-digit",
            minute: "2-digit",
          });
        } catch {}
      }

      return {
        id: c.id,
        jenis: JENIS_LABEL[c.jenis_keluhan] ?? c.jenis_keluhan,
        lokasi: c.lokasi?.nama_lokasi ?? "-",
        unit: c.lokasi?.unit?.nama_unit ?? "-",
        pelapor: c.nama_pelapor,
        telepon: c.telepon_pelapor ?? "-",
        deskripsi: c.deskripsi_keluhan,
        waktu,
        status: c.status,
        assignedTo: c.assignee?.name,
      };
    });
  }, [rawComplaints]);

  const stats = useMemo(
    () => ({
      total: complaints.length,
      pending: complaints.filter((c) => c.status === "pending").length,
      inProgress: complaints.filter((c) => c.status === "in_progress").length,
    }),
    [complaints]
  );

  const filtered = useMemo(() => {
    const s = q.toLowerCase().trim();
    let list = complaints;
    if (filter !== "all") list = list.filter((c) => c.status === filter);
    if (s) {
      list = list.filter(
        (c) =>
          c.lokasi.toLowerCase().includes(s) ||
          c.pelapor.toLowerCase().includes(s) ||
          c.deskripsi.toLowerCase().includes(s)
      );
    }
    return list;
  }, [q, filter, complaints]);

  const assignFields = useMemo<FieldDef[]>(
    () => [
      {
        key: "assigned_to",
        label: "Petugas Lapangan",
        type: "select",
        required: true,
        options: (petugasList ?? []).map((u) => ({ value: u.id, label: u.name })),
      },
    ],
    [petugasList]
  );

  const statusFields = useMemo<FieldDef[]>(
    () => [
      {
        key: "status",
        label: "Status Penanganan",
        type: "select",
        required: true,
        options: [
          { value: "in_progress", label: "Sedang Ditangani" },
          { value: "resolved", label: "Selesai" },
          { value: "rejected", label: "Ditolak" },
        ],
      },
      {
        key: "catatan_penanganan",
        label: "Catatan Tindak Lanjut",
        type: "textarea",
      },
    ],
    []
  );

  const handleAssignSubmit = (values: FormValues) => {
    if (!assigningId) return;
    assignMutation.mutate(
      {
        id: assigningId,
        assignedTo: Number(values.assigned_to),
      },
      {
        onSuccess: () => {
          setAssigningId(null);
          Alert.alert("Berhasil", "Keluhan telah ditugaskan.");
          refetch();
        },
        onError: showApiError,
      }
    );
  };

  const handleStatusSubmit = (values: FormValues) => {
    if (!updatingId) return;
    updateStatusMutation.mutate(
      {
        id: updatingId,
        status: String(values.status),
        catatanPenanganan: values.catatan_penanganan ? String(values.catatan_penanganan) : undefined,
      },
      {
        onSuccess: () => {
          setUpdatingId(null);
          Alert.alert("Berhasil", "Status keluhan telah diperbarui.");
          refetch();
        },
        onError: showApiError,
      }
    );
  };

  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <AdminScreen
        title="Keluhan Tamu"
        subtitle={`${stats.pending} menunggu · ${stats.inProgress} ditangani`}
        icon="chatbubble-ellipses-outline"
        color="#d62828"
        searchValue={q}
        onSearchChange={setQ}
        searchPlaceholder="Cari lokasi / pelapor..."
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
                    justifyContent: "center",
                    borderColor: active ? "#d62828" : "#e1e3e4",
                    backgroundColor: active ? "#d62828" : "#ffffff",
                  }}
                >
                  <Text
                    style={{
                      fontSize: 13,
                      fontWeight: "600",
                      lineHeight: 18,
                      color: active ? "#ffffff" : "#1a1c1e",
                    }}
                  >
                    {f.label}
                  </Text>
                </Pressable>
              );
            })}
          </ScrollView>
        </View>

        <ScrollView
          contentContainerStyle={{ padding: isTablet ? 32 : 20, paddingBottom: 120 }}
        >
          {isLoading ? (
            <View className="items-center py-16">
              <ActivityIndicator color="#d62828" />
            </View>
          ) : filtered.length === 0 ? (
            <EmptyState
              icon="checkmark-done-circle-outline"
              title="Tidak ada keluhan"
            />
          ) : (
            <View className={isTablet ? "flex-row flex-wrap -m-2" : "gap-3"}>
              {filtered.map((c) => {
                const tone = STATUS_TONE[c.status];
                // normalize tone key match
                const normalizedJenis = String(c.jenis).toLowerCase();
                const jenisColor = JENIS_TONE[normalizedJenis] ?? "#5a6072";
                return (
                  <View key={c.id} className={isTablet ? "w-1/2 p-2" : ""}>
                    <Pressable
                      onPress={() => setUpdatingId(c.id)}
                      className="p-4 rounded-2xl bg-surface-container-lowest border border-outline-variant active:opacity-80"
                    >
                      <View className="flex-row items-start gap-3">
                        <View
                          className="w-12 h-12 rounded-xl items-center justify-center"
                          style={{ backgroundColor: `${jenisColor}1a` }}
                        >
                          <Ionicons
                            name="warning"
                            size={22}
                            color={jenisColor}
                          />
                        </View>
                        <View className="flex-1">
                          <View className="flex-row items-center justify-between">
                            <View
                              className="px-2 py-0.5 rounded-full"
                              style={{ backgroundColor: `${jenisColor}1a` }}
                            >
                              <Text
                                className="text-[10px] font-bold"
                                style={{ color: jenisColor }}
                              >
                                {c.jenis}
                              </Text>
                            </View>
                            <Text className="text-on-surface-variant text-[10px]">
                              {c.waktu}
                            </Text>
                          </View>
                          <Text
                            className="font-bold text-on-surface mt-1"
                            numberOfLines={1}
                          >
                            {c.lokasi}
                          </Text>
                          <View className="flex-row items-center gap-1 mt-0.5">
                            <Ionicons
                              name="person-outline"
                              size={11}
                              color="#5a6072"
                            />
                            <Text
                              className="text-on-surface-variant text-xs"
                              numberOfLines={1}
                            >
                              {c.pelapor} · {c.telepon}
                            </Text>
                          </View>
                          <Text
                            className="text-on-surface-variant text-xs mt-1 italic"
                            numberOfLines={2}
                          >
                            "{c.deskripsi}"
                          </Text>
                        </View>
                      </View>
                      <View className="flex-row items-center justify-between mt-3 pt-3 border-t border-outline-variant/50">
                        <Pressable
                          onPress={() => setUpdatingId(c.id)}
                          className={`px-2 py-0.5 rounded-full ${tone.bg}`}
                        >
                          <Text className={`text-[10px] font-bold ${tone.text}`}>
                            {tone.label}
                          </Text>
                        </Pressable>
                        {c.assignedTo ? (
                          <View className="flex-row items-center gap-1">
                            <Ionicons
                              name="person"
                              size={11}
                              color="#005bbf"
                            />
                            <Text className="text-primary text-[11px] font-semibold">
                              {c.assignedTo}
                            </Text>
                          </View>
                        ) : (
                          <Pressable
                            onPress={() => setAssigningId(c.id)}
                            className="px-3 py-1 rounded-lg bg-primary active:opacity-80"
                          >
                            <Text className="text-white text-[11px] font-bold">
                              Assign
                            </Text>
                          </Pressable>
                        )}
                      </View>
                    </Pressable>
                  </View>
                );
              })}
            </View>
          )}
        </ScrollView>
      </AdminScreen>

      <EntityFormModal
        visible={assigningId !== null}
        title="Tugaskan Petugas"
        fields={assignFields}
        initialValues={{}}
        submitting={assignMutation.isPending}
        submitLabel="Tugaskan"
        onCancel={() => setAssigningId(null)}
        onSubmit={handleAssignSubmit}
      />

      <EntityFormModal
        visible={updatingId !== null}
        title="Ubah Status Keluhan"
        fields={statusFields}
        initialValues={{
          status: "in_progress",
        }}
        submitting={updateStatusMutation.isPending}
        submitLabel="Perbarui Status"
        onCancel={() => setUpdatingId(null)}
        onSubmit={handleStatusSubmit}
      />
    </>
  );
}
