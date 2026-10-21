import { useEntityProp } from "@wordpress/core-data";
import { useSelect } from "@wordpress/data";
import { PluginDocumentSettingPanel, store as editorStore } from "@wordpress/editor";
import { __ } from "@wordpress/i18n";

import { testablePostUrl } from "./preview/rich-results-test";
import { SchemaEditorFields } from "./schema-editor-fields";
import { isSchemaMeta } from "../schema/schema-meta";

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
      <SchemaEditorFields
        postId={postId}
        meta={meta}
        savedAt={savedAt}
        testableUrl={testableUrl}
        onChangeType={(type) => setMeta({ ...meta, _better_schema_type: type })}
        onChangeDetail={(key, value) => setMeta({ ...meta, [key]: value })}
      />
    </PluginDocumentSettingPanel>
  );
}
