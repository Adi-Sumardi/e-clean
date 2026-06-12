"use client";

/** Menampilkan satu grup foto laporan (URL dari server). */
export default function ReportPhotos({
  label,
  urls,
}: {
  label: string;
  urls?: string[];
}) {
  if (!urls || urls.length === 0) return null;
  return (
    <div className="flex flex-col gap-2">
      <span className="text-sm font-semibold text-muted">{label}</span>
      <div className="grid grid-cols-3 gap-2">
        {urls.map((u) => (
          <a
            key={u}
            href={u}
            target="_blank"
            rel="noopener noreferrer"
            className="clay block aspect-square overflow-hidden rounded-2xl"
          >
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img src={u} alt={label} className="h-full w-full object-cover" />
          </a>
        ))}
      </div>
    </div>
  );
}
