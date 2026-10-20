import { PanelBody } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

import { CustomJsonLdField } from "./custom-jsonld-field";
import { EventFields } from "./event-fields";
import { FaqFields } from "./faq-fields";
import {
  CUSTOM_JSONLD_META_KEY,
  EVENT_META_KEY,
  FAQ_META_KEY,
  RECIPE_META_KEY,
  readCustomJsonLd,
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
        <PanelBody title={__("Advanced", "better-schema-markup")} initialOpen={readCustomJsonLd(meta) !== ""}>
          <CustomJsonLdField
            value={readCustomJsonLd(meta)}
            onChange={(value) => onChange(CUSTOM_JSONLD_META_KEY, value)}
          />
        </PanelBody>
      ) : null}
    </>
  );
}
