import type { UserRole } from "@/stores/auth-store";

export const ROLE_LABEL: Record<UserRole, string> = {
  super_admin: "Super Admin",
  supervisor: "Supervisor",
  pengurus: "Pengurus",
  petugas: "Petugas Kebersihan",
  satpam: "Satpam",
  office_boy: "Office Boy",
  petugas_toko: "Petugas Toko",
};

export const ROLE_ICON: Record<UserRole, string> = {
  super_admin: "admin_panel_settings",
  supervisor: "supervisor_account",
  pengurus: "groups",
  petugas: "cleaning_services",
  satpam: "security",
  office_boy: "cleaning_services",
  petugas_toko: "storefront",
};
