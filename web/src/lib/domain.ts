/**
 * Pemetaan role petugas → domain (endpoint + label).
 *
 * Satu basis kode melayani 4 jenis petugas lapangan. PWA membaca role user dari
 * /auth/me lalu memilih konfigurasi domain yang sesuai, sehingga layar jadwal &
 * laporan memakai endpoint yang benar tanpa cabang if-else tersebar.
 */

export type PetugasRole = "petugas" | "satpam" | "office_boy" | "petugas_toko";

export interface DomainConfig {
  /** Kunci internal. */
  key: "kebersihan" | "satpam" | "ob" | "toko";
  /** Label tampil di UI. */
  label: string;
  /** Nama role (Spatie) untuk domain ini. */
  role: PetugasRole;
  /** Endpoint daftar/aksi jadwal (tanpa /api/v1). */
  jadwalBase: string;
  /** Endpoint submit/daftar laporan (tanpa /api/v1). */
  laporanBase: string;
}

export const DOMAINS: Record<PetugasRole, DomainConfig> = {
  petugas: {
    key: "kebersihan",
    label: "Kebersihan",
    role: "petugas",
    jadwalBase: "/jadwal",
    laporanBase: "/activity-reports",
  },
  satpam: {
    key: "satpam",
    label: "Keamanan",
    role: "satpam",
    jadwalBase: "/satpam/jadwal",
    laporanBase: "/satpam/laporan",
  },
  office_boy: {
    key: "ob",
    label: "Office Boy",
    role: "office_boy",
    jadwalBase: "/office-boy/jadwal",
    laporanBase: "/office-boy/laporan",
  },
  petugas_toko: {
    key: "toko",
    label: "Toko",
    role: "petugas_toko",
    jadwalBase: "/toko/jadwal",
    laporanBase: "/toko/laporan",
  },
};

const PETUGAS_ROLES: PetugasRole[] = [
  "petugas",
  "satpam",
  "office_boy",
  "petugas_toko",
];

/** Ambil konfigurasi domain dari daftar role user (role petugas pertama yang cocok). */
export function resolveDomain(roles: string[]): DomainConfig | null {
  const match = roles.find((r): r is PetugasRole =>
    PETUGAS_ROLES.includes(r as PetugasRole),
  );
  return match ? DOMAINS[match] : null;
}

const MANAGER_ROLES = ["supervisor", "pengurus", "admin", "super_admin"];

/** Apakah user pemegang peran peninjau (supervisor/admin)? */
export function isManager(roles: string[]): boolean {
  return roles.some((r) => MANAGER_ROLES.includes(r));
}

const ADMIN_ROLES = ["admin", "super_admin"];

/** Apakah user admin (boleh kelola master data)? */
export function isAdmin(roles: string[]): boolean {
  return roles.some((r) => ADMIN_ROLES.includes(r));
}

/** Label ramah untuk nama role. */
export const ROLE_LABELS: Record<string, string> = {
  super_admin: "Super Admin",
  admin: "Admin",
  supervisor: "Supervisor",
  pengurus: "Pengurus",
  petugas: "Petugas Kebersihan",
  satpam: "Satpam",
  office_boy: "Office Boy",
  petugas_toko: "Petugas Toko",
};

export function roleLabel(role: string): string {
  return ROLE_LABELS[role] ?? role;
}

/** Semua domain untuk inbox review supervisor (lintas domain). */
export const REVIEW_DOMAINS: DomainConfig[] = [
  DOMAINS.petugas,
  DOMAINS.satpam,
  DOMAINS.office_boy,
  DOMAINS.petugas_toko,
];

/** Cari DomainConfig dari kunci internal (kebersihan/satpam/ob/toko). */
export function domainByKey(key: string | null): DomainConfig | null {
  return REVIEW_DOMAINS.find((d) => d.key === key) ?? null;
}
