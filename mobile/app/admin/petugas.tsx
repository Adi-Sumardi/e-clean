import { useMemo, useState } from "react";
import { Alert, Pressable, ScrollView, Text, View } from "react-native";
import { Stack } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { AdminScreen, EmptyState } from "@/components/admin/AdminScreen";
import { useIsTablet } from "@/lib/useIsTablet";

interface PetugasRow {
  id: number;
  name: string;
  email: string;
  unit: string;
  jadwalAktif: number;
  rating: number;
  laporanBulanIni: number;
  status: "aktif" | "cuti";
}

const PETUGAS: PetugasRow[] = [
  {
    id: 1,
    name: "Rahmat Hidayat",
    email: "rahmat@yapi",
    unit: "Office",
    jadwalAktif: 5,
    rating: 4.8,
    laporanBulanIni: 42,
    status: "aktif",
  },
  {
    id: 2,
    name: "Siti Nurhaliza",
    email: "siti@yapi",
    unit: "Office",
    jadwalAktif: 4,
    rating: 4.7,
    laporanBulanIni: 39,
    status: "aktif",
  },
  {
    id: 3,
    name: "Andi Setiawan",
    email: "andi@yapi",
    unit: "Office",
    jadwalAktif: 5,
    rating: 4.6,
    laporanBulanIni: 36,
    status: "aktif",
  },
  {
    id: 4,
    name: "Budi Hartono",
    email: "budi@yapi",
    unit: "Office",
    jadwalAktif: 3,
    rating: 4.3,
    laporanBulanIni: 28,
    status: "aktif",
  },
  {
    id: 5,
    name: "Citra Wijaya",
    email: "citra@yapi",
    unit: "Office",
    jadwalAktif: 4,
    rating: 4.5,
    laporanBulanIni: 31,
    status: "aktif",
  },
  {
    id: 6,
    name: "Eko Prasetyo",
    email: "eko@yapi",
    unit: "Office",
    jadwalAktif: 0,
    rating: 4.1,
    laporanBulanIni: 8,
    status: "cuti",
  },
];

function ratingColor(r: number) {
  if (r >= 4.5) return "#0a7e3e";
  if (r >= 4.0) return "#e08a14";
  return "#d62828";
}

export default function PetugasAdminScreen() {
  const isTablet = useIsTablet();
  const [q, setQ] = useState("");

  const filtered = useMemo(() => {
    const s = q.toLowerCase().trim();
    if (!s) return PETUGAS;
    return PETUGAS.filter(
      (p) =>
        p.name.toLowerCase().includes(s) || p.email.toLowerCase().includes(s)
    );
  }, [q]);

  const onAdd = () => Alert.alert("Tambah Petugas", "Form akan tampil di sini.");

  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <AdminScreen
        title="Petugas Kebersihan"
        subtitle={`${PETUGAS.length} petugas terdaftar`}
        icon="people-circle-outline"
        color="#0a7e3e"
        searchValue={q}
        onSearchChange={setQ}
        searchPlaceholder="Cari petugas..."
        onAdd={onAdd}
        addLabel="Petugas"
      >
        <ScrollView
          contentContainerStyle={{ padding: isTablet ? 32 : 20, paddingBottom: 40 }}
        >
          {filtered.length === 0 ? (
            <EmptyState icon="search-outline" title="Tidak ditemukan" />
          ) : isTablet ? (
            <View className="flex-row flex-wrap -m-2">
              {filtered.map((p) => (
                <View key={p.id} className="w-1/2 p-2">
                  <PetugasCard p={p} />
                </View>
              ))}
            </View>
          ) : (
            <View className="gap-3">
              {filtered.map((p) => (
                <PetugasCard key={p.id} p={p} />
              ))}
            </View>
          )}
        </ScrollView>
      </AdminScreen>
    </>
  );
}

function PetugasCard({ p }: { p: PetugasRow }) {
  const isActive = p.status === "aktif";
  const rColor = ratingColor(p.rating);
  return (
    <Pressable className="p-4 rounded-2xl bg-surface-container-lowest border border-outline-variant active:opacity-80">
      <View className="flex-row items-center gap-3">
        <View className="w-12 h-12 rounded-full bg-secondary/10 items-center justify-center">
          <Text className="font-bold text-base text-secondary">
            {p.name.charAt(0).toUpperCase()}
          </Text>
        </View>
        <View className="flex-1">
          <Text className="font-bold text-on-surface" numberOfLines={1}>
            {p.name}
          </Text>
          <View className="flex-row items-center gap-2 mt-0.5">
            <View className="flex-row items-center gap-1">
              <Ionicons name="business-outline" size={11} color="#5a6072" />
              <Text className="text-on-surface-variant text-xs">{p.unit}</Text>
            </View>
            <View className="flex-row items-center gap-1">
              <Ionicons name="star" size={11} color={rColor} />
              <Text className="text-xs font-bold" style={{ color: rColor }}>
                {p.rating}
              </Text>
            </View>
          </View>
        </View>
        <View
          className={`px-2 py-0.5 rounded-full ${
            isActive ? "bg-secondary/15" : "bg-tertiary/15"
          }`}
        >
          <Text
            className={`text-[10px] font-bold ${
              isActive ? "text-secondary" : "text-tertiary"
            }`}
          >
            {isActive ? "Aktif" : "Cuti"}
          </Text>
        </View>
      </View>
      <View className="flex-row items-center gap-2 mt-3 pt-3 border-t border-outline-variant/50">
        <View className="flex-1 flex-row items-center gap-1">
          <Ionicons name="calendar-outline" size={12} color="#005bbf" />
          <Text className="text-on-surface-variant text-xs">
            {p.jadwalAktif} jadwal
          </Text>
        </View>
        <View className="flex-1 flex-row items-center gap-1">
          <Ionicons name="clipboard-outline" size={12} color="#0a7e3e" />
          <Text className="text-on-surface-variant text-xs">
            {p.laporanBulanIni} laporan/bln
          </Text>
        </View>
      </View>
    </Pressable>
  );
}
