import { __ } from "@wordpress/i18n";

import { readCustomJsonLd, readCustomJsonLdMode } from "./post-details";
import { FormRoot } from "./components/ui/field";
import { richResultsTestUrl } from "./rich-results-test";
import { SchemaDetails } from "./schema-details";
import { SchemaPreviewSection } from "./schema-preview-section";
import type { SchemaMeta } from "./schema-meta";
import { SchemaTypePicker } from "./schema-type-picker";
import type { SchemaType } from "./schema-types";
import { useSchemaPreview } from "./use-schema-preview";

interface SchemaEditorFieldsProps {
  readonly postId: number;
  readonly meta: SchemaMeta;
  readonly savedAt: string;
  readonly testableUrl: string | null;
  readonly onChangeType: (type: SchemaType) => void;
  readonly onChangeDetail: (key: string, value: unknown) => void;
}

// Shared by both editors; callers own how changes are saved.
export function SchemaEditorFields({
  postId,
  meta,
  savedAt,
  testableUrl,
  onChangeType,
  onChangeDetail,
}: SchemaEditorFieldsProps) {
  const schemaType = meta._better_schema_type;
  const preview = useSchemaPreview(postId, schemaType, savedAt, {
    jsonLd: readCustomJsonLd(meta),
    mode: readCustomJsonLdMode(meta),
  });

  return (
    <FormRoot className="bsm:grid bsm:gap-6">
      <SchemaTypePicker value={schemaType} onChange={onChangeType} />
      <SchemaDetails meta={meta} schemaType={schemaType} onChange={onChangeDetail} />
      <SchemaPreviewSection state={preview} />
      {testableUrl && schemaType !== "none" ? (
        <p className="bsm:m-0 bsm:text-sm">
          <a href={richResultsTestUrl(testableUrl)} target="_blank" rel="noopener noreferrer" className="bsm:font-medium bsm:text-foreground bsm:underline bsm:underline-offset-4">
            {__("Test with Google", "better-schema-markup")}
            <span className="bsm:sr-only">{__("(opens in a new tab)", "better-schema-markup")}</span>
            <span aria-hidden="true"> ↗</span>
          </a>
        </p>
      ) : null}
    </FormRoot>
  );
}
