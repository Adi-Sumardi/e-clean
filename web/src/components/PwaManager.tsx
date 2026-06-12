"use client";

import { useEffect, useState } from "react";

/**
 * Mendaftarkan service worker dan menampilkan toast "Versi baru" saat ada
 * update SW yang menunggu. User menekan untuk mengaktifkan & reload — tidak
 * memaksa di tengah pengisian form.
 */
export default function PwaManager() {
  const [waiting, setWaiting] = useState<ServiceWorker | null>(null);

  useEffect(() => {
    if (typeof navigator === "undefined" || !("serviceWorker" in navigator)) {
      return;
    }

    let reg: ServiceWorkerRegistration | null = null;

    const promote = (worker: ServiceWorker | null) => {
      if (worker) setWaiting(worker);
    };

    const register = async () => {
      try {
        reg = await navigator.serviceWorker.register("/sw.js");

        // SW yang sudah menunggu saat halaman dibuka.
        if (reg.waiting && navigator.serviceWorker.controller) {
          promote(reg.waiting);
        }

        // SW baru ditemukan saat app berjalan.
        reg.addEventListener("updatefound", () => {
          const installing = reg!.installing;
          if (!installing) return;
          installing.addEventListener("statechange", () => {
            if (installing.state === "installed" && navigator.serviceWorker.controller) {
              promote(reg!.waiting ?? installing);
            }
          });
        });
      } catch {
        /* app tetap jalan tanpa SW */
      }
    };

    // Reload sekali setelah SW baru mengambil alih.
    let reloaded = false;
    const onControllerChange = () => {
      if (reloaded) return;
      reloaded = true;
      window.location.reload();
    };
    navigator.serviceWorker.addEventListener("controllerchange", onControllerChange);

    if (document.readyState === "complete") void register();
    else window.addEventListener("load", register, { once: true });

    return () => {
      navigator.serviceWorker.removeEventListener("controllerchange", onControllerChange);
    };
  }, []);

  if (!waiting) return null;

  return (
    <div className="fixed inset-x-0 top-0 z-50 flex justify-center px-4 pt-3">
      <button
        onClick={() => waiting.postMessage({ type: "SKIP_WAITING" })}
        className="clay-primary flex items-center gap-2 px-5 py-3 text-sm font-bold"
      >
        ✨ Versi baru tersedia — ketuk untuk perbarui
      </button>
    </div>
  );
}
