import { useState } from "react";
import { Alert, Pressable, ScrollView, Switch, Text, View } from "react-native";
import { Stack } from "expo-router";
import { Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";
import { AdminScreen } from "@/components/admin/AdminScreen";
import { FormField } from "@/components/FormField";
import { FormSelect, type SelectOption } from "@/components/FormSelect";
import { useIsTablet } from "@/lib/useIsTablet";

const PROVIDER_OPTIONS: SelectOption[] = [
  { value: "watzap", label: "WatZap" },
  { value: "twilio", label: "Twilio" },
  { value: "fonnte", label: "Fonnte" },
];

const TIMEZONE_OPTIONS: SelectOption[] = [
  { value: "Asia/Jakarta", label: "Asia/Jakarta (WIB)" },
  { value: "Asia/Makassar", label: "Asia/Makassar (WITA)" },
  { value: "Asia/Jayapura", label: "Asia/Jayapura (WIT)" },
];

export default function SettingsScreen() {
  const isTablet = useIsTablet();
  const [appName, setAppName] = useState("e-Office Kopkaryapi");
  const [appUrl, setAppUrl] = useState("https://css.kopkaryapi.id");
  const [provider, setProvider] = useState<string | number | null>("watzap");
  const [tz, setTz] = useState<string | number | null>("Asia/Jakarta");
  const [waActive, setWaActive] = useState(true);
  const [autoApprove, setAutoApprove] = useState(false);
  const [lateThreshold, setLateThreshold] = useState("15");
  const [retentionDays, setRetentionDays] = useState("30");

  const onSave = () =>
    Alert.alert("Berhasil", "Pengaturan aplikasi berhasil disimpan.");

  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <AdminScreen
        title="Pengaturan Aplikasi"
        subtitle="Konfigurasi sistem"
        icon="settings-outline"
        color="#5a6072"
      >
        <ScrollView
          contentContainerStyle={{ padding: isTablet ? 32 : 20, paddingBottom: 60 }}
          keyboardShouldPersistTaps="handled"
        >
          <View className={isTablet ? "flex-row gap-6" : ""}>
            {/* LEFT */}
            <View className={isTablet ? "flex-1" : ""}>
              {/* App Info */}
              <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-5">
                <View className="flex-row items-center gap-2 mb-3">
                  <Ionicons name="information-circle" size={20} color="#005bbf" />
                  <Text className="font-bold text-on-surface">Informasi Aplikasi</Text>
                </View>
                <FormField
                  label="Nama Aplikasi"
                  icon="apps-outline"
                  value={appName}
                  onChangeText={setAppName}
                />
                <FormField
                  label="URL Aplikasi"
                  icon="link-outline"
                  value={appUrl}
                  onChangeText={setAppUrl}
                />
                <FormSelect
                  label="Timezone"
                  icon="time-outline"
                  value={tz}
                  options={TIMEZONE_OPTIONS}
                  onChange={setTz}
                />
              </View>

              {/* Approval / Reporting */}
              <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-5">
                <View className="flex-row items-center gap-2 mb-3">
                  <Ionicons name="clipboard-outline" size={20} color="#0a7e3e" />
                  <Text className="font-bold text-on-surface">Pelaporan & Approval</Text>
                </View>
                <FormField
                  label="Batas Telat (menit)"
                  icon="alarm-outline"
                  value={lateThreshold}
                  onChangeText={setLateThreshold}
                  keyboardType="numeric"
                  hint="Setelah berapa menit lewat jadwal dianggap terlambat"
                />
                <FormField
                  label="Retensi Data (hari)"
                  icon="archive-outline"
                  value={retentionDays}
                  onChangeText={setRetentionDays}
                  keyboardType="numeric"
                  hint="Lama data ditampilkan di list (default 30 hari)"
                />
                <ToggleRow
                  icon="checkmark-circle-outline"
                  label="Auto-Approve Laporan"
                  description="Setujui otomatis laporan dengan rating ≥ 4"
                  value={autoApprove}
                  onChange={setAutoApprove}
                />
              </View>
            </View>

            {/* RIGHT */}
            <View className={isTablet ? "flex-1" : ""}>
              {/* WhatsApp */}
              <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-5">
                <View className="flex-row items-center gap-2 mb-3">
                  <MaterialCommunityIcons
                    name="whatsapp"
                    size={20}
                    color="#0a7e3e"
                  />
                  <Text className="font-bold text-on-surface">Notifikasi WhatsApp</Text>
                </View>
                <ToggleRow
                  icon="notifications-outline"
                  label="Aktifkan Notifikasi WhatsApp"
                  description="Kirim notifikasi keluhan & laporan ke supervisor"
                  value={waActive}
                  onChange={setWaActive}
                />
                {waActive && (
                  <>
                    <FormSelect
                      label="Provider"
                      icon="cloud-outline"
                      value={provider}
                      options={PROVIDER_OPTIONS}
                      onChange={setProvider}
                    />
                    <View className="bg-primary/5 rounded-xl p-3 flex-row items-center gap-2 mt-2">
                      <Ionicons name="information-circle" size={16} color="#005bbf" />
                      <Text className="text-primary text-xs flex-1">
                        API key dapat diatur di backend Filament.
                      </Text>
                    </View>
                  </>
                )}
              </View>

              {/* System Status */}
              <View className="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-5">
                <View className="flex-row items-center gap-2 mb-3">
                  <Ionicons name="pulse-outline" size={20} color="#005bbf" />
                  <Text className="font-bold text-on-surface">Status Sistem</Text>
                </View>
                <StatusRow label="Versi Aplikasi" value="1.4.2" />
                <StatusRow label="Versi Database" value="PostgreSQL 15.3" />
                <StatusRow label="PHP" value="8.3.1" />
                <StatusRow label="Backup Terakhir" value="Hari ini 03:00" />
                <StatusRow
                  label="Storage Tersisa"
                  value="38% (152 GB)"
                  valueColor="#e08a14"
                />
              </View>

              {/* Save button */}
              <Pressable
                onPress={onSave}
                className="h-12 rounded-xl bg-primary items-center justify-center flex-row gap-2 active:opacity-90"
              >
                <Ionicons name="save" size={18} color="#ffffff" />
                <Text className="text-white font-bold">Simpan Perubahan</Text>
              </Pressable>
            </View>
          </View>
        </ScrollView>
      </AdminScreen>
    </>
  );
}

function ToggleRow({
  icon,
  label,
  description,
  value,
  onChange,
}: {
  icon: React.ComponentProps<typeof Ionicons>["name"];
  label: string;
  description?: string;
  value: boolean;
  onChange: (v: boolean) => void;
}) {
  return (
    <View className="flex-row items-center gap-3 py-3">
      <View className="w-10 h-10 rounded-xl bg-primary/10 items-center justify-center">
        <Ionicons name={icon} size={18} color="#005bbf" />
      </View>
      <View className="flex-1">
        <Text className="text-on-surface font-semibold">{label}</Text>
        {description ? (
          <Text className="text-on-surface-variant text-xs mt-0.5">
            {description}
          </Text>
        ) : null}
      </View>
      <Switch
        value={value}
        onValueChange={onChange}
        trackColor={{ false: "#c1c6d6", true: "#005bbf" }}
        thumbColor="#ffffff"
      />
    </View>
  );
}

function StatusRow({
  label,
  value,
  valueColor,
}: {
  label: string;
  value: string;
  valueColor?: string;
}) {
  return (
    <View className="flex-row items-center justify-between py-2 border-b border-outline-variant/50">
      <Text className="text-on-surface-variant text-sm">{label}</Text>
      <Text
        className="text-sm font-bold"
        style={{ color: valueColor ?? "#1a1c1e" }}
      >
        {value}
      </Text>
    </View>
  );
}
