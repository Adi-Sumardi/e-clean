/**
 * Klien API ringan berbasis fetch (tanpa axios — hemat bundle untuk HP murah).
 *
 * Same-origin dengan Laravel: base URL cukup "/api/v1". Auth pakai Bearer token
 * Sanctum (lihat auth.ts). Penting: error transport (offline) menghasilkan
 * ApiError dengan `status === undefined` — penanda inilah yang dipakai outbox
 * untuk membedakan "tidak ada koneksi" (retryable) vs "ditolak server".
 */

import { getToken, clearToken } from "./auth";
import { BACKEND_ORIGIN } from "./config";

// Produksi: same-origin ("/api/v1"). Dev: prefiks origin Laravel bila di-set.
export const API_BASE = `${BACKEND_ORIGIN}/api/v1`;

/** Envelope standar respons API KopkarYAPI (Laravel). */
export interface ApiEnvelope<T> {
  success: boolean;
  message: string;
  data: T;
  errors?: Record<string, string[]>;
}

/** Error berisi pesan human-readable + error validasi Laravel. */
export class ApiError extends Error {
  status?: number;
  errors?: Record<string, string[]>;
  constructor(
    message: string,
    status?: number,
    errors?: Record<string, string[]>,
  ) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.errors = errors;
  }
}

/** True bila error berarti "tidak ada koneksi" (boleh di-retry oleh outbox). */
export function isOfflineError(err: unknown): boolean {
  return err instanceof ApiError && err.status === undefined;
}

interface RequestOptions extends Omit<RequestInit, "body"> {
  /** Body JSON (otomatis di-stringify) — abaikan bila pakai FormData. */
  json?: unknown;
  /** FormData untuk upload multipart (laporan + foto). */
  form?: FormData;
  /** Query params. */
  params?: Record<string, string | number | boolean | undefined>;
}

async function request<T>(path: string, opts: RequestOptions = {}): Promise<T> {
  const { json, form, params, headers, ...init } = opts;

  let url = `${API_BASE}${path}`;
  if (params) {
    const q = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
      if (v !== undefined) q.set(k, String(v));
    }
    const qs = q.toString();
    if (qs) url += `?${qs}`;
  }

  const token = getToken();
  const finalHeaders: Record<string, string> = {
    Accept: "application/json",
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...(headers as Record<string, string>),
  };

  let body: BodyInit | undefined;
  if (form) {
    body = form; // jangan set Content-Type — browser isi boundary sendiri
  } else if (json !== undefined) {
    body = JSON.stringify(json);
    finalHeaders["Content-Type"] = "application/json";
  }

  let res: Response;
  try {
    res = await fetch(url, { ...init, headers: finalHeaders, body });
  } catch {
    // Network/transport error → tidak ada koneksi.
    throw new ApiError("Tidak ada koneksi internet.", undefined);
  }

  if (res.status === 401) {
    clearToken();
    if (typeof window !== "undefined") {
      window.location.replace("/login");
    }
    throw new ApiError("Sesi berakhir, silakan masuk lagi.", 401);
  }

  let payload: ApiEnvelope<T> | undefined;
  try {
    payload = (await res.json()) as ApiEnvelope<T>;
  } catch {
    payload = undefined;
  }

  if (!res.ok) {
    throw new ApiError(
      payload?.message || `Permintaan gagal (${res.status}).`,
      res.status,
      payload?.errors,
    );
  }

  // Sebagian endpoint mengembalikan data langsung; sebagian dalam envelope.
  return (payload?.data ?? (payload as unknown)) as T;
}

/**
 * Unduh file biner (PDF) dari endpoint terproteksi. Tidak bisa pakai <a href>
 * biasa karena butuh header Bearer — jadi fetch → blob → anchor sementara.
 */
export async function downloadFile(
  path: string,
  params: Record<string, string | number | boolean | undefined>,
  fallbackName: string,
): Promise<void> {
  const q = new URLSearchParams();
  for (const [k, v] of Object.entries(params)) {
    if (v !== undefined) q.set(k, String(v));
  }
  const qs = q.toString();
  const url = `${API_BASE}${path}${qs ? `?${qs}` : ""}`;

  const token = getToken();
  let res: Response;
  try {
    res = await fetch(url, {
      headers: token ? { Authorization: `Bearer ${token}` } : undefined,
    });
  } catch {
    throw new ApiError("Tidak ada koneksi internet.", undefined);
  }

  if (!res.ok) {
    let message = `Unduhan gagal (${res.status}).`;
    try {
      const payload = (await res.json()) as ApiEnvelope<unknown>;
      if (payload?.message) message = payload.message;
    } catch {
      /* bukan JSON — pakai pesan default */
    }
    throw new ApiError(message, res.status);
  }

  // Nama file dari Content-Disposition bila ada.
  const disposition = res.headers.get("Content-Disposition") ?? "";
  const match = /filename="?([^";]+)"?/.exec(disposition);
  const filename = match?.[1] ?? fallbackName;

  const blob = await res.blob();
  const objectUrl = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = objectUrl;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(objectUrl);
}

export const api = {
  get: <T>(path: string, opts?: RequestOptions) =>
    request<T>(path, { ...opts, method: "GET" }),
  post: <T>(path: string, opts?: RequestOptions) =>
    request<T>(path, { ...opts, method: "POST" }),
  put: <T>(path: string, opts?: RequestOptions) =>
    request<T>(path, { ...opts, method: "PUT" }),
  delete: <T>(path: string, opts?: RequestOptions) =>
    request<T>(path, { ...opts, method: "DELETE" }),
};
