import { ExternalLink } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

import { readCustomJsonLd, readCustomJsonLdMode } from "./post-details";
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
    <>
      <SchemaTypePicker value={schemaType} onChange={onChangeType} />
      <SchemaDetails meta={meta} schemaType={schemaType} onChange={onChangeDetail} />
      <SchemaPreviewSection state={preview} />
      {testableUrl && schemaType !== "none" ? (
        <p>
          <ExternalLink href={richResultsTestUrl(testableUrl)}>
            {__("Test with Google", "better-schema-markup")}
          </ExternalLink>
        </p>
      ) : null}
    </>
  );
}
