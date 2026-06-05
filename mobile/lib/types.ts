/**
 * TypeScript mirrors of the Laravel API Resources (app/Http/Resources/*).
 * Keep these in sync with the backend response shapes.
 */

export type UserRole =
  | "super_admin"
  | "supervisor"
  | "pengurus"
  | "petugas"
  | "satpam"
  | "office_boy"
  | "petugas_toko";

/** Raw user as returned by UserResource (roles is an array of role names). */
export interface ApiUser {
  id: number;
  name: string;
  email: string;
  phone?: string | null;
  roles: string[];
  permissions: string[];
  created_at?: string;
  updated_at?: string;
}

export interface AuthPayload {
  user: ApiUser;
  token: string;
  token_type: string;
}

export interface Lokasi {
  id: number;
  kode_lokasi: string;
  nama_lokasi: string;
  kategori?: string | null;
  lantai?: string | null;
  deskripsi?: string | null;
  foto?: string | null;
  is_active: boolean;
  unit?: { id: number; kode_unit: string; nama_unit: string } | null;
  created_at?: string;
  updated_at?: string;
}

export interface PetugasRef {
  id: number;
  name: string;
  phone?: string | null;
}

export type ShiftKey =
  | "pagi"
  | "siang"
  | "sore"
  | "malam"
  | "standby"
  | "sweeping";

export type JadwalStatus = "pending" | "in_progress" | "completed" | "missed";

export interface Jadwal {
  id: number;
  tanggal: string; // Y-m-d
  shift: ShiftKey | string;
  jam_mulai: string; // H:i
  jam_selesai: string; // H:i
  status: JadwalStatus | string;
  catatan?: string | null;
  petugas?: PetugasRef;
  lokasi?: Lokasi;
  created_at?: string;
  updated_at?: string;
}

export type ReportStatus =
  | "draft"
  | "submitted"
  | "approved"
  | "rejected";

export interface ActivityReport {
  id: number;
  tanggal: string;
  jam_mulai: string;
  jam_selesai: string;
  kegiatan: string;
  foto_sebelum: string[];
  foto_sesudah: string[];
  koordinat_lokasi?: string | null;
  status: ReportStatus | string;
  catatan_petugas?: string | null;
  catatan_supervisor?: string | null;
  rating?: number | null;
  rejected_reason?: string | null;
  approved_at?: string | null;
  petugas?: PetugasRef;
  lokasi?: Lokasi;
  jadwal?: Jadwal;
  approver?: { id: number; name: string } | null;
  created_at?: string;
  updated_at?: string;
}

/** Payload for creating an activity report (multipart). */
export interface CreateActivityReportInput {
  jadwal_id: number;
  lokasi_id: number;
  tanggal: string; // Y-m-d
  jam_mulai: string; // H:i
  jam_selesai: string; // H:i
  kegiatan: string;
  foto_sebelum: string[]; // local image uris
  foto_sesudah: string[]; // local image uris
  koordinat_lokasi?: string;
  catatan_petugas?: string;
  status?: "draft" | "submitted";
}

export interface Penilaian {
  id: number;
  periode_bulan: number;
  periode_tahun: number;
  skor_kehadiran: number;
  skor_kualitas: number;
  skor_ketepatan_waktu: number;
  skor_kebersihan: number;
  total_skor: number;
  rata_rata: number;
  kategori?: string | null;
  catatan?: string | null;
  petugas?: PetugasRef & { email?: string };
  penilai?: { id: number; name: string; email?: string };
  created_at?: string;
  updated_at?: string;
}

export interface Unit {
  id: number;
  kode_unit: string;
  nama_unit: string;
  deskripsi?: string | null;
  alamat?: string | null;
  penanggung_jawab?: string | null;
  telepon?: string | null;
  is_active?: boolean;
  lokasi_count?: number;
}

/** Reference to a unit as embedded in report resources. */
export interface UnitRef {
  id: number;
  kode_unit: string;
  nama_unit: string;
}

/**
 * The field-staff domains that flow through the supervisor approval queue.
 * "kebersihan" maps to the existing ActivityReport domain.
 */
export type ApprovalScope = "kebersihan" | "satpam" | "ob" | "toko";

/** Normalized approval row shown in the supervisor queue, across all domains. */
export interface ApprovalItem {
  id: number;
  scope: ApprovalScope;
  tanggal: string;
  status: string;
  petugasName: string;
  lokasiName: string;
  unit: UnitRef | null;
  summary: string;
  createdAt?: string;
}

export interface NotificationItem {
  id: string;
  type:
    | "approval"
    | "guest_complaint"
    | "report_approved"
    | "report_rejected"
    | string;
  scope?: ApprovalScope;
  ref_id?: number;
  lokasi_id?: number;
  title: string;
  body: string;
  time?: string;
  read?: boolean;
}

/** Loose typing for dashboard/statistics — backend shape is dynamic. */
export type DashboardStatistics = Record<string, unknown>;

export interface LeaderboardEntry {
  id?: number;
  name?: string;
  [key: string]: unknown;
}

/** Map the backend roles[] array to the single primary UserRole used in UI. */
const ROLE_PRIORITY: UserRole[] = [
  "super_admin",
  "pengurus",
  "supervisor",
  "petugas",
  "satpam",
  "office_boy",
  "petugas_toko",
];

export function primaryRole(roles: string[] | undefined): UserRole {
  if (!roles?.length) return "petugas";
  for (const r of ROLE_PRIORITY) {
    if (roles.includes(r)) return r;
  }
  return (roles[0] as UserRole) ?? "petugas";
}

export interface GuestComplaint {
  id: number;
  nama_pelapor: string;
  email_pelapor?: string | null;
  telepon_pelapor?: string | null;
  jenis_keluhan: string;
  deskripsi_keluhan: string;
  foto_keluhan?: string | null;
  status: "pending" | "in_progress" | "resolved" | "rejected";
  assigned_to?: number | null;
  assigned_at?: string | null;
  handled_by?: number | null;
  handled_at?: string | null;
  catatan_penanganan?: string | null;
  foto_penanganan?: string | null;
  created_at: string;
  updated_at: string;
  lokasi?: Lokasi;
  assignee?: {
    id: number;
    name: string;
  };
  handler?: {
    id: number;
    name: string;
  };
}
