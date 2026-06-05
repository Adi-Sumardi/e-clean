import { useState } from "react";
import { Alert, Pressable, ScrollView, Text, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { LinearGradient } from "expo-linear-gradient";
import { Stack, useRouter } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { FormField } from "@/components/FormField";
import { useAuthStore } from "@/stores/auth-store";
import { ApiError } from "@/lib/api";

export default function ChangePasswordScreen() {
  const router = useRouter();
  const updateProfile = useAuthStore((s) => s.updateProfile);

  const [current, setCurrent] = useState("");
  const [next, setNext] = useState("");
  const [confirm, setConfirm] = useState("");
  const [saving, setSaving] = useState(false);

  const onSave = async () => {
    if (next.length < 8) {
      Alert.alert("Kata sandi lemah", "Kata sandi baru minimal 8 karakter.");
      return;
    }
    if (next !== confirm) {
      Alert.alert("Tidak cocok", "Konfirmasi kata sandi tidak sama.");
      return;
    }
    setSaving(true);
    try {
      await updateProfile({
        current_password: current,
        password: next,
        password_confirmation: confirm,
      });
      Alert.alert("Berhasil", "Kata sandi berhasil diubah.", [
        { text: "OK", onPress: () => router.back() },
      ]);
    } catch (e) {
      const msg =
        e instanceof ApiError && e.errors
          ? Object.values(e.errors).flat().join("\n")
          : e instanceof Error
            ? e.message
            : "Gagal mengubah kata sandi.";
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
            <Text className="text-white font-bold text-lg">Ubah Kata Sandi</Text>
          </View>
        </SafeAreaView>
      </LinearGradient>

      <ScrollView
        contentContainerStyle={{ padding: 20, paddingBottom: 60 }}
        keyboardShouldPersistTaps="handled"
      >
        <View className="bg-primary/5 rounded-2xl p-4 mb-5 flex-row items-center gap-3">
          <Ionicons name="shield-checkmark-outline" size={20} color="#0a5fd6" />
          <Text className="flex-1 text-primary text-xs">
            Gunakan kata sandi minimal 8 karakter. Kamu akan tetap masuk setelah
            mengganti kata sandi.
          </Text>
        </View>

        <FormField
          label="Kata Sandi Saat Ini"
          required
          icon="lock-closed-outline"
          value={current}
          onChangeText={setCurrent}
          placeholder="Kata sandi lama"
          secureTextEntry
          autoCapitalize="none"
        />
        <FormField
          label="Kata Sandi Baru"
          required
          icon="key-outline"
          value={next}
          onChangeText={setNext}
          placeholder="Minimal 8 karakter"
          secureTextEntry
          autoCapitalize="none"
        />
        <FormField
          label="Konfirmasi Kata Sandi Baru"
          required
          icon="key-outline"
          value={confirm}
          onChangeText={setConfirm}
          placeholder="Ulangi kata sandi baru"
          secureTextEntry
          autoCapitalize="none"
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
            {saving ? "Menyimpan..." : "Ubah Kata Sandi"}
          </Text>
        </Pressable>
      </ScrollView>
    </View>
  );
}
