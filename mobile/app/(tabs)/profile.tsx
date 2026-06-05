import { Alert, Pressable, ScrollView, Text, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { LinearGradient } from "expo-linear-gradient";
import { useRouter } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { useAuthStore } from "@/stores/auth-store";
import { ROLE_LABEL } from "@/constants/role";
import { useIsTablet } from "@/lib/useIsTablet";

function InfoRow({
  icon,
  label,
  value,
}: {
  icon: React.ComponentProps<typeof Ionicons>["name"];
  label: string;
  value?: string | null;
}) {
  return (
    <View className="flex-row items-center gap-3 py-3 border-b border-outline-variant/60">
      <View className="w-10 h-10 rounded-full bg-primary/10 items-center justify-center">
        <Ionicons name={icon} size={20} color="#0a5fd6" />
      </View>
      <View className="flex-1">
        <Text className="text-on-surface-variant text-xs">{label}</Text>
        <Text className="text-on-surface font-semibold mt-0.5">
          {value ?? "-"}
        </Text>
      </View>
    </View>
  );
}

function MenuItem({
  icon,
  label,
  onPress,
  tone,
}: {
  icon: React.ComponentProps<typeof Ionicons>["name"];
  label: string;
  onPress?: () => void;
  tone?: "default" | "danger";
}) {
  const isDanger = tone === "danger";

  if (isDanger) {
    // Logout: centered icon + label.
    return (
      <Pressable
        onPress={onPress}
        className="flex-row items-center justify-center gap-2 px-4 h-14 rounded-2xl border-2 border-error active:opacity-70"
      >
        <Ionicons name={icon} size={18} color="#d62828" />
        <Text className="font-bold text-error">{label}</Text>
      </Pressable>
    );
  }

  return (
    <Pressable
      onPress={onPress}
      className="flex-row items-center gap-3 px-4 h-14 rounded-2xl bg-surface-container-lowest border border-outline-variant active:opacity-70"
    >
      <View
        className="w-9 h-9 rounded-full items-center justify-center"
        style={{ backgroundColor: "rgba(10,95,214,0.1)" }}
      >
        <Ionicons name={icon} size={18} color="#0a5fd6" />
      </View>
      <Text className="flex-1 font-semibold text-on-surface">{label}</Text>
      <Ionicons name="chevron-forward" size={18} color="#9aa0aa" />
    </Pressable>
  );
}

export default function ProfileScreen() {
  const isTablet = useIsTablet();
  const router = useRouter();
  const user = useAuthStore((s) => s.user);
  const logout = useAuthStore((s) => s.logout);

  const onLogout = () => {
    Alert.alert("Keluar", "Yakin ingin keluar dari akun?", [
      { text: "Batal", style: "cancel" },
      { text: "Keluar", style: "destructive", onPress: () => logout() },
    ]);
  };

  const contentPad = isTablet ? 32 : 20;

  const InfoCard = (
    <View
      className="bg-surface-container-lowest rounded-3xl px-5 py-2"
      style={{
        shadowColor: "#0a3aa0",
        shadowOpacity: 0.1,
        shadowRadius: 16,
        shadowOffset: { width: 0, height: 6 },
        elevation: 5,
      }}
    >
      <InfoRow icon="business-outline" label="Unit" value={user?.unit?.name} />
      <InfoRow icon="call-outline" label="Telepon" value={user?.phone} />
      <InfoRow
        icon="shield-checkmark-outline"
        label="Peran"
        value={user ? ROLE_LABEL[user.role] : null}
      />
    </View>
  );

  const MenuList = (
    <View className="gap-3">
      <MenuItem
        icon="person-outline"
        label="Ubah Profil"
        onPress={() => router.push("/settings/edit-profile")}
      />
      <MenuItem
        icon="lock-closed-outline"
        label="Ubah Kata Sandi"
        onPress={() => router.push("/settings/change-password")}
      />
      <MenuItem
        icon="help-circle-outline"
        label="Bantuan & FAQ"
        onPress={() => router.push("/settings/help")}
      />
      <MenuItem
        icon="information-circle-outline"
        label="Tentang Aplikasi"
        onPress={() => router.push("/settings/about")}
      />
      <MenuItem
        icon="log-out-outline"
        label="Keluar"
        tone="danger"
        onPress={onLogout}
      />
    </View>
  );

  return (
    <View className="flex-1 bg-background">
      {/* Gradient hero */}
      <LinearGradient
        colors={["#0a5fd6", "#0a3aa0", "#06246b"]}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={{
          paddingBottom: 52,
          borderBottomLeftRadius: 32,
          borderBottomRightRadius: 32,
          overflow: "hidden",
        }}
      >
        <View
          style={{
            position: "absolute",
            top: -50,
            right: -30,
            width: 170,
            height: 170,
            borderRadius: 85,
            backgroundColor: "rgba(255,255,255,0.08)",
          }}
        />
        <View
          style={{
            position: "absolute",
            bottom: -20,
            left: -30,
            width: 120,
            height: 120,
            borderRadius: 60,
            backgroundColor: "rgba(255,255,255,0.06)",
          }}
        />
        <SafeAreaView edges={["top"]}>
          <Text className="text-white/90 font-bold text-base text-center mt-2 mb-4">
            Profil
          </Text>
          <View className="items-center px-6">
            <View
              className="rounded-full items-center justify-center mb-3"
              style={{
                width: isTablet ? 110 : 92,
                height: isTablet ? 110 : 92,
                backgroundColor: "rgba(255,255,255,0.2)",
                borderWidth: 3,
                borderColor: "rgba(255,255,255,0.45)",
              }}
            >
              <Text
                className={`text-white font-bold ${isTablet ? "text-5xl" : "text-4xl"}`}
              >
                {user?.name?.charAt(0).toUpperCase() ?? "?"}
              </Text>
            </View>
            <Text
              className={`font-bold text-white ${isTablet ? "text-2xl" : "text-xl"}`}
            >
              {user?.name ?? "-"}
            </Text>
            <Text className="text-white/75 text-sm">{user?.email}</Text>
            <View
              className="mt-2 px-3 py-1 rounded-full flex-row items-center gap-1"
              style={{ backgroundColor: "rgba(255,255,255,0.18)" }}
            >
              <Ionicons name="shield-checkmark" size={12} color="#fff" />
              <Text className="text-white text-xs font-bold">
                {user ? ROLE_LABEL[user.role] : "-"}
              </Text>
            </View>
          </View>
        </SafeAreaView>
      </LinearGradient>

      <ScrollView
        contentContainerStyle={{ padding: contentPad, paddingBottom: 160 }}
        showsVerticalScrollIndicator={false}
      >
        {/* Info card slightly overlapping the hero, with breathing room */}
        <View style={{ marginTop: -12 }}>{InfoCard}</View>

        <Text className="text-on-surface font-bold text-base mt-7 mb-3">
          Pengaturan
        </Text>
        {MenuList}

        <Text className="text-center text-on-surface-variant text-xs mt-6">
          ServiceGO · Smart Operational Management
        </Text>
      </ScrollView>
    </View>
  );
}
