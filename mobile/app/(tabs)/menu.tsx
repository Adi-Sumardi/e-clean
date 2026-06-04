import { Alert, Pressable, ScrollView, Text, View } from "react-native";
import { useRouter } from "expo-router";
import { Ionicons, MaterialCommunityIcons } from "@expo/vector-icons";
import { useAuthStore } from "@/stores/auth-store";
import { ROLE_LABEL } from "@/constants/role";
import { useIsTablet } from "@/lib/useIsTablet";
import { DashboardHeader } from "@/components/DashboardHeader";

type IoniconName = React.ComponentProps<typeof Ionicons>["name"];

interface MenuTileData {
  key: string;
  label: string;
  icon: IoniconName;
  color: string;
  badge?: string | number;
  href?: string;
  adminOnly?: boolean;
}

interface MenuGroup {
  title: string;
  items: MenuTileData[];
}

const MENU_GROUPS: MenuGroup[] = [
  {
    title: "Master Data",
    items: [
      {
        key: "users",
        label: "Users",
        icon: "people-outline",
        color: "#005bbf",
        adminOnly: true,
        href: "/admin/users",
      },
      {
        key: "petugas",
        label: "Petugas",
        icon: "people-circle-outline",
        color: "#0a7e3e",
        href: "/admin/petugas",
      },
      {
        key: "unit",
        label: "Unit",
        icon: "business-outline",
        color: "#7e5a17",
        href: "/admin/unit",
      },
      {
        key: "lokasi",
        label: "Lokasi",
        icon: "location-outline",
        color: "#0891b2",
        href: "/admin/lokasi",
      },
      {
        key: "settings",
        label: "Pengaturan Aplikasi",
        icon: "settings-outline",
        color: "#5a6072",
        href: "/admin/settings",
      },
    ],
  },
  {
    title: "Monitoring",
    items: [
      {
        key: "laporan-kegiatan",
        label: "Laporan Kegiatan",
        icon: "clipboard-outline",
        color: "#005bbf",
        href: "/admin/laporan-kegiatan",
      },
      {
        key: "laporan-keterlambatan",
        label: "Laporan Keterlambatan",
        icon: "time-outline",
        color: "#e08a14",
        href: "/admin/laporan-keterlambatan",
      },
      {
        key: "laporan-bulanan",
        label: "Laporan Bulanan",
        icon: "document-text-outline",
        color: "#0891b2",
        href: "/admin/laporan-bulanan",
      },
    ],
  },
  {
    title: "Kelola Jadwal",
    items: [
      {
        key: "jadwal-kebersihan",
        label: "Jadwal Kebersihan",
        icon: "sparkles-outline",
        color: "#0a7e3e",
        href: "/admin/jadwal-kebersihan",
      },
      {
        key: "jadwal-satpam",
        label: "Jadwal Satpam",
        icon: "shield-outline",
        color: "#005bbf",
        href: "/admin/jadwal-satpam",
      },
      {
        key: "jadwal-ob",
        label: "Jadwal Office Boy",
        icon: "cafe-outline",
        color: "#7e5a17",
        href: "/admin/jadwal-ob",
      },
      {
        key: "jadwal-toko",
        label: "Jadwal Petugas Toko",
        icon: "storefront-outline",
        color: "#0891b2",
        href: "/admin/jadwal-toko",
      },
    ],
  },
  {
    title: "Approval Laporan",
    items: [
      {
        key: "approval-kebersihan",
        label: "Approval Kebersihan",
        icon: "sparkles-outline",
        color: "#0a7e3e",
        badge: 3,
        href: "/admin/approval-kebersihan",
      },
      {
        key: "approval-satpam",
        label: "Approval Satpam",
        icon: "shield-checkmark-outline",
        color: "#005bbf",
        badge: 2,
        href: "/admin/approval-satpam",
      },
      {
        key: "approval-ob",
        label: "Approval Office Boy",
        icon: "cafe-outline",
        color: "#7e5a17",
        badge: 3,
        href: "/admin/approval-ob",
      },
      {
        key: "approval-toko",
        label: "Approval Petugas Toko",
        icon: "storefront-outline",
        color: "#0891b2",
        badge: 2,
        href: "/admin/approval-toko",
      },
    ],
  },
  {
    title: "Operasional",
    items: [
      {
        key: "keluhan",
        label: "Keluhan Tamu",
        icon: "chatbubble-ellipses-outline",
        color: "#d62828",
        badge: 3,
        href: "/admin/keluhan",
      },
      {
        key: "penilaian",
        label: "Penilaian",
        icon: "star-outline",
        color: "#e08a14",
        href: "/admin/penilaian",
      },
      {
        key: "leaderboard",
        label: "Peringkat Petugas",
        icon: "trophy-outline",
        color: "#7e5a17",
        href: "/admin/leaderboard",
      },
      {
        key: "qr-scan",
        label: "Scan QR Code",
        icon: "qr-code-outline",
        color: "#0a7e3e",
        href: "/admin/qr-scan",
      },
    ],
  },
];

function MenuTile({
  item,
  onPress,
}: {
  item: MenuTileData;
  onPress: () => void;
}) {
  return (
    <Pressable
      onPress={onPress}
      className="rounded-2xl bg-surface-container-lowest border border-outline-variant px-2 py-3 items-center active:opacity-80"
      style={{ minHeight: 96 }}
    >
      <View>
        <View
          className="w-12 h-12 rounded-2xl items-center justify-center mb-2"
          style={{ backgroundColor: `${item.color}1a` }}
        >
          <Ionicons name={item.icon} size={22} color={item.color} />
        </View>
        {item.badge !== undefined ? (
          <View className="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-error items-center justify-center">
            <Text className="text-white text-[9px] font-bold">
              {item.badge}
            </Text>
          </View>
        ) : null}
      </View>
      <Text
        className="text-on-surface font-semibold text-[11px] text-center leading-tight"
        numberOfLines={2}
      >
        {item.label}
      </Text>
    </Pressable>
  );
}

export default function MenuScreen() {
  const router = useRouter();
  const isTablet = useIsTablet();
  const user = useAuthStore((s) => s.user);
  const isAdmin = user?.role === "super_admin";

  const visibleGroups = MENU_GROUPS.map((g) => ({
    ...g,
    items: g.items.filter((it) => !it.adminOnly || isAdmin),
  })).filter((g) => g.items.length > 0);

  const handlePress = (item: MenuTileData) => {
    if (item.href) {
      router.push(item.href as Parameters<typeof router.push>[0]);
      return;
    }
    Alert.alert(
      item.label,
      "Halaman ini sedang dalam pengembangan untuk versi mobile.",
      [{ text: "OK" }]
    );
  };

  const contentPad = isTablet ? 32 : 20;
  const cols = isTablet ? 6 : 4;

  return (
    <View className="flex-1 bg-background">
      <DashboardHeader
        colors={["#0a5fd6", "#0a3aa0"]}
        title={isAdmin ? "Menu Admin" : "Menu Supervisor"}
        subtitle={`${user ? ROLE_LABEL[user.role] : ""} · ${user?.unit?.name ?? "Semua Unit"}`}
        icon={<Ionicons name="grid" size={22} color="#fff" />}
      />

      <ScrollView
        contentContainerStyle={{ padding: contentPad, paddingBottom: 130 }}
        showsVerticalScrollIndicator={false}
      >
        {visibleGroups.map((group, gi) => (
          <View key={group.title} className={gi > 0 ? "mt-6" : ""}>
            <View className="flex-row items-center gap-2 mb-3">
              <View className="w-1 h-5 rounded-full bg-primary" />
              <Text className="font-bold text-on-surface text-base">
                {group.title}
              </Text>
              <View className="px-2 py-0.5 rounded-full bg-primary/10">
                <Text className="text-primary text-[10px] font-bold">
                  {group.items.length}
                </Text>
              </View>
            </View>
            <View className="flex-row flex-wrap -m-1.5">
              {group.items.map((item) => (
                <View
                  key={item.key}
                  className="p-1.5"
                  style={{ width: `${100 / cols}%` }}
                >
                  <MenuTile item={item} onPress={() => handlePress(item)} />
                </View>
              ))}
            </View>
          </View>
        ))}

        <View className="mt-8 bg-primary/5 rounded-2xl p-4 flex-row items-center gap-3">
          <MaterialCommunityIcons
            name="information-outline"
            size={20}
            color="#005bbf"
          />
          <Text className="flex-1 text-primary text-xs">
            Beberapa halaman masih dalam pengembangan untuk versi mobile.
          </Text>
        </View>
      </ScrollView>
    </View>
  );
}
