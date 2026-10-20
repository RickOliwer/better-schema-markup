import { describe, expect, it } from "vite-plus/test";
import {
  EMPTY_BUSINESS,
  isCoordinate,
  isFrontendUrl,
  invalidSocialProfiles,
  parseSocialProfiles,
  readBusinessSettings,
  readPluginSettings,
  readSettingsConfig,
} from "./settings-data";

const config = {
  postTypes: [{ name: "post", label: "Posts" }, { name: "book", label: "Books" }],
  showSetup: true,
  logoUrl: "",
  siteName: "My Site",
};

describe("settings data from WordPress", () => {
  it("accepts the screen configuration and rejects malformed values", () => {
    expect(readSettingsConfig(config)).toEqual(config);
    expect(readSettingsConfig({ ...config, postTypes: [{ name: 1, label: "Books" }] })).toBeNull();
    expect(readSettingsConfig({ ...config, showSetup: "yes" })).toBeNull();
  });

  it("reads valid settings and fills a missing custom post type default", () => {
    expect(readPluginSettings({
      better_schema_frontend_url: "",
      better_schema_identity: { type: "Person", name: "Ada", logo: 7, same_as: ["https://example.com"] },
      better_schema_type_defaults: { post: "BlogPosting" },
    }, config)).toEqual({
      frontendUrl: "",
      identity: { type: "Person", name: "Ada", logo: 7, same_as: ["https://example.com"] },
      defaults: { post: "BlogPosting", book: "WebPage" },
    });
  });

  it("rejects invalid identity settings from the REST API", () => {
    const valid = {
      better_schema_frontend_url: "",
      better_schema_identity: { type: "Organization", name: "Acme", logo: 0, same_as: [] },
      better_schema_type_defaults: {},
    };
    expect(readPluginSettings({ ...valid, better_schema_identity: { ...valid.better_schema_identity, logo: "7" } }, config)).toBeNull();
    expect(readPluginSettings({ ...valid, better_schema_identity: { ...valid.better_schema_identity, same_as: "https://example.com" } }, config)).toBeNull();
  });
});

describe("social profiles", () => {
  it("parses one URL per line and rejects unsafe or malformed URLs", () => {
    const urls = parseSocialProfiles(" https://example.com/a \n\n javascript:alert(1)\nhttp://example.org/x");
    expect(urls).toEqual(["https://example.com/a", "javascript:alert(1)", "http://example.org/x"]);
    expect(invalidSocialProfiles(urls)).toEqual(["javascript:alert(1)"]);
  });
});

describe("frontend URL", () => {
  it("accepts empty or absolute web bases and rejects credentials and suffixes", () => {
    for (const url of ["", "https://example.com", "http://localhost:3000/blog/"]) expect(isFrontendUrl(url)).toBe(true);
    for (const url of ["/blog", "javascript:alert(1)", "https://user:pass@example.com", "https://example.com?q=1", "https://example.com#x"]) expect(isFrontendUrl(url)).toBe(false);
  });
});

describe("local business settings", () => {
  it("reads valid settings", () => {
    const business = {
      enabled: true,
      telephone: "+46 8 123",
      price_range: "$$",
      address: { street: "Storgatan 1", locality: "Stockholm", region: "", postal_code: "111 22", country: "SE" },
      geo: { lat: "59.33", lng: "18.06" },
      hours: [{ days: ["Monday", "Friday"], opens: "09:00", closes: "17:00" }],
    };
    expect(readBusinessSettings({ better_schema_business: business })).toEqual(business);
  });

  it("falls back to empty settings for missing or malformed values", () => {
    expect(readBusinessSettings({})).toEqual(EMPTY_BUSINESS);
    expect(readBusinessSettings({ better_schema_business: "yes" })).toEqual(EMPTY_BUSINESS);
    expect(readBusinessSettings({ better_schema_business: { enabled: "true", address: 5, hours: "x" } })).toEqual(EMPTY_BUSINESS);
  });

  it("drops unknown weekdays and non-object hours", () => {
    const { hours } = readBusinessSettings({
      better_schema_business: { hours: [{ days: ["Monday", "Funday"], opens: "09:00", closes: "17:00" }, "x"] },
    });
    expect(hours).toEqual([{ days: ["Monday"], opens: "09:00", closes: "17:00" }]);
  });

  it("checks coordinates against their range", () => {
    expect(isCoordinate("", 90)).toBe(true);
    expect(isCoordinate("59.33", 90)).toBe(true);
    expect(isCoordinate("-90", 90)).toBe(true);
    expect(isCoordinate("91", 90)).toBe(false);
    expect(isCoordinate("north", 90)).toBe(false);
  });
});
