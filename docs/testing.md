# Testing

## General rule

Add or update tests for every behavior change.

Test behavior and public contracts rather than implementation details.

## PHP tests

Test schema generation as data. Cover:

- graph shape;
- entity properties;
- stable `@id` relationships;
- post and page context behavior;
- selected schema types;
- invalid or missing metadata;
- defaults; and
- developer filters.

Keep graph-builder tests independent of rendered HTML where possible.

Test frontend output separately from graph construction. Verify that output uses the graph builder rather than duplicating its logic.

## TypeScript tests

Use Vitest for:

- type guards;
- validation;
- shared utilities; and
- UI behavior where it has meaningful logic.

Test invalid external values as well as valid values.

## Running tests

Run the relevant test suite during implementation. Before handoff, run the applicable project checks and tests:

`vp check --no-fmt`

`vp run test`

If PHP dependencies have not been installed, use:

`vp run composer install`
