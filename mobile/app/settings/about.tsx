import { ScrollView, Text, View, Pressable } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { LinearGradient } from "expo-linear-gradient";
import { Stack, useRouter } from "expo-router";
import { Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";
import Constants from "expo-constants";

const MILESTONES: { month: string; title: string; desc: string; color: string }[] = [
  {
    month: "Nov 2025",
    title: "Fondasi Aplikasi",
    desc: "Setup Laravel + Filament, autentikasi, model inti (unit, lokasi, jadwal, laporan).",
    color: "#0a5fd6",
  },
  {
    month: "Des 2025",
    title: "Persiapan Produksi & Fitur Inti",
    desc: "Workflow laporan kebersihan, approval supervisor, penilaian & leaderboard.",
    color: "#0a7e3e",
  },
  {
    month: "Jan 2026",
    title: "Integrasi Keluhan Tamu",
    desc: "Form keluhan tamu publik + notifikasi ke petugas terkait.",
    color: "#e08a14",
  },
  {
    month: "Feb 2026",
    title: "Pelaporan Bulanan",
    desc: "Rekap laporan bulanan & penyederhanaan validasi GPS.",
    color: "#7e5a17",
  },
  {
    month: "Mar 2026",
    title: "Stabilisasi & Testing",
    desc: "Perbaikan besar, unit & feature test, dukungan PostgreSQL produksi.",
    color: "#0891b2",
  },
  {
    month: "Apr 2026",
    title: "Pembatasan Data & WhatsApp",
    desc: "Pembatasan data 30 hari per peran, provider WhatsApp (Twilio).",
    color: "#9333ea",
  },
  {
    month: "Jun 2026",
    title: "Aplikasi Mobile (ServiceGO)",
    desc: "Dashboard per peran, laporan multi-domain (kebersihan, satpam, OB, toko), push notification & build Android.",
    color: "#d62828",
  },
];

function Row({
  icon,
  label,
  value,
}: {
  icon: React.ComponentProps<typeof Ionicons>["name"];
  label: string;
  value: string;
}) {
  return (
    <View className="flex-row items-center gap-3 py-2.5 border-b border-outline-variant/60">
      <Ionicons name={icon} size={18} color="#0a5fd6" />
      <Text className="flex-1 text-on-surface-variant text-sm">{label}</Text>
      <Text className="text-on-surface font-semibold text-sm">{value}</Text>
    </View>
  );
}

export default function AboutScreen() {
  const router = useRouter();
  const version =
    Constants.expoConfig?.version ?? Constants.nativeAppVersion ?? "1.0.0";

  return (
    <View className="flex-1 bg-background">
      <Stack.Screen options={{ headerShown: false }} />
      <LinearGradient
        colors={["#0a5fd6", "#0a3aa0"]}
        style={{ borderBottomLeftRadius: 24, borderBottomRightRadius: 24 }}
      >
        <SafeAreaView edges={["top"]}>
          <View className="flex-row items-center gap-3 px-4 h-16">
            <Pressable
              onPress={() => router.back()}
              hitSlop={8}
              className="w-10 h-10 rounded-full bg-white/15 items-center justify-center"
            >
              <Ionicons name="arrow-back" size={20} color="#fff" />
            </Pressable>
            <Text className="text-white font-bold text-lg">Tentang Aplikasi</Text>
          </View>
        </SafeAreaView>
      </LinearGradient>

      <ScrollView contentContainerStyle={{ padding: 20, paddingBottom: 60 }}>
        {/* App identity */}
        <View className="items-center mb-6 mt-2">
          <View className="w-20 h-20 rounded-3xl bg-primary items-center justify-center mb-3">
            <MaterialCommunityIcons name="broom" size={38} color="#fff" />
          </View>
          <Text className="text-on-surface font-bold text-2xl">ServiceGO</Text>
          <Text className="text-on-surface-variant text-sm text-center mt-1 px-6">
            Smart Operational Management untuk kebersihan, keamanan, office boy,
            dan toko — Koperasi Karyawan YAPI.
          </Text>
        </View>

        <View className="bg-surface-container-lowest rounded-2xl px-4 py-1 border border-outline-variant mb-6">
          <Row icon="pricetag-outline" label="Versi" value={`v${version}`} />
          <Row icon="business-outline" label="Penerbit" value="Kopkar YAPI" />
          <Row icon="phone-portrait-outline" label="Platform" value="Android" />
        </View>

        {/* Roadmap */}
        <View className="flex-row items-center gap-2 mb-4">
          <View className="w-1 h-5 rounded-full bg-primary" />
          <Text className="font-bold text-on-surface text-base">
            Perjalanan Pembuatan Aplikasi
          </Text>
        </View>

        <View>
          {MILESTONES.map((m, i) => (
            <View key={m.month} className="flex-row gap-3">
              {/* timeline rail */}
              <View className="items-center" style={{ width: 24 }}>
                <View
                  className="w-3.5 h-3.5 rounded-full mt-1.5"
                  style={{ backgroundColor: m.color }}
                />
                {i < MILESTONES.length - 1 && (
                  <View className="flex-1 w-0.5 bg-outline-variant my-1" />
                )}
              </View>
              <View className="flex-1 pb-5">
                <View
                  className="self-start px-2 py-0.5 rounded-full mb-1"
                  style={{ backgroundColor: `${m.color}1a` }}
                >
                  <Text
                    className="text-[10px] font-bold"
                    style={{ color: m.color }}
                  >
                    {m.month}
                  </Text>
                </View>
                <Text className="font-bold text-on-surface text-sm">
                  {m.title}
                </Text>
                <Text className="text-on-surface-variant text-xs mt-0.5 leading-5">
                  {m.desc}
                </Text>
              </View>
            </View>
          ))}
        </View>

        <Text className="text-center text-on-surface-variant text-xs mt-2">
          © 2026 Koperasi Karyawan YAPI · ServiceGO
        </Text>
      </ScrollView>
    </View>
  );
}
