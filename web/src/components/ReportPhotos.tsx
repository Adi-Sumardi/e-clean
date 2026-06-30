"use client";

import { useEffect, useState } from "react";

/** Menampilkan satu grup foto laporan dengan lightbox modal saat diklik. */
export default function ReportPhotos({
  label,
  urls,
}: {
  label: string;
  urls?: string[];
}) {
  const [activeIdx, setActiveIdx] = useState<number | null>(null);

  // Tutup modal saat tekan Escape, navigasi prev/next dengan arrow key
  useEffect(() => {
    if (activeIdx === null) return;
    function onKey(e: KeyboardEvent) {
      if (e.key === "Escape") setActiveIdx(null);
      if (e.key === "ArrowRight" && urls) setActiveIdx((i) => (i === null ? 0 : Math.min(i + 1, urls.length - 1)));
      if (e.key === "ArrowLeft") setActiveIdx((i) => (i === null ? 0 : Math.max(i! - 1, 0)));
    }
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [activeIdx, urls]);

  if (!urls || urls.length === 0) return null;

  return (
    <>
      <div className="flex flex-col gap-2">
        <span className="text-sm font-semibold text-muted">{label}</span>
        <div className="grid grid-cols-3 gap-2">
          {urls.map((u, i) => (
            <button
              key={u}
              type="button"
              onClick={() => setActiveIdx(i)}
              className="clay block aspect-square overflow-hidden rounded-2xl active:scale-95 transition-transform"
            >
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={u} alt={`${label} ${i + 1}`} className="h-full w-full object-cover" />
            </button>
          ))}
        </div>
      </div>

      {/* Lightbox modal */}
      {activeIdx !== null && (
        <div
          className="fixed inset-0 z-50 flex flex-col items-center justify-center bg-black/80 backdrop-blur-sm"
          onClick={() => setActiveIdx(null)}
        >
          {/* Header */}
          <div
            className="flex w-full max-w-2xl items-center justify-between px-4 py-3"
            onClick={(e) => e.stopPropagation()}
          >
            <span className="text-sm font-semibold text-white/80">
              {label} · {activeIdx + 1}/{urls.length}
            </span>
            <button
              type="button"
              onClick={() => setActiveIdx(null)}
              className="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20"
            >
              ✕
            </button>
          </div>

          {/* Gambar */}
          <div
            className="relative flex w-full max-w-2xl flex-1 items-center justify-center px-4"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Tombol prev */}
            {activeIdx > 0 && (
              <button
                type="button"
                onClick={() => setActiveIdx((i) => i! - 1)}
                className="absolute left-2 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white text-xl hover:bg-white/20"
              >
                ‹
              </button>
            )}

            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img
              src={urls[activeIdx]}
              alt={`${label} ${activeIdx + 1}`}
              className="max-h-[70vh] max-w-full rounded-2xl object-contain shadow-2xl"
            />

            {/* Tombol next */}
            {activeIdx < urls.length - 1 && (
              <button
                type="button"
                onClick={() => setActiveIdx((i) => i! + 1)}
                className="absolute right-2 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white text-xl hover:bg-white/20"
              >
                ›
              </button>
            )}
          </div>

          {/* Thumbnail strip (jika > 1 foto) */}
          {urls.length > 1 && (
            <div
              className="flex gap-2 overflow-x-auto px-4 py-3"
              onClick={(e) => e.stopPropagation()}
            >
              {urls.map((u, i) => (
                <button
                  key={u}
                  type="button"
                  onClick={() => setActiveIdx(i)}
                  className={`h-14 w-14 shrink-0 overflow-hidden rounded-xl transition-all ${
                    i === activeIdx
                      ? "ring-2 ring-white ring-offset-1 ring-offset-black"
                      : "opacity-50 hover:opacity-80"
                  }`}
                >
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img src={u} alt="" className="h-full w-full object-cover" />
                </button>
              ))}
            </div>
          )}
        </div>
      )}
    </>
  );
}
