"use client";

import { useEffect, useState } from "react";
import { pushSupported, isSubscribed, enablePush, disablePush } from "@/lib/push";

/** Tombol aktif/nonaktif notifikasi Web Push di Profil. */
export default function PushToggle() {
  const [supported, setSupported] = useState(true);
  const [on, setOn] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setSupported(pushSupported());
    isSubscribed().then(setOn).catch(() => setOn(false));
  }, []);

  async function toggle() {
    setBusy(true);
    setError(null);
    try {
      if (on) {
        await disablePush();
        setOn(false);
      } else {
        await enablePush();
        setOn(true);
      }
    } catch (e) {
      setError(e instanceof Error ? e.message : "Gagal mengubah notifikasi.");
    } finally {
      setBusy(false);
    }
  }

  if (!supported) {
    return (
      <div className="clay-sunken px-5 py-4 text-sm text-muted">
        🔔 Perangkat/browser ini belum mendukung notifikasi.
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-2">
      <button
        onClick={toggle}
        disabled={busy}
        className="clay-button flex items-center justify-between px-5 py-4 text-base font-semibold text-text disabled:opacity-60"
      >
        <span>🔔 Notifikasi {on ? "aktif" : "nonaktif"}</span>
        <span
          className={`relative h-7 w-12 rounded-full transition-colors ${
            on ? "bg-success" : "bg-surface-sunken"
          }`}
        >
          <span
            className={`absolute top-1 h-5 w-5 rounded-full bg-white shadow transition-all ${
              on ? "left-6" : "left-1"
            }`}
          />
        </span>
      </button>
      {error && <p className="px-2 text-xs text-danger">{error}</p>}
    </div>
  );
}
