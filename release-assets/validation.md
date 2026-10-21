# Release validation: 0.19.1

Validated locally on 2026-10-21, on `fix/plugin-guidelines`.

## Results

- `vp check`: formatting, linting and TypeScript checks passed, including after building.
- `vp run test`: 61 Vitest tests; 269 PHPUnit tests, 922 assertions.
- New Vimeo consent and admin notice regression tests failed before the fixes and passed afterward. Existing Vimeo success/failure tests now opt in explicitly; their original assertions remain intact.
- Multisite uninstall: 2 tests, 19 assertions, including removal of the new consent option from another site and restoration of blog context.
- `vp build`, `vp run i18n`, and `vp run package` completed. Builds retain Base UI module-directive warnings. POT generation reports existing translator-comment and plural-placeholder warnings in editor/import strings; these are not new consent strings.
- Installed the 0.19.1 ZIP under the distributable slug on the disposable wp-env test site. Plugin Check 2.1.0, including runtime checks through its CLI bootstrap, reported no errors and no warning findings; no exclusions were used.
- Real Chrome smoke test on the installed ZIP: initial consent off, enable/save/reload, revoke/save/reload, no JavaScript errors. With Yoast active, the takeover notice appeared on Better Schema settings and not on the dashboard.
- ZIP inspection confirmed one plugin root, matching 0.19.1 versions, original TypeScript/build inputs, and the complete generated third-party notices. The nine bundled dependency packages declare MIT licenses; full upstream copyright and permission texts are included.

## Artifact

`dist/better-schema-markup-0.19.1.zip`: 332,380 bytes, 92 files.

SHA-256: `9b34dee19157d2c5f89c0e5f711070e026ff8e46c925248d08373abfa2cd2b99`

## Remaining owner action

Register a WordPress.org account, add its exact contributor username, rebuild/recheck the final ZIP, and submit it. Submission task 40 remains incomplete. No commits, pushes or WordPress.org submission were performed. These checks do not guarantee directory approval.

---

# Release validation: 0.16.0

Validated locally on 2026-10-20, on `feat/release-prep`.

## Versions

- WordPress 7.1.3; PHP 8.3 (wp-env).
- Yoast SEO 28.7 and Rank Math 1.0.280, activated individually.
- WPGraphQL 2.24.2; WooCommerce 11.2.1.
- Plugin Check 2.1.0, Chromium via local Google Chrome.

## Passed checks

- `vp check --no-fmt`: no lint warnings, lint errors or TypeScript errors.
- `vp run test`: 37 Vitest tests; 236 PHPUnit tests, 743 assertions.
- `vp build`, `vp run i18n`, `vp run package`: passed; POT extraction emitted no warnings.
- `WP_MULTISITE=1 vendor/bin/phpunit --filter UninstallTest` through wp-env: 2 tests, 17 assertions, including another site's cleanup and restoration of blog context.
- Full Plugin Check on the installed final ZIP, including runtime checks using `--require=.../plugin-check/cli.php`: no errors or warnings; no exclusion flags.
- Real Yoast and Rank Math, takeover enabled and disabled: exactly one valid JSON-LD graph on homepage, post, page and category archive. Source-plugin canonical, Open Graph, description and sitemap preserved. Better Schema preview and GraphQL available with either setting.
- No SEO plugin, both takeover settings: exactly one graph on all four page types; preview and GraphQL available.
- Clean ZIP installation without optional plugins: activation, first-run setup save, frontend graph and editor panel passed. The disposable site needed a stock theme activated and the editor's initial welcome guide dismissed.
- Direct HTTP access to uninstall.php exits without deleting saved data.
- Real WordPress deactivation preserves all six options and five meta keys. Real WordPress uninstall removes those values and video transients, preserving content and Yoast metadata.
- Temporary Swedish PHP MO and compiled-path JavaScript JSON loaded in settings and editor. Translated weekday labels rendered; saved weekday values remain English schema identifiers. Temporary catalogs removed.
- Browser regression: opening JSON preview previously produced a 621px scroll width inside a 248px grid. After the fix both measure 248px, with JSON scrolling within its own panel.
- CLI import dry run and batch importer tests cover existing choices, unsupported fields, malformed metadata, templates, safe repetition and 100-post batches. CLI saving and repetition were also exercised on fictional demo content.
- Four genuine WordPress UI screenshots captured using fictional Northstar Journal content. Banners exported from original vector artwork at 772×250 and 1544×500.

## Artifact

`dist/better-schema-markup-0.16.0.zip`: 176,605 bytes, 64 allowlisted files under one `better-schema-markup/` root. Includes original UI sources and build inputs; excludes tests, dependencies, secrets, development scripts and directory artwork. ZIP build output matches the tracked regenerated output.

SHA-256: `e7b49a89acc730ffde793f60a50334280d944346900bbed6bf47359670e2e613`

`dist/better-schema-markup-directory-assets.zip` contains the four screenshots and both PNG banners for the future WordPress.org assets directory.

## Remaining owner action

No WordPress.org account exists yet. Register the desired username (tentatively `r3`, subject to availability), add the verified username to `Contributors:` in readme.txt, rebuild/recheck the ZIP, and submit it. Submission and approval have not occurred. Roadmap task 40 intentionally remains unchecked. No Git commit or push was made.

See docs/release.md for repeatable packaging, compatibility testing, translation handling and submission steps.
