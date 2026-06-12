"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { useMe, usePendingReviews } from "@/lib/hooks";
import { REVIEW_DOMAINS } from "@/lib/domain";
import { PageHeader, Spinner, EmptyState, ErrorState } from "@/components/ui";
import { formatTanggal, formatJam } from "@/lib/format";
import type { ReviewItem } from "@/lib/services";

export default function ReviewPage() {
  const { manager } = useMe();
  const { data, isLoading, isError, refetch } = usePendingReviews(manager);
  const [toast, setToast] = useState(false);

  const [domainKey, setDomainKey] = useState<string | "all">("all");
  const [unitId, setUnitId] = useState<string>("all");

  useEffect(() => {
    if (new URLSearchParams(window.location.search).get("done") === "1") {
      setToast(true);
      window.history.replaceState({}, "", "/review");
      const t = setTimeout(() => setToast(false), 3000);
      return () => clearTimeout(t);
    }
  }, []);

  // Opsi unit diturunkan dari data (unit yang benar-benar ada di laporan).
  const unitOptions = useMemo(() => {
    const map = new Map<number, string>();
    (data ?? []).forEach((it) => {
      const u = it.report.lokasi?.unit;
      if (u) map.set(u.id, u.nama_unit);
    });
    return Array.from(map, ([id, nama]) => ({ id, nama }));
  }, [data]);

  const filtered = useMemo(() => {
    return (data ?? []).filter((it) => {
      if (domainKey !== "all" && it.domain.key !== domainKey) return false;
      if (unitId !== "all" && String(it.report.lokasi?.unit?.id ?? "") !== unitId)
        return false;
      return true;
    });
  }, [data, domainKey, unitId]);

  return (
    <div className="flex flex-col gap-5">
      <PageHeader title="Review Laporan" subtitle="Menunggu persetujuan" />

      {toast && (
        <div className="clay px-4 py-3 text-sm font-semibold text-text">
          ✅ Laporan diproses.
        </div>
      )}

      {manager && (
        <div className="flex flex-col gap-3">
          {/* Filter tipe petugas */}
          <div className="flex gap-2 overflow-x-auto pb-1">
            <FilterChip
              label="Semua"
              active={domainKey === "all"}
              onClick={() => setDomainKey("all")}
              count={data?.length}
            />
            {REVIEW_DOMAINS.map((d) => {
              const n = (data ?? []).filter((it) => it.domain.key === d.key).length;
              return (
                <FilterChip
                  key={d.key}
                  label={d.label}
                  active={domainKey === d.key}
                  onClick={() => setDomainKey(d.key)}
                  count={n}
                />
              );
            })}
          </div>

          {/* Filter unit */}
          {unitOptions.length > 0 && (
            <select
              value={unitId}
              onChange={(e) => setUnitId(e.target.value)}
              className="clay-sunken w-full rounded-2xl px-4 py-3 text-sm font-semibold text-text outline-none"
            >
              <option value="all">Semua unit</option>
              {unitOptions.map((u) => (
                <option key={u.id} value={String(u.id)}>
                  {u.nama}
                </option>
              ))}
            </select>
          )}
        </div>
      )}

      {!manager ? (
        <EmptyState icon="🔒" title="Halaman khusus supervisor" />
      ) : filtered.length > 0 ? (
        <div className="flex flex-col gap-3">
          {filtered.map((it) => (
            <ReviewCard key={`${it.domain.key}-${it.report.id}`} item={it} />
          ))}
        </div>
      ) : isLoading && !data ? (
        <Spinner />
      ) : isError && !data ? (
        <ErrorState message="Gagal memuat laporan." onRetry={() => refetch()} />
      ) : (data?.length ?? 0) > 0 ? (
        <EmptyState icon="🔎" title="Tidak ada yang cocok dengan filter" />
      ) : (
        <EmptyState icon="✅" title="Tidak ada laporan menunggu" hint="Semua sudah ditinjau." />
      )}
    </div>
  );
}

function FilterChip({
  label,
  active,
  onClick,
  count,
}: {
  label: string;
  active: boolean;
  onClick: () => void;
  count?: number;
}) {
  return (
    <button
      onClick={onClick}
      className={`flex items-center gap-1.5 whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold ${
        active ? "clay-primary" : "clay-button text-muted"
      }`}
    >
      {label}
      {count != null && count > 0 && (
        <span
          className={`rounded-full px-1.5 text-xs ${
            active ? "bg-white/25" : "bg-primary/15 text-primary"
          }`}
        >
          {count}
        </span>
      )}
    </button>
  );
}

function ReviewCard({ item }: { item: ReviewItem }) {
  const { domain, report } = item;
  return (
    <Link
      href={`/review/detail?domain=${domain.key}&id=${report.id}`}
      className="clay block p-5 active:translate-y-px"
    >
      <div className="mb-2 flex items-start justify-between gap-3">
        <h3 className="font-bold text-text">{report.petugas?.name ?? "Petugas"}</h3>
        <span className="clay-sunken px-3 py-1 text-xs font-bold text-text">
          {domain.label}
        </span>
      </div>
      <div className="flex flex-col gap-1 text-sm text-muted">
        <span>📍 {report.lokasi?.nama_lokasi ?? "-"}</span>
        {report.lokasi?.unit?.nama_unit && <span>🏢 {report.lokasi.unit.nama_unit}</span>}
        <span>
          📅 {formatTanggal(report.tanggal)} · ⏰{" "}
          {formatJam(report.jam_mulai, report.jam_selesai)}
        </span>
      </div>
    </Link>
  );
}
