import { __, _n, sprintf } from "@wordpress/i18n";

export interface ImportTotals {
  readonly imported: number;
  readonly skipped: number;
  readonly unsupported: number;
  readonly failed: number;
}

/**
 * Explains an import or preview in plain words. Every checked post is either
 * imported, skipped or failed; `unsupported` counts posts with settings that
 * could not be transferred, on top of those.
 *
 * @param totals Counts from all import batches.
 * @param dryRun Whether this was a preview, which saves nothing.
 * @param source Name of the plugin imported from, for example "Yoast SEO".
 */
export function summarizeImport(totals: ImportTotals, dryRun: boolean, source: string): string {
  const checked = totals.imported + totals.skipped + totals.failed;

  if (checked === 0) {
    return __("There are no posts to check.", "better-schema-markup");
  }

  const parts: string[] = [];

  if (totals.imported > 0) {
    parts.push(
      dryRun
        ? sprintf(
            /* translators: 1: number of posts that would be imported, 2: number of posts checked. */
            _n("Preview: %1$d of %2$d post would be imported.", "Preview: %1$d of %2$d posts would be imported.", checked, "better-schema-markup"),
            totals.imported,
            checked,
          )
        : sprintf(
            /* translators: 1: number of posts imported, 2: number of posts checked. */
            _n("Imported %1$d of %2$d post.", "Imported %1$d of %2$d posts.", checked, "better-schema-markup"),
            totals.imported,
            checked,
          ),
    );
  } else {
    parts.push(
      sprintf(
        /* translators: 1: "Preview: nothing to import." or "Nothing was imported.", 2: number of posts checked, 3: plugin name. */
        _n(
          "%1$s Your post has no %3$s schema choice to import.",
          "%1$s None of your %2$d posts have a %3$s schema choice to import.",
          checked,
          "better-schema-markup",
        ),
        dryRun ? __("Preview: nothing to import.", "better-schema-markup") : __("Nothing was imported.", "better-schema-markup"),
        checked,
        source,
      ),
    );
  }

  if (totals.imported > 0 && totals.skipped > 0) {
    parts.push(
      sprintf(
        /* translators: %d: number of posts skipped. */
        _n(
          "%d was skipped because it has no schema choice to import, or already has Better Schema settings.",
          "%d were skipped because they have no schema choice to import, or already have Better Schema settings.",
          totals.skipped,
          "better-schema-markup",
        ),
        totals.skipped,
      ),
    );
  }

  if (totals.unsupported > 0) {
    parts.push(
      sprintf(
        /* translators: %d: number of posts with settings that could not be transferred. */
        _n(
          "%d has settings that cannot be transferred. See the details below.",
          "%d have settings that cannot be transferred. See the details below.",
          totals.unsupported,
          "better-schema-markup",
        ),
        totals.unsupported,
      ),
    );
  }

  if (totals.failed > 0) {
    parts.push(
      sprintf(
        /* translators: %d: number of posts that failed. */
        _n("%d post failed. See the details below.", "%d posts failed. See the details below.", totals.failed, "better-schema-markup"),
        totals.failed,
      ),
    );
  }

  parts.push(dryRun ? __("Nothing was saved.", "better-schema-markup") : __("Your Yoast and Rank Math data was not changed.", "better-schema-markup"));

  return parts.join(" ");
}
