import { describe, expect, it } from "vite-plus/test";
import { richResultsTestUrl, testablePostUrl } from "./rich-results-test";

describe("richResultsTestUrl", () => {
  it("links to Google's Rich Results Test with the page URL encoded", () => {
    expect(richResultsTestUrl("https://example.com/hello-world/?a=1&b=2")).toBe(
      "https://search.google.com/test/rich-results?url=https%3A%2F%2Fexample.com%2Fhello-world%2F%3Fa%3D1%26b%3D2",
    );
  });
});

describe("testablePostUrl", () => {
  it("returns the link of a published post", () => {
    expect(testablePostUrl("publish", "https://example.com/hello/")).toBe(
      "https://example.com/hello/",
    );
  });

  it("returns null for posts Google cannot load", () => {
    for (const status of ["draft", "pending", "private", "future", "auto-draft", undefined]) {
      expect(testablePostUrl(status, "https://example.com/hello/")).toBeNull();
    }
  });

  it("returns null for a missing or non-http link", () => {
    expect(testablePostUrl("publish", undefined)).toBeNull();
    expect(testablePostUrl("publish", "")).toBeNull();
    expect(testablePostUrl("publish", "javascript:alert(1)")).toBeNull();
    expect(testablePostUrl("publish", 42)).toBeNull();
  });
});
