import type { TaskItem } from "@/components/TaskCard";
import type { Jadwal } from "./types";

/** Map a backend Jadwal into the TaskCard's TaskItem shape. */
export function jadwalToTask(j: Jadwal): TaskItem {
  const status: TaskItem["status"] =
    j.status === "completed"
      ? "done"
      : j.status === "in_progress"
        ? "in_progress"
        : "pending";
  return {
    id: j.id,
    title: j.lokasi?.nama_lokasi
      ? `Pembersihan ${j.lokasi.nama_lokasi}`
      : `Jadwal #${j.id}`,
    location: j.lokasi
      ? [j.lokasi.nama_lokasi, j.lokasi.lantai].filter(Boolean).join(" · ")
      : undefined,
    time: [j.jam_mulai, j.jam_selesai].filter(Boolean).join(" - "),
    status,
  };
}
