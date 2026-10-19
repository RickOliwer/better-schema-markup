## TypeScript Conventions

- Keep TypeScript strict. Resolve type errors instead of weakening compiler options.
- Avoid `any`. Treat data from WordPress APIs, REST responses, post meta, options, and other external boundaries as `unknown` until it has been validated or narrowed.
- Use shared runtime validation and type guards for externally sourced values. Derive static types from runtime schemas when a schema library is already used; do not add Zod solely for simple string unions or small metadata objects.
- Use `import type` for type-only imports.
- Mark values `readonly` when callers should not mutate them. Do not mutate objects received from another layer.
- Choose descriptive domain names instead of generic names such as `Data`, `Item`, or `Result`.
- Keep schema type lists, validation, and type guards in dedicated modules when shared or independently testable.
- Do not duplicate PHP schema-generation rules in TypeScript. TypeScript is for editor and settings UI; PHP is the source of truth for generated schema.
- Follow consistent naming conventions:
  - Use `kebab-case` for folders and filenames, for example `schema-types.ts`, `type-picker.tsx`, and `use-post-schema-type.ts`.
  - Use `PascalCase` for React components, types, and classes, for example `SchemaTypePicker` and `SchemaType`.
  - Use `camelCase` for functions, variables, props, and hooks, for example `isSchemaType`, `selectedType`, and `usePostSchemaType`.
- Prefer named exports unless an existing local convention clearly calls for a default export.
- Use the WordPress packages and globals configured by the project build setup. Do not add dependencies that duplicate or conflict with externalized `@wordpress/*` packages.
