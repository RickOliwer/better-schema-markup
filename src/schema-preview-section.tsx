import { Notice, Spinner } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

import type { SchemaPreviewState } from "./use-schema-preview";

interface SchemaPreviewSectionProps {
  readonly state: SchemaPreviewState;
}

/** Validation hints and the JSON-LD the frontend would output. */
export function SchemaPreviewSection({ state }: SchemaPreviewSectionProps) {
  const { preview, isLoading, hasError } = state;

  if (hasError) {
    return (
      <Notice status="error" isDismissible={false}>
        {__("The schema preview could not be loaded.", "better-schema-markup")}
      </Notice>
    );
  }

  if (!preview) {
    return <Spinner />;
  }

  return (
    <div aria-busy={isLoading} style={{ display: "grid", gridTemplateColumns: "minmax(0, 1fr)", gap: "8px", marginTop: "8px" }}>
      {preview.hints.map((hint) => (
        <Notice
          key={`${hint.type}-${hint.property}`}
          status={hint.level === "required" ? "error" : "warning"}
          isDismissible={false}
        >
          {hint.message}
        </Notice>
      ))}
      {preview.document ? (
        <details>
          <summary>{__("Show JSON-LD", "better-schema-markup")}</summary>
          <p>
            {__(
              "What this page outputs, using the type and custom JSON-LD above. Save to include your other changes.",
              "better-schema-markup",
            )}
          </p>
          <pre style={{ overflowX: "auto", fontSize: "11px" }}>
            {JSON.stringify(preview.document, null, 2)}
          </pre>
        </details>
      ) : (
        <p>{__("This page outputs no schema.", "better-schema-markup")}</p>
      )}
    </div>
  );
}
