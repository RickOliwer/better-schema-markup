import { ExternalLink } from "@wordpress/components";
import { useEntityProp } from "@wordpress/core-data";
import { useSelect } from "@wordpress/data";
import { PluginDocumentSettingPanel, store as editorStore } from "@wordpress/editor";
import { __ } from "@wordpress/i18n";

import { richResultsTestUrl, testablePostUrl } from "./rich-results-test";
import { SchemaPreviewSection } from "./schema-preview-section";
import { isSchemaMeta } from "./schema-meta";
import type { SchemaMeta } from "./schema-meta";
import { SchemaTypePicker } from "./schema-type-picker";
import type { SchemaType } from "./schema-types";
import { useSchemaPreview } from "./use-schema-preview";

export function SchemaPanel() {
  const { postType, postId, savedAt, testableUrl } = useSelect((select) => {
    const editor = select(editorStore);
    const modified: unknown = editor.getCurrentPostAttribute("modified");

    return {
      postType: editor.getCurrentPostType(),
      postId: editor.getCurrentPostId(),
      savedAt: typeof modified === "string" ? modified : "",
      testableUrl: testablePostUrl(
        editor.getCurrentPostAttribute("status"),
        editor.getCurrentPostAttribute("link"),
      ),
    };
  }, []);
  const [meta, setMeta] = useEntityProp("postType", postType, "meta");

  if (!isSchemaMeta(meta) || typeof postId !== "number") {
    return null;
  }

  return (
    <PluginDocumentSettingPanel name="better-schema" title={__("Schema", "better-schema-markup")}>
      <SchemaPanelContent
        postId={postId}
        meta={meta}
        savedAt={savedAt}
        testableUrl={testableUrl}
        onChangeType={(type) => setMeta({ ...meta, _better_schema_type: type })}
      />
    </PluginDocumentSettingPanel>
  );
}

interface SchemaPanelContentProps {
  readonly postId: number;
  readonly meta: SchemaMeta;
  readonly savedAt: string;
  readonly testableUrl: string | null;
  readonly onChangeType: (type: SchemaType) => void;
}

// Separate from SchemaPanel so the preview hook only runs once the meta is known to be valid.
function SchemaPanelContent({
  postId,
  meta,
  savedAt,
  testableUrl,
  onChangeType,
}: SchemaPanelContentProps) {
  const schemaType = meta._better_schema_type;
  const preview = useSchemaPreview(postId, schemaType, savedAt);

  return (
    <>
      <SchemaTypePicker value={schemaType} onChange={onChangeType} />
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
