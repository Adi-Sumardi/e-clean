"use client";

/** Komponen UI bersama bertema Claymorphism. */

export function PageHeader({
  title,
  subtitle,
  right,
}: {
  title: string;
  subtitle?: string;
  right?: React.ReactNode;
}) {
  return (
    <header className="mb-5 flex items-center justify-between">
      <div>
        <h1 className="text-xl font-bold text-text">{title}</h1>
        {subtitle && <p className="text-sm text-muted">{subtitle}</p>}
      </div>
      {right}
    </header>
  );
}

export function Spinner({ label = "Memuat…" }: { label?: string }) {
  return (
    <div className="flex items-center justify-center gap-3 py-10 text-muted">
      <span className="h-5 w-5 animate-spin rounded-full border-2 border-muted/40 border-t-primary" />
      {label}
    </div>
  );
}

export function EmptyState({
  icon = "🗒️",
  title,
  hint,
}: {
  icon?: string;
  title: string;
  hint?: string;
}) {
  return (
    <div className="clay-sunken flex flex-col items-center gap-2 px-6 py-10 text-center">
      <span className="text-3xl">{icon}</span>
      <p className="font-semibold text-text">{title}</p>
      {hint && <p className="text-sm text-muted">{hint}</p>}
    </div>
  );
}

export function ErrorState({
  message,
  onRetry,
}: {
  message: string;
  onRetry?: () => void;
}) {
  return (
    <div className="clay-sunken flex flex-col items-center gap-3 px-6 py-8 text-center">
      <span className="text-3xl">⚠️</span>
      <p className="text-sm text-danger">{message}</p>
      {onRetry && (
        <button
          onClick={onRetry}
          className="clay-button px-5 py-2 text-sm font-semibold text-text"
        >
          Coba lagi
        </button>
      )}
    </div>
  );
}

const STATUS_STYLES: Record<string, string> = {
  approved: "bg-success/15 text-success",
  completed: "bg-success/15 text-success",
  selesai: "bg-success/15 text-success",
  active: "bg-success/15 text-success",
  rejected: "bg-danger/15 text-danger",
  inactive: "bg-muted/15 text-muted",
  skipped: "bg-muted/15 text-muted",
  pending: "bg-warning/15 text-[#b07d12]",
  in_progress: "bg-primary/15 text-primary",
  scheduled: "bg-primary/15 text-primary",
};

const STATUS_LABELS: Record<string, string> = {
  approved: "Disetujui",
  rejected: "Ditolak",
  pending: "Menunggu",
  in_progress: "Berjalan",
  scheduled: "Terjadwal",
  completed: "Selesai",
  selesai: "Selesai",
  active: "Aktif",
  inactive: "Nonaktif",
  skipped: "Dilewati",
};

export function StatusBadge({ status }: { status: string }) {
  const cls = STATUS_STYLES[status] ?? "bg-muted/15 text-muted";
  const label = STATUS_LABELS[status] ?? status;
  return (
    <span className={`rounded-full px-3 py-1 text-xs font-bold ${cls}`}>
      {label}
    </span>
  );
}
