import { describe, it, expect, vi, afterEach } from "vitest";
import { formatTanggal, formatJam, namaBulan, timeAgo } from "@/lib/format";

afterEach(() => vi.useRealTimers());

describe("formatTanggal", () => {
  it("memformat ISO date ke bahasa Indonesia singkat", () => {
    // 2026-06-09 = Selasa
    expect(formatTanggal("2026-06-09")).toBe("Sel, 9 Jun 2026");
  });

  it("fallback aman untuk input kosong / tak valid", () => {
    expect(formatTanggal(null)).toBe("-");
    expect(formatTanggal(undefined)).toBe("-");
    expect(formatTanggal("bukan-tanggal")).toBe("bukan-tanggal");
  });
});

describe("formatJam", () => {
  it("menggabungkan jam mulai–selesai", () => {
    expect(formatJam("08:00", "10:00")).toBe("08:00 – 10:00");
  });

  it("menandai sisi yang kosong dengan ?", () => {
    expect(formatJam("08:00", null)).toBe("08:00 – ?");
    expect(formatJam(null, "10:00")).toBe("? – 10:00");
  });

  it("strip bila dua-duanya kosong", () => {
    expect(formatJam(null, undefined)).toBe("-");
  });
});

describe("namaBulan", () => {
  it("1=Januari, 12=Desember", () => {
    expect(namaBulan(1)).toBe("Januari");
    expect(namaBulan(12)).toBe("Desember");
  });

  it("fallback angka untuk di luar 1..12", () => {
    expect(namaBulan(13)).toBe("13");
    expect(namaBulan(0)).toBe("0");
  });
});

describe("timeAgo", () => {
  it("berjenjang detik → menit → jam → hari", () => {
    vi.useFakeTimers();
    vi.setSystemTime(new Date("2026-06-12T12:00:00Z"));
    expect(timeAgo("2026-06-12T11:59:30Z")).toBe("baru saja");
    expect(timeAgo("2026-06-12T11:55:00Z")).toBe("5 mnt");
    expect(timeAgo("2026-06-12T10:00:00Z")).toBe("2 jam");
    expect(timeAgo("2026-06-09T12:00:00Z")).toBe("3 hari");
  });

  it("string kosong untuk input tak valid", () => {
    expect(timeAgo(null)).toBe("");
    expect(timeAgo("xxx")).toBe("");
  });
});
