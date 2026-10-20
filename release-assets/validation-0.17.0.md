# Release validation: 0.17.0

Validated locally on 2026-10-20, on `feat/classic-editor`.

- `vp check --no-fmt`: no warnings, lint errors or TypeScript errors.
- `vp run test`: 47 Vitest tests and 245 PHPUnit tests (804 assertions) passed.
- Native save tests cover the classic meta box, post-specific nonces, edit permissions, revisions/autosaves, unknown and malformed submissions, sanitized values, JSON/text backslashes, explicit clearing and preservation of inherited defaults and untouched fields.
- Real browser checks with Disable Gutenberg 3.3.2 on WordPress 7.1.3/PHP 8.3 passed for posts, pages, a public REST-enabled custom post type and new auto-drafts. Event, Recipe, FAQ and custom JSON-LD fields saved through Save Draft/Publish; previews and the Google test link worked.
- A temporary separate frontend domain was used to verify classic editor saves, previews and No schema on a headless setup. The preview remained on the backend domain.
- Switching back to Gutenberg preserved the classic editor's saved FAQ and Recipe fields. Gutenberg save/reload, preview and settings screen passed, with no duplicate classic meta box.
- A database check confirmed that saving an untouched post in the classic editor did not create an explicit schema type override.
- `vp build`, `vp run i18n` and `vp run package` passed. Translation extraction emitted no warnings.
- Plugin Check 2.1.0 on the installed final ZIP, including runtime checks, passed with no errors or warnings and no exclusion flags.
- ZIP integrity, one plugin root, exclusion of tests/dependencies and exact equality with the validated source/build were verified. Temporary browser fixtures were removed and Disable Gutenberg was left inactive locally.

Package: `dist/better-schema-markup-0.17.0.zip` (183091 bytes, 70 files).

SHA256: `f6f580031b4b73e4ec9ddf72b851cbc908a788da203c81190f461bb0dfa67510`

Classic editor controls require JavaScript. Previews require authenticated backend REST access and use the selected type plus saved content/detail fields. Public custom post types retain the existing REST-enabled eligibility rule. These are local checks; no production configuration or GitHub release was changed.
