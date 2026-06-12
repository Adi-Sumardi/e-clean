"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { authService } from "@/lib/services";
import { isAuthenticated, setToken } from "@/lib/auth";
import { ApiError } from "@/lib/api";
import { backendUrl } from "@/lib/config";

export default function LoginPage() {
  const router = useRouter();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [show, setShow] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Saat kembali dari Google OAuth, backend redirect ke /login?token=... (atau ?error=).
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const token = params.get("token");
    const err = params.get("error");
    if (token) {
      setToken(token);
      router.replace("/beranda");
      return;
    }
    if (err) {
      setError(err);
      window.history.replaceState({}, "", "/login");
      return;
    }
    if (isAuthenticated()) router.replace("/beranda");
  }, [router]);

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setLoading(true);
    try {
      await authService.login(email.trim(), password);
      router.replace("/beranda");
    } catch (err) {
      setError(
        err instanceof ApiError ? err.message : "Gagal masuk. Coba lagi.",
      );
    } finally {
      setLoading(false);
    }
  }

  function loginWithGoogle() {
    // Navigasi penuh ke route Laravel; platform=pwa → callback balik ke /login?token=...
    // Pakai backendUrl agar di dev menunjuk origin Laravel (bukan :3000).
    window.location.href = backendUrl("/auth/google?platform=pwa");
  }

  return (
    <div className="mx-auto flex min-h-dvh w-full max-w-md flex-col justify-center px-6 py-10">
      {/* Logo / brand */}
      <div className="mb-8 flex flex-col items-center text-center">
        <div className="clay mb-4 grid h-24 w-24 place-items-center p-3">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src="/icons/logo.png"
            alt="Logo KopkarYAPI"
            className="h-full w-full object-contain"
          />
        </div>
        <h1 className="text-2xl font-bold text-text">Apps KopkarYAPI</h1>
        <p className="mt-1 text-sm text-muted">Masuk sebagai petugas</p>
      </div>

      <form onSubmit={onSubmit} className="clay flex flex-col gap-4 p-6">
        <label className="flex flex-col gap-2">
          <span className="text-sm font-semibold text-text">Email</span>
          <input
            type="email"
            autoComplete="email"
            required
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            placeholder="nama@email.com"
            className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none placeholder:text-muted"
          />
        </label>

        <label className="flex flex-col gap-2">
          <span className="text-sm font-semibold text-text">Kata sandi</span>
          <div className="relative">
            <input
              type={show ? "text" : "password"}
              autoComplete="current-password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="••••••••"
              className="clay-sunken w-full rounded-2xl px-4 py-3 pr-20 text-text outline-none placeholder:text-muted"
            />
            <button
              type="button"
              onClick={() => setShow((s) => !s)}
              className="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-semibold text-primary"
            >
              {show ? "Sembunyi" : "Lihat"}
            </button>
          </div>
        </label>

        {error && (
          <p className="rounded-2xl bg-danger/10 px-4 py-3 text-sm text-danger">
            {error}
          </p>
        )}

        <button
          type="submit"
          disabled={loading}
          className="clay-primary mt-2 w-full px-6 py-4 text-base font-bold disabled:opacity-60"
        >
          {loading ? "Memproses…" : "Masuk"}
        </button>

        {/* Pemisah */}
        <div className="my-1 flex items-center gap-3 text-xs text-muted">
          <span className="h-px flex-1 bg-border" />
          atau
          <span className="h-px flex-1 bg-border" />
        </div>

        {/* Login Google */}
        <button
          type="button"
          onClick={loginWithGoogle}
          className="clay-button flex w-full items-center justify-center gap-3 px-6 py-4 text-base font-semibold text-text"
        >
          <GoogleLogo />
          Masuk dengan Google
        </button>
      </form>

      <p className="mt-6 text-center text-xs text-muted">
        Apps KopkarYAPI · kopkaryapi.id
      </p>
    </div>
  );
}

function GoogleLogo() {
  return (
    <svg width="20" height="20" viewBox="0 0 48 48" aria-hidden="true">
      <path
        fill="#FFC107"
        d="M43.6 20.5H42V20H24v8h11.3c-1.6 4.7-6.1 8-11.3 8a12 12 0 0 1 0-24c3 0 5.8 1.1 7.9 3l5.7-5.7A20 20 0 1 0 24 44c11 0 20-8 20-20 0-1.3-.1-2.3-.4-3.5z"
      />
      <path
        fill="#FF3D00"
        d="M6.3 14.7l6.6 4.8C14.7 16 19 13 24 13c3 0 5.8 1.1 7.9 3l5.7-5.7A20 20 0 0 0 6.3 14.7z"
      />
      <path
        fill="#4CAF50"
        d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2A12 12 0 0 1 12.7 28l-6.5 5C9.5 39.6 16.2 44 24 44z"
      />
      <path
        fill="#1976D2"
        d="M43.6 20.5H42V20H24v8h11.3a12 12 0 0 1-4.1 5.6l6.2 5.2C39.9 35.7 44 30.4 44 24c0-1.3-.1-2.3-.4-3.5z"
      />
    </svg>
  );
}
