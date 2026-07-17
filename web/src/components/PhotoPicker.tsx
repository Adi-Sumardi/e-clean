"use client";

import { useEffect, useRef, useState } from "react";
import dynamic from "next/dynamic";

// Lazy-load kamera (besar, hanya diperlukan saat tap +)
const CameraCapture = dynamic(() => import("./CameraCapture"), { ssr: false });

function hasGetUserMedia(): boolean {
  return (
    typeof navigator !== "undefined" &&
    !!navigator.mediaDevices?.getUserMedia
  );
}

export default function PhotoPicker({
  label,
  max = 5,
  required,
  value,
  onChange,
}: {
  label: string;
  max?: number;
  required?: boolean;
  value: Blob[];
  onChange: (blobs: Blob[]) => void;
}) {
  const inputRef = useRef<HTMLInputElement>(null);
  const [cameraOpen, setCameraOpen] = useState(false);
  const [busy, setBusy] = useState(false);
  const [pickError, setPickError] = useState<string | null>(null);
  const [urls, setUrls] = useState<string[]>([]);

  useEffect(() => {
    const next = value.map((b) => URL.createObjectURL(b));
    setUrls(next);
    return () => next.forEach((u) => URL.revokeObjectURL(u));
  }, [value]);

  function openCamera() {
    setPickError(null);
    if (hasGetUserMedia()) {
      setCameraOpen(true);
    } else {
      // Fallback: file input biasa (foto masuk ke galeri HP)
      inputRef.current?.click();
    }
  }

  function onCaptured(blob: Blob) {
    onChange([...value, blob]);
    setCameraOpen(false);
  }

  // Fallback handler (dipakai hanya jika getUserMedia tidak tersedia)
  async function onFilePick(e: React.ChangeEvent<HTMLInputElement>) {
    const files = Array.from(e.target.files ?? []);
    if (files.length === 0) return;
    setPickError(null);
    setBusy(true);
    try {
      const room = max - value.length;
      const picked = files.slice(0, Math.max(0, room));
      // Satu per satu agar RAM tidak melonjak
      const compressed: Blob[] = [];
      for (const f of picked) {
        const { compressImage } = await import("@/lib/image");
        compressed.push(await compressImage(f));
      }
      onChange([...value, ...compressed]);
    } catch {
      setPickError("Gagal memproses foto. Coba pilih ulang.");
    } finally {
      setBusy(false);
      if (inputRef.current) inputRef.current.value = "";
    }
  }

  function remove(i: number) {
    onChange(value.filter((_, idx) => idx !== i));
  }

  const full = value.length >= max;

  return (
    <>
      {cameraOpen && (
        <CameraCapture
          onCapture={onCaptured}
          onClose={() => setCameraOpen(false)}
        />
      )}

      <div className="flex flex-col gap-2">
        <span className="text-sm font-semibold text-text">
          {label}
          {required && <span className="text-danger"> *</span>}
          <span className="ml-1 font-normal text-muted">
            ({value.length}/{max})
          </span>
        </span>

        <div className="grid grid-cols-3 gap-3">
          {urls.map((u, i) => (
            <div key={u} className="clay relative aspect-square overflow-hidden rounded-2xl">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={u} alt="" className="h-full w-full object-cover" />
              <button
                type="button"
                onClick={() => remove(i)}
                className="absolute right-1 top-1 grid h-6 w-6 place-items-center rounded-full bg-danger text-xs font-bold text-white"
                aria-label="Hapus foto"
              >
                ✕
              </button>
            </div>
          ))}

          {!full && (
            <button
              type="button"
              onClick={openCamera}
              disabled={busy}
              className="clay-sunken grid aspect-square place-items-center rounded-2xl text-3xl text-muted disabled:opacity-50"
            >
              {busy ? "⏳" : "📷"}
            </button>
          )}
        </div>

        {pickError && (
          <p className="text-xs text-danger">{pickError}</p>
        )}

        {/* File input — fallback saja jika getUserMedia tidak tersedia */}
        <input
          ref={inputRef}
          type="file"
          accept="image/*"
          capture="environment"
          hidden
          onChange={onFilePick}
        />
      </div>
    </>
  );
}
