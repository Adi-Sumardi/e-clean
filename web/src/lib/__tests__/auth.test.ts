import { describe, it, expect, beforeEach, vi } from "vitest";

/**
 * auth.ts menyimpan cache token di state modul, jadi tiap test memuat ulang
 * modul agar saling terisolasi.
 */
async function freshAuth() {
  vi.resetModules();
  return import("@/lib/auth");
}

beforeEach(() => {
  window.localStorage.clear();
});

describe("token store", () => {
  it("default: tidak ada token → belum terautentikasi", async () => {
    const auth = await freshAuth();
    expect(auth.getToken()).toBeNull();
    expect(auth.isAuthenticated()).toBe(false);
  });

  it("setToken menyimpan ke localStorage dan getToken membacanya", async () => {
    const auth = await freshAuth();
    auth.setToken("abc123");
    expect(auth.getToken()).toBe("abc123");
    expect(auth.isAuthenticated()).toBe(true);
    expect(window.localStorage.getItem("eclean.auth.token")).toBe("abc123");
  });

  it("token bertahan lintas reload modul (persisted)", async () => {
    const auth1 = await freshAuth();
    auth1.setToken("persisted-token");

    const auth2 = await freshAuth(); // simulasi reload app
    expect(auth2.getToken()).toBe("persisted-token");
  });

  it("clearToken menghapus dari cache dan localStorage", async () => {
    const auth = await freshAuth();
    auth.setToken("to-be-cleared");
    auth.clearToken();
    expect(auth.getToken()).toBeNull();
    expect(auth.isAuthenticated()).toBe(false);
    expect(window.localStorage.getItem("eclean.auth.token")).toBeNull();
  });
});
