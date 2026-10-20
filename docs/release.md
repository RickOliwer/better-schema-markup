# Release preparation

Use `vp check --no-fmt`, `vp run test`, `vp build`, and `vp run i18n` before `vp run package`.
Packaging needs Python 3 and creates `dist/better-schema-markup-<version>.zip` from an explicit allowlist. Original UI sources and build inputs are included for GPL reproducibility; tests, dependencies, secrets, Git internals, and directory artwork are excluded. The ZIP has one `better-schema-markup` root. Do not upload a ZIP of the whole repository.

## Local integrations

The wp-env configuration downloads Yoast SEO, Rank Math and Plugin Check for local checks. wp-env may activate configured plugins on startup: deactivate both SEO plugins first, then activate only the one under test. Do not run Yoast and Rank Math together. Keep both inactive for the regular PHPUnit suite. Never run migration or uninstall checks on production data.

Test takeover enabled and disabled on a populated homepage, post, page and archive. With takeover enabled, check for one JSON-LD block, plus the SEO plugin's title, canonical, description, Open Graph data and sitemap. With takeover disabled, the SEO plugin owns frontend schema while Better Schema previews and WPGraphQL continue working. Complete Rank Math’s account-free setup (skip account connection) before testing its output. Repeat without another SEO plugin: Better Schema must still output its graph.

Run a dry import before saving. Test modern Rank Math Event, Recipe and FAQ metadata, Yoast Article/BlogPosting and article None, malformed metadata, unsupported types, templates, existing Better Schema choices and interrupted/repeated imports. Import source data is never deleted. Source defaults and block-generated schema are outside the importer; review all reported limitations before production takeover.

The REST import endpoint is `POST /better-schema/v1/import`, with `source` (`yoast` or `rank-math`), `cursor` (nonnegative offset, initially 0), and `dry_run` (default true). It requires `manage_options` and the standard WordPress REST authentication nonce for cookie-authenticated requests. Each response contains `imported`, `skipped`, `unsupported`, `failed`, `details` (post IDs and messages), the next `cursor`, and `done`. Counts can overlap: a post can import supported values and also report unsupported properties. Continue until `done`; the browser and CLI share the same batch implementation. Keep source posts unchanged during a run because the cursor is an offset in ascending IDs.

`better_schema_takeover` is a REST settings boolean, default true. The toggle saves independently of the main settings form.

## Plugin Check and clean install

Install the ZIP in a disposable clean WordPress instance, with the distributable slug, and run Plugin Check on that installed package. To enable runtime checks, invoke WP-CLI with `--require=/path/to/plugin-check/cli.php` before the `plugin check` command. Do not hide errors with ignore flags. Record the WordPress, PHP, SEO plugin and Plugin Check versions and results in `release-assets/validation.md`.

Verify activation without integrations, settings, editor and frontend output. Install WPGraphQL and WooCommerce for optional integration smoke checks. Run uninstall tests on disposable single-site and multisite installations. Confirm deletion removes only owned data, while deactivation preserves it.

## Translations

`vp run i18n` extracts PHP plus the actual compiled JavaScript into `languages/better-schema-markup.pot`. Original TypeScript is excluded from extraction to avoid duplicate references. PO files must use the compiled `build/index.js` source reference when generating JavaScript translation JSON with `wp i18n make-json`. Both editor and settings enqueue the same translated handle. Test with temporary translations; do not ship a fake locale.

## Directory materials and submission

Artwork belongs in `release-assets/` and will eventually be copied into the WordPress.org SVN `assets` directory. Screenshots show fictional demo content in the actual WordPress UI; their numbering matches the readme. Banners are provided at 772×250 and 1544×500.

The author is Rick Centerhall, https://rickcenterhall.com/. No verified WordPress.org contributor account exists yet. Create the intended account (tentatively `r3`, subject to availability), add its exact username to `Contributors:` in `readme.txt`, and rebuild and recheck the ZIP before submission. Do not use a display name or an unregistered username as the contributor.

Upload the validated plugin ZIP at https://wordpress.org/plugins/developers/add/. Submission and review are handled by the owner. The plugin slug is subject to directory approval. Do not mark roadmap task 40 complete until the actual submission is finished. No repository commits, pushes, or directory submission are part of this preparation.
