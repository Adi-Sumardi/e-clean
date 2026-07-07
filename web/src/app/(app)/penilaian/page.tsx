"use client";

import { usePenilaian, useMe } from "@/lib/hooks";
import { PageHeader, Spinner, EmptyState, ErrorState } from "@/components/ui";
import { namaBulan } from "@/lib/format";
import type { Penilaian } from "@/lib/types";

const SKOR_LABELS: Record<string, string> = {
  kebersihan: "Kebersihan",
  satpam: "Keamanan",
  ob: "Kebersihan",
  toko: "Pengelolaan",
};

export default function PenilaianPage() {
  const { domain } = useMe();
  const { data, isLoading, isError, refetch } = usePenilaian();
  const skor4Label = SKOR_LABELS[domain?.key ?? "kebersihan"] ?? "Kebersihan";
  const latest = data?.[0];
  const history = data?.slice(1) ?? [];

  return (
    <div className="flex flex-col gap-5">
      <PageHeader title="Penilaian" subtitle="Performa kamu" />

      {!latest && isLoading ? (
        <Spinner />
      ) : !latest && isError ? (
        <ErrorState message="Gagal memuat penilaian." onRetry={() => refetch()} />
      ) : !latest ? (
        <EmptyState icon="⭐" title="Belum ada penilaian" />
      ) : (
        <>
          {/* Skor terbaru */}
          <div className="clay flex flex-col items-center gap-1 p-6 text-center">
            <p className="text-sm text-muted">
              {namaBulan(latest.periode_bulan)} {latest.periode_tahun}
            </p>
            <p className="text-5xl font-extrabold text-primary">
              {fmt(latest.rata_rata ?? latest.total_skor)}
            </p>
            {latest.kategori && (
              <span className="clay-primary mt-1 px-4 py-1 text-xs font-bold">
                {latest.kategori}
              </span>
            )}
          </div>

          {/* Rincian skor */}
          <div className="clay grid grid-cols-2 gap-4 p-5">
            <Skor label="Kehadiran" value={latest.skor_kehadiran} />
            <Skor label="Kualitas" value={latest.skor_kualitas} />
            <Skor label="Ketepatan" value={latest.skor_ketepatan_waktu} />
            <Skor label={skor4Label} value={latest.skor_kebersihan} />
          </div>

          {latest.catatan && (
            <div className="clay-sunken p-4 text-sm text-text">
              {latest.catatan}
            </div>
          )}

          {/* Riwayat */}
          {history.length > 0 && (
            <section className="flex flex-col gap-3">
              <h2 className="text-sm font-bold text-muted">Riwayat</h2>
              {history.map((p) => (
                <HistoryRow key={p.id} p={p} />
              ))}
            </section>
          )}
        </>
      )}
    </div>
  );
}

function fmt(n: number | null): string {
  return n == null ? "-" : Number(n).toFixed(1);
}

function Skor({ label, value }: { label: string; value: number | null }) {
  return (
    <div className="flex flex-col items-center gap-1">
      <span className="text-2xl font-bold text-text">{fmt(value)}</span>
      <span className="text-xs text-muted">{label}</span>
    </div>
  );
}

function HistoryRow({ p }: { p: Penilaian }) {
  return (
    <div className="clay flex items-center justify-between p-4">
      <span className="text-sm font-semibold text-text">
        {namaBulan(p.periode_bulan)} {p.periode_tahun}
      </span>
      <span className="text-lg font-bold text-primary">
        {fmt(p.rata_rata ?? p.total_skor)}
      </span>
    </div>
  );
}
