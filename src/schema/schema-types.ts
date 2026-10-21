import { __ } from "@wordpress/i18n";

export const SCHEMA_TYPES = [
  {
    label: __("Article", "better-schema-markup"),
    value: "Article",
    description: __("News, guides and other editorial content.", "better-schema-markup"),
  },
  {
    label: __("Blog post", "better-schema-markup"),
    value: "BlogPosting",
    description: __("A post on a blog, often more personal or informal.", "better-schema-markup"),
  },
  {
    label: __("Regular page", "better-schema-markup"),
    value: "WebPage",
    description: __(
      "A page that is not an article, such as About or Contact.",
      "better-schema-markup",
    ),
  },
  {
    label: __("Event", "better-schema-markup"),
    value: "Event",
    description: __(
      "A concert, class, meetup or other event with a date and place.",
      "better-schema-markup",
    ),
  },
  {
    label: __("Recipe", "better-schema-markup"),
    value: "Recipe",
    description: __("A cooking recipe with ingredients and steps.", "better-schema-markup"),
  },
  {
    label: __("FAQ page", "better-schema-markup"),
    value: "FAQPage",
    description: __(
      "A page of questions and answers. Google only shows FAQ results for well-known government and health sites.",
      "better-schema-markup",
    ),
  },
  {
    label: __("No schema", "better-schema-markup"),
    value: "none",
    description: __(
      "Turns off all schema on this page, including the site-wide information.",
      "better-schema-markup",
    ),
  },
] as const;

export type SchemaType = (typeof SCHEMA_TYPES)[number]["value"];

export function isSchemaType(value: unknown): value is SchemaType {
  return SCHEMA_TYPES.some((schemaType) => schemaType.value === value);
}

export function schemaTypeDescription(value: SchemaType): string {
  return SCHEMA_TYPES.find((schemaType) => schemaType.value === value)?.description ?? "";
}
