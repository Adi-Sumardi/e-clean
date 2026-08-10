"use client";

import { useEffect, useRef, useState, useCallback } from "react";

interface Props {
  onCapture: (blob: Blob) => void;
  onClose: () => void;
}

const MAX_PX = 800;
const TIMER_OPTIONS = [3, 5, 10] as const;
type TimerOption = typeof TIMER_OPTIONS[number];

export default function CameraCapture({ onCapture, onClose }: Props) {
  const videoRef = useRef<HTMLVideoElement>(null);
  const [ready, setReady] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [capturing, setCapturing] = useState(false);
  const [facing, setFacing] = useState<"environment" | "user">("environment");
  const [timerSec, setTimerSec] = useState<TimerOption | null>(null);
  const [countdown, setCountdown] = useState<number | null>(null);
  const streamRef = useRef<MediaStream | null>(null);
  const countdownRef = useRef<ReturnType<typeof setInterval> | null>(null);

  useEffect(() => {
    let alive = true;
    setReady(false);

    streamRef.current?.getTracks().forEach((t) => t.stop());
    streamRef.current = null;

    navigator.mediaDevices
      .getUserMedia({
        video: { facingMode: facing, width: { ideal: MAX_PX }, height: { ideal: MAX_PX } },
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
  }, [facing]);

  // Bersihkan timer saat unmount
  useEffect(() => {
    return () => { if (countdownRef.current) clearInterval(countdownRef.current); };
  }, []);

  const doCapture = useCallback(async () => {
    setCapturing(true);
    try {
      const blob = await captureBlob();
      streamRef.current?.getTracks().forEach((t) => t.stop());
      onCapture(blob);
      onClose();
    } catch {
      setCapturing(false);
    }
  }, [onCapture, onClose]); // eslint-disable-line react-hooks/exhaustive-deps

  function handleShutter() {
    if (!ready || capturing || countdown !== null) return;

    if (timerSec !== null) {
      setCountdown(timerSec);
      let remaining = timerSec;
      countdownRef.current = setInterval(() => {
        remaining -= 1;
        if (remaining <= 0) {
          clearInterval(countdownRef.current!);
          countdownRef.current = null;
          setCountdown(null);
          void doCapture();
        } else {
          setCountdown(remaining);
        }
      }, 1000);
    } else {
      void doCapture();
    }
  }

  function cycleTimer() {
    if (countdown !== null) return;
    setTimerSec((prev) => {
      if (prev === null) return 3;
      const idx = TIMER_OPTIONS.indexOf(prev);
      // null → 3 → 5 → 10 → null
      return idx < TIMER_OPTIONS.length - 1 ? TIMER_OPTIONS[idx + 1] : null;
    });
  }

  function cancelCountdown() {
    if (countdownRef.current) clearInterval(countdownRef.current);
    countdownRef.current = null;
    setCountdown(null);
  }

  function captureBlob(): Promise<Blob> {
    const track = streamRef.current?.getVideoTracks()[0];

    if (track && typeof ImageCapture !== "undefined") {
      const ic = new ImageCapture(track);
      return ic.takePhoto({ imageWidth: MAX_PX } as PhotoSettings);
    }

    const video = videoRef.current;
    if (!video) return Promise.reject(new Error("no video"));

    let w = video.videoWidth || MAX_PX;
    let h = video.videoHeight || MAX_PX;
    if (w > MAX_PX) { h = Math.round((h * MAX_PX) / w); w = MAX_PX; }
    if (h > MAX_PX) { w = Math.round((w * MAX_PX) / h); h = MAX_PX; }

    const canvas = document.createElement("canvas");
    canvas.width = w;
    canvas.height = h;
    const ctx = canvas.getContext("2d");
    if (!ctx) return Promise.reject(new Error("no ctx"));
    ctx.drawImage(video, 0, 0, w, h);

    return new Promise<Blob>((resolve, reject) =>
      canvas.toBlob(
        (b) => (b ? resolve(b) : reject(new Error("empty blob"))),
        "image/jpeg",
        0.82,
      ),
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

          {/* Overlay hitung mundur */}
          {countdown !== null && (
            <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
              <span className="text-white font-black drop-shadow-lg" style={{ fontSize: "25vw" }}>
                {countdown}
              </span>
            </div>
          )}

          {/* Bar kontrol — padding bawah otomatis sesuai safe area Android/iOS */}
          <div
            className="flex items-center justify-between bg-black/80 px-8 pt-5"
            style={{ paddingBottom: "max(1.5rem, env(safe-area-inset-bottom, 1.5rem))" }}
          >
            {/* Batal / batalkan timer */}
            <button
              type="button"
              onClick={countdown !== null ? cancelCountdown : onClose}
              className="flex flex-col items-center gap-1"
            >
              <span className="flex h-12 w-12 items-center justify-center rounded-full bg-white/20 text-white text-lg font-bold">
                ✕
              </span>
              <span className="text-[10px] text-white/70">
                {countdown !== null ? "Batal" : "Tutup"}
              </span>
            </button>

            {/* Tombol rana */}
            <button
              type="button"
              onClick={handleShutter}
              disabled={!ready || capturing}
              aria-label="Ambil foto"
              className="flex h-20 w-20 items-center justify-center rounded-full border-4 border-white disabled:opacity-40"
            >
              <span className="h-14 w-14 rounded-full bg-white" />
            </button>

            {/* Kolom kanan: balik kamera + timer */}
            <div className="flex flex-col items-center gap-3">
              <button
                type="button"
                onClick={() => setFacing((f) => f === "environment" ? "user" : "environment")}
                disabled={capturing || countdown !== null}
                className="flex flex-col items-center gap-1 disabled:opacity-40"
              >
                <span className="flex h-12 w-12 items-center justify-center rounded-full bg-white/20 text-xl">
                  🔄
                </span>
                <span className="text-[10px] text-white/70">
                  {facing === "environment" ? "Depan" : "Belakang"}
                </span>
              </button>

              <button
                type="button"
                onClick={cycleTimer}
                disabled={capturing || countdown !== null}
                className="flex flex-col items-center gap-1 disabled:opacity-40"
              >
                <span
                  className={`flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold ${
                    timerSec !== null ? "bg-warning text-black" : "bg-white/20 text-white"
                  }`}
                >
                  {timerSec !== null ? `${timerSec}s` : "⏱"}
                </span>
                <span className="text-[10px] text-white/70">Timer</span>
              </button>
            </div>
          </div>
        </>
      )}
    </div>
  );
}
