import { registerPlugin } from "@wordpress/plugins";
import { PluginDocumentSettingPanel, store as editorStore } from "@wordpress/editor";
import { SelectControl } from "@wordpress/components";
import { useSelect } from "@wordpress/data";
import { useEntityProp } from "@wordpress/core-data";

import { SCHEMA_TYPES, isSchemaType } from "./schema-types";
import type { SchemaType } from "./schema-types";

interface SchemaMeta {
  readonly _better_schema_type: SchemaType;
}

function isSchemaMeta(value: unknown): value is SchemaMeta {
  return (
    typeof value === "object" &&
    value !== null &&
    "_better_schema_type" in value &&
    isSchemaType(value._better_schema_type)
  );
}

function SchemaPanel() {
  const postType = useSelect((select) => select(editorStore).getCurrentPostType(), []);
  const [meta, setMeta] = useEntityProp("postType", postType, "meta");

  if (!isSchemaMeta(meta)) {
    return null;
  }

  return (
    <PluginDocumentSettingPanel name="better-schema" title="Schema">
      <SelectControl
        label="Schema type"
        value={meta._better_schema_type}
        options={[...SCHEMA_TYPES]}
        onChange={(value) => {
          if (isSchemaType(value)) {
            setMeta({ ...meta, _better_schema_type: value });
          }
        }}
        __next40pxDefaultSize
        __nextHasNoMarginBottom
      />
    </PluginDocumentSettingPanel>
  );
}

registerPlugin("better-schema", { render: SchemaPanel });
