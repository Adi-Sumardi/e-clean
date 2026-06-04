import { useMemo, useState } from "react";
import { Alert, Pressable, ScrollView, Text, View } from "react-native";
import { useRouter } from "expo-router";
import { Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";
import { useAuthStore } from "@/stores/auth-store";
import { ROLE_LABEL } from "@/constants/role";
import { StatCard } from "@/components/StatCard";
import { useIsTablet } from "@/lib/useIsTablet";
import { NotificationBell } from "@/components/NotificationBell";
import { DashboardHeader } from "@/components/DashboardHeader";

type IoniconName = React.ComponentProps<typeof Ionicons>["name"];

interface ChecklistItem {
  id: number;
  label: string;
  icon: IoniconName;
  done: boolean;
}

const INITIAL_CHECKLIST: ChecklistItem[] = [
  {
    id: 1,
    label: "Pembukaan toko + nyalakan lampu/AC",
    icon: "sunny-outline",
    done: true,
  },
  {
    id: 2,
    label: "Cek kebersihan area display",
    icon: "sparkles-outline",
    done: true,
  },
  {
    id: 3,
    label: "Restocking display produk",
    icon: "cube-outline",
    done: true,
  },
  {
    id: 4,
    label: "Cek harga & label produk",
    icon: "pricetag-outline",
    done: false,
  },
  {
    id: 5,
    label: "Cek stok kasir & uang kembali",
    icon: "cash-outline",
    done: false,
  },
  {
    id: 6,
    label: "Briefing tim sebelum buka",
    icon: "people-outline",
    done: false,
  },
];

interface CustomerComplaint {
  id: number;
  pelanggan: string;
  kategori: string;
  deskripsi: string;
  waktu: string;
  status: "open" | "selesai";
}

const COMPLAINTS: CustomerComplaint[] = [
  {
    id: 1,
    pelanggan: "Ibu Ratna",
    kategori: "Produk Rusak",
    deskripsi: "Kemasan biskuit sobek di rak depan",
    waktu: "15 menit lalu",
    status: "open",
  },
  {
    id: 2,
    pelanggan: "Bp. Hasan",
    kategori: "Harga Beda",
    deskripsi: "Label harga di rak beda dengan kasir",
    waktu: "1 jam lalu",
    status: "selesai",
  },
];

const STOCK_ALERTS = [
  { id: 1, nama: "Air Mineral 600ml", sisa: 4, satuan: "dus" },
  { id: 2, nama: "Indomie Goreng", sisa: 8, satuan: "pcs" },
  { id: 3, nama: "Susu UHT Coklat", sisa: 6, satuan: "pcs" },
];

export function PetugasTokoDashboard() {
  const router = useRouter();
  const isTablet = useIsTablet();
  const user = useAuthStore((s) => s.user);
  const [checklist, setChecklist] = useState<ChecklistItem[]>(INITIAL_CHECKLIST);
  const [shiftStarted, setShiftStarted] = useState(true);

  const stats = useMemo(() => {
    const done = checklist.filter((c) => c.done).length;
    const openComplaints = COMPLAINTS.filter((c) => c.status === "open").length;
    return {
      done,
      total: checklist.length,
      progress: Math.round((done / checklist.length) * 100),
      openComplaints,
      lowStock: STOCK_ALERTS.length,
      transaksi: 47,
      omset: "Rp 2.4jt",
    };
  }, [checklist]);

  const toggle = (id: number) => {
    setChecklist((c) =>
      c.map((item) => (item.id === id ? { ...item, done: !item.done } : item))
    );
  };

  const onHandover = () => {
    Alert.alert(
      "Serah-Terima Shift",
      "Akhiri shift dan serah-terima ke petugas berikutnya?",
      [
        { text: "Batal", style: "cancel" },
        {
          text: "Serah-Terima",
          onPress: () => {
            setShiftStarted(false);
            Alert.alert("Berhasil", "Shift telah diserahterimakan.");
          },
        },
      ]
    );
  };

  const onStartShift = () => {
    setShiftStarted(true);
    Alert.alert("Berhasil", "Shift baru dimulai.");
  };

  const headerPad = isTablet ? "px-8" : "px-5";
  const contentPad = isTablet ? 32 : 20;

  return (
    <View className="flex-1 bg-background">
      <DashboardHeader
        colors={["#0891b2", "#075f7a"]}
        title={user?.unit?.name ?? "Toko Kopkar YAPI"}
        subtitle="Petugas Toko"
        icon={
          <MaterialCommunityIcons name="storefront-outline" size={22} color="#fff" />
        }
        right={<NotificationBell size={22} color="#fff" />}
      />

      <ScrollView
        contentContainerStyle={{ padding: contentPad, paddingBottom: 120 }}
        showsVerticalScrollIndicator={false}
      >
        {/* Greeting */}
        <View className="mb-5">
          <Text className="text-on-surface-variant">Selamat bertugas,</Text>
          <Text
            className={`font-bold text-on-surface ${isTablet ? "text-4xl" : "text-2xl"}`}
          >
            {user?.name ?? "Petugas Toko"}
          </Text>
          <View className="flex-row items-center gap-2 mt-2">
            <View className="px-3 py-1 rounded-full bg-tertiary/10 flex-row items-center gap-1">
              <MaterialCommunityIcons
                name="storefront"
                size={12}
                color="#7e5a17"
              />
              <Text className="text-tertiary text-xs font-bold">
                {user ? ROLE_LABEL[user.role] : "Petugas Toko"}
              </Text>
            </View>
            <View
              className={`px-3 py-1 rounded-full flex-row items-center gap-1 ${
                shiftStarted ? "bg-secondary/10" : "bg-error/10"
              }`}
            >
              <View
                className={`w-2 h-2 rounded-full ${
                  shiftStarted ? "bg-secondary" : "bg-error"
                }`}
              />
              <Text
                className={`text-xs font-bold ${
                  shiftStarted ? "text-secondary" : "text-error"
                }`}
              >
                {shiftStarted ? "Shift Aktif" : "Shift Berakhir"}
              </Text>
            </View>
          </View>
        </View>

        {/* Shift card */}
        <View className="bg-tertiary rounded-2xl p-5 mb-5 shadow-md">
          <View className="flex-row items-center justify-between">
            <View className="flex-1">
              <Text className="text-white/80 font-semibold">
                Shift {shiftStarted ? "Aktif" : "Belum Mulai"}
              </Text>
              <Text
                className={`text-white font-bold mt-1 ${isTablet ? "text-3xl" : "text-2xl"}`}
              >
                08:00 - 16:00
              </Text>
              <Text className="text-white/70 text-xs mt-1">
                Hari ini · Mulai 4 jam lalu
              </Text>
            </View>
            <View className="w-20 h-20 rounded-2xl bg-white/15 items-center justify-center">
              <MaterialCommunityIcons
                name="storefront"
                size={36}
                color="#ffffff"
              />
            </View>
          </View>
          <View className="flex-row gap-2 mt-4">
            {shiftStarted ? (
              <>
                <Pressable className="flex-1 h-11 rounded-xl bg-white/20 items-center justify-center flex-row gap-2 active:opacity-80">
                  <Ionicons name="qr-code" size={16} color="#ffffff" />
                  <Text className="text-white font-bold text-sm">
                    Scan Absen
                  </Text>
                </Pressable>
                <Pressable
                  onPress={onHandover}
                  className="flex-1 h-11 rounded-xl bg-white items-center justify-center flex-row gap-2 active:opacity-90"
                >
                  <Ionicons
                    name="swap-horizontal"
                    size={16}
                    color="#7e5a17"
                  />
                  <Text className="text-tertiary font-bold text-sm">
                    Serah-Terima
                  </Text>
                </Pressable>
              </>
            ) : (
              <Pressable
                onPress={onStartShift}
                className="flex-1 h-11 rounded-xl bg-white items-center justify-center flex-row gap-2 active:opacity-90"
              >
                <Ionicons name="play-circle" size={16} color="#7e5a17" />
                <Text className="text-tertiary font-bold text-sm">
                  Mulai Shift
                </Text>
              </Pressable>
            )}
          </View>
        </View>

        {/* Stats */}
        <View className="flex-row gap-3 mb-6">
          <StatCard
            icon="receipt-outline"
            label="Transaksi"
            value={stats.transaksi}
            hint="Hari ini"
            tone="primary"
          />
          <StatCard
            icon="cash-outline"
            label="Omset"
            value={stats.omset}
            hint="Hari ini"
            tone="secondary"
          />
          <StatCard
            icon="alert-outline"
            label="Stok Minim"
            value={stats.lowStock}
            hint="Item"
            tone={stats.lowStock > 0 ? "error" : "secondary"}
          />
        </View>

        {/* Quick Actions */}
        <Text
          className={`font-bold text-on-surface mb-3 ${isTablet ? "text-xl" : "text-lg"}`}
        >
          Aksi Cepat
        </Text>
        <View className="gap-3 mb-6">
          <View className="flex-row gap-3">
            <Pressable
              onPress={() => router.push("/(tabs)/laporan")}
              className={`flex-1 rounded-2xl bg-tertiary items-center gap-2 active:opacity-90 ${
                isTablet ? "p-6" : "p-4"
              }`}
            >
              <Ionicons
                name="camera"
                size={isTablet ? 36 : 28}
                color="#ffffff"
              />
              <Text className="font-bold text-white text-center">
                Lapor Harian
              </Text>
            </Pressable>
            <Pressable
              className={`flex-1 rounded-2xl bg-error items-center gap-2 active:opacity-90 ${
                isTablet ? "p-6" : "p-4"
              }`}
            >
              <Ionicons
                name="chatbubble-ellipses"
                size={isTablet ? 36 : 28}
                color="#ffffff"
              />
              <Text className="font-bold text-white text-center">
                Keluhan Pelanggan
              </Text>
            </Pressable>
          </View>
          <View className="flex-row gap-3">
            <Pressable
              className={`flex-1 rounded-2xl border border-outline-variant bg-surface-container-lowest items-center gap-2 active:opacity-80 ${
                isTablet ? "p-6" : "p-4"
              }`}
            >
              <Ionicons
                name="cube"
                size={isTablet ? 36 : 28}
                color="#005bbf"
              />
              <Text className="font-bold text-on-surface text-center">
                Cek Stok
              </Text>
            </Pressable>
            <Pressable
              className={`flex-1 rounded-2xl border border-outline-variant bg-surface-container-lowest items-center gap-2 active:opacity-80 ${
                isTablet ? "p-6" : "p-4"
              }`}
            >
              <Ionicons
                name="calculator"
                size={isTablet ? 36 : 28}
                color="#0a7e3e"
              />
              <Text className="font-bold text-on-surface text-center">
                Tutup Kasir
              </Text>
            </Pressable>
          </View>
        </View>

        {/* 2 columns on tablet */}
        <View className={isTablet ? "flex-row gap-6" : ""}>
          {/* LEFT — Checklist + Complaints */}
          <View className={isTablet ? "flex-1" : ""}>
            {/* Daily Checklist */}
            <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6">
              <View className="flex-row items-center justify-between mb-3">
                <View>
                  <Text
                    className={`font-bold text-on-surface ${isTablet ? "text-lg" : "text-base"}`}
                  >
                    Checklist Harian
                  </Text>
                  <Text className="text-on-surface-variant text-xs">
                    {stats.done} dari {stats.total} selesai
                  </Text>
                </View>
                <View
                  className="w-12 h-12 rounded-full items-center justify-center"
                  style={{
                    borderWidth: 3,
                    borderColor:
                      stats.progress >= 80
                        ? "#0a7e3e"
                        : stats.progress >= 50
                          ? "#e08a14"
                          : "#d62828",
                  }}
                >
                  <Text
                    className="font-bold text-xs"
                    style={{
                      color:
                        stats.progress >= 80
                          ? "#0a7e3e"
                          : stats.progress >= 50
                            ? "#e08a14"
                            : "#d62828",
                    }}
                  >
                    {stats.progress}%
                  </Text>
                </View>
              </View>
              <View className="gap-2">
                {checklist.map((c) => (
                  <Pressable
                    key={c.id}
                    onPress={() => toggle(c.id)}
                    className={`flex-row items-center gap-3 p-3 rounded-xl border ${
                      c.done
                        ? "bg-secondary/5 border-secondary/30"
                        : "bg-surface border-outline-variant"
                    } active:opacity-80`}
                  >
                    <View
                      className={`w-6 h-6 rounded-md ${
                        c.done
                          ? "bg-secondary"
                          : "bg-surface border-2 border-outline-variant"
                      } items-center justify-center`}
                    >
                      {c.done && (
                        <Ionicons name="checkmark" size={16} color="#ffffff" />
                      )}
                    </View>
                    <Ionicons
                      name={c.icon}
                      size={18}
                      color={c.done ? "#0a7e3e" : "#5a6072"}
                    />
                    <Text
                      className={`flex-1 text-sm ${
                        c.done
                          ? "text-secondary line-through"
                          : "text-on-surface font-semibold"
                      }`}
                    >
                      {c.label}
                    </Text>
                  </Pressable>
                ))}
              </View>
            </View>

            {/* Customer Complaints */}
            <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6">
              <View className="flex-row items-center justify-between mb-3">
                <View>
                  <Text
                    className={`font-bold text-on-surface ${isTablet ? "text-lg" : "text-base"}`}
                  >
                    Keluhan Pelanggan
                  </Text>
                  <Text className="text-on-surface-variant text-xs">
                    {stats.openComplaints} terbuka · {COMPLAINTS.length - stats.openComplaints} selesai
                  </Text>
                </View>
                <Pressable className="px-3 py-1 rounded-full bg-error/10 flex-row items-center gap-1">
                  <Ionicons name="add" size={14} color="#d62828" />
                  <Text className="text-error text-xs font-bold">Catat</Text>
                </Pressable>
              </View>
              <View className="gap-3">
                {COMPLAINTS.map((c) => {
                  const isOpen = c.status === "open";
                  return (
                    <View
                      key={c.id}
                      className="p-3 rounded-xl border border-outline-variant bg-surface"
                    >
                      <View className="flex-row items-start gap-3">
                        <View
                          className={`w-10 h-10 rounded-xl ${
                            isOpen ? "bg-error/15" : "bg-secondary/15"
                          } items-center justify-center`}
                        >
                          <Ionicons
                            name={isOpen ? "chatbubble-ellipses" : "checkmark-done"}
                            size={18}
                            color={isOpen ? "#d62828" : "#0a7e3e"}
                          />
                        </View>
                        <View className="flex-1">
                          <View className="flex-row items-center justify-between">
                            <Text className="font-bold text-on-surface" numberOfLines={1}>
                              {c.pelanggan}
                            </Text>
                            <Text className="text-on-surface-variant text-[10px]">
                              {c.waktu}
                            </Text>
                          </View>
                          <View className="flex-row items-center gap-2 mt-0.5">
                            <View className="px-2 py-0.5 rounded-full bg-tertiary/10">
                              <Text className="text-tertiary text-[10px] font-bold">
                                {c.kategori}
                              </Text>
                            </View>
                            <View
                              className={`px-2 py-0.5 rounded-full ${
                                isOpen ? "bg-error/10" : "bg-secondary/10"
                              }`}
                            >
                              <Text
                                className={`text-[10px] font-bold ${
                                  isOpen ? "text-error" : "text-secondary"
                                }`}
                              >
                                {isOpen ? "Terbuka" : "Selesai"}
                              </Text>
                            </View>
                          </View>
                          <Text
                            className="text-on-surface-variant text-xs mt-1"
                            numberOfLines={2}
                          >
                            {c.deskripsi}
                          </Text>
                        </View>
                      </View>
                    </View>
                  );
                })}
              </View>
            </View>
          </View>

          {/* RIGHT — Stock alerts + Cashier */}
          <View className={isTablet ? "flex-1" : ""}>
            {/* Stock alerts */}
            <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6">
              <View className="flex-row items-center justify-between mb-3">
                <View>
                  <Text
                    className={`font-bold text-on-surface ${isTablet ? "text-lg" : "text-base"}`}
                  >
                    Stok Menipis
                  </Text>
                  <Text className="text-on-surface-variant text-xs">
                    Perlu restock segera
                  </Text>
                </View>
                <View className="px-3 py-1 rounded-full bg-error/10">
                  <Text className="text-error text-xs font-bold">
                    {stats.lowStock} item
                  </Text>
                </View>
              </View>
              <View className="gap-2">
                {STOCK_ALERTS.map((s) => (
                  <View
                    key={s.id}
                    className="flex-row items-center gap-3 p-3 rounded-xl border border-error/30 bg-error/5"
                  >
                    <View className="w-10 h-10 rounded-xl bg-error/15 items-center justify-center">
                      <Ionicons name="cube" size={20} color="#d62828" />
                    </View>
                    <View className="flex-1">
                      <Text className="font-bold text-on-surface text-sm" numberOfLines={1}>
                        {s.nama}
                      </Text>
                      <Text className="text-error text-xs font-semibold">
                        Sisa {s.sisa} {s.satuan}
                      </Text>
                    </View>
                    <Pressable className="px-3 py-1.5 rounded-lg bg-error active:opacity-80">
                      <Text className="text-white text-[11px] font-bold">
                        Order
                      </Text>
                    </Pressable>
                  </View>
                ))}
              </View>
            </View>

            {/* Cashier Summary */}
            <View className="bg-secondary/5 border border-secondary/30 rounded-2xl p-4 mb-6">
              <View className="flex-row items-center justify-between mb-4">
                <View>
                  <Text
                    className={`font-bold text-on-surface ${isTablet ? "text-lg" : "text-base"}`}
                  >
                    Ringkasan Kasir
                  </Text>
                  <Text className="text-on-surface-variant text-xs">
                    Penjualan hari ini
                  </Text>
                </View>
                <View className="w-12 h-12 rounded-xl bg-secondary/15 items-center justify-center">
                  <Ionicons name="cash" size={24} color="#0a7e3e" />
                </View>
              </View>
              <View className="gap-3">
                <View className="flex-row items-center justify-between p-3 rounded-xl bg-surface">
                  <View className="flex-row items-center gap-2">
                    <Ionicons name="receipt" size={16} color="#005bbf" />
                    <Text className="text-on-surface text-sm font-semibold">
                      Transaksi
                    </Text>
                  </View>
                  <Text className="text-on-surface text-base font-bold">
                    {stats.transaksi}×
                  </Text>
                </View>
                <View className="flex-row items-center justify-between p-3 rounded-xl bg-surface">
                  <View className="flex-row items-center gap-2">
                    <Ionicons name="trending-up" size={16} color="#0a7e3e" />
                    <Text className="text-on-surface text-sm font-semibold">
                      Total Omset
                    </Text>
                  </View>
                  <Text className="text-secondary text-base font-bold">
                    {stats.omset}
                  </Text>
                </View>
                <View className="flex-row items-center justify-between p-3 rounded-xl bg-surface">
                  <View className="flex-row items-center gap-2">
                    <Ionicons name="trending-down" size={16} color="#e08a14" />
                    <Text className="text-on-surface text-sm font-semibold">
                      Retur
                    </Text>
                  </View>
                  <Text className="text-tertiary text-base font-bold">
                    Rp 65rb
                  </Text>
                </View>
                <View className="flex-row items-center justify-between p-3 rounded-xl bg-surface">
                  <View className="flex-row items-center gap-2">
                    <Ionicons name="wallet" size={16} color="#7e5a17" />
                    <Text className="text-on-surface text-sm font-semibold">
                      Saldo Kasir
                    </Text>
                  </View>
                  <Text className="text-tertiary text-base font-bold">
                    Rp 2.65jt
                  </Text>
                </View>
              </View>
            </View>
          </View>
        </View>
      </ScrollView>
    </View>
  );
}
