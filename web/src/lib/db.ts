/**
 * IndexedDB untuk data offline (outbox laporan).
 *
 * Foto disimpan sebagai Blob di IndexedDB (bukan object URL yang hilang saat
 * reload), sehingga laporan yang dibuat offline tetap utuh lintas refresh dan
 * bisa disinkron saat online kembali.
 */

import { openDB, type DBSchema, type IDBPDatabase } from "idb";

export const OUTBOX_STORE = "outbox";

export interface OutboxJob {
  /** ID lokal unik. */
  id: string;
  /** Kunci anti-duplikat dikirim ke server sebagai header Idempotency-Key. */
  idempotencyKey: string;
  /** Domain laporan (menentukan endpoint). */
  domain: "kebersihan" | "satpam" | "ob" | "toko";
  /** Endpoint laporan relatif (tanpa /api/v1), mis. /activity-reports. */
  endpoint: string;
  /** Field teks/skalar (akan jadi FormData). */
  fields: Record<string, string>;
  /** Grup foto: nama field → daftar Blob. mis. { foto_sebelum: [...] }. */
  photos: Record<string, Blob[]>;
  /** Ringkasan untuk ditampilkan di daftar pending (nama lokasi, dll). */
  label: string;
  createdAt: number;
  attempts: number;
  status: "pending" | "syncing" | "failed";
  lastError?: string;
}

interface EcleanDB extends DBSchema {
  [OUTBOX_STORE]: {
    key: string;
    value: OutboxJob;
    indexes: { byStatus: string; byCreatedAt: number };
  };
}

let dbPromise: Promise<IDBPDatabase<EcleanDB>> | null = null;

export function getDB(): Promise<IDBPDatabase<EcleanDB>> {
  if (!dbPromise) {
    dbPromise = openDB<EcleanDB>("eclean", 1, {
      upgrade(db) {
        const store = db.createObjectStore(OUTBOX_STORE, { keyPath: "id" });
        store.createIndex("byStatus", "status");
        store.createIndex("byCreatedAt", "createdAt");
      },
    });
  }
  return dbPromise;
}
