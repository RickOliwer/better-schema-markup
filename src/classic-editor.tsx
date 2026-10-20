import { useState } from "react";
import { __ } from "@wordpress/i18n";

import type { ClassicEditorConfig } from "./classic-editor-data";
import { SchemaEditorFields } from "./schema-editor-fields";
import type { SchemaType } from "./schema-types";

interface ClassicEditorProps {
  readonly config: ClassicEditorConfig;
}

export function ClassicEditor({ config }: ClassicEditorProps) {
  const [meta, setMeta] = useState(config.meta);
  const [changes, setChanges] = useState<Readonly<Record<string, unknown>>>({});

  function change(key: string, value: unknown) {
    setMeta((previous) => ({ ...previous, [key]: value }));
    setChanges((previous) => ({ ...previous, [key]: value }));
  }

  return (
    <div style={{ maxWidth: "760px", display: "grid", gap: "12px" }}>
      <p>{__("Schema changes are saved when you update or publish this page.", "better-schema-markup")}</p>
      <input type="hidden" name="better_schema_classic_meta" value={JSON.stringify(changes)} />
      <SchemaEditorFields
        postId={config.postId}
        meta={meta}
        savedAt={config.savedAt}
        testableUrl={config.testableUrl}
        onChangeType={(type: SchemaType) => change("_better_schema_type", type)}
        onChangeDetail={change}
      />
    </div>
  );
}
