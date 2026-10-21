import { __ } from "@wordpress/i18n";

import { SCHEMA_TYPES, schemaTypeDescription } from "./schema-types";
import type { SchemaType } from "./schema-types";
import { SelectField } from "./settings-fields";

interface SchemaTypePickerProps {
  readonly value: SchemaType;
  readonly onChange: (value: SchemaType) => void;
}

export function SchemaTypePicker({ value, onChange }: SchemaTypePickerProps) {
  return (
    <SelectField
      label={__("What is this page?", "better-schema-markup")}
      help={schemaTypeDescription(value)}
      value={value}
      options={SCHEMA_TYPES.map(({ label, value: optionValue }) => ({ label, value: optionValue }))}
      onChange={onChange}
    />
  );
}
