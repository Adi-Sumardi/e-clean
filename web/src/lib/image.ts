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
  maxWidthOrHeight: 1920, // sisi terpanjang — cukup tajam di layar
  initialQuality: 0.8, // beda visual ~nol
  maxSizeMB: 0.5, // plafon ~500 KB
  useWebWorker: true,
  fileType: "image/jpeg" as const,
};

/** Kompres satu file gambar → Blob JPEG. Jika gagal, kembalikan file asli. */
export async function compressImage(file: File | Blob): Promise<Blob> {
  try {
    const input =
      file instanceof File
        ? file
        : new File([file], "photo.jpg", { type: file.type || "image/jpeg" });
    return await imageCompression(input, OPTIONS);
  } catch {
    return file;
  }
}
