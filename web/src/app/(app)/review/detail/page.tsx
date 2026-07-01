"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useReviewDetail } from "@/lib/hooks";
import { domainByKey, type DomainConfig } from "@/lib/domain";
import { reviewService } from "@/lib/services";
import { Spinner, ErrorState, StatusBadge } from "@/components/ui";
import ReportPhotos from "@/components/ReportPhotos";
import { formatTanggal, formatJam } from "@/lib/format";
import type { Laporan } from "@/lib/types";

export default function ReviewDetailPage() {
  const router = useRouter();
  const qc = useQueryClient();
  const [domain, setDomain] = useState<DomainConfig | null>(null);
  const [id, setId] = useState<number | null>(null);
  const [rating, setRating] = useState(0);
  const [komentar, setKomentar] = useState("");
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const sp = new URLSearchParams(window.location.search);
    setDomain(domainByKey(sp.get("domain")));
    const raw = sp.get("id");
    setId(raw ? Number(raw) : null);
  }, []);

  const { data: report, isLoading, isError, refetch } = useReviewDetail(domain, id);

  const decide = useMutation({
    mutationFn: async (kind: "approve" | "reject") => {
      if (!domain || !id) throw new Error("Data tidak lengkap.");
      if (kind === "approve") {
        await reviewService.approve(domain, id, {
          rating: rating || undefined,
          catatan_supervisor: komentar.trim() || undefined,
        });
      } else {
        await reviewService.reject(domain, id, komentar.trim());
      }
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["review"] });
      router.replace("/review?done=1");
    },
    onError: (err: unknown) => {
      const msg =
        err instanceof Error ? err.message : "Gagal memproses. Coba lagi.";
      setError(msg);
    },
  });

  if (!domain || !id) return <Spinner />;

  const canReview =
    report && (report.status === "submitted" || report.status === "pending");

  function approve() {
    setError(null);
    decide.mutate("approve");
  }
  function reject() {
    if (!komentar.trim()) {
      setError("Isi komentar/alasan dulu untuk menolak laporan.");
      return;
    }
    setError(null);
    decide.mutate("reject");
  }

  return (
    <div className="flex flex-col gap-5">
      <Link href="/review" className="text-sm font-semibold text-primary">
        ← Kembali
      </Link>

      {isLoading && !report ? (
        <Spinner />
      ) : (isError && !report) || !report ? (
        <ErrorState message="Gagal memuat laporan." onRetry={() => refetch()} />
      ) : (
        <>
          <div className="clay flex flex-col gap-3 p-6">
            <div className="flex items-start justify-between gap-3">
              <div>
                <h1 className="text-lg font-bold text-text">
                  {report.petugas?.name ?? "Petugas"}
                </h1>
                <p className="text-sm text-muted">{domain.label}</p>
              </div>
              <StatusBadge status={report.status} />
            </div>
            <Row label="Lokasi" value={report.lokasi?.nama_lokasi ?? "-"} />
            {report.lokasi?.unit?.nama_unit && (
              <Row label="Unit" value={report.lokasi.unit.nama_unit} />
            )}
            <Row label="Tanggal" value={formatTanggal(report.tanggal)} />
            <Row label="Jam" value={formatJam(report.jam_mulai, report.jam_selesai)} />
            <DomainFields report={report} />
            {report.catatan_petugas && (
              <Row label="Catatan petugas" value={report.catatan_petugas} />
            )}
          </div>

          {/* Foto */}
          <ReportPhotos label="Foto sebelum" urls={report.foto_sebelum} />
          <ReportPhotos label="Foto sesudah" urls={report.foto_sesudah} />
          <ReportPhotos label="Foto" urls={report.foto} />

          {canReview ? (
            <div className="clay flex flex-col gap-4 p-5">
              <h2 className="font-bold text-text">Tinjau Laporan</h2>

              {/* Rating bintang */}
              <div className="flex flex-col gap-2">
                <span className="text-sm font-semibold text-text">
                  Nilai (untuk persetujuan)
                </span>
                <div className="flex items-center gap-2">
                  {[1, 2, 3, 4, 5].map((n) => (
                    <button
                      key={n}
                      type="button"
                      onClick={() => setRating(n === rating ? 0 : n)}
                      className={`text-3xl transition-transform active:scale-90 ${
                        n <= rating ? "" : "opacity-25"
                      }`}
                      aria-label={`Beri ${n} bintang`}
                    >
                      ⭐
                    </button>
                  ))}
                  {rating > 0 && (
                    <span className="ml-1 text-sm font-bold text-primary">
                      {rating}/5
                    </span>
                  )}
                </div>
              </div>

              {/* Komentar supervisor */}
              <div className="flex flex-col gap-2">
                <span className="text-sm font-semibold text-text">
                  Komentar supervisor
                  <span className="font-normal text-muted"> (wajib bila menolak)</span>
                </span>
                <textarea
                  value={komentar}
                  onChange={(e) => setKomentar(e.target.value)}
                  rows={3}
                  placeholder="Tulis catatan/alasan untuk petugas…"
                  className="clay-sunken w-full rounded-2xl px-4 py-3 text-text outline-none placeholder:text-muted"
                />
              </div>

              {error && (
                <p className="rounded-2xl bg-danger/10 px-4 py-3 text-sm text-danger">
                  {error}
                </p>
              )}

              <div className="flex gap-3">
                <button
                  onClick={reject}
                  disabled={decide.isPending}
                  className="clay-button flex-1 px-4 py-4 text-base font-bold text-danger disabled:opacity-60"
                >
                  Tolak
                </button>
                <button
                  onClick={approve}
                  disabled={decide.isPending}
                  className="clay-primary flex-1 px-4 py-4 text-base font-bold disabled:opacity-60"
                >
                  {decide.isPending ? "Memproses…" : "Setujui"}
                </button>
              </div>
            </div>
          ) : (
            <div className="clay-sunken flex flex-col gap-2 p-4 text-sm">
              <p className="text-center font-semibold text-muted">
                Laporan sudah ditinjau.
              </p>
              {report.rating ? (
                <p className="text-center text-text">Nilai: {"⭐".repeat(report.rating)}</p>
              ) : null}
              {report.catatan_supervisor && (
                <p className="text-center text-text">“{report.catatan_supervisor}”</p>
              )}
              {report.rejected_reason && (
                <p className="text-center text-danger">Ditolak: {report.rejected_reason}</p>
              )}
            </div>
          )}
        </>
      )}
    </div>
  );
}

function DomainFields({ report }: { report: Laporan }) {
  return (
    <>
      {report.kegiatan && <Row label="Kegiatan" value={report.kegiatan} />}
      {report.kondisi && <Row label="Kondisi" value={report.kondisi} />}
      {report.temuan && <Row label="Temuan" value={report.temuan} />}
      {report.tindakan && <Row label="Tindakan" value={report.tindakan} />}
      {report.jenis_pekerjaan && (
        <Row label="Jenis pekerjaan" value={report.jenis_pekerjaan} />
      )}
      {report.uraian && <Row label="Uraian" value={report.uraian} />}
      {report.kondisi_stok && <Row label="Kondisi stok" value={report.kondisi_stok} />}
      {report.catatan_stok && <Row label="Catatan stok" value={report.catatan_stok} />}
      {report.checklist && report.checklist.length > 0 && (
        <div className="flex flex-col gap-1 border-b border-border pb-2">
          <span className="text-sm text-muted">Checklist</span>
          {report.checklist.map((c, i) => (
            <span key={i} className="text-sm text-text">
              {c.done ? "☑" : "☐"} {c.item}
            </span>
          ))}
        </div>
      )}
    </>
  );
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex justify-between gap-4 border-b border-border pb-2 last:border-0 last:pb-0">
      <span className="shrink-0 text-sm text-muted">{label}</span>
      <span className="text-right text-sm font-semibold text-text">{value}</span>
    </div>
  );
}
