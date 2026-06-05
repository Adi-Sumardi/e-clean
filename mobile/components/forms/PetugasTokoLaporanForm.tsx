import { useEffect, useMemo, useState } from "react";
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
  { value: "pagi", label: "Pagi (08:00 - 16:00)" },
  { value: "sore", label: "Sore (16:00 - 22:00)" },
];

const KONDISI_OPTIONS: SelectOption[] = [
  { value: "baik", label: "Baik & Terkendali" },
  { value: "biasa", label: "Normal" },
  { value: "perlu_perhatian", label: "Perlu Perhatian" },
];

const todayStr = () => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
};

const nowTime = () => {
  const d = new Date();
  return `${String(d.getHours()).padStart(2, "0")}:${String(d.getMinutes()).padStart(2, "0")}`;
};

export function PetugasTokoLaporanForm({ preselectedLokasiId }: { preselectedLokasiId?: number }) {
  const isTablet = useIsTablet();
  const [lokasiId, setLokasiId] = useState<number | string | null>(null);
  const [jadwalId, setJadwalId] = useState<number | string | null>(null);
  const [shift, setShift] = useState<number | string | null>("pagi");
  const [tanggal, setTanggal] = useState(todayStr());
  const [jamBuka, setJamBuka] = useState("08:00");
  const [jamTutup, setJamTutup] = useState(nowTime());
  const [jumlahTransaksi, setJumlahTransaksi] = useState("");
  const [totalOmset, setTotalOmset] = useState("");
  const [totalRetur, setTotalRetur] = useState("");
  const [saldoKasir, setSaldoKasir] = useState("");
  const [kondisi, setKondisi] = useState<number | string | null>("baik");
  const [catatan, setCatatan] = useState("");
  const [fotoKasir, setFotoKasir] = useState<PhotoItem[]>([]);
  const [fotoToko, setFotoToko] = useState<PhotoItem[]>([]);

  const lokasiQuery = useLokasi();
  const jadwalQuery = useFieldJadwalToday("toko");
  const createLaporan = useCreateFieldLaporan("toko");
  const submitting = createLaporan.isPending;

  useEffect(() => {
    if (preselectedLokasiId && jadwalQuery.data) {
      const schedule = (jadwalQuery.data ?? []).find(
        (j) => j.lokasi?.id === preselectedLokasiId
      );
      if (schedule) {
        setJadwalId(schedule.id);
        setLokasiId(schedule.lokasi?.id ?? null);
      }
    }
  }, [preselectedLokasiId, jadwalQuery.data]);

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

  const onJadwalChange = (val: number | string) => {
    setJadwalId(val);
    const j = (jadwalQuery.data ?? []).find((x) => x.id === val);
    if (j?.lokasi?.id) {
      setLokasiId(j.lokasi.id);
    } else {
      setLokasiId(null);
    }
  };

  const canSubmit =
    !!lokasiId &&
    !!jadwalId &&
    !!shift &&
    tanggal.length > 0 &&
    jamBuka.length > 0 &&
    jamTutup.length > 0 &&
    jumlahTransaksi.trim().length > 0 &&
    totalOmset.trim().length > 0 &&
    saldoKasir.trim().length > 0 &&
    fotoKasir.length > 0 &&
    !submitting;

  const onSubmit = () => {
    const kondisiLabel =
      KONDISI_OPTIONS.find((o) => o.value === kondisi)?.label ?? String(kondisi);
    // The store schema keeps the daily checklist + a free-text stock note; we
    // fold the cashier summary into catatan_stok and the kasir/store photos
    // into the single `foto` array (max 5).
    const ringkasan = [
      `Shift: ${shift}`,
      `Transaksi: ${jumlahTransaksi}`,
      `Omset: ${totalOmset}`,
      totalRetur.trim() ? `Retur: ${totalRetur}` : null,
      `Saldo kasir: ${saldoKasir}`,
      `Kondisi: ${kondisiLabel}`,
      catatan.trim() ? `Catatan: ${catatan.trim()}` : null,
    ]
      .filter(Boolean)
      .join("; ");

    createLaporan.mutate(
      {
        fields: {
          jadwal_id: jadwalId ? Number(jadwalId) : undefined,
          lokasi_id: Number(lokasiId),
          tanggal,
          jam_mulai: jamBuka,
          jam_selesai: jamTutup,
          catatan_stok: ringkasan,
          catatan_petugas: catatan.trim() || undefined,
          status: "submitted",
        },
        photos: {
          foto: [...fotoKasir, ...fotoToko].slice(0, 5).map((p) => p.uri),
        },
      },
      {
        onSuccess: (result) => {
          setJumlahTransaksi("");
          setTotalOmset("");
          setTotalRetur("");
          setSaldoKasir("");
          setCatatan("");
          setJadwalId(null);
          setLokasiId(null);
          setFotoKasir([]);
          setFotoToko([]);
          Alert.alert(
            "Berhasil",
            result === "queued"
              ? "Tidak ada koneksi. Laporan tersimpan dan akan dikirim otomatis saat online."
              : "Laporan harian toko berhasil dikirim."
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

  const ShiftSection = (
    <View>
      <Text
        className={`font-bold text-on-surface mb-3 ${isTablet ? "text-xl" : "text-base"}`}
      >
        Informasi Shift
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
        label="Toko / Lokasi"
        required
        icon="storefront-outline"
        value={lokasiId}
        options={lokasiOptions}
        onChange={setLokasiId}
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
            label="Jam Buka"
            required
            icon="enter-outline"
            value={jamBuka}
            onChangeText={setJamBuka}
            placeholder="HH:MM"
          />
        </View>
        <View className="flex-1">
          <FormField
            label="Jam Tutup"
            required
            icon="exit-outline"
            value={jamTutup}
            onChangeText={setJamTutup}
            placeholder="HH:MM"
          />
        </View>
      </View>
    </View>
  );

  const KasirSection = (
    <View>
      <Text
        className={`font-bold text-on-surface mb-3 ${isTablet ? "text-xl" : "text-base"}`}
      >
        Ringkasan Kasir
      </Text>
      <View className="flex-row gap-3">
        <View className="flex-1">
          <FormField
            label="Jumlah Transaksi"
            required
            icon="receipt-outline"
            value={jumlahTransaksi}
            onChangeText={setJumlahTransaksi}
            placeholder="0"
            keyboardType="numeric"
          />
        </View>
        <View className="flex-1">
          <FormField
            label="Total Retur"
            icon="trending-down-outline"
            value={totalRetur}
            onChangeText={setTotalRetur}
            placeholder="Rp 0"
            keyboardType="numeric"
          />
        </View>
      </View>
      <FormField
        label="Total Omset"
        required
        icon="trending-up-outline"
        value={totalOmset}
        onChangeText={setTotalOmset}
        placeholder="Rp 0"
        keyboardType="numeric"
      />
      <FormField
        label="Saldo Kasir Akhir"
        required
        icon="wallet-outline"
        value={saldoKasir}
        onChangeText={setSaldoKasir}
        placeholder="Rp 0"
        keyboardType="numeric"
        hint="Total uang di dalam kasir saat tutup shift"
      />
    </View>
  );

  const KondisiSection = (
    <View>
      <Text
        className={`font-bold text-on-surface mb-3 ${isTablet ? "text-xl" : "text-base"}`}
      >
        Kondisi & Foto
      </Text>
      <FormSelect
        label="Kondisi Toko"
        required
        icon="storefront-outline"
        value={kondisi}
        options={KONDISI_OPTIONS}
        onChange={setKondisi}
      />
      <FormField
        label="Catatan Akhir Shift"
        icon="create-outline"
        value={catatan}
        onChangeText={setCatatan}
        placeholder="Catatan untuk shift berikutnya (opsional)..."
        multiline
        rows={3}
      />
      <PhotoUpload
        label="Foto Tutup Kasir"
        required
        photos={fotoKasir}
        onChange={setFotoKasir}
        max={2}
        thumbSize={isTablet ? "lg" : "md"}
        hint="Foto saldo kasir / struk total"
      />
      <PhotoUpload
        label="Foto Kondisi Toko"
        photos={fotoToko}
        onChange={setFotoToko}
        max={3}
        thumbSize={isTablet ? "lg" : "md"}
        hint="Foto rak / display saat akhir shift (opsional)"
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
        name={submitting ? "hourglass-outline" : "checkmark-done"}
        size={18}
        color="#ffffff"
      />
      <Text className="text-white font-bold text-base">
        {submitting ? "Mengirim..." : "Kirim Laporan Harian"}
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
            name="storefront-outline"
            size={isTablet ? 28 : 22}
            color="#7e5a17"
          />
          <Text
            className={`font-bold text-on-surface ${isTablet ? "text-2xl" : "text-lg"}`}
          >
            Laporan Harian Toko
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
              {ShiftSection}
              <View className="h-4" />
              {KasirSection}
            </View>
            <View className="flex-1">
              {KondisiSection}
              {SubmitButton}
            </View>
          </View>
        ) : (
          <>
            {ShiftSection}
            <View className="h-2" />
            {KasirSection}
            <View className="h-2" />
            {KondisiSection}
            <View className="h-4" />
            {SubmitButton}
          </>
        )}

        {!canSubmit && !submitting && (
          <Text className="text-xs text-on-surface-variant text-center mt-3">
            Lengkapi semua field wajib (*) dan foto tutup kasir.
          </Text>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}
