/** Tipe data inti dari API KopkarYAPI. */

export interface User {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  is_active: boolean;
  roles: string[];
  permissions: string[];
  created_at?: string;
  updated_at?: string;
}

export interface LoginResponse {
  user: User;
  token: string;
  token_type: string;
}

export interface LokasiRef {
  id: number;
  kode_lokasi?: string;
  nama_lokasi: string;
  kategori?: string;
  lantai?: string | null;
  foto?: string | null;
  unit?: { id: number; nama_unit: string } | null;
}

export type JadwalStatus =
  | "pending"
  | "scheduled"
  | "in_progress"
  | "completed"
  | "selesai"
  | string;

/** Jadwal — shape sama untuk kebersihan & domain field (satpam/ob/toko). */
export interface Jadwal {
  id: number;
  tanggal: string | null;
  shift: string | null;
  jam_mulai: string | null;
  jam_selesai: string | null;
  status: JadwalStatus;
  catatan: string | null;
  lokasi?: LokasiRef | null;
  petugas?: { id: number; name: string; phone?: string | null } | null;
}

export type LaporanStatus =
  | "pending"
  | "submitted"
  | "approved"
  | "rejected"
  | string;

export interface PetugasRef {
  id: number;
  name: string;
  phone?: string | null;
}

export interface ChecklistItem {
  item: string;
  done: boolean;
}

/** Laporan — gabungan bidang umum + spesifik per domain (semua opsional). */
export interface Laporan {
  id: number;
  tanggal: string | null;
  jam_mulai?: string | null;
  jam_selesai?: string | null;
  status: LaporanStatus;
  catatan_petugas?: string | null;
  catatan_supervisor?: string | null;
  rejected_reason?: string | null;
  rating?: number | null;
  lokasi?: LokasiRef | null;
  petugas?: PetugasRef | null;
  // Kebersihan
  kegiatan?: string | null;
  foto_sebelum?: string[];
  foto_sesudah?: string[];
  // Satpam
  kondisi?: string | null;
  temuan?: string | null;
  tindakan?: string | null;
  // OB
  jenis_pekerjaan?: string | null;
  uraian?: string | null;
  // Toko
  kondisi_stok?: string | null;
  catatan_stok?: string | null;
  checklist?: ChecklistItem[];
  // Foto generik (satpam/toko)
  foto?: string[];
  created_at?: string;
  updated_at?: string;
}

export interface Penilaian {
  id: number;
  petugas_id?: number;
  periode_bulan: number;
  periode_tahun: number;
  skor_kehadiran: number | null;
  skor_kualitas: number | null;
  skor_ketepatan_waktu: number | null;
  skor_kebersihan: number | null;
  total_skor: number | null;
  rata_rata: number | null;
  kategori: string | null;
  catatan: string | null;
  petugas?: PetugasRef | null;
}

export interface NotificationItem {
  id: string;
  type: string;
  ref_id?: number;
  lokasi_id?: number;
  title: string;
  body: string;
  time: string | null;
  read: boolean;
}

export interface NotificationFeed {
  count: number;
  items: NotificationItem[];
}

export interface Unit {
  id: number;
  kode_unit: string;
  nama_unit: string;
  deskripsi?: string | null;
  is_active?: boolean;
}

export interface Lokasi {
  id: number;
  kode_lokasi: string;
  nama_lokasi: string;
  kategori: string;
  lantai?: string | null;
  deskripsi?: string | null;
  foto?: string | null;
  is_active?: boolean;
  unit?: { id: number; kode_unit?: string; nama_unit: string } | null;
}

export type ComplaintStatus =
  | "pending"
  | "in_progress"
  | "resolved"
  | "rejected"
  | string;

export interface GuestComplaint {
  id: number;
  nama_pelapor?: string | null;
  email_pelapor?: string | null;
  telepon_pelapor?: string | null;
  deskripsi_keluhan: string;
  jenis_keluhan?: string | null;
  foto_keluhan?: string | null;
  catatan_penanganan?: string | null;
  foto_penanganan?: string | null;
  status: ComplaintStatus;
  lokasi?: {
    id: number;
    nama_lokasi: string;
    unit?: { id: number; nama_unit: string } | null;
  } | null;
  assignee?: { id: number; name: string } | null;
  handler?: { id: number; name: string } | null;
  created_at?: string;
}

export interface LeaderboardEntry {
  petugas_id: number;
  name: string;
  total_reports: number;
  approved_reports: number;
  average_rating: number;
  punctuality_rate: number;
  evaluation_score: number;
  evaluation_kategori: string | null;
  overall_score: number;
  rank: number;
}

export interface LeaderboardResponse {
  period: { month: number; year: number };
  leaderboard: LeaderboardEntry[];
}

/* ---------- Admin Tahap 3: settings, laporan bulanan, keterlambatan ---------- */

export interface AppSettings {
  reporting_tolerance_minutes: number;
}

export interface MonthlyPetugasRekap {
  name: string;
  total: number;
  ontime: number;
  late: number;
  expired: number;
  approved: number;
  avg_rating: number;
}

export interface MonthlyUnitRekap {
  unit: string;
  total: number;
  petugas: MonthlyPetugasRekap[];
}

export interface MonthlyStats {
  total: number;
  ontime: number;
  ontime_pct: number;
  late: number;
  late_pct: number;
  expired: number;
  expired_pct: number;
  avg_rating: number;
}

export interface MonthlyReport {
  stats: MonthlyStats;
  units: MonthlyUnitRekap[];
}

/** Laporan keterlambatan — dibuat otomatis sistem saat jadwal terlewat. */
export interface Keterlambatan {
  id: number;
  domain: string;
  tanggal: string | null;
  shift: string | null;
  status: string;
  keterangan: string | null;
  batas_waktu_mulai: string | null;
  batas_waktu_selesai: string | null;
  waktu_terdeteksi: string | null;
  petugas: { id: number; name: string } | null;
  lokasi: {
    id: number;
    nama_lokasi: string;
    unit: { id: number; nama_unit: string } | null;
  } | null;
}

/** Statistik dashboard untuk widget analitik beranda (subset yang dipakai). */
export interface StatusTrendPoint {
  date: string;
  approved: number | string;
  rejected: number | string;
}

export interface MonthlyTrendPoint {
  month: string;
  count: number;
}

export interface DashboardStatistics {
  status_trend: StatusTrendPoint[];
  monthly_trend: MonthlyTrendPoint[];
}
