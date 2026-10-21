import { isSchemaMeta } from "./schema-meta";
import type { SchemaMeta } from "./schema-meta";
import {
  readCustomJsonLd,
  readCustomJsonLdMode,
  readEventDetails,
  readFaqItems,
  readRecipeDetails,
} from "./post-details";

export interface ClassicEditorConfig {
  readonly postId: number;
  readonly savedAt: string;
  readonly testableUrl: string | null;
  readonly meta: SchemaMeta & Readonly<Record<string, unknown>>;
}

export function readClassicEditorConfig(value: unknown): ClassicEditorConfig | null {
  if (typeof value !== "object" || value === null || !("postId" in value) || typeof value.postId !== "number" || !Number.isSafeInteger(value.postId) || value.postId <= 0 || !("savedAt" in value) || typeof value.savedAt !== "string" || !("testableUrl" in value) || (value.testableUrl !== null && typeof value.testableUrl !== "string") || !("meta" in value) || !isSchemaMeta(value.meta)) {
    return null;
  }

  return {
    postId: value.postId,
    savedAt: value.savedAt,
    testableUrl: value.testableUrl,
    meta: {
      _better_schema_type: value.meta._better_schema_type,
      _better_schema_event: readEventDetails(value.meta),
      _better_schema_recipe: readRecipeDetails(value.meta),
      _better_schema_faq: readFaqItems(value.meta),
      _better_schema_custom_jsonld: readCustomJsonLd(value.meta),
      _better_schema_custom_mode: readCustomJsonLdMode(value.meta),
    },
  };
}
