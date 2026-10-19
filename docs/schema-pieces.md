# Schema Pieces

## Purpose

Each schema.org type is a small, focused schema piece. Examples include:

- `WebSite`;
- `Organization`;
- `WebPage`;
- `Article`;
- `BlogPosting`; and
- `BreadcrumbList`.

## Responsibilities

A schema piece should:

- receive explicit schema context and required shared data;
- return one entity or a small, tightly related set of entities;
- use `@id` references instead of duplicating related entities;
- contain the rules specific to its schema.org type;
- avoid rendering HTML; and
- avoid registering WordPress hooks.

## Extraction

Create a separate piece when a schema.org entity has its own rules, configuration, filter, or tests.

Do not extract trivial wrappers or one-off property assignments that are clearer where they are used.

## Extensibility

Provide focused filters that let developers refine or replace a piece without rebuilding the whole graph.

Filter names must use the `better_schema_` prefix. Pass the minimum useful context to filters and document the expected return value.

## Correctness

Prefer omitting an optional property to outputting a guessed, invalid, or misleading value.

Use canonical URLs and stable IDs. An entity’s `@id` should be predictable and reused whenever another entity refers to it.
