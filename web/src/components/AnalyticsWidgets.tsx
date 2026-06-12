"use client";

/**
 * Widget analitik beranda untuk supervisor/pengurus/admin — port ringan dari
 * widget Filament (SupervisorMonthlyReport, PengurusPerformanceTrend,
 * AdminSystemOverview). Grafik digambar dengan div + CSS (tanpa chart lib)
 * supaya bundle tetap kecil di HP kelas bawah.
 */

import Link from "next/link";
import { useMe, useMonthlyReport, useDashboardStatistics } from "@/lib/hooks";
import { namaBulan } from "@/lib/format";

const now = new Date();

export default function AnalyticsWidgets() {
  const { manager, admin } = useMe();

  if (!manager) return null;

  return (
    <>
      <RekapBulanIni />
      <TrendMingguan />
      {admin && <TrendDuaBelasBulan />}
    </>
  );
}

/** Rekap laporan bulan berjalan (≈ SupervisorMonthlyReportWidget). */
function RekapBulanIni() {
  const { manager } = useMe();
  const { data } = useMonthlyReport(manager, {
    bulan: now.getMonth() + 1,
    tahun: now.getFullYear(),
  });

  const stats = data?.stats;
  if (!stats) return null;

  return (
    <section className="flex flex-col gap-3">
      <div className="flex items-center justify-between">
        <h2 className="text-sm font-bold text-muted">
          Rekap {namaBulan(now.getMonth() + 1)} {now.getFullYear()}
        </h2>
        <Link href="/kelola/laporan-bulanan" className="text-xs font-bold text-primary">
          Detail →
        </Link>
      </div>
      <div className="grid grid-cols-2 gap-3">
        <div className="clay p-4">
          <p className="text-xs font-semibold text-muted">Total Laporan</p>
          <p className="text-2xl font-bold text-text">{stats.total}</p>
        </div>
        <div className="clay p-4">
          <p className="text-xs font-semibold text-muted">Rata-rata Rating</p>
          <p className="text-2xl font-bold text-text">⭐ {stats.avg_rating}/5</p>
        </div>
      </div>
      <div className="clay flex flex-col gap-2 p-4">
        <Meter label="Tepat waktu" pct={stats.ontime_pct} count={stats.ontime} barClass="bg-success" />
        <Meter label="Terlambat" pct={stats.late_pct} count={stats.late} barClass="bg-warning" />
        <Meter label="Tidak lapor" pct={stats.expired_pct} count={stats.expired} barClass="bg-danger" />
      </div>
    </section>
  );
}

function Meter({
  label,
  pct,
  count,
  barClass,
}: {
  label: string;
  pct: number;
  count: number;
  barClass: string;
}) {
  return (
    <div>
      <div className="mb-1 flex items-center justify-between text-xs">
        <span className="font-semibold text-muted">{label}</span>
        <span className="font-bold text-text">
          {count} ({pct}%)
        </span>
      </div>
      <div className="clay-sunken h-2.5 overflow-hidden rounded-full">
        <div
          className={`h-full rounded-full ${barClass}`}
          style={{ width: `${Math.min(100, Math.max(0, pct))}%` }}
        />
      </div>
    </div>
  );
}

/** 7 hari terakhir (terlama → terbaru), dihitung sekali saat modul dimuat. */
const LAST_7_DAYS = Array.from({ length: 7 }, (_, i) => {
  const d = new Date(now.getTime() - (6 - i) * 86400000);
  return {
    key: d.toISOString().slice(0, 10),
    label: d.toLocaleDateString("id-ID", { weekday: "short" }),
  };
});

/** Trend 7 hari approved vs rejected (≈ PengurusPerformanceTrendWidget). */
function TrendMingguan() {
  const { manager } = useMe();
  const { data } = useDashboardStatistics(manager, {
    start_date: LAST_7_DAYS[0].key,
  });

  const trend = data?.status_trend ?? [];

  // Lengkapi 7 hari penuh (hari tanpa laporan tetap muncul sebagai 0).
  const days = LAST_7_DAYS.map((day) => {
    const point = trend.find((t) => t.date === day.key);
    return {
      ...day,
      approved: Number(point?.approved ?? 0),
      rejected: Number(point?.rejected ?? 0),
    };
  });

  if (!data) return null;
  const max = Math.max(1, ...days.map((d) => Math.max(d.approved, d.rejected)));

  return (
    <section className="flex flex-col gap-3">
      <h2 className="text-sm font-bold text-muted">Trend Laporan 7 Hari</h2>
      <div className="clay p-4">
        <div className="flex h-28 items-end justify-between gap-2">
          {days.map((d) => (
            <div key={d.key} className="flex h-full flex-1 flex-col items-center justify-end gap-1">
              <div className="flex h-full w-full items-end justify-center gap-1">
                <div
                  className="w-2.5 rounded-t-full bg-success"
                  style={{ height: `${(d.approved / max) * 100}%` }}
                  title={`Disetujui: ${d.approved}`}
                />
                <div
                  className="w-2.5 rounded-t-full bg-danger"
                  style={{ height: `${(d.rejected / max) * 100}%` }}
                  title={`Ditolak: ${d.rejected}`}
                />
              </div>
              <span className="text-[10px] font-semibold text-muted">{d.label}</span>
            </div>
          ))}
        </div>
        <div className="mt-3 flex justify-center gap-4 text-xs text-muted">
          <span>
            <span className="mr-1 inline-block h-2 w-2 rounded-full bg-success" />
            Disetujui
          </span>
          <span>
            <span className="mr-1 inline-block h-2 w-2 rounded-full bg-danger" />
            Ditolak
          </span>
        </div>
      </div>
    </section>
  );
}

/** Laporan per bulan, 12 bulan terakhir (≈ AdminSystemOverviewWidget). */
function TrendDuaBelasBulan() {
  const { manager } = useMe();
  const { data } = useDashboardStatistics(manager, undefined);

  const trend = data?.monthly_trend ?? [];
  if (!data || trend.length === 0) return null;

  const max = Math.max(1, ...trend.map((t) => Number(t.count)));

  return (
    <section className="flex flex-col gap-3">
      <h2 className="text-sm font-bold text-muted">Laporan 12 Bulan Terakhir</h2>
      <div className="clay p-4">
        <div className="flex h-28 items-end justify-between gap-1">
          {trend.map((t) => {
            const [y, m] = t.month.split("-");
            return (
              <div key={t.month} className="flex h-full flex-1 flex-col items-center justify-end gap-1">
                <div
                  className="w-full max-w-4 rounded-t-full bg-primary"
                  style={{ height: `${(Number(t.count) / max) * 100}%` }}
                  title={`${namaBulan(Number(m))} ${y}: ${t.count} laporan`}
                />
                <span className="text-[9px] font-semibold text-muted">
                  {namaBulan(Number(m)).slice(0, 3)}
                </span>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
