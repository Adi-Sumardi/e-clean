/**
 * Origin backend Laravel.
 *
 * Produksi: PWA & Laravel SATU origin (css.kopkaryapi.id) → kosongkan, semua
 * path relatif (/api, /auth) otomatis ke origin yang sama.
 *
 * Dev: Next jalan di :3000 tanpa Laravel, jadi set
 *   NEXT_PUBLIC_BACKEND_ORIGIN=https://css.kopkaryapi.id   (atau http://localhost:8000)
 * di web/.env.local agar /api & /auth menunjuk ke server Laravel sungguhan.
 */
export const BACKEND_ORIGIN = (
  process.env.NEXT_PUBLIC_BACKEND_ORIGIN ?? ""
).replace(/\/$/, "");

/** Bangun URL absolut ke route Laravel (mis. backendUrl("/auth/google")). */
export function backendUrl(path: string): string {
  return `${BACKEND_ORIGIN}${path}`;
}
