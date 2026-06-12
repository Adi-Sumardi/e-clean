"use client";

import { useState } from "react";
import Link from "next/link";
import { useQueryClient } from "@tanstack/react-query";
import { useMe, useKeterlambatan } from "@/lib/hooks";
import { keterlambatanService } from "@/lib/services";
import { REVIEW_DOMAINS } from "@/lib/domain";
import { namaBulan, formatTanggal } from "@/lib/format";
import { PageHeader, Spinner, EmptyState, ErrorState } from "@/components/ui";
import type { Keterlambatan } from "@/lib/types";

const now = new Date();

const DOMAIN_LABELS: Record<string, string> = {
  kebersihan: "Kebersihan",
  satpam: "Keamanan",
  ob: "Office Boy",
  toko: "Toko",
};

export default function KeterlambatanPage() {
  const { manager } = useMe();
  const qc = useQueryClient();
  const [domain, setDomain] = useState<string>("all");
  const [bulan, setBulan] = useState(now.getMonth() + 1);
  const [tahun, setTahun] = useState(now.getFullYear());

  const { data, isLoading, isError, refetch } = useKeterlambatan(manager, {
    domain: domain === "all" ? undefined : domain,
    bulan,
    tahun,
  });

  async function hapus(item: Keterlambatan) {
    const nama = item.petugas?.name ?? "petugas";
    if (!confirm(`Hapus catatan keterlambatan ${nama} (${formatTanggal(item.tanggal ?? "")})?`)) {
      return;
    }
    try {
      await keterlambatanService.remove(item.id);
      qc.invalidateQueries({ queryKey: ["keterlambatan"] });
    } catch {
      alert("Gagal menghapus catatan.");
    }
  }

  const list = data ?? [];

  return (
    <div className="flex flex-col gap-5">
      <Link href="/beranda" className="text-sm font-semibold text-primary">
        ← Kembali
      </Link>
      <PageHeader
        title="Laporan Keterlambatan"
        subtitle="Dicatat otomatis sistem saat jadwal terlewat tanpa laporan"
      />

      {/* Filter domain */}
      <div className="flex gap-2 overflow-x-auto pb-1">
        {[{ key: "all", label: "Semua" }, ...REVIEW_DOMAINS].map((d) => (
          <button
            key={d.key}
            onClick={() => setDomain(d.key)}
            className={`whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold ${
              domain === d.key ? "clay-primary" : "clay-button text-muted"
            }`}
          >
            {d.label}
          </button>
        ))}
      </div>

      {/* Filter bulan + tahun */}
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
      </div>

      {!manager ? (
        <EmptyState icon="🔒" title="Khusus supervisor / admin" />
      ) : isLoading && !data ? (
        <Spinner />
      ) : isError && !data ? (
        <ErrorState message="Gagal memuat data keterlambatan." onRetry={() => refetch()} />
      ) : list.length > 0 ? (
        <div className="flex flex-col gap-3">
          {list.map((item) => (
            <div key={item.id} className="clay p-4">
              <div className="mb-1 flex items-start justify-between gap-3">
                <p className="truncate font-bold text-text">
                  {item.petugas?.name ?? "Petugas"}
                </p>
                <span className="shrink-0 rounded-full bg-danger/15 px-3 py-1 text-xs font-bold text-danger">
                  Tidak Lapor
                </span>
              </div>
              <p className="truncate text-sm text-muted">
                📍 {item.lokasi?.nama_lokasi ?? "-"}
                {item.lokasi?.unit ? ` · ${item.lokasi.unit.nama_unit}` : ""}
              </p>
              <p className="text-xs text-muted">
                🏷️ {DOMAIN_LABELS[item.domain] ?? item.domain} · 📅{" "}
                {formatTanggal(item.tanggal ?? "")}
                {item.shift ? ` · ${item.shift}` : ""}
                {item.batas_waktu_mulai
                  ? ` · ${item.batas_waktu_mulai}–${item.batas_waktu_selesai ?? ""}`
                  : ""}
              </p>
              {item.keterangan && (
                <p className="mt-1 text-xs text-muted">{item.keterangan}</p>
              )}
              <div className="mt-2 flex justify-end">
                <button
                  onClick={() => hapus(item)}
                  className="clay-button px-3 py-2 text-xs font-semibold text-danger"
                >
                  Hapus
                </button>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <EmptyState
          icon="✅"
          title="Tidak ada keterlambatan"
          hint={`Tidak ada jadwal terlewat di ${namaBulan(bulan)} ${tahun}.`}
        />
      )}
    </div>
  );
}
