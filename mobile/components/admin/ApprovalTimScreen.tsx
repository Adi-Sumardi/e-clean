import { useMemo, useState } from "react";
import { Alert, Pressable, ScrollView, Text, View, ActivityIndicator } from "react-native";
import { Stack, useRouter } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { AdminScreen, EmptyState } from "@/components/admin/AdminScreen";
import { useIsTablet } from "@/lib/useIsTablet";
import { usePendingApprovals, useApproveReport, useRejectReport } from "@/lib/hooks";
import type { ApprovalScope, ApprovalItem } from "@/lib/types";
import { ApiError } from "@/lib/api";

type IoniconName = React.ComponentProps<typeof Ionicons>["name"];

export interface ApprovalConfig {
  title: string;
  subtitle?: string;
  icon: IoniconName;
  color: string;
  teamLabel: string;
  scope: ApprovalScope;
}

export function ApprovalTimScreen({ config }: { config: ApprovalConfig }) {
  const router = useRouter();
  const isTablet = useIsTablet();
  const [q, setQ] = useState("");

  const { data, isLoading, refetch } = usePendingApprovals(config.scope);
  const approveMutation = useApproveReport();
  const rejectMutation = useRejectReport();

  const items = data ?? [];

  const filtered = useMemo(() => {
    const s = q.toLowerCase().trim();
    if (!s) return items;
    return items.filter(
      (i) =>
        i.petugasName.toLowerCase().includes(s) ||
        i.lokasiName.toLowerCase().includes(s) ||
        i.summary.toLowerCase().includes(s)
    );
  }, [items, q]);

  const onApprove = (item: ApprovalItem) => {
    Alert.alert(
      "Setujui Laporan",
      `Beri rating/catatan secara detail?`,
      [
        { text: "Batal", style: "cancel" },
        {
          text: "Detail & Nilai",
          onPress: () => onDetail(item),
        },
        {
          text: "Setujui Langsung (Bintang 5)",
          onPress: () => {
            approveMutation.mutate(
              {
                scope: item.scope,
                id: item.id,
                rating: 5,
                catatan_supervisor: "Disetujui langsung dari list",
              },
              {
                onSuccess: () => {
                  Alert.alert("Berhasil", "Laporan telah disetujui.");
                  refetch();
                },
                onError: (err) => {
                  const msg = err instanceof ApiError ? err.message : "Gagal menyetujui laporan";
                  Alert.alert("Gagal", msg);
                },
              }
            );
          },
        },
      ]
    );
  };

  const onReject = (item: ApprovalItem) => {
    Alert.prompt(
      "Tolak Laporan",
      `Masukkan alasan penolakan untuk ${item.petugasName}:`,
      [
        { text: "Batal", style: "cancel" },
        {
          text: "Tolak",
          style: "destructive",
          onPress: (reason) => {
            if (!reason || !reason.trim()) {
              Alert.alert("Gagal", "Alasan penolakan tidak boleh kosong.");
              return;
            }
            rejectMutation.mutate(
              {
                scope: item.scope,
                id: item.id,
                reason: reason.trim(),
              },
              {
                onSuccess: () => {
                  Alert.alert("Berhasil", "Laporan ditolak.");
                  refetch();
                },
                onError: (err) => {
                  const msg = err instanceof ApiError ? err.message : "Gagal menolak laporan";
                  Alert.alert("Gagal", msg);
                },
              }
            );
          },
        },
      ],
      "plain-text"
    );
  };

  const onDetail = (item: ApprovalItem) =>
    router.push({
      pathname: "/admin/laporan-detail",
      params: {
        id: item.id,
        scope: item.scope,
        petugas: item.petugasName,
        lokasi: item.lokasiName,
        unit: item.unit?.nama_unit ?? "-",
        tanggal: item.tanggal,
        summary: item.summary,
        status: item.status,
      },
    });

  const timeLabel = (createdAt?: string) => {
    if (!createdAt) return "";
    try {
      const d = new Date(createdAt);
      return d.toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" });
    } catch {
      return "";
    }
  };

  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <AdminScreen
        title={config.title}
        subtitle={config.subtitle ?? `${filtered.length} laporan menunggu`}
        icon={config.icon}
        color={config.color}
        searchValue={q}
        onSearchChange={setQ}
        searchPlaceholder="Cari petugas / area..."
      >
        <ScrollView
          contentContainerStyle={{ padding: isTablet ? 32 : 20, paddingBottom: 40 }}
        >
          {isLoading ? (
            <View className="items-center py-20">
              <ActivityIndicator size="large" color={config.color} />
            </View>
          ) : filtered.length > 0 && (
            <View
              className="p-4 rounded-2xl mb-5 flex-row items-center gap-3"
              style={{ backgroundColor: `${config.color}10`, borderColor: `${config.color}40`, borderWidth: 1 }}
            >
              <View
                className="w-11 h-11 rounded-xl items-center justify-center"
                style={{ backgroundColor: `${config.color}1a` }}
              >
                <Ionicons name="hourglass" size={22} color={config.color} />
              </View>
              <View className="flex-1">
                <Text className="font-bold" style={{ color: config.color }}>
                  {filtered.length} laporan menunggu approval
                </Text>
                <Text
                  className="text-xs"
                  style={{ color: config.color, opacity: 0.8 }}
                >
                  Tinjau laporan dari tim {config.teamLabel}
                </Text>
              </View>
            </View>
          )}

          {!isLoading && filtered.length === 0 ? (
            <EmptyState
              icon="checkmark-done-circle-outline"
              title="Semua sudah diproses"
              description="Tidak ada laporan menunggu approval"
            />
          ) : (
            <View className={isTablet ? "flex-row flex-wrap -m-2" : "gap-3"}>
              {filtered.map((item) => (
                <View key={item.id} className={isTablet ? "w-1/2 p-2" : ""}>
                  <View className="p-4 rounded-2xl bg-surface-container-lowest border border-outline-variant">
                    <Pressable
                      onPress={() => onDetail(item)}
                      className="flex-row items-start gap-3 active:opacity-70"
                    >
                      <View
                        className="w-10 h-10 rounded-full items-center justify-center"
                        style={{ backgroundColor: `${config.color}1a` }}
                      >
                        <Ionicons name="person" size={18} color={config.color} />
                      </View>
                      <View className="flex-1">
                        <View className="flex-row items-center justify-between">
                          <Text
                            className="font-bold text-on-surface"
                            numberOfLines={1}
                          >
                            {item.petugasName}
                          </Text>
                          <Text className="text-on-surface-variant text-[10px]">
                            {item.tanggal} {timeLabel(item.createdAt)}
                          </Text>
                        </View>
                        <View className="flex-row items-center gap-1 mt-0.5">
                          <Ionicons
                            name="location-outline"
                            size={11}
                            color="#5a6072"
                          />
                          <Text
                            className="text-on-surface-variant text-xs flex-1"
                            numberOfLines={1}
                          >
                            {item.lokasiName} · {item.unit?.nama_unit ?? "-"}
                          </Text>
                        </View>
                        <Text
                          className="text-on-surface-variant text-xs mt-1"
                          numberOfLines={2}
                        >
                          {item.summary}
                        </Text>
                      </View>
                    </Pressable>

                    <View className="flex-row gap-2 mt-3">
                      <Pressable
                        onPress={() => onReject(item)}
                        className="flex-1 h-10 rounded-lg border-2 border-error items-center justify-center flex-row gap-1 active:opacity-80"
                      >
                        <Ionicons name="close" size={16} color="#d62828" />
                        <Text className="text-error text-xs font-bold">
                          Tolak
                        </Text>
                      </Pressable>
                      <Pressable
                        onPress={() => onDetail(item)}
                        className="w-10 h-10 rounded-lg border border-outline-variant items-center justify-center active:opacity-80"
                      >
                        <Ionicons name="eye-outline" size={16} color="#414754" />
                      </Pressable>
                      <Pressable
                        onPress={() => onApprove(item)}
                        className="flex-[2] h-10 rounded-lg items-center justify-center flex-row gap-1 active:opacity-90"
                        style={{ backgroundColor: config.color }}
                      >
                        <Ionicons name="checkmark" size={16} color="#ffffff" />
                        <Text className="text-white text-xs font-bold">
                          Setujui
                        </Text>
                      </Pressable>
                    </View>
                  </View>
                </View>
              ))}
            </View>
          )}
        </ScrollView>
      </AdminScreen>
    </>
  );
}
