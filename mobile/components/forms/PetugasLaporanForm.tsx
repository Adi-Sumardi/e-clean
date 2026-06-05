import { useMemo, useState } from "react";
import { Alert, Pressable, ScrollView, Text, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { Ionicons } from "@expo/vector-icons";
import { FormSelect, type SelectOption } from "@/components/FormSelect";
import { FormField } from "@/components/FormField";
import { FormTimeField } from "@/components/FormTimeField";
import { PhotoUpload, type PhotoItem } from "@/components/PhotoUpload";
import { useIsTablet } from "@/lib/useIsTablet";
import { useLokasi, useJadwalToday, useCreateActivityReport } from "@/lib/hooks";
import { ApiError } from "@/lib/api";

const todayStr = () => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
};

const nowTime = () => {
  const d = new Date();
  return `${String(d.getHours()).padStart(2, "0")}:${String(d.getMinutes()).padStart(2, "0")}`;
};

export function PetugasLaporanForm() {
  const isTablet = useIsTablet();
  const [lokasiId, setLokasiId] = useState<number | string | null>(null);
  const [jadwalId, setJadwalId] = useState<number | string | null>(null);
  const [tanggal, setTanggal] = useState(todayStr());
  const [jamMulai, setJamMulai] = useState(nowTime());
  const [jamSelesai, setJamSelesai] = useState("");
  const [kegiatan, setKegiatan] = useState("");
  const [catatanPetugas, setCatatanPetugas] = useState("");
  const [fotoSebelum, setFotoSebelum] = useState<PhotoItem[]>([]);
  const [fotoSesudah, setFotoSesudah] = useState<PhotoItem[]>([]);

  const lokasiQuery = useLokasi();
  const jadwalQuery = useJadwalToday();
  const createReport = useCreateActivityReport();
  const submitting = createReport.isPending;

  const lokasiOptions = useMemo<SelectOption[]>(
    () =>
      (lokasiQuery.data ?? []).map((l) => ({
        value: l.id,
        label: l.lantai ? `${l.nama_lokasi} - ${l.lantai}` : l.nama_lokasi,
      })),
    [lokasiQuery.data]
  );

  const jadwalOptions = useMemo<SelectOption[]>(
    () =>
      (jadwalQuery.data ?? []).map((j) => ({
        value: j.id,
        label: `${j.shift} · ${j.jam_mulai}${
          j.lokasi ? ` · ${j.lokasi.nama_lokasi}` : ""
        }`,
      })),
    [jadwalQuery.data]
  );

  // Selecting a schedule auto-fills the location it belongs to.
  const onJadwalChange = (val: number | string) => {
    setJadwalId(val);
    const j = (jadwalQuery.data ?? []).find((x) => x.id === val);
    if (j?.lokasi?.id) setLokasiId(j.lokasi.id);
  };

  const canSubmit =
    !!lokasiId &&
    !!jadwalId &&
    tanggal.length > 0 &&
    jamMulai.length > 0 &&
    jamSelesai.length > 0 &&
    kegiatan.trim().length >= 10 &&
    fotoSebelum.length > 0 &&
    fotoSesudah.length > 0 &&
    !submitting;

  const onSubmit = () => {
    createReport.mutate(
      {
        jadwal_id: Number(jadwalId),
        lokasi_id: Number(lokasiId),
        tanggal,
        jam_mulai: jamMulai,
        jam_selesai: jamSelesai,
        kegiatan: kegiatan.trim(),
        catatan_petugas: catatanPetugas.trim() || undefined,
        foto_sebelum: fotoSebelum.map((p) => p.uri),
        foto_sesudah: fotoSesudah.map((p) => p.uri),
        status: "submitted",
      },
      {
        onSuccess: (result) => {
          setKegiatan("");
          setCatatanPetugas("");
          setJamSelesai("");
          setJadwalId(null);
          setFotoSebelum([]);
          setFotoSesudah([]);
          Alert.alert(
            "Berhasil",
            result === "queued"
              ? "Tidak ada koneksi. Laporan tersimpan dan akan dikirim otomatis saat online."
              : "Laporan kegiatan berhasil dikirim."
          );
        },
        onError: (err) => {
          const msg =
            err instanceof ApiError && err.errors
              ? Object.values(err.errors).flat().join("\n")
              : err instanceof Error
                ? err.message
                : "Gagal mengirim laporan.";
          Alert.alert("Gagal", msg);
        },
      }
    );
  };

  const headerPad = isTablet ? "px-8" : "px-5";
  const contentPad = isTablet ? 32 : 20;

  const InfoSection = (
    <View>
      <Text
        className={`font-bold text-on-surface mb-3 ${isTablet ? "text-xl" : "text-base"}`}
      >
        Informasi Kegiatan
      </Text>
      <FormSelect
        label="Jadwal Terkait"
        required
        icon="calendar-outline"
        value={jadwalId}
        options={jadwalOptions}
        onChange={onJadwalChange}
        placeholder={
          jadwalQuery.isLoading
            ? "Memuat jadwal..."
            : jadwalOptions.length === 0
              ? "Tidak ada jadwal hari ini"
              : "Pilih jadwal..."
        }
      />
      <FormSelect
        label="Lokasi"
        required
        icon="location-outline"
        value={lokasiId}
        options={lokasiOptions}
        onChange={setLokasiId}
        disabled={!!jadwalId}
        placeholder={
          jadwalId ? "Otomatis dari jadwal" : "Pilih jadwal dulu"
        }
      />
      <FormField
        label="Tanggal"
        required
        icon="calendar-clear-outline"
        value={tanggal}
        onChangeText={setTanggal}
        placeholder="YYYY-MM-DD"
        hint="Format: YYYY-MM-DD"
      />
      <View className="flex-row gap-3">
        <View className="flex-1">
          <FormTimeField
            label="Jam Mulai"
            required
            value={jamMulai}
            onChange={setJamMulai}
          />
        </View>
        <View className="flex-1">
          <FormTimeField
            label="Jam Selesai"
            required
            value={jamSelesai}
            onChange={setJamSelesai}
          />
        </View>
      </View>
    </View>
  );

  const DetailSection = (
    <View>
      <Text
        className={`font-bold text-on-surface mb-3 ${isTablet ? "text-xl" : "text-base"}`}
      >
        Detail Kegiatan
      </Text>
      <FormField
        label="Deskripsi Kegiatan"
        required
        icon="document-text-outline"
        value={kegiatan}
        onChangeText={setKegiatan}
        placeholder="Jelaskan kegiatan yang dilakukan..."
        multiline
        rows={4}
      />
      <FormField
        label="Catatan Petugas"
        icon="create-outline"
        value={catatanPetugas}
        onChangeText={setCatatanPetugas}
        placeholder="Catatan tambahan (opsional)..."
        multiline
        rows={3}
      />
    </View>
  );

  const PhotoSection = (
    <View>
      <Text
        className={`font-bold text-on-surface mb-3 ${isTablet ? "text-xl" : "text-base"}`}
      >
        Bukti Foto
      </Text>
      <PhotoUpload
        label="Foto Sebelum Dibersihkan"
        required
        photos={fotoSebelum}
        onChange={setFotoSebelum}
        max={3}
        thumbSize={isTablet ? "lg" : "md"}
      />
      <PhotoUpload
        label="Foto Sesudah Dibersihkan"
        required
        photos={fotoSesudah}
        onChange={setFotoSesudah}
        max={3}
        thumbSize={isTablet ? "lg" : "md"}
      />
    </View>
  );

  const SubmitButton = (
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
        name={submitting ? "hourglass-outline" : "send"}
        size={18}
        color="#ffffff"
      />
      <Text className="text-white font-bold text-base">
        {submitting ? "Mengirim..." : "Kirim Laporan"}
      </Text>
    </Pressable>
  );

  return (
    <SafeAreaView className="flex-1 bg-background" edges={["top"]}>
      <View
        className={`${headerPad} h-16 justify-center border-b border-surface-variant bg-surface`}
      >
        <View className="flex-row items-center gap-3">
          <Ionicons name="camera" size={isTablet ? 28 : 22} color="#0a7e3e" />
          <Text
            className={`font-bold text-on-surface ${isTablet ? "text-2xl" : "text-lg"}`}
          >
            Laporan Kegiatan Kebersihan
          </Text>
        </View>
      </View>
      <ScrollView
        contentContainerStyle={{ padding: contentPad, paddingBottom: 130 }}
        keyboardShouldPersistTaps="handled"
      >
        {isTablet ? (
          <View className="flex-row gap-8">
            <View className="flex-1">
              {InfoSection}
              <View className="h-4" />
              {DetailSection}
            </View>
            <View className="flex-1">
              {PhotoSection}
              {SubmitButton}
            </View>
          </View>
        ) : (
          <>
            {InfoSection}
            <View className="h-2" />
            {DetailSection}
            <View className="h-2" />
            {PhotoSection}
            <View className="h-4" />
            {SubmitButton}
          </>
        )}

        {!canSubmit && !submitting && (
          <Text className="text-xs text-on-surface-variant text-center mt-3">
            Lengkapi semua field wajib (*) dan unggah foto sebelum/sesudah.
          </Text>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}
