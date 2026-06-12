"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useQueryClient } from "@tanstack/react-query";
import { useMe, useSettings } from "@/lib/hooks";
import { settingService } from "@/lib/services";
import { ApiError } from "@/lib/api";
import { PageHeader, Spinner, EmptyState, ErrorState } from "@/components/ui";

export default function PengaturanPage() {
  const { manager } = useMe();
  const qc = useQueryClient();
  const { data, isLoading, isError, refetch } = useSettings(manager);

  const [toleransi, setToleransi] = useState("");
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [saved, setSaved] = useState(false);

  useEffect(() => {
    if (data) setToleransi(String(data.reporting_tolerance_minutes));
  }, [data]);

  async function save(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setSaved(false);

    const value = Number(toleransi);
    if (!Number.isInteger(value) || value < 1 || value > 120) {
      return setError("Toleransi harus angka 1–120 menit.");
    }

    setSaving(true);
    try {
      await settingService.update({ reporting_tolerance_minutes: value });
      qc.invalidateQueries({ queryKey: ["settings"] });
      setSaved(true);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Gagal menyimpan pengaturan.");
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="flex flex-col gap-5">
      <Link href="/beranda" className="text-sm font-semibold text-primary">
        ← Kembali
      </Link>
      <PageHeader title="Pengaturan Aplikasi" subtitle="Konfigurasi laporan kegiatan" />

      {!manager ? (
        <EmptyState icon="🔒" title="Khusus supervisor / admin" />
      ) : isLoading ? (
        <Spinner />
      ) : isError ? (
        <ErrorState message="Gagal memuat pengaturan." onRetry={() => refetch()} />
      ) : (
        <form onSubmit={save} className="clay flex flex-col gap-3 p-5">
          <div>
            <p className="font-bold text-text">Toleransi Keterlambatan</p>
            <p className="text-sm text-muted">
              Jumlah menit setelah jam selesai jadwal yang masih dianggap
              &ldquo;terlambat&rdquo;. Lewat dari ini, sistem otomatis mencatat
              &ldquo;Tidak Lapor&rdquo; di Laporan Keterlambatan.
            </p>
          </div>

          <div className="flex items-center gap-3">
            <input
              value={toleransi}
              onChange={(e) => setToleransi(e.target.value)}
              type="number"
              min={1}
              max={120}
              inputMode="numeric"
              className="clay-sunken w-28 rounded-2xl px-4 py-3 text-text outline-none"
            />
            <span className="text-sm font-semibold text-muted">menit</span>
          </div>

          {error && <p className="text-sm text-danger">{error}</p>}
          {saved && !error && (
            <p className="text-sm font-semibold text-success">✓ Pengaturan tersimpan.</p>
          )}

          <button
            type="submit"
            disabled={saving}
            className="clay-primary px-4 py-3 text-sm font-bold disabled:opacity-60"
          >
            {saving ? "Menyimpan…" : "Simpan Pengaturan"}
          </button>
        </form>
      )}
    </div>
  );
}
