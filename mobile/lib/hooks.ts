import {
  useQuery,
  useMutation,
  useQueryClient,
} from "@tanstack/react-query";
import {
  jadwalService,
  type JadwalFilters,
  activityReportService,
  type ActivityReportFilters,
  lokasiService,
  penilaianService,
  dashboardService,
  unitService,
  approvalService,
  fieldService,
  userService,
  notificationService,
  type FieldScope,
  type ActivityReportFilters as ReportFilters,
  type LokasiInput,
  type UnitInput,
  type UserInput,
  type PenilaianInput,
} from "./services";
import { useEffect, useState } from "react";
import {
  submitActivityReport,
  submitFieldReport,
  subscribeQueue,
} from "./offline-queue";
import type {
  CreateActivityReportInput,
  ApprovalScope,
} from "./types";

/** Central registry of query keys for cache invalidation. */
export const qk = {
  jadwal: ["jadwal"] as const,
  jadwalToday: ["jadwal", "today"] as const,
  jadwalUpcoming: ["jadwal", "upcoming"] as const,
  reports: ["activity-reports"] as const,
  reportStats: ["activity-reports", "statistics"] as const,
  lokasi: ["lokasi"] as const,
  penilaian: ["penilaian"] as const,
  dashboard: ["dashboard"] as const,
  leaderboard: ["dashboard", "leaderboard"] as const,
  units: ["units"] as const,
  approvals: ["approvals"] as const,
};

/* ----------------------------------------------------------------- jadwal */

export const useJadwalToday = () =>
  useQuery({ queryKey: qk.jadwalToday, queryFn: jadwalService.today });

export const useJadwalUpcoming = () =>
  useQuery({ queryKey: qk.jadwalUpcoming, queryFn: jadwalService.upcoming });

export const useJadwal = (filters?: JadwalFilters) =>
  useQuery({
    queryKey: [...qk.jadwal, filters ?? {}],
    queryFn: () => jadwalService.list(filters),
  });

/* -------------------------------------------------------- activity reports */

export const useActivityReports = (filters?: ActivityReportFilters) =>
  useQuery({
    queryKey: [...qk.reports, filters ?? {}],
    queryFn: () => activityReportService.list(filters),
  });

export const useActivityReport = (id: number) =>
  useQuery({
    queryKey: [...qk.reports, id],
    queryFn: () => activityReportService.show(id),
    enabled: Number.isFinite(id),
  });

export const useCreateActivityReport = () => {
  const qc = useQueryClient();
  return useMutation({
    // Routes through the offline queue: resolves 'sent' or 'queued'.
    mutationFn: (input: CreateActivityReportInput) =>
      submitActivityReport(input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: qk.reports });
      qc.invalidateQueries({ queryKey: qk.jadwal });
      qc.invalidateQueries({ queryKey: qk.dashboard });
    },
  });
};

/* ----------------------------------------------------------------- lokasi */

export const useLokasi = () =>
  useQuery({
    queryKey: qk.lokasi,
    queryFn: () => lokasiService.list(),
    staleTime: 15 * 60 * 1000, // 15 minutes
    gcTime: 30 * 60 * 1000,    // 30 minutes
  });

/* -------------------------------------------------------------- penilaian */

export const usePenilaianLatest = () =>
  useQuery({
    queryKey: [...qk.penilaian, "latest"],
    queryFn: penilaianService.latest,
  });

export const usePenilaianHistory = () =>
  useQuery({
    queryKey: [...qk.penilaian, "history"],
    queryFn: penilaianService.history,
  });

/* -------------------------------------------------------------- dashboard */

export const useDashboard = () =>
  useQuery({ queryKey: qk.dashboard, queryFn: dashboardService.index });

export const useLeaderboard = () =>
  useQuery({
    queryKey: qk.leaderboard,
    queryFn: dashboardService.leaderboard,
  });

/* ------------------------------------------------------------------ units */

export const useUnits = () =>
  useQuery({
    queryKey: qk.units,
    queryFn: unitService.list,
    staleTime: 15 * 60 * 1000, // 15 minutes
    gcTime: 30 * 60 * 1000,    // 30 minutes
  });

/* -------------------------------------------------------------- approvals */

/** Supervisor approval queue for a scope ("all" or one domain), per unit. */
export const usePendingApprovals = (
  scope: ApprovalScope | "all",
  unitId?: number | null
) =>
  useQuery({
    queryKey: [...qk.approvals, scope, unitId ?? "all"],
    queryFn: () => approvalService.list(scope, { unitId }),
  });

export const useApprovalReportDetail = (
  scope: ApprovalScope | undefined,
  id: number
) =>
  useQuery({
    queryKey: [...qk.approvals, scope, "detail", id],
    queryFn: () => approvalService.show(scope!, id),
    enabled: !!scope && Number.isFinite(id) && id > 0,
  });

export const useApproveReport = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (vars: {
      scope: ApprovalScope;
      id: number;
      rating?: number;
      catatan_supervisor?: string;
    }) =>
      approvalService.approve(vars.scope, vars.id, {
        rating: vars.rating,
        catatan_supervisor: vars.catatan_supervisor,
      }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: qk.approvals });
      qc.invalidateQueries({ queryKey: qk.dashboard });
    },
  });
};

export const useRejectReport = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (vars: { scope: ApprovalScope; id: number; reason: string }) =>
      approvalService.reject(vars.scope, vars.id, vars.reason),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: qk.approvals });
      qc.invalidateQueries({ queryKey: qk.dashboard });
    },
  });
};

/* ---------------------------------------------- field-staff domains (CRUD) */

/** Today's schedule for a field-staff domain (satpam / OB / toko). */
export const useFieldJadwalToday = (scope: FieldScope) =>
  useQuery({
    queryKey: ["field", scope, "jadwal", "today"],
    queryFn: () => fieldService.jadwalToday(scope),
  });

/** List of schedules for a field-staff domain. */
export const useFieldJadwalList = (scope: FieldScope, filters?: { date?: string; start_date?: string; end_date?: string }) =>
  useQuery({
    queryKey: ["field", scope, "jadwal", "list", filters ?? {}],
    queryFn: () => fieldService.jadwalList(scope, filters),
  });

/** Submit a field-staff report (multipart with photos). */
export const useCreateFieldLaporan = (scope: FieldScope) => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (vars: {
      fields: Record<string, unknown>;
      photos?: Record<string, string[]>;
    }) => submitFieldReport(scope, vars.fields, vars.photos ?? {}),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["field", scope] });
      qc.invalidateQueries({ queryKey: qk.approvals });
    },
  });
};

/* ------------------------------------------------------------ offline sync */

/* --------------------------------------------------------- notifications */

export const useNotifications = () =>
  useQuery({
    queryKey: ["notifications"],
    queryFn: notificationService.list,
    refetchInterval: 60_000,
  });

/** Reactive count of report submissions waiting to be synced. */
export const usePendingSyncCount = (): number => {
  const [count, setCount] = useState(0);
  useEffect(() => subscribeQueue(setCount), []);
  return count;
};

/* ---------------------------------------------------- penilaian & reports */

export const usePenilaianList = (params?: {
  periode_bulan?: number;
  periode_tahun?: number;
}) =>
  useQuery({
    queryKey: [...qk.penilaian, "list", params ?? {}],
    queryFn: () => penilaianService.list(params),
  });

export const useCreatePenilaian = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: PenilaianInput) => penilaianService.create(data),
    onSuccess: () => qc.invalidateQueries({ queryKey: qk.penilaian }),
  });
};

/** Monthly activity reports (for the laporan-bulanan screen). */
export const useMonthlyReports = (filters: ReportFilters) =>
  useQuery({
    queryKey: [...qk.reports, "monthly", filters],
    queryFn: () =>
      activityReportService.list({ per_page: "all", ...filters }),
  });

/* -------------------------------------------------- master-data management */

const masterKeys = {
  lokasi: ["lokasi", "manage"] as const,
  units: qk.units,
  users: ["users"] as const,
  userRoles: ["users", "roles"] as const,
};

export const useManagedLokasi = (params?: {
  unit_id?: number;
  include_inactive?: boolean;
  search?: string;
}) =>
  useQuery({
    queryKey: [...masterKeys.lokasi, params ?? {}],
    queryFn: () => lokasiService.list({ include_inactive: true, ...params }),
    staleTime: 15 * 60 * 1000, // 15 minutes
    gcTime: 30 * 60 * 1000,    // 30 minutes
  });

export const useCreateLokasi = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: LokasiInput) => lokasiService.create(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["lokasi"] });
    },
  });
};

export const useUpdateLokasi = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (vars: { id: number; data: Partial<LokasiInput> }) =>
      lokasiService.update(vars.id, vars.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["lokasi"] }),
  });
};

export const useDeleteLokasi = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => lokasiService.remove(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["lokasi"] }),
  });
};

export const useCreateUnit = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: UnitInput) => unitService.create(data),
    onSuccess: () => qc.invalidateQueries({ queryKey: qk.units }),
  });
};

export const useUpdateUnit = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (vars: { id: number; data: Partial<UnitInput> }) =>
      unitService.update(vars.id, vars.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: qk.units }),
  });
};

export const useDeleteUnit = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => unitService.remove(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: qk.units }),
  });
};

export const useUsers = (params?: {
  role?: string;
  search?: string;
  active_only?: boolean;
}) =>
  useQuery({
    queryKey: [...masterKeys.users, params ?? {}],
    queryFn: () => userService.list(params),
  });

export const useUserRoles = () =>
  useQuery({
    queryKey: masterKeys.userRoles,
    queryFn: userService.roles,
    staleTime: 15 * 60 * 1000, // 15 minutes
    gcTime: 30 * 60 * 1000,    // 30 minutes
  });

export const useCreateUser = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: UserInput) => userService.create(data),
    onSuccess: () => qc.invalidateQueries({ queryKey: masterKeys.users }),
  });
};

export const useUpdateUser = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (vars: { id: number; data: Partial<UserInput> }) =>
      userService.update(vars.id, vars.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: masterKeys.users }),
  });
};

export const useDeleteUser = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => userService.remove(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: masterKeys.users }),
  });
};

/* ----------------------------------------------------- jadwal management */

export const useCreateJadwal = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: import("./services").JadwalInput) =>
      jadwalService.create(data),
    onSuccess: () => qc.invalidateQueries({ queryKey: qk.jadwal }),
  });
};

export const useDeleteJadwal = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => jadwalService.remove(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: qk.jadwal }),
  });
};
