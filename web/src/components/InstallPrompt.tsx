"use client";

import { useEffect, useState } from "react";

interface BeforeInstallPromptEvent extends Event {
  prompt: () => Promise<void>;
  userChoice: Promise<{ outcome: "accepted" | "dismissed" }>;
}

const DISMISS_KEY = "eclean.install.dismissed";

function isStandalone(): boolean {
  return (
    window.matchMedia?.("(display-mode: standalone)").matches ||
    // iOS Safari
    (window.navigator as unknown as { standalone?: boolean }).standalone === true
  );
}

function isIOS(): boolean {
  return /iphone|ipad|ipod/i.test(window.navigator.userAgent);
}

/**
 * Ajakan pasang ke layar utama (A2HS).
 * - Android/Chrome: tombol Pasang via event beforeinstallprompt.
 * - iOS Safari: instruksi manual (Bagikan → Tambah ke Layar Utama).
 * Bisa ditutup; tidak muncul lagi setelah ditutup atau bila sudah terpasang.
 */
export default function InstallPrompt() {
  const [deferred, setDeferred] = useState<BeforeInstallPromptEvent | null>(null);
  const [showIOS, setShowIOS] = useState(false);
  const [dismissed, setDismissed] = useState(true);

  useEffect(() => {
    if (isStandalone()) return;
    if (localStorage.getItem(DISMISS_KEY)) return;
    setDismissed(false);

    const onPrompt = (e: Event) => {
      e.preventDefault();
      setDeferred(e as BeforeInstallPromptEvent);
    };
    window.addEventListener("beforeinstallprompt", onPrompt);

    // iOS tidak punya beforeinstallprompt → tampilkan instruksi.
    if (isIOS()) setShowIOS(true);

    return () => window.removeEventListener("beforeinstallprompt", onPrompt);
  }, []);

  function close() {
    localStorage.setItem(DISMISS_KEY, "1");
    setDismissed(true);
  }

  async function install() {
    if (!deferred) return;
    await deferred.prompt();
    await deferred.userChoice;
    setDeferred(null);
    close();
  }

  if (dismissed) return null;
  if (!deferred && !showIOS) return null;

  return (
    <div className="clay flex items-start gap-3 p-4">
      <span className="clay-sunken grid h-10 w-10 shrink-0 place-items-center rounded-2xl p-1.5">
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img src="/icons/logo.png" alt="" className="h-full w-full object-contain" />
      </span>
      <div className="min-w-0 flex-1">
        <p className="font-bold text-text">Pasang Apps KopkarYAPI</p>
        {deferred ? (
          <p className="text-sm text-muted">Akses cepat seperti aplikasi di HP.</p>
        ) : (
          <p className="text-sm text-muted">
            Ketuk <b>Bagikan</b> lalu <b>Tambah ke Layar Utama</b> untuk memasang
            & mengaktifkan notifikasi.
          </p>
        )}
        {deferred && (
          <button
            onClick={install}
            className="clay-primary mt-2 px-4 py-2 text-sm font-bold"
          >
            Pasang
          </button>
        )}
      </div>
      <button
        onClick={close}
        className="shrink-0 text-muted"
        aria-label="Tutup"
      >
        ✕
      </button>
    </div>
  );
}
