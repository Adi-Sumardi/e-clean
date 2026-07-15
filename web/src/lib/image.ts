/**
 * Kompresi foto di sisi klien.
 *
 * Tujuan: ukuran file kecil tapi tampak identik di layar, dan tidak membebani
 * UI. Kompresi berjalan di Web Worker (useWebWorker) sehingga form tetap mulus
 * walau foto besar. Lihat desain §11.
 *
 * Backend tetap menempel watermark (lat/lng/waktu) seperti sekarang; koordinat
 * & timestamp dikirim sebagai field terpisah, bukan dari EXIF.
 */

import imageCompression from "browser-image-compression";

const OPTIONS = {
  maxWidthOrHeight: 1600,
  initialQuality: 0.75,
  maxSizeMB: 0.3,
  // Main thread lebih stabil di Android WebView — worker spawn sering gagal
  // di device low-end dan bisa bikin session mati.
  useWebWorker: false,
  fileType: "image/jpeg" as const,
};

/** Kompres satu file gambar → Blob JPEG. Throw jika gagal (jangan return file asli yang bisa 10MB+). */
export async function compressImage(file: File | Blob): Promise<Blob> {
  const input =
    file instanceof File
      ? file
      : new File([file], "photo.jpg", { type: file.type || "image/jpeg" });
  return await imageCompression(input, OPTIONS);
}
