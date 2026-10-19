# Editor UI

## Scope

React and TypeScript are used only for WordPress admin and block-editor interfaces.

Editor UI saves configuration through registered WordPress post meta or options. PHP reads those saved values when building schema.

Do not implement frontend schema-generation logic in TypeScript.

## Entry points

Keep editor entry files focused on:

- registering the plugin or sidebar;
- loading top-level UI; and
- composing feature-level components.

Do not put substantial UI, validation, or data-access logic directly in an entry file.

## Components

Extract a component when it:

- represents a distinct interface section;
- owns meaningful state or event handling;
- contains substantial JSX;
- performs data access or validation; or
- is easier to understand or test independently.

Avoid extracting trivial wrappers that do not improve clarity.

Keep shared UI utilities separate from feature-specific UI. Prefer existing WordPress components before introducing a new dependency.

## Data handling

Treat values from WordPress APIs, REST responses, and post meta as `unknown` until validated.

Use the schema-type guard and shared type definitions rather than duplicating string unions across components.

Show clear controls and descriptions. The default experience should work without requiring configuration.
