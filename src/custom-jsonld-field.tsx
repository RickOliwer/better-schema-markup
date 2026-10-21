import { __ } from "@wordpress/i18n";
import { useId, useState } from "react";

import { HelpPopover } from "./components/ui/help-popover";
import { RadioGroup } from "./components/ui/radio-group";
import { checkCustomJsonLd } from "./post-details";
import type { CustomJsonLdMode } from "./post-details";
import { TextareaField } from "./settings-fields";

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
  const modeLabelId = useId();

  return (
    <div className="bsm:grid bsm:gap-6">
      <TextareaField
        label={__("Custom JSON-LD", "better-schema-markup")}
        tip={__(
          "Your own schema.org code for this page, for example from an SEO specialist. Paste one object, a list of objects, or a document with @graph. Each object needs an @type. Use the options below to choose how it combines with the schema this plugin generates.",
          "better-schema-markup",
        )}
        help={__(
          "For advanced use. Paste one schema.org object, a list of objects, or a document with @graph.",
          "better-schema-markup",
        )}
        error={
          check.status !== "invalid"
            ? undefined
            : check.reason === "syntax"
              ? __("This is not valid JSON, so it will not be saved.", "better-schema-markup")
              : __("Each object needs an @type, so this will not be saved.", "better-schema-markup")
        }
        rows={8}
        spellCheck={false}
        className="bsm:font-mono bsm:text-xs"
        value={draft}
        onChange={(next) => {
          setDraft(next);
          if (checkCustomJsonLd(next).status !== "invalid") onChange(next);
        }}
      />
      <div className="bsm:grid bsm:gap-2">
        <div className="bsm:flex bsm:items-center bsm:gap-1">
          <span
            id={modeLabelId}
            className="bsm:text-sm bsm:leading-snug bsm:font-medium bsm:text-foreground"
          >
            {__("How to use it", "better-schema-markup")}
          </span>
          <HelpPopover topic={__("How to use it", "better-schema-markup")}>
            {__(
              "Add keeps everything the plugin generates and adds yours next to it, which can create duplicates such as two WebSites. Override replaces generated entities of the same kind, such as the WebSite or organization, with yours. Replace outputs only your JSON-LD on this page.",
              "better-schema-markup",
            )}
          </HelpPopover>
        </div>
        <RadioGroup
          labelledBy={modeLabelId}
          value={mode}
          onChange={onChangeMode}
          choices={[
            {
              value: "add",
              label: __("Add to generated schema", "better-schema-markup"),
              description: __(
                "Output it alongside the schema this plugin generates.",
                "better-schema-markup",
              ),
            },
            {
              value: "override",
              label: __("Override matching schema", "better-schema-markup"),
              description: __(
                "Replace generated entities with the same @id or kind, such as WebSite, the organization or WebPage. Links between entities are kept.",
                "better-schema-markup",
              ),
            },
            {
              value: "replace",
              label: __("Replace generated schema", "better-schema-markup"),
              description: __("Output only this JSON-LD on this page.", "better-schema-markup"),
            },
          ]}
        />
      </div>
    </div>
  );
}
