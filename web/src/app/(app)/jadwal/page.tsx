"use client";

import { useMe, useJadwalToday, useJadwalUpcoming } from "@/lib/hooks";
import { PageHeader, Spinner, EmptyState, ErrorState } from "@/components/ui";
import JadwalCard from "@/components/JadwalCard";

export default function JadwalPage() {
  const { domain } = useMe();
  const today = useJadwalToday(domain);
  const upcoming = useJadwalUpcoming(domain);

  return (
    <div className="flex flex-col gap-6">
      <PageHeader title="Jadwal" subtitle={domain?.label} />

      <section className="flex flex-col gap-3">
        <h2 className="text-sm font-bold text-muted">Hari ini</h2>
        {today.data && today.data.length > 0 ? (
          today.data.map((j) => <JadwalCard key={j.id} jadwal={j} />)
        ) : today.isLoading ? (
          <Spinner />
        ) : today.isError ? (
          <ErrorState message="Gagal memuat jadwal." onRetry={() => today.refetch()} />
        ) : (
          <EmptyState icon="☀️" title="Tidak ada jadwal hari ini" />
        )}
      </section>

      <section className="flex flex-col gap-3">
        <h2 className="text-sm font-bold text-muted">Akan datang</h2>
        {upcoming.data && upcoming.data.length > 0 ? (
          upcoming.data.map((j) => <JadwalCard key={j.id} jadwal={j} />)
        ) : upcoming.isLoading ? (
          <Spinner />
        ) : upcoming.isError ? (
          <ErrorState
            message="Gagal memuat jadwal."
            onRetry={() => upcoming.refetch()}
          />
        ) : (
          <EmptyState icon="🗓️" title="Belum ada jadwal mendatang" />
        )}
      </section>
    </div>
  );
}
