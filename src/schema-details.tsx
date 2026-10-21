import { __ } from "@wordpress/i18n";
import { useState } from "react";

import { CustomJsonLdField } from "./custom-jsonld-field";
import { EventFields } from "./event-fields";
import { FaqFields } from "./faq-fields";
import {
  CUSTOM_JSONLD_META_KEY,
  CUSTOM_JSONLD_MODE_META_KEY,
  EVENT_META_KEY,
  FAQ_META_KEY,
  RECIPE_META_KEY,
  readCustomJsonLd,
  readCustomJsonLdMode,
  readEventDetails,
  readFaqItems,
  readRecipeDetails,
} from "./post-details";
import { RecipeFields } from "./recipe-fields";
import type { SchemaType } from "./schema-types";

interface SchemaDetailsProps {
  /** The post's meta, which is validated field by field. */
  readonly meta: unknown;
  readonly schemaType: SchemaType;
  readonly onChange: (key: string, value: unknown) => void;
}

/** The fields for the chosen schema type, and the advanced custom JSON-LD field. */
export function SchemaDetails({ meta, schemaType, onChange }: SchemaDetailsProps) {
  // Open at first when the post has custom JSON-LD; after that the user decides,
  // so clearing the field while typing does not collapse it.
  const [advancedOpen] = useState(() => readCustomJsonLd(meta) !== "");

  return (
    <>
      {schemaType === "Event" ? (
        <EventFields value={readEventDetails(meta)} onChange={(value) => onChange(EVENT_META_KEY, value)} />
      ) : null}
      {schemaType === "Recipe" ? (
        <RecipeFields value={readRecipeDetails(meta)} onChange={(value) => onChange(RECIPE_META_KEY, value)} />
      ) : null}
      {schemaType === "FAQPage" ? (
        <FaqFields value={readFaqItems(meta)} onChange={(value) => onChange(FAQ_META_KEY, value)} />
      ) : null}
      {schemaType !== "none" ? (
        <details
          open={advancedOpen || undefined}
          className="bsm:group bsm:rounded-lg bsm:border bsm:border-solid bsm:border-border"
        >
          <summary className="bsm:flex bsm:cursor-pointer bsm:list-none bsm:items-center bsm:justify-between bsm:gap-2 bsm:px-4 bsm:py-3 bsm:text-sm bsm:font-medium bsm:text-foreground bsm:select-none bsm:hover:bg-accent bsm:[&::-webkit-details-marker]:hidden">
            {__("Advanced", "better-schema-markup")}
            <svg className="bsm:text-muted-foreground bsm:transition-transform bsm:group-open:rotate-180" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5" aria-hidden="true">
              <path d="m4 6 4 4 4-4" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
          </summary>
          <div aria-hidden="true" className="bsm:h-px bsm:bg-border" />
          <div className="bsm:p-4">
            <CustomJsonLdField
              value={readCustomJsonLd(meta)}
              mode={readCustomJsonLdMode(meta)}
              onChange={(value) => onChange(CUSTOM_JSONLD_META_KEY, value)}
              onChangeMode={(mode) => onChange(CUSTOM_JSONLD_MODE_META_KEY, mode)}
            />
          </div>
        </details>
      ) : null}
    </>
  );
}
