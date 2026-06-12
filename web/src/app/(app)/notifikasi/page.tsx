"use client";

import { useNotifications } from "@/lib/hooks";
import { PageHeader, Spinner, EmptyState, ErrorState } from "@/components/ui";
import { timeAgo } from "@/lib/format";
import type { NotificationItem } from "@/lib/types";

const ICONS: Record<string, string> = {
  report_approved: "✅",
  report_rejected: "❌",
  guest_complaint: "📣",
};

export default function NotifikasiPage() {
  const { data, isLoading, isError, refetch } = useNotifications();
  const items = data?.items ?? [];

  return (
    <div className="flex flex-col gap-5">
      <PageHeader title="Notifikasi" />

      {items.length > 0 ? (
        <div className="flex flex-col gap-3">
          {items.map((n) => (
            <NotifRow key={n.id} n={n} />
          ))}
        </div>
      ) : isLoading ? (
        <Spinner />
      ) : isError ? (
        <ErrorState message="Gagal memuat notifikasi." onRetry={() => refetch()} />
      ) : (
        <EmptyState icon="🔔" title="Belum ada notifikasi" />
      )}
    </div>
  );
}

function NotifRow({ n }: { n: NotificationItem }) {
  return (
    <div className="clay flex items-start gap-3 p-4">
      <span className="clay-sunken grid h-10 w-10 shrink-0 place-items-center rounded-2xl text-lg">
        {ICONS[n.type] ?? "🔔"}
      </span>
      <div className="min-w-0 flex-1">
        <div className="flex items-center justify-between gap-2">
          <p className="font-semibold text-text">{n.title}</p>
          <span className="shrink-0 text-xs text-muted">{timeAgo(n.time)}</span>
        </div>
        <p className="mt-0.5 text-sm text-muted">{n.body}</p>
      </div>
    </div>
  );
}
