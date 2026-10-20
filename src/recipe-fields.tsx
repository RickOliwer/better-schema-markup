import { TextareaControl, TextControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useState } from "react";

import { parseLines } from "./post-details";
import type { RecipeDetails } from "./post-details";

interface LineListFieldProps {
  readonly label: string;
  readonly help: string;
  readonly value: readonly string[];
  readonly onChange: (value: string[]) => void;
}

// Keeps the typed text locally so a trailing newline is not removed while typing.
function LineListField({ label, help, value, onChange }: LineListFieldProps) {
  const [draft, setDraft] = useState(value.join("\n"));

  return (
    <TextareaControl
      label={label}
      help={help}
      rows={5}
      value={draft}
      onChange={(next) => {
        setDraft(next);
        onChange(parseLines(next));
      }}
      __nextHasNoMarginBottom
    />
  );
}

function minutes(value: string): number {
  const parsed = Number.parseInt(value, 10);
  return Number.isSafeInteger(parsed) && parsed > 0 ? parsed : 0;
}

interface RecipeFieldsProps {
  readonly value: RecipeDetails;
  readonly onChange: (value: RecipeDetails) => void;
}

export function RecipeFields({ value, onChange }: RecipeFieldsProps) {
  return (
    <>
      <LineListField
        label={__("Ingredients", "better-schema-markup")}
        help={__("One ingredient per line.", "better-schema-markup")}
        value={value.ingredients}
        onChange={(ingredients) => onChange({ ...value, ingredients })}
      />
      <LineListField
        label={__("Steps", "better-schema-markup")}
        help={__("One step per line.", "better-schema-markup")}
        value={value.instructions}
        onChange={(instructions) => onChange({ ...value, instructions })}
      />
      <TextControl
        type="number"
        min={0}
        label={__("Preparation time (minutes)", "better-schema-markup")}
        value={value.prep_minutes ? String(value.prep_minutes) : ""}
        onChange={(next) => onChange({ ...value, prep_minutes: minutes(next) })}
        __next40pxDefaultSize
        __nextHasNoMarginBottom
      />
      <TextControl
        type="number"
        min={0}
        label={__("Cooking time (minutes)", "better-schema-markup")}
        value={value.cook_minutes ? String(value.cook_minutes) : ""}
        onChange={(next) => onChange({ ...value, cook_minutes: minutes(next) })}
        __next40pxDefaultSize
        __nextHasNoMarginBottom
      />
      <TextControl
        label={__("Servings", "better-schema-markup")}
        help={__("For example “4 servings”.", "better-schema-markup")}
        value={value.yield}
        onChange={(next) => onChange({ ...value, yield: next })}
        __next40pxDefaultSize
        __nextHasNoMarginBottom
      />
      <TextControl
        type="number"
        min={0}
        label={__("Calories per serving", "better-schema-markup")}
        value={value.calories ? String(value.calories) : ""}
        onChange={(next) => onChange({ ...value, calories: minutes(next) })}
        __next40pxDefaultSize
        __nextHasNoMarginBottom
      />
    </>
  );
}
