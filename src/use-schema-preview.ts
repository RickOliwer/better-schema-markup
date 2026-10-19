import { useEffect, useState } from "react";
import apiFetch from "@wordpress/api-fetch";
import { addQueryArgs } from "@wordpress/url";

import { isSchemaPreview } from "./schema-preview";
import type { SchemaPreview } from "./schema-preview";
import type { SchemaType } from "./schema-types";

export interface SchemaPreviewState {
  /** The latest preview. Kept while a newer one loads, so the panel does not flicker. */
  readonly preview: SchemaPreview | null;
  readonly isLoading: boolean;
  readonly hasError: boolean;
}

/**
 * Fetches the JSON-LD the frontend would output for a post, using the schema
 * type picked in the editor. Refetches when the type changes or the post is saved.
 *
 * @param postId     Post being edited.
 * @param schemaType Type picked in the editor, which may not be saved yet.
 * @param savedAt    Changes on every save, for example the post's `modified` date.
 */
export function useSchemaPreview(
  postId: number,
  schemaType: SchemaType,
  savedAt: string,
): SchemaPreviewState {
  const [state, setState] = useState<SchemaPreviewState>({
    preview: null,
    isLoading: true,
    hasError: false,
  });

  useEffect(() => {
    const controller = new AbortController();

    setState((previous) => ({ ...previous, isLoading: true }));

    apiFetch({
      path: addQueryArgs(`/better-schema/v1/preview/${postId}`, { type: schemaType }),
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
  }, [postId, schemaType, savedAt]);

  return state;
}
