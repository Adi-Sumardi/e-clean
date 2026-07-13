"use client";

import { useEffect, useRef, useState } from "react";
import { compressImage } from "@/lib/image";

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
  const [busy, setBusy] = useState(false);
  const [pickError, setPickError] = useState<string | null>(null);
  const [urls, setUrls] = useState<string[]>([]);

  useEffect(() => {
    const next = value.map((b) => URL.createObjectURL(b));
    setUrls(next);
    return () => next.forEach((u) => URL.revokeObjectURL(u));
  }, [value]);

  async function onPick(e: React.ChangeEvent<HTMLInputElement>) {
    const files = Array.from(e.target.files ?? []);
    if (files.length === 0) return;
    setPickError(null);
    setBusy(true);
    try {
      const room = max - value.length;
      const picked = files.slice(0, Math.max(0, room));
      const compressed = await Promise.all(picked.map((f) => compressImage(f)));
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
            onClick={() => { setPickError(null); inputRef.current?.click(); }}
            disabled={busy}
            className="clay-sunken grid aspect-square place-items-center rounded-2xl text-3xl text-muted disabled:opacity-50"
          >
            {busy ? "⏳" : "+"}
          </button>
        )}
      </div>

      {pickError && (
        <p className="text-xs text-danger">{pickError}</p>
      )}

      <input
        ref={inputRef}
        type="file"
        accept="image/*"
        capture="environment"
        multiple
        hidden
        onChange={onPick}
      />
    </div>
  );
}
