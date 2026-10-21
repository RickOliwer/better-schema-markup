import { afterEach, describe, expect, it, vi } from "vite-plus/test";
import apiFetch from "@wordpress/api-fetch";
import { schemaApiFetch } from "./admin-api";

vi.mock("@wordpress/api-fetch", () => ({ default: vi.fn().mockResolvedValue({ saved: true }) }));

afterEach(() => {
  vi.unstubAllGlobals();
  vi.clearAllMocks();
});

describe("admin API requests", () => {
  it.each([
    "https://example.org/index.php?rest_route=%2F",
    "https://cloud.example.org/index.php?rest_route=%2F",
    "https://cloud.example.org/wordpress/index.php?rest_route=%2F",
  ])("uses the supplied backend independently of the frontend: %s", async (rootUrl) => {
    vi.stubGlobal("window", { betterSchemaApi: { rootUrl } });
    const result = await schemaApiFetch({ path: "/wp/v2/settings" });
    const options = vi.mocked(apiFetch).mock.calls[0]?.[0];
    const url = new URL(options?.url ?? "");
    expect(url.origin + url.pathname).toBe(rootUrl.split("?")[0]);
    expect(url.searchParams.get("rest_route")).toBe("/wp/v2/settings");
    expect(options).not.toHaveProperty("path");
    expect(result).toEqual({ saved: true });
  });

  it("preserves preview query parameters and request cancellation", async () => {
    vi.stubGlobal("window", {
      betterSchemaApi: { rootUrl: "https://cloud.example.org/index.php?rest_route=/" },
    });
    const controller = new AbortController();
    await schemaApiFetch({
      path: "/better-schema/v1/preview/42?type=BlogPosting&_locale=sv_SE",
      signal: controller.signal,
    });
    const options = vi.mocked(apiFetch).mock.calls[0]?.[0];
    const url = new URL(options?.url ?? "");
    expect(url.searchParams.get("rest_route")).toBe("/better-schema/v1/preview/42");
    expect(url.searchParams.get("type")).toBe("BlogPosting");
    expect(url.searchParams.get("_locale")).toBe("sv_SE");
    expect(options?.signal).toBe(controller.signal);
  });

  it.each(["/wp/v2/settings", "/better-schema/v1/import"])(
    "preserves POST data for %s",
    async (path) => {
      vi.stubGlobal("window", {
        betterSchemaApi: { rootUrl: "https://cloud.example.org/index.php?rest_route=/" },
      });
      const data = { source: "yoast", dry_run: true, better_schema_takeover: false };
      await schemaApiFetch({ path, method: "POST", data });
      expect(apiFetch).toHaveBeenCalledWith(expect.objectContaining({ method: "POST", data }));
    },
  );

  it("fails without configuration instead of requesting the frontend", async () => {
    vi.stubGlobal("window", {});
    await expect(schemaApiFetch({ path: "/wp/v2/settings" })).rejects.toThrow();
    expect(apiFetch).not.toHaveBeenCalled();
  });
});
