import apiFetch from "@wordpress/api-fetch";
import { Button, Notice, SelectControl, ToggleControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useEffect, useState } from "react";

import { isImportReport } from "./import-report";
import type { ImportReport } from "./import-report";

export function SeoSettings() {
  const [takeover, setTakeover] = useState<boolean | null>(null);
  const [source, setSource] = useState<"yoast" | "rank-math">("yoast");
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [details, setDetails] = useState<ImportReport["details"]>([]);

  useEffect(() => {
    let active = true;
    void apiFetch({ path: "/wp/v2/settings" }).then((value: unknown) => {
      if (!active) return;
      if (typeof value !== "object" || value === null || !("better_schema_takeover" in value) || typeof value.better_schema_takeover !== "boolean") {
        throw new Error();
      }
      setTakeover(value.better_schema_takeover);
    }).catch(() => { if (active) setError(__("Could not load schema takeover settings.", "better-schema-markup")); });
    return () => { active = false; };
  }, []);

  async function saveTakeover(next: boolean) {
    setBusy(true);
    setError("");
    try {
      await apiFetch({ path: "/wp/v2/settings", method: "POST", data: { better_schema_takeover: next } });
      setTakeover(next);
      setMessage(__("Schema takeover setting saved.", "better-schema-markup"));
    } catch {
      setError(__("Could not save schema takeover settings.", "better-schema-markup"));
    } finally {
      setBusy(false);
    }
  }

  async function runImport(dryRun: boolean) {
    setBusy(true);
    setMessage("");
    setError("");
    setDetails([]);
    const total = { imported: 0, skipped: 0, unsupported: 0, failed: 0 };
    let cursor = 0;
    try {
      let done = false;
      while (!done) {
        const report: unknown = await apiFetch({ path: "/better-schema/v1/import", method: "POST", data: { source, cursor, dry_run: dryRun } });
        if (!isImportReport(report) || (!report.done && report.cursor <= cursor)) throw new Error();
        for (const key of ["imported", "skipped", "unsupported", "failed"] as const) total[key] += report[key];
        setDetails((previous) => [...previous, ...report.details]);
        cursor = report.cursor;
        done = report.done;
      }
      setMessage([
        `${__("Imported", "better-schema-markup")}: ${total.imported}`,
        `${__("Skipped", "better-schema-markup")}: ${total.skipped}`,
        `${__("With unsupported settings", "better-schema-markup")}: ${total.unsupported}`,
        `${__("Failed", "better-schema-markup")}: ${total.failed}`,
        dryRun ? __("Dry run only; nothing was saved.", "better-schema-markup") : __("Review the details below. Existing choices were preserved.", "better-schema-markup"),
      ].join(". "));
    } catch {
      setError(__("The import stopped. Previously completed batches were kept. You can safely retry; existing choices will be skipped.", "better-schema-markup"));
    } finally {
      setBusy(false);
    }
  }

  return <section>
    <h2>{__("SEO plugin compatibility", "better-schema-markup")}</h2>
    {error ? <Notice status="error" isDismissible={false}>{error}</Notice> : null}
    {message ? <Notice status="info" isDismissible={false}>{message}</Notice> : null}
    <ToggleControl
      label={__("Let Better Schema Markup handle schema", "better-schema-markup")}
      help={__("Replaces Yoast SEO and Rank Math schema only. When disabled with either plugin active, that plugin handles frontend schema. Previews and WPGraphQL remain available. This setting saves immediately.", "better-schema-markup")}
      checked={takeover === true}
      disabled={busy || takeover === null}
      onChange={(next) => { void saveTakeover(next); }}
      __nextHasNoMarginBottom
    />
    <h3>{__("Import saved schema settings", "better-schema-markup")}</h3>
    <p>{__("Import supported per-post types and details once. Source data and existing Better Schema choices are preserved. Unsupported fields and templates are reported for manual review. Run a preview first.", "better-schema-markup")}</p>
    <SelectControl label={__("Import from", "better-schema-markup")} value={source} disabled={busy}
      options={[{ label: __("Yoast SEO", "better-schema-markup"), value: "yoast" }, { label: __("Rank Math", "better-schema-markup"), value: "rank-math" }]}
      onChange={(value) => { if (value === "yoast" || value === "rank-math") setSource(value); }} __next40pxDefaultSize __nextHasNoMarginBottom />
    <p><Button variant="secondary" disabled={busy} onClick={() => { void runImport(true); }}>{__("Preview import", "better-schema-markup")}</Button>{" "}
      <Button variant="secondary" disabled={busy} isBusy={busy} onClick={() => { void runImport(false); }}>{__("Import settings", "better-schema-markup")}</Button></p>
    {details.length ? <details open><summary>{__("Import details", "better-schema-markup")}</summary><ul>{details.map((detail, index) =>
      <li key={index}><strong>{__("Post", "better-schema-markup")} {detail.post}: </strong>{detail.message}</li>,
    )}</ul></details> : null}
  </section>;
}
