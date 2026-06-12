"use client";

/**
 * Cetak QR code lokasi (pengganti halaman PrintQRCodes Filament).
 * QR berisi URL form keluhan tamu (/keluhan/{kode}) — tamu cukup scan pakai
 * kamera HP, TANPA aplikasi. Tombol cetak memakai window.print(); CSS print
 * menyembunyikan semua kecuali grid kartu QR.
 */

import { useState } from "react";
import Link from "next/link";
import { useMe, useLokasiQrCodes, useUnitList } from "@/lib/hooks";
import { PageHeader, Spinner, EmptyState, ErrorState } from "@/components/ui";

export default function CetakQRPage() {
  const { manager } = useMe();
  const [unitId, setUnitId] = useState<number | undefined>(undefined);
  const units = useUnitList(manager);
  const { data, isLoading, isError, refetch } = useLokasiQrCodes(manager, unitId);

  const list = data ?? [];

  return (
    <div className="flex flex-col gap-5">
      {/* CSS print: hanya area QR yang tercetak */}
      <style>{`
        @media print {
          body * { visibility: hidden; }
          #qr-print-area, #qr-print-area * { visibility: visible; }
          #qr-print-area {
            position: absolute; left: 0; top: 0; width: 100%;
            display: grid !important; grid-template-columns: repeat(2, 1fr); gap: 16px;
          }
          .qr-card { break-inside: avoid; border: 1px dashed #999 !important; box-shadow: none !important; }
        }
      `}</style>

      <Link href="/kelola/lokasi" className="text-sm font-semibold text-primary">
        ← Kembali
      </Link>
      <PageHeader
        title="Cetak QR Lokasi"
        subtitle="Tamu scan QR → langsung ke form keluhan"
        right={
          <button
            onClick={() => window.print()}
            disabled={!manager || list.length === 0}
            className="clay-primary px-4 py-2 text-sm font-bold disabled:opacity-60"
          >
            🖨 Cetak
          </button>
        }
      />

      <select
        value={unitId ?? ""}
        onChange={(e) => setUnitId(e.target.value ? Number(e.target.value) : undefined)}
        className="clay-sunken rounded-2xl px-4 py-3 text-sm font-semibold text-text outline-none"
      >
        <option value="">Semua unit</option>
        {(units.data ?? []).map((u) => (
          <option key={u.id} value={u.id}>
            {u.nama_unit}
          </option>
        ))}
      </select>

      {!manager ? (
        <EmptyState icon="🔒" title="Khusus supervisor / admin" />
      ) : isLoading && !data ? (
        <Spinner label="Menyiapkan QR code…" />
      ) : isError && !data ? (
        <ErrorState message="Gagal memuat QR code." onRetry={() => refetch()} />
      ) : list.length > 0 ? (
        <div id="qr-print-area" className="grid grid-cols-2 gap-4">
          {list.map((l) => (
            <div key={l.id} className="qr-card clay flex flex-col items-center gap-2 p-4 text-center">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={l.qr_url} alt={`QR ${l.kode_lokasi}`} className="aspect-square w-full max-w-44 bg-white" />
              <p className="text-sm font-bold leading-tight text-text">{l.nama_lokasi}</p>
              <p className="text-xs text-muted">
                {l.kode_lokasi}
                {l.unit ? ` · ${l.unit}` : ""}
                {l.lantai ? ` · Lt. ${l.lantai}` : ""}
              </p>
              <p className="text-[10px] text-muted">Scan untuk lapor keluhan</p>
            </div>
          ))}
        </div>
      ) : (
        <EmptyState icon="📍" title="Tidak ada lokasi aktif" />
      )}
    </div>
  );
}
