import { useEffect, useState } from "react";
import { schemaApiFetch } from "../../api/admin-api";

import type { CustomJsonLdMode } from "../../schema/post-details";
import { isSchemaPreview } from "./schema-preview";
import type { SchemaPreview } from "./schema-preview";
import type { SchemaType } from "../../schema/schema-types";
import { useDebouncedValue } from "../../hooks/use-debounced-value";

/** Wait for typing to pause before previewing custom JSON-LD. */
const CUSTOM_JSONLD_DELAY = 500;

export interface UnsavedCustomJsonLd {
  readonly jsonLd: string;
  readonly mode: CustomJsonLdMode;
}

export interface SchemaPreviewState {
  /** The latest preview. Kept while a newer one loads, so the panel does not flicker. */
  readonly preview: SchemaPreview | null;
  readonly isLoading: boolean;
  readonly hasError: boolean;
}

/**
 * Fetches the JSON-LD the frontend would output for a post, using the schema
 * type and custom JSON-LD in the editor. Refetches when they change or the
 * post is saved.
 *
 * @param postId     Post being edited.
 * @param schemaType Type picked in the editor, which may not be saved yet.
 * @param savedAt    Changes on every save, for example the post's `modified` date.
 * @param custom     Custom JSON-LD and its mode in the editor, which may not be saved yet.
 */
export function useSchemaPreview(
  postId: number,
  schemaType: SchemaType,
  savedAt: string,
  custom: UnsavedCustomJsonLd,
): SchemaPreviewState {
  const customJsonLd = useDebouncedValue(custom.jsonLd, CUSTOM_JSONLD_DELAY);
  const customMode = custom.mode;
  const [state, setState] = useState<SchemaPreviewState>({
    preview: null,
    isLoading: true,
    hasError: false,
  });

  useEffect(() => {
    const controller = new AbortController();

    setState((previous) => ({ ...previous, isLoading: true }));

    schemaApiFetch({
      path: `/better-schema/v1/preview/${postId}`,
      method: "POST",
      data: { type: schemaType, custom: customJsonLd, custom_mode: customMode },
      signal: controller.signal,
    })
      .then((response: unknown) => {
        setState(
          isSchemaPreview(response)
            ? { preview: response, isLoading: false, hasError: false }
            : { preview: null, isLoading: false, hasError: true },
        );
      })
      .catch(() => {
        if (!controller.signal.aborted) {
          setState({ preview: null, isLoading: false, hasError: true });
        }
      });

    return () => controller.abort();
  }, [postId, schemaType, savedAt, customJsonLd, customMode]);

  return state;
}
