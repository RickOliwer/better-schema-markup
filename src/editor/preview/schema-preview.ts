export const HINT_LEVELS = ["required", "recommended"] as const;

export type HintLevel = (typeof HINT_LEVELS)[number];

export interface ValidationHint {
  readonly type: string;
  readonly property: string;
  readonly level: HintLevel;
  readonly message: string;
}

export interface JsonLdDocument {
  readonly "@context": string;
  readonly "@graph": readonly unknown[];
}

/** Response of the `better-schema/v1/preview/{id}` endpoint. */
export interface SchemaPreview {
  readonly document: JsonLdDocument | null;
  readonly hints: readonly ValidationHint[];
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null && !Array.isArray(value);
}

function isHintLevel(value: unknown): value is HintLevel {
  return HINT_LEVELS.some((level) => level === value);
}

export function isValidationHint(value: unknown): value is ValidationHint {
  return (
    isRecord(value) &&
    typeof value.type === "string" &&
    typeof value.property === "string" &&
    isHintLevel(value.level) &&
    typeof value.message === "string"
  );
}

export function isJsonLdDocument(value: unknown): value is JsonLdDocument {
  return isRecord(value) && typeof value["@context"] === "string" && Array.isArray(value["@graph"]);
}

export function isSchemaPreview(value: unknown): value is SchemaPreview {
  return (
    isRecord(value) &&
    (value.document === null || isJsonLdDocument(value.document)) &&
    Array.isArray(value.hints) &&
    value.hints.every(isValidationHint)
  );
}
