# Editor UI

## Scope

React and TypeScript are used only for WordPress admin and block-editor interfaces.

Editor UI saves configuration through registered WordPress post meta or options. PHP reads those saved values when building schema.

Do not implement frontend schema-generation logic in TypeScript.

Both editors share the schema type picker, detail fields, validation hints and JSON-LD preview. Gutenberg saves through registered post meta. The classic editor mounts these controls in a native Schema meta box and submits only changed meta fields with the post form. PHP verifies a post-specific nonce and edit permission, ignores autosaves/revisions and unsupported fields, and uses the existing sanitizers. Untouched fields retain their saved values or inherited defaults. Do not save classic editor schema changes independently of Update, Publish or Save Draft.

The preview uses the selected type, the custom JSON-LD and its mode from the editor, and the post's saved content and other detail fields. Custom JSON-LD is debounced before it is previewed. It does not persist changes. The backend API routing applies in both editors, including headless sites. Classic controls require JavaScript; if mounting fails, saving the post must preserve all existing schema values.

## Components and styling

The settings page and the schema fields in both editors share one set of components. There are two scripts: `src/index.tsx` for the block and classic editors, and `src/settings.tsx` for the settings page. Both import `src/admin.css`. They use:

- Base UI (`@base-ui/react`) for interactive controls: Select, Switch, Checkbox, RadioGroup and Popover;
- Tailwind CSS v4, with every class prefixed `bsm:`, utilities marked `!important` so they beat wp-admin's form styles, and no Preflight reset; and
- shadcn/ui-style components in `src/components/ui/`, combined into labelled fields in `src/settings-fields.tsx`.

Rules that follow from this setup:

- Wrap each form in `FormRoot`. `FieldRow` places fields side by side based on the form's width, not the screen's, so rows stack in the narrow block editor sidebar.
- Classes are not merged. Do not pass a `className` that conflicts with a component's own classes, and do not combine a utility with a conflicting one, such as `border-0` with `border`.
- Without Preflight, borders need an explicit style (`border-solid`), and a border on one side leaves the others at the browser default width. Use a 1px element as a divider instead.
- Base UI popups render at the end of the page, so styles must not depend on an ancestor of the form. Theme colors are CSS variables on `:root` (`--bsm-color-*`).
- The block editor panel itself is Gutenberg's `PluginDocumentSettingPanel`; only its contents use these components.

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

Keep shared UI utilities separate from feature-specific UI. Use the components in `src/components/ui/` before adding new ones.

## Data handling

Treat values from WordPress APIs, REST responses, and post meta as `unknown` until validated.

Use the schema-type guard and shared type definitions rather than duplicating string unions across components.

Show clear controls and descriptions. The default experience should work without requiring configuration.
