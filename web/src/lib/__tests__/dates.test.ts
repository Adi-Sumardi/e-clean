import { describe, it, expect } from "vitest";
import { datesInRange, weekdayDatesInRange, WEEKDAYS } from "@/lib/dates";

describe("datesInRange", () => {
  it("inklusif start..end", () => {
    expect(datesInRange("2026-06-10", "2026-06-12")).toEqual([
      "2026-06-10",
      "2026-06-11",
      "2026-06-12",
    ]);
  });

  it("satu hari bila start == end", () => {
    expect(datesInRange("2026-06-12", "2026-06-12")).toEqual(["2026-06-12"]);
  });

  it("melewati batas bulan dengan benar", () => {
    expect(datesInRange("2026-06-29", "2026-07-02")).toEqual([
      "2026-06-29",
      "2026-06-30",
      "2026-07-01",
      "2026-07-02",
    ]);
  });

  it("kosong untuk rentang terbalik / input tak valid / kosong", () => {
    expect(datesInRange("2026-06-12", "2026-06-10")).toEqual([]);
    expect(datesInRange("", "2026-06-12")).toEqual([]);
    expect(datesInRange("abc", "2026-06-12")).toEqual([]);
  });
});

describe("weekdayDatesInRange", () => {
  // 2026-06-08 = Senin … 2026-06-14 = Minggu
  it("memfilter hanya hari yang dipilih", () => {
    expect(weekdayDatesInRange("2026-06-08", "2026-06-14", [1, 3])).toEqual([
      "2026-06-08", // Senin
      "2026-06-10", // Rabu
    ]);
  });

  it("kosong bila tidak ada hari dipilih", () => {
    expect(weekdayDatesInRange("2026-06-08", "2026-06-14", [])).toEqual([]);
  });

  it("minggu (0) ikut terdukung", () => {
    expect(weekdayDatesInRange("2026-06-08", "2026-06-14", [0])).toEqual([
      "2026-06-14",
    ]);
  });
});

describe("WEEKDAYS", () => {
  it("7 hari, Senin lebih dulu, Minggu terakhir", () => {
    expect(WEEKDAYS).toHaveLength(7);
    expect(WEEKDAYS[0]).toEqual({ value: 1, label: "Sen" });
    expect(WEEKDAYS[6]).toEqual({ value: 0, label: "Min" });
  });
});
