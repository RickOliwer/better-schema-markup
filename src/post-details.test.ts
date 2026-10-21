import { describe, expect, it } from "vite-plus/test";
import {
  EMPTY_EVENT,
  EMPTY_RECIPE,
  checkCustomJsonLd,
  isAttendanceMode,
  isCustomJsonLdMode,
  isEventStatus,
  parseLines,
  readCustomJsonLd,
  readCustomJsonLdMode,
  readEventDetails,
  readFaqItems,
  readRecipeDetails,
} from "./post-details";

describe("readEventDetails", () => {
  it("reads valid details", () => {
    const event = {
      start: "2026-12-31T19:00",
      end: "",
      attendance: "mixed",
      status: "cancelled",
      location_name: "Hall",
      location_address: "Storgatan 1",
      offers_url: "https://example.com",
    };
    expect(readEventDetails({ _better_schema_event: event })).toEqual(event);
  });

  it("falls back for missing or invalid values", () => {
    expect(readEventDetails(undefined)).toEqual(EMPTY_EVENT);
    expect(readEventDetails({ _better_schema_event: [] })).toEqual(EMPTY_EVENT);
    expect(
      readEventDetails({ _better_schema_event: { start: 5, attendance: "hybrid", status: null } }),
    ).toEqual(EMPTY_EVENT);
  });
});

describe("readRecipeDetails", () => {
  it("reads valid details and drops invalid list items", () => {
    expect(
      readRecipeDetails({
        _better_schema_recipe: {
          ingredients: ["eggs", 3, "milk"],
          instructions: ["Whisk"],
          prep_minutes: 10,
          cook_minutes: -1,
          yield: "4",
          calories: 1.5,
        },
      }),
    ).toEqual({
      ingredients: ["eggs", "milk"],
      instructions: ["Whisk"],
      prep_minutes: 10,
      cook_minutes: 0,
      yield: "4",
      calories: 0,
    });
  });

  it("falls back to empty details", () => {
    expect(readRecipeDetails({})).toEqual(EMPTY_RECIPE);
    expect(readRecipeDetails({ _better_schema_recipe: "x" })).toEqual(EMPTY_RECIPE);
  });
});

describe("readFaqItems", () => {
  it("reads question and answer pairs and ignores non-objects", () => {
    expect(
      readFaqItems({
        _better_schema_faq: [{ question: "Q", answer: "A" }, "junk", { question: 1 }],
      }),
    ).toEqual([
      { question: "Q", answer: "A" },
      { question: "", answer: "" },
    ]);
    expect(readFaqItems({ _better_schema_faq: "no" })).toEqual([]);
    expect(readFaqItems(null)).toEqual([]);
  });
});

describe("readCustomJsonLd", () => {
  it("returns the stored string or an empty string", () => {
    expect(readCustomJsonLd({ _better_schema_custom_jsonld: '[{"@type":"A"}]' })).toBe(
      '[{"@type":"A"}]',
    );
    expect(readCustomJsonLd({ _better_schema_custom_jsonld: 5 })).toBe("");
  });
});

describe("readCustomJsonLdMode", () => {
  it("reads a valid mode and defaults to add", () => {
    expect(readCustomJsonLdMode({ _better_schema_custom_mode: "override" })).toBe("override");
    expect(readCustomJsonLdMode({ _better_schema_custom_mode: "replace" })).toBe("replace");
    for (const meta of [
      {},
      null,
      { _better_schema_custom_mode: "merge" },
      { _better_schema_custom_mode: 1 },
    ]) {
      expect(readCustomJsonLdMode(meta)).toBe("add");
    }
  });

  it("only accepts known modes", () => {
    expect(isCustomJsonLdMode("add")).toBe(true);
    expect(isCustomJsonLdMode("Override")).toBe(false);
    expect(isCustomJsonLdMode(undefined)).toBe(false);
  });
});

describe("checkCustomJsonLd", () => {
  it("accepts an entity, a list and a graph document", () => {
    expect(checkCustomJsonLd('{"@type":"Course"}').status).toBe("valid");
    expect(checkCustomJsonLd('[{"@type":"A"},{"@type":"B"}]').status).toBe("valid");
    expect(
      checkCustomJsonLd('{"@context":"https://schema.org","@graph":[{"@type":"A"}]}').status,
    ).toBe("valid");
  });

  it("treats blank input as empty", () => {
    expect(checkCustomJsonLd("  \n").status).toBe("empty");
  });

  it("rejects broken syntax and unusable shapes", () => {
    expect(checkCustomJsonLd("{nope")).toEqual({ status: "invalid", reason: "syntax" });
    for (const value of ['"text"', "42", "[]", '{"name":"no type"}', '[{"name":"x"}]', "null"]) {
      expect(checkCustomJsonLd(value)).toEqual({ status: "invalid", reason: "shape" });
    }
  });
});

describe("parseLines", () => {
  it("trims lines and drops blank ones", () => {
    expect(parseLines(" eggs \n\n  milk\r\n")).toEqual(["eggs", "milk"]);
    expect(parseLines("")).toEqual([]);
  });
});

describe("option guards", () => {
  it("accept known values only", () => {
    expect(isAttendanceMode("online")).toBe(true);
    expect(isAttendanceMode("hybrid")).toBe(false);
    expect(isEventStatus("moved_online")).toBe(true);
    expect(isEventStatus(undefined)).toBe(false);
  });
});
