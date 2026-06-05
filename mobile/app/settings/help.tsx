import { useState } from "react";
import { Pressable, ScrollView, Text, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { LinearGradient } from "expo-linear-gradient";
import { Stack, useRouter } from "expo-router";
import { Ionicons } from "@expo/vector-icons";

const FAQ: { q: string; a: string }[] = [
  {
    q: "Bagaimana cara mengirim laporan kegiatan?",
    a: "Buka tab Laporan, pilih jadwal terkait (lokasi akan terisi otomatis), isi jam mulai & selesai, deskripsi kegiatan, lalu unggah foto sebelum dan sesudah. Tekan Kirim Laporan.",
  },
  {
    q: "Kenapa lokasi tidak bisa dipilih manual?",
    a: "Lokasi otomatis mengikuti jadwal yang kamu pilih, supaya laporan selalu sesuai dengan tugas yang dijadwalkan supervisor.",
  },
  {
    q: "Foto bukti wajib diisi?",
    a: "Ya. Minimal satu foto sebelum dan satu foto sesudah dibersihkan diperlukan agar laporan dapat dikirim dan disetujui.",
  },
  {
    q: "Kamera tidak terbuka saat ambil foto?",
    a: "Pastikan izin Kamera sudah diaktifkan. Buka Pengaturan HP → Aplikasi → ServiceGO → Izin → aktifkan Kamera dan Penyimpanan/Foto.",
  },
  {
    q: "Bagaimana proses persetujuan laporan?",
    a: "Setelah dikirim, laporan berstatus 'Menunggu'. Supervisor akan meninjau, memberi rating, lalu menyetujui atau menolak. Kamu akan melihat hasilnya di notifikasi.",
  },
  {
    q: "Apa arti angka merah di ikon lonceng?",
    a: "Itu jumlah notifikasi yang belum dibaca. Angka akan hilang setelah kamu membuka halaman notifikasi.",
  },
  {
    q: "Laporan saya hilang saat tidak ada internet?",
    a: "Tidak. Laporan disimpan sementara di perangkat dan akan terkirim otomatis begitu koneksi internet kembali.",
  },
  {
    q: "Bagaimana mengganti kata sandi?",
    a: "Buka Profil → Pengaturan → Ubah Kata Sandi. Masukkan kata sandi lama dan kata sandi baru (minimal 8 karakter).",
  },
];

function FaqRow({ q, a }: { q: string; a: string }) {
  const [open, setOpen] = useState(false);
  return (
    <Pressable
      onPress={() => setOpen((o) => !o)}
      className="bg-surface-container-lowest border border-outline-variant rounded-2xl px-4 py-3.5 mb-2.5 active:opacity-80"
    >
      <View className="flex-row items-center gap-3">
        <Ionicons name="help-circle" size={20} color="#0a5fd6" />
        <Text className="flex-1 font-semibold text-on-surface">{q}</Text>
        <Ionicons
          name={open ? "chevron-up" : "chevron-down"}
          size={18}
          color="#9aa0aa"
        />
      </View>
      {open && (
        <Text className="text-on-surface-variant text-sm mt-2.5 leading-5 pl-8">
          {a}
        </Text>
      )}
    </Pressable>
  );
}

export default function HelpScreen() {
  const router = useRouter();
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
            <Text className="text-white font-bold text-lg">Bantuan & FAQ</Text>
          </View>
        </SafeAreaView>
      </LinearGradient>

      <ScrollView contentContainerStyle={{ padding: 20, paddingBottom: 60 }}>
        <Text className="text-on-surface-variant text-sm mb-4">
          Pertanyaan yang sering ditanyakan. Ketuk untuk membuka jawaban.
        </Text>
        {FAQ.map((f) => (
          <FaqRow key={f.q} q={f.q} a={f.a} />
        ))}

        <View className="mt-4 bg-primary/5 rounded-2xl p-4 flex-row items-center gap-3">
          <Ionicons name="mail-outline" size={20} color="#0a5fd6" />
          <Text className="flex-1 text-primary text-xs">
            Masih butuh bantuan? Hubungi supervisor atau pengurus koperasi di
            unit kamu.
          </Text>
        </View>
      </ScrollView>
    </View>
  );
}
