"use client";

import { useEffect, useRef, useState } from "react";

interface Props {
  onCapture: (blob: Blob) => void;
  onClose: () => void;
}

const MAX_PX = 1280; // resolusi capture — cukup tajam, canvas kecil, tidak OOM

export default function CameraCapture({ onCapture, onClose }: Props) {
  const videoRef = useRef<HTMLVideoElement>(null);
  const [ready, setReady] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [capturing, setCapturing] = useState(false);
  const streamRef = useRef<MediaStream | null>(null);

  useEffect(() => {
    let alive = true;

    navigator.mediaDevices
      .getUserMedia({
        video: { facingMode: "environment", width: { ideal: MAX_PX }, height: { ideal: MAX_PX } },
        audio: false,
      })
      .then((stream) => {
        if (!alive) { stream.getTracks().forEach((t) => t.stop()); return; }
        streamRef.current = stream;
        if (videoRef.current) {
          videoRef.current.srcObject = stream;
          videoRef.current.play().then(() => { if (alive) setReady(true); }).catch(() => {});
        }
      })
      .catch((err: DOMException) => {
        if (!alive) return;
        if (err.name === "NotAllowedError") {
          setError("Izin kamera ditolak. Buka pengaturan browser dan izinkan kamera.");
        } else if (err.name === "NotFoundError") {
          setError("Kamera tidak ditemukan di perangkat ini.");
        } else {
          setError("Kamera tidak bisa dibuka. Coba tutup aplikasi lain yang menggunakan kamera.");
        }
      });

    return () => {
      alive = false;
      streamRef.current?.getTracks().forEach((t) => t.stop());
    };
  }, []);

  function capture() {
    const video = videoRef.current;
    if (!video || !ready || capturing) return;
    setCapturing(true);

    // Clamp resolusi ke MAX_PX agar canvas tidak terlalu besar
    let w = video.videoWidth || MAX_PX;
    let h = video.videoHeight || MAX_PX;
    if (w > MAX_PX) { h = Math.round((h * MAX_PX) / w); w = MAX_PX; }
    if (h > MAX_PX) { w = Math.round((w * MAX_PX) / h); h = MAX_PX; }

    const canvas = document.createElement("canvas");
    canvas.width = w;
    canvas.height = h;
    const ctx = canvas.getContext("2d");
    if (!ctx) { setCapturing(false); return; }
    ctx.drawImage(video, 0, 0, w, h);

    canvas.toBlob(
      (blob) => {
        setCapturing(false);
        if (blob) {
          streamRef.current?.getTracks().forEach((t) => t.stop());
          onCapture(blob);
          onClose();
        }
      },
      "image/jpeg",
      0.80,
    );
  }

  return (
    <div className="fixed inset-0 z-50 flex flex-col bg-black">
      {error ? (
        <div className="flex flex-1 flex-col items-center justify-center gap-4 p-6 text-center text-white">
          <p className="text-base">{error}</p>
          <button
            onClick={onClose}
            className="rounded-2xl bg-white px-6 py-3 font-bold text-black"
          >
            Tutup
          </button>
        </div>
      ) : (
        <>
          {/* eslint-disable-next-line jsx-a11y/media-has-caption */}
          <video
            ref={videoRef}
            playsInline
            muted
            className="flex-1 w-full object-cover"
          />

          <div className="flex items-center justify-between bg-black px-8 py-8">
            <button
              type="button"
              onClick={onClose}
              className="flex h-14 w-14 items-center justify-center rounded-full bg-white/20 text-white text-sm font-semibold"
            >
              Batal
            </button>

            {/* Tombol capture */}
            <button
              type="button"
              onClick={capture}
              disabled={!ready || capturing}
              aria-label="Ambil foto"
              className="flex h-20 w-20 items-center justify-center rounded-full border-4 border-white bg-white/20 disabled:opacity-40"
            >
              <span className="h-14 w-14 rounded-full bg-white" />
            </button>

            <div className="h-14 w-14" />
          </div>
        </>
      )}
    </div>
  );
}
