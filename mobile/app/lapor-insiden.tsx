import { useState } from "react";
import { Alert, Pressable, ScrollView, Text, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { Stack, useRouter } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { FormSelect, type SelectOption } from "@/components/FormSelect";
import { FormField } from "@/components/FormField";
import { PhotoUpload, type PhotoItem } from "@/components/PhotoUpload";
import { useIsTablet } from "@/lib/useIsTablet";

// Sesuai GuestComplaint::getJenisKeluhanOptions()
const JENIS_KELUHAN: SelectOption[] = [
  { value: "tumpahan", label: "Tumpahan" },
  { value: "kotor", label: "Kotor" },
  { value: "bau", label: "Bau" },
  { value: "rusak", label: "Fasilitas Rusak" },
  { value: "lainnya", label: "Lainnya" },
];

const LOKASI_OPTIONS: SelectOption[] = [
  { value: 1, label: "Toilet Lantai 1 - Gedung A" },
  { value: 2, label: "Lobi Utama" },
  { value: 3, label: "Pantry Lantai 2" },
  { value: 4, label: "Area Parkir" },
];

const PETUGAS_OPTIONS: SelectOption[] = [
  { value: 1, label: "Andi Setiawan" },
  { value: 2, label: "Budi Hartono" },
  { value: 3, label: "Citra Wijaya" },
];

export default function LaporInsidenScreen() {
  const router = useRouter();
  const isTablet = useIsTablet();

  // Sesuai GuestComplaintResource (Filament)
  const [lokasiId, setLokasiId] = useState<number | string | null>(null);
  const [jenisKeluhan, setJenisKeluhan] = useState<number | string | null>(null);
  const [deskripsi, setDeskripsi] = useState("");
  const [namaPelapor, setNamaPelapor] = useState("");
  const [emailPelapor, setEmailPelapor] = useState("");
  const [teleponPelapor, setTeleponPelapor] = useState("");
  const [assignedTo, setAssignedTo] = useState<number | string | null>(null);
  const [fotoKeluhan, setFotoKeluhan] = useState<PhotoItem[]>([]);
  const [submitting, setSubmitting] = useState(false);

  const canSubmit =
    !!lokasiId &&
    !!jenisKeluhan &&
    deskripsi.trim().length > 0 &&
    namaPelapor.trim().length > 0 &&
    teleponPelapor.trim().length > 0 &&
    !submitting;

  const onSubmit = () => {
    setSubmitting(true);
    setTimeout(() => {
      setSubmitting(false);
      Alert.alert("Berhasil", "Laporan insiden / keluhan tamu berhasil dikirim.", [
        { text: "OK", onPress: () => router.back() },
      ]);
    }, 800);
  };

  const headerPad = isTablet ? "px-8" : "px-5";
  const contentPad = isTablet ? 32 : 20;

  const LokasiSection = (
    <View>
      <Text
        className={`font-bold text-on-surface mb-3 ${isTablet ? "text-xl" : "text-base"}`}
      >
        Lokasi & Jenis Keluhan
      </Text>
      <FormSelect
        label="Lokasi"
        required
        icon="location-outline"
        value={lokasiId}
        options={LOKASI_OPTIONS}
        onChange={setLokasiId}
      />
      <FormSelect
        label="Jenis Keluhan"
        required
        icon="alert-circle-outline"
        value={jenisKeluhan}
        options={JENIS_KELUHAN}
        onChange={setJenisKeluhan}
      />
      <FormField
        label="Deskripsi Keluhan"
        required
        icon="document-text-outline"
        value={deskripsi}
        onChangeText={setDeskripsi}
        placeholder="Jelaskan keluhan / insiden secara detail..."
        multiline
        rows={4}
      />
      <PhotoUpload
        label="Foto Keluhan"
        photos={fotoKeluhan}
        onChange={setFotoKeluhan}
        max={3}
        thumbSize={isTablet ? "lg" : "md"}
      />
    </View>
  );

  const PelaporSection = (
    <View>
      <Text
        className={`font-bold text-on-surface mb-3 ${isTablet ? "text-xl" : "text-base"}`}
      >
        Informasi Pelapor
      </Text>
      <FormField
        label="Nama Pelapor"
        required
        icon="person-outline"
        value={namaPelapor}
        onChangeText={setNamaPelapor}
        placeholder="Nama lengkap pelapor"
      />
      <FormField
        label="Email"
        icon="mail-outline"
        value={emailPelapor}
        onChangeText={setEmailPelapor}
        placeholder="email@contoh.com"
        keyboardType="email-address"
      />
      <FormField
        label="Telepon"
        required
        icon="call-outline"
        value={teleponPelapor}
        onChangeText={setTeleponPelapor}
        placeholder="08xx-xxxx-xxxx"
        keyboardType="phone-pad"
      />
      <FormSelect
        label="Tugaskan ke Petugas"
        icon="people-outline"
        value={assignedTo}
        options={PETUGAS_OPTIONS}
        onChange={setAssignedTo}
        placeholder="Otomatis dari jadwal..."
      />
    </View>
  );

  const SubmitButton = (
    <Pressable
      onPress={onSubmit}
      disabled={!canSubmit}
      className="mt-2 h-12 rounded-xl items-center justify-center flex-row gap-2"
      style={{
        backgroundColor: canSubmit ? "#d62828" : "#c1c6d6",
        opacity: submitting ? 0.7 : 1,
      }}
    >
      <Ionicons
        name={submitting ? "hourglass-outline" : "alert-circle"}
        size={18}
        color="#ffffff"
      />
      <Text className="text-white font-bold text-base">
        {submitting ? "Mengirim..." : "Kirim Laporan Insiden"}
      </Text>
    </Pressable>
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
          <Ionicons name="warning" size={isTablet ? 28 : 22} color="#d62828" />
          <Text
            className={`font-bold text-on-surface ${isTablet ? "text-2xl" : "text-lg"}`}
          >
            Lapor Insiden
          </Text>
        </View>

        <ScrollView
          contentContainerStyle={{ padding: contentPad, paddingBottom: 60 }}
          keyboardShouldPersistTaps="handled"
        >
          {isTablet ? (
            <View className="flex-row gap-8">
              <View className="flex-1">{LokasiSection}</View>
              <View className="flex-1">
                {PelaporSection}
                {SubmitButton}
              </View>
            </View>
          ) : (
            <>
              {LokasiSection}
              <View className="h-2" />
              {PelaporSection}
              <View className="h-4" />
              {SubmitButton}
            </>
          )}

          {!canSubmit && !submitting && (
            <Text className="text-xs text-on-surface-variant text-center mt-3">
              Lengkapi semua field wajib (*) untuk mengirim laporan.
            </Text>
          )}
        </ScrollView>
      </SafeAreaView>
    </>
  );
}
