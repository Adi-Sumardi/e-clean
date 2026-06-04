import { useState } from "react";
import { Alert, Pressable, ScrollView, Text, View } from "react-native";
import { Stack, useRouter } from "expo-router";
import { Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";
import { AdminScreen } from "@/components/admin/AdminScreen";
import { useIsTablet } from "@/lib/useIsTablet";

interface ScanHistory {
  id: number;
  kode: string;
  lokasi: string;
  unit: string;
  waktu: string;
}

const HISTORY: ScanHistory[] = [
  {
    id: 1,
    kode: "LK-A01",
    lokasi: "Toilet Lt.1 - Gedung A",
    unit: "Office",
    waktu: "10 menit lalu",
  },
  {
    id: 2,
    kode: "LK-A02",
    lokasi: "Lobi Utama",
    unit: "Office",
    waktu: "1 jam lalu",
  },
  {
    id: 3,
    kode: "TK-D01",
    lokasi: "Display Rak Toko",
    unit: "Toko",
    waktu: "Kemarin",
  },
];

export default function QRScanScreen() {
  const router = useRouter();
  const isTablet = useIsTablet();
  const [scanning, setScanning] = useState(false);

  const onStartScan = () => {
    setScanning(true);
    setTimeout(() => {
      setScanning(false);
      Alert.alert(
        "QR Code Terdeteksi",
        "Kode: LK-A01\nLokasi: Toilet Lt.1 - Gedung A\n\nLanjutkan ke detail?",
        [
          { text: "Batal", style: "cancel" },
          { text: "Buka Detail", onPress: () => router.push("/admin/lokasi") },
        ]
      );
    }, 1500);
  };

  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <AdminScreen
        title="Scan QR Code"
        subtitle="Scan kode di lokasi untuk akses cepat"
        icon="qr-code-outline"
        color="#0a7e3e"
      >
        <ScrollView
          contentContainerStyle={{ padding: isTablet ? 32 : 20, paddingBottom: 40 }}
        >
          {/* Scan area */}
          <View className="bg-secondary rounded-2xl p-8 items-center mb-6">
            <View className="w-48 h-48 rounded-3xl bg-white/10 items-center justify-center mb-4 relative">
              {scanning ? (
                <>
                  <MaterialCommunityIcons
                    name="qrcode-scan"
                    size={120}
                    color="#ffffff"
                  />
                  <View
                    className="absolute inset-x-0 top-0 h-1 bg-white/80"
                    style={{ top: "50%" }}
                  />
                  <Text className="absolute bottom-2 text-white text-xs font-bold">
                    Memindai...
                  </Text>
                </>
              ) : (
                <>
                  <MaterialCommunityIcons
                    name="qrcode"
                    size={120}
                    color="#ffffff"
                  />
                  {/* Corner markers */}
                  <View className="absolute top-2 left-2 w-6 h-6 border-t-4 border-l-4 border-white rounded-tl-xl" />
                  <View className="absolute top-2 right-2 w-6 h-6 border-t-4 border-r-4 border-white rounded-tr-xl" />
                  <View className="absolute bottom-2 left-2 w-6 h-6 border-b-4 border-l-4 border-white rounded-bl-xl" />
                  <View className="absolute bottom-2 right-2 w-6 h-6 border-b-4 border-r-4 border-white rounded-br-xl" />
                </>
              )}
            </View>
            <Text className="text-white font-bold text-base">
              {scanning ? "Mendeteksi QR Code..." : "Arahkan kamera ke QR Code"}
            </Text>
            <Text className="text-white/70 text-xs mt-1 text-center">
              {scanning
                ? "Tahan device dengan stabil"
                : "Pastikan QR code terlihat jelas dalam frame"}
            </Text>
            <Pressable
              onPress={onStartScan}
              disabled={scanning}
              className={`mt-5 px-6 h-12 rounded-xl bg-white items-center justify-center flex-row gap-2 ${
                scanning ? "opacity-60" : "active:opacity-90"
              }`}
            >
              <Ionicons
                name={scanning ? "hourglass" : "scan"}
                size={20}
                color="#0a7e3e"
              />
              <Text className="text-secondary font-bold">
                {scanning ? "Memindai..." : "Mulai Scan"}
              </Text>
            </Pressable>
          </View>

          {/* Alternative input */}
          <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-5">
            <View className="flex-row items-center gap-2 mb-2">
              <Ionicons name="keypad-outline" size={18} color="#005bbf" />
              <Text className="font-bold text-on-surface">
                Atau input manual
              </Text>
            </View>
            <Text className="text-on-surface-variant text-xs mb-3">
              Masukkan kode QR jika scanner tidak bisa membaca
            </Text>
            <Pressable
              onPress={() =>
                Alert.alert("Input Kode", "Form input kode akan tampil.")
              }
              className="h-12 rounded-xl bg-surface border border-outline-variant items-center justify-center flex-row gap-2 active:opacity-80"
            >
              <Ionicons name="text-outline" size={18} color="#414754" />
              <Text className="text-on-surface font-semibold">
                Input Kode Manual
              </Text>
            </Pressable>
          </View>

          {/* History */}
          <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4">
            <View className="flex-row items-center justify-between mb-3">
              <View>
                <Text className="font-bold text-on-surface">Riwayat Scan</Text>
                <Text className="text-on-surface-variant text-xs">
                  {HISTORY.length} scan terakhir
                </Text>
              </View>
              <Ionicons name="time-outline" size={20} color="#5a6072" />
            </View>
            <View className="gap-2">
              {HISTORY.map((h) => (
                <Pressable
                  key={h.id}
                  className="flex-row items-center gap-3 p-3 rounded-xl bg-surface active:opacity-80"
                >
                  <View className="w-10 h-10 rounded-xl bg-secondary/15 items-center justify-center">
                    <MaterialCommunityIcons
                      name="qrcode"
                      size={20}
                      color="#0a7e3e"
                    />
                  </View>
                  <View className="flex-1">
                    <View className="flex-row items-center gap-2">
                      <Text className="font-bold text-on-surface" numberOfLines={1}>
                        {h.lokasi}
                      </Text>
                      <View className="px-2 py-0.5 rounded-full bg-primary/10">
                        <Text className="text-primary text-[10px] font-bold">
                          {h.kode}
                        </Text>
                      </View>
                    </View>
                    <Text className="text-on-surface-variant text-xs">
                      {h.unit} · {h.waktu}
                    </Text>
                  </View>
                  <Ionicons name="chevron-forward" size={18} color="#5a6072" />
                </Pressable>
              ))}
            </View>
          </View>
        </ScrollView>
      </AdminScreen>
    </>
  );
}
