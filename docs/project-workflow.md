# Project Workflow

## Toolchain

Use the project toolchain only:

- `vp build` to build JavaScript;
- `vp run dev` for watch rebuilding;
- `vp run start` and `vp run stop` for local WordPress;
- `vp run wp <arguments>` for WP-CLI;
- `vp check --no-fmt` for the current lint and type-check workflow; and
- `vp run test` for the test suites.

Do not use npm, pnpm, or Yarn.

## Versioning

For every feature or fix, update all of the following:

- `package.json`;
- the plugin header; and
- `readme.txt`, including a changelog line.

## Changes

Keep each task small and reviewable. Avoid unrelated cleanup in feature work.

Before implementation work, confirm whether to continue on the current branch or create a branch from the latest `main`.

Do not commit or push. After completing a task, provide one short suggested conventional commit message.

## Build output

Do not manually edit generated files under `build/`. Regenerate them with the project build command when required.

`vp build` creates two admin scripts: `build/index.js` for the block and classic editors, and `build/settings.js` with `build/settings.css` for the settings page. Each has a `.asset.php` file listing the WordPress scripts it depends on. `bin/package.py` must list every file under `build/` that the plugin loads.

`package.json` aliases `vite` to Vite+'s core (`@voidzero-dev/vite-plus-core`) and pins `vitest`, so Vite plugins such as `@tailwindcss/vite` use the same Vite as `vp`. Keep these versions aligned with `vite-plus` when upgrading.

## Documentation

Update user-facing or developer documentation when a change introduces new configuration, extension points, schema behavior, or workflow requirements.
