"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useMe } from "@/lib/hooks";

type Item = { href: string; label: string; icon: React.ReactNode };

const I = {
  home: (
    <path d="M3 10.5 12 3l9 7.5M5 9.5V20a1 1 0 0 0 1 1h3v-6h6v6h3a1 1 0 0 0 1-1V9.5" />
  ),
  calendar: (
    <>
      <rect x="3" y="4.5" width="18" height="16" rx="3" />
      <path d="M3 9h18M8 2.5v4M16 2.5v4" />
    </>
  ),
  clipboard: (
    <>
      <rect x="5" y="4" width="14" height="17" rx="3" />
      <path d="M9 4V3a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1M9 11h6M9 15h4" />
    </>
  ),
  bell: (
    <path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0" />
  ),
  user: (
    <>
      <circle cx="12" cy="8" r="4" />
      <path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1" />
    </>
  ),
};

const petugasItems: Item[] = [
  { href: "/beranda", label: "Beranda", icon: I.home },
  { href: "/jadwal", label: "Jadwal", icon: I.calendar },
  { href: "/laporan", label: "Laporan", icon: I.clipboard },
  { href: "/notifikasi", label: "Notif", icon: I.bell },
  { href: "/profil", label: "Profil", icon: I.user },
];

const managerItems: Item[] = [
  { href: "/beranda", label: "Beranda", icon: I.home },
  { href: "/review", label: "Review", icon: I.clipboard },
  { href: "/kelola/jadwal", label: "Jadwal", icon: I.calendar },
  { href: "/notifikasi", label: "Notif", icon: I.bell },
  { href: "/profil", label: "Profil", icon: I.user },
];

const I_cog = (
  <>
    <circle cx="12" cy="12" r="3" />
    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
  </>
);

const adminItems: Item[] = [
  { href: "/beranda", label: "Beranda", icon: I.home },
  { href: "/review", label: "Review", icon: I.clipboard },
  { href: "/kelola", label: "Kelola", icon: I_cog },
  { href: "/notifikasi", label: "Notif", icon: I.bell },
  { href: "/profil", label: "Profil", icon: I.user },
];

export default function BottomNav() {
  const pathname = usePathname();
  const { manager, admin } = useMe();
  const items = admin ? adminItems : manager ? managerItems : petugasItems;

  return (
    <nav className="fixed inset-x-0 bottom-0 z-30 flex justify-center pb-[env(safe-area-inset-bottom)]">
      <div className="clay mx-3 mb-3 flex w-full max-w-md md:max-w-2xl items-center justify-around gap-1 p-2">
        {items.map((it) => {
          const active = pathname.startsWith(it.href);
          return (
            <Link
              key={it.href}
              href={it.href}
              className={`flex flex-1 flex-col items-center gap-1 rounded-3xl px-2 py-2 text-[11px] font-medium transition-colors ${
                active ? "text-primary" : "text-muted"
              }`}
            >
              <span
                className={
                  active ? "clay-primary grid h-10 w-10 place-items-center" : "grid h-10 w-10 place-items-center"
                }
              >
                <svg
                  width="22"
                  height="22"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="2"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  className={active ? "text-primary-foreground" : ""}
                >
                  {it.icon}
                </svg>
              </span>
              {it.label}
            </Link>
          );
        })}
      </div>
    </nav>
  );
}
