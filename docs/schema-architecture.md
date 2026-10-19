# Schema Architecture

## Core principle

PHP owns schema generation. React and TypeScript are only for WordPress editor and settings interfaces.

Generate one schema.org JSON-LD `@graph` from an explicit context. Reuse the same graph builder for frontend output and WPGraphQL.

## Context

Schema-generation code must receive an explicit context representing what is being described, such as:

- a post;
- a term; or
- a page type.

Do not use `is_singular()`, `is_archive()`, global `$post`, or other request globals inside the graph builder or schema pieces.

WordPress integration code may resolve the current request. It must then pass the resolved context to the graph builder.

## Graph builder

The graph builder is the single source of truth for schema output.

It must:

- accept explicit context;
- return PHP data representing a complete JSON-LD graph;
- produce entities linked through stable `@id` values;
- be reusable by `wp_head` and WPGraphQL;
- not render HTML; and
- expose filters at useful extension points.

Keep JSON encoding and `<script type="application/ld+json">` rendering separate from graph construction.

## Data flow

Use this direction of dependency:

`WordPress request or WPGraphQL resolver → explicit context → graph builder → schema pieces → graph data → output or GraphQL response`

Do not make schema pieces depend on output functions, editor UI, or GraphQL resolvers.
