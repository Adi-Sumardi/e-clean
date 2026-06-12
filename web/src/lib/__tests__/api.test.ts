import { describe, it, expect, beforeEach, afterEach, vi } from "vitest";
import type { ApiError as ApiErrorT } from "@/lib/api";

/** Tangkap error promise dengan tipe ApiError (gagal bila tidak melempar). */
function caught(p: Promise<unknown>): Promise<ApiErrorT> {
  return p.then(
    () => {
      throw new Error("harusnya melempar error");
    },
    (e) => e as ApiErrorT,
  );
}

/** Respons JSON ala envelope Laravel. */
function jsonResponse(body: unknown, status = 200, headers: Record<string, string> = {}) {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json", ...headers },
  });
}

const fetchMock = vi.fn();

/** Muat ulang modul api+auth dengan localStorage/token terkontrol. */
async function freshApi() {
  vi.resetModules();
  vi.stubGlobal("fetch", fetchMock);
  const auth = await import("@/lib/auth");
  const api = await import("@/lib/api");
  return { auth, api };
}

beforeEach(() => {
  window.localStorage.clear();
  fetchMock.mockReset();
});

afterEach(() => {
  vi.unstubAllGlobals();
});

describe("request envelope & header", () => {
  it("meng-unwrap envelope {success, data} Laravel", async () => {
    const { api } = await freshApi();
    fetchMock.mockResolvedValueOnce(
      jsonResponse({ success: true, message: "ok", data: { id: 7 } }),
    );

    const data = await api.api.get<{ id: number }>("/jadwal/7");
    expect(data).toEqual({ id: 7 });

    const [url, init] = fetchMock.mock.calls[0];
    expect(url).toBe("/api/v1/jadwal/7");
    expect((init.headers as Record<string, string>).Accept).toBe("application/json");
  });

  it("mengirim Bearer token bila login", async () => {
    const { auth, api } = await freshApi();
    auth.setToken("tok-123");
    fetchMock.mockResolvedValueOnce(jsonResponse({ success: true, data: null }));

    await api.api.get("/auth/me");

    const [, init] = fetchMock.mock.calls[0];
    expect((init.headers as Record<string, string>).Authorization).toBe("Bearer tok-123");
  });

  it("menserialisasi params dan melewati yang undefined", async () => {
    const { api } = await freshApi();
    fetchMock.mockResolvedValueOnce(jsonResponse({ success: true, data: [] }));

    await api.api.get("/laporan-keterlambatan", {
      params: { domain: "satpam", bulan: 6, status: undefined },
    });

    const [url] = fetchMock.mock.calls[0];
    expect(url).toBe("/api/v1/laporan-keterlambatan?domain=satpam&bulan=6");
  });

  it("body JSON di-stringify + Content-Type json; FormData tanpa Content-Type", async () => {
    const { api } = await freshApi();
    fetchMock.mockResolvedValue(jsonResponse({ success: true, data: null }));

    await api.api.post("/units", { json: { nama_unit: "A" } });
    let [, init] = fetchMock.mock.calls[0];
    expect(init.body).toBe(JSON.stringify({ nama_unit: "A" }));
    expect((init.headers as Record<string, string>)["Content-Type"]).toBe("application/json");

    const fd = new FormData();
    fd.append("x", "1");
    await api.api.post("/activity-reports", { form: fd });
    [, init] = fetchMock.mock.calls[1];
    expect(init.body).toBe(fd);
    // Browser harus mengisi boundary sendiri.
    expect((init.headers as Record<string, string>)["Content-Type"]).toBeUndefined();
  });
});

describe("penanganan error", () => {
  it("error transport → ApiError dengan status undefined (penanda offline)", async () => {
    const { api } = await freshApi();
    fetchMock.mockRejectedValueOnce(new TypeError("Failed to fetch"));

    const err = await caught(api.api.get("/jadwal"));
    expect(err).toBeInstanceOf(api.ApiError);
    expect(err.status).toBeUndefined();
    expect(api.isOfflineError(err)).toBe(true);
  });

  it("4xx → ApiError berisi pesan + errors validasi server (BUKAN offline)", async () => {
    const { api } = await freshApi();
    fetchMock.mockResolvedValueOnce(
      jsonResponse(
        {
          success: false,
          message: "Data tidak valid",
          errors: { kegiatan: ["Kegiatan wajib diisi"] },
        },
        422,
      ),
    );

    const err = await caught(api.api.post("/activity-reports"));
    expect(err).toBeInstanceOf(api.ApiError);
    expect(err.status).toBe(422);
    expect(err.message).toBe("Data tidak valid");
    expect(err.errors).toEqual({ kegiatan: ["Kegiatan wajib diisi"] });
    expect(api.isOfflineError(err)).toBe(false);
  });

  it("401 → token dibersihkan (sesi berakhir)", async () => {
    const { auth, api } = await freshApi();
    auth.setToken("expired");
    fetchMock.mockResolvedValueOnce(jsonResponse({ message: "Unauthenticated" }, 401));

    const err = await caught(api.api.get("/auth/me"));
    expect(err.status).toBe(401);
    expect(auth.getToken()).toBeNull();
  });
});

describe("downloadFile (unduh PDF dengan Bearer)", () => {
  it("memicu unduhan blob dengan nama dari Content-Disposition", async () => {
    const { auth, api } = await freshApi();
    auth.setToken("tok-pdf");

    // Body string (bukan Blob jsdom) — Response Node menolak Blob asing.
    fetchMock.mockResolvedValueOnce(
      new Response("%PDF-1.4", {
        status: 200,
        headers: {
          "Content-Type": "application/pdf",
          "Content-Disposition": 'attachment; filename="laporan-juni.pdf"',
        },
      }),
    );

    const created: string[] = [];
    vi.stubGlobal("URL", {
      ...URL,
      createObjectURL: vi.fn(() => {
        created.push("blob:mock");
        return "blob:mock";
      }),
      revokeObjectURL: vi.fn(),
    });
    const clickSpy = vi
      .spyOn(HTMLAnchorElement.prototype, "click")
      .mockImplementation(() => {});

    await api.downloadFile("/reports/monthly/pdf", { bulan: 6 }, "fallback.pdf");

    const [url, init] = fetchMock.mock.calls[0];
    expect(url).toBe("/api/v1/reports/monthly/pdf?bulan=6");
    expect((init.headers as Record<string, string>).Authorization).toBe("Bearer tok-pdf");
    expect(created).toHaveLength(1);
    expect(clickSpy).toHaveBeenCalledOnce();
    clickSpy.mockRestore();
  });

  it("status gagal → ApiError dengan pesan server", async () => {
    const { api } = await freshApi();
    fetchMock.mockResolvedValueOnce(
      jsonResponse({ success: false, message: "You are not allowed" }, 403),
    );

    const err = await caught(api.downloadFile("/reports/monthly/pdf", {}, "x.pdf"));
    expect(err).toBeInstanceOf(api.ApiError);
    expect(err.status).toBe(403);
    expect(err.message).toBe("You are not allowed");
  });

  it("error transport → penanda offline", async () => {
    const { api } = await freshApi();
    fetchMock.mockRejectedValueOnce(new TypeError("Failed to fetch"));

    const err = await caught(api.downloadFile("/reports/monthly/pdf", {}, "x.pdf"));
    expect(api.isOfflineError(err)).toBe(true);
  });
});
