"use client";

import Link from "next/link";
import {
  useMe,
  useJadwalToday,
  useNotifications,
  usePendingReviews,
  useTodayAllDomains,
} from "@/lib/hooks";
import JadwalCard from "@/components/JadwalCard";
import { Spinner } from "@/components/ui";
import InstallPrompt from "@/components/InstallPrompt";
import AnalyticsWidgets from "@/components/AnalyticsWidgets";
import { formatJam, formatTanggal } from "@/lib/format";

export default function BerandaPage() {
  const { data: user, domain, manager, isLoading } = useMe();
  const today = useJadwalToday(domain);
  const notif = useNotifications();
  const reviews = usePendingReviews(manager);
  const todayAll = useTodayAllDomains(manager);

  const todayCount = today.data?.length ?? 0;
  const pendingCount = reviews.data?.length ?? 0;
  const todayAllCount = todayAll.data?.length ?? 0;

  return (
    <div className="flex flex-col gap-5">
      <InstallPrompt />

      {/* Sapaan */}
      <header className="flex items-center justify-between">
        <div>
          <p className="text-sm text-muted">Selamat datang,</p>
          <h1 className="text-xl font-bold text-text">
            {isLoading ? "…" : (user?.name ?? "Pengguna")}
          </h1>
        </div>
        {(domain || manager) && (
          <span className="clay-primary px-4 py-2 text-xs font-bold">
            {manager
              ? (user?.roles?.includes("super_admin")
                ? "Super Admin"
                : user?.roles?.includes("admin")
                ? "Admin"
                : user?.roles?.includes("pengurus")
                ? "Pengurus"
                : "Supervisor")
              : domain?.label}
          </span>
        )}
      </header>

      {manager ? (
        /* ---- Tampilan Supervisor ---- */
        <>
          {/* Ringkasan angka */}
          <section className="grid grid-cols-2 gap-4">
            <div className="clay p-5">
              <h2 className="text-xs font-semibold text-muted">Jadwal hari ini</h2>
              <p className="text-3xl font-bold text-text">
                {todayAll.isLoading && !todayAll.data ? "…" : todayAllCount}
              </p>
            </div>
            <Link href="/review" className="clay p-5">
              <h2 className="text-xs font-semibold text-muted">Menunggu review</h2>
              <p className="text-3xl font-bold text-primary">
                {reviews.isLoading && !reviews.data ? "…" : pendingCount}
              </p>
            </Link>
          </section>

          {/* Section: Perlu direview */}
          <section className="flex flex-col gap-3">
            <div className="flex items-center justify-between">
              <h2 className="text-sm font-bold text-muted">Perlu direview</h2>
              <Link href="/review" className="text-xs font-bold text-primary">
                Lihat semua →
              </Link>
            </div>
            {reviews.isLoading && !reviews.data ? (
              <Spinner />
            ) : pendingCount > 0 ? (
              reviews.data!.slice(0, 4).map((it) => (
                <Link
                  key={`${it.domain.key}-${it.report.id}`}
                  href={`/review/detail?domain=${it.domain.key}&id=${it.report.id}`}
                  className="clay flex items-center justify-between gap-3 p-4 active:translate-y-px"
                >
                  <div className="min-w-0">
                    <p className="truncate font-bold text-text">
                      {it.report.petugas?.name ?? "Petugas"}
                    </p>
                    <p className="truncate text-xs text-muted">
                      {it.domain.label} · {it.report.lokasi?.nama_lokasi ?? "-"}
                    </p>
                  </div>
                  <span className="clay-sunken shrink-0 rounded-full px-3 py-1 text-xs font-semibold text-text">
                    Tinjau
                  </span>
                </Link>
              ))
            ) : (
              <p className="clay-sunken p-4 text-center text-sm text-muted">
                Semua laporan sudah ditinjau. 🎉
              </p>
            )}
          </section>

          {/* Section: Jadwal hari ini (semua petugas) */}
          <section className="flex flex-col gap-3">
            <h2 className="text-sm font-bold text-muted">Jadwal hari ini</h2>
            {todayAll.isLoading && !todayAll.data ? (
              <Spinner />
            ) : todayAllCount > 0 ? (
              todayAll.data!.slice(0, 5).map((it) => (
                <div
                  key={`${it.domain.key}-${it.jadwal.id}`}
                  className="clay flex items-center justify-between gap-3 p-4"
                >
                  <div className="min-w-0">
                    <p className="truncate font-bold text-text">
                      {it.jadwal.lokasi?.nama_lokasi ?? "Lokasi -"}
                    </p>
                    <p className="truncate text-xs text-muted">
                      {it.domain.label}
                      {it.jadwal.petugas?.name ? ` · ${it.jadwal.petugas.name}` : ""}
                      {" · "}
                      {formatJam(it.jadwal.jam_mulai, it.jadwal.jam_selesai)}
                    </p>
                  </div>
                  {it.jadwal.shift && (
                    <span className="clay-sunken shrink-0 rounded-full px-3 py-1 text-xs font-semibold text-text">
                      {it.jadwal.shift}
                    </span>
                  )}
                </div>
              ))
            ) : (
              <p className="clay-sunken p-4 text-center text-sm text-muted">
                Tidak ada jadwal hari ini. ({formatTanggal(new Date().toISOString())})
              </p>
            )}
          </section>

          {/* Menu manajemen */}
          <section className="flex flex-col gap-3">
            <h2 className="text-sm font-bold text-muted">Menu</h2>
            <div className="grid grid-cols-3 gap-3">
              <MenuCard href="/kelola/penilaian" icon="⭐" label="Penilaian" />
              <MenuCard href="/kelola/leaderboard" icon="🏆" label="Peringkat" />
              <MenuCard href="/kelola/keluhan" icon="📣" label="Keluhan" />
              <MenuCard href="/kelola/laporan" icon="📋" label="Laporan" />
              <MenuCard href="/kelola/lokasi" icon="📍" label="Lokasi" />
              <MenuCard href="/kelola/unit" icon="🏢" label="Unit" />
              <MenuCard href="/kelola/laporan-bulanan" icon="📊" label="Lap. Bulanan" />
              <MenuCard href="/kelola/keterlambatan" icon="⏰" label="Terlambat" />
              <MenuCard href="/kelola/pengaturan" icon="⚙️" label="Pengaturan" />
            </div>
          </section>

          {/* Widget analitik (rekap bulanan, trend mingguan, 12 bulan) */}
          <AnalyticsWidgets />
        </>
      ) : (
        /* ---- Tampilan Petugas ---- */
        <>
          <section className="clay flex items-center justify-between p-6">
            <div>
              <h2 className="text-sm font-semibold text-muted">Jadwal hari ini</h2>
              <p className="text-3xl font-bold text-text">
                {today.isLoading ? "…" : todayCount}
              </p>
            </div>
            <Link
              href="/jadwal"
              className="clay-button px-5 py-3 text-sm font-bold text-primary"
            >
              Lihat
            </Link>
          </section>

          {today.isLoading ? (
            <Spinner />
          ) : todayCount > 0 ? (
            <section className="flex flex-col gap-3">
              {today.data!.slice(0, 3).map((j) => (
                <JadwalCard key={j.id} jadwal={j} />
              ))}
            </section>
          ) : null}

          <section className="grid grid-cols-2 gap-4">
            <Link
              href="/laporan"
              className="clay flex flex-col items-center gap-2 p-5 text-center"
            >
              <span className="text-2xl">📋</span>
              <span className="text-sm font-semibold text-text">Laporan</span>
            </Link>
            <Link
              href="/penilaian"
              className="clay flex flex-col items-center gap-2 p-5 text-center"
            >
              <span className="text-2xl">⭐</span>
              <span className="text-sm font-semibold text-text">Penilaian</span>
            </Link>
          </section>
        </>
      )}

      {/* Notifikasi terbaru (umum) */}
      <section className="clay-sunken flex items-center justify-between p-4">
        <span className="text-sm text-muted">Notifikasi belum dibaca</span>
        <Link href="/notifikasi" className="text-sm font-bold text-primary">
          {notif.data?.count ?? 0} →
        </Link>
      </section>
    </div>
  );
}

function MenuCard({ href, icon, label }: { href: string; icon: string; label: string }) {
  return (
    <Link href={href} className="clay flex flex-col items-center gap-2 p-4 text-center">
      <span className="text-2xl">{icon}</span>
      <span className="text-xs font-semibold text-text">{label}</span>
    </Link>
  );
}
