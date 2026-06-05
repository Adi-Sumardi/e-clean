import { useMemo, useState } from "react";
import { Alert, Pressable, ScrollView, Text, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";
import { FormSelect, type SelectOption } from "@/components/FormSelect";
import { FormField } from "@/components/FormField";
import { PhotoUpload, type PhotoItem } from "@/components/PhotoUpload";
import { useIsTablet } from "@/lib/useIsTablet";
import { useLokasi, useCreateFieldLaporan, useFieldJadwalToday } from "@/lib/hooks";
import { ApiError } from "@/lib/api";

const JENIS_TUGAS: SelectOption[] = [
  { value: "pembersihan", label: "Pembersihan Rutin" },
  { value: "setup_rapat", label: "Setup Ruang Rapat" },
  { value: "refill_pantry", label: "Refill Pantry" },
  { value: "antar_dokumen", label: "Antar Dokumen / Paket" },
  { value: "permintaan", label: "Permintaan Karyawan" },
  { value: "lainnya", label: "Lainnya" },
];

const todayStr = () => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
};

const nowTime = () => {
  const d = new Date();
  return `${String(d.getHours()).padStart(2, "0")}:${String(d.getMinutes()).padStart(2, "0")}`;
};

export function OfficeBoyLaporanForm() {
  const isTablet = useIsTablet();
  const [areaId, setAreaId] = useState<number | string | null>(null);
  const [jadwalId, setJadwalId] = useState<number | string | null>(null);
  const [jenisTugas, setJenisTugas] = useState<number | string | null>(null);
  const [pemohon, setPemohon] = useState("");
  const [tanggal, setTanggal] = useState(todayStr());
  const [jamMulai, setJamMulai] = useState(nowTime());
  const [jamSelesai, setJamSelesai] = useState("");
  const [deskripsi, setDeskripsi] = useState("");
  const [fotoSebelum, setFotoSebelum] = useState<PhotoItem[]>([]);
  const [fotoSesudah, setFotoSesudah] = useState<PhotoItem[]>([]);

  const lokasiQuery = useLokasi();
  const jadwalQuery = useFieldJadwalToday("ob");
  const createLaporan = useCreateFieldLaporan("ob");
  const submitting = createLaporan.isPending;

  const areaOptions = useMemo<SelectOption[]>(
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

  const onJadwalChange = (val: number | string) => {
    setJadwalId(val);
    const j = (jadwalQuery.data ?? []).find((x) => x.id === val);
    if (j?.lokasi?.id) {
      setAreaId(j.lokasi.id);
    } else {
      setAreaId(null);
    }
  };

  const isRequest = jenisTugas === "permintaan" || jenisTugas === "antar_dokumen";

  const canSubmit =
    !!areaId &&
    !!jadwalId &&
    !!jenisTugas &&
    tanggal.length > 0 &&
    jamMulai.length > 0 &&
    jamSelesai.length > 0 &&
    deskripsi.trim().length > 0 &&
    fotoSesudah.length > 0 &&
    !submitting;

  const onSubmit = () => {
    const jenisLabel =
      JENIS_TUGAS.find((o) => o.value === jenisTugas)?.label ??
      String(jenisTugas);
    createLaporan.mutate(
      {
        fields: {
          jadwal_id: jadwalId ? Number(jadwalId) : undefined,
          lokasi_id: Number(areaId),
          tanggal,
          jam_mulai: jamMulai,
          jam_selesai: jamSelesai,
          jenis_pekerjaan: jenisLabel,
          uraian: deskripsi.trim(),
          catatan_petugas: pemohon.trim() ? `Pemohon: ${pemohon.trim()}` : undefined,
          status: "submitted",
        },
        photos: {
          foto_sebelum: fotoSebelum.map((p) => p.uri),
          foto_sesudah: fotoSesudah.map((p) => p.uri),
        },
      },
      {
        onSuccess: (result) => {
          setDeskripsi("");
          setPemohon("");
          setJamSelesai("");
          setJadwalId(null);
          setAreaId(null);
          setFotoSebelum([]);
          setFotoSesudah([]);
          Alert.alert(
            "Berhasil",
            result === "queued"
              ? "Tidak ada koneksi. Laporan tersimpan dan akan dikirim otomatis saat online."
              : "Laporan tugas berhasil dikirim."
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
        Informasi Tugas
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
        label="Area / Ruangan"
        required
        icon="home-outline"
        value={areaId}
        options={areaOptions}
        onChange={setAreaId}
        disabled={true}
        placeholder={
          jadwalId ? "Otomatis dari jadwal" : "Pilih jadwal terlebih dahulu"
        }
      />
      <FormSelect
        label="Jenis Tugas"
        required
        icon="briefcase-outline"
        value={jenisTugas}
        options={JENIS_TUGAS}
        onChange={setJenisTugas}
      />
      {isRequest && (
        <FormField
          label="Pemohon / Karyawan"
          icon="person-outline"
          value={pemohon}
          onChangeText={setPemohon}
          placeholder="Nama karyawan yang meminta"
        />
      )}
      <FormField
        label="Tanggal"
        required
        icon="calendar-clear-outline"
        value={tanggal}
        onChangeText={setTanggal}
        placeholder="YYYY-MM-DD"
      />
      <View className="flex-row gap-3">
        <View className="flex-1">
          <FormField
            label="Jam Mulai"
            required
            icon="time-outline"
            value={jamMulai}
            onChangeText={setJamMulai}
            placeholder="HH:MM"
          />
        </View>
        <View className="flex-1">
          <FormField
            label="Jam Selesai"
            required
            icon="time-outline"
            value={jamSelesai}
            onChangeText={setJamSelesai}
            placeholder="HH:MM"
          />
        </View>
      </View>
      <FormField
        label="Deskripsi Tugas"
        required
        icon="document-text-outline"
        value={deskripsi}
        onChangeText={setDeskripsi}
        placeholder="Jelaskan tugas yang dikerjakan..."
        multiline
        rows={4}
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
        label="Foto Sebelum (Opsional)"
        photos={fotoSebelum}
        onChange={setFotoSebelum}
        max={3}
        thumbSize={isTablet ? "lg" : "md"}
      />
      <PhotoUpload
        label="Foto Sesudah / Hasil"
        required
        photos={fotoSesudah}
        onChange={setFotoSesudah}
        max={3}
        thumbSize={isTablet ? "lg" : "md"}
        hint="Foto kondisi setelah tugas diselesaikan"
      />
    </View>
  );

  const SubmitButton = (
    <Pressable
      onPress={onSubmit}
      disabled={!canSubmit}
      className="mt-2 h-12 rounded-xl items-center justify-center flex-row gap-2"
      style={{
        backgroundColor: canSubmit ? "#7e5a17" : "#c1c6d6",
        opacity: submitting ? 0.7 : 1,
      }}
    >
      <Ionicons
        name={submitting ? "hourglass-outline" : "send"}
        size={18}
        color="#ffffff"
      />
      <Text className="text-white font-bold text-base">
        {submitting ? "Mengirim..." : "Kirim Laporan Tugas"}
      </Text>
    </Pressable>
  );

  return (
    <SafeAreaView className="flex-1 bg-background" edges={["top"]}>
      <View
        className={`${headerPad} h-16 justify-center border-b border-surface-variant bg-surface`}
      >
        <View className="flex-row items-center gap-3">
          <MaterialCommunityIcons
            name="coffee-outline"
            size={isTablet ? 28 : 22}
            color="#7e5a17"
          />
          <Text
            className={`font-bold text-on-surface ${isTablet ? "text-2xl" : "text-lg"}`}
          >
            Laporan Tugas Office Boy
          </Text>
        </View>
      </View>
      <ScrollView
        contentContainerStyle={{ padding: contentPad, paddingBottom: 130 }}
        keyboardShouldPersistTaps="handled"
      >
        {isTablet ? (
          <View className="flex-row gap-8">
            <View className="flex-1">{InfoSection}</View>
            <View className="flex-1">
              {PhotoSection}
              {SubmitButton}
            </View>
          </View>
        ) : (
          <>
            {InfoSection}
            <View className="h-2" />
            {PhotoSection}
            <View className="h-4" />
            {SubmitButton}
          </>
        )}

        {!canSubmit && !submitting && (
          <Text className="text-xs text-on-surface-variant text-center mt-3">
            Lengkapi semua field wajib (*) dan unggah foto hasil.
          </Text>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}
