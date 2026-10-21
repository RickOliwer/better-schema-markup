import { __ } from "@wordpress/i18n";

import { Alert } from "./components/ui/alert";
import { Spinner } from "./components/ui/button";
import type { SchemaPreviewState } from "./use-schema-preview";

interface SchemaPreviewSectionProps {
  readonly state: SchemaPreviewState;
}

/** Validation hints and the JSON-LD the frontend would output. */
export function SchemaPreviewSection({ state }: SchemaPreviewSectionProps) {
  const { preview, isLoading, hasError } = state;

  if (hasError) {
    return <Alert tone="error">{__("The schema preview could not be loaded.", "better-schema-markup")}</Alert>;
  }

  if (!preview) {
    return <Spinner className="bsm:text-muted-foreground" />;
  }

  return (
    <div aria-busy={isLoading} className="bsm:grid bsm:min-w-0 bsm:gap-2">
      {preview.hints.map((hint) => (
        <Alert key={`${hint.type}-${hint.property}`} tone={hint.level === "required" ? "error" : "warning"}>
          {hint.message}
        </Alert>
      ))}
      {preview.document ? (
        <details className="bsm:group bsm:min-w-0 bsm:rounded-lg bsm:border bsm:border-solid bsm:border-border">
          <summary className="bsm:flex bsm:cursor-pointer bsm:list-none bsm:items-center bsm:justify-between bsm:gap-2 bsm:px-4 bsm:py-3 bsm:text-sm bsm:font-medium bsm:text-foreground bsm:select-none bsm:hover:bg-accent bsm:[&::-webkit-details-marker]:hidden">
            {__("Show JSON-LD", "better-schema-markup")}
            <svg className="bsm:text-muted-foreground bsm:transition-transform bsm:group-open:rotate-180" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5" aria-hidden="true">
              <path d="m4 6 4 4 4-4" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
          </summary>
          <div aria-hidden="true" className="bsm:h-px bsm:bg-border" />
          <div className="bsm:grid bsm:min-w-0 bsm:gap-2 bsm:p-4">
            <p className="bsm:m-0 bsm:text-sm bsm:text-muted-foreground">
              {__("What this page outputs, using the type and custom JSON-LD above. Save to include your other changes.", "better-schema-markup")}
            </p>
            <pre className="bsm:m-0 bsm:max-h-96 bsm:overflow-auto bsm:rounded-md bsm:bg-muted bsm:p-3 bsm:font-mono bsm:text-xs bsm:leading-relaxed bsm:text-foreground">
              {JSON.stringify(preview.document, null, 2)}
            </pre>
          </div>
        </details>
      ) : (
        <p className="bsm:m-0 bsm:text-sm bsm:text-muted-foreground">{__("This page outputs no schema.", "better-schema-markup")}</p>
      )}
    </div>
  );
}
