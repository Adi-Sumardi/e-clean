"use client";

import { useState } from "react";
import Link from "next/link";
import { useMe, useLeaderboard } from "@/lib/hooks";
import { REVIEW_DOMAINS } from "@/lib/domain";
import { namaBulan } from "@/lib/format";
import { PageHeader, Spinner, EmptyState, ErrorState } from "@/components/ui";
import type { LeaderboardEntry } from "@/lib/types";

const MEDAL = ["🥇", "🥈", "🥉"];
const now = new Date();

export default function LeaderboardPage() {
  const { manager } = useMe();
  const [roleKey, setRoleKey] = useState(REVIEW_DOMAINS[0].role);
  const [bulan, setBulan] = useState(now.getMonth() + 1);
  const [tahun, setTahun] = useState(now.getFullYear());

  const { data, isLoading, isError, refetch } = useLeaderboard(manager, {
    role: roleKey,
    month: bulan,
    year: tahun,
  });
  const list = data?.leaderboard ?? [];

  return (
    <div className="flex flex-col gap-5">
      <Link href="/beranda" className="text-sm font-semibold text-primary">
        ← Kembali
      </Link>
      <PageHeader title="Peringkat Petugas" subtitle="Ranking performa per bulan" />

      {/* Filter tipe petugas */}
      <div className="flex gap-2 overflow-x-auto pb-1">
        {REVIEW_DOMAINS.map((d) => (
          <button
            key={d.key}
            onClick={() => setRoleKey(d.role)}
            className={`whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold ${
              roleKey === d.role ? "clay-primary" : "clay-button text-muted"
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
        <ErrorState message="Gagal memuat peringkat." onRetry={() => refetch()} />
      ) : list.length > 0 ? (
        <div className="flex flex-col gap-3">
          {list.map((e) => (
            <Row key={e.petugas_id} e={e} />
          ))}
        </div>
      ) : (
        <EmptyState
          icon="🏆"
          title="Belum ada data peringkat"
          hint={`Belum ada laporan/penilaian di ${namaBulan(bulan)} ${tahun}.`}
        />
      )}
    </div>
  );
}

function Row({ e }: { e: LeaderboardEntry }) {
  const top = e.rank <= 3;
  return (
    <div className={`flex items-center gap-4 p-4 ${top ? "clay-primary" : "clay"}`}>
      <div
        className={`grid h-11 w-11 shrink-0 place-items-center rounded-2xl text-lg font-bold ${
          top ? "bg-white/25 text-primary-foreground" : "clay-sunken text-text"
        }`}
      >
        {top ? MEDAL[e.rank - 1] : e.rank}
      </div>
      <div className="min-w-0 flex-1">
        <p className={`truncate font-bold ${top ? "text-primary-foreground" : "text-text"}`}>
          {e.name}
        </p>
        <p className={`truncate text-xs ${top ? "text-primary-foreground/80" : "text-muted"}`}>
          {e.total_reports} laporan · {e.approved_reports} disetujui · ⭐{" "}
          {e.average_rating || 0}
        </p>
      </div>
      <div className="shrink-0 text-right">
        <p className={`text-xl font-extrabold ${top ? "text-primary-foreground" : "text-primary"}`}>
          {e.overall_score}
        </p>
        <p className={`text-[10px] ${top ? "text-primary-foreground/80" : "text-muted"}`}>skor</p>
      </div>
    </div>
  );
}
