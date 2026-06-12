/**
 * Opsi shift kerja — dipakai SEMUA jenis petugas (kebersihan/satpam/ob/toko).
 *
 * Selaras dengan App\Enums\WorkShift backend. Memilih shift akan auto-mengisi
 * jam mulai/selesai (tetap bisa diedit manual).
 */

export interface ShiftOption {
  value: string;
  label: string;
  mulai: string;
  selesai: string;
}

export const WORK_SHIFTS: ShiftOption[] = [
  { value: "pagi", label: "Pagi (05:30–07:30)", mulai: "05:30", selesai: "07:30" },
  { value: "standby", label: "Standby (07:30–09:30)", mulai: "07:30", selesai: "09:30" },
  { value: "siang", label: "Siang (09:30–12:00)", mulai: "09:30", selesai: "12:00" },
  { value: "sweeping", label: "Sweeping (13:00–14:00)", mulai: "13:00", selesai: "14:00" },
  { value: "sore", label: "Sore (14:00–16:30)", mulai: "14:00", selesai: "16:30" },
];

/** Sama untuk semua domain. */
export function shiftsFor(_domainKey: string): ShiftOption[] {
  return WORK_SHIFTS;
}
