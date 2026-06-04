import { useMemo, useState } from "react";
import { Alert, Pressable, ScrollView, Text, TextInput, View } from "react-native";
import { Stack, useRouter } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { AdminScreen } from "@/components/admin/AdminScreen";
import { FormSelect, type SelectOption } from "@/components/FormSelect";
import { useIsTablet } from "@/lib/useIsTablet";
import { useUsers, useCreatePenilaian } from "@/lib/hooks";
import { ApiError } from "@/lib/api";

const KATEGORI_OPTIONS: SelectOption[] = [
  { value: "kinerja", label: "Kinerja Umum" },
  { value: "kerapihan", label: "Kerapihan" },
  { value: "kecepatan", label: "Kecepatan" },
  { value: "kedisiplinan", label: "Kedisiplinan" },
  { value: "komunikasi", label: "Komunikasi" },
];

const PERIODE_OPTIONS: SelectOption[] = [
  { value: "harian", label: "Harian" },
  { value: "mingguan", label: "Mingguan" },
  { value: "bulanan", label: "Bulanan" },
];

const RATING_LABELS = [
  "",
  "Sangat Kurang",
  "Kurang",
  "Cukup",
  "Baik",
  "Sangat Baik",
];

const RATING_COLORS = ["", "#d62828", "#d62828", "#e08a14", "#0891b2", "#0a7e3e"];

export default function BeriPenilaianScreen() {
  const router = useRouter();
  const isTablet = useIsTablet();

  const [petugas, setPetugas] = useState<number | string | null>(null);
  const [kategori, setKategori] = useState<number | string | null>("kinerja");
  const [periode, setPeriode] = useState<number | string | null>("bulanan");
  const [rating, setRating] = useState(0);
  const [catatan, setCatatan] = useState("");

  const { data: petugasList, isLoading: loadingPetugas } = useUsers({
    role: "petugas",
    active_only: true,
  });
  const createPenilaian = useCreatePenilaian();
  const submitting = createPenilaian.isPending;

  const petugasOptions = useMemo<SelectOption[]>(
    () =>
      (petugasList ?? []).map((u) => ({ value: u.id, label: u.name })),
    [petugasList]
  );

  const canSubmit = !!petugas && rating > 0 && !submitting;

  const onSubmit = () => {
    const now = new Date();
    // The single 1-5 star rating maps to all four 0-100 sub-scores (rating*20).
    const skor = rating * 20;
    const kategoriLabel =
      KATEGORI_OPTIONS.find((o) => o.value === kategori)?.label ?? "";
    createPenilaian.mutate(
      {
        petugas_id: Number(petugas),
        periode_bulan: now.getMonth() + 1,
        periode_tahun: now.getFullYear(),
        skor_kehadiran: skor,
        skor_kualitas: skor,
        skor_ketepatan_waktu: skor,
        skor_kebersihan: skor,
        catatan: [kategoriLabel ? `[${kategoriLabel}]` : "", catatan.trim()]
          .filter(Boolean)
          .join(" ") || undefined,
      },
      {
        onSuccess: () => {
          Alert.alert("Berhasil", "Penilaian telah disimpan.", [
            { text: "OK", onPress: () => router.back() },
          ]);
        },
        onError: (err) => {
          const msg =
            err instanceof ApiError && err.errors
              ? Object.values(err.errors).flat().join("\n")
              : err instanceof Error
                ? err.message
                : "Gagal menyimpan penilaian.";
          Alert.alert("Gagal", msg);
        },
      }
    );
  };

  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <AdminScreen
        title="Beri Penilaian"
        subtitle="Form penilaian petugas"
        icon="star-outline"
        color="#e08a14"
        backHref={null}
      >
        <ScrollView
          contentContainerStyle={{ padding: isTablet ? 32 : 20, paddingBottom: 60 }}
          keyboardShouldPersistTaps="handled"
        >
          <View className={isTablet ? "flex-row gap-6" : ""}>
            {/* LEFT — Form */}
            <View className={isTablet ? "flex-1" : ""}>
              <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-5">
                <View className="flex-row items-center gap-2 mb-3">
                  <Ionicons name="person-outline" size={18} color="#7e5a17" />
                  <Text className="font-bold text-on-surface">
                    Informasi Penilaian
                  </Text>
                </View>
                <FormSelect
                  label="Petugas"
                  required
                  icon="person-circle-outline"
                  value={petugas}
                  options={petugasOptions}
                  onChange={setPetugas}
                  placeholder={
                    loadingPetugas ? "Memuat petugas..." : "Pilih petugas..."
                  }
                />
                <FormSelect
                  label="Kategori"
                  required
                  icon="grid-outline"
                  value={kategori}
                  options={KATEGORI_OPTIONS}
                  onChange={setKategori}
                />
                <FormSelect
                  label="Periode"
                  required
                  icon="calendar-outline"
                  value={periode}
                  options={PERIODE_OPTIONS}
                  onChange={setPeriode}
                />
              </View>

              {/* Catatan */}
              <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-5">
                <View className="flex-row items-center gap-2 mb-2">
                  <Ionicons
                    name="chatbubble-outline"
                    size={18}
                    color="#005bbf"
                  />
                  <Text className="font-bold text-on-surface">
                    Catatan / Feedback
                  </Text>
                </View>
                <Text className="text-on-surface-variant text-xs mb-2">
                  Tuliskan feedback konstruktif untuk petugas
                </Text>
                <TextInput
                  value={catatan}
                  onChangeText={setCatatan}
                  placeholder="Misal: Kinerja sudah baik, perlu ditingkatkan dalam hal kerapihan..."
                  placeholderTextColor="#c1c6d6"
                  multiline
                  numberOfLines={5}
                  className="bg-surface border border-outline-variant rounded-xl p-3 text-on-surface"
                  style={{
                    textAlignVertical: "top",
                    minHeight: 120,
                  }}
                />
              </View>
            </View>

            {/* RIGHT — Rating */}
            <View className={isTablet ? "flex-1" : ""}>
              <View className="bg-tertiary/5 border border-tertiary/30 rounded-2xl p-5 mb-5">
                <Text className="font-bold text-on-surface text-base mb-2">
                  Rating
                </Text>
                <Text className="text-on-surface-variant text-sm mb-4">
                  Pilih nilai 1 (sangat kurang) sampai 5 (sangat baik)
                </Text>

                <View className="flex-row items-center justify-center gap-2 mb-4">
                  {[1, 2, 3, 4, 5].map((i) => (
                    <Pressable
                      key={i}
                      onPress={() => setRating(i)}
                      className="p-1"
                    >
                      <Ionicons
                        name={i <= rating ? "star" : "star-outline"}
                        size={44}
                        color={i <= rating ? "#e08a14" : "#c1c6d6"}
                      />
                    </Pressable>
                  ))}
                </View>

                {rating > 0 && (
                  <View
                    className="items-center p-3 rounded-xl mb-2"
                    style={{ backgroundColor: `${RATING_COLORS[rating]}1a` }}
                  >
                    <Text
                      className="text-2xl font-bold"
                      style={{ color: RATING_COLORS[rating] }}
                    >
                      {rating} / 5
                    </Text>
                    <Text
                      className="text-sm font-bold mt-1"
                      style={{ color: RATING_COLORS[rating] }}
                    >
                      {RATING_LABELS[rating]}
                    </Text>
                  </View>
                )}

                {/* Quick rating buttons */}
                <View className="flex-row gap-2 mt-3">
                  {[1, 2, 3, 4, 5].map((i) => {
                    const active = rating === i;
                    return (
                      <Pressable
                        key={i}
                        onPress={() => setRating(i)}
                        className="flex-1 h-10 rounded-lg items-center justify-center"
                        style={{
                          backgroundColor: active
                            ? RATING_COLORS[i]
                            : "transparent",
                          borderWidth: 1,
                          borderColor: active
                            ? RATING_COLORS[i]
                            : "#e1e3e4",
                        }}
                      >
                        <Text
                          className="text-xs font-bold"
                          style={{
                            color: active ? "#ffffff" : "#414754",
                          }}
                        >
                          {i}
                        </Text>
                      </Pressable>
                    );
                  })}
                </View>
              </View>

              {/* Submit */}
              <Pressable
                onPress={onSubmit}
                disabled={!canSubmit}
                className="h-12 rounded-xl items-center justify-center flex-row gap-2"
                style={{
                  backgroundColor: canSubmit ? "#e08a14" : "#c1c6d6",
                  opacity: submitting ? 0.7 : 1,
                }}
              >
                <Ionicons
                  name={submitting ? "hourglass-outline" : "checkmark-circle"}
                  size={18}
                  color="#ffffff"
                />
                <Text className="text-white font-bold">
                  {submitting ? "Menyimpan..." : "Simpan Penilaian"}
                </Text>
              </Pressable>

              {!canSubmit && !submitting && (
                <Text className="text-xs text-on-surface-variant text-center mt-2">
                  Pilih petugas dan beri rating untuk menyimpan
                </Text>
              )}

              {/* Tips */}
              <View className="bg-primary/5 rounded-2xl p-4 mt-5 flex-row items-start gap-3">
                <Ionicons
                  name="bulb-outline"
                  size={20}
                  color="#005bbf"
                  style={{ marginTop: 2 }}
                />
                <View className="flex-1">
                  <Text className="text-primary text-xs font-bold mb-1">
                    Tips Penilaian
                  </Text>
                  <Text className="text-primary text-[11px] leading-4">
                    Berikan feedback yang spesifik dan konstruktif. Penilaian
                    akan diteruskan ke petugas dan menjadi bahan evaluasi
                    bulanan.
                  </Text>
                </View>
              </View>
            </View>
          </View>
        </ScrollView>
      </AdminScreen>
    </>
  );
}
