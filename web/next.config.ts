import type { NextConfig } from "next";
import { fileURLToPath } from "node:url";

const nextConfig: NextConfig = {
  // Folder `web/` adalah root project frontend (repo punya lockfile sendiri di root).
  turbopack: { root: fileURLToPath(new URL(".", import.meta.url)) },

  // Static export (SPA) — di-host langsung oleh nginx di root css.kopkaryapi.id.
  // Tidak ada server Node; service worker yang menangani offline/caching.
  // Hasil build ada di folder `out/`.
  output: "export",

  // next/image tanpa optimizer server (export tidak bisa optimize on the fly).
  images: { unoptimized: true },

  // Tiap route jadi folder index.html → ramah static hosting di nginx.
  trailingSlash: true,

  // Sembunyikan badge dev-tools Next.js (logo "N" pojok kiri-bawah saat dev).
  // Hanya muncul di dev; produksi tidak terpengaruh.
  devIndicators: false,
};

export default nextConfig;
