import { ReactNode } from "react";
import { Pressable, Text, TextInput, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { LinearGradient } from "expo-linear-gradient";
import { useRouter } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { useIsTablet } from "@/lib/useIsTablet";

type IoniconName = React.ComponentProps<typeof Ionicons>["name"];

/** Darken a hex color by `amt` (0..1) for the gradient's second stop. */
function darken(hex: string, amt = 0.28): string {
  const h = hex.replace("#", "");
  const full = h.length === 3 ? h.split("").map((c) => c + c).join("") : h;
  const n = parseInt(full, 16);
  const r = Math.max(0, Math.round(((n >> 16) & 255) * (1 - amt)));
  const g = Math.max(0, Math.round(((n >> 8) & 255) * (1 - amt)));
  const b = Math.max(0, Math.round((n & 255) * (1 - amt)));
  return `#${((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)}`;
}

interface Props {
  title: string;
  subtitle?: string;
  icon: IoniconName;
  color: string;
  onAdd?: () => void;
  addLabel?: string;
  searchValue?: string;
  onSearchChange?: (v: string) => void;
  searchPlaceholder?: string;
  children: ReactNode;
  rightAction?: ReactNode;
  /**
   * Target route when back button tapped. Defaults to "/menu".
   * Pass a specific path to override (e.g. "/admin/approval-kebersihan").
   * Pass `null` to use router.back() with /menu fallback.
   */
  backHref?: string | null;
}

export function AdminScreen({
  title,
  subtitle,
  icon,
  color,
  onAdd,
  addLabel = "Tambah",
  searchValue,
  onSearchChange,
  searchPlaceholder = "Cari...",
  children,
  rightAction,
  backHref = "/menu",
}: Props) {
  const router = useRouter();
  const isTablet = useIsTablet();

  const headerPad = isTablet ? "px-8" : "px-5";
  const showSearch = onSearchChange !== undefined;

  const onBack = () => {
    // null = use native back stack (e.g. detail screen that may come from
    // various parents); fall back to /menu if stack is empty.
    if (backHref === null) {
      if (router.canGoBack()) {
        router.back();
      } else {
        router.replace("/menu");
      }
      return;
    }
    // String = explicit target. Use replace so admin screens don't pile up
    // on the navigation stack.
    router.replace(backHref as Parameters<typeof router.replace>[0]);
  };

  return (
    <View className="flex-1 bg-background">
      {/* Gradient header */}
      <LinearGradient
        colors={[color, darken(color)]}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={{
          borderBottomLeftRadius: 28,
          borderBottomRightRadius: 28,
          paddingBottom: showSearch ? 32 : 16,
          overflow: "hidden",
        }}
      >
        {/* Decorative circle */}
        <View
          style={{
            position: "absolute",
            top: -50,
            right: -30,
            width: 160,
            height: 160,
            borderRadius: 80,
            backgroundColor: "rgba(255,255,255,0.08)",
          }}
        />
        <SafeAreaView edges={["top"]}>
          <View className={`flex-row items-center gap-3 ${headerPad} h-16`}>
            <Pressable
              onPress={onBack}
              className="w-10 h-10 rounded-full items-center justify-center active:opacity-70"
              style={{ backgroundColor: "rgba(255,255,255,0.15)" }}
            >
              <Ionicons name="arrow-back" size={22} color="#ffffff" />
            </Pressable>
            <View
              className="w-10 h-10 rounded-xl items-center justify-center"
              style={{ backgroundColor: "rgba(255,255,255,0.2)" }}
            >
              <Ionicons name={icon} size={isTablet ? 24 : 20} color="#ffffff" />
            </View>
            <View className="flex-1">
              <Text
                className={`font-bold text-white ${isTablet ? "text-xl" : "text-base"}`}
                numberOfLines={1}
              >
                {title}
              </Text>
              {subtitle ? (
                <Text className="text-white/75 text-xs" numberOfLines={1}>
                  {subtitle}
                </Text>
              ) : null}
            </View>
            {rightAction}
            {onAdd ? (
              <Pressable
                onPress={onAdd}
                className="px-3 h-10 rounded-xl flex-row items-center gap-1 active:opacity-90"
                style={{ backgroundColor: "rgba(255,255,255,0.22)" }}
              >
                <Ionicons name="add" size={18} color="#ffffff" />
                <Text className="text-white text-xs font-bold">{addLabel}</Text>
              </Pressable>
            ) : null}
          </View>
        </SafeAreaView>
      </LinearGradient>

      {/* Search — floating card overlapping the gradient */}
      {showSearch && (
        <View className={`${headerPad}`} style={{ marginTop: -24 }}>
          <View
            className="flex-row items-center bg-surface-container-lowest rounded-2xl px-3 h-12"
            style={{
              shadowColor: "#000",
              shadowOpacity: 0.1,
              shadowRadius: 10,
              shadowOffset: { width: 0, height: 4 },
              elevation: 4,
            }}
          >
            <Ionicons name="search-outline" size={18} color="#5a6072" />
            <TextInput
              value={searchValue}
              onChangeText={onSearchChange}
              placeholder={searchPlaceholder}
              placeholderTextColor="#c1c6d6"
              className="flex-1 ml-2 text-on-surface"
              style={{ fontSize: 14 }}
            />
            {searchValue && searchValue.length > 0 && (
              <Pressable
                onPress={() => onSearchChange?.("")}
                className="w-6 h-6 items-center justify-center"
              >
                <Ionicons name="close-circle" size={16} color="#5a6072" />
              </Pressable>
            )}
          </View>
        </View>
      )}

      {children}
    </View>
  );
}

interface EmptyStateProps {
  icon: IoniconName;
  title: string;
  description?: string;
}

export function EmptyState({ icon, title, description }: EmptyStateProps) {
  return (
    <View className="items-center mt-20">
      <Ionicons name={icon} size={64} color="#c1c6d6" />
      <Text className="text-on-surface font-semibold mt-3">{title}</Text>
      {description ? (
        <Text className="text-on-surface-variant text-xs mt-1 text-center px-8">
          {description}
        </Text>
      ) : null}
    </View>
  );
}
