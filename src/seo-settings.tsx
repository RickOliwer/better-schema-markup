import { schemaApiFetch } from "./admin-api";
import { __ } from "@wordpress/i18n";
import { useEffect, useState } from "react";

import { Alert } from "./components/ui/alert";
import { Button } from "./components/ui/button";
import { FieldGroup, FieldSeparator, FieldSet } from "./components/ui/field";
import { HelpPopover } from "./components/ui/help-popover";
import { isImportReport } from "./import-report";
import { summarizeImport } from "./import-summary";
import { SelectField, SwitchField } from "./settings-fields";
import type { ImportReport } from "./import-report";

export function SeoSettings() {
  const [takeover, setTakeover] = useState<boolean | null>(null);
  const [source, setSource] = useState<"yoast" | "rank-math">("yoast");
  const [busy, setBusy] = useState(false);
  // Each message is shown next to the controls it belongs to.
  const [takeoverMessage, setTakeoverMessage] = useState("");
  const [takeoverError, setTakeoverError] = useState("");
  const [importMessage, setImportMessage] = useState("");
  const [importError, setImportError] = useState("");
  const [details, setDetails] = useState<ImportReport["details"]>([]);

  useEffect(() => {
    let active = true;
    void schemaApiFetch({ path: "/wp/v2/settings" }).then((value: unknown) => {
      if (!active) return;
      if (typeof value !== "object" || value === null || !("better_schema_takeover" in value) || typeof value.better_schema_takeover !== "boolean") {
        throw new Error();
      }
      setTakeover(value.better_schema_takeover);
    }).catch(() => { if (active) setTakeoverError(__("Could not load schema takeover settings.", "better-schema-markup")); });
    return () => { active = false; };
  }, []);

  async function saveTakeover(next: boolean) {
    setBusy(true);
    setTakeoverError("");
    setTakeoverMessage("");
    try {
      await schemaApiFetch({ path: "/wp/v2/settings", method: "POST", data: { better_schema_takeover: next } });
      setTakeover(next);
      setTakeoverMessage(__("Schema takeover setting saved.", "better-schema-markup"));
    } catch {
      setTakeoverError(__("Could not save schema takeover settings.", "better-schema-markup"));
    } finally {
      setBusy(false);
    }
  }

  async function runImport(dryRun: boolean) {
    setBusy(true);
    setImportMessage("");
    setImportError("");
    setDetails([]);
    const total = { imported: 0, skipped: 0, unsupported: 0, failed: 0 };
    let cursor = 0;
    try {
      let done = false;
      while (!done) {
        const report: unknown = await schemaApiFetch({ path: "/better-schema/v1/import", method: "POST", data: { source, cursor, dry_run: dryRun } });
        if (!isImportReport(report) || (!report.done && report.cursor <= cursor)) throw new Error();
        for (const key of ["imported", "skipped", "unsupported", "failed"] as const) total[key] += report[key];
        setDetails((previous) => [...previous, ...report.details]);
        cursor = report.cursor;
        done = report.done;
      }
      setImportMessage(summarizeImport(total, dryRun, source === "yoast" ? "Yoast SEO" : "Rank Math"));
    } catch {
      setImportError(__("The import stopped. Previously completed batches were kept. You can safely retry; existing choices will be skipped.", "better-schema-markup"));
    } finally {
      setBusy(false);
    }
  }

  return (
    <>
      <FieldSet
        title={__("SEO plugin compatibility", "better-schema-markup")}
        description={__("Choose whether Better Schema Markup replaces the schema of Yoast SEO and Rank Math.", "better-schema-markup")}
      >
        <FieldGroup>
          {takeoverError ? <Alert tone="error">{takeoverError}</Alert> : null}
          <SwitchField
            label={__("Let Better Schema Markup handle schema", "better-schema-markup")}
            help={__("Replaces Yoast SEO and Rank Math schema only. When disabled with either plugin active, that plugin handles frontend schema. Previews and WPGraphQL remain available. This setting saves immediately.", "better-schema-markup")}
            checked={takeover === true}
            disabled={busy || takeover === null}
            onChange={(next) => { void saveTakeover(next); }}
          />
          {takeoverMessage ? <Alert tone="success" onDismiss={() => setTakeoverMessage("")}>{takeoverMessage}</Alert> : null}
        </FieldGroup>
      </FieldSet>

      <FieldSeparator />
      <FieldSet
        title={
          <>
            {__("Import from Yoast SEO or Rank Math", "better-schema-markup")}
            <HelpPopover topic={__("Import from Yoast SEO or Rank Math", "better-schema-markup")}>
              {__("If you chose a schema type for individual posts in Yoast SEO or Rank Math, this copies those choices into Better Schema, so you do not have to set them again. Preview shows what would happen without saving anything. Your Yoast and Rank Math data is never changed, and posts that already have Better Schema settings are skipped. If you never set schema per post, there is nothing to import.", "better-schema-markup")}
            </HelpPopover>
          </>
        }
        description={__("Copy the schema types you set on individual posts in another SEO plugin. Start with Preview.", "better-schema-markup")}
      >
        <FieldGroup>
          <SelectField
            label={__("Import from", "better-schema-markup")}
            value={source}
            disabled={busy}
            options={[
              { label: __("Yoast SEO", "better-schema-markup"), value: "yoast" },
              { label: __("Rank Math", "better-schema-markup"), value: "rank-math" },
            ]}
            onChange={setSource}
          />
          <div className="bsm:flex bsm:gap-2">
            <Button variant="outline" disabled={busy} onClick={() => { void runImport(true); }}>
              {__("Preview import", "better-schema-markup")}
            </Button>
            <Button variant="outline" disabled={busy} isBusy={busy} onClick={() => { void runImport(false); }}>
              {__("Import settings", "better-schema-markup")}
            </Button>
          </div>
          {importError ? <Alert tone="error">{importError}</Alert> : null}
          {importMessage ? <Alert>{importMessage}</Alert> : null}
          {details.length ? (
            <details open className="bsm:text-sm">
              <summary className="bsm:cursor-pointer bsm:font-medium">{__("Import details", "better-schema-markup")}</summary>
              <ul className="bsm:mt-2 bsm:mb-0 bsm:grid bsm:gap-1 bsm:pl-0">
                {details.map((detail, index) => (
                  <li key={index} className="bsm:m-0 bsm:text-muted-foreground">
                    <strong className="bsm:text-foreground">{__("Post", "better-schema-markup")} {detail.post}: </strong>
                    {detail.message}
                  </li>
                ))}
              </ul>
            </details>
          ) : null}
        </FieldGroup>
      </FieldSet>
    </>
  );
}
