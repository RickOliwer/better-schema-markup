import { describe, expect, it } from "vite-plus/test";
import { isImportReport } from "./import-report";

describe("import responses", () => {
  const report = {
    imported: 1,
    skipped: 0,
    unsupported: 0,
    failed: 0,
    cursor: 1,
    done: true,
    details: [{ post: 1, message: "Review" }],
  };
  it("accepts the batch report", () => {
    expect(isImportReport(report)).toBe(true);
  });
  it("rejects invalid API values", () => {
    for (const value of [
      null,
      {},
      { ...report, cursor: -1 },
      { ...report, imported: "1" },
      { ...report, details: [{}] },
      { ...report, done: 1 },
    ]) {
      expect(isImportReport(value)).toBe(false);
    }
  });
});
