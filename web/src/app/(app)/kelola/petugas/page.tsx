"use client";

import { useState } from "react";
import Link from "next/link";
import { useQueryClient } from "@tanstack/react-query";
import { useMe, useUsersList, useRoles } from "@/lib/hooks";
import { userService } from "@/lib/services";
import { roleLabel } from "@/lib/domain";
import { PageHeader, Spinner, EmptyState, ErrorState } from "@/components/ui";
import type { User } from "@/lib/types";

export default function PetugasListPage() {
  const { admin } = useMe();
  const qc = useQueryClient();
  const [role, setRole] = useState<string | "all">("all");
  const roles = useRoles(admin);
  const { data, isLoading, isError, refetch } = useUsersList(
    admin,
    role === "all" ? undefined : role,
  );

  async function hapus(u: User) {
    if (!confirm(`Hapus pengguna "${u.name}"?`)) return;
    try {
      await userService.remove(u.id);
      qc.invalidateQueries({ queryKey: ["users"] });
    } catch {
      alert("Gagal menghapus pengguna.");
    }
  }

  return (
    <div className="flex flex-col gap-5">
      <Link href="/kelola" className="text-sm font-semibold text-primary">
        ← Kembali
      </Link>
      <PageHeader
        title="Petugas & Pengguna"
        right={
          <Link href="/kelola/petugas/form" className="clay-primary px-4 py-2 text-sm font-bold">
            + Tambah
          </Link>
        }
      />

      {/* Filter role */}
      <div className="flex gap-2 overflow-x-auto pb-1">
        <Chip label="Semua" active={role === "all"} onClick={() => setRole("all")} />
        {roles.data?.map((r) => (
          <Chip key={r} label={roleLabel(r)} active={role === r} onClick={() => setRole(r)} />
        ))}
      </div>

      {!admin ? (
        <EmptyState icon="🔒" title="Khusus admin" />
      ) : isLoading && !data ? (
        <Spinner />
      ) : isError && !data ? (
        <ErrorState message="Gagal memuat pengguna." onRetry={() => refetch()} />
      ) : data && data.length > 0 ? (
        <div className="flex flex-col gap-3">
          {data.map((u) => (
            <div key={u.id} className="clay p-4">
              <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                  <p className="font-bold text-text">{u.name}</p>
                  <p className="truncate text-sm text-muted">{u.email}</p>
                  <div className="mt-1 flex flex-wrap gap-1">
                    {u.roles.map((r) => (
                      <span key={r} className="clay-sunken rounded-full px-2 py-0.5 text-xs text-text">
                        {roleLabel(r)}
                      </span>
                    ))}
                  </div>
                </div>
                {!u.is_active && (
                  <span className="rounded-full bg-muted/15 px-2 py-1 text-xs text-muted">
                    Nonaktif
                  </span>
                )}
              </div>
              <div className="mt-3 flex gap-2">
                <Link
                  href={`/kelola/petugas/form?id=${u.id}`}
                  className="clay-button flex-1 px-4 py-2 text-center text-sm font-semibold text-text"
                >
                  Edit
                </Link>
                <button
                  onClick={() => hapus(u)}
                  className="clay-button px-4 py-2 text-sm font-semibold text-danger"
                >
                  Hapus
                </button>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <EmptyState icon="👥" title="Belum ada pengguna" />
      )}
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
