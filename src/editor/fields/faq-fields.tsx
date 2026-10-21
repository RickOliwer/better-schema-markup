import { __, sprintf } from "@wordpress/i18n";

import { Button } from "../../components/ui/button";
import { FieldDescription } from "../../components/ui/field";
import type { FaqItem } from "../../schema/post-details";
import { TextareaField, TextField } from "../../components/form-fields";

interface FaqFieldsProps {
  readonly value: readonly FaqItem[];
  readonly onChange: (value: FaqItem[]) => void;
}

export function FaqFields({ value, onChange }: FaqFieldsProps) {
  function update(index: number, item: FaqItem) {
    onChange(value.map((existing, position) => (position === index ? item : existing)));
  }

  return (
    <div className="bsm:grid bsm:gap-3">
      {value.map((item, index) => (
        // Items have no id, so the position is the identity; removing one re-keys the rest.
        <fieldset
          key={index}
          className="bsm:m-0 bsm:grid bsm:min-w-0 bsm:gap-4 bsm:rounded-lg bsm:border bsm:border-solid bsm:border-border bsm:p-4"
        >
          <legend className="bsm:sr-only">
            {sprintf(
              /* translators: %d: question number. */
              __("Question %d", "better-schema-markup"),
              index + 1,
            )}
          </legend>
          <TextField
            label={__("Question", "better-schema-markup")}
            value={item.question}
            onChange={(question) => update(index, { ...item, question })}
          />
          <TextareaField
            label={__("Answer", "better-schema-markup")}
            rows={3}
            value={item.answer}
            onChange={(answer) => update(index, { ...item, answer })}
          />
          <div>
            <Button
              variant="destructive"
              size="sm"
              onClick={() => onChange(value.filter((_, position) => position !== index))}
            >
              {__("Remove question", "better-schema-markup")}
            </Button>
          </div>
        </fieldset>
      ))}
      <div className="bsm:grid bsm:justify-items-start bsm:gap-2">
        <Button
          variant="outline"
          size="sm"
          onClick={() => onChange([...value, { question: "", answer: "" }])}
        >
          {__("Add question", "better-schema-markup")}
        </Button>
        <FieldDescription>
          {__("Questions without an answer are not saved.", "better-schema-markup")}
        </FieldDescription>
      </div>
    </div>
  );
}
