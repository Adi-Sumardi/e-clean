"use client";

import Link from "next/link";
import { useMe } from "@/lib/hooks";
import { PageHeader, EmptyState } from "@/components/ui";

const MENU = [
  { href: "/kelola/jadwal", icon: "🗓️", label: "Jadwal", desc: "Buat jadwal semua petugas" },
  { href: "/kelola/petugas", icon: "👥", label: "Petugas & Pengguna", desc: "Kelola akun & role" },
  { href: "/kelola/lokasi", icon: "📍", label: "Lokasi", desc: "Kelola titik lokasi" },
  { href: "/kelola/unit", icon: "🏢", label: "Unit", desc: "Kelola unit/area" },
  { href: "/kelola/laporan-bulanan", icon: "📊", label: "Laporan Bulanan", desc: "Rekap bulanan + unduh PDF" },
  { href: "/kelola/keterlambatan", icon: "⏰", label: "Keterlambatan", desc: "Jadwal terlewat tanpa laporan" },
  { href: "/kelola/pengaturan", icon: "⚙️", label: "Pengaturan", desc: "Toleransi waktu pelaporan" },
];

export default function KelolaPage() {
  const { admin } = useMe();

  if (!admin) {
    return (
      <div className="flex flex-col gap-5">
        <PageHeader title="Kelola" />
        <EmptyState icon="🔒" title="Halaman khusus admin" />
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-5">
      <PageHeader title="Kelola Data" subtitle="Administrasi master data" />

      <div className="grid grid-cols-1 gap-4">
        {MENU.map((m) => (
          <Link key={m.href} href={m.href} className="clay flex items-center gap-4 p-5">
            <span className="clay-sunken grid h-12 w-12 place-items-center rounded-2xl text-2xl">
              {m.icon}
            </span>
            <div className="flex-1">
              <p className="font-bold text-text">{m.label}</p>
              <p className="text-sm text-muted">{m.desc}</p>
            </div>
            <span className="text-muted">›</span>
          </Link>
        ))}
      </div>

      <p className="clay-sunken p-4 text-center text-xs text-muted">
        Fitur QR (scan & cetak kode lokasi) masih di panel admin (Filament).
      </p>
    </div>
  );
}
