import { useMemo, useState } from "react";
import { ActivityIndicator, Alert, Pressable, ScrollView, Text, View } from "react-native";
import { Stack, useRouter } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { AdminScreen, EmptyState } from "@/components/admin/AdminScreen";
import { useIsTablet } from "@/lib/useIsTablet";
import { useUsers } from "@/lib/hooks";
import { ROLE_LABEL } from "@/constants/role";

interface PetugasRow {
  id: number;
  name: string;
  email: string;
  role: string;
  status: "aktif" | "nonaktif";
  phone: string;
}

export default function PetugasAdminScreen() {
  const isTablet = useIsTablet();
  const router = useRouter();
  const [q, setQ] = useState("");

  const { data, isLoading } = useUsers();

  const STAFF_ROLES = ["petugas", "satpam", "office_boy", "petugas_toko"];

  const staffList = useMemo<PetugasRow[]>(() => {
    if (!data) return [];
    return data
      .filter((u) => u.roles.some((r) => STAFF_ROLES.includes(r)))
      .map((u) => ({
        id: u.id,
        name: u.name,
        email: u.email,
        role: u.roles[0] ?? "petugas",
        status: u.is_active ? "aktif" : "nonaktif",
        phone: u.phone ?? "-",
      }));
  }, [data]);

  const filtered = useMemo(() => {
    const s = q.toLowerCase().trim();
    if (!s) return staffList;
    return staffList.filter(
      (p) =>
        p.name.toLowerCase().includes(s) ||
        p.email.toLowerCase().includes(s) ||
        (ROLE_LABEL[p.role as keyof typeof ROLE_LABEL] ?? p.role).toLowerCase().includes(s)
    );
  }, [q, staffList]);

  const onAdd = () => {
    // Navigate to users management to add new user/staff
    router.push("/admin/users");
  };

  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <AdminScreen
        title="Petugas Lapangan"
        subtitle={`${staffList.length} petugas terdaftar`}
        icon="people-circle-outline"
        color="#0a7e3e"
        searchValue={q}
        onSearchChange={setQ}
        searchPlaceholder="Cari petugas..."
        onAdd={onAdd}
        addLabel="Kelola User"
      >
        <ScrollView
          contentContainerStyle={{ padding: isTablet ? 32 : 20, paddingBottom: 40 }}
        >
          {isLoading ? (
            <View className="items-center py-20">
              <ActivityIndicator size="large" color="#0a7e3e" />
            </View>
          ) : filtered.length === 0 ? (
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
  const roleLabelStr = ROLE_LABEL[p.role as keyof typeof ROLE_LABEL] ?? p.role;
  return (
    <View className="p-4 rounded-2xl bg-surface-container-lowest border border-outline-variant">
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
              <Ionicons name="shield-outline" size={11} color="#5a6072" />
              <Text className="text-on-surface-variant text-xs">{roleLabelStr}</Text>
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
            {isActive ? "Aktif" : "Nonaktif"}
          </Text>
        </View>
      </View>
      <View className="flex-row items-center gap-2 mt-3 pt-3 border-t border-outline-variant/50">
        <View className="flex-1 flex-row items-center gap-1">
          <Ionicons name="mail-outline" size={12} color="#005bbf" />
          <Text className="text-on-surface-variant text-xs" numberOfLines={1}>
            {p.email}
          </Text>
        </View>
        <View className="flex-1 flex-row items-center gap-1">
          <Ionicons name="call-outline" size={12} color="#0a7e3e" />
          <Text className="text-on-surface-variant text-xs" numberOfLines={1}>
            {p.phone}
          </Text>
        </View>
      </View>
    </View>
  );
}
