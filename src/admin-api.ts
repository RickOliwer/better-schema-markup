import apiFetch from "@wordpress/api-fetch";
import { __ } from "@wordpress/i18n";

declare global {
  interface Window {
    betterSchemaApi?: unknown;
  }
}

interface SchemaApiOptions {
  readonly path: string;
  readonly method?: "GET" | "POST";
  readonly data?: unknown;
  readonly signal?: AbortSignal;
}

/** Use explicit backend URLs without changing other plugins' apiFetch routing. */
export async function schemaApiFetch({ path, ...options }: SchemaApiOptions): Promise<unknown> {
  const config = window.betterSchemaApi;
  if (typeof config !== "object" || config === null || !("rootUrl" in config) || typeof config.rootUrl !== "string") {
    throw new Error(__("Could not configure the WordPress backend request. Reload this page and try again.", "better-schema-markup"));
  }

  const url = new URL(config.rootUrl);
  const queryStart = path.indexOf("?");
  const route = queryStart < 0 ? path : path.slice(0, queryStart);
  const query = new URLSearchParams(queryStart < 0 ? "" : path.slice(queryStart + 1));
  query.forEach((value, key) => url.searchParams.append(key, value));
  url.searchParams.set("rest_route", route);

  // apiFetch still supplies WordPress's nonce, user locale and same-origin cookies.
  // Omitting `path` prevents its global root middleware from choosing the frontend.
  return apiFetch({ ...options, url: url.toString() });
}
