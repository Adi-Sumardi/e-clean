"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { isAuthenticated } from "@/lib/auth";

/** Pintu masuk: arahkan ke beranda bila sudah login, kalau tidak ke login. */
export default function Home() {
  const router = useRouter();

  useEffect(() => {
    router.replace(isAuthenticated() ? "/beranda" : "/login");
  }, [router]);

  return (
    <div className="flex min-h-dvh items-center justify-center text-muted">
      Memuat…
    </div>
  );
}
