# Release validation: 0.16.1

Validated locally on 2026-10-20, on `fix/headless-admin-api`.

- `vp check --no-fmt`: no warnings, lint errors or TypeScript errors.
- `vp run test`: 44 Vitest tests and 238 PHPUnit tests (773 assertions) passed.
- PHP coverage includes matching and separate frontend/backend domains, subdirectory installations, HTTP/HTTPS admin requests, plain/pretty permalinks, and configuration enqueued before settings and editor scripts.
- Browser checks against local WordPress passed in standard mode and with a temporary, browser-scoped separate frontend URL: settings load/save, takeover changes, import preview/save, and editor previews. Each mode produced 10 authenticated backend API requests. WordPress's global API root remained unchanged, including the separate frontend root in headless mode.
- `vp build`, `vp run i18n`, and `vp run package` passed. Translation extraction emitted no warnings.
- ZIP integrity and contents checked: 66 files, one `better-schema-markup/` root, both new runtime helpers included, tests and dependencies excluded, compiled JavaScript matches the validated build.

Package: `dist/better-schema-markup-0.16.1.zip` (178529 bytes).

SHA256: `bfd40fd5b63f9382494f40cf6ef2d9573358a624d70f9563457520a4a53b627b`

These are local checks. The production backend must allow authenticated REST requests; production server restrictions were not changed or tested.
