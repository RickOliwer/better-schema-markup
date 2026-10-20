# Better Schema Markup

Better Schema Markup is a WordPress plugin that generates schema.org JSON-LD for SEO and rich results.

Its goals are to:

- replace the schema output from Yoast SEO and Rank Math while preserving their other SEO features;
- provide the same schema data through WPGraphQL for headless sites; and
- produce useful, correct schema immediately after activation, with simple settings for refinement.

## Package manager and tooling

- Use Vite+ (`vp`) for project tooling. Bun is the underlying package manager, pinned in `package.json`.
- Use `vp build` to build JavaScript and `vp run dev` for watch rebuilding.
- Use `vp run start` and `vp run stop` for local WordPress through wp-env.
- Use `vp run wp <arguments>` to run WP-CLI.
- If the global `vp` command is unavailable, use `bunx vp <command>`.
- Do not use npm, pnpm, or Yarn.

## Repository safety

- Never add `.env` files, credentials, or secrets to version control.
- Do not create Git commits or push changes. The repository owner reviews and performs all commits and pushes.
- After every completed task, provide a short suggested conventional commit message, for example: `feat: add WebSite schema piece`.
- Before planning implementation work, ask whether to continue on the current branch or create a new branch from the latest `main` branch.
- If creating a branch, use a short, descriptive name such as `feat/graph-builder` or `fix/schema-output`.
- Do not modify tests to accommodate incorrect production behavior.

## Checks and tests

- Linting, formatting, and TypeScript settings are configured in `vite.config.ts`.
- After making changes, run `vp check --no-fmt` and fix all reported errors.
- Run `vp fmt` only when intentionally formatting files. A repository-wide formatting cleanup is tracked separately.
- Run all tests with `vp run test`.
- Write tests next to the code they test when that matches the existing repository pattern.
- Use the repository’s existing Vitest and PHPUnit test setup. Do not change test frameworks or imports without a task that requires it.

## Versioning

When adding a feature or fixing a bug, update all of the following:

- `version` in `package.json`;
- the plugin header version in `better-schema-markup.php`; and
- `readme.txt`, including a changelog entry.

## Naming

- Prefix PHP functions with `better_schema_`.
- Prefix PHP constants with `BETTER_SCHEMA_`.
- Use `better-schema-markup` as the WordPress text domain.

## Task-specific instructions

Read only the documentation relevant to the current task:

- Before creating or modifying schema-generation code, read `docs/schema-architecture.md`.
- Before creating or modifying a schema.org entity or its rules, read `docs/schema-pieces.md`.
- Before creating or modifying WordPress hooks, post meta, options, REST exposure, frontend output, or WPGraphQL integration, read `docs/wordpress-integration.md`.
- Before creating or modifying editor or settings UI, read `docs/editor-ui.md`.
- Before creating or modifying TypeScript code, read `docs/typescript-conventions.md`.
- Before creating or modifying PHP code, read `docs/php-conventions.md`.
- Before creating or modifying tests, read `docs/testing.md`.
- Before changing dependencies, build tooling, scripts, CI, releases, or repository metadata, read `docs/project-workflow.md`.


## Test integrity

- Treat existing tests as specifications of intended behavior.
- Do not change, weaken, skip, delete, or make a test less specific solely to make an implementation pass.
- When a test fails, first determine whether the implementation is wrong or the test is genuinely outdated because the requested behavior changed.
- Change an existing test only when the task explicitly changes the expected behavior, or when the test is demonstrably incorrect. Explain why the expectation changed.
- Do not replace meaningful assertions with weaker assertions such as checking only that a value exists, has a type, or matches a snapshot if the previous test verified important behavior.
- Do not add `.skip`, `.only`, broad mocks, or error suppression to hide a failure unless the task explicitly requires it.
- When fixing a bug, add a regression test that fails before the fix and passes after it.
