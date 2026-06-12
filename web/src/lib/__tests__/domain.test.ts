import { describe, it, expect } from "vitest";
import {
  DOMAINS,
  REVIEW_DOMAINS,
  resolveDomain,
  isManager,
  isAdmin,
  domainByKey,
  roleLabel,
} from "@/lib/domain";

describe("resolveDomain", () => {
  it("memetakan tiap role petugas ke domain yang benar", () => {
    expect(resolveDomain(["petugas"])?.key).toBe("kebersihan");
    expect(resolveDomain(["satpam"])?.key).toBe("satpam");
    expect(resolveDomain(["office_boy"])?.key).toBe("ob");
    expect(resolveDomain(["petugas_toko"])?.key).toBe("toko");
  });

  it("mengembalikan null untuk role non-petugas", () => {
    expect(resolveDomain(["supervisor"])).toBeNull();
    expect(resolveDomain(["admin", "super_admin"])).toBeNull();
    expect(resolveDomain([])).toBeNull();
  });

  it("memilih role petugas pertama yang cocok bila role campuran", () => {
    expect(resolveDomain(["supervisor", "satpam"])?.key).toBe("satpam");
  });
});

describe("endpoint per domain", () => {
  it("kebersihan memakai endpoint legacy (tanpa prefiks domain)", () => {
    expect(DOMAINS.petugas.jadwalBase).toBe("/jadwal");
    expect(DOMAINS.petugas.laporanBase).toBe("/activity-reports");
  });

  it("domain field memakai prefiks masing-masing", () => {
    expect(DOMAINS.satpam.laporanBase).toBe("/satpam/laporan");
    expect(DOMAINS.office_boy.laporanBase).toBe("/office-boy/laporan");
    expect(DOMAINS.petugas_toko.laporanBase).toBe("/toko/laporan");
    expect(DOMAINS.satpam.jadwalBase).toBe("/satpam/jadwal");
    expect(DOMAINS.office_boy.jadwalBase).toBe("/office-boy/jadwal");
    expect(DOMAINS.petugas_toko.jadwalBase).toBe("/toko/jadwal");
  });
});

describe("isManager / isAdmin", () => {
  it("supervisor & pengurus adalah manager tapi bukan admin", () => {
    for (const role of ["supervisor", "pengurus"]) {
      expect(isManager([role])).toBe(true);
      expect(isAdmin([role])).toBe(false);
    }
  });

  it("admin & super_admin adalah manager sekaligus admin", () => {
    for (const role of ["admin", "super_admin"]) {
      expect(isManager([role])).toBe(true);
      expect(isAdmin([role])).toBe(true);
    }
  });

  it("petugas bukan manager maupun admin", () => {
    expect(isManager(["petugas"])).toBe(false);
    expect(isAdmin(["satpam"])).toBe(false);
  });
});

describe("domainByKey", () => {
  it("menemukan domain dari kunci internal", () => {
    expect(domainByKey("kebersihan")?.role).toBe("petugas");
    expect(domainByKey("ob")?.role).toBe("office_boy");
  });

  it("null untuk kunci tak dikenal / null", () => {
    expect(domainByKey("xyz")).toBeNull();
    expect(domainByKey(null)).toBeNull();
  });
});

describe("REVIEW_DOMAINS", () => {
  it("mencakup keempat domain untuk inbox review supervisor", () => {
    expect(REVIEW_DOMAINS.map((d) => d.key)).toEqual([
      "kebersihan",
      "satpam",
      "ob",
      "toko",
    ]);
  });
});

describe("roleLabel", () => {
  it("memberi label ramah dan fallback ke nama role", () => {
    expect(roleLabel("office_boy")).toBe("Office Boy");
    expect(roleLabel("role_aneh")).toBe("role_aneh");
  });
});
