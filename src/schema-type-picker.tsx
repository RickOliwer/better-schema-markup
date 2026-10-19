import { SelectControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

import { SCHEMA_TYPES, isSchemaType, schemaTypeDescription } from "./schema-types";
import type { SchemaType } from "./schema-types";

interface SchemaTypePickerProps {
  readonly value: SchemaType;
  readonly onChange: (value: SchemaType) => void;
}

export function SchemaTypePicker({ value, onChange }: SchemaTypePickerProps) {
  return (
    <SelectControl
      label={__("What is this page?", "better-schema-markup")}
      help={schemaTypeDescription(value)}
      value={value}
      options={SCHEMA_TYPES.map(({ label, value: optionValue }) => ({ label, value: optionValue }))}
      onChange={(selected) => {
        if (isSchemaType(selected)) {
          onChange(selected);
        }
      }}
      __next40pxDefaultSize
      __nextHasNoMarginBottom
    />
  );
}
