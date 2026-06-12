"use client";

import { useQuery } from "@tanstack/react-query";
import {
  authService,
  jadwalService,
  laporanService,
  penilaianService,
  notificationService,
  reviewService,
} from "./services";
import {
  resolveDomain,
  isManager,
  isAdmin,
  REVIEW_DOMAINS,
  type DomainConfig,
} from "./domain";
import {
  lokasiService,
  unitService,
  complaintService,
  userService,
  dashboardService,
} from "./services";
import { isAuthenticated } from "./auth";

/** User saat ini (dari /auth/me) + domain hasil pemetaan role. */
export function useMe() {
  const query = useQuery({
    queryKey: ["me"],
    queryFn: authService.me,
    enabled: isAuthenticated(),
    staleTime: 10 * 60 * 1000,
  });

  const roles = query.data?.roles ?? [];
  const domain = query.data ? resolveDomain(roles) : null;
  const manager = query.data ? isManager(roles) : false;
  const admin = query.data ? isAdmin(roles) : false;
  return { ...query, domain, manager, admin };
}

/* ---------- Admin master-data hooks (Tahap 3) ---------- */

export function useLokasiList(enabled = true) {
  return useQuery({
    queryKey: ["lokasi"],
    queryFn: lokasiService.list,
    enabled: enabled && isAuthenticated(),
  });
}

export function useUnitList(enabled = true) {
  return useQuery({
    queryKey: ["units"],
    queryFn: unitService.list,
    enabled: enabled && isAuthenticated(),
  });
}

export function useComplaints(enabled = true, status?: string) {
  return useQuery({
    queryKey: ["complaints", status ?? "all"],
    queryFn: () => complaintService.list(status),
    enabled: enabled && isAuthenticated(),
    staleTime: 30 * 1000,
  });
}

export function useUsersList(enabled = true, role?: string) {
  return useQuery({
    queryKey: ["users", role ?? "all"],
    queryFn: () => userService.list(role),
    enabled: enabled && isAuthenticated(),
  });
}

export function useRoles(enabled = true) {
  return useQuery({
    queryKey: ["roles"],
    queryFn: userService.roles,
    enabled: enabled && isAuthenticated(),
    staleTime: 60 * 60 * 1000,
  });
}

export function useLeaderboard(
  enabled: boolean,
  opts: { role?: string; month?: number; year?: number },
) {
  return useQuery({
    queryKey: ["leaderboard", opts.role, opts.month, opts.year],
    queryFn: () => dashboardService.leaderboard(opts),
    enabled: enabled && isAuthenticated(),
    staleTime: 5 * 60 * 1000,
  });
}

/** Inbox review supervisor: laporan menunggu dari semua domain. */
export function usePendingReviews(enabled: boolean) {
  return useQuery({
    queryKey: ["review", "pending"],
    queryFn: () => reviewService.pendingAll(REVIEW_DOMAINS),
    enabled: enabled && isAuthenticated(),
    staleTime: 30 * 1000,
  });
}

/** Jadwal hari ini gabungan semua domain (dashboard supervisor). */
export function useTodayAllDomains(enabled: boolean) {
  return useQuery({
    queryKey: ["jadwal", "today-all"],
    queryFn: () => jadwalService.todayAll(REVIEW_DOMAINS),
    enabled: enabled && isAuthenticated(),
    staleTime: 60 * 1000,
  });
}

/** Detail satu laporan untuk review (domain + id). */
export function useReviewDetail(domain: DomainConfig | null, id: number | null) {
  return useQuery({
    queryKey: ["review", "detail", domain?.key, id],
    queryFn: () => reviewService.show(domain!, id!),
    enabled: !!domain && !!id,
  });
}

export function useJadwalToday(domain: DomainConfig | null) {
  return useQuery({
    queryKey: ["jadwal", "today", domain?.key],
    queryFn: () => jadwalService.today(domain!),
    enabled: !!domain,
  });
}

export function useJadwalUpcoming(domain: DomainConfig | null) {
  return useQuery({
    queryKey: ["jadwal", "upcoming", domain?.key],
    queryFn: () => jadwalService.upcoming(domain!),
    enabled: !!domain,
  });
}

export function useJadwalDetail(domain: DomainConfig | null, id: number | null) {
  return useQuery({
    queryKey: ["jadwal", "detail", domain?.key, id],
    queryFn: () => jadwalService.show(domain!, id!),
    enabled: !!domain && !!id,
  });
}

export function useLaporan(domain: DomainConfig | null) {
  return useQuery({
    queryKey: ["laporan", domain?.key],
    queryFn: () => laporanService.list(domain!),
    enabled: !!domain,
  });
}

export function usePenilaian() {
  return useQuery({
    queryKey: ["penilaian"],
    queryFn: penilaianService.list,
    enabled: isAuthenticated(),
  });
}

export function useNotifications() {
  return useQuery({
    queryKey: ["notifications"],
    queryFn: notificationService.feed,
    enabled: isAuthenticated(),
    staleTime: 60 * 1000,
  });
}
