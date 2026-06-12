"use client";

import { useState } from "react";
import Link from "next/link";
import { useQueryClient } from "@tanstack/react-query";
import { useMe, useComplaints, useUsersList } from "@/lib/hooks";
import { complaintService } from "@/lib/services";
import { PageHeader, Spinner, EmptyState, ErrorState, StatusBadge } from "@/components/ui";
import { formatTanggal } from "@/lib/format";
import type { GuestComplaint } from "@/lib/types";

const FILTERS = [
  { key: undefined, label: "Semua" },
  { key: "pending", label: "Menunggu" },
  { key: "in_progress", label: "Diproses" },
  { key: "resolved", label: "Selesai" },
];

const NEXT_ACTIONS: Record<string, { status: string; label: string }[]> = {
  pending: [
    { status: "in_progress", label: "Proses" },
    { status: "rejected", label: "Tolak" },
  ],
  in_progress: [
    { status: "resolved", label: "Selesai" },
    { status: "rejected", label: "Tolak" },
  ],
};

/** Tipe laporan keluhan → label + role petugas yang menangani. */
const TIPE_INFO: Record<string, { label: string; icon: string; role: string }> = {
  kebersihan: { label: "Kebersihan", icon: "🧹", role: "petugas" },
  office_boy: { label: "Office Boy", icon: "🛎️", role: "office_boy" },
  satpam: { label: "Keamanan", icon: "🛡️", role: "satpam" },
};

export default function KeluhanPage() {
  const { manager } = useMe();
  const qc = useQueryClient();
  const [filter, setFilter] = useState<string | undefined>(undefined);
  const { data, isLoading, isError, refetch } = useComplaints(manager, filter);
  // Semua user → difilter per keluhan sesuai role tipe laporannya.
  const users = useUsersList(manager);

  async function setStatus(c: GuestComplaint, status: string) {
    try {
      await complaintService.updateStatus(c.id, status);
      qc.invalidateQueries({ queryKey: ["complaints"] });
    } catch {
      alert("Gagal memperbarui status.");
    }
  }

  async function assign(c: GuestComplaint, userId: string) {
    if (!userId) return;
    try {
      await complaintService.assign(c.id, Number(userId));
      qc.invalidateQueries({ queryKey: ["complaints"] });
    } catch {
      alert("Gagal menugaskan petugas.");
    }
  }

  return (
    <div className="flex flex-col gap-5">
      <Link href="/beranda" className="text-sm font-semibold text-primary">
        ← Kembali
      </Link>
      <PageHeader title="Keluhan Tamu" subtitle="Tindak lanjut & tugaskan" />

      <div className="flex gap-2 overflow-x-auto pb-1">
        {FILTERS.map((f) => (
          <button
            key={f.label}
            onClick={() => setFilter(f.key)}
            className={`whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold ${
              filter === f.key ? "clay-primary" : "clay-button text-muted"
            }`}
          >
            {f.label}
          </button>
        ))}
      </div>

      {!manager ? (
        <EmptyState icon="🔒" title="Khusus supervisor / admin" />
      ) : isLoading && !data ? (
        <Spinner />
      ) : isError && !data ? (
        <ErrorState message="Gagal memuat keluhan." onRetry={() => refetch()} />
      ) : data && data.length > 0 ? (
        <div className="flex flex-col gap-3">
          {data.map((c) => (
            <div key={c.id} className="clay p-4">
              <div className="mb-1 flex items-start justify-between gap-3">
                <div className="min-w-0">
                  <p className="truncate font-bold text-text">
                    {c.lokasi?.nama_lokasi ?? "Lokasi -"}
                  </p>
                  {c.lokasi?.unit?.nama_unit && (
                    <p className="text-xs text-muted">🏢 {c.lokasi.unit.nama_unit}</p>
                  )}
                </div>
                <div className="flex shrink-0 flex-col items-end gap-1">
                  <StatusBadge status={c.status} />
                  {c.jenis_keluhan && (
                    <span className="clay-sunken rounded-full px-2 py-0.5 text-[10px] font-semibold text-text">
                      {c.jenis_keluhan}
                    </span>
                  )}
                  {(() => {
                    const tipe = TIPE_INFO[c.tipe_laporan ?? "kebersihan"];
                    return tipe ? (
                      <span className="clay-sunken rounded-full px-2 py-0.5 text-[10px] font-semibold text-text">
                        {tipe.icon} {tipe.label}
                      </span>
                    ) : null;
                  })()}
                </div>
              </div>

              <p className="text-sm text-text">{c.deskripsi_keluhan}</p>

              {/* Foto keluhan */}
              {c.foto_keluhan && (
                <a
                  href={c.foto_keluhan}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="clay mt-2 block aspect-video max-h-40 w-fit overflow-hidden rounded-2xl"
                >
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img src={c.foto_keluhan} alt="Foto keluhan" className="h-full w-full object-cover" />
                </a>
              )}

              <p className="mt-2 text-xs text-muted">
                {c.nama_pelapor ? `👤 ${c.nama_pelapor}` : "👤 Anonim"}
                {c.telepon_pelapor ? ` · ${c.telepon_pelapor}` : ""}
                {" · "}
                {formatTanggal(c.created_at)}
              </p>
              {(c.assignee?.name || c.handler?.name) && (
                <p className="text-xs text-muted">
                  {c.assignee?.name ? `🧹 Ditugaskan: ${c.assignee.name}` : ""}
                  {c.handler?.name ? ` · Ditangani: ${c.handler.name}` : ""}
                </p>
              )}

              {/* Tugaskan ke petugas sesuai tipe laporan keluhan */}
              <div className="mt-3 flex items-center gap-2">
                <span className="text-xs text-muted">Tugaskan:</span>
                <select
                  value=""
                  onChange={(e) => assign(c, e.target.value)}
                  className="clay-sunken flex-1 rounded-xl px-3 py-2 text-sm text-text outline-none"
                >
                  <option value="">
                    {c.assignee?.name ? `Ubah (kini: ${c.assignee.name})` : "Pilih petugas…"}
                  </option>
                  {(users.data ?? [])
                    .filter((u) =>
                      u.roles?.includes(
                        (TIPE_INFO[c.tipe_laporan ?? "kebersihan"] ?? TIPE_INFO.kebersihan).role,
                      ),
                    )
                    .map((u) => (
                      <option key={u.id} value={u.id}>
                        {u.name}
                      </option>
                    ))}
                </select>
              </div>

              {NEXT_ACTIONS[c.status] && (
                <div className="mt-3 flex gap-2">
                  {NEXT_ACTIONS[c.status].map((a) => (
                    <button
                      key={a.status}
                      onClick={() => setStatus(c, a.status)}
                      className={`clay-button px-4 py-2 text-sm font-semibold ${
                        a.status === "rejected" ? "text-danger" : "text-primary"
                      }`}
                    >
                      {a.label}
                    </button>
                  ))}
                </div>
              )}
            </div>
          ))}
        </div>
      ) : (
        <EmptyState icon="📣" title="Tidak ada keluhan" />
      )}
    </div>
  );
}
