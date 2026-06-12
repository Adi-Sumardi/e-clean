"use client";

import Link from "next/link";
import { PageHeader } from "@/components/ui";

type Status = "done" | "progress" | "next";

const BADGE: Record<Status, { label: string; cls: string }> = {
  done: { label: "Selesai", cls: "bg-success/15 text-success" },
  progress: { label: "Berjalan", cls: "bg-primary/15 text-primary" },
  next: { label: "Berikutnya", cls: "bg-warning/15 text-[#b07d12]" },
};

const PHASES: { title: string; status: Status; items: string[] }[] = [
  {
    title: "Fase 1 — Jadwal & Riwayat",
    status: "done",
    items: ["Jadwal hari ini & mendatang", "Riwayat laporan + status", "Penilaian & notifikasi"],
  },
  {
    title: "Fase 2 — Laporan & Offline",
    status: "done",
    items: [
      "Form laporan + foto (kompresi otomatis)",
      "Kirim walau offline, tersimpan lokal",
      "Auto-sync saat online kembali",
    ],
  },
  {
    title: "Fase 3 — Notifikasi Push",
    status: "progress",
    items: ["Pemberitahuan laporan disetujui/ditolak", "Aktif/nonaktif dari Profil"],
  },
  {
    title: "Fase 4 — Penyempurnaan",
    status: "next",
    items: ["Pasang ke layar utama (PWA)", "Uji lapangan & perbaikan"],
  },
];

const ROLLOUT: { title: string; status: Status; desc: string }[] = [
  { title: "Petugas lapangan", status: "progress", desc: "Kebersihan, keamanan, office boy, toko" },
  { title: "Supervisor", status: "next", desc: "Approve/reject & monitoring" },
  { title: "Admin", status: "next", desc: "Kelola data, pengaturan, laporan" },
];

export default function TentangPage() {
  return (
    <div className="flex flex-col gap-5">
      <Link href="/profil" className="text-sm font-semibold text-primary">
        ← Kembali
      </Link>
      <PageHeader title="Tentang Aplikasi" subtitle="Apps KopkarYAPI · Peta jalan pengembangan" />

      <div className="clay flex flex-col gap-2 p-5 text-sm text-text">
        <p>
          <b>Apps KopkarYAPI</b> adalah aplikasi petugas untuk mengelola jadwal & laporan
          kebersihan/keamanan — ringan, bisa dipakai offline, dan terpasang
          seperti aplikasi di HP.
        </p>
      </div>

      <section className="flex flex-col gap-3">
        <h2 className="text-sm font-bold text-muted">Tahap Pengembangan</h2>
        {PHASES.map((p) => (
          <div key={p.title} className="clay p-5">
            <div className="mb-2 flex items-center justify-between gap-3">
              <h3 className="font-bold text-text">{p.title}</h3>
              <span className={`rounded-full px-3 py-1 text-xs font-bold ${BADGE[p.status].cls}`}>
                {BADGE[p.status].label}
              </span>
            </div>
            <ul className="flex flex-col gap-1 text-sm text-muted">
              {p.items.map((it) => (
                <li key={it}>• {it}</li>
              ))}
            </ul>
          </div>
        ))}
      </section>

      <section className="flex flex-col gap-3">
        <h2 className="text-sm font-bold text-muted">Urutan Peluncuran</h2>
        {ROLLOUT.map((r) => (
          <div key={r.title} className="clay flex items-center justify-between gap-3 p-4">
            <div>
              <p className="font-semibold text-text">{r.title}</p>
              <p className="text-xs text-muted">{r.desc}</p>
            </div>
            <span className={`rounded-full px-3 py-1 text-xs font-bold ${BADGE[r.status].cls}`}>
              {BADGE[r.status].label}
            </span>
          </div>
        ))}
      </section>

      <p className="py-2 text-center text-xs text-muted">Apps KopkarYAPI · kopkaryapi.id</p>
    </div>
  );
}
