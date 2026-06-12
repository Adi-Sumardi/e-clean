/** Util pembangkit tanggal untuk pembuatan jadwal massal. */

function toISO(d: Date): string {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${y}-${m}-${day}`;
}

/** Semua tanggal (inklusif) dari start..end (format YYYY-MM-DD). */
export function datesInRange(start: string, end: string): string[] {
  const out: string[] = [];
  if (!start || !end) return out;
  const s = new Date(start + "T00:00:00");
  const e = new Date(end + "T00:00:00");
  if (isNaN(s.getTime()) || isNaN(e.getTime()) || e < s) return out;
  for (let d = new Date(s); d <= e; d.setDate(d.getDate() + 1)) {
    out.push(toISO(d));
  }
  return out;
}

/** Tanggal dalam rentang yang harinya termasuk weekdays (0=Min..6=Sab). */
export function weekdayDatesInRange(
  start: string,
  end: string,
  weekdays: number[],
): string[] {
  if (weekdays.length === 0) return [];
  return datesInRange(start, end).filter((ds) =>
    weekdays.includes(new Date(ds + "T00:00:00").getDay()),
  );
}

/** Hari dalam Bahasa Indonesia (0=Min..6=Sab). */
export const WEEKDAYS = [
  { value: 1, label: "Sen" },
  { value: 2, label: "Sel" },
  { value: 3, label: "Rab" },
  { value: 4, label: "Kam" },
  { value: 5, label: "Jum" },
  { value: 6, label: "Sab" },
  { value: 0, label: "Min" },
];
