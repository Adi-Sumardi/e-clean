"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { isAuthenticated } from "@/lib/auth";

/**
 * Pelindung rute sisi-klien (static export = tidak ada guard server).
 *
 * Gating berbasis `ready` agar render server (prerender) & render klien pertama
 * sama-sama menampilkan "Memuat…" — menghindari hydration mismatch dari
 * pembacaan localStorage. Setelah mount, cek token: tanpa token → ke /login.
 */
export default function AuthGate({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const [ready, setReady] = useState(false);

  useEffect(() => {
    if (!isAuthenticated()) {
      router.replace("/login");
      return;
    }
    setReady(true);
  }, [router]);

  if (!ready) {
    return (
      <div className="flex min-h-dvh items-center justify-center text-muted">
        Memuat…
      </div>
    );
  }
  return <>{children}</>;
}
