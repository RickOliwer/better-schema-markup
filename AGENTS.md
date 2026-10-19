# Better-Schema

This is a plugin that will allow users to add schema markdown to there wp sites for better SEO and better UX

## Package manager and tooling

* Use Vite+ (`vp`) for everything: `vp install`, `vp add <pkg>`, `vp remove <pkg>`, `vp run <script>`, `vp check`, `vp fmt`, `vp lint` and `vp test`. Bun is the underlying package manager (pinned in `package.json`).
* If the global `vp` isn't available, fall back to Bun: `bunx vp <command>`, `bun install` or `bun run <script>`.
* Do not use npm, pnpm, or Yarn.

## Repository safety

* Never add `.env` files, credentials, or secrets to version control.
* Do not create Git commits or push changes. The repository owner reviews and performs all commits and pushes.
* after every task give me a short commit msg like "feat: implemented a new form for recipies"
* **Confirm the branch before planning implementation work.** When asked to plan an
implementation task, ask whether to continue on the current branch or create a new branch from the latest `main` branch. If creating a branch, use a short, descriptive name, such as
`feat/profile-form` or `fix/checkout-validation`.

## Checks and tests

* Linting (Oxlint) and formatting (Oxfmt) are configured in the root `vite.config.ts`.
* After making changes, run `vp check` (format, lint and type check) and fix all errors. Run `vp fmt` to format.
* Run tests with `vp run test` from the repository root (all packages) or `vp test` inside a package.
* Write tests as `*.test.ts` next to the code they test and import from `vite-plus/test`, not `vitest`.

## Versioning

* When adding a feature or fixing a bug, bump `version` in the `package.json`.

## Task-specific instructions

Read only the documentation relevant to the current task:

* Before creating or modifying TypeScript code, read `docs/typescript-conventions.md`.
