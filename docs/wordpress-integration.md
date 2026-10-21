# WordPress Integration

## Scope

Integration files connect WordPress to the plugin’s domain logic. Keep them small and focused on:

- actions and filters;
- post meta and options registration;
- REST exposure;
- editor asset loading;
- request-context resolution;
- frontend JSON-LD output; and
- WPGraphQL fields and resolvers.

Do not put substantial schema-building rules directly in hook callbacks.

## Hooks and compatibility

When replacing Yoast SEO or Rank Math schema output, disable only their schema output. Preserve their other SEO features.

Verify third-party hook names against the currently supported plugin versions before adding compatibility code.

Use WordPress hook priorities deliberately. Document a non-obvious priority choice in a short comment.

## Metadata and settings

When storing plugin-owned data:

- use a `_better_schema_` meta key prefix;
- validate and sanitize data on registration and save;
- provide defaults in PHP;
- register `show_in_rest` when editor UI needs access; and
- treat missing, malformed, or legacy stored values safely.

## Admin API requests

Better Schema's settings, import and editor preview requests use the WordPress installation URL (`site_url`) with `index.php?rest_route=...`. The public home URL can belong to a separate headless frontend. The shared admin request helper passes explicit backend URLs to WordPress `apiFetch`, preserving its authentication and locale handling without changing global routing for other plugins. This works with standard sites, separate frontend domains, subdirectory installations and either permalink configuration. The backend must allow authenticated WordPress REST requests.

## Output

Frontend output functions should:

- resolve the current context;
- call the graph builder;
- JSON-encode the resulting graph safely; and
- render one JSON-LD script element only when there is useful graph data.

Do not duplicate graph-building logic between `wp_head` and WPGraphQL.

## Naming

- Prefix PHP functions with `better_schema_`.
- Prefix constants with `BETTER_SCHEMA_`.
- Use the `better-schema-markup` text domain.
- Use descriptive WordPress hook and filter names prefixed with `better_schema_`.

## Vimeo consent

`better_schema_vimeo_enabled` is a site-local boolean option, false by default, exposed through the authenticated WordPress settings REST API. Only administrators with `manage_options` may change it. The full settings form saves permission explicitly; completing first-run setup does not enable it. Existing installations must also opt in.

The shared Vimeo metadata helper checks permission before reading cached metadata or contacting Vimeo. With permission off, automatic Vimeo entities are omitted consistently from frontend output, previews and WPGraphQL. YouTube schema is unaffected. Uninstall removes the option on each site along with existing video caches.
