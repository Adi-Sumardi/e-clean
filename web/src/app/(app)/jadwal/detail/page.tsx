"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useMe, useJadwalDetail } from "@/lib/hooks";
import { Spinner, ErrorState, StatusBadge } from "@/components/ui";
import { formatTanggal, formatJam } from "@/lib/format";

export default function JadwalDetailPage() {
  const [id, setId] = useState<number | null>(null);
  const { domain } = useMe();

  // Baca ?id dari URL (hindari useSearchParams agar mulus di static export).
  useEffect(() => {
    const raw = new URLSearchParams(window.location.search).get("id");
    setId(raw ? Number(raw) : null);
  }, []);

  const { data: jadwal, isLoading, isError, refetch } = useJadwalDetail(domain, id);

  return (
    <div className="flex flex-col gap-5">
      <Link href="/jadwal" className="text-sm font-semibold text-primary">
        ← Kembali
      </Link>

      {isLoading || !id ? (
        <Spinner />
      ) : isError || !jadwal ? (
        <ErrorState message="Gagal memuat detail jadwal." onRetry={refetch} />
      ) : (
        <>
          <div className="clay flex flex-col gap-3 p-6">
            <div className="flex items-start justify-between gap-3">
              <h1 className="text-lg font-bold text-text">
                {jadwal.lokasi?.nama_lokasi ?? "Lokasi"}
              </h1>
              <StatusBadge status={jadwal.status} />
            </div>
            <Row label="Tanggal" value={formatTanggal(jadwal.tanggal)} />
            <Row
              label="Jam"
              value={formatJam(jadwal.jam_mulai, jadwal.jam_selesai)}
            />
            {jadwal.shift && <Row label="Shift" value={jadwal.shift} />}
            {jadwal.lokasi?.lantai && (
              <Row label="Lantai" value={jadwal.lokasi.lantai} />
            )}
            {jadwal.lokasi?.kategori && (
              <Row label="Kategori" value={jadwal.lokasi.kategori} />
            )}
            {jadwal.catatan && <Row label="Catatan" value={jadwal.catatan} />}
          </div>

          <Link
            href={`/laporan/baru?jadwal=${jadwal.id}&lokasi=${jadwal.lokasi?.id ?? ""}&nama=${encodeURIComponent(jadwal.lokasi?.nama_lokasi ?? "")}&shift=${encodeURIComponent(jadwal.shift ?? "")}`}
            className="clay-primary block w-full px-6 py-4 text-center text-base font-bold"
          >
            Buat Laporan
          </Link>
        </>
      )}
    </div>
  );
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex justify-between gap-4 border-b border-border pb-2 last:border-0 last:pb-0">
      <span className="text-sm text-muted">{label}</span>
      <span className="text-right text-sm font-semibold text-text">{value}</span>
    </div>
  );
}
