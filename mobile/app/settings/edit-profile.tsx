import { useState } from "react";
import { Alert, Pressable, ScrollView, Text, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { LinearGradient } from "expo-linear-gradient";
import { Stack, useRouter } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { FormField } from "@/components/FormField";
import { useAuthStore } from "@/stores/auth-store";
import { ApiError } from "@/lib/api";

export default function EditProfileScreen() {
  const router = useRouter();
  const user = useAuthStore((s) => s.user);
  const updateProfile = useAuthStore((s) => s.updateProfile);

  const [name, setName] = useState(user?.name ?? "");
  const [phone, setPhone] = useState(user?.phone ?? "");
  const [saving, setSaving] = useState(false);

  const onSave = async () => {
    if (name.trim().length < 2) {
      Alert.alert("Nama tidak valid", "Nama minimal 2 karakter.");
      return;
    }
    setSaving(true);
    try {
      await updateProfile({ name: name.trim(), phone: phone.trim() });
      Alert.alert("Berhasil", "Profil berhasil diperbarui.", [
        { text: "OK", onPress: () => router.back() },
      ]);
    } catch (e) {
      const msg =
        e instanceof ApiError && e.errors
          ? Object.values(e.errors).flat().join("\n")
          : e instanceof Error
            ? e.message
            : "Gagal memperbarui profil.";
      Alert.alert("Gagal", msg);
    } finally {
      setSaving(false);
    }
  };

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
            <Text className="text-white font-bold text-lg">Ubah Profil</Text>
          </View>
        </SafeAreaView>
      </LinearGradient>

      <ScrollView
        contentContainerStyle={{ padding: 20, paddingBottom: 60 }}
        keyboardShouldPersistTaps="handled"
      >
        <View className="bg-surface-container-lowest rounded-2xl p-4 mb-5 flex-row items-center gap-3 border border-outline-variant">
          <View className="w-12 h-12 rounded-full bg-primary/10 items-center justify-center">
            <Ionicons name="mail-outline" size={20} color="#0a5fd6" />
          </View>
          <View className="flex-1">
            <Text className="text-on-surface-variant text-xs">Email (tidak dapat diubah)</Text>
            <Text className="text-on-surface font-semibold" numberOfLines={1}>
              {user?.email ?? "-"}
            </Text>
          </View>
        </View>

        <FormField
          label="Nama Lengkap"
          required
          icon="person-outline"
          value={name}
          onChangeText={setName}
          placeholder="Nama lengkap"
          autoCapitalize="words"
        />
        <FormField
          label="Nomor Telepon"
          icon="call-outline"
          value={phone}
          onChangeText={setPhone}
          placeholder="08xxxxxxxxxx"
          keyboardType="phone-pad"
        />

        <Pressable
          onPress={onSave}
          disabled={saving}
          className="mt-2 h-12 rounded-xl items-center justify-center flex-row gap-2"
          style={{ backgroundColor: saving ? "#7da8e0" : "#0a5fd6" }}
        >
          <Ionicons
            name={saving ? "hourglass-outline" : "checkmark"}
            size={18}
            color="#fff"
          />
          <Text className="text-white font-bold text-base">
            {saving ? "Menyimpan..." : "Simpan Perubahan"}
          </Text>
        </Pressable>
      </ScrollView>
    </View>
  );
}
