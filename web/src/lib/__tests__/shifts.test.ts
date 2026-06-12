import { describe, it, expect } from "vitest";
import { WORK_SHIFTS, shiftsFor } from "@/lib/shifts";

describe("WORK_SHIFTS (selaras App\\Enums\\WorkShift backend)", () => {
  it("5 shift dengan nilai yang dikenal backend", () => {
    expect(WORK_SHIFTS.map((s) => s.value)).toEqual([
      "pagi",
      "standby",
      "siang",
      "sweeping",
      "sore",
    ]);
  });

  it("tiap shift punya jam mulai < jam selesai (format HH:MM)", () => {
    for (const s of WORK_SHIFTS) {
      expect(s.mulai).toMatch(/^\d{2}:\d{2}$/);
      expect(s.selesai).toMatch(/^\d{2}:\d{2}$/);
      expect(s.mulai < s.selesai).toBe(true);
    }
  });

  it("label menampilkan rentang jam yang sama dengan field mulai/selesai", () => {
    for (const s of WORK_SHIFTS) {
      expect(s.label).toContain(s.mulai.replace(":", ":"));
      expect(s.label).toContain(s.selesai);
    }
  });
});

describe("shiftsFor", () => {
  it("semua domain memakai set shift yang sama", () => {
    for (const key of ["kebersihan", "satpam", "ob", "toko"]) {
      expect(shiftsFor(key)).toEqual(WORK_SHIFTS);
    }
  });
});
