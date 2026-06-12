"use client";

import { useState } from "react";
import Link from "next/link";
import { useMe, useMonthlyReport, useUnitList } from "@/lib/hooks";
import { reportService } from "@/lib/services";
import { ApiError } from "@/lib/api";
import { namaBulan } from "@/lib/format";
import { PageHeader, Spinner, EmptyState, ErrorState } from "@/components/ui";

const now = new Date();

export default function LaporanBulananPage() {
  const { manager } = useMe();
  const [bulan, setBulan] = useState(now.getMonth() + 1);
  const [tahun, setTahun] = useState(now.getFullYear());
  const [unitId, setUnitId] = useState<number | undefined>(undefined);
  const [downloading, setDownloading] = useState(false);
  const [downloadError, setDownloadError] = useState<string | null>(null);

  const units = useUnitList(manager);
  const { data, isLoading, isError, refetch } = useMonthlyReport(manager, {
    bulan,
    tahun,
    unit_id: unitId,
  });

  async function downloadPdf() {
    setDownloadError(null);
    setDownloading(true);
    try {
      await reportService.downloadMonthlyPdf({ bulan, tahun, unit_id: unitId });
    } catch (err) {
      setDownloadError(
        err instanceof ApiError ? err.message : "Gagal mengunduh PDF.",
      );
    } finally {
      setDownloading(false);
    }
  }

  const stats = data?.stats;

  return (
    <div className="flex flex-col gap-5">
      <Link href="/beranda" className="text-sm font-semibold text-primary">
        ← Kembali
      </Link>
      <PageHeader
        title="Laporan Bulanan"
        subtitle="Rekap laporan kebersihan per unit & petugas"
        right={
          <button
            onClick={downloadPdf}
            disabled={downloading || !manager}
            className="clay-primary px-4 py-2 text-sm font-bold disabled:opacity-60"
          >
            {downloading ? "Menyiapkan…" : "⬇ PDF"}
          </button>
        }
      />

      {/* Filter bulan + tahun + unit */}
      <div className="grid grid-cols-2 gap-3">
        <select
          value={bulan}
          onChange={(e) => setBulan(Number(e.target.value))}
          className="clay-sunken rounded-2xl px-4 py-3 text-sm font-semibold text-text outline-none"
        >
          {Array.from({ length: 12 }, (_, i) => i + 1).map((m) => (
            <option key={m} value={m}>
              {namaBulan(m)}
            </option>
          ))}
        </select>
        <select
          value={tahun}
          onChange={(e) => setTahun(Number(e.target.value))}
          className="clay-sunken rounded-2xl px-4 py-3 text-sm font-semibold text-text outline-none"
        >
          {Array.from({ length: 5 }, (_, i) => now.getFullYear() - i).map((y) => (
            <option key={y} value={y}>
              {y}
            </option>
          ))}
        </select>
        <select
          value={unitId ?? ""}
          onChange={(e) => setUnitId(e.target.value ? Number(e.target.value) : undefined)}
          className="clay-sunken col-span-2 rounded-2xl px-4 py-3 text-sm font-semibold text-text outline-none"
        >
          <option value="">Semua unit</option>
          {(units.data ?? []).map((u) => (
            <option key={u.id} value={u.id}>
              {u.nama_unit}
            </option>
          ))}
        </select>
      </div>

      {downloadError && <p className="text-sm text-danger">{downloadError}</p>}

      {!manager ? (
        <EmptyState icon="🔒" title="Khusus supervisor / admin" />
      ) : isLoading && !data ? (
        <Spinner />
      ) : isError && !data ? (
        <ErrorState message="Gagal memuat laporan bulanan." onRetry={() => refetch()} />
      ) : stats && stats.total > 0 ? (
        <>
          {/* Ringkasan */}
          <section className="grid grid-cols-2 gap-3">
            <StatCard label="Total Laporan" value={String(stats.total)} />
            <StatCard label="Rata-rata Rating" value={`${stats.avg_rating}/5`} />
            <StatCard
              label="Tepat Waktu"
              value={`${stats.ontime} (${stats.ontime_pct}%)`}
              tone="text-success"
            />
            <StatCard
              label="Terlambat"
              value={`${stats.late} (${stats.late_pct}%)`}
              tone="text-[#b07d12]"
            />
            <StatCard
              label="Tidak Lapor"
              value={`${stats.expired} (${stats.expired_pct}%)`}
              tone="text-danger"
            />
          </section>

          {/* Rekap per unit → petugas */}
          {(data?.units ?? []).map((u) => (
            <section key={u.unit} className="clay flex flex-col gap-3 p-4">
              <div className="flex items-center justify-between">
                <p className="font-bold text-text">🏢 {u.unit}</p>
                <span className="clay-sunken rounded-full px-3 py-1 text-xs font-semibold text-muted">
                  {u.total} laporan
                </span>
              </div>
              <div className="flex flex-col gap-2">
                {u.petugas.map((p) => (
                  <div key={p.name} className="clay-sunken rounded-2xl p-3">
                    <div className="mb-1 flex items-center justify-between gap-3">
                      <p className="truncate text-sm font-bold text-text">{p.name}</p>
                      <span className="shrink-0 text-xs font-semibold text-muted">
                        ⭐ {p.avg_rating}/5
                      </span>
                    </div>
                    <p className="text-xs text-muted">
                      {p.total} laporan · ✅ {p.ontime} tepat · ⏰ {p.late} terlambat
                      {p.expired > 0 ? ` · ⚠️ ${p.expired} tidak lapor` : ""}
                    </p>
                  </div>
                ))}
              </div>
            </section>
          ))}
        </>
      ) : (
        <EmptyState
          icon="📊"
          title="Tidak ada laporan"
          hint={`Belum ada laporan kebersihan di ${namaBulan(bulan)} ${tahun}.`}
        />
      )}
    </div>
  );
}

function StatCard({
  label,
  value,
  tone = "text-text",
}: {
  label: string;
  value: string;
  tone?: string;
}) {
  return (
    <div className="clay p-4">
      <p className="text-xs font-semibold text-muted">{label}</p>
      <p className={`text-xl font-bold ${tone}`}>{value}</p>
    </div>
  );
}
