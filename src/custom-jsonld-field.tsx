import { Notice, RadioControl, TextareaControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useState } from "react";

import { checkCustomJsonLd, isCustomJsonLdMode } from "./post-details";
import type { CustomJsonLdMode } from "./post-details";

interface CustomJsonLdFieldProps {
  readonly value: string;
  readonly mode: CustomJsonLdMode;
  readonly onChange: (value: string) => void;
  readonly onChangeMode: (mode: CustomJsonLdMode) => void;
}

/**
 * Edits custom JSON-LD for the post and how it combines with the generated
 * schema. Only valid JSON, or an empty field, is passed on to be saved, so the
 * editor never stores a broken value.
 */
export function CustomJsonLdField({ value, mode, onChange, onChangeMode }: CustomJsonLdFieldProps) {
  const [draft, setDraft] = useState(value);
  const check = checkCustomJsonLd(draft);

  return (
    <>
      <TextareaControl
        label={__("Custom JSON-LD", "better-schema-markup")}
        help={__(
          "For advanced use. Paste one schema.org object, a list of objects, or a document with @graph.",
          "better-schema-markup",
        )}
        rows={8}
        value={draft}
        onChange={(next) => {
          setDraft(next);
          const result = checkCustomJsonLd(next);
          if (result.status !== "invalid") onChange(next);
        }}
        __nextHasNoMarginBottom
      />
      {check.status === "invalid" ? (
        <Notice status="error" isDismissible={false}>
          {check.reason === "syntax"
            ? __("This is not valid JSON, so it will not be saved.", "better-schema-markup")
            : __(
                "Each object needs an @type, so this will not be saved.",
                "better-schema-markup",
              )}
        </Notice>
      ) : null}
      <RadioControl
        label={__("How to use it", "better-schema-markup")}
        selected={mode}
        options={[
          {
            label: __("Add to generated schema", "better-schema-markup"),
            value: "add",
            description: __(
              "Output it alongside the schema this plugin generates.",
              "better-schema-markup",
            ),
          },
          {
            label: __("Override matching schema", "better-schema-markup"),
            value: "override",
            description: __(
              "Replace generated entities with the same @id or kind, such as WebSite, the organization or WebPage. Links between entities are kept.",
              "better-schema-markup",
            ),
          },
          {
            label: __("Replace generated schema", "better-schema-markup"),
            value: "replace",
            description: __(
              "Output only this JSON-LD on this page.",
              "better-schema-markup",
            ),
          },
        ]}
        onChange={(next) => {
          if (isCustomJsonLdMode(next)) onChangeMode(next);
        }}
      />
    </>
  );
}
