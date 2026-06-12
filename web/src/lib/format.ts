/** Util format tanggal/jam berbahasa Indonesia. */

const HARI = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];
const BULAN = [
  "Januari", "Februari", "Maret", "April", "Mei", "Juni",
  "Juli", "Agustus", "September", "Oktober", "November", "Desember",
];

/** "2026-06-09" → "Sel, 9 Jun 2026". */
export function formatTanggal(iso: string | null | undefined): string {
  if (!iso) return "-";
  const d = new Date(iso);
  if (isNaN(d.getTime())) return iso;
  return `${HARI[d.getDay()]}, ${d.getDate()} ${BULAN[d.getMonth()].slice(0, 3)} ${d.getFullYear()}`;
}

/** "08:00"–"10:00" → "08:00 – 10:00". */
export function formatJam(
  mulai: string | null | undefined,
  selesai: string | null | undefined,
): string {
  if (!mulai && !selesai) return "-";
  return `${mulai ?? "?"} – ${selesai ?? "?"}`;
}

export function namaBulan(n: number): string {
  return BULAN[n - 1] ?? String(n);
}

/** Waktu relatif singkat: "baru saja", "5 mnt", "2 jam", "3 hari". */
export function timeAgo(iso: string | null | undefined): string {
  if (!iso) return "";
  const then = new Date(iso).getTime();
  if (isNaN(then)) return "";
  const s = Math.floor((Date.now() - then) / 1000);
  if (s < 60) return "baru saja";
  const m = Math.floor(s / 60);
  if (m < 60) return `${m} mnt`;
  const h = Math.floor(m / 60);
  if (h < 24) return `${h} jam`;
  const d = Math.floor(h / 24);
  return `${d} hari`;
}
