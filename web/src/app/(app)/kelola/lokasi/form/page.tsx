"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useQueryClient } from "@tanstack/react-query";
import { useMe, useUnitList } from "@/lib/hooks";
import { lokasiService, type LokasiInput } from "@/lib/services";
import { ApiError } from "@/lib/api";
import { Spinner } from "@/components/ui";

const EMPTY: LokasiInput = {
  unit_id: 0,
  kode_lokasi: "",
  nama_lokasi: "",
  kategori: "",
  lantai: "",
  deskripsi: "",
  is_active: true,
};

export default function LokasiFormPage() {
  const router = useRouter();
  const qc = useQueryClient();
  const { manager } = useMe();
  const units = useUnitList(manager);

  const [id, setId] = useState<number | null>(null);
  const [form, setForm] = useState<LokasiInput>(EMPTY);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const raw = new URLSearchParams(window.location.search).get("id");
    const eid = raw ? Number(raw) : null;
    setId(eid);
    if (!eid) {
      setLoading(false);
      return;
    }
    lokasiService
      .show(eid)
      .then((l) =>
        setForm({
          unit_id: l.unit?.id ?? 0,
          kode_lokasi: l.kode_lokasi,
          nama_lokasi: l.nama_lokasi,
          kategori: l.kategori,
          lantai: l.lantai ?? "",
          deskripsi: l.deskripsi ?? "",
          is_active: l.is_active ?? true,
        }),
      )
      .catch(() => setError("Gagal memuat data lokasi."))
      .finally(() => setLoading(false));
  }, []);

  const set = (k: keyof LokasiInput, v: string | number | boolean) =>
    setForm((f) => ({ ...f, [k]: v }));

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    if (!form.unit_id) return setError("Pilih unit dulu.");
    if (!form.kode_lokasi.trim() || !form.nama_lokasi.trim() || !form.kategori.trim()) {
      return setError("Kode, nama, dan kategori wajib diisi.");
    }
    setSaving(true);
    try {
      const payload: LokasiInput = {
        ...form,
        lantai: form.lantai?.trim() || undefined,
        deskripsi: form.deskripsi?.trim() || undefined,
      };
      if (id) await lokasiService.update(id, payload);
      else await lokasiService.create(payload);
      qc.invalidateQueries({ queryKey: ["lokasi"] });
      router.replace("/kelola/lokasi");
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Gagal menyimpan.");
      setSaving(false);
    }
  }

  if (loading) return <Spinner />;

  return (
    <div className="flex flex-col gap-5">
      <Link href="/kelola/lokasi" className="text-sm font-semibold text-primary">
        ← Batal
      </Link>
      <h1 className="text-xl font-bold text-text">
        {id ? "Edit Lokasi" : "Tambah Lokasi"}
      </h1>

      <form onSubmit={submit} className="flex flex-col gap-4">
        <Field label="Unit *">
          <select
            value={form.unit_id || ""}
            onChange={(e) => set("unit_id", Number(e.target.value))}
            className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none"
          >
            <option value="">Pilih unit…</option>
            {units.data?.map((u) => (
              <option key={u.id} value={u.id}>
                {u.nama_unit}
              </option>
            ))}
          </select>
        </Field>

        <Field label="Kode lokasi *">
          <input
            value={form.kode_lokasi}
            onChange={(e) => set("kode_lokasi", e.target.value)}
            placeholder="mis. LOK-001"
            className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none placeholder:text-muted"
          />
        </Field>

        <Field label="Nama lokasi *">
          <input
            value={form.nama_lokasi}
            onChange={(e) => set("nama_lokasi", e.target.value)}
            placeholder="mis. Toilet Lantai 1"
            className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none placeholder:text-muted"
          />
        </Field>

        <Field label="Kategori *">
          <select
            value={form.kategori}
            onChange={(e) => set("kategori", e.target.value)}
            className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none"
          >
            <option value="">Pilih kategori…</option>
            <option value="ruang_kelas">Ruang Kelas</option>
            <option value="toilet">Toilet</option>
            <option value="kantor">Kantor</option>
            <option value="aula">Aula</option>
            <option value="taman">Taman</option>
            <option value="koridor">Koridor</option>
            <option value="lainnya">Lainnya</option>
          </select>
        </Field>

        <Field label="Lantai">
          <input
            value={form.lantai}
            onChange={(e) => set("lantai", e.target.value)}
            placeholder="mis. 1"
            className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none placeholder:text-muted"
          />
        </Field>

        <Field label="Deskripsi">
          <textarea
            value={form.deskripsi}
            onChange={(e) => set("deskripsi", e.target.value)}
            rows={2}
            className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none placeholder:text-muted"
          />
        </Field>

        <label className="clay-button flex items-center justify-between px-5 py-3">
          <span className="text-sm font-semibold text-text">Aktif</span>
          <input
            type="checkbox"
            checked={!!form.is_active}
            onChange={(e) => set("is_active", e.target.checked)}
            className="h-5 w-5 accent-primary"
          />
        </label>

        {error && (
          <p className="rounded-2xl bg-danger/10 px-4 py-3 text-sm text-danger">{error}</p>
        )}

        <button
          type="submit"
          disabled={saving}
          className="clay-primary w-full px-6 py-4 text-base font-bold disabled:opacity-60"
        >
          {saving ? "Menyimpan…" : "Simpan"}
        </button>
      </form>
    </div>
  );
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <label className="flex flex-col gap-2">
      <span className="text-sm font-semibold text-text">{label}</span>
      {children}
    </label>
  );
}
