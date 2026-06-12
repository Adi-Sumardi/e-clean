"use client";

import { useState } from "react";
import Link from "next/link";
import { useQueryClient } from "@tanstack/react-query";
import { useMe, useUnitList } from "@/lib/hooks";
import { unitService, type UnitInput } from "@/lib/services";
import { ApiError } from "@/lib/api";
import { PageHeader, Spinner, EmptyState, ErrorState } from "@/components/ui";
import type { Unit } from "@/lib/types";

const EMPTY: UnitInput = { kode_unit: "", nama_unit: "", deskripsi: "", is_active: true };

export default function UnitPage() {
  const { manager } = useMe();
  const qc = useQueryClient();
  const { data, isLoading, isError, refetch } = useUnitList(manager);

  const [editId, setEditId] = useState<number | null>(null);
  const [form, setForm] = useState<UnitInput>(EMPTY);
  const [open, setOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const set = (k: keyof UnitInput, v: string | boolean) =>
    setForm((f) => ({ ...f, [k]: v }));

  function startAdd() {
    setEditId(null);
    setForm(EMPTY);
    setError(null);
    setOpen(true);
  }
  function startEdit(u: Unit) {
    setEditId(u.id);
    setForm({
      kode_unit: u.kode_unit,
      nama_unit: u.nama_unit,
      deskripsi: u.deskripsi ?? "",
      is_active: u.is_active ?? true,
    });
    setError(null);
    setOpen(true);
  }

  async function save(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    if (!form.kode_unit.trim() || !form.nama_unit.trim()) {
      return setError("Kode dan nama unit wajib diisi.");
    }
    setSaving(true);
    try {
      const payload = { ...form, deskripsi: form.deskripsi?.trim() || undefined };
      if (editId) await unitService.update(editId, payload);
      else await unitService.create(payload);
      qc.invalidateQueries({ queryKey: ["units"] });
      setOpen(false);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Gagal menyimpan.");
    } finally {
      setSaving(false);
    }
  }

  async function hapus(u: Unit) {
    if (!confirm(`Hapus unit "${u.nama_unit}"?`)) return;
    try {
      await unitService.remove(u.id);
      qc.invalidateQueries({ queryKey: ["units"] });
    } catch {
      alert("Gagal menghapus. Mungkin unit masih dipakai lokasi.");
    }
  }

  return (
    <div className="flex flex-col gap-5">
      <Link href="/beranda" className="text-sm font-semibold text-primary">
        ← Kembali
      </Link>
      <PageHeader
        title="Unit"
        right={
          <button onClick={startAdd} className="clay-primary px-4 py-2 text-sm font-bold">
            + Tambah
          </button>
        }
      />

      {open && (
        <form onSubmit={save} className="clay flex flex-col gap-3 p-5">
          <p className="font-bold text-text">{editId ? "Edit Unit" : "Unit Baru"}</p>
          <input
            value={form.kode_unit}
            onChange={(e) => set("kode_unit", e.target.value)}
            placeholder="Kode unit (mis. UNT-01)"
            className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none placeholder:text-muted"
          />
          <input
            value={form.nama_unit}
            onChange={(e) => set("nama_unit", e.target.value)}
            placeholder="Nama unit"
            className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none placeholder:text-muted"
          />
          <textarea
            value={form.deskripsi}
            onChange={(e) => set("deskripsi", e.target.value)}
            rows={2}
            placeholder="Deskripsi (opsional)"
            className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none placeholder:text-muted"
          />
          {error && <p className="text-sm text-danger">{error}</p>}
          <div className="flex gap-3">
            <button
              type="button"
              onClick={() => setOpen(false)}
              className="clay-button flex-1 px-4 py-3 text-sm font-semibold text-muted"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={saving}
              className="clay-primary flex-1 px-4 py-3 text-sm font-bold disabled:opacity-60"
            >
              {saving ? "Menyimpan…" : "Simpan"}
            </button>
          </div>
        </form>
      )}

      {!manager ? (
        <EmptyState icon="🔒" title="Khusus supervisor / admin" />
      ) : isLoading ? (
        <Spinner />
      ) : isError ? (
        <ErrorState message="Gagal memuat unit." onRetry={() => refetch()} />
      ) : data && data.length > 0 ? (
        <div className="flex flex-col gap-3">
          {data.map((u) => (
            <div key={u.id} className="clay flex items-center justify-between gap-3 p-4">
              <div className="min-w-0">
                <p className="font-bold text-text">{u.nama_unit}</p>
                <p className="text-sm text-muted">{u.kode_unit}</p>
              </div>
              <div className="flex gap-2">
                <button
                  onClick={() => startEdit(u)}
                  className="clay-button px-3 py-2 text-sm font-semibold text-text"
                >
                  Edit
                </button>
                <button
                  onClick={() => hapus(u)}
                  className="clay-button px-3 py-2 text-sm font-semibold text-danger"
                >
                  Hapus
                </button>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <EmptyState icon="🏢" title="Belum ada unit" />
      )}
    </div>
  );
}
