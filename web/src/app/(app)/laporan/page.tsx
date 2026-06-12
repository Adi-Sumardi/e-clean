"use client";

import { useEffect, useState } from "react";
import { useMe, useLaporan } from "@/lib/hooks";
import { PageHeader, Spinner, EmptyState, ErrorState, StatusBadge } from "@/components/ui";
import { formatTanggal } from "@/lib/format";
import type { Laporan } from "@/lib/types";

export default function LaporanPage() {
  const { domain } = useMe();
  const { data, isLoading, isError, refetch } = useLaporan(domain);
  const [toast, setToast] = useState<string | null>(null);

  // Tampilkan konfirmasi setelah submit dari form (?sent=0|1).
  useEffect(() => {
    const sent = new URLSearchParams(window.location.search).get("sent");
    if (sent === null) return;
    setToast(
      sent === "1"
        ? "✅ Laporan terkirim."
        : "💾 Laporan tersimpan — akan dikirim otomatis saat online.",
    );
    window.history.replaceState({}, "", "/laporan");
    const t = setTimeout(() => setToast(null), 3500);
    return () => clearTimeout(t);
  }, []);

  return (
    <div className="flex flex-col gap-5">
      <PageHeader title="Laporan" subtitle="Riwayat & status" />

      {toast && (
        <div className="clay px-4 py-3 text-sm font-semibold text-text">
          {toast}
        </div>
      )}

      {data && data.length > 0 ? (
        <div className="flex flex-col gap-3">
          {data.map((l) => (
            <LaporanCard key={l.id} laporan={l} />
          ))}
        </div>
      ) : isLoading ? (
        <Spinner />
      ) : isError ? (
        <ErrorState message="Gagal memuat laporan." onRetry={() => refetch()} />
      ) : (
        <EmptyState
          icon="📋"
          title="Belum ada laporan"
          hint="Laporan yang kamu kirim akan muncul di sini."
        />
      )}
    </div>
  );
}

function LaporanCard({ laporan }: { laporan: Laporan }) {
  return (
    <div className="clay p-5">
      <div className="mb-2 flex items-start justify-between gap-3">
        <h3 className="font-bold text-text">
          {laporan.lokasi?.nama_lokasi ?? "Laporan"}
        </h3>
        <StatusBadge status={laporan.status} />
      </div>
      <p className="text-sm text-muted">📅 {formatTanggal(laporan.tanggal)}</p>
      {laporan.kegiatan && (
        <p className="mt-1 line-clamp-2 text-sm text-text">{laporan.kegiatan}</p>
      )}
      {laporan.status === "rejected" && laporan.rejected_reason && (
        <p className="mt-2 rounded-xl bg-danger/10 px-3 py-2 text-xs text-danger">
          Ditolak: {laporan.rejected_reason}
        </p>
      )}
      {laporan.status === "approved" && laporan.catatan_supervisor && (
        <p className="mt-2 rounded-xl bg-success/10 px-3 py-2 text-xs text-success">
          {laporan.catatan_supervisor}
        </p>
      )}
    </div>
  );
}
