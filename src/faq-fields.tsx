import { Button, TextareaControl, TextControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

import type { FaqItem } from "./post-details";

interface FaqFieldsProps {
  readonly value: readonly FaqItem[];
  readonly onChange: (value: FaqItem[]) => void;
}

export function FaqFields({ value, onChange }: FaqFieldsProps) {
  function update(index: number, item: FaqItem) {
    onChange(value.map((existing, position) => (position === index ? item : existing)));
  }

  return (
    <>
      {value.map((item, index) => (
        // Items have no id, so the position is the identity; removing one re-keys the rest.
        <div key={index} className="bsm-fields" style={{ paddingBottom: "16px", borderBottom: "1px solid #dcdcde" }}>
          <TextControl
            label={__("Question", "better-schema-markup")}
            value={item.question}
            onChange={(question) => update(index, { ...item, question })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextareaControl
            label={__("Answer", "better-schema-markup")}
            value={item.answer}
            onChange={(answer) => update(index, { ...item, answer })}
            __nextHasNoMarginBottom
          />
          <div>
            <Button
              variant="link"
              isDestructive
              onClick={() => onChange(value.filter((_, position) => position !== index))}
            >
              {__("Remove question", "better-schema-markup")}
            </Button>
          </div>
        </div>
      ))}
      <div>
        <Button variant="secondary" onClick={() => onChange([...value, { question: "", answer: "" }])}>
          {__("Add question", "better-schema-markup")}
        </Button>
        <p className="bsm-help">{__("Questions without an answer are not saved.", "better-schema-markup")}</p>
      </div>
    </>
  );
}
