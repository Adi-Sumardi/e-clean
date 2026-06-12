/**
 * Skema form laporan per-domain (deklaratif).
 *
 * Tiap domain punya field berbeda; satu komponen form me-render skema ini.
 * Field otomatis (jadwal_id, lokasi_id, tanggal, koordinat, status) ditambah
 * oleh halaman form, bukan bagian skema.
 */

import type { DomainConfig } from "./domain";

export type ReportField =
  | { name: string; label: string; kind: "time"; required?: boolean; defaultNow?: boolean }
  | { name: string; label: string; kind: "text"; required?: boolean; placeholder?: string }
  | {
      name: string;
      label: string;
      kind: "textarea";
      required?: boolean;
      min?: number;
      max?: number;
      placeholder?: string;
    }
  | {
      name: string;
      label: string;
      kind: "select";
      required?: boolean;
      options: { value: string; label: string }[];
    }
  | {
      name: string;
      label: string;
      kind: "photos";
      required?: boolean;
      min?: number;
      max?: number;
    };

export interface ReportSchema {
  title: string;
  fields: ReportField[];
}

type DomainKey = DomainConfig["key"];

export const REPORT_SCHEMAS: Record<DomainKey, ReportSchema> = {
  kebersihan: {
    title: "Laporan Kebersihan",
    fields: [
      { name: "jam_mulai", label: "Jam mulai", kind: "time", required: true, defaultNow: true },
      { name: "jam_selesai", label: "Jam selesai", kind: "time", required: true },
      {
        name: "kegiatan",
        label: "Kegiatan",
        kind: "textarea",
        required: true,
        min: 10,
        max: 1000,
        placeholder: "Jelaskan kegiatan kebersihan (min. 10 karakter)…",
      },
      { name: "foto_sebelum", label: "Foto sebelum", kind: "photos", required: true, min: 1, max: 5 },
      { name: "foto_sesudah", label: "Foto sesudah", kind: "photos", required: true, min: 1, max: 5 },
      { name: "catatan_petugas", label: "Catatan (opsional)", kind: "textarea", max: 1000 },
    ],
  },
  satpam: {
    title: "Laporan Keamanan",
    fields: [
      { name: "jam_mulai", label: "Jam mulai", kind: "time", required: true, defaultNow: true },
      { name: "jam_selesai", label: "Jam selesai", kind: "time" },
      {
        name: "kondisi",
        label: "Kondisi",
        kind: "select",
        required: true,
        options: [
          { value: "aman", label: "Aman" },
          { value: "perhatian", label: "Perhatian" },
          { value: "bahaya", label: "Bahaya" },
        ],
      },
      { name: "temuan", label: "Temuan (opsional)", kind: "textarea", max: 1000 },
      { name: "tindakan", label: "Tindakan (opsional)", kind: "textarea", max: 1000 },
      { name: "foto", label: "Foto (opsional)", kind: "photos", max: 5 },
      { name: "catatan_petugas", label: "Catatan (opsional)", kind: "textarea", max: 1000 },
    ],
  },
  ob: {
    title: "Laporan Office Boy",
    fields: [
      { name: "jam_mulai", label: "Jam mulai", kind: "time", required: true, defaultNow: true },
      { name: "jam_selesai", label: "Jam selesai", kind: "time" },
      { name: "jenis_pekerjaan", label: "Jenis pekerjaan", kind: "text", placeholder: "mis. Bersih ruang rapat" },
      { name: "uraian", label: "Uraian (opsional)", kind: "textarea", max: 1000 },
      { name: "foto_sebelum", label: "Foto sebelum (opsional)", kind: "photos", max: 5 },
      { name: "foto_sesudah", label: "Foto sesudah (opsional)", kind: "photos", max: 5 },
      { name: "catatan_petugas", label: "Catatan (opsional)", kind: "textarea", max: 1000 },
    ],
  },
  toko: {
    title: "Laporan Toko",
    fields: [
      { name: "jam_mulai", label: "Jam mulai", kind: "time", required: true, defaultNow: true },
      { name: "jam_selesai", label: "Jam selesai", kind: "time" },
      {
        name: "kondisi_stok",
        label: "Kondisi stok",
        kind: "select",
        options: [
          { value: "aman", label: "Aman" },
          { value: "menipis", label: "Menipis" },
          { value: "kosong", label: "Kosong" },
        ],
      },
      { name: "catatan_stok", label: "Catatan stok (opsional)", kind: "textarea", max: 1000 },
      { name: "foto", label: "Foto (opsional)", kind: "photos", max: 5 },
      { name: "catatan_petugas", label: "Catatan (opsional)", kind: "textarea", max: 1000 },
    ],
  },
};

/** Daftar field foto pada skema (untuk pemisahan photos vs fields teks). */
export function photoFields(schema: ReportSchema): ReportField[] {
  return schema.fields.filter((f) => f.kind === "photos");
}
