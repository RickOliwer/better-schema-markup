import { describe, expect, it } from "vite-plus/test";

import { summarizeImport } from "./import-summary";

const none = { imported: 0, skipped: 0, unsupported: 0, failed: 0 };

describe("summarizeImport", () => {
  it("explains a preview where no post has anything to import", () => {
    expect(summarizeImport({ ...none, skipped: 11 }, true, "Yoast SEO")).toBe(
      "Preview: nothing to import. None of your 11 posts have a Yoast SEO schema choice to import. Nothing was saved.",
    );
  });

  it("explains a real import where nothing was imported", () => {
    const text = summarizeImport({ ...none, skipped: 3 }, false, "Rank Math");
    expect(text).toContain("Nothing was imported. None of your 3 posts have a Rank Math schema choice to import.");
    expect(text).not.toContain("preserved");
  });

  it("counts imported posts and mentions skipped, unsupported and failed ones", () => {
    const text = summarizeImport({ imported: 2, skipped: 1, unsupported: 1, failed: 1 }, false, "Yoast SEO");
    expect(text).toContain("Imported 2 of 4 posts.");
    expect(text).toContain("1 was skipped");
    expect(text).toContain("1 has settings that cannot be transferred");
    expect(text).toContain("1 post failed");
  });

  it("says a preview saves nothing", () => {
    expect(summarizeImport({ ...none, imported: 1 }, true, "Yoast SEO")).toBe(
      "Preview: 1 of 1 post would be imported. Nothing was saved.",
    );
  });

  it("handles a site with no posts", () => {
    expect(summarizeImport(none, true, "Yoast SEO")).toBe("There are no posts to check.");
  });
});
