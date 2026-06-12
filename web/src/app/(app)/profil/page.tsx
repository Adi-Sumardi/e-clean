"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useQueryClient } from "@tanstack/react-query";
import { useMe } from "@/lib/hooks";
import { authService } from "@/lib/services";
import { PageHeader, Spinner } from "@/components/ui";
import PushToggle from "@/components/PushToggle";

export default function ProfilPage() {
  const router = useRouter();
  const qc = useQueryClient();
  const { data: user, domain, isLoading } = useMe();

  async function logout() {
    await authService.logout();
    qc.clear();
    router.replace("/login");
  }

  if (isLoading) return <Spinner />;

  const initial = user?.name?.charAt(0).toUpperCase() ?? "?";

  return (
    <div className="flex flex-col gap-5">
      <PageHeader title="Profil" />

      <div className="clay flex flex-col items-center gap-3 p-6 text-center">
        <div className="clay-primary grid h-20 w-20 place-items-center text-3xl font-bold">
          {initial}
        </div>
        <div>
          <p className="text-lg font-bold text-text">{user?.name}</p>
          <p className="text-sm text-muted">{user?.email}</p>
        </div>
        {domain && (
          <span className="clay-sunken px-4 py-1 text-xs font-bold text-text">
            Petugas {domain.label}
          </span>
        )}
      </div>

      <div className="clay flex flex-col gap-3 p-5">
        <Row label="Nomor HP" value={user?.phone ?? "-"} />
        <Row label="Status" value={user?.is_active ? "Aktif" : "Nonaktif"} />
      </div>

      {/* Notifikasi Web Push */}
      <PushToggle />

      <Link
        href="/tentang"
        className="clay-button flex items-center justify-between px-5 py-4 text-base font-semibold text-text"
      >
        <span>ℹ️ Tentang Aplikasi</span>
        <span className="text-muted">›</span>
      </Link>

      <button
        onClick={logout}
        className="clay-button px-6 py-4 text-base font-bold text-danger"
      >
        Keluar
      </button>
    </div>
  );
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex justify-between gap-4 border-b border-border pb-2 last:border-0 last:pb-0">
      <span className="text-sm text-muted">{label}</span>
      <span className="text-sm font-semibold text-text">{value}</span>
    </div>
  );
}
