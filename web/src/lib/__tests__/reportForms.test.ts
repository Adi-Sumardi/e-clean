import { describe, it, expect } from "vitest";
import { REPORT_SCHEMAS, photoFields } from "@/lib/reportForms";

const DOMAIN_KEYS = ["kebersihan", "satpam", "ob", "toko"] as const;

describe("REPORT_SCHEMAS", () => {
  it("tersedia untuk keempat domain", () => {
    for (const key of DOMAIN_KEYS) {
      expect(REPORT_SCHEMAS[key]).toBeDefined();
      expect(REPORT_SCHEMAS[key].fields.length).toBeGreaterThan(0);
    }
  });

  it("semua domain wajib jam_mulai dengan defaultNow", () => {
    for (const key of DOMAIN_KEYS) {
      const jamMulai = REPORT_SCHEMAS[key].fields.find((f) => f.name === "jam_mulai");
      expect(jamMulai, `${key} harus punya jam_mulai`).toBeDefined();
      expect(jamMulai!.required).toBe(true);
      expect(jamMulai!.kind).toBe("time");
    }
  });

  it("kebersihan: kegiatan + foto sebelum & sesudah wajib (min 1, sinkron API)", () => {
    const f = REPORT_SCHEMAS.kebersihan.fields;
    const kegiatan = f.find((x) => x.name === "kegiatan");
    expect(kegiatan?.required).toBe(true);

    for (const name of ["foto_sebelum", "foto_sesudah"]) {
      const foto = f.find((x) => x.name === name);
      expect(foto?.kind).toBe("photos");
      expect(foto?.required).toBe(true);
      if (foto?.kind === "photos") {
        expect(foto.min).toBe(1);
        expect(foto.max).toBe(5);
      }
    }
  });

  it("satpam: kondisi wajib dengan opsi aman/perhatian/bahaya (sinkron API)", () => {
    const kondisi = REPORT_SCHEMAS.satpam.fields.find((x) => x.name === "kondisi");
    expect(kondisi?.kind).toBe("select");
    expect(kondisi?.required).toBe(true);
    if (kondisi?.kind === "select") {
      expect(kondisi.options.map((o) => o.value)).toEqual([
        "aman",
        "perhatian",
        "bahaya",
      ]);
    }
  });

  it("toko: kondisi_stok beropsi aman/menipis/kosong (sinkron API)", () => {
    const stok = REPORT_SCHEMAS.toko.fields.find((x) => x.name === "kondisi_stok");
    expect(stok?.kind).toBe("select");
    if (stok?.kind === "select") {
      expect(stok.options.map((o) => o.value)).toEqual(["aman", "menipis", "kosong"]);
    }
  });

  it("semua field foto dibatasi maksimal 5", () => {
    for (const key of DOMAIN_KEYS) {
      for (const foto of photoFields(REPORT_SCHEMAS[key])) {
        if (foto.kind === "photos") expect(foto.max).toBe(5);
      }
    }
  });
});

describe("photoFields", () => {
  it("hanya mengembalikan field kind=photos", () => {
    const fotos = photoFields(REPORT_SCHEMAS.kebersihan);
    expect(fotos.map((f) => f.name)).toEqual(["foto_sebelum", "foto_sesudah"]);
    expect(fotos.every((f) => f.kind === "photos")).toBe(true);
  });
});
