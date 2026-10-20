# Testing

## When to add tests

Test important observable behavior, regressions, edge cases, and integration boundaries. Do not add tests merely because a file or function changed.

Prioritize behavior that would be costly or hard to notice if it broke, especially generated schema, entity links, defaults, saved values, frontend/GraphQL consistency, and SEO-plugin compatibility.

For a bug fix, add a regression test when the bug can be reproduced reliably. For straightforward UI or refactoring changes, run existing checks and tests; add a new test only if it protects meaningful behavior.

Test behavior and public contracts rather than implementation details. Avoid tests for private call sequences or trivial wrappers.

## Test integrity

Treat existing tests as evidence of intended behavior. Do not weaken, skip, delete, or change their expectations merely to make an implementation pass.

Change a test expectation only when the requested behavior genuinely changed or the test is demonstrably wrong. Explain why it changed.

## PHP tests

Test schema generation as data. Prioritize tests for:

- graph shape and important entity properties;
- stable `@id` relationships;
- post and page context behavior;
- selected schema types and defaults;
- invalid or missing metadata; and
- developer filters.

Keep graph-builder tests independent of rendered HTML where possible.

Test frontend output separately from graph construction. Verify that its schema data matches the graph builder’s result.

## TypeScript tests

Use Vitest for meaningful behavior in type guards, validation, shared utilities, and editor UI.

Test invalid external values as well as valid values when those cases matter.

## Running tests

Run the relevant test suite during implementation. Before handoff, run the applicable project checks and tests:

`vp check --no-fmt`

`vp run test`

If PHP dependencies have not been installed, use:

`vp run composer install`
