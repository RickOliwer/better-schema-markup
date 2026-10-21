import { describe, expect, it } from "vite-plus/test";
import { isSchemaPreview, isValidationHint } from "./schema-preview";

const hint = {
  type: "Article",
  property: "image",
  level: "recommended",
  message: "Add a featured image.",
};

const document = {
  "@context": "https://schema.org",
  "@graph": [{ "@type": "WebSite" }],
};

describe("isValidationHint", () => {
  it("accepts a hint from the preview endpoint", () => {
    expect(isValidationHint(hint)).toBe(true);
    expect(isValidationHint({ ...hint, level: "required" })).toBe(true);
  });

  it("rejects hints with missing or invalid fields", () => {
    expect(isValidationHint({ ...hint, level: "optional" })).toBe(false);
    expect(isValidationHint({ ...hint, message: 1 })).toBe(false);
    expect(isValidationHint({ type: "Article" })).toBe(false);
    expect(isValidationHint(null)).toBe(false);
    expect(isValidationHint("Add a featured image.")).toBe(false);
  });
});

describe("isSchemaPreview", () => {
  it("accepts a document with hints", () => {
    expect(isSchemaPreview({ document, hints: [hint] })).toBe(true);
  });

  it("accepts no document when the post outputs no schema", () => {
    expect(isSchemaPreview({ document: null, hints: [] })).toBe(true);
  });

  it("rejects malformed responses", () => {
    expect(isSchemaPreview(null)).toBe(false);
    expect(isSchemaPreview([])).toBe(false);
    expect(isSchemaPreview({ document })).toBe(false);
    expect(isSchemaPreview({ hints: [] })).toBe(false);
    expect(isSchemaPreview({ document: [], hints: [] })).toBe(false);
    expect(isSchemaPreview({ document: { "@graph": [] }, hints: [] })).toBe(false);
    expect(
      isSchemaPreview({ document: { "@context": "https://schema.org", "@graph": {} }, hints: [] }),
    ).toBe(false);
    expect(isSchemaPreview({ document, hints: [hint, { message: "x" }] })).toBe(false);
    expect(isSchemaPreview({ code: "rest_forbidden", message: "Sorry" })).toBe(false);
  });
});
