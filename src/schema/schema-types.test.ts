import { describe, expect, it } from "vite-plus/test";
import { SCHEMA_TYPES, isSchemaType, schemaTypeDescription } from "./schema-types";

describe("isSchemaType", () => {
  it("accepts every type offered in the editor panel", () => {
    for (const { value } of SCHEMA_TYPES) {
      expect(isSchemaType(value)).toBe(true);
    }
  });

  it("rejects unknown values", () => {
    expect(isSchemaType("Product")).toBe(false);
    expect(isSchemaType("")).toBe(false);
    expect(isSchemaType(undefined)).toBe(false);
    expect(isSchemaType(null)).toBe(false);
  });
});

describe("SCHEMA_TYPES", () => {
  it("matches the types the PHP side accepts", () => {
    // Keep in sync with better_schema_types() in includes/data/meta.php.
    expect(SCHEMA_TYPES.map((schemaType) => schemaType.value)).toEqual([
      "Article",
      "BlogPosting",
      "WebPage",
      "Event",
      "Recipe",
      "FAQPage",
      "none",
    ]);
  });
});

describe("schemaTypeDescription", () => {
  it("explains every type in plain language", () => {
    for (const { value } of SCHEMA_TYPES) {
      expect(schemaTypeDescription(value)).not.toBe("");
    }
  });
});
