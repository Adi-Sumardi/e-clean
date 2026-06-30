"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useQueryClient } from "@tanstack/react-query";
import { useMe, useSettings } from "@/lib/hooks";
import { settingService } from "@/lib/services";
import { ApiError } from "@/lib/api";
import { PageHeader, Spinner, EmptyState, ErrorState } from "@/components/ui";
import { ShiftOption } from "@/lib/types";

const DEFAULT_SHIFTS: ShiftOption[] = [
  { value: "pagi", label: "Pagi (05:30–07:30)", mulai: "05:30", selesai: "07:30" },
  { value: "standby", label: "Standby (07:30–09:30)", mulai: "07:30", selesai: "09:30" },
  { value: "siang", label: "Siang (09:30–12:00)", mulai: "09:30", selesai: "12:00" },
  { value: "sweeping", label: "Sweeping (13:00–14:00)", mulai: "13:00", selesai: "14:00" },
  { value: "sore", label: "Sore (14:00–16:30)", mulai: "14:00", selesai: "16:30" },
];

export default function PengaturanPage() {
  const { manager } = useMe();
  const qc = useQueryClient();
  const { data, isLoading, isError, refetch } = useSettings(manager);

  const [toleransi, setToleransi] = useState("");
  const [shifts, setShifts] = useState<ShiftOption[]>([]);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [saved, setSaved] = useState(false);

  // State form add shift
  const [newKey, setNewKey] = useState("");
  const [newLabel, setNewLabel] = useState("");
  const [newMulai, setNewMulai] = useState("");
  const [newSelesai, setNewSelesai] = useState("");
  const [addError, setAddError] = useState<string | null>(null);

  // State edit shift
  const [editingIndex, setEditingIndex] = useState<number | null>(null);
  const [editLabel, setEditLabel] = useState("");
  const [editMulai, setEditMulai] = useState("");
  const [editSelesai, setEditSelesai] = useState("");

  useEffect(() => {
    if (data) {
      setToleransi(String(data.reporting_tolerance_minutes));
      setShifts(data.work_shifts && data.work_shifts.length > 0 ? data.work_shifts : DEFAULT_SHIFTS);
    }
  }, [data]);

  async function save(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setSaved(false);

    const value = Number(toleransi);
    if (!Number.isInteger(value) || value < 1 || value > 120) {
      return setError("Toleransi harus angka 1–120 menit.");
    }

    if (shifts.length === 0) {
      return setError("Harus ada minimal 1 shift kerja.");
    }

    setSaving(true);
    try {
      await settingService.update({
        reporting_tolerance_minutes: value,
        work_shifts: shifts,
      });
      qc.invalidateQueries({ queryKey: ["settings"] });
      setSaved(true);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Gagal menyimpan pengaturan.");
    } finally {
      setSaving(false);
    }
  }

  function addShift() {
    setAddError(null);
    const key = newKey.trim().toLowerCase();
    const label = newLabel.trim();
    const mulai = newMulai.trim();
    const selesai = newSelesai.trim();

    if (!key || !label || !mulai || !selesai) {
      return setAddError("Lengkapi semua input shift baru.");
    }

    if (!/^[a-z0-9_-]+$/.test(key)) {
      return setAddError("Kode shift hanya boleh huruf kecil, angka, - dan _.");
    }

    if (shifts.some((s) => s.value === key)) {
      return setAddError("Kode shift sudah digunakan.");
    }

    const timeRegex = /^\d{2}:\d{2}$/;
    if (!timeRegex.test(mulai) || !timeRegex.test(selesai)) {
      return setAddError("Waktu harus berformat HH:MM.");
    }

    const newShift: ShiftOption = { value: key, label, mulai, selesai };
    setShifts([...shifts, newShift]);

    // Reset form
    setNewKey("");
    setNewLabel("");
    setNewMulai("");
    setNewSelesai("");
  }

  function startEdit(index: number) {
    const s = shifts[index];
    setEditingIndex(index);
    setEditLabel(s.label);
    setEditMulai(s.mulai);
    setEditSelesai(s.selesai);
  }

  function saveEdit(index: number) {
    const label = editLabel.trim();
    const mulai = editMulai.trim();
    const selesai = editSelesai.trim();

    if (!label || !mulai || !selesai) {
      return alert("Semua field edit harus diisi.");
    }

    const timeRegex = /^\d{2}:\d{2}$/;
    if (!timeRegex.test(mulai) || !timeRegex.test(selesai)) {
      return alert("Waktu harus berformat HH:MM.");
    }

    const updated = [...shifts];
    updated[index] = {
      ...updated[index],
      label,
      mulai,
      selesai,
    };

    setShifts(updated);
    setEditingIndex(null);
  }

  function deleteShift(index: number) {
    if (confirm("Hapus shift ini?")) {
      setShifts(shifts.filter((_, i) => i !== index));
      if (editingIndex === index) {
        setEditingIndex(null);
      }
    }
  }

  return (
    <div className="flex flex-col gap-5">
      <Link href="/beranda" className="text-sm font-semibold text-primary">
        ← Kembali
      </Link>
      <PageHeader title="Pengaturan Aplikasi" subtitle="Konfigurasi laporan & shift kegiatan" />

      {!manager ? (
        <EmptyState icon="🔒" title="Khusus supervisor / admin" />
      ) : isLoading ? (
        <Spinner />
      ) : isError ? (
        <ErrorState message="Gagal memuat pengaturan." onRetry={() => refetch()} />
      ) : (
        <div className="flex flex-col gap-6">
          {/* Form Toleransi */}
          <form onSubmit={save} className="clay flex flex-col gap-4 p-5">
            <div>
              <p className="font-bold text-text text-lg">Toleransi Keterlambatan</p>
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

            {error && <p className="text-sm text-danger font-semibold">{error}</p>}
            {saved && !error && (
              <p className="text-sm font-semibold text-success">✓ Pengaturan berhasil disimpan.</p>
            )}

            <button
              type="submit"
              disabled={saving}
              className="clay-primary px-4 py-3 text-sm font-bold disabled:opacity-60 transition-opacity hover:opacity-90 active:translate-y-px"
            >
              {saving ? "Menyimpan…" : "Simpan Pengaturan"}
            </button>
          </form>

          {/* Section Dynamic Shifts */}
          <div className="clay flex flex-col gap-5 p-5">
            <div>
              <p className="font-bold text-text text-lg">Pengaturan Shift Kerja</p>
              <p className="text-sm text-muted">
                Kelola daftar shift kerja yang dapat dipilih oleh petugas saat membuat atau menjadwalkan tugas.
              </p>
            </div>

            {/* List Shifts */}
            <div className="flex flex-col gap-3">
              {shifts.map((s, index) => {
                const isEditing = editingIndex === index;

                return (
                  <div
                    key={s.value}
                    className="clay-sunken flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-2xl border border-outline-variant bg-surface transition-all"
                  >
                    {isEditing ? (
                      <div className="flex-1 flex flex-col gap-3">
                        <div className="flex flex-col gap-1">
                          <label className="text-xs font-bold text-muted">Label Shift</label>
                          <input
                            type="text"
                            value={editLabel}
                            onChange={(e) => setEditLabel(e.target.value)}
                            className="clay bg-background rounded-xl px-3 py-2 text-sm text-text outline-none w-full"
                            placeholder="Contoh: Pagi (05:30–07:30)"
                          />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                          <div className="flex flex-col gap-1">
                            <label className="text-xs font-bold text-muted">Jam Mulai</label>
                            <input
                              type="text"
                              value={editMulai}
                              onChange={(e) => setEditMulai(e.target.value)}
                              className="clay bg-background rounded-xl px-3 py-2 text-sm text-text outline-none"
                              placeholder="HH:MM"
                            />
                          </div>
                          <div className="flex flex-col gap-1">
                            <label className="text-xs font-bold text-muted">Jam Selesai</label>
                            <input
                              type="text"
                              value={editSelesai}
                              onChange={(e) => setEditSelesai(e.target.value)}
                              className="clay bg-background rounded-xl px-3 py-2 text-sm text-text outline-none"
                              placeholder="HH:MM"
                            />
                          </div>
                        </div>
                      </div>
                    ) : (
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2">
                          <span className="font-bold text-text">{s.label}</span>
                          <span className="clay-sunken px-2 py-0.5 rounded-full text-[10px] font-bold text-muted bg-surface/20">
                            {s.value}
                          </span>
                        </div>
                        <p className="text-xs text-muted mt-1 font-semibold">
                          Waktu: {s.mulai} – {s.selesai}
                        </p>
                      </div>
                    )}

                    <div className="flex items-center justify-end gap-2">
                      {isEditing ? (
                        <>
                          <button
                            onClick={() => saveEdit(index)}
                            className="clay-primary bg-secondary px-3 py-2 rounded-xl text-xs font-bold transition-all hover:opacity-90 active:translate-y-px"
                          >
                            Simpan
                          </button>
                          <button
                            onClick={() => setEditingIndex(null)}
                            className="clay px-3 py-2 rounded-xl text-xs font-bold bg-background text-text transition-all hover:opacity-90 active:translate-y-px"
                          >
                            Batal
                          </button>
                        </>
                      ) : (
                        <>
                          <button
                            onClick={() => startEdit(index)}
                            className="clay px-3 py-2 rounded-xl text-xs font-bold text-primary transition-all hover:bg-primary/10 active:translate-y-px"
                          >
                            ✏️ Edit
                          </button>
                          <button
                            onClick={() => deleteShift(index)}
                            className="clay px-3 py-2 rounded-xl text-xs font-bold text-danger transition-all hover:bg-danger/10 active:translate-y-px"
                          >
                            🗑️ Hapus
                          </button>
                        </>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>

            {/* Add Shift Form */}
            <div className="clay-sunken p-4 rounded-2xl border border-outline-variant bg-surface/30 mt-2">
              <p className="font-bold text-text text-sm mb-3">Tambah Shift Baru</p>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="flex flex-col gap-1">
                  <label className="text-xs font-bold text-muted">Kode Shift (Unik, huruf kecil)</label>
                  <input
                    type="text"
                    value={newKey}
                    onChange={(e) => setNewKey(e.target.value)}
                    className="clay bg-background rounded-xl px-3 py-2 text-sm text-text outline-none"
                    placeholder="Contoh: malam"
                  />
                </div>
                <div className="flex flex-col gap-1">
                  <label className="text-xs font-bold text-muted">Label Tampilan</label>
                  <input
                    type="text"
                    value={newLabel}
                    onChange={(e) => setNewLabel(e.target.value)}
                    className="clay bg-background rounded-xl px-3 py-2 text-sm text-text outline-none"
                    placeholder="Contoh: Malam (20:00–06:00)"
                  />
                </div>
                <div className="flex flex-col gap-1">
                  <label className="text-xs font-bold text-muted">Jam Mulai</label>
                  <input
                    type="text"
                    value={newMulai}
                    onChange={(e) => setNewMulai(e.target.value)}
                    className="clay bg-background rounded-xl px-3 py-2 text-sm text-text outline-none"
                    placeholder="HH:MM (Contoh: 20:00)"
                  />
                </div>
                <div className="flex flex-col gap-1">
                  <label className="text-xs font-bold text-muted">Jam Selesai</label>
                  <input
                    type="text"
                    value={newSelesai}
                    onChange={(e) => setNewSelesai(e.target.value)}
                    className="clay bg-background rounded-xl px-3 py-2 text-sm text-text outline-none"
                    placeholder="HH:MM (Contoh: 06:00)"
                  />
                </div>
              </div>

              {addError && <p className="text-xs text-danger font-semibold mt-3">{addError}</p>}

              <button
                onClick={addShift}
                className="clay bg-primary text-white text-xs font-bold px-4 py-2.5 rounded-xl mt-4 transition-all hover:opacity-90 active:translate-y-px"
              >
                + Tambah Shift Ke Daftar
              </button>
            </div>

            {/* Alert info */}
            <div className="bg-primary/5 rounded-xl p-3 border border-primary/10">
              <p className="text-xs text-primary font-semibold leading-relaxed">
                💡 <strong>Catatan:</strong> Klik tombol <strong>&ldquo;Simpan Pengaturan&rdquo;</strong> di atas setelah menambah/mengedit/menghapus shift untuk menyimpan perubahan secara permanen ke server database.
              </p>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
