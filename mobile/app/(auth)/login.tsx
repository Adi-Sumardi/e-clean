import { useEffect, useState } from "react";
import {
  ActivityIndicator,
  Dimensions,
  Image,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { LinearGradient } from "expo-linear-gradient";
import { StatusBar } from "expo-status-bar";
import { Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";
import * as Linking from "expo-linking";
import Constants from "expo-constants";
import { useLocalSearchParams } from "expo-router";
import { useAuthStore } from "@/stores/auth-store";
import { TOKEN_KEY } from "@/lib/api";
import { storage } from "@/lib/storage";

const APP_ICON = require("../../assets/icons/app_icon.png");
const { width } = Dimensions.get("window");

const ROLE_CHIPS = [
  { icon: "broom", label: "Kebersihan", lib: "mci" as const },
  { icon: "shield-account", label: "Satpam", lib: "mci" as const },
  { icon: "coffee-outline", label: "Office Boy", lib: "mci" as const },
  { icon: "storefront-outline", label: "Toko", lib: "mci" as const },
];

export default function LoginScreen() {
  const login = useAuthStore((s) => s.login);
  const status = useAuthStore((s) => s.status);
  const error = useAuthStore((s) => s.error);
  const hydrate = useAuthStore((s) => s.hydrate);

  const { token, error: urlError } = useLocalSearchParams<{
    token?: string;
    error?: string;
  }>();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [localError, setLocalError] = useState<string | null>(null);

  const loading = status === "loading";

  useEffect(() => {
    if (urlError) setLocalError(decodeURIComponent(urlError));
  }, [urlError]);

  useEffect(() => {
    if (token) {
      const handleGoogleToken = async () => {
        try {
          setLocalError(null);
          await storage.setItem(TOKEN_KEY, token);
          await hydrate();
        } catch (e: any) {
          setLocalError("Gagal memproses login Google: " + (e?.message || e));
        }
      };
      void handleGoogleToken();
    }
  }, [token, hydrate]);

  const onSubmit = async () => {
    setLocalError(null);
    if (!email || !password) {
      setLocalError("Email dan kata sandi wajib diisi.");
      return;
    }
    try {
      await login(email.trim(), password);
    } catch (err: any) {
      setLocalError(err?.message ?? "Login gagal.");
    }
  };

  const onGoogleLogin = async () => {
    try {
      setLocalError(null);
      const apiUrl =
        process.env.EXPO_PUBLIC_API_URL ??
        (Constants.expoConfig?.extra as { apiUrl?: string } | undefined)?.apiUrl ??
        "http://10.0.2.2:8000";
      await Linking.openURL(`${apiUrl}/auth/google?platform=mobile`);
    } catch (err: any) {
      setLocalError(err?.message ?? "Gagal membuka Google login.");
    }
  };

  return (
    <View className="flex-1 bg-background">
      <StatusBar style="light" />
      <KeyboardAvoidingView
        behavior={Platform.OS === "ios" ? "padding" : undefined}
        className="flex-1"
      >
        <ScrollView
          contentContainerStyle={{ flexGrow: 1 }}
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}
        >
          {/* ---------------- Hero ---------------- */}
          <LinearGradient
            colors={["#0a5fd6", "#0a3aa0", "#06246b"]}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={{
              paddingTop: 64,
              paddingBottom: 72,
              borderBottomLeftRadius: 36,
              borderBottomRightRadius: 36,
              overflow: "hidden",
            }}
          >
            {/* Decorative translucent circles */}
            <View
              style={{
                position: "absolute",
                top: -60,
                right: -40,
                width: 200,
                height: 200,
                borderRadius: 100,
                backgroundColor: "rgba(255,255,255,0.08)",
              }}
            />
            <View
              style={{
                position: "absolute",
                top: 40,
                left: -50,
                width: 150,
                height: 150,
                borderRadius: 75,
                backgroundColor: "rgba(255,255,255,0.06)",
              }}
            />
            <View
              style={{
                position: "absolute",
                bottom: 20,
                right: width * 0.3,
                width: 90,
                height: 90,
                borderRadius: 45,
                backgroundColor: "rgba(255,255,255,0.05)",
              }}
            />

            <SafeAreaView edges={["top"]}>
              <View className="items-center px-6">
                <View
                  style={{
                    width: 88,
                    height: 88,
                    borderRadius: 24,
                    backgroundColor: "#ffffff",
                    alignItems: "center",
                    justifyContent: "center",
                    shadowColor: "#000",
                    shadowOpacity: 0.25,
                    shadowRadius: 12,
                    shadowOffset: { width: 0, height: 6 },
                    elevation: 8,
                  }}
                >
                  <Image
                    source={APP_ICON}
                    style={{ width: 64, height: 64, borderRadius: 14 }}
                    resizeMode="contain"
                  />
                </View>
                <Text className="text-white text-3xl font-bold mt-4 tracking-tight">
                  ServiceGO
                </Text>
                <Text className="text-white/75 text-sm mt-1">
                  Smart Operational Management
                </Text>

                {/* Role chips */}
                <View className="flex-row flex-wrap justify-center gap-2 mt-5">
                  {ROLE_CHIPS.map((r) => (
                    <View
                      key={r.label}
                      className="flex-row items-center gap-1.5 px-3 py-1.5 rounded-full"
                      style={{ backgroundColor: "rgba(255,255,255,0.15)" }}
                    >
                      <MaterialCommunityIcons
                        name={r.icon as never}
                        size={13}
                        color="#fff"
                      />
                      <Text className="text-white text-xs font-semibold">
                        {r.label}
                      </Text>
                    </View>
                  ))}
                </View>
              </View>
            </SafeAreaView>
          </LinearGradient>

          {/* ---------------- Form card (overlapping hero) ---------------- */}
          <View className="px-6" style={{ marginTop: -40 }}>
            <View
              className="bg-surface-container-lowest rounded-3xl p-6"
              style={{
                shadowColor: "#0a3aa0",
                shadowOpacity: 0.12,
                shadowRadius: 20,
                shadowOffset: { width: 0, height: 8 },
                elevation: 6,
              }}
            >
              <Text className="text-xl font-bold text-on-surface mb-1">
                Selamat Datang 👋
              </Text>
              <Text className="text-on-surface-variant text-sm mb-5">
                Masuk untuk melanjutkan ke akun Anda
              </Text>

              {/* Email */}
              <Text className="text-on-surface font-semibold mb-2">Email</Text>
              <View className="flex-row items-center h-12 px-3 bg-surface border border-outline rounded-xl mb-4">
                <Ionicons name="mail-outline" size={18} color="#5a6072" />
                <TextInput
                  value={email}
                  onChangeText={setEmail}
                  placeholder="nama@perusahaan.com"
                  placeholderTextColor="#c1c6d6"
                  autoCapitalize="none"
                  keyboardType="email-address"
                  autoComplete="email"
                  editable={!loading}
                  className="flex-1 ml-2 text-on-surface"
                />
              </View>

              {/* Password */}
              <View className="flex-row justify-between items-center mb-2">
                <Text className="text-on-surface font-semibold">Kata Sandi</Text>
                <Pressable>
                  <Text className="text-primary font-semibold text-sm">Lupa?</Text>
                </Pressable>
              </View>
              <View className="flex-row items-center h-12 px-3 bg-surface border border-outline rounded-xl">
                <Ionicons name="lock-closed-outline" size={18} color="#5a6072" />
                <TextInput
                  value={password}
                  onChangeText={setPassword}
                  placeholder="••••••••"
                  placeholderTextColor="#c1c6d6"
                  secureTextEntry={!showPassword}
                  autoComplete="password"
                  editable={!loading}
                  className="flex-1 ml-2 text-on-surface"
                />
                <Pressable onPress={() => setShowPassword((v) => !v)} hitSlop={8}>
                  <Ionicons
                    name={showPassword ? "eye-off-outline" : "eye-outline"}
                    size={20}
                    color="#5a6072"
                  />
                </Pressable>
              </View>

              {(localError || error) && (
                <View className="mt-3 p-3 bg-error/10 border border-error/30 rounded-xl flex-row items-center gap-2">
                  <Ionicons name="alert-circle" size={16} color="#d62828" />
                  <Text className="text-error text-sm flex-1">
                    {localError ?? error}
                  </Text>
                </View>
              )}

              {/* Masuk */}
              <Pressable
                onPress={onSubmit}
                disabled={loading}
                className="mt-5 rounded-xl overflow-hidden active:opacity-90"
                style={{ opacity: loading ? 0.7 : 1 }}
              >
                <LinearGradient
                  colors={["#0a5fd6", "#0a3aa0"]}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 0 }}
                  style={{
                    height: 52,
                    alignItems: "center",
                    justifyContent: "center",
                    flexDirection: "row",
                    gap: 8,
                  }}
                >
                  {loading ? (
                    <ActivityIndicator color="#fff" />
                  ) : (
                    <>
                      <Text className="text-white font-bold text-base">Masuk</Text>
                      <Ionicons name="arrow-forward" size={18} color="#fff" />
                    </>
                  )}
                </LinearGradient>
              </Pressable>

              {/* Divider */}
              <View className="flex-row items-center my-4">
                <View className="flex-1 h-[1px] bg-outline-variant" />
                <Text className="text-on-surface-variant text-xs px-3 font-medium">
                  Atau masuk dengan
                </Text>
                <View className="flex-1 h-[1px] bg-outline-variant" />
              </View>

              {/* Google */}
              <Pressable
                onPress={onGoogleLogin}
                disabled={loading}
                className="h-12 rounded-xl border border-outline-variant bg-surface items-center justify-center flex-row gap-2 active:opacity-80"
              >
                <Ionicons name="logo-google" size={18} color="#EA4335" />
                <Text className="text-on-surface font-semibold text-base">
                  Google
                </Text>
              </Pressable>
            </View>

            <Text className="text-center text-on-surface-variant my-6 text-sm">
              Belum punya akun? Hubungi admin Anda.
            </Text>
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </View>
  );
}
