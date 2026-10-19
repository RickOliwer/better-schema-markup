export const SCHEMA_TYPES = [
  { label: "Article", value: "Article" },
  { label: "Blog posting", value: "BlogPosting" },
  { label: "Web page", value: "WebPage" },
  { label: "None", value: "none" },
] as const;

export type SchemaType = (typeof SCHEMA_TYPES)[number]["value"];

export function isSchemaType(value: unknown): value is SchemaType {
  return SCHEMA_TYPES.some((schemaType) => schemaType.value === value);
}
