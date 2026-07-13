"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useQueryClient } from "@tanstack/react-query";
import { useMe } from "@/lib/hooks";
import { REPORT_SCHEMAS, type ReportField, type ReportSchema } from "@/lib/reportForms";
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

  const [params, setParams] = useState<{ jadwal?: string; lokasi?: string; nama?: string; shift?: string }>({});
  const [text, setText] = useState<Record<string, string>>({});
  const [photos, setPhotos] = useState<Record<string, Blob[]>>({});
  const [lists, setLists] = useState<Record<string, Array<{ item: string; done: boolean }>>>({});
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    const sp = new URLSearchParams(window.location.search);
    setParams({
      jadwal: sp.get("jadwal") ?? undefined,
      lokasi: sp.get("lokasi") ?? undefined,
      nama: sp.get("nama") ?? undefined,
      shift: sp.get("shift") ?? undefined,
    });
  }, []);

  const baseSchema = domain ? REPORT_SCHEMAS[domain.key] : null;

  // Satpam: foto wajib & batas berbeda tergantung shift dari jadwal.
  const schema = useMemo((): ReportSchema | null => {
    if (!baseSchema || domain?.key !== "satpam") return baseSchema;
    const shift = params.shift ?? "";
    const isMalam = shift === "security-malam";
    const isPagi = shift === "security-pagi" ||
      shift === "security-standby-pagi" ||
      shift === "security-standby-malam";
    if (!isMalam && !isPagi) return baseSchema;
    const maxFoto = isMalam ? 15 : 5;
    const label = isMalam
      ? `Foto Patroli Malam (wajib, maks. ${maxFoto})`
      : `Foto Patroli Pagi (wajib, maks. ${maxFoto})`;
    return {
      ...baseSchema,
      fields: baseSchema.fields.map((f) =>
        f.kind === "photos" && f.name === "foto"
          ? { ...f, label, required: true, min: 1, max: maxFoto }
          : f,
      ),
    };
  }, [baseSchema, domain?.key, params.shift]);

  // Prefill jam_mulai = sekarang + init checklist defaultItems.
  useEffect(() => {
    if (!schema) return;
    const defaults: Record<string, string> = {};
    const defaultLists: Record<string, Array<{ item: string; done: boolean }>> = {};
    schema.fields.forEach((f) => {
      if (f.kind === "time" && f.defaultNow) defaults[f.name] = nowHHMM();
      if (f.kind === "checklist") {
        defaultLists[f.name] = f.defaultItems.map((item) => ({ item, done: false }));
      }
    });
    setText((t) => ({ ...defaults, ...t }));
    setLists((l) => ({ ...defaultLists, ...l }));
  }, [schema]);

  const setField = (name: string, val: string) =>
    setText((t) => ({ ...t, [name]: val }));
  const setPhotoField = (name: string, blobs: Blob[]) =>
    setPhotos((p) => ({ ...p, [name]: blobs }));
  const setListField = (name: string, items: Array<{ item: string; done: boolean }>) =>
    setLists((l) => ({ ...l, [name]: items }));

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
      if (params.shift) fields.shift = params.shift;
      if (coords) fields.koordinat_lokasi = coords;
      // Serialisasi checklist sebagai indexed FormData agar Laravel parse sebagai array.
      // checklist[0][item]=... & checklist[0][done]=1
      for (const [name, items] of Object.entries(lists)) {
        items.forEach((entry, i) => {
          fields[`${name}[${i}][item]`] = entry.item;
          fields[`${name}[${i}][done]`] = entry.done ? "1" : "0";
        });
      }

      let sent = 0;
      let savedToOutbox = false;
      const idempotencyKey =
        typeof crypto !== "undefined" && "randomUUID" in crypto
          ? crypto.randomUUID()
          : `${Date.now()}-${Math.random().toString(36).slice(2)}`;

      try {
        await enqueue({
          domain: domain!.key,
          endpoint: domain!.laporanBase,
          fields,
          photos,
          label: params.nama ?? "Laporan",
          idempotencyKey,
        });
        savedToOutbox = true;
      } catch {
        // IndexedDB tidak tersedia (storage penuh / iOS private mode).
        // Jika online, kirim langsung tanpa outbox.
        if (!isOnline()) {
          setError("Tidak ada koneksi & penyimpanan lokal tidak tersedia. Coba saat online.");
          setSubmitting(false);
          return;
        }
        const fd = new FormData();
        for (const [k, v] of Object.entries(fields)) fd.append(k, v);
        for (const [field, blobs] of Object.entries(photos)) {
          blobs.forEach((blob, i) => fd.append(`${field}[]`, blob, `${field}-${i}.jpg`));
        }
        const { api } = await import("@/lib/api");
        await api.post(domain!.laporanBase, {
          form: fd,
          headers: { "Idempotency-Key": idempotencyKey },
        });
        sent = 1;
      }

      // Coba kirim sekarang; kalau offline, tetap aman di outbox.
      if (savedToOutbox && isOnline()) sent = await syncOutbox();

      qc.invalidateQueries({ queryKey: ["laporan"] });
      qc.invalidateQueries({ queryKey: ["jadwal"] });

      router.replace(`/laporan?sent=${sent > 0 ? "1" : "0"}`);
    } catch {
      setError("Gagal mengirim laporan. Periksa koneksi lalu coba lagi.");
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
            listItems={lists[f.name] ?? []}
            onList={(items) => setListField(f.name, items)}
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
  listItems,
  onList,
}: {
  field: ReportField;
  value: string;
  onText: (v: string) => void;
  photos: Blob[];
  onPhotos: (b: Blob[]) => void;
  listItems: Array<{ item: string; done: boolean }>;
  onList: (items: Array<{ item: string; done: boolean }>) => void;
}) {
  if (field.kind === "checklist") {
    return (
      <ChecklistField
        label={field.label}
        items={listItems}
        onChange={onList}
      />
    );
  }

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

function ChecklistField({
  label,
  items,
  onChange,
}: {
  label: string;
  items: Array<{ item: string; done: boolean }>;
  onChange: (items: Array<{ item: string; done: boolean }>) => void;
}) {
  const [newItem, setNewItem] = useState("");

  function toggle(i: number) {
    const next = items.map((it, idx) => idx === i ? { ...it, done: !it.done } : it);
    onChange(next);
  }

  function remove(i: number) {
    onChange(items.filter((_, idx) => idx !== i));
  }

  function addItem() {
    const trimmed = newItem.trim();
    if (!trimmed) return;
    onChange([...items, { item: trimmed, done: false }]);
    setNewItem("");
  }

  const doneCount = items.filter((it) => it.done).length;

  return (
    <div className="flex flex-col gap-2">
      <div className="flex items-center justify-between">
        <span className="text-sm font-semibold text-text">{label}</span>
        {items.length > 0 && (
          <span className="text-xs font-semibold text-primary">
            {doneCount}/{items.length} selesai
          </span>
        )}
      </div>

      <div className="clay-sunken flex flex-col divide-y divide-border rounded-2xl overflow-hidden">
        {items.map((it, i) => (
          <div key={i} className="flex items-center gap-3 px-4 py-3">
            <button
              type="button"
              onClick={() => toggle(i)}
              className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border-2 transition-colors ${
                it.done
                  ? "border-success bg-success text-white"
                  : "border-border bg-surface"
              }`}
            >
              {it.done && <span className="text-xs font-bold">✓</span>}
            </button>
            <span
              className={`flex-1 text-sm ${
                it.done ? "line-through text-muted" : "text-text"
              }`}
            >
              {it.item}
            </span>
            <button
              type="button"
              onClick={() => remove(i)}
              className="text-muted hover:text-danger text-base leading-none"
            >
              ×
            </button>
          </div>
        ))}

        {/* Input tambah item baru */}
        <div className="flex items-center gap-2 px-4 py-2">
          <input
            type="text"
            value={newItem}
            onChange={(e) => setNewItem(e.target.value)}
            onKeyDown={(e) => { if (e.key === "Enter") { e.preventDefault(); addItem(); } }}
            placeholder="Tambah item…"
            className="flex-1 bg-transparent py-1 text-sm text-text outline-none placeholder:text-muted"
          />
          <button
            type="button"
            onClick={addItem}
            disabled={!newItem.trim()}
            className="text-primary font-bold text-sm disabled:opacity-30"
          >
            + Tambah
          </button>
        </div>
      </div>
    </div>
  );
}
