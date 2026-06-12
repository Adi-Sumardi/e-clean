"use client";

import Link from "next/link";
import { useMemo, useState } from "react";
import { useQueryClient } from "@tanstack/react-query";
import { useMe, useLokasiList, useUnitList } from "@/lib/hooks";
import { lokasiService } from "@/lib/services";
import { PageHeader, Spinner, EmptyState, ErrorState } from "@/components/ui";
import type { Lokasi } from "@/lib/types";

export default function LokasiListPage() {
  const { manager } = useMe();
  const qc = useQueryClient();
  const { data, isLoading, isError, refetch } = useLokasiList(manager);
  const units = useUnitList(manager);
  const [unitId, setUnitId] = useState("all");

  const filtered = useMemo(
    () =>
      (data ?? []).filter(
        (l) => unitId === "all" || String(l.unit?.id ?? "") === unitId,
      ),
    [data, unitId],
  );

  async function hapus(l: Lokasi) {
    if (!confirm(`Hapus lokasi "${l.nama_lokasi}"?`)) return;
    try {
      await lokasiService.remove(l.id);
      qc.invalidateQueries({ queryKey: ["lokasi"] });
    } catch {
      alert("Gagal menghapus. Mungkin lokasi sedang dipakai.");
    }
  }

  return (
    <div className="flex flex-col gap-5">
      <Link href="/beranda" className="text-sm font-semibold text-primary">
        ← Kembali
      </Link>
      <PageHeader
        title="Lokasi"
        right={
          <div className="flex gap-2">
            <Link href="/kelola/lokasi/qr" className="clay-button px-4 py-2 text-sm font-bold text-text">
              🖨 QR
            </Link>
            <Link href="/kelola/lokasi/form" className="clay-primary px-4 py-2 text-sm font-bold">
              + Tambah
            </Link>
          </div>
        }
      />

      {/* Filter unit */}
      {manager && (units.data?.length ?? 0) > 0 && (
        <select
          value={unitId}
          onChange={(e) => setUnitId(e.target.value)}
          className="clay-sunken w-full rounded-2xl px-4 py-3 text-sm font-semibold text-text outline-none"
        >
          <option value="all">Semua unit</option>
          {units.data?.map((u) => (
            <option key={u.id} value={String(u.id)}>
              {u.nama_unit}
            </option>
          ))}
        </select>
      )}

      {!manager ? (
        <EmptyState icon="🔒" title="Khusus supervisor / admin" />
      ) : isLoading ? (
        <Spinner />
      ) : isError ? (
        <ErrorState message="Gagal memuat lokasi." onRetry={() => refetch()} />
      ) : filtered.length > 0 ? (
        <div className="flex flex-col gap-3">
          {filtered.map((l) => (
            <div key={l.id} className="clay p-4">
              <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                  <p className="font-bold text-text">{l.nama_lokasi}</p>
                  <p className="text-sm text-muted">
                    {l.kode_lokasi} · {l.kategori}
                    {l.unit?.nama_unit ? ` · ${l.unit.nama_unit}` : ""}
                  </p>
                </div>
                {l.is_active === false && (
                  <span className="rounded-full bg-muted/15 px-2 py-1 text-xs text-muted">
                    Nonaktif
                  </span>
                )}
              </div>
              <div className="mt-3 flex gap-2">
                <Link
                  href={`/kelola/lokasi/form?id=${l.id}`}
                  className="clay-button flex-1 px-4 py-2 text-center text-sm font-semibold text-text"
                >
                  Edit
                </Link>
                <button
                  onClick={() => hapus(l)}
                  className="clay-button px-4 py-2 text-sm font-semibold text-danger"
                >
                  Hapus
                </button>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <EmptyState icon="📍" title="Belum ada lokasi" hint="Tambah lokasi pertama." />
      )}
    </div>
  );
}
