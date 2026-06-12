"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useQueryClient } from "@tanstack/react-query";
import { useMe } from "@/lib/hooks";
import { REPORT_SCHEMAS, type ReportField } from "@/lib/reportForms";
import { enqueue } from "@/lib/outbox";
import { syncOutbox, isOnline } from "@/lib/sync";
import { Spinner } from "@/components/ui";
import PhotoPicker from "@/components/PhotoPicker";

function nowHHMM(): string {
  const d = new Date();
  return `${String(d.getHours()).padStart(2, "0")}:${String(d.getMinutes()).padStart(2, "0")}`;
}

/** Ambil koordinat (opsional, timeout cepat). */
function getCoords(): Promise<string | null> {
  return new Promise((resolve) => {
    if (typeof navigator === "undefined" || !navigator.geolocation) {
      return resolve(null);
    }
    navigator.geolocation.getCurrentPosition(
      (p) => resolve(`${p.coords.latitude},${p.coords.longitude}`),
      () => resolve(null),
      { timeout: 4000, maximumAge: 60000 },
    );
  });
}

export default function LaporanBaruPage() {
  const router = useRouter();
  const qc = useQueryClient();
  const { domain } = useMe();

  const [params, setParams] = useState<{ jadwal?: string; lokasi?: string; nama?: string }>({});
  const [text, setText] = useState<Record<string, string>>({});
  const [photos, setPhotos] = useState<Record<string, Blob[]>>({});
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    const sp = new URLSearchParams(window.location.search);
    setParams({
      jadwal: sp.get("jadwal") ?? undefined,
      lokasi: sp.get("lokasi") ?? undefined,
      nama: sp.get("nama") ?? undefined,
    });
  }, []);

  const schema = domain ? REPORT_SCHEMAS[domain.key] : null;

  // Prefill jam_mulai = sekarang.
  useEffect(() => {
    if (!schema) return;
    const defaults: Record<string, string> = {};
    schema.fields.forEach((f) => {
      if (f.kind === "time" && f.defaultNow) defaults[f.name] = nowHHMM();
    });
    setText((t) => ({ ...defaults, ...t }));
  }, [schema]);

  const setField = (name: string, val: string) =>
    setText((t) => ({ ...t, [name]: val }));
  const setPhotoField = (name: string, blobs: Blob[]) =>
    setPhotos((p) => ({ ...p, [name]: blobs }));

  function validate(): string | null {
    if (!schema) return "Domain tidak dikenali.";
    if (!params.lokasi) return "Lokasi tidak diketahui. Buka laporan dari jadwal.";
    for (const f of schema.fields) {
      if (f.kind === "photos") {
        const count = photos[f.name]?.length ?? 0;
        if (f.required && count < (f.min ?? 1)) return `${f.label} wajib diisi.`;
      } else {
        const v = (text[f.name] ?? "").trim();
        if (f.required && !v) return `${f.label} wajib diisi.`;
        if (f.kind === "textarea" && f.min && v && v.length < f.min)
          return `${f.label} minimal ${f.min} karakter.`;
      }
    }
    return null;
  }

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    const err = validate();
    if (err) {
      setError(err);
      return;
    }
    setError(null);
    setSubmitting(true);
    try {
      const coords = await getCoords();
      const fields: Record<string, string> = {
        ...Object.fromEntries(
          Object.entries(text).filter(([, v]) => v !== "" && v != null),
        ),
        lokasi_id: params.lokasi!,
        tanggal: new Date().toISOString().slice(0, 10),
        status: "submitted",
      };
      if (params.jadwal) fields.jadwal_id = params.jadwal;
      if (coords) fields.koordinat_lokasi = coords;

      await enqueue({
        domain: domain!.key,
        endpoint: domain!.laporanBase,
        fields,
        photos,
        label: params.nama ?? "Laporan",
      });

      // Coba kirim sekarang; kalau offline, tetap aman di outbox.
      let sent = 0;
      if (isOnline()) sent = await syncOutbox();
      qc.invalidateQueries({ queryKey: ["laporan"] });

      router.replace(`/laporan?sent=${sent > 0 ? "1" : "0"}`);
    } catch {
      setError("Gagal menyimpan laporan. Coba lagi.");
      setSubmitting(false);
    }
  }

  if (!domain || !schema) return <Spinner />;

  return (
    <div className="flex flex-col gap-5">
      <Link href="/jadwal" className="text-sm font-semibold text-primary">
        ← Batal
      </Link>
      <div>
        <h1 className="text-xl font-bold text-text">{schema.title}</h1>
        {params.nama && <p className="text-sm text-muted">📍 {params.nama}</p>}
      </div>

      <form onSubmit={onSubmit} className="flex flex-col gap-4">
        {schema.fields.map((f) => (
          <FieldRenderer
            key={f.name}
            field={f}
            value={text[f.name] ?? ""}
            onText={(v) => setField(f.name, v)}
            photos={photos[f.name] ?? []}
            onPhotos={(b) => setPhotoField(f.name, b)}
          />
        ))}

        {error && (
          <p className="rounded-2xl bg-danger/10 px-4 py-3 text-sm text-danger">
            {error}
          </p>
        )}

        <button
          type="submit"
          disabled={submitting}
          className="clay-primary mt-1 w-full px-6 py-4 text-base font-bold disabled:opacity-60"
        >
          {submitting ? "Menyimpan…" : "Kirim Laporan"}
        </button>
        <p className="text-center text-xs text-muted">
          Bisa dikirim walau offline — tersimpan & otomatis tersinkron saat online.
        </p>
      </form>
    </div>
  );
}

function FieldRenderer({
  field,
  value,
  onText,
  photos,
  onPhotos,
}: {
  field: ReportField;
  value: string;
  onText: (v: string) => void;
  photos: Blob[];
  onPhotos: (b: Blob[]) => void;
}) {
  if (field.kind === "photos") {
    return (
      <PhotoPicker
        label={field.label}
        max={field.max}
        required={field.required}
        value={photos}
        onChange={onPhotos}
      />
    );
  }

  const labelEl = (
    <span className="text-sm font-semibold text-text">
      {field.label}
      {field.required && <span className="text-danger"> *</span>}
    </span>
  );

  if (field.kind === "select") {
    return (
      <label className="flex flex-col gap-2">
        {labelEl}
        <select
          value={value}
          onChange={(e) => onText(e.target.value)}
          className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none"
        >
          <option value="">Pilih…</option>
          {field.options.map((o) => (
            <option key={o.value} value={o.value}>
              {o.label}
            </option>
          ))}
        </select>
      </label>
    );
  }

  if (field.kind === "textarea") {
    return (
      <label className="flex flex-col gap-2">
        {labelEl}
        <textarea
          value={value}
          onChange={(e) => onText(e.target.value)}
          placeholder={field.placeholder}
          rows={3}
          className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none placeholder:text-muted"
        />
      </label>
    );
  }

  // time | text
  return (
    <label className="flex flex-col gap-2">
      {labelEl}
      <input
        type={field.kind === "time" ? "time" : "text"}
        value={value}
        onChange={(e) => onText(e.target.value)}
        placeholder={field.kind === "text" ? field.placeholder : undefined}
        className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none placeholder:text-muted"
      />
    </label>
  );
}
