"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { useQueryClient } from "@tanstack/react-query";
import {
  useMe,
  useUsersList,
  useLokasiList,
  useUnitList,
  useJadwalUpcoming,
  useSettings,
} from "@/lib/hooks";
import { REVIEW_DOMAINS, type DomainConfig } from "@/lib/domain";
import { jadwalService, type JadwalInput } from "@/lib/services";
import { shiftsFor } from "@/lib/shifts";
import { datesInRange, weekdayDatesInRange, WEEKDAYS } from "@/lib/dates";
import { PageHeader, Spinner, EmptyState, StatusBadge } from "@/components/ui";
import { formatTanggal, formatJam } from "@/lib/format";
import type { Jadwal } from "@/lib/types";

type DateMode = "sekali" | "mingguan" | "harian" | "custom";

export default function KelolaJadwalPage() {
  const { manager } = useMe();
  const qc = useQueryClient();

  const [domain, setDomain] = useState<DomainConfig>(REVIEW_DOMAINS[0]);
  const [petugasId, setPetugasId] = useState("");
  const [unitId, setUnitId] = useState("");
  const [lokasiId, setLokasiId] = useState("");
  const [shift, setShift] = useState("");
  const [jamMulai, setJamMulai] = useState("");
  const [jamSelesai, setJamSelesai] = useState("");
  const [catatan, setCatatan] = useState("");

  // Mode tanggal
  const [dateMode, setDateMode] = useState<DateMode>("sekali");
  const [singleDate, setSingleDate] = useState("");
  const [rangeStart, setRangeStart] = useState("");
  const [rangeEnd, setRangeEnd] = useState("");
  const [weekdays, setWeekdays] = useState<number[]>([1, 2, 3, 4, 5]);
  const [customDates, setCustomDates] = useState<string[]>([]);
  const [customInput, setCustomInput] = useState("");

  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [result, setResult] = useState<{ ok: number; fail: number } | null>(null);

  // Filter jadwal mendatang
  const [filterPetugas, setFilterPetugas] = useState("");
  const [filterUnit, setFilterUnit] = useState("");
  const [filterLokasi, setFilterLokasi] = useState("");

  const petugas = useUsersList(manager, domain.role);
  const unit = useUnitList(manager);
  const lokasi = useLokasiList(manager);
  const upcoming = useJadwalUpcoming(manager ? domain : null);
  const { data: settingsData } = useSettings(manager);
  const shiftOptions = useMemo(() => {
    return settingsData?.work_shifts && settingsData.work_shifts.length > 0
      ? settingsData.work_shifts
      : shiftsFor(domain.key);
  }, [settingsData, domain.key]);

  const lokasiOptions = (lokasi.data ?? []).filter(
    (l) => unitId && l.unit?.id === Number(unitId),
  );

  useEffect(() => {
    setPetugasId("");
  }, [domain.key]);

  // Hitung daftar tanggal dari mode terpilih.
  const dates = useMemo<string[]>(() => {
    switch (dateMode) {
      case "sekali":
        return singleDate ? [singleDate] : [];
      case "harian":
        return datesInRange(rangeStart, rangeEnd);
      case "mingguan":
        return weekdayDatesInRange(rangeStart, rangeEnd, weekdays);
      case "custom":
        return [...customDates].sort();
    }
  }, [dateMode, singleDate, rangeStart, rangeEnd, weekdays, customDates]);

  function pickShift(value: string) {
    setShift(value);
    const s = shiftOptions.find((o) => o.value === value);
    if (s) {
      setJamMulai(s.mulai);
      setJamSelesai(s.selesai);
    }
  }

  function toggleWeekday(d: number) {
    setWeekdays((w) => (w.includes(d) ? w.filter((x) => x !== d) : [...w, d]));
  }
  function addCustomDate() {
    if (customInput && !customDates.includes(customInput)) {
      setCustomDates((c) => [...c, customInput]);
    }
    setCustomInput("");
  }

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setResult(null);
    if (!petugasId || !lokasiId || !shift || !jamMulai || !jamSelesai) {
      return setError("Lengkapi petugas, unit, lokasi, shift, dan jam.");
    }
    if (dates.length === 0) {
      return setError("Pilih minimal satu tanggal.");
    }
    setSaving(true);
    let ok = 0;
    let fail = 0;
    for (const tanggal of dates) {
      const body: JadwalInput = {
        petugas_id: Number(petugasId),
        lokasi_id: Number(lokasiId),
        tanggal,
        shift,
        jam_mulai: jamMulai,
        jam_selesai: jamSelesai,
        catatan: catatan.trim() || undefined,
      };
      try {
        await jadwalService.create(domain, body);
        ok++;
      } catch {
        fail++;
      }
    }
    setSaving(false);
    setResult({ ok, fail });
    qc.invalidateQueries({ queryKey: ["jadwal"] });
  }

  async function hapus(j: Jadwal) {
    if (!confirm("Hapus jadwal ini?")) return;
    try {
      await jadwalService.remove(domain, j.id);
      qc.invalidateQueries({ queryKey: ["jadwal"] });
    } catch {
      alert("Gagal menghapus jadwal.");
    }
  }

  if (!manager) {
    return (
      <div className="flex flex-col gap-5">
        <PageHeader title="Jadwal" />
        <EmptyState icon="🔒" title="Khusus supervisor / admin" />
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-5">
      <PageHeader title="Buat Jadwal" subtitle="Sekaligus banyak tanggal" />

      {/* Jenis petugas */}
      <div className="flex gap-2 overflow-x-auto pb-1">
        {REVIEW_DOMAINS.map((d) => (
          <button
            key={d.key}
            onClick={() => setDomain(d)}
            className={`whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold ${
              domain.key === d.key ? "clay-primary" : "clay-button text-muted"
            }`}
          >
            {d.label}
          </button>
        ))}
      </div>

      <form onSubmit={submit} className="clay flex flex-col gap-4 p-5">
        <Field label="Petugas *">
          <SearchableSelect
            placeholder={petugas.isLoading ? "Memuat…" : `Pilih ${domain.label}…`}
            options={petugas.data?.map((u) => ({ value: String(u.id), label: u.name })) ?? []}
            value={petugasId}
            onChange={setPetugasId}
          />
        </Field>

        <Field label="Unit *">
          <SearchableSelect
            placeholder={unit.isLoading ? "Memuat…" : "Pilih unit…"}
            options={unit.data?.map((u) => ({ value: String(u.id), label: u.nama_unit })) ?? []}
            value={unitId}
            onChange={(v) => { setUnitId(v); setLokasiId(""); }}
          />
        </Field>

        <Field label="Lokasi *">
          <SearchableSelect
            placeholder={
              !unitId
                ? "Pilih unit dulu…"
                : lokasiOptions.length === 0
                  ? "Tidak ada lokasi di unit ini"
                  : "Pilih lokasi…"
            }
            options={lokasiOptions.map((l) => ({ value: String(l.id), label: l.nama_lokasi }))}
            value={lokasiId}
            onChange={setLokasiId}
          />
        </Field>

        {/* Shift + jam */}
        <Field label="Shift *">
          <select
            value={shift}
            onChange={(e) => pickShift(e.target.value)}
            className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none"
          >
            <option value="">Pilih shift…</option>
            {shiftOptions.map((s) => (
              <option key={s.value} value={s.value}>
                {s.label}
              </option>
            ))}
          </select>
        </Field>
        <div className="grid grid-cols-2 gap-3">
          <Field label="Jam mulai *">
            <input
              type="time"
              value={jamMulai}
              onChange={(e) => setJamMulai(e.target.value)}
              className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none"
            />
          </Field>
          <Field label="Jam selesai *">
            <input
              type="time"
              value={jamSelesai}
              onChange={(e) => setJamSelesai(e.target.value)}
              className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none"
            />
          </Field>
        </div>

        {/* ===== Mode tanggal ===== */}
        <div className="flex flex-col gap-2">
          <span className="text-sm font-semibold text-text">Tanggal *</span>
          <div className="flex gap-2 overflow-x-auto pb-1">
            {(
              [
                ["sekali", "Sekali"],
                ["mingguan", "Mingguan"],
                ["harian", "Rutin (harian)"],
                ["custom", "Custom"],
              ] as [DateMode, string][]
            ).map(([m, label]) => (
              <button
                key={m}
                type="button"
                onClick={() => setDateMode(m)}
                className={`whitespace-nowrap rounded-full px-4 py-2 text-xs font-bold ${
                  dateMode === m ? "clay-primary" : "clay-button text-muted"
                }`}
              >
                {label}
              </button>
            ))}
          </div>

          {dateMode === "sekali" && (
            <input
              type="date"
              value={singleDate}
              onChange={(e) => setSingleDate(e.target.value)}
              className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none"
            />
          )}

          {(dateMode === "harian" || dateMode === "mingguan") && (
            <div className="flex flex-col gap-3">
              <div className="grid grid-cols-2 gap-3">
                <DateInput label="Dari" value={rangeStart} onChange={setRangeStart} />
                <DateInput label="Sampai" value={rangeEnd} onChange={setRangeEnd} />
              </div>
              {dateMode === "mingguan" && (
                <div className="flex flex-wrap gap-2">
                  {WEEKDAYS.map((d) => (
                    <button
                      key={d.value}
                      type="button"
                      onClick={() => toggleWeekday(d.value)}
                      className={`rounded-full px-3 py-2 text-xs font-bold ${
                        weekdays.includes(d.value)
                          ? "clay-primary"
                          : "clay-button text-muted"
                      }`}
                    >
                      {d.label}
                    </button>
                  ))}
                </div>
              )}
            </div>
          )}

          {dateMode === "custom" && (
            <div className="flex flex-col gap-2">
              <div className="flex gap-2">
                <input
                  type="date"
                  value={customInput}
                  onChange={(e) => setCustomInput(e.target.value)}
                  className="clay-sunken flex-1 rounded-2xl px-4 py-3 text-text outline-none"
                />
                <button
                  type="button"
                  onClick={addCustomDate}
                  className="clay-primary px-5 text-sm font-bold"
                >
                  + Tambah
                </button>
              </div>
              {customDates.length > 0 && (
                <div className="flex flex-wrap gap-2">
                  {[...customDates].sort().map((d) => (
                    <span
                      key={d}
                      className="clay-sunken flex items-center gap-2 rounded-full px-3 py-1 text-xs text-text"
                    >
                      {formatTanggal(d)}
                      <button
                        type="button"
                        onClick={() =>
                          setCustomDates((c) => c.filter((x) => x !== d))
                        }
                        className="font-bold text-danger"
                      >
                        ✕
                      </button>
                    </span>
                  ))}
                </div>
              )}
            </div>
          )}

          {/* Pratinjau jumlah */}
          <p className="text-xs text-muted">
            {dates.length > 0
              ? `${dates.length} tanggal akan dibuat${dates.length > 1 ? ` (${formatTanggal(dates[0])} – ${formatTanggal(dates[dates.length - 1])})` : ""}.`
              : "Belum ada tanggal terpilih."}
          </p>
        </div>

        <Field label="Catatan">
          <textarea
            value={catatan}
            onChange={(e) => setCatatan(e.target.value)}
            rows={2}
            className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none placeholder:text-muted"
            placeholder="Opsional"
          />
        </Field>

        {error && (
          <p className="rounded-2xl bg-danger/10 px-4 py-3 text-sm text-danger">{error}</p>
        )}
        {result && (
          <p
            className={`rounded-2xl px-4 py-3 text-sm ${
              result.fail > 0 ? "bg-warning/15 text-[#b07d12]" : "bg-success/10 text-success"
            }`}
          >
            ✅ {result.ok} jadwal dibuat
            {result.fail > 0 ? ` · ${result.fail} dilewati (sudah ada)` : ""}.
          </p>
        )}

        <button
          type="submit"
          disabled={saving}
          className="clay-primary w-full px-6 py-4 text-base font-bold disabled:opacity-60"
        >
          {saving
            ? "Menyimpan…"
            : `Buat ${dates.length > 0 ? dates.length : ""} Jadwal`}
        </button>
      </form>

      {/* Jadwal mendatang */}
      <section className="flex flex-col gap-3">
        <h2 className="text-sm font-bold text-muted">
          Jadwal mendatang — {domain.label}
        </h2>

        {/* Filter / cari */}
        <div className="clay flex flex-col gap-3 p-4">
          <input
            type="search"
            placeholder="Cari nama petugas…"
            value={filterPetugas}
            onChange={(e) => setFilterPetugas(e.target.value)}
            className="clay-sunken w-full rounded-2xl px-4 py-2.5 text-sm text-text outline-none placeholder:text-muted"
          />
          <div className="grid grid-cols-2 gap-3">
            <select
              value={filterUnit}
              onChange={(e) => { setFilterUnit(e.target.value); setFilterLokasi(""); }}
              className="clay-sunken w-full rounded-2xl px-4 py-2.5 text-sm text-text outline-none"
            >
              <option value="">Semua unit</option>
              {unit.data?.map((u) => (
                <option key={u.id} value={u.id}>{u.nama_unit}</option>
              ))}
            </select>
            <input
              type="search"
              placeholder="Cari lokasi…"
              value={filterLokasi}
              onChange={(e) => setFilterLokasi(e.target.value)}
              className="clay-sunken w-full rounded-2xl px-4 py-2.5 text-sm text-text outline-none placeholder:text-muted"
            />
          </div>
        </div>

        {(() => {
          const q = filterPetugas.toLowerCase().trim();
          const ql = filterLokasi.toLowerCase().trim();
          const filtered = (upcoming.data ?? []).filter((j) => {
            if (q && !j.petugas?.name?.toLowerCase().includes(q)) return false;
            if (filterUnit && String(j.lokasi?.unit?.id ?? "") !== filterUnit) return false;
            if (ql && !j.lokasi?.nama_lokasi?.toLowerCase().includes(ql)) return false;
            return true;
          });
          if (upcoming.isLoading && !upcoming.data) return <Spinner />;
          if (filtered.length === 0) return <EmptyState icon="🗓️" title="Tidak ada jadwal yang cocok" />;
          return filtered.map((j) => (
            <div key={j.id} className="clay flex items-center justify-between gap-3 p-4">
              <div className="min-w-0">
                <p className="truncate font-bold text-text">
                  {j.lokasi?.nama_lokasi ?? "Lokasi -"}
                </p>
                <p className="truncate text-xs text-muted">
                  {j.petugas?.name ? `${j.petugas.name} · ` : ""}
                  {formatTanggal(j.tanggal)} · {formatJam(j.jam_mulai, j.jam_selesai)}
                </p>
                {j.lokasi?.unit?.nama_unit && (
                  <p className="truncate text-xs text-muted">{j.lokasi.unit.nama_unit}</p>
                )}
              </div>
              <div className="flex shrink-0 items-center gap-2">
                <StatusBadge status={j.status} />
                <button
                  onClick={() => hapus(j)}
                  className="clay-button px-3 py-1.5 text-xs font-semibold text-danger"
                >
                  Hapus
                </button>
              </div>
            </div>
          ));
        })()}
      </section>
    </div>
  );
}

function SearchableSelect({
  placeholder,
  options,
  value,
  onChange,
}: {
  placeholder: string;
  options: { value: string; label: string }[];
  value: string;
  onChange: (v: string) => void;
}) {
  const [query, setQuery] = useState("");
  const [open, setOpen] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);

  const selected = options.find((o) => o.value === value);
  const filtered = query.trim()
    ? options.filter((o) => o.label.toLowerCase().includes(query.toLowerCase().trim()))
    : options;

  // Tutup saat klik di luar
  useEffect(() => {
    function onPointerDown(e: PointerEvent) {
      if (!containerRef.current?.contains(e.target as Node)) setOpen(false);
    }
    document.addEventListener("pointerdown", onPointerDown);
    return () => document.removeEventListener("pointerdown", onPointerDown);
  }, []);

  function select(opt: { value: string; label: string }) {
    onChange(opt.value);
    setQuery("");
    setOpen(false);
  }

  function clear() {
    onChange("");
    setQuery("");
    setOpen(true);
  }

  return (
    <div ref={containerRef} className="relative">
      <div
        className="clay-sunken flex items-center gap-2 rounded-2xl px-4 py-3 cursor-pointer"
        onClick={() => setOpen((o) => !o)}
      >
        {open ? (
          <input
            autoFocus
            type="text"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            onClick={(e) => e.stopPropagation()}
            placeholder="Ketik nama…"
            className="flex-1 bg-transparent text-sm text-text outline-none placeholder:text-muted"
          />
        ) : (
          <span className={`flex-1 text-sm ${selected ? "text-text" : "text-muted"}`}>
            {selected ? selected.label : placeholder}
          </span>
        )}
        {selected && !open ? (
          <button
            type="button"
            onClick={(e) => { e.stopPropagation(); clear(); }}
            className="text-muted hover:text-danger text-base leading-none"
          >
            ✕
          </button>
        ) : (
          <span className="text-muted text-xs">{open ? "▲" : "▼"}</span>
        )}
      </div>

      {open && (
        <div className="absolute inset-x-0 top-full z-50 mt-1 max-h-56 overflow-y-auto rounded-2xl border border-border bg-surface shadow-lg">
          {filtered.length === 0 ? (
            <p className="px-4 py-3 text-sm text-muted">Tidak ditemukan.</p>
          ) : (
            filtered.map((opt) => (
              <button
                key={opt.value}
                type="button"
                onClick={() => select(opt)}
                className={`w-full px-4 py-3 text-left text-sm hover:bg-primary/10 ${
                  opt.value === value ? "font-bold text-primary" : "text-text"
                }`}
              >
                {opt.label}
              </button>
            ))
          )}
        </div>
      )}
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

function DateInput({
  label,
  value,
  onChange,
}: {
  label: string;
  value: string;
  onChange: (v: string) => void;
}) {
  return (
    <label className="flex flex-col gap-1">
      <span className="text-xs text-muted">{label}</span>
      <input
        type="date"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none"
      />
    </label>
  );
}
