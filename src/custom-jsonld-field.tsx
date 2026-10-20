import { Notice, TextareaControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useState } from "react";

import { checkCustomJsonLd } from "./post-details";

interface CustomJsonLdFieldProps {
  readonly value: string;
  readonly onChange: (value: string) => void;
}

/**
 * Edits custom JSON-LD for the post. Only valid JSON, or an empty field, is
 * passed on to be saved, so the editor never stores a broken value.
 */
export function CustomJsonLdField({ value, onChange }: CustomJsonLdFieldProps) {
  const [draft, setDraft] = useState(value);
  const check = checkCustomJsonLd(draft);

  return (
    <>
      <TextareaControl
        label={__("Custom JSON-LD", "better-schema-markup")}
        help={__(
          "For advanced use. Paste one schema.org object, a list of objects, or a document with @graph. It is added to this page’s schema.",
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
    </>
  );
}
