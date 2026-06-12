"use client";

import { useMemo, useState } from "react";
import Link from "next/link";
import { useMe, useLaporan } from "@/lib/hooks";
import { REVIEW_DOMAINS, type DomainConfig } from "@/lib/domain";
import { reportService } from "@/lib/services";
import { ApiError } from "@/lib/api";
import { PageHeader, Spinner, EmptyState, ErrorState, StatusBadge } from "@/components/ui";
import { formatTanggal, formatJam } from "@/lib/format";

const STATUS_FILTERS = [
  { key: "all", label: "Semua" },
  { key: "submitted", label: "Menunggu" },
  { key: "approved", label: "Disetujui" },
  { key: "rejected", label: "Ditolak" },
];

export default function LaporanMonitorPage() {
  const { manager } = useMe();
  const [domain, setDomain] = useState<DomainConfig>(REVIEW_DOMAINS[0]);
  const [status, setStatus] = useState("all");

  const [downloading, setDownloading] = useState(false);
  const [downloadError, setDownloadError] = useState<string | null>(null);

  const { data, isLoading, isError, refetch } = useLaporan(manager ? domain : null);

  const filtered = useMemo(() => {
    const list = data ?? [];
    return status === "all" ? list : list.filter((l) => l.status === status);
  }, [data, status]);

  async function downloadPdf() {
    const now = new Date();
    setDownloadError(null);
    setDownloading(true);
    try {
      await reportService.downloadListPdf({
        domain: domain.key,
        bulan: now.getMonth() + 1,
        tahun: now.getFullYear(),
        status: status === "all" ? undefined : status,
      });
    } catch (err) {
      setDownloadError(err instanceof ApiError ? err.message : "Gagal mengunduh PDF.");
    } finally {
      setDownloading(false);
    }
  }

  return (
    <div className="flex flex-col gap-5">
      <Link href="/beranda" className="text-sm font-semibold text-primary">
        ← Kembali
      </Link>
      <PageHeader
        title="Laporan"
        subtitle="Pantau laporan semua petugas"
        right={
          <button
            onClick={downloadPdf}
            disabled={downloading || !manager}
            title="Unduh PDF laporan bulan ini (sesuai filter)"
            className="clay-primary px-4 py-2 text-sm font-bold disabled:opacity-60"
          >
            {downloading ? "Menyiapkan…" : "⬇ PDF"}
          </button>
        }
      />
      {downloadError && <p className="text-sm text-danger">{downloadError}</p>}

      {/* Filter tipe */}
      <div className="flex gap-2 overflow-x-auto pb-1">
        {REVIEW_DOMAINS.map((d) => (
          <button
            key={d.key}
            onClick={() => setDomain(d)}
            className={`whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold ${
              domain.key === d.key ? "clay-primary" : "clay-button text-muted"
            }`}
          >
            {d.label}
          </button>
        ))}
      </div>

      {/* Filter status */}
      <div className="flex gap-2 overflow-x-auto pb-1">
        {STATUS_FILTERS.map((s) => (
          <button
            key={s.key}
            onClick={() => setStatus(s.key)}
            className={`whitespace-nowrap rounded-full px-4 py-1.5 text-xs font-bold ${
              status === s.key ? "clay-primary" : "clay-button text-muted"
            }`}
          >
            {s.label}
          </button>
        ))}
      </div>

      {!manager ? (
        <EmptyState icon="🔒" title="Khusus supervisor / admin" />
      ) : isLoading && !data ? (
        <Spinner />
      ) : isError && !data ? (
        <ErrorState message="Gagal memuat laporan." onRetry={() => refetch()} />
      ) : filtered.length > 0 ? (
        <div className="flex flex-col gap-3">
          {filtered.map((l) => (
            <Link
              key={l.id}
              href={`/review/detail?domain=${domain.key}&id=${l.id}`}
              className="clay block p-4 active:translate-y-px"
            >
              <div className="mb-1 flex items-start justify-between gap-3">
                <p className="truncate font-bold text-text">
                  {l.petugas?.name ?? l.lokasi?.nama_lokasi ?? "Laporan"}
                </p>
                <StatusBadge status={l.status} />
              </div>
              <p className="truncate text-sm text-muted">
                📍 {l.lokasi?.nama_lokasi ?? "-"}
              </p>
              <p className="text-xs text-muted">
                📅 {formatTanggal(l.tanggal)} · ⏰ {formatJam(l.jam_mulai, l.jam_selesai)}
              </p>
            </Link>
          ))}
        </div>
      ) : (
        <EmptyState icon="📋" title="Tidak ada laporan" />
      )}
    </div>
  );
}
