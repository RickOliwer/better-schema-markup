import { describe, expect, it } from "vite-plus/test";
import { isSchemaMeta } from "./schema-meta";

describe("isSchemaMeta", () => {
  it("accepts meta with a valid schema type", () => {
    expect(isSchemaMeta({ _better_schema_type: "BlogPosting" })).toBe(true);
    expect(isSchemaMeta({ _better_schema_type: "none", other_meta: 1 })).toBe(true);
  });

  it("rejects missing or invalid meta", () => {
    expect(isSchemaMeta(undefined)).toBe(false);
    expect(isSchemaMeta(null)).toBe(false);
    expect(isSchemaMeta([])).toBe(false);
    expect(isSchemaMeta({})).toBe(false);
    expect(isSchemaMeta({ _better_schema_type: "Product" })).toBe(false);
    expect(isSchemaMeta({ _better_schema_type: 1 })).toBe(false);
  });
});
