import { useState } from "react";
import { Alert, Pressable, ScrollView, Text, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { Stack, useRouter } from "expo-router";
import { Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";
import { FormSelect, type SelectOption } from "@/components/FormSelect";
import { FormField } from "@/components/FormField";
import { PhotoUpload, type PhotoItem } from "@/components/PhotoUpload";
import { useIsTablet } from "@/lib/useIsTablet";

const KEPERLUAN_OPTIONS: SelectOption[] = [
  { value: "tamu", label: "Tamu Karyawan" },
  { value: "vendor", label: "Vendor / Supplier" },
  { value: "kurir", label: "Kurir / Pengiriman" },
  { value: "interview", label: "Interview / Wawancara" },
  { value: "lainnya", label: "Lainnya" },
];

const IDENTITAS_OPTIONS: SelectOption[] = [
  { value: "ktp", label: "KTP" },
  { value: "sim", label: "SIM" },
  { value: "paspor", label: "Paspor" },
  { value: "kartu_pelajar", label: "Kartu Pelajar / Mahasiswa" },
  { value: "lainnya", label: "Lainnya" },
];

interface TamuEntry {
  id: number;
  nama: string;
  instansi: string;
  keperluan: string;
  jamMasuk: string;
  jamKeluar?: string;
}

const RECENT_TAMU: TamuEntry[] = [
  {
    id: 1,
    nama: "Budi Santoso",
    instansi: "PT Maju Jaya",
    keperluan: "Vendor",
    jamMasuk: "09:15",
  },
  {
    id: 2,
    nama: "Siti Nurhaliza",
    instansi: "Pribadi",
    keperluan: "Tamu Karyawan",
    jamMasuk: "08:30",
    jamKeluar: "10:45",
  },
  {
    id: 3,
    nama: "JNE Cabang Pusat",
    instansi: "JNE Express",
    keperluan: "Kurir",
    jamMasuk: "07:50",
    jamKeluar: "08:00",
  },
];

const nowTime = () => {
  const d = new Date();
  return `${String(d.getHours()).padStart(2, "0")}:${String(d.getMinutes()).padStart(2, "0")}`;
};

export default function BukuTamuScreen() {
  const router = useRouter();
  const isTablet = useIsTablet();

  const [nama, setNama] = useState("");
  const [instansi, setInstansi] = useState("");
  const [keperluan, setKeperluan] = useState<string | number | null>(null);
  const [tujuan, setTujuan] = useState("");
  const [nomorIdentitas, setNomorIdentitas] = useState("");
  const [jenisIdentitas, setJenisIdentitas] = useState<string | number | null>(
    "ktp"
  );
  const [jamMasuk, setJamMasuk] = useState(nowTime());
  const [nomorKendaraan, setNomorKendaraan] = useState("");
  const [fotoIdentitas, setFotoIdentitas] = useState<PhotoItem[]>([]);
  const [submitting, setSubmitting] = useState(false);

  const canSubmit =
    nama.trim().length > 0 &&
    !!keperluan &&
    tujuan.trim().length > 0 &&
    nomorIdentitas.trim().length > 0 &&
    !submitting;

  const onSubmit = () => {
    setSubmitting(true);
    setTimeout(() => {
      setSubmitting(false);
      Alert.alert("Berhasil", "Data tamu berhasil dicatat.", [
        { text: "OK", onPress: () => router.back() },
      ]);
    }, 800);
  };

  const headerPad = isTablet ? "px-8" : "px-5";
  const contentPad = isTablet ? 32 : 20;

  const FormSection = (
    <View>
      <Text
        className={`font-bold text-on-surface mb-3 ${isTablet ? "text-xl" : "text-base"}`}
      >
        Data Tamu
      </Text>
      <FormField
        label="Nama Lengkap"
        required
        icon="person-outline"
        value={nama}
        onChangeText={setNama}
        placeholder="Nama tamu"
      />
      <FormField
        label="Asal Instansi / Perusahaan"
        icon="business-outline"
        value={instansi}
        onChangeText={setInstansi}
        placeholder="Nama perusahaan / instansi"
      />
      <FormSelect
        label="Keperluan"
        required
        icon="briefcase-outline"
        value={keperluan}
        options={KEPERLUAN_OPTIONS}
        onChange={setKeperluan}
      />
      <FormField
        label="Tujuan / Bertemu Dengan"
        required
        icon="people-outline"
        value={tujuan}
        onChangeText={setTujuan}
        placeholder="Nama karyawan / unit yang dituju"
      />
      <View className="flex-row gap-3">
        <View className="flex-[1]">
          <FormSelect
            label="Jenis ID"
            required
            icon="card-outline"
            value={jenisIdentitas}
            options={IDENTITAS_OPTIONS}
            onChange={setJenisIdentitas}
          />
        </View>
        <View className="flex-[2]">
          <FormField
            label="Nomor Identitas"
            required
            icon="finger-print-outline"
            value={nomorIdentitas}
            onChangeText={setNomorIdentitas}
            placeholder="Nomor pada kartu"
          />
        </View>
      </View>
      <View className="flex-row gap-3">
        <View className="flex-1">
          <FormField
            label="Jam Masuk"
            required
            icon="time-outline"
            value={jamMasuk}
            onChangeText={setJamMasuk}
            placeholder="HH:MM"
          />
        </View>
        <View className="flex-1">
          <FormField
            label="Nomor Kendaraan"
            icon="car-outline"
            value={nomorKendaraan}
            onChangeText={setNomorKendaraan}
            placeholder="Plat (opsional)"
          />
        </View>
      </View>
      <PhotoUpload
        label="Foto Identitas / Tamu"
        photos={fotoIdentitas}
        onChange={setFotoIdentitas}
        max={2}
        thumbSize={isTablet ? "lg" : "md"}
        hint="Foto KTP / wajah tamu sebagai bukti kunjungan"
      />

      <Pressable
        onPress={onSubmit}
        disabled={!canSubmit}
        className="mt-2 h-12 rounded-xl items-center justify-center flex-row gap-2"
        style={{
          backgroundColor: canSubmit ? "#0a7e3e" : "#c1c6d6",
          opacity: submitting ? 0.7 : 1,
        }}
      >
        <Ionicons
          name={submitting ? "hourglass-outline" : "checkmark-circle"}
          size={18}
          color="#ffffff"
        />
        <Text className="text-white font-bold text-base">
          {submitting ? "Menyimpan..." : "Catat Kunjungan"}
        </Text>
      </Pressable>
    </View>
  );

  const HistorySection = (
    <View>
      <View className="flex-row items-center justify-between mb-3">
        <Text
          className={`font-bold text-on-surface ${isTablet ? "text-xl" : "text-base"}`}
        >
          Riwayat Hari Ini
        </Text>
        <View className="px-3 py-1 rounded-full bg-secondary/10">
          <Text className="text-secondary text-xs font-bold">
            {RECENT_TAMU.length} tamu
          </Text>
        </View>
      </View>
      <View className="gap-3">
        {RECENT_TAMU.map((t) => (
          <View
            key={t.id}
            className="p-4 rounded-2xl border border-outline-variant bg-surface-container-lowest"
          >
            <View className="flex-row items-center gap-3">
              <View className="w-11 h-11 rounded-full bg-secondary/10 items-center justify-center">
                <Ionicons name="person" size={20} color="#0a7e3e" />
              </View>
              <View className="flex-1">
                <Text className="font-bold text-on-surface">{t.nama}</Text>
                <Text className="text-on-surface-variant text-xs">
                  {t.instansi} · {t.keperluan}
                </Text>
              </View>
              <View className="items-end">
                <View className="flex-row items-center gap-1">
                  <Ionicons name="enter-outline" size={14} color="#0a7e3e" />
                  <Text className="text-xs font-semibold text-secondary">
                    {t.jamMasuk}
                  </Text>
                </View>
                {t.jamKeluar ? (
                  <View className="flex-row items-center gap-1 mt-0.5">
                    <Ionicons name="exit-outline" size={14} color="#5a6072" />
                    <Text className="text-xs text-on-surface-variant">
                      {t.jamKeluar}
                    </Text>
                  </View>
                ) : (
                  <Text className="text-xs text-error font-bold mt-0.5">
                    Belum keluar
                  </Text>
                )}
              </View>
            </View>
          </View>
        ))}
      </View>
    </View>
  );

  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <SafeAreaView className="flex-1 bg-background" edges={["top"]}>
        <View
          className={`flex-row items-center gap-3 ${headerPad} h-16 border-b border-surface-variant bg-surface`}
        >
          <Pressable
            onPress={() => router.back()}
            className="w-10 h-10 rounded-full items-center justify-center active:bg-surface-container-high"
          >
            <Ionicons name="arrow-back" size={22} color="#414754" />
          </Pressable>
          <MaterialCommunityIcons
            name="book-open-page-variant"
            size={isTablet ? 28 : 22}
            color="#0a7e3e"
          />
          <Text
            className={`font-bold text-on-surface ${isTablet ? "text-2xl" : "text-lg"}`}
          >
            Buku Tamu
          </Text>
        </View>

        <ScrollView
          contentContainerStyle={{ padding: contentPad, paddingBottom: 60 }}
          keyboardShouldPersistTaps="handled"
        >
          {isTablet ? (
            <View className="flex-row gap-8">
              <View className="flex-1">{FormSection}</View>
              <View className="flex-1">{HistorySection}</View>
            </View>
          ) : (
            <>
              {FormSection}
              <View className="h-6" />
              {HistorySection}
            </>
          )}
        </ScrollView>
      </SafeAreaView>
    </>
  );
}
