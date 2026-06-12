"use client";

import Link from "next/link";
import type { Jadwal } from "@/lib/types";
import { formatTanggal, formatJam } from "@/lib/format";
import { StatusBadge } from "./ui";

export default function JadwalCard({ jadwal }: { jadwal: Jadwal }) {
  return (
    <Link
      href={`/jadwal/detail?id=${jadwal.id}`}
      className="clay block p-5 active:translate-y-px"
    >
      <div className="mb-2 flex items-start justify-between gap-3">
        <h3 className="font-bold text-text">
          {jadwal.lokasi?.nama_lokasi ?? "Lokasi tidak diketahui"}
        </h3>
        <StatusBadge status={jadwal.status} />
      </div>
      <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted">
        <span>📅 {formatTanggal(jadwal.tanggal)}</span>
        <span>⏰ {formatJam(jadwal.jam_mulai, jadwal.jam_selesai)}</span>
        {jadwal.shift && <span>🔁 {jadwal.shift}</span>}
      </div>
      {jadwal.lokasi?.lantai && (
        <p className="mt-1 text-xs text-muted">Lantai {jadwal.lokasi.lantai}</p>
      )}
    </Link>
  );
}
