# PHP Conventions

## Style

Follow WordPress PHP coding standards and the repository’s existing patterns.

Prefer small functions with one clear responsibility. Use early returns to keep nesting shallow.

## Types and inputs

Treat WordPress values, stored metadata, and third-party filter input as untrusted.

- Validate values before using them.
- Cast only after validation makes the cast safe.
- Return predictable types.
- Use `null` or an empty result consistently when no schema can be produced.

Avoid hidden dependencies on global state in reusable code.

## Structure

Keep these responsibilities separate:

- WordPress registration and hooks;
- context resolution;
- graph construction;
- individual schema pieces; and
- JSON-LD rendering.

Use private helper functions only when they make a unit easier to read or test. Do not introduce classes merely for organization unless the task benefits from object state or a clear public contract.

## Security and output

Sanitize data at input boundaries. Escape only when rendering an HTML context.

Do not manually concatenate JSON. Use WordPress or PHP JSON encoding functions.

## Naming

Use descriptive snake_case names and the `better_schema_` prefix for globally available functions.

Avoid abbreviations unless they are standard WordPress or schema.org terms.
