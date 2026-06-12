/** Pembungkus endpoint API per domain fitur. */

import { api, downloadFile } from "./api";
import { setToken, clearToken } from "./auth";
import type { DomainConfig } from "./domain";
import type {
  LoginResponse,
  User,
  Jadwal,
  Laporan,
  Penilaian,
  NotificationFeed,
  Lokasi,
  Unit,
  GuestComplaint,
  LeaderboardResponse,
  AppSettings,
  MonthlyReport,
  Keterlambatan,
  DashboardStatistics,
} from "./types";

export const authService = {
  async login(email: string, password: string): Promise<User> {
    const data = await api.post<LoginResponse>("/auth/login", {
      json: { email, password },
    });
    setToken(data.token);
    return data.user;
  },

  me(): Promise<User> {
    return api.get<User>("/auth/me");
  },

  async logout(): Promise<void> {
    try {
      await api.post("/auth/logout");
    } finally {
      clearToken();
    }
  },
};

/** Sebagian endpoint list mengembalikan array langsung, sebagian {data:[...]}. */
function unwrapList<T>(res: unknown): T[] {
  if (Array.isArray(res)) return res as T[];
  if (res && typeof res === "object" && Array.isArray((res as { data?: T[] }).data)) {
    return (res as { data: T[] }).data;
  }
  return [];
}

export const jadwalService = {
  async today(domain: DomainConfig): Promise<Jadwal[]> {
    return unwrapList<Jadwal>(await api.get(`${domain.jadwalBase}/today`));
  },
  async upcoming(domain: DomainConfig): Promise<Jadwal[]> {
    return unwrapList<Jadwal>(await api.get(`${domain.jadwalBase}/upcoming`));
  },
  show(domain: DomainConfig, id: number): Promise<Jadwal> {
    return api.get<Jadwal>(`${domain.jadwalBase}/${id}`);
  },
  /** Jadwal hari ini gabungan semua domain (untuk dashboard supervisor). */
  async todayAll(domains: DomainConfig[]): Promise<{ domain: DomainConfig; jadwal: Jadwal }[]> {
    const results = await Promise.all(
      domains.map(async (domain) => {
        try {
          const list = await jadwalService.today(domain);
          return list.map((jadwal) => ({ domain, jadwal }));
        } catch {
          return [];
        }
      }),
    );
    return results.flat();
  },
  create(domain: DomainConfig, body: JadwalInput): Promise<Jadwal> {
    return api.post<Jadwal>(domain.jadwalBase, { json: body });
  },
  remove(domain: DomainConfig, id: number): Promise<unknown> {
    return api.delete(`${domain.jadwalBase}/${id}`);
  },
};

export interface JadwalInput {
  petugas_id: number;
  lokasi_id: number;
  tanggal: string;
  shift: string;
  jam_mulai: string;
  jam_selesai: string;
  catatan?: string;
}

export const laporanService = {
  async list(domain: DomainConfig): Promise<Laporan[]> {
    return unwrapList<Laporan>(await api.get(domain.laporanBase));
  },
};

export interface PenilaianInput {
  petugas_id: number;
  periode_bulan: number;
  periode_tahun: number;
  skor_kehadiran: number;
  skor_kualitas: number;
  skor_ketepatan_waktu: number;
  skor_kebersihan: number;
  catatan?: string;
}

export const penilaianService = {
  async list(): Promise<Penilaian[]> {
    return unwrapList<Penilaian>(await api.get("/penilaian"));
  },
  latest(): Promise<Penilaian | null> {
    return api.get<Penilaian | null>("/penilaian/latest");
  },
  create(body: PenilaianInput): Promise<Penilaian> {
    return api.post<Penilaian>("/penilaian", { json: body });
  },
};

export const notificationService = {
  feed(): Promise<NotificationFeed> {
    return api.get<NotificationFeed>("/notifications");
  },
};

/** Item inbox review supervisor: laporan + domain asalnya. */
export interface ReviewItem {
  domain: DomainConfig;
  report: Laporan;
}

export const reviewService = {
  /** Laporan menunggu review (status=submitted) untuk satu domain. */
  async pending(domain: DomainConfig): Promise<Laporan[]> {
    return unwrapList<Laporan>(
      await api.get(domain.laporanBase, { params: { status: "submitted" } }),
    );
  },

  /** Gabungan laporan menunggu review dari semua domain. */
  async pendingAll(domains: DomainConfig[]): Promise<ReviewItem[]> {
    const results = await Promise.all(
      domains.map(async (domain) => {
        try {
          const reports = await reviewService.pending(domain);
          return reports.map((report) => ({ domain, report }));
        } catch {
          return [];
        }
      }),
    );
    return results
      .flat()
      .sort((a, b) => (b.report.tanggal ?? "").localeCompare(a.report.tanggal ?? ""));
  },

  show(domain: DomainConfig, id: number): Promise<Laporan> {
    return api.get<Laporan>(`${domain.laporanBase}/${id}`);
  },

  approve(
    domain: DomainConfig,
    id: number,
    body: { rating?: number; catatan_supervisor?: string },
  ): Promise<unknown> {
    return api.post(`${domain.laporanBase}/${id}/approve`, { json: body });
  },

  reject(
    domain: DomainConfig,
    id: number,
    rejected_reason: string,
  ): Promise<unknown> {
    return api.post(`${domain.laporanBase}/${id}/reject`, {
      json: { rejected_reason },
    });
  },
};

/* ---------- Admin: master data (Tahap 3) ---------- */

export interface LokasiInput {
  unit_id: number;
  kode_lokasi: string;
  nama_lokasi: string;
  kategori: string;
  lantai?: string;
  deskripsi?: string;
  is_active?: boolean;
}

export const lokasiService = {
  async list(): Promise<Lokasi[]> {
    return unwrapList<Lokasi>(await api.get("/lokasi"));
  },
  show(id: number): Promise<Lokasi> {
    return api.get<Lokasi>(`/lokasi/${id}`);
  },
  create(body: LokasiInput): Promise<Lokasi> {
    return api.post<Lokasi>("/lokasi", { json: body });
  },
  update(id: number, body: LokasiInput): Promise<Lokasi> {
    return api.put<Lokasi>(`/lokasi/${id}`, { json: body });
  },
  remove(id: number): Promise<unknown> {
    return api.delete(`/lokasi/${id}`);
  },
};

export interface UnitInput {
  kode_unit: string;
  nama_unit: string;
  deskripsi?: string;
  is_active?: boolean;
}

export const unitService = {
  async list(): Promise<Unit[]> {
    return unwrapList<Unit>(await api.get("/units"));
  },
  create(body: UnitInput): Promise<Unit> {
    return api.post<Unit>("/units", { json: body });
  },
  update(id: number, body: UnitInput): Promise<Unit> {
    return api.put<Unit>(`/units/${id}`, { json: body });
  },
  remove(id: number): Promise<unknown> {
    return api.delete(`/units/${id}`);
  },
};

export const complaintService = {
  async list(status?: string): Promise<GuestComplaint[]> {
    return unwrapList<GuestComplaint>(
      await api.get("/guest-complaints", {
        params: status ? { status } : undefined,
      }),
    );
  },
  assign(id: number, assigned_to: number): Promise<unknown> {
    return api.post(`/guest-complaints/${id}/assign`, { json: { assigned_to } });
  },
  updateStatus(id: number, status: string): Promise<unknown> {
    return api.post(`/guest-complaints/${id}/status`, { json: { status } });
  },
};

export interface UserInput {
  name: string;
  email: string;
  password?: string;
  phone?: string;
  role: string;
  is_active?: boolean;
}

export const userService = {
  async list(role?: string): Promise<User[]> {
    return unwrapList<User>(
      await api.get("/users", { params: role ? { role } : undefined }),
    );
  },
  show(id: number): Promise<User> {
    return api.get<User>(`/users/${id}`);
  },
  roles(): Promise<string[]> {
    return api.get<string[]>("/users/roles");
  },
  create(body: UserInput): Promise<User> {
    return api.post<User>("/users", { json: body });
  },
  update(id: number, body: Partial<UserInput>): Promise<User> {
    return api.put<User>(`/users/${id}`, { json: body });
  },
  remove(id: number): Promise<unknown> {
    return api.delete(`/users/${id}`);
  },
};

export const dashboardService = {
  leaderboard(opts?: {
    role?: string;
    month?: number;
    year?: number;
  }): Promise<LeaderboardResponse> {
    return api.get<LeaderboardResponse>("/dashboard/leaderboard", {
      params: { role: opts?.role, month: opts?.month, year: opts?.year },
    });
  },
  /** Statistik untuk widget analitik beranda (trend status + 12 bulan). */
  statistics(opts?: { start_date?: string; end_date?: string }): Promise<DashboardStatistics> {
    return api.get<DashboardStatistics>("/dashboard/statistics", {
      params: { start_date: opts?.start_date, end_date: opts?.end_date },
    });
  },
};

export const settingService = {
  get(): Promise<AppSettings> {
    return api.get<AppSettings>("/settings");
  },
  update(body: AppSettings): Promise<AppSettings> {
    return api.put<AppSettings>("/settings", { json: body });
  },
};

export interface MonthlyReportParams {
  bulan: number;
  tahun: number;
  unit_id?: number;
  petugas_id?: number;
}

export const reportService = {
  monthly(params: MonthlyReportParams): Promise<MonthlyReport> {
    return api.get<MonthlyReport>("/reports/monthly", { params: { ...params } });
  },
  downloadMonthlyPdf(params: MonthlyReportParams): Promise<void> {
    return downloadFile(
      "/reports/monthly/pdf",
      { ...params },
      `laporan-bulanan-${params.tahun}-${params.bulan}.pdf`,
    );
  },
  /** Export PDF daftar laporan satu domain sesuai filter aktif. */
  downloadListPdf(params: {
    domain: string;
    bulan: number;
    tahun: number;
    status?: string;
    unit_id?: number;
    petugas_id?: number;
  }): Promise<void> {
    return downloadFile(
      "/reports/export/pdf",
      { ...params },
      `laporan-${params.domain}-${params.tahun}-${params.bulan}.pdf`,
    );
  },
};

export const keterlambatanService = {
  async list(filters?: {
    domain?: string;
    status?: string;
    bulan?: number;
    tahun?: number;
  }): Promise<Keterlambatan[]> {
    return unwrapList<Keterlambatan>(
      await api.get("/laporan-keterlambatan", { params: { ...filters } }),
    );
  },
  remove(id: number): Promise<unknown> {
    return api.delete(`/laporan-keterlambatan/${id}`);
  },
};
