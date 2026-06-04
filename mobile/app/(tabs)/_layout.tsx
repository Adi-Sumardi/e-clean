import { Tabs } from "expo-router";
import { View } from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { useIsTablet } from "@/lib/useIsTablet";
import { useAuthStore } from "@/stores/auth-store";

type IoniconName = React.ComponentProps<typeof Ionicons>["name"];

/** Icon with an active "pill" highlight, matching the floating bar design. */
function TabIcon({
  name,
  focused,
  size,
}: {
  name: IoniconName;
  focused: boolean;
  size: number;
}) {
  return (
    <View
      style={{
        width: 46,
        height: 32,
        borderRadius: 16,
        alignItems: "center",
        justifyContent: "center",
        backgroundColor: focused ? "rgba(10,95,214,0.12)" : "transparent",
      }}
    >
      <Ionicons
        name={name}
        size={size}
        color={focused ? "#0a5fd6" : "#8a9099"}
      />
    </View>
  );
}

export default function TabsLayout() {
  const isTablet = useIsTablet();
  const insets = useSafeAreaInsets();
  const role = useAuthStore((s) => s.user?.role);
  const isAdmin = role === "super_admin";
  const isSupervisor = role === "supervisor";
  const showMenu = isAdmin || isSupervisor;
  const hideFieldTabs = isAdmin || isSupervisor;
  const iconSize = isTablet ? 26 : 22;

  // Lift the floating bar above the system navigation / gesture bar.
  const bottomGap = Math.max(insets.bottom, 10) + 6;

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: "#0a5fd6",
        tabBarInactiveTintColor: "#8a9099",
        tabBarHideOnKeyboard: true,
        tabBarLabelStyle: {
          fontSize: 11,
          fontWeight: "700",
          marginTop: 2,
        },
        tabBarItemStyle: {
          paddingVertical: 6,
        },
        // Solid floating rounded "pill" bar with shadow, spaced from edges + nav bar.
        tabBarStyle: {
          position: "absolute",
          left: 16,
          right: 16,
          bottom: bottomGap,
          height: 66,
          paddingTop: 8,
          paddingBottom: 8,
          borderRadius: 26,
          borderTopWidth: 0,
          backgroundColor: "#ffffff",
          shadowColor: "#0a3aa0",
          shadowOpacity: 0.2,
          shadowRadius: 20,
          shadowOffset: { width: 0, height: 8 },
          elevation: 16,
        },
      }}
    >
      <Tabs.Screen
        name="index"
        options={{
          title: "Beranda",
          tabBarIcon: ({ focused }) => (
            <TabIcon
              name={focused ? "home" : "home-outline"}
              focused={focused}
              size={iconSize}
            />
          ),
        }}
      />
      <Tabs.Screen
        name="menu"
        options={{
          title: "Menu",
          href: showMenu ? "/menu" : null,
          tabBarIcon: ({ focused }) => (
            <TabIcon
              name={focused ? "grid" : "grid-outline"}
              focused={focused}
              size={iconSize}
            />
          ),
        }}
      />
      <Tabs.Screen
        name="tugas"
        options={{
          title: "Tugas",
          href: hideFieldTabs ? null : "/tugas",
          tabBarIcon: ({ focused }) => (
            <TabIcon
              name={focused ? "list-circle" : "list-outline"}
              focused={focused}
              size={iconSize}
            />
          ),
        }}
      />
      <Tabs.Screen
        name="laporan"
        options={{
          title: "Laporan",
          href: hideFieldTabs ? null : "/laporan",
          tabBarIcon: ({ focused }) => (
            <TabIcon
              name={focused ? "camera" : "camera-outline"}
              focused={focused}
              size={iconSize}
            />
          ),
        }}
      />
      <Tabs.Screen
        name="profile"
        options={{
          title: "Profil",
          tabBarIcon: ({ focused }) => (
            <TabIcon
              name={focused ? "person-circle" : "person-circle-outline"}
              focused={focused}
              size={iconSize}
            />
          ),
        }}
      />
    </Tabs>
  );
}
