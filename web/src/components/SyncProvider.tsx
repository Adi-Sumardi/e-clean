"use client";

import { useEffect, useState } from "react";
import { useQueryClient } from "@tanstack/react-query";
import { subscribeOutbox, failedCount, clearFailedJobs, resetStuckJobs } from "@/lib/outbox";
import { syncOutbox, onSynced, isOnline } from "@/lib/sync";

/**
 * Mengatur sinkronisasi outbox + menampilkan banner offline & status sync.
 * Trigger sync: saat online kembali, app difokuskan, dan saat pertama dibuka.
 */
export default function SyncProvider() {
  const qc = useQueryClient();
  const [pending, setPending] = useState(0);
  const [failed, setFailed] = useState(0);
  const [online, setOnline] = useState(true);
  const [justSynced, setJustSynced] = useState(false);

  useEffect(() => {
    setOnline(isOnline());

    const unsubCount = subscribeOutbox((count) => {
      setPending(count);
      failedCount().then(setFailed).catch(() => {});
    });
    const unsubSynced = onSynced(() => {
      qc.invalidateQueries({ queryKey: ["laporan"] });
      qc.invalidateQueries({ queryKey: ["jadwal"] });
      setJustSynced(true);
      setTimeout(() => setJustSynced(false), 2500);
    });

    const goOnline = () => {
      setOnline(true);
      void syncOutbox();
    };
    const goOffline = () => setOnline(false);
    const onVisible = () => {
      if (document.visibilityState === "visible" && isOnline()) void syncOutbox();
    };

    window.addEventListener("online", goOnline);
    window.addEventListener("offline", goOffline);
    document.addEventListener("visibilitychange", onVisible);

    // Reset job macet (crash mid-upload) lalu langsung sync.
    void resetStuckJobs().then(() => syncOutbox());

    return () => {
      unsubCount();
      unsubSynced();
      window.removeEventListener("online", goOnline);
      window.removeEventListener("offline", goOffline);
      document.removeEventListener("visibilitychange", onVisible);
    };
  }, [qc]);

  return (
    <>
      {!online && (
        <div className="fixed inset-x-0 top-0 z-40 bg-warning/90 py-2 text-center text-xs font-bold text-[#5a3d00] backdrop-blur">
          ⚠️ Mode offline — laporan tersimpan & dikirim otomatis saat online
        </div>
      )}

      {failed > 0 && (
        <div className="fixed inset-x-0 bottom-24 z-40 flex justify-center px-4">
          <button
            onClick={() => clearFailedJobs()}
            className="clay flex items-center gap-2 px-5 py-2 text-sm font-semibold text-danger"
          >
            <span className="h-2.5 w-2.5 rounded-full bg-danger" />
            {failed} laporan gagal terkirim · Hapus
          </button>
        </div>
      )}

      {failed === 0 && (pending > 0 || justSynced) && (
        <div className="fixed inset-x-0 bottom-24 z-40 flex justify-center px-4">
          <button
            onClick={() => syncOutbox()}
            className="clay flex items-center gap-2 px-5 py-2 text-sm font-semibold text-text"
          >
            {pending > 0 ? (
              <>
                <span className="h-2.5 w-2.5 animate-pulse rounded-full bg-warning" />
                {pending} laporan menunggu sync
                {online && <span className="text-primary">· Sync</span>}
              </>
            ) : (
              <>
                <span className="h-2.5 w-2.5 rounded-full bg-success" />
                Tersinkron
              </>
            )}
          </button>
        </div>
      )}
    </>
  );
}
