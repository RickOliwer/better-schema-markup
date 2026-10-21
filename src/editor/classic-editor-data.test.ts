import { describe, expect, it } from "vite-plus/test";
import { readClassicEditorConfig } from "./classic-editor-data";

describe("classic editor bootstrap", () => {
  const config = {
    postId: 42,
    savedAt: "2026-10-20 10:00:00",
    testableUrl: null,
    meta: { _better_schema_type: "WebPage" },
  };

  it("reads the effective type and safely defaults missing details", () => {
    expect(readClassicEditorConfig(config)).toMatchObject({
      postId: 42,
      meta: {
        _better_schema_type: "WebPage",
        _better_schema_faq: [],
        _better_schema_custom_jsonld: "",
        _better_schema_custom_mode: "add",
        _better_schema_event: { start: "", attendance: "offline" },
      },
    });
  });

  it("preserves saved details when switching between editor modes", () => {
    const meta = {
      _better_schema_type: "Recipe",
      _better_schema_recipe: { ingredients: ["Flour"], instructions: ["Mix"], cook_minutes: 20 },
      _better_schema_faq: [{ question: "Q", answer: "A" }],
      _better_schema_custom_jsonld: '{"@type":"Thing"}',
      _better_schema_custom_mode: "replace",
    };
    const result = readClassicEditorConfig({ ...config, meta });
    expect(result?.meta._better_schema_recipe).toMatchObject({
      ingredients: ["Flour"],
      instructions: ["Mix"],
      cook_minutes: 20,
    });
    expect(result?.meta._better_schema_faq).toEqual(meta._better_schema_faq);
    expect(result?.meta._better_schema_custom_jsonld).toBe(meta._better_schema_custom_jsonld);
    expect(result?.meta._better_schema_custom_mode).toBe("replace");
  });

  it("rejects invalid post identities and schema types before mounting", () => {
    for (const value of [
      null,
      {},
      { ...config, postId: 0 },
      { ...config, postId: "42" },
      { ...config, savedAt: false },
      { ...config, testableUrl: 7 },
      { ...config, meta: { _better_schema_type: "Invalid" } },
    ]) {
      expect(readClassicEditorConfig(value)).toBeNull();
    }
  });
});
