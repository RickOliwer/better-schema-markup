# Editor UI

## Scope

React and TypeScript are used only for WordPress admin and block-editor interfaces.

Editor UI saves configuration through registered WordPress post meta or options. PHP reads those saved values when building schema.

Do not implement frontend schema-generation logic in TypeScript.

Both editors share the schema type picker, detail fields, validation hints and JSON-LD preview. Gutenberg saves through registered post meta. The classic editor mounts these controls in a native Schema meta box and submits only changed meta fields with the post form. PHP verifies a post-specific nonce and edit permission, ignores autosaves/revisions and unsupported fields, and uses the existing sanitizers. Untouched fields retain their saved values or inherited defaults. Do not save classic editor schema changes independently of Update, Publish or Save Draft.

The preview uses the selected type, the custom JSON-LD and its mode from the editor, and the post's saved content and other detail fields. Custom JSON-LD is debounced before it is previewed. It does not persist changes. The backend API routing applies in both editors, including headless sites. Classic controls require JavaScript; if mounting fails, saving the post must preserve all existing schema values.

## Settings page

The settings page (`src/settings.tsx`) is built separately from the editor script (`src/index.tsx`), so the block editor never loads its code. It uses:

- Base UI (`@base-ui/react`) for interactive controls: Select, Switch, Checkbox and Popover;
- Tailwind CSS v4, with every class prefixed `bsm:`, utilities marked `!important` so they beat wp-admin's form styles, and no Preflight reset (see `src/settings.css`); and
- shadcn/ui-style components in `src/components/ui/`, combined into labelled fields in `src/settings-fields.tsx`.

Base UI popups render at the end of the page, so styles must not depend on an ancestor of the settings container. Theme colors are CSS variables on `:root` (`--bsm-color-*`).

The block editor sidebar and classic editor meta box keep WordPress components, so they match the rest of the editor.

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
