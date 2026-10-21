/** Per-post details saved in post meta for the Event, Recipe and FAQ page types. */

export const ATTENDANCE_MODES = ["offline", "online", "mixed"] as const;
export const EVENT_STATUSES = [
  "scheduled",
  "cancelled",
  "postponed",
  "rescheduled",
  "moved_online",
] as const;

export type AttendanceMode = (typeof ATTENDANCE_MODES)[number];
export type EventStatus = (typeof EVENT_STATUSES)[number];

export interface EventDetails {
  readonly start: string;
  readonly end: string;
  readonly attendance: AttendanceMode;
  readonly status: EventStatus;
  readonly location_name: string;
  readonly location_address: string;
  readonly offers_url: string;
}

export interface RecipeDetails {
  readonly ingredients: readonly string[];
  readonly instructions: readonly string[];
  readonly prep_minutes: number;
  readonly cook_minutes: number;
  readonly yield: string;
  readonly calories: number;
}

export interface FaqItem {
  readonly question: string;
  readonly answer: string;
}

export const EVENT_META_KEY = "_better_schema_event";
export const RECIPE_META_KEY = "_better_schema_recipe";
export const FAQ_META_KEY = "_better_schema_faq";
export const CUSTOM_JSONLD_META_KEY = "_better_schema_custom_jsonld";
export const CUSTOM_JSONLD_MODE_META_KEY = "_better_schema_custom_mode";

/**
 * How custom JSON-LD combines with the generated schema: added to it, replacing
 * generated entities with the same `@id` or role, or replacing it entirely.
 */
export const CUSTOM_JSONLD_MODES = ["add", "override", "replace"] as const;

export type CustomJsonLdMode = (typeof CUSTOM_JSONLD_MODES)[number];

export const EMPTY_EVENT: EventDetails = {
  start: "",
  end: "",
  attendance: "offline",
  status: "scheduled",
  location_name: "",
  location_address: "",
  offers_url: "",
};

export const EMPTY_RECIPE: RecipeDetails = {
  ingredients: [],
  instructions: [],
  prep_minutes: 0,
  cook_minutes: 0,
  yield: "",
  calories: 0,
};

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null && !Array.isArray(value);
}

function text(value: unknown): string {
  return typeof value === "string" ? value : "";
}

function whole(value: unknown): number {
  return typeof value === "number" && Number.isSafeInteger(value) && value > 0 ? value : 0;
}

function textList(value: unknown): string[] {
  return Array.isArray(value) ? value.filter((item): item is string => typeof item === "string") : [];
}

function oneOf<T extends string>(options: readonly T[], value: unknown, fallback: T): T {
  return options.find((option) => option === value) ?? fallback;
}

function metaValue(meta: unknown, key: string): unknown {
  return isRecord(meta) ? meta[key] : undefined;
}

/** Post meta comes from the REST API, so every field is checked and falls back to empty. */
export function readEventDetails(meta: unknown): EventDetails {
  const value = metaValue(meta, EVENT_META_KEY);
  if (!isRecord(value)) return EMPTY_EVENT;

  return {
    start: text(value.start),
    end: text(value.end),
    attendance: oneOf(ATTENDANCE_MODES, value.attendance, EMPTY_EVENT.attendance),
    status: oneOf(EVENT_STATUSES, value.status, EMPTY_EVENT.status),
    location_name: text(value.location_name),
    location_address: text(value.location_address),
    offers_url: text(value.offers_url),
  };
}

export function readRecipeDetails(meta: unknown): RecipeDetails {
  const value = metaValue(meta, RECIPE_META_KEY);
  if (!isRecord(value)) return EMPTY_RECIPE;

  return {
    ingredients: textList(value.ingredients),
    instructions: textList(value.instructions),
    prep_minutes: whole(value.prep_minutes),
    cook_minutes: whole(value.cook_minutes),
    yield: text(value.yield),
    calories: whole(value.calories),
  };
}

export function readFaqItems(meta: unknown): FaqItem[] {
  const value = metaValue(meta, FAQ_META_KEY);
  if (!Array.isArray(value)) return [];

  return value.flatMap((item: unknown) =>
    isRecord(item) ? [{ question: text(item.question), answer: text(item.answer) }] : [],
  );
}

export function readCustomJsonLd(meta: unknown): string {
  return text(metaValue(meta, CUSTOM_JSONLD_META_KEY));
}

export function isCustomJsonLdMode(value: unknown): value is CustomJsonLdMode {
  return CUSTOM_JSONLD_MODES.some((mode) => mode === value);
}

export function readCustomJsonLdMode(meta: unknown): CustomJsonLdMode {
  const value = metaValue(meta, CUSTOM_JSONLD_MODE_META_KEY);
  return isCustomJsonLdMode(value) ? value : "add";
}

export type CustomJsonLdCheck =
  | { readonly status: "empty" }
  | { readonly status: "valid" }
  | { readonly status: "invalid"; readonly reason: "syntax" | "shape" };

/**
 * Checks custom JSON-LD the way the server will: a JSON object or list whose
 * entities each have an `@type`, or a document with an `@graph` of such entities.
 * The server is the source of truth and drops anything it does not accept.
 */
export function checkCustomJsonLd(value: string): CustomJsonLdCheck {
  if (value.trim() === "") return { status: "empty" };

  let data: unknown;
  try {
    data = JSON.parse(value);
  } catch {
    return { status: "invalid", reason: "syntax" };
  }

  let entities: unknown = data;
  if (isRecord(data)) {
    entities = Array.isArray(data["@graph"]) ? data["@graph"] : "@type" in data ? [data] : [];
  }

  const hasEntity =
    Array.isArray(entities) && entities.some((entity) => isRecord(entity) && "@type" in entity);

  return hasEntity ? { status: "valid" } : { status: "invalid", reason: "shape" };
}

export function isAttendanceMode(value: unknown): value is AttendanceMode {
  return ATTENDANCE_MODES.some((mode) => mode === value);
}

export function isEventStatus(value: unknown): value is EventStatus {
  return EVENT_STATUSES.some((status) => status === value);
}

/** Splits text into lines, dropping blank ones. */
export function parseLines(value: string): string[] {
  return value
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter(Boolean);
}
