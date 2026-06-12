"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useQueryClient } from "@tanstack/react-query";
import { useMe, useRoles } from "@/lib/hooks";
import { userService, type UserInput } from "@/lib/services";
import { roleLabel } from "@/lib/domain";
import { ApiError } from "@/lib/api";
import { Spinner } from "@/components/ui";

const EMPTY: UserInput = {
  name: "",
  email: "",
  password: "",
  phone: "",
  role: "",
  is_active: true,
};

export default function PetugasFormPage() {
  const router = useRouter();
  const qc = useQueryClient();
  const { admin } = useMe();
  const roles = useRoles(admin);

  const [id, setId] = useState<number | null>(null);
  const [form, setForm] = useState<UserInput>(EMPTY);
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
    userService
      .show(eid)
      .then((u) =>
        setForm({
          name: u.name,
          email: u.email,
          password: "",
          phone: u.phone ?? "",
          role: u.roles[0] ?? "",
          is_active: u.is_active,
        }),
      )
      .catch(() => setError("Gagal memuat data pengguna."))
      .finally(() => setLoading(false));
  }, []);

  const set = (k: keyof UserInput, v: string | boolean) =>
    setForm((f) => ({ ...f, [k]: v }));

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    if (!form.name.trim() || !form.email.trim() || !form.role) {
      return setError("Nama, email, dan role wajib diisi.");
    }
    if (!id && (form.password ?? "").length < 8) {
      return setError("Kata sandi minimal 8 karakter.");
    }
    setSaving(true);
    try {
      const payload: Partial<UserInput> = {
        name: form.name.trim(),
        email: form.email.trim(),
        phone: form.phone?.trim() || undefined,
        role: form.role,
        is_active: form.is_active,
      };
      if (form.password) payload.password = form.password;

      if (id) await userService.update(id, payload);
      else await userService.create(payload as UserInput);
      qc.invalidateQueries({ queryKey: ["users"] });
      router.replace("/kelola/petugas");
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Gagal menyimpan.");
      setSaving(false);
    }
  }

  if (loading) return <Spinner />;

  return (
    <div className="flex flex-col gap-5">
      <Link href="/kelola/petugas" className="text-sm font-semibold text-primary">
        ← Batal
      </Link>
      <h1 className="text-xl font-bold text-text">
        {id ? "Edit Pengguna" : "Tambah Pengguna"}
      </h1>

      <form onSubmit={submit} className="flex flex-col gap-4">
        <Field label="Nama *">
          <input
            value={form.name}
            onChange={(e) => set("name", e.target.value)}
            className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none placeholder:text-muted"
            placeholder="Nama lengkap"
          />
        </Field>

        <Field label="Email *">
          <input
            type="email"
            value={form.email}
            onChange={(e) => set("email", e.target.value)}
            className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none placeholder:text-muted"
            placeholder="nama@email.com"
          />
        </Field>

        <Field label="Nomor HP">
          <input
            value={form.phone}
            onChange={(e) => set("phone", e.target.value)}
            className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none placeholder:text-muted"
            placeholder="08xxxxxxxxxx"
          />
        </Field>

        <Field label="Role *">
          <select
            value={form.role}
            onChange={(e) => set("role", e.target.value)}
            className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none"
          >
            <option value="">Pilih role…</option>
            {roles.data?.map((r) => (
              <option key={r} value={r}>
                {roleLabel(r)}
              </option>
            ))}
          </select>
        </Field>

        <Field label={id ? "Kata sandi (kosongkan jika tidak diubah)" : "Kata sandi *"}>
          <input
            type="password"
            value={form.password}
            onChange={(e) => set("password", e.target.value)}
            className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none placeholder:text-muted"
            placeholder={id ? "••••••••" : "Min. 8 karakter"}
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
