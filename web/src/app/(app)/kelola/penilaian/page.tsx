"use client";

import { useMemo, useState } from "react";
import Link from "next/link";
import { useMe, usePenilaian, useUsersList } from "@/lib/hooks";
import { REVIEW_DOMAINS } from "@/lib/domain";
import { namaBulan } from "@/lib/format";
import { PageHeader, Spinner, EmptyState, ErrorState } from "@/components/ui";
import type { Penilaian } from "@/lib/types";

export default function PenilaianHistoryPage() {
  const { manager } = useMe();
  const [roleKey, setRoleKey] = useState<string | "all">("all");

  const { data, isLoading, isError, refetch } = usePenilaian();
  const users = useUsersList(manager);

  // Peta petugas_id → role (untuk filter tipe).
  const roleOf = useMemo(() => {
    const m = new Map<number, string[]>();
    (users.data ?? []).forEach((u) => m.set(u.id, u.roles));
    return m;
  }, [users.data]);

  const filtered = useMemo(() => {
    if (roleKey === "all") return data ?? [];
    return (data ?? []).filter((p) =>
      p.petugas_id ? (roleOf.get(p.petugas_id) ?? []).includes(roleKey) : false,
    );
  }, [data, roleKey, roleOf]);

  return (
    <div className="flex flex-col gap-5">
      <Link href="/beranda" className="text-sm font-semibold text-primary">
        ← Kembali
      </Link>
      <PageHeader title="Penilaian Petugas" subtitle="Riwayat nilai kinerja" />

      {/* Filter tipe */}
      <div className="flex gap-2 overflow-x-auto pb-1">
        <Chip label="Semua" active={roleKey === "all"} onClick={() => setRoleKey("all")} />
        {REVIEW_DOMAINS.map((d) => (
          <Chip
            key={d.key}
            label={d.label}
            active={roleKey === d.role}
            onClick={() => setRoleKey(d.role)}
          />
        ))}
      </div>

      {!manager ? (
        <EmptyState icon="🔒" title="Khusus supervisor / admin" />
      ) : filtered.length > 0 ? (
        <div className="flex flex-col gap-3">
          {filtered.map((p) => (
            <Card key={p.id} p={p} />
          ))}
        </div>
      ) : isLoading && !data ? (
        <Spinner />
      ) : isError && !data ? (
        <ErrorState message="Gagal memuat penilaian." onRetry={() => refetch()} />
      ) : (
        <EmptyState icon="⭐" title="Belum ada penilaian" />
      )}
    </div>
  );
}

function Card({ p }: { p: Penilaian }) {
  const skor = [
    ["Kehadiran", p.skor_kehadiran],
    ["Kualitas", p.skor_kualitas],
    ["Ketepatan", p.skor_ketepatan_waktu],
    ["Kebersihan", p.skor_kebersihan],
  ] as const;
  return (
    <div className="clay flex flex-col gap-3 p-5">
      <div className="flex items-start justify-between gap-3">
        <div className="min-w-0">
          <p className="truncate font-bold text-text">
            {p.petugas?.name ?? `Petugas #${p.petugas_id ?? "?"}`}
          </p>
          <p className="text-xs text-muted">
            {namaBulan(p.periode_bulan)} {p.periode_tahun}
            {p.kategori ? ` · ${p.kategori}` : ""}
          </p>
        </div>
        <div className="shrink-0 text-right">
          <p className="text-2xl font-extrabold text-primary">
            {p.rata_rata != null ? Number(p.rata_rata).toFixed(1) : "-"}
          </p>
          <p className="text-[10px] text-muted">rata-rata</p>
        </div>
      </div>
      <div className="grid grid-cols-4 gap-2">
        {skor.map(([label, v]) => (
          <div key={label} className="clay-sunken flex flex-col items-center gap-0.5 rounded-2xl py-2">
            <span className="text-sm font-bold text-text">
              {v != null ? Number(v).toFixed(0) : "-"}
            </span>
            <span className="text-[10px] text-muted">{label}</span>
          </div>
        ))}
      </div>
      {p.catatan && <p className="text-sm text-muted">“{p.catatan}”</p>}
    </div>
  );
}

function Chip({ label, active, onClick }: { label: string; active: boolean; onClick: () => void }) {
  return (
    <button
      onClick={onClick}
      className={`whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold ${
        active ? "clay-primary" : "clay-button text-muted"
      }`}
    >
      {label}
    </button>
  );
}
