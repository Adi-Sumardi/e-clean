import { useMemo, useState } from "react";
import { Alert, Pressable, ScrollView, Text, View } from "react-native";
import { Stack } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { AdminScreen, EmptyState } from "@/components/admin/AdminScreen";
import { useIsTablet } from "@/lib/useIsTablet";

interface JadwalRow {
  id: number;
  tanggal: string;
  shift: string;
  petugas: string;
  lokasi: string;
  unit: string;
  status: "scheduled" | "completed" | "missed";
}

const JADWAL: JadwalRow[] = [
  {
    id: 1,
    tanggal: "02 Jun",
    shift: "Pagi",
    petugas: "Rahmat Hidayat",
    lokasi: "Toilet Lt.1 - Gedung A",
    unit: "Office",
    status: "completed",
  },
  {
    id: 2,
    tanggal: "02 Jun",
    shift: "Pagi",
    petugas: "Siti Nurhaliza",
    lokasi: "Lobi Utama",
    unit: "Office",
    status: "completed",
  },
  {
    id: 3,
    tanggal: "02 Jun",
    shift: "Siang",
    petugas: "Andi Setiawan",
    lokasi: "Pantry Lt.2",
    unit: "Office",
    status: "scheduled",
  },
  {
    id: 4,
    tanggal: "02 Jun",
    shift: "Siang",
    petugas: "Budi Hartono",
    lokasi: "Ruang Rapat Besar",
    unit: "Office",
    status: "scheduled",
  },
  {
    id: 5,
    tanggal: "03 Jun",
    shift: "Pagi",
    petugas: "Citra Wijaya",
    lokasi: "Toilet Lt.3",
    unit: "Office",
    status: "scheduled",
  },
  {
    id: 6,
    tanggal: "01 Jun",
    shift: "Sore",
    petugas: "Eko Prasetyo",
    lokasi: "Display Toko",
    unit: "Toko",
    status: "missed",
  },
];

const STATUS_TONE: Record<
  JadwalRow["status"],
  { bg: string; text: string; label: string; icon: React.ComponentProps<typeof Ionicons>["name"] }
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

export default function JadwalAdminScreen() {
  const isTablet = useIsTablet();
  const [q, setQ] = useState("");

  const filtered = useMemo(() => {
    const s = q.toLowerCase().trim();
    if (!s) return JADWAL;
    return JADWAL.filter(
      (j) =>
        j.petugas.toLowerCase().includes(s) ||
        j.lokasi.toLowerCase().includes(s)
    );
  }, [q]);

  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <AdminScreen
        title="Jadwal Kebersihan"
        subtitle={`${JADWAL.length} jadwal aktif`}
        icon="calendar-outline"
        color="#005bbf"
        searchValue={q}
        onSearchChange={setQ}
        searchPlaceholder="Cari petugas / lokasi..."
        onAdd={() => Alert.alert("Buat Jadwal", "Form akan tampil.")}
        addLabel="Jadwal"
      >
        <ScrollView
          contentContainerStyle={{ padding: isTablet ? 32 : 20, paddingBottom: 40 }}
        >
          {filtered.length === 0 ? (
            <EmptyState icon="calendar-outline" title="Tidak ada jadwal" />
          ) : (
            <View className={isTablet ? "flex-row flex-wrap -m-2" : "gap-3"}>
              {filtered.map((j) => {
                const tone = STATUS_TONE[j.status];
                return (
                  <View key={j.id} className={isTablet ? "w-1/2 p-2" : ""}>
                    <Pressable className="p-4 rounded-2xl bg-surface-container-lowest border border-outline-variant active:opacity-80">
                      <View className="flex-row items-center gap-3">
                        <View className="w-14 h-14 rounded-xl bg-primary/10 items-center justify-center">
                          <Text className="text-primary text-[10px] font-semibold">
                            {j.tanggal.split(" ")[1]}
                          </Text>
                          <Text className="text-primary text-lg font-bold">
                            {j.tanggal.split(" ")[0]}
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
                              <Text className="text-tertiary text-[10px] font-bold">
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
                              {j.lokasi}
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
                      <View
                        className={`mt-3 self-start px-3 py-1 rounded-full flex-row items-center gap-1 ${tone.bg}`}
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
