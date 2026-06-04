import { useMemo, useState } from "react";
import { Alert, Pressable, ScrollView, Text, View } from "react-native";
import { Stack, useRouter } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { AdminScreen, EmptyState } from "@/components/admin/AdminScreen";
import { useIsTablet } from "@/lib/useIsTablet";

type IoniconName = React.ComponentProps<typeof Ionicons>["name"];

export interface ApprovalItem {
  id: number;
  tanggal: string;
  petugas: string;
  area: string;
  unit: string;
  kegiatan: string;
  dibuat: string;
}

export interface ApprovalConfig {
  title: string;
  subtitle?: string;
  icon: IoniconName;
  color: string;
  teamLabel: string;
  data: ApprovalItem[];
}

export function ApprovalTimScreen({ config }: { config: ApprovalConfig }) {
  const router = useRouter();
  const isTablet = useIsTablet();
  const [q, setQ] = useState("");
  const [items, setItems] = useState(config.data);

  const filtered = useMemo(() => {
    const s = q.toLowerCase().trim();
    if (!s) return items;
    return items.filter(
      (i) =>
        i.petugas.toLowerCase().includes(s) ||
        i.area.toLowerCase().includes(s) ||
        i.kegiatan.toLowerCase().includes(s)
    );
  }, [items, q]);

  const onApprove = (item: ApprovalItem) => {
    Alert.alert(
      "Setujui Laporan",
      `Setujui laporan dari ${item.petugas} (${item.area})?`,
      [
        { text: "Batal", style: "cancel" },
        {
          text: "Setujui",
          onPress: () => {
            setItems((prev) => prev.filter((p) => p.id !== item.id));
            Alert.alert("Berhasil", "Laporan telah disetujui.");
          },
        },
      ]
    );
  };

  const onReject = (item: ApprovalItem) => {
    Alert.alert(
      "Tolak Laporan",
      `Tolak laporan dari ${item.petugas}?`,
      [
        { text: "Batal", style: "cancel" },
        {
          text: "Tolak",
          style: "destructive",
          onPress: () => {
            setItems((prev) => prev.filter((p) => p.id !== item.id));
            Alert.alert("Berhasil", "Laporan ditolak.");
          },
        },
      ]
    );
  };

  const onDetail = (item: ApprovalItem) =>
    router.push({
      pathname: "/admin/laporan-detail",
      params: {
        id: item.id,
        petugas: item.petugas,
        lokasi: item.area,
        unit: item.unit,
        tanggal: item.tanggal,
        summary: item.kegiatan,
        status: "submitted",
      },
    });

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
          {/* Summary banner */}
          {filtered.length > 0 && (
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

          {filtered.length === 0 ? (
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
                            {item.petugas}
                          </Text>
                          <Text className="text-on-surface-variant text-[10px]">
                            {item.dibuat}
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
                            {item.area} · {item.unit}
                          </Text>
                        </View>
                        <Text
                          className="text-on-surface-variant text-xs mt-1"
                          numberOfLines={2}
                        >
                          {item.kegiatan}
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
