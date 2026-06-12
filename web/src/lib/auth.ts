/**
 * Penyimpanan token Sanctum.
 *
 * Untuk Fase 0 token disimpan di localStorage (cukup sederhana & sinkron).
 * Catatan desain: akan dipindah ke IndexedDB bersama outbox di Fase 2 agar
 * konsisten dengan data offline. API kecil ini menjaga pemanggil tidak berubah.
 */

const TOKEN_KEY = "eclean.auth.token";

let cached: string | null | undefined;

export function getToken(): string | null {
  if (cached !== undefined) return cached;
  if (typeof window === "undefined") return null;
  cached = window.localStorage.getItem(TOKEN_KEY);
  return cached;
}

export function setToken(token: string): void {
  cached = token;
  if (typeof window !== "undefined") {
    window.localStorage.setItem(TOKEN_KEY, token);
  }
}

export function clearToken(): void {
  cached = null;
  if (typeof window !== "undefined") {
    window.localStorage.removeItem(TOKEN_KEY);
  }
}

export function isAuthenticated(): boolean {
  return !!getToken();
}
