import { useState } from "react";
import {
  ActivityIndicator,
  Alert,
  Image,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
} from "react-native";
import { Stack, useLocalSearchParams, useRouter } from "expo-router";
import { Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";
import { AdminScreen } from "@/components/admin/AdminScreen";
import { useIsTablet } from "@/lib/useIsTablet";
import { useApproveReport, useRejectReport, useApprovalReportDetail } from "@/lib/hooks";
import { ApiError } from "@/lib/api";
import type { ApprovalScope } from "@/lib/types";

interface LaporanDetail {
  id: number;
  tanggal: string;
  petugas: string;
  petugasEmail: string;
  lokasi: string;
  unit: string;
  jamMulai: string;
  jamSelesai: string;
  kegiatan: string;
  catatanPetugas: string;
  status: "submitted" | "approved" | "rejected" | "draft";
  reportingStatus: "ontime" | "late";
  lateMinutes?: number;
  fotoSebelum: string[];
  fotoSesudah: string[];
  foto?: string[];
  rating: number | null;
  catatanSupervisor?: string;
}

// In real app, fetch by id. Here we have a single mock.
const MOCK: LaporanDetail = {
  id: 101,
  tanggal: "02 Juni 2026",
  petugas: "Rahmat Hidayat",
  petugasEmail: "rahmat@yapi",
  lokasi: "Toilet Lt.1 - Gedung A",
  unit: "Office Kopkar YAPI",
  jamMulai: "08:00",
  jamSelesai: "08:45",
  kegiatan:
    "Pembersihan rutin pagi: mopping lantai, lap wastafel, ganti tissue, cek pasokan sabun, dan buang sampah. Semua area difoto sebelum dan sesudah.",
  catatanPetugas:
    "Tidak ada masalah. Stok tissue tinggal sedikit, perlu restock besok.",
  status: "submitted",
  reportingStatus: "ontime",
  fotoSebelum: [],
  fotoSesudah: [],
  rating: null,
};

export default function LaporanDetailScreen() {
  const router = useRouter();
  const isTablet = useIsTablet();
  const params = useLocalSearchParams<{
    id?: string;
    scope?: string;
    petugas?: string;
    lokasi?: string;
    unit?: string;
    tanggal?: string;
    summary?: string;
    status?: string;
  }>();

  const scope = params.scope as ApprovalScope | undefined;
  const reportId = params.id ? Number(params.id) : 0;

  const { data: detailData, isLoading } = useApprovalReportDetail(scope, reportId);

  const [rating, setRating] = useState<number>(0);
  const [catatan, setCatatan] = useState("");
  const [rejectReason, setRejectReason] = useState("");

  const approveMutation = useApproveReport();
  const rejectMutation = useRejectReport();
  const busy = approveMutation.isPending || rejectMutation.isPending;

  const apiError = (e: unknown, fallback: string) =>
    Alert.alert("Gagal", e instanceof ApiError ? e.message : fallback);

  if (isLoading) {
    return (
      <View style={{ flex: 1, backgroundColor: "#f8f9fa", justifyContent: "center", alignItems: "center" }}>
        <ActivityIndicator size="large" color="#005bbf" />
        <Text style={{ marginTop: 12, color: "#5a6072", fontSize: 14 }}>Memuat detail laporan...</Text>
      </View>
    );
  }

  const data = detailData || {};
  const report: LaporanDetail = {
    id: reportId || data.id || 0,
    tanggal: data.tanggal || params.tanggal || "-",
    petugas: data.petugas?.name || params.petugas || "-",
    petugasEmail: data.petugas?.email || data.petugas?.phone || "-",
    lokasi: data.lokasi?.nama_lokasi || params.lokasi || "-",
    unit: data.unit?.nama_unit || params.unit || "-",
    jamMulai: data.jam_mulai || "-",
    jamSelesai: data.jam_selesai || "-",
    catatanPetugas: data.catatan_petugas || "",
    status: (data.status || params.status || "submitted") as LaporanDetail["status"],
    reportingStatus: "ontime",
    fotoSebelum: data.foto_sebelum || [],
    fotoSesudah: data.foto_sesudah || [],
    foto: data.foto || [],
    rating: data.rating || null,
    kegiatan:
      scope === "kebersihan"
        ? (data.kegiatan || params.summary || "-")
        : scope === "satpam"
        ? `Kondisi: ${data.kondisi ?? "-"}\nTemuan: ${data.temuan || "-"}\nTindakan: ${data.tindakan || "-"}`
        : scope === "ob"
        ? `Uraian: ${data.uraian || "-"}\nJenis Pekerjaan: ${data.jenis_pekerjaan || "-"}`
        : scope === "toko"
        ? `Kondisi Stok: ${data.kondisi_stok || "-"}\nCatatan Stok: ${data.catatan_stok || "-"}`
        : (params.summary || "-"),
  };

  const isPending = report.status === "submitted";

  const onApprove = () => {
    if (rating === 0) {
      Alert.alert(
        "Beri Rating Dulu",
        "Pilih rating 1-5 bintang sebelum menyetujui."
      );
      return;
    }
    Alert.alert(
      "Setujui Laporan",
      `Setujui laporan dari ${report.petugas} dengan rating ${rating}/5?`,
      [
        { text: "Batal", style: "cancel" },
        {
          text: "Setujui",
          onPress: () => {
            if (!scope) {
              Alert.alert("Berhasil", "Laporan telah disetujui.", [
                { text: "OK", onPress: () => router.back() },
              ]);
              return;
            }
            approveMutation.mutate(
              {
                scope,
                id: reportId,
                rating,
                catatan_supervisor: catatan.trim() || undefined,
              },
              {
                onSuccess: () =>
                  Alert.alert("Berhasil", "Laporan telah disetujui.", [
                    { text: "OK", onPress: () => router.back() },
                  ]),
                onError: (e) => apiError(e, "Gagal menyetujui laporan."),
              }
            );
          },
        },
      ]
    );
  };

  const onReject = () => {
    if (rejectReason.trim().length === 0) {
      Alert.alert(
        "Alasan Penolakan",
        "Tuliskan alasan kenapa laporan ditolak."
      );
      return;
    }
    Alert.alert("Tolak Laporan", `Tolak laporan ini?\n\nAlasan: ${rejectReason}`, [
      { text: "Batal", style: "cancel" },
      {
        text: "Tolak",
        style: "destructive",
        onPress: () => {
          if (!scope) {
            Alert.alert("Berhasil", "Laporan telah ditolak.", [
              { text: "OK", onPress: () => router.back() },
            ]);
            return;
          }
          rejectMutation.mutate(
            { scope, id: reportId, reason: rejectReason.trim() },
            {
              onSuccess: () =>
                Alert.alert("Berhasil", "Laporan telah ditolak.", [
                  { text: "OK", onPress: () => router.back() },
                ]),
              onError: (e) => apiError(e, "Gagal menolak laporan."),
            }
          );
        },
      },
    ]);
  };

  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <AdminScreen
        title={`Laporan #${report.id}`}
        subtitle={`${report.tanggal} · ${report.petugas}`}
        icon="clipboard-outline"
        color="#005bbf"
        backHref={null}
      >
        <ScrollView
          contentContainerStyle={{ padding: isTablet ? 32 : 20, paddingBottom: 60 }}
        >
          <View className={isTablet ? "flex-row gap-6" : ""}>
            {/* LEFT */}
            <View className={isTablet ? "flex-1" : ""}>
              {/* Petugas info */}
              <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-5">
                <View className="flex-row items-center gap-3">
                  <View className="w-14 h-14 rounded-full bg-secondary/15 items-center justify-center">
                    <Text className="text-secondary font-bold text-lg">
                      {report.petugas.charAt(0)}
                    </Text>
                  </View>
                  <View className="flex-1">
                    <Text className="font-bold text-on-surface text-base">
                      {report.petugas}
                    </Text>
                    <Text className="text-on-surface-variant text-xs">
                      {report.petugasEmail}
                    </Text>
                    <View className="flex-row items-center gap-1 mt-1">
                      <Ionicons
                        name="business-outline"
                        size={11}
                        color="#5a6072"
                      />
                      <Text className="text-on-surface-variant text-xs">
                        {report.unit}
                      </Text>
                    </View>
                  </View>
                </View>
              </View>

              {/* Info detail */}
              <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-5">
                <Text className="font-bold text-on-surface mb-3">
                  Detail Kegiatan
                </Text>
                <InfoRow
                  icon="location-outline"
                  label="Lokasi"
                  value={report.lokasi}
                />
                <InfoRow
                  icon="calendar-outline"
                  label="Tanggal"
                  value={report.tanggal}
                />
                <InfoRow
                  icon="time-outline"
                  label="Waktu"
                  value={`${report.jamMulai} - ${report.jamSelesai}`}
                />
                <View
                  className={`mt-3 p-3 rounded-xl ${
                    report.reportingStatus === "ontime"
                      ? "bg-secondary/10"
                      : "bg-tertiary/10"
                  }`}
                >
                  <View className="flex-row items-center gap-2">
                    <Ionicons
                      name={
                        report.reportingStatus === "ontime"
                          ? "checkmark-circle"
                          : "alert-circle"
                      }
                      size={16}
                      color={
                        report.reportingStatus === "ontime"
                          ? "#0a7e3e"
                          : "#e08a14"
                      }
                    />
                    <Text
                      className={`text-xs font-bold ${
                        report.reportingStatus === "ontime"
                          ? "text-secondary"
                          : "text-tertiary"
                      }`}
                    >
                      {report.reportingStatus === "ontime"
                        ? "Lapor Tepat Waktu"
                        : `Telat ${report.lateMinutes ?? "?"} menit`}
                    </Text>
                  </View>
                </View>
              </View>

              {/* Description */}
              <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-5">
                <View className="flex-row items-center gap-2 mb-2">
                  <Ionicons
                    name="document-text-outline"
                    size={16}
                    color="#005bbf"
                  />
                  <Text className="font-bold text-on-surface">
                    Deskripsi Kegiatan
                  </Text>
                </View>
                <Text className="text-on-surface text-sm leading-5">
                  {report.kegiatan}
                </Text>
              </View>

              {/* Catatan Petugas */}
              {report.catatanPetugas && (
                <View className="bg-primary/5 border border-primary/30 rounded-2xl p-4 mb-5">
                  <View className="flex-row items-center gap-2 mb-2">
                    <Ionicons
                      name="chatbubble-outline"
                      size={16}
                      color="#005bbf"
                    />
                    <Text className="font-bold text-primary text-sm">
                      Catatan Petugas
                    </Text>
                  </View>
                  <Text className="text-primary text-sm italic leading-5">
                    "{report.catatanPetugas}"
                  </Text>
                </View>
              )}

              {/* Photos */}
              <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-5">
                <Text className="font-bold text-on-surface mb-3">
                  Bukti Foto
                </Text>
                {scope === "kebersihan" || scope === "ob" ? (
                  <>
                    <Text className="text-on-surface-variant text-xs mb-2">
                      Sebelum
                    </Text>
                    <PhotoGrid urls={report.fotoSebelum} placeholder="Sebelum" />
                    <Text className="text-on-surface-variant text-xs mt-4 mb-2">
                      Sesudah
                    </Text>
                    <PhotoGrid urls={report.fotoSesudah} placeholder="Sesudah" />
                  </>
                ) : (
                  <>
                    <Text className="text-on-surface-variant text-xs mb-2">
                      Foto Lampiran
                    </Text>
                    <PhotoGrid urls={report.foto} placeholder="Lampiran" />
                  </>
                )}
              </View>
            </View>

            {/* RIGHT — actions */}
            <View className={isTablet ? "flex-1" : ""}>
              {isPending ? (
                <>
                  {/* Rating */}
                  <View className="bg-tertiary/5 border border-tertiary/30 rounded-2xl p-4 mb-5">
                    <Text className="font-bold text-on-surface mb-2">
                      Beri Rating
                    </Text>
                    <Text className="text-on-surface-variant text-xs mb-3">
                      Nilai pekerjaan petugas 1-5 bintang
                    </Text>
                    <View className="flex-row items-center justify-center gap-2 mb-2">
                      {[1, 2, 3, 4, 5].map((i) => (
                        <Pressable
                          key={i}
                          onPress={() => setRating(i)}
                          className="p-2"
                        >
                          <Ionicons
                            name={i <= rating ? "star" : "star-outline"}
                            size={36}
                            color={i <= rating ? "#e08a14" : "#c1c6d6"}
                          />
                        </Pressable>
                      ))}
                    </View>
                    {rating > 0 && (
                      <Text className="text-center text-tertiary font-bold">
                        {rating} / 5 bintang
                      </Text>
                    )}
                  </View>

                  {/* Catatan supervisor */}
                  <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-5">
                    <View className="flex-row items-center gap-2 mb-2">
                      <Ionicons
                        name="create-outline"
                        size={16}
                        color="#414754"
                      />
                      <Text className="font-bold text-on-surface">
                        Catatan Supervisor
                      </Text>
                    </View>
                    <TextInput
                      value={catatan}
                      onChangeText={setCatatan}
                      placeholder="Catatan untuk petugas (opsional)..."
                      placeholderTextColor="#c1c6d6"
                      multiline
                      numberOfLines={3}
                      className="bg-surface border border-outline-variant rounded-xl p-3 text-on-surface"
                      style={{
                        textAlignVertical: "top",
                        minHeight: 80,
                      }}
                    />
                  </View>

                  {/* Approve button */}
                  <Pressable
                    onPress={onApprove}
                    className="h-12 rounded-xl bg-secondary items-center justify-center flex-row gap-2 mb-3 active:opacity-90"
                  >
                    <Ionicons name="checkmark-circle" size={20} color="#ffffff" />
                    <Text className="text-white font-bold">
                      Setujui Laporan
                    </Text>
                  </Pressable>

                  {/* Reject area */}
                  <View className="bg-error/5 border border-error/30 rounded-2xl p-4">
                    <View className="flex-row items-center gap-2 mb-2">
                      <Ionicons
                        name="close-circle-outline"
                        size={16}
                        color="#d62828"
                      />
                      <Text className="font-bold text-error">Tolak Laporan</Text>
                    </View>
                    <Text className="text-on-surface-variant text-xs mb-3">
                      Berikan alasan kenapa laporan tidak memenuhi standar
                    </Text>
                    <TextInput
                      value={rejectReason}
                      onChangeText={setRejectReason}
                      placeholder="Alasan penolakan..."
                      placeholderTextColor="#c1c6d6"
                      multiline
                      numberOfLines={3}
                      className="bg-surface border border-outline-variant rounded-xl p-3 text-on-surface mb-3"
                      style={{
                        textAlignVertical: "top",
                        minHeight: 80,
                      }}
                    />
                    <Pressable
                      onPress={onReject}
                      className="h-11 rounded-xl border-2 border-error items-center justify-center flex-row gap-2 active:opacity-80"
                    >
                      <Ionicons name="close" size={18} color="#d62828" />
                      <Text className="text-error font-bold">Tolak Laporan</Text>
                    </Pressable>
                  </View>
                </>
              ) : (
                <View className="bg-secondary/10 border border-secondary/30 rounded-2xl p-5 items-center">
                  <Ionicons
                    name="checkmark-done-circle"
                    size={48}
                    color="#0a7e3e"
                  />
                  <Text className="font-bold text-secondary mt-2">
                    Sudah Diproses
                  </Text>
                  <Text className="text-on-surface-variant text-xs mt-1 text-center">
                    Laporan ini sudah {report.status === "approved" ? "disetujui" : "ditolak"}
                  </Text>
                </View>
              )}
            </View>
          </View>
        </ScrollView>
      </AdminScreen>
    </>
  );
}

function InfoRow({
  icon,
  label,
  value,
}: {
  icon: React.ComponentProps<typeof Ionicons>["name"];
  label: string;
  value: string;
}) {
  return (
    <View className="flex-row items-center gap-3 py-2 border-b border-outline-variant/40 last:border-0">
      <Ionicons name={icon} size={16} color="#5a6072" />
      <Text className="text-on-surface-variant text-sm w-20">{label}</Text>
      <Text className="flex-1 text-on-surface text-sm font-semibold" numberOfLines={1}>
        {value}
      </Text>
    </View>
  );
}

function PhotoGrid({
  urls,
  placeholder,
}: {
  urls: string[] | undefined;
  placeholder: string;
}) {
  const images = urls || [];
  if (images.length === 0) {
    return (
      <View className="flex-row items-center gap-2 p-3 bg-surface border border-dashed border-outline-variant rounded-xl w-full">
        <MaterialCommunityIcons name="image-off-outline" size={20} color="#8a9099" />
        <Text className="text-on-surface-variant text-xs italic">
          Tidak ada foto {placeholder.toLowerCase()}
        </Text>
      </View>
    );
  }

  return (
    <View className="flex-row flex-wrap gap-2">
      {images.map((url, i) => (
        <View
          key={url + i}
          className="w-24 h-24 rounded-xl overflow-hidden bg-surface-container border border-outline-variant"
        >
          <Image
            source={{ uri: url }}
            style={{ width: "100%", height: "100%" }}
            resizeMode="cover"
          />
        </View>
      ))}
    </View>
  );
}
