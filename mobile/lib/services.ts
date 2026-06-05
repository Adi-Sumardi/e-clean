import { api, request, toApiError, toFormData, filePart } from "./api";
import type {
  ApiUser,
  AuthPayload,
  Lokasi,
  Jadwal,
  ActivityReport,
  CreateActivityReportInput,
  Penilaian,
  DashboardStatistics,
  LeaderboardEntry,
  Unit,
  ApprovalScope,
  ApprovalItem,
} from "./types";

/* ------------------------------------------------------------------ auth */

export const authService = {
  login: (email: string, password: string) =>
    request<AuthPayload>({
      method: "POST",
      url: "/auth/login",
      data: { email, password },
    }),

  me: () => request<ApiUser>({ method: "GET", url: "/auth/me" }),

  logout: () => request<null>({ method: "POST", url: "/auth/logout" }),

  updateProfile: (data: {
    name?: string;
    phone?: string;
    current_password?: string;
    password?: string;
    password_confirmation?: string;
  }) => request<ApiUser>({ method: "PUT", url: "/auth/profile", data }),

  registerPushToken: (expo_push_token: string) =>
    request<null>({ method: "POST", url: "/auth/push-token", data: { expo_push_token } }),

  unregisterPushToken: () =>
    request<null>({ method: "DELETE", url: "/auth/push-token" }),
};

/* ---------------------------------------------------------------- lokasi */

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
  list: (params?: { unit_id?: number; include_inactive?: boolean; search?: string }) =>
    request<Lokasi[]>({ method: "GET", url: "/lokasi", params }),
  show: (id: number) => request<Lokasi>({ method: "GET", url: `/lokasi/${id}` }),
  create: (data: LokasiInput) =>
    request<Lokasi>({ method: "POST", url: "/lokasi", data }),
  update: (id: number, data: Partial<LokasiInput>) =>
    request<Lokasi>({ method: "PUT", url: `/lokasi/${id}`, data }),
  remove: (id: number) =>
    request<null>({ method: "DELETE", url: `/lokasi/${id}` }),
};

/* ---------------------------------------------------------------- jadwal */

export interface JadwalFilters {
  date?: string;
  start_date?: string;
  end_date?: string;
  status?: string;
  shift?: string;
}

export interface JadwalInput {
  petugas_id: number;
  lokasi_id: number;
  tanggal: string; // Y-m-d
  shift: string;
  jam_mulai: string; // H:i
  jam_selesai: string; // H:i
  catatan?: string;
  status?: string;
}

export const jadwalService = {
  list: (params?: JadwalFilters) =>
    request<Jadwal[]>({ method: "GET", url: "/jadwal", params }),
  today: () => request<Jadwal[]>({ method: "GET", url: "/jadwal/today" }),
  upcoming: () =>
    request<Jadwal[]>({ method: "GET", url: "/jadwal/upcoming" }),
  show: (id: number) =>
    request<Jadwal>({ method: "GET", url: `/jadwal/${id}` }),
  create: (data: JadwalInput) =>
    request<Jadwal>({ method: "POST", url: "/jadwal", data }),
  update: (id: number, data: Partial<JadwalInput>) =>
    request<Jadwal>({ method: "PUT", url: `/jadwal/${id}`, data }),
  remove: (id: number) =>
    request<null>({ method: "DELETE", url: `/jadwal/${id}` }),
};

/* ------------------------------------------------------- activity reports */

export interface ActivityReportFilters {
  status?: string;
  lokasi_id?: number;
  unit_id?: number;
  petugas_id?: number;
  month?: number;
  year?: number;
  start_date?: string;
  end_date?: string;
  per_page?: number | "all";
}

export const activityReportService = {
  list: (params?: ActivityReportFilters) =>
    request<ActivityReport[]>({
      method: "GET",
      url: "/activity-reports",
      params,
    }),

  show: (id: number) =>
    request<ActivityReport>({
      method: "GET",
      url: `/activity-reports/${id}`,
    }),

  statistics: () =>
    request<Record<string, unknown>>({
      method: "GET",
      url: "/activity-reports/statistics",
    }),

  /** Create a report with before/after photos (multipart/form-data). */
  async create(input: CreateActivityReportInput): Promise<ActivityReport> {
    const { foto_sebelum, foto_sesudah, ...rest } = input;
    const form = toFormData(rest as Record<string, unknown>);
    foto_sebelum.forEach((uri, i) =>
      form.append("foto_sebelum[]", filePart(uri, `sebelum_${i}`))
    );
    foto_sesudah.forEach((uri, i) =>
      form.append("foto_sesudah[]", filePart(uri, `sesudah_${i}`))
    );
    try {
      const res = await api.post("/activity-reports", form, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      return res.data.data as ActivityReport;
    } catch (err) {
      throw toApiError(err);
    }
  },

  remove: (id: number) =>
    request<null>({ method: "DELETE", url: `/activity-reports/${id}` }),
};

/* ------------------------------------------------------------ penilaian */

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
  list: (params?: { periode_bulan?: number; periode_tahun?: number; per_page?: "all" }) =>
    request<Penilaian[]>({ method: "GET", url: "/penilaian", params: { per_page: "all", ...params } }),
  latest: () =>
    request<Penilaian | null>({ method: "GET", url: "/penilaian/latest" }),
  history: () =>
    request<Penilaian[]>({ method: "GET", url: "/penilaian/history" }),
  statistics: () =>
    request<Record<string, unknown>>({
      method: "GET",
      url: "/penilaian/statistics",
    }),
  show: (id: number) =>
    request<Penilaian>({ method: "GET", url: `/penilaian/${id}` }),
  create: (data: PenilaianInput) =>
    request<Penilaian>({ method: "POST", url: "/penilaian", data }),
};

/* ------------------------------------------------------------ dashboard */

export const dashboardService = {
  index: () =>
    request<DashboardStatistics>({ method: "GET", url: "/dashboard" }),
  statistics: () =>
    request<DashboardStatistics>({
      method: "GET",
      url: "/dashboard/statistics",
    }),
  leaderboard: async (): Promise<LeaderboardEntry[]> => {
    // Backend wraps the array as { period, leaderboard: [...] }.
    const res = await request<{ leaderboard?: LeaderboardEntry[] }>({
      method: "GET",
      url: "/dashboard/leaderboard",
    });
    return res?.leaderboard ?? [];
  },
};

/* ------------------------------------------------------------------ units */

export interface UnitInput {
  kode_unit: string;
  nama_unit: string;
  deskripsi?: string;
  alamat?: string;
  penanggung_jawab?: string;
  telepon?: string;
  is_active?: boolean;
}

export interface NotificationFeed {
  count: number;
  items: import("./types").NotificationItem[];
}

export const notificationService = {
  list: () => request<NotificationFeed>({ method: "GET", url: "/notifications" }),
};

export const unitService = {
  list: () => request<Unit[]>({ method: "GET", url: "/units" }),
  create: (data: UnitInput) =>
    request<Unit>({ method: "POST", url: "/units", data }),
  update: (id: number, data: Partial<UnitInput>) =>
    request<Unit>({ method: "PUT", url: `/units/${id}`, data }),
  remove: (id: number) => request<null>({ method: "DELETE", url: `/units/${id}` }),
};

/* ------------------------------------------------------------------ users */

export interface ManagedUser {
  id: number;
  name: string;
  email: string;
  phone?: string | null;
  is_active: boolean;
  roles: string[];
}

export interface UserInput {
  name: string;
  email: string;
  password?: string;
  phone?: string;
  role: string;
  is_active?: boolean;
}

export const userService = {
  list: (params?: { role?: string; search?: string; active_only?: boolean }) =>
    request<ManagedUser[]>({ method: "GET", url: "/users", params }),
  roles: () => request<string[]>({ method: "GET", url: "/users/roles" }),
  create: (data: UserInput) =>
    request<ManagedUser>({ method: "POST", url: "/users", data }),
  update: (id: number, data: Partial<UserInput>) =>
    request<ManagedUser>({ method: "PUT", url: `/users/${id}`, data }),
  remove: (id: number) => request<null>({ method: "DELETE", url: `/users/${id}` }),
};

/* -------------------------------------------------------------- approvals */

/** Per-domain endpoint base + how to summarize each report row. */
const APPROVAL_ENDPOINTS: Record<
  ApprovalScope,
  { base: string; listParams?: Record<string, unknown>; summarize: (r: any) => string }
> = {
  kebersihan: {
    base: "/activity-reports",
    listParams: { per_page: "all" },
    summarize: (r) => r.kegiatan ?? "Laporan kebersihan",
  },
  satpam: {
    base: "/satpam/laporan",
    summarize: (r) =>
      r.temuan?.trim() ? `Temuan: ${r.temuan}` : `Kondisi: ${r.kondisi ?? "-"}`,
  },
  ob: {
    base: "/office-boy/laporan",
    summarize: (r) => r.jenis_pekerjaan || r.uraian || "Laporan office boy",
  },
  toko: {
    base: "/toko/laporan",
    summarize: (r) =>
      r.kondisi_stok ? `Stok: ${r.kondisi_stok}` : "Checklist toko",
  },
};

function normalizeApproval(scope: ApprovalScope, r: any): ApprovalItem {
  const unit = r.unit ?? r.lokasi?.unit ?? null;
  return {
    id: r.id,
    scope,
    tanggal: r.tanggal,
    status: r.status,
    petugasName: r.petugas?.name ?? "-",
    lokasiName: r.lokasi?.nama_lokasi ?? "-",
    unit: unit ? { id: unit.id, kode_unit: unit.kode_unit, nama_unit: unit.nama_unit } : null,
    summary: APPROVAL_ENDPOINTS[scope].summarize(r),
    createdAt: r.created_at,
  };
}

export interface ApprovalFilters {
  unitId?: number | null;
}

/* ----------------------------------------------- field-staff domains (CRUD) */

/** The three non-cleaning field domains with dedicated backend endpoints. */
export type FieldScope = "satpam" | "ob" | "toko";

const FIELD_BASE: Record<FieldScope, string> = {
  satpam: "/satpam",
  ob: "/office-boy",
  toko: "/toko",
};

export const fieldService = {
  jadwalList: (scope: FieldScope, params?: { date?: string; start_date?: string; end_date?: string }) =>
    request<Jadwal[]>({
      method: "GET",
      url: `${FIELD_BASE[scope]}/jadwal`,
      params,
    }),

  jadwalToday: (scope: FieldScope) =>
    request<Jadwal[]>({
      method: "GET",
      url: `${FIELD_BASE[scope]}/jadwal/today`,
    }),

  /**
   * Create a field-staff report (multipart). `fields` holds scalar values;
   * `photos` maps each photo field name to an array of local image uris.
   */
  async createLaporan(
    scope: FieldScope,
    fields: Record<string, unknown>,
    photos: Record<string, string[]> = {}
  ): Promise<unknown> {
    const form = toFormData(fields);
    for (const [field, uris] of Object.entries(photos)) {
      uris.forEach((uri, i) => form.append(`${field}[]`, filePart(uri, `${field}_${i}`)));
    }
    try {
      const res = await api.post(`${FIELD_BASE[scope]}/laporan`, form, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      return res.data.data;
    } catch (err) {
      throw toApiError(err);
    }
  },
};

export const approvalService = {
  /** Fetch submitted (pending) reports for one domain, optionally per unit. */
  async listScope(
    scope: ApprovalScope,
    filters: ApprovalFilters = {}
  ): Promise<ApprovalItem[]> {
    const cfg = APPROVAL_ENDPOINTS[scope];
    const params: Record<string, unknown> = {
      status: "submitted",
      ...(cfg.listParams ?? {}),
    };
    if (filters.unitId) params.unit_id = filters.unitId;

    const rows = await request<any[]>({
      method: "GET",
      url: cfg.base,
      params,
    });
    return (rows ?? []).map((r) => normalizeApproval(scope, r));
  },

  /** Fetch pending reports for all domains (or one) and merge, newest first. */
  async list(
    scope: ApprovalScope | "all",
    filters: ApprovalFilters = {}
  ): Promise<ApprovalItem[]> {
    const scopes: ApprovalScope[] =
      scope === "all" ? ["kebersihan", "satpam", "ob", "toko"] : [scope];
    const results = await Promise.all(
      scopes.map((s) => approvalService.listScope(s, filters))
    );
    return results
      .flat()
      .sort((a, b) => (b.createdAt ?? "").localeCompare(a.createdAt ?? ""));
  },

  show(scope: ApprovalScope, id: number) {
    return request<any>({
      method: "GET",
      url: `${APPROVAL_ENDPOINTS[scope].base}/${id}`,
    });
  },

  approve(
    scope: ApprovalScope,
    id: number,
    payload: { rating?: number; catatan_supervisor?: string } = {}
  ) {
    return request<unknown>({
      method: "POST",
      url: `${APPROVAL_ENDPOINTS[scope].base}/${id}/approve`,
      data: payload,
    });
  },

  reject(scope: ApprovalScope, id: number, rejected_reason: string) {
    return request<unknown>({
      method: "POST",
      url: `${APPROVAL_ENDPOINTS[scope].base}/${id}/reject`,
      data: { rejected_reason },
    });
  },
};
