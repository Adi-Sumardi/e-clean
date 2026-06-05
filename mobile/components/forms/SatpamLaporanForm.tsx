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

const SHIFT_OPTIONS: SelectOption[] = [
  { value: "pagi", label: "Pagi (06:00 - 14:00)" },
  { value: "siang", label: "Siang (14:00 - 22:00)" },
  { value: "malam", label: "Malam (22:00 - 06:00)" },
];

const KONDISI_OPTIONS: SelectOption[] = [
  { value: "aman", label: "Aman & Terkendali" },
  { value: "perhatian", label: "Perlu Perhatian" },
  { value: "bahaya", label: "Ada Insiden / Bahaya" },
];

const todayStr = () => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
};

const nowTime = () => {
  const d = new Date();
  return `${String(d.getHours()).padStart(2, "0")}:${String(d.getMinutes()).padStart(2, "0")}`;
};

export function SatpamLaporanForm() {
  const isTablet = useIsTablet();
  const [posId, setPosId] = useState<number | string | null>(null);
  const [jadwalId, setJadwalId] = useState<number | string | null>(null);
  const [shift, setShift] = useState<number | string | null>("pagi");
  const [kondisi, setKondisi] = useState<number | string | null>("aman");
  const [tanggal, setTanggal] = useState(todayStr());
  const [jamPatroli, setJamPatroli] = useState(nowTime());
  const [catatan, setCatatan] = useState("");
  const [tindakLanjut, setTindakLanjut] = useState("");
  const [foto, setFoto] = useState<PhotoItem[]>([]);

  const lokasiQuery = useLokasi();
  const jadwalQuery = useFieldJadwalToday("satpam");
  const createLaporan = useCreateFieldLaporan("satpam");
  const submitting = createLaporan.isPending;

  const posOptions = useMemo<SelectOption[]>(
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
      setPosId(j.lokasi.id);
    } else {
      setPosId(null);
    }
  };

  const canSubmit =
    !!posId &&
    !!jadwalId &&
    !!shift &&
    !!kondisi &&
    tanggal.length > 0 &&
    jamPatroli.length > 0 &&
    catatan.trim().length > 0 &&
    foto.length > 0 &&
    !submitting;

  const onSubmit = () => {
    createLaporan.mutate(
      {
        fields: {
          jadwal_id: jadwalId ? Number(jadwalId) : undefined,
          lokasi_id: Number(posId),
          tanggal,
          jam_mulai: jamPatroli,
          kondisi: String(kondisi),
          temuan: catatan.trim(),
          tindakan: tindakLanjut.trim() || undefined,
          status: "submitted",
        },
        photos: { foto: foto.map((p) => p.uri) },
      },
      {
        onSuccess: (result) => {
          setCatatan("");
          setTindakLanjut("");
          setJadwalId(null);
          setPosId(null);
          setFoto([]);
          Alert.alert(
            "Berhasil",
            result === "queued"
              ? "Tidak ada koneksi. Laporan tersimpan dan akan dikirim otomatis saat online."
              : "Laporan patroli berhasil dikirim."
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
        Informasi Patroli
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
        label="Pos / Lokasi Patroli"
        required
        icon="location-outline"
        value={posId}
        options={posOptions}
        onChange={setPosId}
        disabled={true}
        placeholder={
          jadwalId ? "Otomatis dari jadwal" : "Pilih jadwal terlebih dahulu"
        }
      />
      <FormSelect
        label="Shift"
        required
        icon="time-outline"
        value={shift}
        options={SHIFT_OPTIONS}
        onChange={setShift}
      />
      <View className="flex-row gap-3">
        <View className="flex-1">
          <FormField
            label="Tanggal"
            required
            icon="calendar-clear-outline"
            value={tanggal}
            onChangeText={setTanggal}
            placeholder="YYYY-MM-DD"
          />
        </View>
        <View className="flex-1">
          <FormField
            label="Jam Patroli"
            required
            icon="time-outline"
            value={jamPatroli}
            onChangeText={setJamPatroli}
            placeholder="HH:MM"
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
        Hasil Pemeriksaan
      </Text>
      <FormSelect
        label="Kondisi Lokasi"
        required
        icon="shield-checkmark-outline"
        value={kondisi}
        options={KONDISI_OPTIONS}
        onChange={setKondisi}
      />
      <FormField
        label="Catatan Patroli"
        required
        icon="document-text-outline"
        value={catatan}
        onChangeText={setCatatan}
        placeholder="Catat hasil pemeriksaan di lokasi..."
        multiline
        rows={4}
      />
      {kondisi !== "aman" && (
        <FormField
          label="Tindak Lanjut"
          icon="warning-outline"
          value={tindakLanjut}
          onChangeText={setTindakLanjut}
          placeholder="Apa tindak lanjut yang dilakukan?"
          multiline
          rows={3}
        />
      )}
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
        label="Foto Pos / Lokasi"
        required
        photos={foto}
        onChange={setFoto}
        max={5}
        thumbSize={isTablet ? "lg" : "md"}
        hint="Foto kondisi lokasi sebagai bukti patroli"
      />
    </View>
  );

  const SubmitButton = (
    <Pressable
      onPress={onSubmit}
      disabled={!canSubmit}
      className="mt-2 h-12 rounded-xl items-center justify-center flex-row gap-2"
      style={{
        backgroundColor: canSubmit ? "#005bbf" : "#c1c6d6",
        opacity: submitting ? 0.7 : 1,
      }}
    >
      <Ionicons
        name={submitting ? "hourglass-outline" : "shield-checkmark"}
        size={18}
        color="#ffffff"
      />
      <Text className="text-white font-bold text-base">
        {submitting ? "Mengirim..." : "Kirim Laporan Patroli"}
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
            name="shield-account"
            size={isTablet ? 28 : 22}
            color="#005bbf"
          />
          <Text
            className={`font-bold text-on-surface ${isTablet ? "text-2xl" : "text-lg"}`}
          >
            Laporan Patroli
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
            Lengkapi semua field wajib (*) dan unggah minimal 1 foto.
          </Text>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}
