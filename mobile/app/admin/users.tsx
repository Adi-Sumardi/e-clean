import { useMemo, useState } from "react";
import {
  ActivityIndicator,
  Alert,
  Pressable,
  ScrollView,
  Text,
  View,
} from "react-native";
import { Stack } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { AdminScreen, EmptyState } from "@/components/admin/AdminScreen";
import {
  EntityFormModal,
  type FieldDef,
  type FormValues,
} from "@/components/admin/EntityFormModal";
import { useIsTablet } from "@/lib/useIsTablet";
import { ROLE_LABEL } from "@/constants/role";
import type { UserRole } from "@/stores/auth-store";
import {
  useUsers,
  useUserRoles,
  useCreateUser,
  useUpdateUser,
  useDeleteUser,
} from "@/lib/hooks";
import { ApiError } from "@/lib/api";
import type { ManagedUser } from "@/lib/services";

const ROLE_COLOR: Record<string, string> = {
  super_admin: "#d62828",
  supervisor: "#7e5a17",
  pengurus: "#0891b2",
  petugas: "#0a7e3e",
  satpam: "#005bbf",
  office_boy: "#7e5a17",
  petugas_toko: "#0891b2",
};

function roleLabel(role?: string) {
  return role && role in ROLE_LABEL
    ? ROLE_LABEL[role as UserRole]
    : (role ?? "-");
}

function showApiError(err: unknown) {
  const msg =
    err instanceof ApiError && err.errors
      ? Object.values(err.errors).flat().join("\n")
      : err instanceof Error
        ? err.message
        : "Terjadi kesalahan.";
  Alert.alert("Gagal", msg);
}

export default function UsersScreen() {
  const isTablet = useIsTablet();
  const [q, setQ] = useState("");
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<ManagedUser | null>(null);

  const { data, isLoading } = useUsers();
  const { data: roles } = useUserRoles();
  const createUser = useCreateUser();
  const updateUser = useUpdateUser();
  const deleteUser = useDeleteUser();

  const users = data ?? [];
  const filtered = useMemo(() => {
    const s = q.toLowerCase().trim();
    if (!s) return users;
    return users.filter(
      (u) =>
        u.name.toLowerCase().includes(s) ||
        u.email.toLowerCase().includes(s) ||
        roleLabel(u.roles[0]).toLowerCase().includes(s)
    );
  }, [q, users]);

  const roleOptions = useMemo(
    () => (roles ?? []).map((r) => ({ value: r, label: roleLabel(r) })),
    [roles]
  );

  const fields = useMemo<FieldDef[]>(
    () => [
      { key: "name", label: "Nama", type: "text", required: true },
      { key: "email", label: "Email", type: "text", required: true, keyboardType: "email-address" },
      {
        key: "password",
        label: editing ? "Password (kosongkan jika tetap)" : "Password",
        type: "password",
        required: !editing,
      },
      { key: "phone", label: "Telepon", type: "text", keyboardType: "phone-pad" },
      { key: "role", label: "Role", type: "select", required: true, options: roleOptions },
      { key: "is_active", label: "Aktif", type: "switch" },
    ],
    [editing, roleOptions]
  );

  const openCreate = () => {
    setEditing(null);
    setModalOpen(true);
  };
  const openEdit = (u: ManagedUser) => {
    setEditing(u);
    setModalOpen(true);
  };

  const onSubmit = (values: FormValues) => {
    const payload = {
      name: String(values.name),
      email: String(values.email),
      password: values.password ? String(values.password) : undefined,
      phone: values.phone ? String(values.phone) : undefined,
      role: String(values.role),
      is_active: Boolean(values.is_active),
    };
    const onDone = { onSuccess: () => setModalOpen(false), onError: showApiError };
    if (editing) {
      updateUser.mutate({ id: editing.id, data: payload }, onDone);
    } else {
      createUser.mutate(payload, onDone);
    }
  };

  const confirmDelete = (u: ManagedUser) =>
    Alert.alert("Hapus User", `Hapus "${u.name}"?`, [
      { text: "Batal", style: "cancel" },
      {
        text: "Hapus",
        style: "destructive",
        onPress: () => deleteUser.mutate(u.id, { onError: showApiError }),
      },
    ]);

  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <AdminScreen
        title="Users"
        subtitle={`${users.length} pengguna terdaftar`}
        icon="people-outline"
        color="#005bbf"
        searchValue={q}
        onSearchChange={setQ}
        searchPlaceholder="Cari nama / email / role..."
        onAdd={openCreate}
        addLabel="User"
      >
        <ScrollView
          contentContainerStyle={{ padding: isTablet ? 32 : 20, paddingBottom: 40 }}
        >
          {isLoading ? (
            <View className="items-center py-16">
              <ActivityIndicator color="#005bbf" />
            </View>
          ) : filtered.length === 0 ? (
            <EmptyState
              icon="search-outline"
              title="Tidak ditemukan"
              description="Coba kata kunci lain"
            />
          ) : (
            <View className={isTablet ? "flex-row flex-wrap -m-2" : "gap-3"}>
              {filtered.map((u) => (
                <View key={u.id} className={isTablet ? "w-1/2 p-2" : ""}>
                  <UserCard
                    user={u}
                    onPress={() => openEdit(u)}
                    onDelete={() => confirmDelete(u)}
                  />
                </View>
              ))}
            </View>
          )}
        </ScrollView>
      </AdminScreen>

      <EntityFormModal
        visible={modalOpen}
        title={editing ? "Edit User" : "Tambah User"}
        fields={fields}
        initialValues={
          editing
            ? {
                name: editing.name,
                email: editing.email,
                password: "",
                phone: editing.phone ?? "",
                role: editing.roles[0] ?? "",
                is_active: editing.is_active,
              }
            : undefined
        }
        submitting={createUser.isPending || updateUser.isPending}
        onCancel={() => setModalOpen(false)}
        onSubmit={onSubmit}
      />
    </>
  );
}

function UserCard({
  user,
  onPress,
  onDelete,
}: {
  user: ManagedUser;
  onPress: () => void;
  onDelete: () => void;
}) {
  const role = user.roles[0] ?? "petugas";
  const roleColor = ROLE_COLOR[role] ?? "#5a6072";
  const isActive = user.is_active;
  return (
    <Pressable
      onPress={onPress}
      onLongPress={onDelete}
      className="p-4 rounded-2xl bg-surface-container-lowest border border-outline-variant active:opacity-80"
    >
      <View className="flex-row items-center gap-3">
        <View
          className="w-12 h-12 rounded-full items-center justify-center"
          style={{ backgroundColor: `${roleColor}1a` }}
        >
          <Text className="font-bold text-base" style={{ color: roleColor }}>
            {user.name.charAt(0).toUpperCase()}
          </Text>
        </View>
        <View className="flex-1">
          <Text className="font-bold text-on-surface" numberOfLines={1}>
            {user.name}
          </Text>
          <Text className="text-on-surface-variant text-xs" numberOfLines={1}>
            {user.email}
          </Text>
        </View>
        <View
          className={`px-2 py-0.5 rounded-full ${
            isActive ? "bg-secondary/15" : "bg-on-surface-variant/15"
          }`}
        >
          <Text
            className={`text-[10px] font-bold ${
              isActive ? "text-secondary" : "text-on-surface-variant"
            }`}
          >
            {isActive ? "Aktif" : "Nonaktif"}
          </Text>
        </View>
      </View>
      <View className="flex-row items-center justify-between mt-3 pt-3 border-t border-outline-variant/50">
        <View
          className="px-2 py-1 rounded-full flex-row items-center gap-1"
          style={{ backgroundColor: `${roleColor}1a` }}
        >
          <Ionicons name="shield-checkmark" size={10} color={roleColor} />
          <Text className="text-[10px] font-bold" style={{ color: roleColor }}>
            {roleLabel(role)}
          </Text>
        </View>
        <Pressable onPress={onDelete} hitSlop={8} className="flex-row items-center gap-1">
          <Ionicons name="trash-outline" size={14} color="#d62828" />
          <Text className="text-error text-xs font-bold">Hapus</Text>
        </Pressable>
      </View>
    </Pressable>
  );
}
